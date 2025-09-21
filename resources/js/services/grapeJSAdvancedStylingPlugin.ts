/**
 * GrapeJS Advanced Styling Plugin
 * Integrates Tailwind CSS classes and brand guidelines with GrapeJS Style Manager
 */

import type { Editor, Plugin } from 'grapesjs';
import { BrandGuidelinesService } from './BrandGuidelinesService';
import { TailwindStyleManager } from './TailwindStyleManager';

export interface AdvancedStylingPluginOptions {
    // Plugin configuration options
    enableBrandGuidelines?: boolean;
    enableTailwindIntegration?: boolean;
    enableStylePresets?: boolean;
    brandColors?: string[];
    brandFonts?: string[];
    customSpacing?: Record<string, string>;
}

const advancedStylingPlugin: Plugin<AdvancedStylingPluginOptions> = (editor: Editor, options = {}) => {
    const opts: AdvancedStylingPluginOptions = {
        enableBrandGuidelines: true,
        enableTailwindIntegration: true,
        enableStylePresets: true,
        brandColors: ['#3B82F6', '#1E40AF', '#10B981', '#F59E0B', '#6B7280', '#059669', '#D97706', '#DC2626'],
        brandFonts: ['Inter, sans-serif', 'Roboto, sans-serif', 'Playfair Display, serif'],
        customSpacing: {
            xs: '0.5rem',
            sm: '0.75rem',
            md: '1rem',
            lg: '1.5rem',
            xl: '2rem',
        },
        ...options,
    };

    let tailwindStyleManager: TailwindStyleManager;
    let brandGuidelinesService: BrandGuidelinesService;

    // Initialize services
    editor.on('load', () => {
        if (opts.enableTailwindIntegration) {
            tailwindStyleManager = new TailwindStyleManager(editor);
        }

        if (opts.enableBrandGuidelines) {
            brandGuidelinesService = new BrandGuidelinesService();
        }

        setupAdvancedStyleManager();
        setupBrandComplianceValidation();
        setupStylePresetIntegration();
        setupTailwindClassManager();
    });

    /**
     * Setup advanced style manager with Tailwind integration
     */
    function setupAdvancedStyleManager(): void {
        const styleManager = editor.StyleManager;

        // Remove default sectors
        styleManager.getSectors().reset();

        // Add Tailwind-integrated sectors
        if (opts.enableTailwindIntegration) {
            addTailwindLayoutSector();
            addTailwindSpacingSector();
            addTailwindTypographySector();
            addTailwindColorsSector();
            addTailwindEffectsSector();
        }

        // Add brand guidelines sector
        if (opts.enableBrandGuidelines) {
            addBrandGuidelinesSector();
        }

        // Add style presets sector
        if (opts.enableStylePresets) {
            addStylePresetsSector();
        }
    }

    /**
     * Add Tailwind Layout sector
     */
    function addTailwindLayoutSector(): void {
        editor.StyleManager.addSector('tailwind-layout', {
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
                        { value: 'inline-grid', name: 'Inline Grid' },
                        { value: 'hidden', name: 'Hidden' },
                    ],
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'display');
                    },
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
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'position');
                    },
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
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'flex-direction');
                    },
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
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'justify-content');
                    },
                },
            ],
        });
    }

    /**
     * Add Tailwind Spacing sector
     */
    function addTailwindSpacingSector(): void {
        editor.StyleManager.addSector('tailwind-spacing', {
            name: 'Spacing',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Padding',
                    property: 'padding',
                    defaults: '0',
                    options: getTailwindSpacingOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'padding');
                    },
                },
                {
                    type: 'select',
                    name: 'Margin',
                    property: 'margin',
                    defaults: '0',
                    options: getTailwindSpacingOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'margin');
                    },
                },
                {
                    type: 'select',
                    name: 'Gap',
                    property: 'gap',
                    defaults: '0',
                    options: getTailwindSpacingOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'gap');
                    },
                },
            ],
        });
    }

    /**
     * Add Tailwind Typography sector
     */
    function addTailwindTypographySector(): void {
        editor.StyleManager.addSector('tailwind-typography', {
            name: 'Typography',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Font Family',
                    property: 'font-family',
                    defaults: 'sans-serif',
                    options:
                        opts.brandFonts?.map((font) => ({
                            value: font,
                            name: font.split(',')[0],
                        })) || [],
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'font-family');
                        validateBrandCompliance();
                    },
                },
                {
                    type: 'select',
                    name: 'Font Size',
                    property: 'font-size',
                    defaults: '1rem',
                    options: getTailwindFontSizeOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'font-size');
                    },
                },
                {
                    type: 'select',
                    name: 'Font Weight',
                    property: 'font-weight',
                    defaults: '400',
                    options: getTailwindFontWeightOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'font-weight');
                    },
                },
            ],
        });
    }

    /**
     * Add Tailwind Colors sector
     */
    function addTailwindColorsSector(): void {
        editor.StyleManager.addSector('tailwind-colors', {
            name: 'Colors',
            open: false,
            properties: [
                {
                    type: 'color',
                    name: 'Text Color',
                    property: 'color',
                    defaults: '#000000',
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'color');
                        validateBrandCompliance();
                    },
                },
                {
                    type: 'color',
                    name: 'Background Color',
                    property: 'background-color',
                    defaults: 'transparent',
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'background-color');
                        validateBrandCompliance();
                    },
                },
                {
                    type: 'color',
                    name: 'Border Color',
                    property: 'border-color',
                    defaults: 'transparent',
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'border-color');
                        validateBrandCompliance();
                    },
                },
            ],
        });
    }

    /**
     * Add Tailwind Effects sector
     */
    function addTailwindEffectsSector(): void {
        editor.StyleManager.addSector('tailwind-effects', {
            name: 'Effects',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Shadow',
                    property: 'box-shadow',
                    defaults: 'none',
                    options: getTailwindShadowOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'box-shadow');
                    },
                },
                {
                    type: 'slider',
                    name: 'Opacity',
                    property: 'opacity',
                    defaults: 1,
                    min: 0,
                    max: 1,
                    step: 0.1,
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'opacity');
                    },
                },
                {
                    type: 'select',
                    name: 'Border Radius',
                    property: 'border-radius',
                    defaults: '0',
                    options: getTailwindBorderRadiusOptions(),
                    onChange: (property: any) => {
                        updateTailwindClass(property, 'border-radius');
                    },
                },
            ],
        });
    }

    /**
     * Add Brand Guidelines sector
     */
    function addBrandGuidelinesSector(): void {
        editor.StyleManager.addSector('brand-guidelines', {
            name: 'Brand Guidelines',
            open: false,
            properties: [
                {
                    type: 'select',
                    name: 'Brand Colors',
                    property: 'brand-color',
                    options:
                        opts.brandColors?.map((color, index) => ({
                            value: color,
                            name: `Brand Color ${index + 1}`,
                        })) || [],
                    onChange: (property: any) => {
                        applyBrandColor(property.getValue());
                    },
                },
            ],
        });
    }

    /**
     * Add Style Presets sector
     */
    function addStylePresetsSector(): void {
        editor.StyleManager.addSector('style-presets', {
            name: 'Style Presets',
            open: false,
            properties: [
                {
                    type: 'button',
                    name: 'Save Current Style',
                    command: 'save-style-preset',
                },
                {
                    type: 'button',
                    name: 'Load Preset',
                    command: 'load-style-preset',
                },
            ],
        });
    }

    /**
     * Setup brand compliance validation
     */
    function setupBrandComplianceValidation(): void {
        if (!opts.enableBrandGuidelines) return;

        editor.on('component:selected', async (component) => {
            if (component && brandGuidelinesService) {
                const styles = component.getStyle();
                const compliance = await brandGuidelinesService.validateComponentStyles(styles);

                // Show compliance status in UI
                showComplianceStatus(compliance);
            }
        });

        editor.on('component:styleUpdate', async (component) => {
            if (component && brandGuidelinesService) {
                const styles = component.getStyle();
                const compliance = await brandGuidelinesService.validateComponentStyles(styles);

                // Update compliance status
                showComplianceStatus(compliance);
            }
        });
    }

    /**
     * Setup style preset integration
     */
    function setupStylePresetIntegration(): void {
        if (!opts.enableStylePresets) return;

        // Add commands for style preset management
        editor.Commands.add('save-style-preset', {
            run: (editor) => {
                const selected = editor.getSelected();
                if (selected && tailwindStyleManager) {
                    // Open save preset modal
                    openSavePresetModal(selected);
                }
            },
        });

        editor.Commands.add('load-style-preset', {
            run: (editor) => {
                // Open load preset modal
                openLoadPresetModal();
            },
        });
    }

    /**
     * Setup Tailwind class manager
     */
    function setupTailwindClassManager(): void {
        if (!opts.enableTailwindIntegration) return;

        // Add custom trait for Tailwind classes
        editor.TraitManager.addType('tailwind-classes', {
            createInput({ trait }: any) {
                const input = document.createElement('input');
                input.type = 'text';
                input.placeholder = 'Enter Tailwind classes';
                input.className = 'tailwind-classes-input';

                input.addEventListener('input', (e) => {
                    const target = e.target as HTMLInputElement;
                    const component = editor.getSelected();
                    if (component) {
                        component.setClass(target.value);
                    }
                });

                return input;
            },

            onUpdate({ elInput, component }: any) {
                const classes = component.getClasses().join(' ');
                elInput.value = classes;
            },
        });
    }

    /**
     * Update Tailwind class based on property change
     */
    function updateTailwindClass(property: any, cssProperty: string): void {
        const selected = editor.getSelected();
        if (!selected) return;

        const value = property.getValue();
        const tailwindClass = getTailwindClassForProperty(cssProperty, value);

        if (tailwindClass) {
            // Remove existing classes for this property
            const existingClasses = selected.getClasses();
            const filteredClasses = existingClasses.filter((cls: string) => !cls.startsWith(getTailwindPrefixForProperty(cssProperty)));

            // Add new class
            filteredClasses.push(tailwindClass);
            selected.setClass(filteredClasses.join(' '));
        }
    }

    /**
     * Get Tailwind class for CSS property and value
     */
    function getTailwindClassForProperty(property: string, value: string): string | null {
        const classMap: Record<string, Record<string, string>> = {
            display: {
                block: 'block',
                'inline-block': 'inline-block',
                flex: 'flex',
                'inline-flex': 'inline-flex',
                grid: 'grid',
                'inline-grid': 'inline-grid',
                hidden: 'hidden',
            },
            position: {
                static: 'static',
                relative: 'relative',
                absolute: 'absolute',
                fixed: 'fixed',
                sticky: 'sticky',
            },
            'flex-direction': {
                row: 'flex-row',
                'row-reverse': 'flex-row-reverse',
                column: 'flex-col',
                'column-reverse': 'flex-col-reverse',
            },
            'justify-content': {
                'flex-start': 'justify-start',
                center: 'justify-center',
                'flex-end': 'justify-end',
                'space-between': 'justify-between',
                'space-around': 'justify-around',
                'space-evenly': 'justify-evenly',
            },
        };

        return classMap[property]?.[value] || null;
    }

    /**
     * Get Tailwind prefix for property
     */
    function getTailwindPrefixForProperty(property: string): string {
        const prefixMap: Record<string, string> = {
            display: '',
            position: '',
            'flex-direction': 'flex-',
            'justify-content': 'justify-',
            'align-items': 'items-',
            padding: 'p-',
            margin: 'm-',
            'font-size': 'text-',
            'font-weight': 'font-',
            color: 'text-',
            'background-color': 'bg-',
            'border-radius': 'rounded',
        };

        return prefixMap[property] || '';
    }

    /**
     * Apply brand color to selected component
     */
    function applyBrandColor(color: string): void {
        const selected = editor.getSelected();
        if (selected) {
            selected.addStyle({ color });
            validateBrandCompliance();
        }
    }

    /**
     * Validate brand compliance for current selection
     */
    async function validateBrandCompliance(): Promise<void> {
        if (!brandGuidelinesService) return;

        const selected = editor.getSelected();
        if (selected) {
            const styles = selected.getStyle();
            const compliance = await brandGuidelinesService.validateComponentStyles(styles);
            showComplianceStatus(compliance);
        }
    }

    /**
     * Show compliance status in UI
     */
    function showComplianceStatus(compliance: any): void {
        // Emit event for UI to handle
        editor.trigger('brand:compliance-update', compliance);
    }

    /**
     * Open save preset modal
     */
    function openSavePresetModal(component: any): void {
        // Emit event for UI to handle
        editor.trigger('style-preset:save-modal', {
            component,
            styles: component.getStyle(),
            classes: component.getClasses(),
        });
    }

    /**
     * Open load preset modal
     */
    function openLoadPresetModal(): void {
        // Emit event for UI to handle
        editor.trigger('style-preset:load-modal');
    }

    /**
     * Get Tailwind spacing options
     */
    function getTailwindSpacingOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '0', name: '0' },
            { value: '0.125rem', name: '0.5 (2px)' },
            { value: '0.25rem', name: '1 (4px)' },
            { value: '0.375rem', name: '1.5 (6px)' },
            { value: '0.5rem', name: '2 (8px)' },
            { value: '0.75rem', name: '3 (12px)' },
            { value: '1rem', name: '4 (16px)' },
            { value: '1.25rem', name: '5 (20px)' },
            { value: '1.5rem', name: '6 (24px)' },
            { value: '2rem', name: '8 (32px)' },
            { value: '2.5rem', name: '10 (40px)' },
            { value: '3rem', name: '12 (48px)' },
        ];
    }

    /**
     * Get Tailwind font size options
     */
    function getTailwindFontSizeOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '0.75rem', name: 'xs (12px)' },
            { value: '0.875rem', name: 'sm (14px)' },
            { value: '1rem', name: 'base (16px)' },
            { value: '1.125rem', name: 'lg (18px)' },
            { value: '1.25rem', name: 'xl (20px)' },
            { value: '1.5rem', name: '2xl (24px)' },
            { value: '1.875rem', name: '3xl (30px)' },
            { value: '2.25rem', name: '4xl (36px)' },
        ];
    }

    /**
     * Get Tailwind font weight options
     */
    function getTailwindFontWeightOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '300', name: 'Light' },
            { value: '400', name: 'Normal' },
            { value: '500', name: 'Medium' },
            { value: '600', name: 'Semibold' },
            { value: '700', name: 'Bold' },
        ];
    }

    /**
     * Get Tailwind shadow options
     */
    function getTailwindShadowOptions(): Array<{ value: string; name: string }> {
        return [
            { value: 'none', name: 'None' },
            { value: '0 1px 2px 0 rgb(0 0 0 / 0.05)', name: 'Small' },
            { value: '0 1px 3px 0 rgb(0 0 0 / 0.1)', name: 'Default' },
            { value: '0 4px 6px -1px rgb(0 0 0 / 0.1)', name: 'Medium' },
            { value: '0 10px 15px -3px rgb(0 0 0 / 0.1)', name: 'Large' },
        ];
    }

    /**
     * Get Tailwind border radius options
     */
    function getTailwindBorderRadiusOptions(): Array<{ value: string; name: string }> {
        return [
            { value: '0', name: 'None' },
            { value: '0.125rem', name: 'Small (2px)' },
            { value: '0.25rem', name: 'Default (4px)' },
            { value: '0.375rem', name: 'Medium (6px)' },
            { value: '0.5rem', name: 'Large (8px)' },
            { value: '9999px', name: 'Full' },
        ];
    }
};

export default advancedStylingPlugin;
