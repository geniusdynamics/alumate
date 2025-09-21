import { reactive } from 'vue';

export interface AutoSaveConfig {
    enabled: boolean;
    interval: number; // in milliseconds
    maxRetries: number;
    retryDelay: number; // in milliseconds
    debounceDelay: number; // in milliseconds
}

export interface AutoSaveState {
    isEnabled: boolean;
    isSaving: boolean;
    lastSaved: Date | null;
    hasUnsavedChanges: boolean;
    saveCount: number;
    errorCount: number;
    lastError: string | null;
}

export interface SaveData {
    html: string;
    css: string;
    components: any;
    timestamp: number;
}

export class AutoSaveService {
    private config: AutoSaveConfig;
    private saveInterval: NodeJS.Timeout | null = null;
    private debounceTimeout: NodeJS.Timeout | null = null;
    private retryTimeout: NodeJS.Timeout | null = null;
    private saveCallback: ((data: SaveData) => Promise<void>) | null = null;

    public state = reactive<AutoSaveState>({
        isEnabled: true,
        isSaving: false,
        lastSaved: null,
        hasUnsavedChanges: false,
        saveCount: 0,
        errorCount: 0,
        lastError: null,
    });

    constructor(config: Partial<AutoSaveConfig> = {}) {
        this.config = {
            enabled: true,
            interval: 30000, // 30 seconds
            maxRetries: 3,
            retryDelay: 5000, // 5 seconds
            debounceDelay: 2000, // 2 seconds
            ...config,
        };

        this.state.isEnabled = this.config.enabled;
    }

    /**
     * Initialize auto-save with a save callback
     */
    public initialize(saveCallback: (data: SaveData) => Promise<void>): void {
        this.saveCallback = saveCallback;

        if (this.config.enabled) {
            this.start();
        }
    }

    /**
     * Start auto-save functionality
     */
    public start(): void {
        if (!this.saveCallback) {
            console.warn('AutoSaveService: No save callback provided');
            return;
        }

        this.state.isEnabled = true;
        this.startInterval();
    }

    /**
     * Stop auto-save functionality
     */
    public stop(): void {
        this.state.isEnabled = false;
        this.clearInterval();
        this.clearDebounce();
        this.clearRetry();
    }

    /**
     * Trigger a save with debouncing
     */
    public triggerSave(data: SaveData, immediate: boolean = false): void {
        if (!this.state.isEnabled || !this.saveCallback) return;

        this.state.hasUnsavedChanges = true;

        if (immediate) {
            this.performSave(data);
        } else {
            this.debouncedSave(data);
        }
    }

    /**
     * Perform immediate save
     */
    public async saveNow(data: SaveData): Promise<void> {
        if (!this.saveCallback) {
            throw new Error('No save callback provided');
        }

        return this.performSave(data);
    }

    /**
     * Update auto-save configuration
     */
    public updateConfig(newConfig: Partial<AutoSaveConfig>): void {
        const wasEnabled = this.state.isEnabled;

        this.config = { ...this.config, ...newConfig };
        this.state.isEnabled = this.config.enabled;

        if (wasEnabled !== this.state.isEnabled) {
            if (this.state.isEnabled) {
                this.start();
            } else {
                this.stop();
            }
        } else if (this.state.isEnabled) {
            // Restart with new interval if changed
            this.clearInterval();
            this.startInterval();
        }
    }

    /**
     * Get current configuration
     */
    public getConfig(): AutoSaveConfig {
        return { ...this.config };
    }

    /**
     * Get save statistics
     */
    public getStats(): {
        saveCount: number;
        errorCount: number;
        successRate: number;
        lastSaved: Date | null;
        lastError: string | null;
    } {
        const total = this.state.saveCount + this.state.errorCount;
        const successRate = total > 0 ? (this.state.saveCount / total) * 100 : 100;

        return {
            saveCount: this.state.saveCount,
            errorCount: this.state.errorCount,
            successRate: Math.round(successRate),
            lastSaved: this.state.lastSaved,
            lastError: this.state.lastError,
        };
    }

    /**
     * Clear error state
     */
    public clearError(): void {
        this.state.lastError = null;
    }

    /**
     * Start the auto-save interval
     */
    private startInterval(): void {
        this.clearInterval();

        this.saveInterval = setInterval(() => {
            if (this.state.hasUnsavedChanges && !this.state.isSaving) {
                // Emit event to request current data
                window.dispatchEvent(new CustomEvent('autosave:request-data'));
            }
        }, this.config.interval);
    }

    /**
     * Clear the auto-save interval
     */
    private clearInterval(): void {
        if (this.saveInterval) {
            clearInterval(this.saveInterval);
            this.saveInterval = null;
        }
    }

    /**
     * Debounced save to avoid too frequent saves
     */
    private debouncedSave(data: SaveData): void {
        this.clearDebounce();

        this.debounceTimeout = setTimeout(() => {
            this.performSave(data);
        }, this.config.debounceDelay);
    }

    /**
     * Clear debounce timeout
     */
    private clearDebounce(): void {
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = null;
        }
    }

    /**
     * Clear retry timeout
     */
    private clearRetry(): void {
        if (this.retryTimeout) {
            clearTimeout(this.retryTimeout);
            this.retryTimeout = null;
        }
    }

    /**
     * Perform the actual save operation
     */
    private async performSave(data: SaveData, retryCount: number = 0): Promise<void> {
        if (!this.saveCallback || this.state.isSaving) return;

        this.state.isSaving = true;
        this.state.lastError = null;

        try {
            await this.saveCallback(data);

            // Save successful
            this.state.hasUnsavedChanges = false;
            this.state.lastSaved = new Date();
            this.state.saveCount++;

            // Emit success event
            window.dispatchEvent(
                new CustomEvent('autosave:success', {
                    detail: {
                        timestamp: this.state.lastSaved,
                        saveCount: this.state.saveCount,
                    },
                }),
            );
        } catch (error) {
            console.error('Auto-save failed:', error);

            this.state.errorCount++;
            this.state.lastError = error instanceof Error ? error.message : 'Unknown error';

            // Retry logic
            if (retryCount < this.config.maxRetries) {
                this.scheduleRetry(data, retryCount + 1);
            } else {
                // Emit error event after max retries
                window.dispatchEvent(
                    new CustomEvent('autosave:error', {
                        detail: {
                            error: this.state.lastError,
                            retryCount,
                            maxRetries: this.config.maxRetries,
                        },
                    }),
                );
            }
        } finally {
            this.state.isSaving = false;
        }
    }

    /**
     * Schedule a retry after delay
     */
    private scheduleRetry(data: SaveData, retryCount: number): void {
        this.clearRetry();

        const delay = this.config.retryDelay * Math.pow(2, retryCount - 1); // Exponential backoff

        this.retryTimeout = setTimeout(() => {
            this.performSave(data, retryCount);
        }, delay);

        // Emit retry event
        window.dispatchEvent(
            new CustomEvent('autosave:retry', {
                detail: {
                    retryCount,
                    delay,
                    maxRetries: this.config.maxRetries,
                },
            }),
        );
    }

    /**
     * Cleanup resources
     */
    public destroy(): void {
        this.stop();
        this.saveCallback = null;
    }
}

// Create singleton instance
export const autoSaveService = new AutoSaveService();

// Global event listeners for auto-save coordination
window.addEventListener('autosave:request-data', () => {
    // This event is listened to by components that have the current data
    // They should respond by calling autoSaveService.triggerSave()
});

// Utility function to format auto-save status
export function formatAutoSaveStatus(state: AutoSaveState): string {
    if (state.isSaving) {
        return 'Saving...';
    }

    if (!state.isEnabled) {
        return 'Auto-save disabled';
    }

    if (state.lastError) {
        return `Save failed: ${state.lastError}`;
    }

    if (!state.hasUnsavedChanges && state.lastSaved) {
        const now = new Date();
        const diff = now.getTime() - state.lastSaved.getTime();

        if (diff < 60000) {
            return 'Saved just now';
        } else if (diff < 3600000) {
            return `Saved ${Math.floor(diff / 60000)}m ago`;
        } else {
            return `Saved ${Math.floor(diff / 3600000)}h ago`;
        }
    }

    if (state.hasUnsavedChanges) {
        return 'Unsaved changes';
    }

    return 'Ready';
}

// Utility function to get auto-save icon
export function getAutoSaveIcon(state: AutoSaveState): string {
    if (state.isSaving) {
        return 'spinner';
    }

    if (state.lastError) {
        return 'alert-circle';
    }

    if (!state.hasUnsavedChanges && state.lastSaved) {
        return 'check-circle';
    }

    if (state.hasUnsavedChanges) {
        return 'clock';
    }

    return 'save';
}
