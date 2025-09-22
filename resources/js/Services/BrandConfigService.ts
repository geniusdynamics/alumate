export interface BrandColors {
    primary: string;
    secondary: string;
    accent: string;
    neutral: string;
    success: string;
    warning: string;
    error: string;
    info: string;
    [key: string]: string;
}

export interface TypographySettings {
    fontFamilies: string[];
    fontSizes: string[];
    lineHeights: string[];
    letterSpacing: string[];
}

export interface SpacingSettings {
    margins: string[];
    paddings: string[];
    gaps: string[];
}

export interface BorderRadiusSettings {
    small: string;
    medium: string;
    large: string;
    full: string;
}

export interface BrandConfig {
    tenantId: string;
    colors: BrandColors;
    typography: TypographySettings;
    spacing: SpacingSettings;
    borderRadius: BorderRadiusSettings;
    updatedAt: string;
}

export class BrandConfigService {
    private static instance: BrandConfigService;
    private brandConfigs: Map<string, BrandConfig> = new Map();

    private constructor() {}

    static getInstance(): BrandConfigService {
        if (!BrandConfigService.instance) {
            BrandConfigService.instance = new BrandConfigService();
        }
        return BrandConfigService.instance;
    }

    async getBrandConfig(tenantId: string): Promise<BrandConfig> {
        // Check cache first
        if (this.brandConfigs.has(tenantId)) {
            return this.brandConfigs.get(tenantId)!;
        }

        try {
            // Fetch from backend
            const response = await fetch(`/api/tenants/${tenantId}/brand-config`);
            if (!response.ok) {
                throw new Error('Failed to fetch brand config');
            }

            const config = await response.json();
            this.brandConfigs.set(tenantId, config);
            return config;
        } catch (error) {
            console.error('Failed to load brand config:', error);
            // Return default brand config
            return this.getDefaultBrandConfig(tenantId);
        }
    }

    async saveBrandConfig(tenantId: string, config: Partial<BrandConfig>): Promise<void> {
        try {
            const response = await fetch(`/api/tenants/${tenantId}/brand-config`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(config),
            });

            if (!response.ok) {
                throw new Error('Failed to save brand config');
            }

            // Update cache
            const existingConfig = this.brandConfigs.get(tenantId);
            if (existingConfig) {
                this.brandConfigs.set(tenantId, { ...existingConfig, ...config });
            }
        } catch (error) {
            console.error('Failed to save brand config:', error);
            throw error;
        }
    }

    getBrandColors(tenantId: string): Promise<BrandColors> {
        return this.getBrandConfig(tenantId).then((config) => config.colors);
    }

    getTypographySettings(tenantId: string): Promise<TypographySettings> {
        return this.getBrandConfig(tenantId).then((config) => config.typography);
    }

    getSpacingSettings(tenantId: string): Promise<SpacingSettings> {
        return this.getBrandConfig(tenantId).then((config) => config.spacing);
    }

    private getDefaultBrandConfig(tenantId: string): BrandConfig {
        return {
            tenantId,
            colors: {
                primary: '#3B82F6',
                secondary: '#64748B',
                accent: '#F59E0B',
                neutral: '#6B7280',
                success: '#10B981',
                warning: '#F59E0B',
                error: '#EF4444',
                info: '#3B82F6',
            },
            typography: {
                fontFamilies: ['Inter, sans-serif', 'system-ui, sans-serif', 'Arial, sans-serif', 'Georgia, serif'],
                fontSizes: ['0.75rem', '0.875rem', '1rem', '1.125rem', '1.25rem', '1.5rem', '2rem', '2.5rem', '3rem', '4rem'],
                lineHeights: ['1', '1.25', '1.5', '1.75', '2'],
                letterSpacing: ['-0.025em', '0', '0.025em', '0.05em', '0.1em'],
            },
            spacing: {
                margins: ['0', '0.25rem', '0.5rem', '1rem', '1.5rem', '2rem', '3rem', '4rem'],
                paddings: ['0', '0.25rem', '0.5rem', '1rem', '1.5rem', '2rem', '3rem', '4rem'],
                gaps: ['0', '0.25rem', '0.5rem', '1rem', '1.5rem', '2rem', '3rem', '4rem'],
            },
            borderRadius: {
                small: '0.25rem',
                medium: '0.5rem',
                large: '0.75rem',
                full: '9999px',
            },
            updatedAt: new Date().toISOString(),
        };
    }

    // Clear cache for a specific tenant
    clearCache(tenantId: string): void {
        this.brandConfigs.delete(tenantId);
    }

    // Clear all cache
    clearAllCache(): void {
        this.brandConfigs.clear();
    }
}

// Export singleton instance
export const brandConfigService = BrandConfigService.getInstance();
