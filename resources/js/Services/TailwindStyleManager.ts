/**
 * TailwindStyleManager - Integrates Tailwind CSS classes with GrapeJS Style Manager
 * Provides advanced styling controls with Tailwind CSS integration
 */

import type { Editor } from 'grapesjs';

export interface TailwindColor {
    name: string;
    value: string;
    category: 'primary' | 'secondary' | 'accent' | 'neutral' | 'semantic';
}

export interface TailwindSpacing {
    name: string;
    value: string;
    pixels: number;
}

export interface TailwindFont {
    name: string;
    family: string;
    weights: number[];
    category: 'sans' | 'serif' | 'mono';
}

export interface StylePreset {
    id: string;
    name: string;
    description: string;
    category: string;
    styles: Record<string, any>;
    tailwindClasses: string[];
    createdAt: Date;
    updatedAt: Date;
}

export class TailwindStyleManager {
    private editor: Editor;
    private brandColors: TailwindColor[] = [];
    private customSpacing: TailwindSpacing[] = [];
    private brandFonts: TailwindFont[] = [];
    private stylePresets: StylePreset[] = [];

    constructor(editor: Editor) {
        this.editor = editor;
        this.initializeTailwindIntegration();
        this.loadBrandGuidelines();
    }

    /**
     * Initialize Tailwind CSS integration with GrapeJS Style Manager
     */
    private initializeTailwindIntegration(): void {
        const styleManager = this.editor.StyleManager;

        // Configure Tailwind-aware style sectors
        styleManager.addSector('tailwind-layout', {
            name: 'Layout',
            open: true,
            properties: [
                {
                    type: 'select',
                    name: 'Display',
                    property: 'display',
                    defaults: 'block',
                    options: [
                        { value: 'block', name: 'Block' },
                        { value: 'inline-block', name: 'Inline Block' },
                        { value: 'flex', name: 'Flex' },
                        { value: 'inline-flex', name: 'Inline Flex' },
                        { value: 'grid', name: 'Grid' },
                        { value: 'hidden', name: 'Hidden' },
                    ],
                },
                {
                    type: 'select',
                    name: 'Position',
                    property: 'position',
                    defaults: 'static',
                    options: [
                        { value: 'static', name: 'Static' },
                        { value: 'relative', name: 'Relative' },
                        { value: 'absolute', name: 'Absolute' },
                        { value: 'fixed', name: 'Fixed' },
                        { value: 'sticky', name: 'Sticky' },
                    ],
                },
                {
                    type: 'select',
                    name: 'Flex Direction',
                    property: 'flex-direction',
                    defaults: 'row',
                    options: [
                        { value: 'row', name: 'Row' },
                        { value: 'row-reverse', name: 'Row Reverse' },
                        { value: 'column', name: 'Column' },
                        { value: 'column-reverse', name: 'Column Reverse' },
                    ],
                },
                {
                    type: 'select',
                    name: 'Justify Content',
                    property: 'justify-content',
                    defaults: 'flex-start',
                    options: [
                        { value: 'flex-start', name: 'Start' },
                        { value: 'center', name: 'Center' },
                        { value: 'flex-end', name: 'End' },
                        { value: 'space-between', name: 'Space Between' },
                        { value: 'space-around', name: 'Space Around' },
                        { value: 'space-evenly', name: 'Space Evenly' },
                    ],
                },
                {
                    type: 'select',
                    name: 'Align Items',
                    property: 'align-items',
                    defaults: 'stretch',
                    options: [
                        { value: 'stretch', name: 'Stretch' },
                        { value: 'flex-start', name: 'Start' },
                        { value: 'center', name: 'Center' },
                        { value: 'flex-end', name: 'End' },
                        { value: 'baseline', name: 'Baseline' },
                    ],
                },
            ],
        });

        // Tailwind Spacing sector
        styleManager.addSector('tailwind-spacing', {
            name: 'Spacing',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Padding',
                    property: 'padding',
                    defaults: '0',
                    options: this.getTailwindSpacingOptions(),
                },
                {
                    type: 'select',
                    name: 'Margin',
                    property: 'margin',
                    defaults: '0',
                    options: this.getTailwindSpacingOptions(),
                },
                {
                    type: 'select',
                    name: 'Gap',
                    property: 'gap',
                    defaults: '0',
                    options: this.getTailwindSpacingOptions(),
                },
            ],
        });

        // Tailwind Typography sector
        styleManager.addSector('tailwind-typography', {
            name: 'Typography',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Font Family',
                    property: 'font-family',
                    defaults: 'sans-serif',
                    options: this.getTailwindFontOptions(),
                },
                {
                    type: 'select',
                    name: 'Font Size',
                    property: 'font-size',
                    defaults: '1rem',
                    options: this.getTailwindFontSizeOptions(),
                },
                {
                    type: 'select',
                    name: 'Font Weight',
                    property: 'font-weight',
                    defaults: '400',
                    options: this.getTailwindFontWeightOptions(),
                },
                {
                    type: 'select',
                    name: 'Text Align',
                    property: 'text-align',
                    defaults: 'left',
                    options: [
                        { value: 'left', name: 'Left' },
                        { value: 'center', name: 'Center' },
                        { value: 'right', name: 'Right' },
                        { value: 'justify', name: 'Justify' },
                    ],
                },
                {
                    type: 'select',
                    name: 'Line Height',
                    property: 'line-height',
                    defaults: '1.5',
                    options: this.getTailwindLineHeightOptions(),
                },
            ],
        });

        // Tailwind Colors sector
        styleManager.addSector('tailwind-colors', {
            name: 'Colors',
            open: false,
            properties: [
                {
                    type: 'color',
                    name: 'Text Color',
                    property: 'color',
                    defaults: '#000000',
                },
                {
                    type: 'color',
                    name: 'Background Color',
                    property: 'background-color',
                    defaults: 'transparent',
                },
                {
                    type: 'color',
                    name: 'Border Color',
                    property: 'border-color',
                    defaults: 'transparent',
                },
            ],
        });

        // Tailwind Effects sector
        styleManager.addSector('tailwind-effects', {
            name: 'Effects',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Shadow',
                    property: 'box-shadow',
                    defaults: 'none',
                    options: this.getTailwindShadowOptions(),
                },
                {
                    type: 'slider',
                    name: 'Opacity',
                    property: 'opacity',
                    defaults: 1,
                    min: 0,
                    max: 1,
                    step: 0.1,
                },
                {
                    type: 'select',
                    name: 'Border Radius',
                    property: 'border-radius',
                    defaults: '0',
                    options: this.getTailwindBorderRadiusOptions(),
                },
                {
                    type: 'slider',
                    name: 'Border Width',
                    property: 'border-width',
                    defaults: 0,
                    min: 0,
                    max: 8,
                    step: 1,
                    units: ['px'],
                },
            ],
        });
    }

    /**
     * Load brand guidelines and design system configuration
     */
    private async loadBrandGuidelines(): Promise<void> {
        try {
            // Load brand colors
            this.brandColors = [
                { name: 'Primary Blue', value: '#3B82F6', category: 'primary' },
                { name: 'Primary Dark', value: '#1E40AF', category: 'primary' },
                { name: 'Secondary Green', value: '#10B981', category: 'secondary' },
                { name: 'Accent Orange', value: '#F59E0B', category: 'accent' },
                { name: 'Neutral Gray', value: '#6B7280', category: 'neutral' },
                { name: 'Success Green', value: '#059669', category: 'semantic' },
                { name: 'Warning Yellow', value: '#D97706', category: 'semantic' },
                { name: 'Error Red', value: '#DC2626', category: 'semantic' },
            ];

            // Load brand fonts
            this.brandFonts = [
                { name: 'Inter', family: 'Inter, sans-serif', weights: [300, 400, 500, 600, 700], category: 'sans' },
                { name: 'Roboto', family: 'Roboto, sans-serif', weights: [300, 400, 500, 700], category: 'sans' },
                { name: 'Playfair Display', family: 'Playfair Display, serif', weights: [400, 500, 600, 700], category: 'serif' },
                { name: 'JetBrains Mono', family: 'JetBrains Mono, monospace', weights: [400, 500, 600], category: 'mono' },
            ];

            // Load custom spacing
            this.customSpacing = [
                { name: 'xs', value: '0.5rem', pixels: 8 },
                { name: 'sm', value: '0.75rem', pixels: 12 },
                { name: 'md', value: '1rem', pixels: 16 },
                { name: 'lg', value: '1.5rem', pixels: 24 },
                { name: 'xl', value: '2rem', pixels: 32 },
                { name: '2xl', value: '3rem', pixels: 48 },
                { name: '3xl', value: '4rem', pixels: 64 },
            ];

            // Load existing style presets
            await this.loadStylePresets();
        } catch (error) {
            console.error('Failed to load brand guidelines:', error);
        }
    }

    /**
     * Get Tailwind spacing options for dropdowns
     */
    private getTailwindSpacingOptions(): Array<{ value: string; name: string }> {
        const spacingScale = [
            { value: '0', name: '0' },
            { value: '0.125rem', name: '0.5 (2px)' },
            { value: '0.25rem', name: '1 (4px)' },
            { value: '0.375rem', name: '1.5 (6px)' },
            { value: '0.5rem', name: '2 (8px)' },
            { value: '0.625rem', name: '2.5 (10px)' },
            { value: '0.75rem', name: '3 (12px)' },
            { value: '0.875rem', name: '3.5 (14px)' },
            { value: '1rem', name: '4 (16px)' },
            { value: '1.25rem', name: '5 (20px)' },
            { value: '1.5rem', name: '6 (24px)' },
            { value: '1.75rem', name: '7 (28px)' },
            { value: '2rem', name: '8 (32px)' },
            { value: '2.25rem', name: '9 (36px)' },
            { value: '2.5rem', name: '10 (40px)' },
            { value: '3rem', name: '12 (48px)' },
            { value: '3.5rem', name: '14 (56px)' },
            { value: '4rem', name: '16 (64px)' },
        ];

        return spacingScale;
    }

    /**
     * Get Tailwind font options
     */
    private getTailwindFontOptions(): Array<{ value: string; name: string }> {
        return this.brandFonts.map((font) => ({
            value: font.family,
            name: font.name,
        }));
    }

    /**
     * Get Tailwind font size options
     */
    private getTailwindFontSizeOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '0.75rem', name: 'xs (12px)' },
            { value: '0.875rem', name: 'sm (14px)' },
            { value: '1rem', name: 'base (16px)' },
            { value: '1.125rem', name: 'lg (18px)' },
            { value: '1.25rem', name: 'xl (20px)' },
            { value: '1.5rem', name: '2xl (24px)' },
            { value: '1.875rem', name: '3xl (30px)' },
            { value: '2.25rem', name: '4xl (36px)' },
            { value: '3rem', name: '5xl (48px)' },
            { value: '3.75rem', name: '6xl (60px)' },
            { value: '4.5rem', name: '7xl (72px)' },
            { value: '6rem', name: '8xl (96px)' },
            { value: '8rem', name: '9xl (128px)' },
        ];
    }

    /**
     * Get Tailwind font weight options
     */
    private getTailwindFontWeightOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '100', name: 'Thin' },
            { value: '200', name: 'Extra Light' },
            { value: '300', name: 'Light' },
            { value: '400', name: 'Normal' },
            { value: '500', name: 'Medium' },
            { value: '600', name: 'Semi Bold' },
            { value: '700', name: 'Bold' },
            { value: '800', name: 'Extra Bold' },
            { value: '900', name: 'Black' },
        ];
    }

    /**
     * Get Tailwind line height options
     */
    private getTailwindLineHeightOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '1', name: 'None (1)' },
            { value: '1.25', name: 'Tight (1.25)' },
            { value: '1.375', name: 'Snug (1.375)' },
            { value: '1.5', name: 'Normal (1.5)' },
            { value: '1.625', name: 'Relaxed (1.625)' },
            { value: '2', name: 'Loose (2)' },
        ];
    }

    /**
     * Get Tailwind shadow options
     */
    private getTailwindShadowOptions(): Array<{ value: string; name: string }> {
        return [
            { value: 'none', name: 'None' },
            { value: '0 1px 2px 0 rgb(0 0 0 / 0.05)', name: 'Small' },
            { value: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)', name: 'Default' },
            { value: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)', name: 'Medium' },
            { value: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)', name: 'Large' },
            { value: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)', name: 'Extra Large' },
            { value: '0 25px 50px -12px rgb(0 0 0 / 0.25)', name: '2XL' },
            { value: 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)', name: 'Inner' },
        ];
    }

    /**
     * Get Tailwind border radius options
     */
    private getTailwindBorderRadiusOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '0', name: 'None' },
            { value: '0.125rem', name: 'Small (2px)' },
            { value: '0.25rem', name: 'Default (4px)' },
            { value: '0.375rem', name: 'Medium (6px)' },
            { value: '0.5rem', name: 'Large (8px)' },
            { value: '0.75rem', name: 'XL (12px)' },
            { value: '1rem', name: '2XL (16px)' },
            { value: '1.5rem', name: '3XL (24px)' },
            { value: '9999px', name: 'Full' },
        ];
    }

    /**
     * Apply brand color to selected component
     */
    public applyBrandColor(colorName: string, property: 'color' | 'background-color' | 'border-color'): void {
        const color = this.brandColors.find((c) => c.name === colorName);
        if (!color) return;

        const selected = this.editor.getSelected();
        if (selected) {
            selected.addStyle({ [property]: color.value });
            this.addTailwindClass(selected, this.getTailwindColorClass(color, property));
        }
    }

    /**
     * Get Tailwind color class for a brand color
     */
    private getTailwindColorClass(color: TailwindColor, property: string): string {
        const colorMap: Record<string, string> = {
            'Primary Blue': 'blue-500',
            'Primary Dark': 'blue-800',
            'Secondary Green': 'green-500',
            'Accent Orange': 'yellow-500',
            'Neutral Gray': 'gray-500',
            'Success Green': 'green-600',
            'Warning Yellow': 'yellow-600',
            'Error Red': 'red-600',
        };

        const colorClass = colorMap[color.name] || 'gray-500';

        switch (property) {
            case 'color':
                return `text-${colorClass}`;
            case 'background-color':
                return `bg-${colorClass}`;
            case 'border-color':
                return `border-${colorClass}`;
            default:
                return `text-${colorClass}`;
        }
    }

    /**
     * Add Tailwind class to component
     */
    private addTailwindClass(component: any, className: string): void {
        const existingClasses = component.getClasses();
        const newClasses = [...existingClasses, className];
        component.setClass(newClasses.join(' '));
    }

    /**
     * Save current component styles as a preset
     */
    public async saveStylePreset(name: string, description: string, category: string): Promise<StylePreset> {
        const selected = this.editor.getSelected();
        if (!selected) {
            throw new Error('No component selected');
        }

        const styles = selected.getStyle();
        const classes = selected.getClasses();

        const preset: StylePreset = {
            id: `preset_${Date.now()}`,
            name,
            description,
            category,
            styles,
            tailwindClasses: classes,
            createdAt: new Date(),
            updatedAt: new Date(),
        };

        this.stylePresets.push(preset);
        await this.savePresetsToStorage();

        return preset;
    }

    /**
     * Apply style preset to selected component
     */
    public applyStylePreset(presetId: string): void {
        const preset = this.stylePresets.find((p) => p.id === presetId);
        if (!preset) return;

        const selected = this.editor.getSelected();
        if (!selected) return;

        // Apply styles
        selected.setStyle(preset.styles);

        // Apply Tailwind classes
        selected.setClass(preset.tailwindClasses.join(' '));
    }

    /**
     * Get all style presets
     */
    public getStylePresets(): StylePreset[] {
        return this.stylePresets;
    }

    /**
     * Get style presets by category
     */
    public getStylePresetsByCategory(category: string): StylePreset[] {
        return this.stylePresets.filter((preset) => preset.category === category);
    }

    /**
     * Delete style preset
     */
    public async deleteStylePreset(presetId: string): Promise<void> {
        this.stylePresets = this.stylePresets.filter((preset) => preset.id !== presetId);
        await this.savePresetsToStorage();
    }

    /**
     * Load style presets from storage
     */
    private async loadStylePresets(): Promise<void> {
        try {
            const response = await fetch('/api/style-presets');
            if (response.ok) {
                this.stylePresets = await response.json();
            }
        } catch (error) {
            console.error('Failed to load style presets:', error);
            // Load from localStorage as fallback
            const stored = localStorage.getItem('pagebuilder_style_presets');
            if (stored) {
                this.stylePresets = JSON.parse(stored);
            }
        }
    }

    /**
     * Save style presets to storage
     */
    private async savePresetsToStorage(): Promise<void> {
        try {
            await fetch('/api/style-presets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(this.stylePresets),
            });
        } catch (error) {
            console.error('Failed to save style presets:', error);
            // Save to localStorage as fallback
            localStorage.setItem('pagebuilder_style_presets', JSON.stringify(this.stylePresets));
        }
    }

    /**
     * Get brand colors
     */
    public getBrandColors(): TailwindColor[] {
        return this.brandColors;
    }

    /**
     * Get brand fonts
     */
    public getBrandFonts(): TailwindFont[] {
        return this.brandFonts;
    }

    /**
     * Validate component styles against brand guidelines
     */
    public validateBrandCompliance(component: any): { isCompliant: boolean; violations: string[] } {
        const styles = component.getStyle();
        const violations: string[] = [];

        // Check color compliance
        if (styles.color && !this.isApprovedColor(styles.color)) {
            violations.push(`Text color ${styles.color} is not in brand guidelines`);
        }

        if (styles['background-color'] && !this.isApprovedColor(styles['background-color'])) {
            violations.push(`Background color ${styles['background-color']} is not in brand guidelines`);
        }

        // Check font compliance
        if (styles['font-family'] && !this.isApprovedFont(styles['font-family'])) {
            violations.push(`Font family ${styles['font-family']} is not in brand guidelines`);
        }

        return {
            isCompliant: violations.length === 0,
            violations,
        };
    }

    /**
     * Check if color is approved in brand guidelines
     */
    private isApprovedColor(color: string): boolean {
        return this.brandColors.some((brandColor) => brandColor.value.toLowerCase() === color.toLowerCase());
    }

    /**
     * Check if font is approved in brand guidelines
     */
    private isApprovedFont(fontFamily: string): boolean {
        return this.brandFonts.some((brandFont) => fontFamily.includes(brandFont.name));
    }

    /**
     * Get style suggestions for better brand compliance
     */
    public getStyleSuggestions(component: any): string[] {
        const suggestions: string[] = [];
        const styles = component.getStyle();

        // Suggest brand colors if using non-brand colors
        if (styles.color && !this.isApprovedColor(styles.color)) {
            suggestions.push('Consider using a brand color for better consistency');
        }

        // Suggest proper spacing
        if (styles.padding && !this.isTailwindSpacing(styles.padding)) {
            suggestions.push('Use Tailwind spacing scale for consistent spacing');
        }

        if (styles.margin && !this.isTailwindSpacing(styles.margin)) {
            suggestions.push('Use Tailwind spacing scale for consistent margins');
        }

        return suggestions;
    }

    /**
     * Check if spacing value follows Tailwind scale
     */
    private isTailwindSpacing(spacing: string): boolean {
        const tailwindValues = this.getTailwindSpacingOptions().map((option) => option.value);
        return tailwindValues.includes(spacing);
    }
}
