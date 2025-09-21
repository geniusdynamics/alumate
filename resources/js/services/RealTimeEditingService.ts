import type { Editor } from 'grapesjs';
import { computed, reactive } from 'vue';

export interface DeviceMode {
    id: string;
    name: string;
    width: number;
    height: number;
    icon: string;
    breakpoint?: string;
}

export interface EditingState {
    selectedComponent: any | null;
    deviceMode: string;
    isEditing: boolean;
    hasUnsavedChanges: boolean;
    lastSaved: Date | null;
    autoSaveEnabled: boolean;
}

export interface UndoRedoState {
    history: any[];
    currentIndex: number;
    maxHistorySize: number;
}

export class RealTimeEditingService {
    private editor: Editor | null = null;
    private autoSaveInterval: NodeJS.Timeout | null = null;
    private changeDebounceTimeout: NodeJS.Timeout | null = null;

    // Reactive state
    public editingState = reactive<EditingState>({
        selectedComponent: null,
        deviceMode: 'desktop',
        isEditing: false,
        hasUnsavedChanges: false,
        lastSaved: null,
        autoSaveEnabled: true,
    });

    public undoRedoState = reactive<UndoRedoState>({
        history: [],
        currentIndex: -1,
        maxHistorySize: 50,
    });

    public devices: DeviceMode[] = [
        {
            id: 'desktop',
            name: 'Desktop',
            width: 1200,
            height: 800,
            icon: 'monitor',
            breakpoint: '1024px',
        },
        {
            id: 'tablet',
            name: 'Tablet',
            width: 768,
            height: 1024,
            icon: 'tablet',
            breakpoint: '768px',
        },
        {
            id: 'mobile',
            name: 'Mobile',
            width: 375,
            height: 667,
            icon: 'smartphone',
            breakpoint: '480px',
        },
    ];

    // Computed properties
    public canUndo = computed(() => this.undoRedoState.currentIndex > 0);
    public canRedo = computed(() => this.undoRedoState.currentIndex < this.undoRedoState.history.length - 1);

    public currentDevice = computed(() => this.devices.find((d) => d.id === this.editingState.deviceMode) || this.devices[0]);

    /**
     * Initialize the real-time editing service with a GrapeJS editor
     */
    public initialize(editor: Editor): void {
        this.editor = editor;
        this.setupEventListeners();
        this.setupDeviceManager();
        this.startAutoSave();
        this.initializeUndoRedo();
    }

    /**
     * Setup event listeners for real-time editing
     */
    private setupEventListeners(): void {
        if (!this.editor) return;

        // Component selection
        this.editor.on('component:selected', (component) => {
            this.editingState.selectedComponent = component;
            this.editingState.isEditing = true;
        });

        this.editor.on('component:deselected', () => {
            this.editingState.selectedComponent = null;
            this.editingState.isEditing = false;
        });

        // Content changes
        this.editor.on('component:update', () => {
            this.handleContentChange();
        });

        this.editor.on('component:add', () => {
            this.handleContentChange();
        });

        this.editor.on('component:remove', () => {
            this.handleContentChange();
        });

        // Style changes
        this.editor.on('style:update', () => {
            this.handleStyleChange();
        });

        // Canvas changes
        this.editor.on('canvas:update', () => {
            this.handleCanvasChange();
        });
    }

    /**
     * Setup device manager for responsive design
     */
    private setupDeviceManager(): void {
        if (!this.editor) return;

        // Configure GrapeJS device manager
        const deviceManager = this.editor.DeviceManager;

        // Clear existing devices
        deviceManager.getAll().forEach((device) => {
            deviceManager.remove(device.id);
        });

        // Add our custom devices
        this.devices.forEach((device) => {
            deviceManager.add({
                id: device.id,
                name: device.name,
                width: `${device.width}px`,
                height: `${device.height}px`,
                widthMedia: device.breakpoint,
            });
        });

        // Set default device
        deviceManager.select(this.editingState.deviceMode);

        // Listen for device changes
        this.editor.on('device:select', (device) => {
            this.editingState.deviceMode = device.id;
            this.updateCanvasForDevice(device.id);
        });
    }

    /**
     * Switch to a specific device mode
     */
    public switchDevice(deviceId: string): void {
        if (!this.editor) return;

        const device = this.devices.find((d) => d.id === deviceId);
        if (!device) return;

        this.editingState.deviceMode = deviceId;
        this.editor.DeviceManager.select(deviceId);
        this.updateCanvasForDevice(deviceId);
    }

    /**
     * Update canvas for specific device
     */
    private updateCanvasForDevice(deviceId: string): void {
        if (!this.editor) return;

        const device = this.devices.find((d) => d.id === deviceId);
        if (!device) return;

        const canvas = this.editor.Canvas;
        const canvasEl = canvas.getElement();

        if (canvasEl) {
            // Update canvas dimensions
            canvasEl.style.width = `${device.width}px`;
            canvasEl.style.height = `${device.height}px`;

            // Add device-specific classes for styling
            canvasEl.className = canvasEl.className.replace(/device-\w+/g, '');
            canvasEl.classList.add(`device-${deviceId}`);
        }

        // Trigger canvas update
        canvas.refresh();
    }

    /**
     * Handle content changes with debouncing
     */
    private handleContentChange(): void {
        this.editingState.hasUnsavedChanges = true;

        // Clear existing timeout
        if (this.changeDebounceTimeout) {
            clearTimeout(this.changeDebounceTimeout);
        }

        // Debounce the change handling
        this.changeDebounceTimeout = setTimeout(() => {
            this.saveToHistory();
            this.triggerLivePreviewUpdate();
        }, 300);
    }

    /**
     * Handle style changes with immediate feedback
     */
    private handleStyleChange(): void {
        this.editingState.hasUnsavedChanges = true;
        this.triggerLivePreviewUpdate();

        // Save to history after a short delay
        setTimeout(() => {
            this.saveToHistory();
        }, 500);
    }

    /**
     * Handle canvas changes
     */
    private handleCanvasChange(): void {
        this.editingState.hasUnsavedChanges = true;
        this.triggerLivePreviewUpdate();
    }

    /**
     * Trigger live preview update
     */
    private triggerLivePreviewUpdate(): void {
        if (!this.editor) return;

        // Get current editor data
        const html = this.editor.getHtml();
        const css = this.editor.getCss();

        // Emit event for preview update
        window.dispatchEvent(
            new CustomEvent('grapejs:preview-update', {
                detail: { html, css, device: this.editingState.deviceMode },
            }),
        );
    }

    /**
     * Initialize undo/redo functionality
     */
    private initializeUndoRedo(): void {
        if (!this.editor) return;

        // Save initial state
        this.saveToHistory();

        // Setup keyboard shortcuts
        this.setupUndoRedoShortcuts();
    }

    /**
     * Setup keyboard shortcuts for undo/redo
     */
    private setupUndoRedoShortcuts(): void {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key === 'z') {
                e.preventDefault();
                this.undo();
            } else if (((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'Z') || ((e.ctrlKey || e.metaKey) && e.key === 'y')) {
                e.preventDefault();
                this.redo();
            }
        });
    }

    /**
     * Save current state to history
     */
    private saveToHistory(): void {
        if (!this.editor) return;

        const currentState = {
            html: this.editor.getHtml(),
            css: this.editor.getCss(),
            components: this.editor.getComponents(),
            timestamp: Date.now(),
        };

        // Remove any states after current index (when undoing then making new changes)
        if (this.undoRedoState.currentIndex < this.undoRedoState.history.length - 1) {
            this.undoRedoState.history = this.undoRedoState.history.slice(0, this.undoRedoState.currentIndex + 1);
        }

        // Add new state
        this.undoRedoState.history.push(currentState);
        this.undoRedoState.currentIndex = this.undoRedoState.history.length - 1;

        // Limit history size
        if (this.undoRedoState.history.length > this.undoRedoState.maxHistorySize) {
            this.undoRedoState.history.shift();
            this.undoRedoState.currentIndex--;
        }
    }

    /**
     * Undo last change
     */
    public undo(): void {
        if (!this.canUndo.value || !this.editor) return;

        this.undoRedoState.currentIndex--;
        const state = this.undoRedoState.history[this.undoRedoState.currentIndex];

        if (state) {
            this.restoreState(state);
        }
    }

    /**
     * Redo last undone change
     */
    public redo(): void {
        if (!this.canRedo.value || !this.editor) return;

        this.undoRedoState.currentIndex++;
        const state = this.undoRedoState.history[this.undoRedoState.currentIndex];

        if (state) {
            this.restoreState(state);
        }
    }

    /**
     * Restore editor state
     */
    private restoreState(state: any): void {
        if (!this.editor) return;

        // Temporarily disable event listeners to prevent infinite loops
        this.editor.off('component:update');
        this.editor.off('component:add');
        this.editor.off('component:remove');
        this.editor.off('style:update');

        // Restore state
        this.editor.setComponents(state.components);
        this.editor.setStyle(state.css);

        // Re-enable event listeners
        setTimeout(() => {
            this.setupEventListeners();
            this.triggerLivePreviewUpdate();
        }, 100);
    }

    /**
     * Start auto-save functionality
     */
    private startAutoSave(): void {
        if (this.autoSaveInterval) return;

        this.autoSaveInterval = setInterval(() => {
            if (this.editingState.autoSaveEnabled && this.editingState.hasUnsavedChanges) {
                this.autoSave();
            }
        }, 30000); // Auto-save every 30 seconds
    }

    /**
     * Stop auto-save functionality
     */
    public stopAutoSave(): void {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = null;
        }
    }

    /**
     * Perform auto-save
     */
    private async autoSave(): Promise<void> {
        if (!this.editor) return;

        try {
            const data = {
                html: this.editor.getHtml(),
                css: this.editor.getCss(),
                components: this.editor.getComponents(),
            };

            // Emit auto-save event
            window.dispatchEvent(
                new CustomEvent('grapejs:auto-save', {
                    detail: data,
                }),
            );

            this.editingState.hasUnsavedChanges = false;
            this.editingState.lastSaved = new Date();
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }

    /**
     * Manual save
     */
    public async save(): Promise<void> {
        if (!this.editor) return;

        try {
            const data = {
                html: this.editor.getHtml(),
                css: this.editor.getCss(),
                components: this.editor.getComponents(),
            };

            // Emit save event
            window.dispatchEvent(
                new CustomEvent('grapejs:save', {
                    detail: data,
                }),
            );

            this.editingState.hasUnsavedChanges = false;
            this.editingState.lastSaved = new Date();
        } catch (error) {
            console.error('Save failed:', error);
            throw error;
        }
    }

    /**
     * Toggle auto-save
     */
    public toggleAutoSave(): void {
        this.editingState.autoSaveEnabled = !this.editingState.autoSaveEnabled;

        if (this.editingState.autoSaveEnabled) {
            this.startAutoSave();
        } else {
            this.stopAutoSave();
        }
    }

    /**
     * Get current editor data
     */
    public getEditorData(): any {
        if (!this.editor) return null;

        return {
            html: this.editor.getHtml(),
            css: this.editor.getCss(),
            components: this.editor.getComponents(),
            device: this.editingState.deviceMode,
        };
    }

    /**
     * Cleanup resources
     */
    public destroy(): void {
        this.stopAutoSave();

        if (this.changeDebounceTimeout) {
            clearTimeout(this.changeDebounceTimeout);
        }

        // Remove event listeners
        document.removeEventListener('keydown', this.setupUndoRedoShortcuts);
    }
}

// Create singleton instance
export const realTimeEditingService = new RealTimeEditingService();
