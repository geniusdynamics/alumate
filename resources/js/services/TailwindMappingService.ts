import type {
  TailwindClassMapping,
  TailwindStyleMapping,
  DeviceType,
  BreakpointName
} from '@/types/components';

/**
 * Service for mapping Tailwind CSS classes to GrapeJS Style Manager
 * Provides responsive design integration and class management
 */
export class TailwindMappingService {
  private static instance: TailwindMappingService;

  // Comprehensive Tailwind CSS class mappings for GrapeJS Style Manager
  private readonly tailwindMappings: TailwindStyleMapping = {
    // Universal mappings for all component types
    '*': {
      '.component-root': [
        {
          property: 'display',
          tailwindClasses: [
            { class: 'block', value: 'block', label: 'Block', category: 'layout', responsive: true },
            { class: 'inline-block', value: 'inline-block', label: 'Inline Block', category: 'layout', responsive: true },
            { class: 'inline', value: 'inline', label: 'Inline', category: 'layout', responsive: true },
            { class: 'flex', value: 'flex', label: 'Flex', category: 'layout', responsive: true },
            { class: 'inline-flex', value: 'inline-flex', label: 'Inline Flex', category: 'layout', responsive: true },
            { class: 'grid', value: 'grid', label: 'Grid', category: 'layout', responsive: true },
            { class: 'inline-grid', value: 'inline-grid', label: 'Inline Grid', category: 'layout', responsive: true },
            { class: 'hidden', value: 'none', label: 'Hidden', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'display',
          responsive: true
        },
        {
          property: 'position',
          tailwindClasses: [
            { class: 'static', value: 'static', label: 'Static', category: 'layout' },
            { class: 'fixed', value: 'fixed', label: 'Fixed', category: 'layout' },
            { class: 'absolute', value: 'absolute', label: 'Absolute', category: 'layout' },
            { class: 'relative', value: 'relative', label: 'Relative', category: 'layout' },
            { class: 'sticky', value: 'sticky', label: 'Sticky', category: 'layout' }
          ],
          grapeJSProperty: 'position',
          responsive: false
        },
        {
          property: 'padding',
          tailwindClasses: [
            { class: 'p-0', value: '0', label: 'None', category: 'spacing', responsive: true },
            { class: 'p-1', value: '0.25rem', label: 'XS', category: 'spacing', responsive: true },
            { class: 'p-2', value: '0.5rem', label: 'SM', category: 'spacing', responsive: true },
            { class: 'p-3', value: '0.75rem', label: 'MD-', category: 'spacing', responsive: true },
            { class: 'p-4', value: '1rem', label: 'MD', category: 'spacing', responsive: true },
            { class: 'p-5', value: '1.25rem', label: 'MD+', category: 'spacing', responsive: true },
            { class: 'p-6', value: '1.5rem', label: 'LG', category: 'spacing', responsive: true },
            { class: 'p-8', value: '2rem', label: 'XL', category: 'spacing', responsive: true },
            { class: 'p-10', value: '2.5rem', label: '2XL', category: 'spacing', responsive: true },
            { class: 'p-12', value: '3rem', label: '3XL', category: 'spacing', responsive: true }
          ],
          grapeJSProperty: 'padding',
          responsive: true
        },
        {
          property: 'margin',
          tailwindClasses: [
            { class: 'm-0', value: '0', label: 'None', category: 'spacing', responsive: true },
            { class: 'm-1', value: '0.25rem', label: 'XS', category: 'spacing', responsive: true },
            { class: 'm-2', value: '0.5rem', label: 'SM', category: 'spacing', responsive: true },
            { class: 'm-3', value: '0.75rem', label: 'MD-', category: 'spacing', responsive: true },
            { class: 'm-4', value: '1rem', label: 'MD', category: 'spacing', responsive: true },
            { class: 'm-5', value: '1.25rem', label: 'MD+', category: 'spacing', responsive: true },
            { class: 'm-6', value: '1.5rem', label: 'LG', category: 'spacing', responsive: true },
            { class: 'm-8', value: '2rem', label: 'XL', category: 'spacing', responsive: true },
            { class: 'm-10', value: '2.5rem', label: '2XL', category: 'spacing', responsive: true },
            { class: 'm-12', value: '3rem', label: '3XL', category: 'spacing', responsive: true },
            { class: 'm-auto', value: 'auto', label: 'Auto', category: 'spacing', responsive: true }
          ],
          grapeJSProperty: 'margin',
          responsive: true
        },
        {
          property: 'width',
          tailwindClasses: [
            { class: 'w-auto', value: 'auto', label: 'Auto', category: 'layout', responsive: true },
            { class: 'w-full', value: '100%', label: 'Full', category: 'layout', responsive: true },
            { class: 'w-1/2', value: '50%', label: '1/2', category: 'layout', responsive: true },
            { class: 'w-1/3', value: '33.333333%', label: '1/3', category: 'layout', responsive: true },
            { class: 'w-2/3', value: '66.666667%', label: '2/3', category: 'layout', responsive: true },
            { class: 'w-1/4', value: '25%', label: '1/4', category: 'layout', responsive: true },
            { class: 'w-3/4', value: '75%', label: '3/4', category: 'layout', responsive: true },
            { class: 'w-fit', value: 'fit-content', label: 'Fit Content', category: 'layout', responsive: true },
            { class: 'w-screen', value: '100vw', label: 'Screen', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'width',
          responsive: true
        },
        {
          property: 'height',
          tailwindClasses: [
            { class: 'h-auto', value: 'auto', label: 'Auto', category: 'layout', responsive: true },
            { class: 'h-full', value: '100%', label: 'Full', category: 'layout', responsive: true },
            { class: 'h-screen', value: '100vh', label: 'Screen', category: 'layout', responsive: true },
            { class: 'h-fit', value: 'fit-content', label: 'Fit Content', category: 'layout', responsive: true },
            { class: 'h-32', value: '8rem', label: '32', category: 'layout', responsive: true },
            { class: 'h-48', value: '12rem', label: '48', category: 'layout', responsive: true },
            { class: 'h-64', value: '16rem', label: '64', category: 'layout', responsive: true },
            { class: 'h-96', value: '24rem', label: '96', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'height',
          responsive: true
        },
        {
          property: 'font-size',
          tailwindClasses: [
            { class: 'text-xs', value: '0.75rem', label: 'XS', category: 'typography', responsive: true },
            { class: 'text-sm', value: '0.875rem', label: 'SM', category: 'typography', responsive: true },
            { class: 'text-base', value: '1rem', label: 'Base', category: 'typography', responsive: true },
            { class: 'text-lg', value: '1.125rem', label: 'LG', category: 'typography', responsive: true },
            { class: 'text-xl', value: '1.25rem', label: 'XL', category: 'typography', responsive: true },
            { class: 'text-2xl', value: '1.5rem', label: '2XL', category: 'typography', responsive: true },
            { class: 'text-3xl', value: '1.875rem', label: '3XL', category: 'typography', responsive: true },
            { class: 'text-4xl', value: '2.25rem', label: '4XL', category: 'typography', responsive: true },
            { class: 'text-5xl', value: '3rem', label: '5XL', category: 'typography', responsive: true },
            { class: 'text-6xl', value: '3.75rem', label: '6XL', category: 'typography', responsive: true }
          ],
          grapeJSProperty: 'font-size',
          responsive: true
        },
        {
          property: 'font-weight',
          tailwindClasses: [
            { class: 'font-thin', value: '100', label: 'Thin', category: 'typography' },
            { class: 'font-extralight', value: '200', label: 'Extra Light', category: 'typography' },
            { class: 'font-light', value: '300', label: 'Light', category: 'typography' },
            { class: 'font-normal', value: '400', label: 'Normal', category: 'typography' },
            { class: 'font-medium', value: '500', label: 'Medium', category: 'typography' },
            { class: 'font-semibold', value: '600', label: 'Semi Bold', category: 'typography' },
            { class: 'font-bold', value: '700', label: 'Bold', category: 'typography' },
            { class: 'font-extrabold', value: '800', label: 'Extra Bold', category: 'typography' },
            { class: 'font-black', value: '900', label: 'Black', category: 'typography' }
          ],
          grapeJSProperty: 'font-weight',
          responsive: false
        },
        {
          property: 'text-align',
          tailwindClasses: [
            { class: 'text-left', value: 'left', label: 'Left', category: 'typography', responsive: true },
            { class: 'text-center', value: 'center', label: 'Center', category: 'typography', responsive: true },
            { class: 'text-right', value: 'right', label: 'Right', category: 'typography', responsive: true },
            { class: 'text-justify', value: 'justify', label: 'Justify', category: 'typography', responsive: true }
          ],
          grapeJSProperty: 'text-align',
          responsive: true
        },
        {
          property: 'color',
          tailwindClasses: [
            { class: 'text-black', value: '#000000', label: 'Black', category: 'colors' },
            { class: 'text-white', value: '#ffffff', label: 'White', category: 'colors' },
            { class: 'text-gray-500', value: '#6b7280', label: 'Gray', category: 'colors' },
            { class: 'text-red-500', value: '#ef4444', label: 'Red', category: 'colors' },
            { class: 'text-blue-500', value: '#3b82f6', label: 'Blue', category: 'colors' },
            { class: 'text-green-500', value: '#10b981', label: 'Green', category: 'colors' },
            { class: 'text-yellow-500', value: '#f59e0b', label: 'Yellow', category: 'colors' },
            { class: 'text-purple-500', value: '#8b5cf6', label: 'Purple', category: 'colors' },
            { class: 'text-pink-500', value: '#ec4899', label: 'Pink', category: 'colors' },
            { class: 'text-indigo-500', value: '#6366f1', label: 'Indigo', category: 'colors' }
          ],
          grapeJSProperty: 'color',
          responsive: false
        },
        {
          property: 'background-color',
          tailwindClasses: [
            { class: 'bg-transparent', value: 'transparent', label: 'Transparent', category: 'colors' },
            { class: 'bg-black', value: '#000000', label: 'Black', category: 'colors' },
            { class: 'bg-white', value: '#ffffff', label: 'White', category: 'colors' },
            { class: 'bg-gray-100', value: '#f3f4f6', label: 'Gray Light', category: 'colors' },
            { class: 'bg-gray-500', value: '#6b7280', label: 'Gray', category: 'colors' },
            { class: 'bg-red-500', value: '#ef4444', label: 'Red', category: 'colors' },
            { class: 'bg-blue-500', value: '#3b82f6', label: 'Blue', category: 'colors' },
            { class: 'bg-green-500', value: '#10b981', label: 'Green', category: 'colors' },
            { class: 'bg-yellow-500', value: '#f59e0b', label: 'Yellow', category: 'colors' },
            { class: 'bg-purple-500', value: '#8b5cf6', label: 'Purple', category: 'colors' }
          ],
          grapeJSProperty: 'background-color',
          responsive: false
        }
      ]
    },

    // Hero component specific mappings
    'hero': {
      '.hero-container': [
        {
          property: 'min-height',
          tailwindClasses: [
            { class: 'min-h-screen', value: '100vh', label: 'Full Screen', category: 'layout', responsive: true },
            { class: 'min-h-96', value: '24rem', label: 'Large', category: 'layout', responsive: true },
            { class: 'min-h-64', value: '16rem', label: 'Medium', category: 'layout', responsive: true },
            { class: 'min-h-48', value: '12rem', label: 'Small', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'min-height',
          responsive: true
        },
        {
          property: 'justify-content',
          tailwindClasses: [
            { class: 'justify-start', value: 'flex-start', label: 'Start', category: 'layout', responsive: true },
            { class: 'justify-center', value: 'center', label: 'Center', category: 'layout', responsive: true },
            { class: 'justify-end', value: 'flex-end', label: 'End', category: 'layout', responsive: true },
            { class: 'justify-between', value: 'space-between', label: 'Space Between', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'justify-content',
          responsive: true
        }
      ],
      '.hero-content': [
        {
          property: 'max-width',
          tailwindClasses: [
            { class: 'max-w-none', value: 'none', label: 'None', category: 'layout', responsive: true },
            { class: 'max-w-sm', value: '24rem', label: 'Small', category: 'layout', responsive: true },
            { class: 'max-w-md', value: '28rem', label: 'Medium', category: 'layout', responsive: true },
            { class: 'max-w-lg', value: '32rem', label: 'Large', category: 'layout', responsive: true },
            { class: 'max-w-xl', value: '36rem', label: 'XL', category: 'layout', responsive: true },
            { class: 'max-w-2xl', value: '42rem', label: '2XL', category: 'layout', responsive: true },
            { class: 'max-w-4xl', value: '56rem', label: '4XL', category: 'layout', responsive: true },
            { class: 'max-w-6xl', value: '72rem', label: '6XL', category: 'layout', responsive: true }
          ],
          grapeJSProperty: 'max-width',
          responsive: true
        }
      ]
    },

    // Form component specific mappings
    'forms': {
      '.form-container': [
        {
          property: 'gap',
          tailwindClasses: [
            { class: 'gap-2', value: '0.5rem', label: 'Small', category: 'spacing', responsive: true },
            { class: 'gap-4', value: '1rem', label: 'Medium', category: 'spacing', responsive: true },
            { class: 'gap-6', value: '1.5rem', label: 'Large', category: 'spacing', responsive: true },
            { class: 'gap-8', value: '2rem', label: 'XL', category: 'spacing', responsive: true }
          ],
          grapeJSProperty: 'gap',
          responsive: true
        }
      ],
      '.form-field': [
        {
          property: 'border-radius',
          tailwindClasses: [
            { class: 'rounded-none', value: '0', label: 'None', category: 'effects' },
            { class: 'rounded-sm', value: '0.125rem', label: 'Small', category: 'effects' },
            { class: 'rounded', value: '0.25rem', label: 'Default', category: 'effects' },
            { class: 'rounded-md', value: '0.375rem', label: 'Medium', category: 'effects' },
            { class: 'rounded-lg', value: '0.5rem', label: 'Large', category: 'effects' },
            { class: 'rounded-xl', value: '0.75rem', label: 'XL', category: 'effects' },
            { class: 'rounded-full', value: '9999px', label: 'Full', category: 'effects' }
          ],
          grapeJSProperty: 'border-radius',
          responsive: false
        }
      ]
    },

    // CTA component specific mappings
    'ctas': {
      '.cta-button': [
        {
          property: 'border-width',
          tailwindClasses: [
            { class: 'border-0', value: '0', label: 'None', category: 'effects' },
            { class: 'border', value: '1px', label: 'Default', category: 'effects' },
            { class: 'border-2', value: '2px', label: 'Thick', category: 'effects' },
            { class: 'border-4', value: '4px', label: 'Extra Thick', category: 'effects' }
          ],
          grapeJSProperty: 'border-width',
          responsive: false
        },
        {
          property: 'box-shadow',
          tailwindClasses: [
            { class: 'shadow-none', value: 'none', label: 'None', category: 'effects' },
            { class: 'shadow-sm', value: '0 1px 2px 0 rgb(0 0 0 / 0.05)', label: 'Small', category: 'effects' },
            { class: 'shadow', value: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)', label: 'Default', category: 'effects' },
            { class: 'shadow-md', value: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)', label: 'Medium', category: 'effects' },
            { class: 'shadow-lg', value: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)', label: 'Large', category: 'effects' },
            { class: 'shadow-xl', value: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)', label: 'XL', category: 'effects' }
          ],
          grapeJSProperty: 'box-shadow',
          responsive: false
        }
      ]
    },

    // Media component specific mappings
    'media': {
      '.media-container': [
        {
          property: 'object-fit',
          tailwindClasses: [
            { class: 'object-contain', value: 'contain', label: 'Contain', category: 'layout' },
            { class: 'object-cover', value: 'cover', label: 'Cover', category: 'layout' },
            { class: 'object-fill', value: 'fill', label: 'Fill', category: 'layout' },
            { class: 'object-none', value: 'none', label: 'None', category: 'layout' },
            { class: 'object-scale-down', value: 'scale-down', label: 'Scale Down', category: 'layout' }
          ],
          grapeJSProperty: 'object-fit',
          responsive: false
        },
        {
          property: 'aspect-ratio',
          tailwindClasses: [
            { class: 'aspect-auto', value: 'auto', label: 'Auto', category: 'layout' },
            { class: 'aspect-square', value: '1 / 1', label: 'Square', category: 'layout' },
            { class: 'aspect-video', value: '16 / 9', label: 'Video', category: 'layout' },
            { class: 'aspect-[4/3]', value: '4 / 3', label: '4:3', category: 'layout' },
            { class: 'aspect-[3/2]', value: '3 / 2', label: '3:2', category: 'layout' },
            { class: 'aspect-[21/9]', value: '21 / 9', label: 'Ultrawide', category: 'layout' }
          ],
          grapeJSProperty: 'aspect-ratio',
          responsive: false
        }
      ]
    }
  };

  // Responsive breakpoint prefixes for Tailwind CSS
  private readonly responsivePrefixes: Record<DeviceType, string> = {
    mobile: '', // Mobile-first, no prefix
    tablet: 'md:',
    desktop: 'lg:'
  };

  public static getInstance(): TailwindMappingService {
    if (!TailwindMappingService.instance) {
      TailwindMappingService.instance = new TailwindMappingService();
    }
    return TailwindMappingService.instance;
  }

  /**
   * Get Tailwind mappings for a specific component type
   */
  public getMappingsForComponent(componentType: string): TailwindStyleMapping[string] {
    const universalMappings = this.tailwindMappings['*'] || {};
    const componentMappings = this.tailwindMappings[componentType] || {};
    
    return { ...universalMappings, ...componentMappings };
  }

  /**
   * Get all available Tailwind mappings
   */
  public getAllMappings(): TailwindStyleMapping {
    return { ...this.tailwindMappings };
  }

  /**
   * Generate responsive Tailwind classes for a property
   */
  public generateResponsiveClasses(
    componentType: string,
    elementSelector: string,
    property: string,
    values: Record<DeviceType, string>
  ): string[] {
    const classes: string[] = [];
    const mappings = this.getMappingsForComponent(componentType);
    const elementMappings = mappings[elementSelector];
    
    if (!elementMappings) {
      return classes;
    }

    const propertyMapping = elementMappings.find(mapping => mapping.property === property);
    if (!propertyMapping || !propertyMapping.responsive) {
      return classes;
    }

    // Add mobile-first class (no prefix)
    if (values.mobile) {
      const mobileClass = this.findTailwindClass(propertyMapping, values.mobile);
      if (mobileClass) {
        classes.push(mobileClass.class);
      }
    }

    // Add tablet-specific class
    if (values.tablet && values.tablet !== values.mobile) {
      const tabletClass = this.findTailwindClass(propertyMapping, values.tablet);
      if (tabletClass) {
        classes.push(`${this.responsivePrefixes.tablet}${tabletClass.class}`);
      }
    }

    // Add desktop-specific class
    if (values.desktop && values.desktop !== values.tablet) {
      const desktopClass = this.findTailwindClass(propertyMapping, values.desktop);
      if (desktopClass) {
        classes.push(`${this.responsivePrefixes.desktop}${desktopClass.class}`);
      }
    }

    return classes;
  }

  /**
   * Find Tailwind class by CSS value
   */
  private findTailwindClass(
    mapping: TailwindClassMapping,
    cssValue: string
  ): TailwindClassMapping['tailwindClasses'][0] | undefined {
    return mapping.tailwindClasses.find(tc => tc.value === cssValue);
  }

  /**
   * Convert CSS properties to Tailwind classes
   */
  public convertCSSToTailwind(
    componentType: string,
    elementSelector: string,
    cssProperties: Record<string, string>
  ): string[] {
    const classes: string[] = [];
    const mappings = this.getMappingsForComponent(componentType);
    const elementMappings = mappings[elementSelector];
    
    if (!elementMappings) {
      return classes;
    }

    Object.entries(cssProperties).forEach(([property, value]) => {
      const propertyMapping = elementMappings.find(mapping => mapping.property === property);
      if (propertyMapping) {
        const tailwindClass = this.findTailwindClass(propertyMapping, value);
        if (tailwindClass) {
          classes.push(tailwindClass.class);
        }
      }
    });

    return classes;
  }

  /**
   * Generate GrapeJS Style Manager sectors from Tailwind mappings
   */
  public generateGrapeJSStyleSectors(componentType: string): Array<{
    name: string;
    properties: Array<{
      name: string;
      property: string;
      type: string;
      options?: Array<{ id: string; label: string; value: string }>;
      responsive?: boolean;
    }>;
  }> {
    const mappings = this.getMappingsForComponent(componentType);
    const sectors: Record<string, any> = {};

    Object.entries(mappings).forEach(([elementSelector, elementMappings]) => {
      elementMappings.forEach(mapping => {
        const category = mapping.tailwindClasses[0]?.category || 'general';
        const sectorName = this.capitalizeSectorName(category);
        
        if (!sectors[sectorName]) {
          sectors[sectorName] = {
            name: sectorName,
            properties: []
          };
        }

        sectors[sectorName].properties.push({
          name: mapping.grapeJSProperty || mapping.property,
          property: mapping.property,
          type: 'select',
          options: mapping.tailwindClasses.map(tc => ({
            id: tc.class,
            label: tc.label,
            value: tc.value
          })),
          responsive: mapping.responsive
        });
      });
    });

    return Object.values(sectors);
  }

  /**
   * Generate device-specific class variants
   */
  public generateDeviceVariants(
    baseClass: string,
    devices: DeviceType[] = ['mobile', 'tablet', 'desktop']
  ): Record<DeviceType, string> {
    const variants: Record<DeviceType, string> = {} as Record<DeviceType, string>;

    devices.forEach(device => {
      const prefix = this.responsivePrefixes[device];
      variants[device] = prefix ? `${prefix}${baseClass}` : baseClass;
    });

    return variants;
  }

  /**
   * Validate Tailwind class compatibility
   */
  public validateTailwindClass(className: string): {
    valid: boolean;
    responsive: boolean;
    category?: string;
    property?: string;
  } {
    // Extract responsive prefix if present
    const responsiveMatch = className.match(/^(sm:|md:|lg:|xl:|2xl:)?(.+)$/);
    const hasResponsivePrefix = !!responsiveMatch?.[1];
    const baseClass = responsiveMatch?.[2] || className;

    // Search through all mappings to find the class
    for (const componentMappings of Object.values(this.tailwindMappings)) {
      for (const elementMappings of Object.values(componentMappings)) {
        for (const mapping of elementMappings) {
          const foundClass = mapping.tailwindClasses.find(tc => tc.class === baseClass);
          if (foundClass) {
            return {
              valid: true,
              responsive: mapping.responsive && hasResponsivePrefix,
              category: foundClass.category,
              property: mapping.property
            };
          }
        }
      }
    }

    return { valid: false, responsive: false };
  }

  /**
   * Get available responsive variants for a class
   */
  public getResponsiveVariants(baseClass: string): string[] {
    const variants: string[] = [baseClass]; // Base class (mobile-first)
    
    // Add responsive variants
    Object.values(this.responsivePrefixes).forEach(prefix => {
      if (prefix) {
        variants.push(`${prefix}${baseClass}`);
      }
    });

    return variants;
  }

  /**
   * Extract component-specific Tailwind utilities
   */
  public extractComponentUtilities(componentType: string): {
    spacing: string[];
    colors: string[];
    typography: string[];
    layout: string[];
    effects: string[];
  } {
    const mappings = this.getMappingsForComponent(componentType);
    const utilities = {
      spacing: [] as string[],
      colors: [] as string[],
      typography: [] as string[],
      layout: [] as string[],
      effects: [] as string[]
    };

    Object.values(mappings).forEach(elementMappings => {
      elementMappings.forEach(mapping => {
        mapping.tailwindClasses.forEach(tc => {
          const category = tc.category as keyof typeof utilities;
          if (category && utilities[category]) {
            utilities[category].push(tc.class);
          }
        });
      });
    });

    // Remove duplicates and sort
    Object.keys(utilities).forEach(key => {
      const categoryKey = key as keyof typeof utilities;
      utilities[categoryKey] = [...new Set(utilities[categoryKey])].sort();
    });

    return utilities;
  }

  /**
   * Generate CSS custom properties for dynamic theming
   */
  public generateCSSCustomProperties(
    componentType: string,
    theme: Record<string, string>
  ): Record<string, string> {
    const customProperties: Record<string, string> = {};
    const mappings = this.getMappingsForComponent(componentType);

    Object.entries(theme).forEach(([property, value]) => {
      // Convert property name to CSS custom property
      const customPropertyName = `--${componentType}-${property.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
      customProperties[customPropertyName] = value;
    });

    return customProperties;
  }

  private capitalizeSectorName(category: string): string {
    return category.charAt(0).toUpperCase() + category.slice(1);
  }
}
export d
efault TailwindMappingService;