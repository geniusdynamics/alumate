import type {
  ResponsiveBreakpoint,
  ResponsiveConfig,
  DeviceType,
  BreakpointName,
  DeviceSpecificConfig,
  ResponsiveComponentVariant,
  ResponsiveComponentConfig,
  ComponentConstraints,
  ResponsiveConstraint,
  TailwindClassMapping,
  TailwindStyleMapping
} from '@/types/components';

/**
 * Service for managing responsive configurations and device-specific variants
 * Integrates with GrapeJS Device Manager for seamless responsive design
 */
export class ResponsiveConfigService {
  private static instance: ResponsiveConfigService;
  
  // Default responsive configuration matching Tailwind CSS breakpoints
  private readonly defaultConfig: ResponsiveConfig = {
    breakpoints: [
      {
        name: 'xs',
        minWidth: 0,
        maxWidth: 639,
        device: 'mobile',
        label: 'Extra Small',
        icon: 'smartphone',
        grapeJSDevice: 'mobile'
      },
      {
        name: 'sm',
        minWidth: 640,
        maxWidth: 767,
        device: 'mobile',
        label: 'Small',
        icon: 'smartphone',
        grapeJSDevice: 'mobile'
      },
      {
        name: 'md',
        minWidth: 768,
        maxWidth: 1023,
        device: 'tablet',
        label: 'Medium',
        icon: 'tablet',
        grapeJSDevice: 'tablet'
      },
      {
        name: 'lg',
        minWidth: 1024,
        maxWidth: 1279,
        device: 'desktop',
        label: 'Large',
        icon: 'desktop',
        grapeJSDevice: 'desktop'
      },
      {
        name: 'xl',
        minWidth: 1280,
        maxWidth: 1535,
        device: 'desktop',
        label: 'Extra Large',
        icon: 'desktop',
        grapeJSDevice: 'desktop'
      },
      {
        name: '2xl',
        minWidth: 1536,
        device: 'desktop',
        label: '2X Large',
        icon: 'desktop',
        grapeJSDevice: 'desktop'
      }
    ],
    defaultBreakpoint: 'lg',
    enabledDevices: ['desktop', 'tablet', 'mobile']
  };

  // Default Tailwind CSS class mappings for common properties
  private readonly defaultTailwindMappings: TailwindStyleMapping = {
    '*': {
      '.component-root': [
        {
          property: 'padding',
          tailwindClasses: [
            { class: 'p-0', value: '0', label: 'None' },
            { class: 'p-1', value: '0.25rem', label: 'XS' },
            { class: 'p-2', value: '0.5rem', label: 'SM' },
            { class: 'p-4', value: '1rem', label: 'MD' },
            { class: 'p-6', value: '1.5rem', label: 'LG' },
            { class: 'p-8', value: '2rem', label: 'XL' }
          ],
          grapeJSProperty: 'padding',
          responsive: true
        },
        {
          property: 'margin',
          tailwindClasses: [
            { class: 'm-0', value: '0', label: 'None' },
            { class: 'm-1', value: '0.25rem', label: 'XS' },
            { class: 'm-2', value: '0.5rem', label: 'SM' },
            { class: 'm-4', value: '1rem', label: 'MD' },
            { class: 'm-6', value: '1.5rem', label: 'LG' },
            { class: 'm-8', value: '2rem', label: 'XL' }
          ],
          grapeJSProperty: 'margin',
          responsive: true
        },
        {
          property: 'display',
          tailwindClasses: [
            { class: 'block', value: 'block', label: 'Block' },
            { class: 'inline-block', value: 'inline-block', label: 'Inline Block' },
            { class: 'flex', value: 'flex', label: 'Flex' },
            { class: 'grid', value: 'grid', label: 'Grid' },
            { class: 'hidden', value: 'none', label: 'Hidden' }
          ],
          grapeJSProperty: 'display',
          responsive: true
        }
      ]
    }
  };

  public static getInstance(): ResponsiveConfigService {
    if (!ResponsiveConfigService.instance) {
      ResponsiveConfigService.instance = new ResponsiveConfigService();
    }
    return ResponsiveConfigService.instance;
  }

  /**
   * Get the default responsive configuration
   */
  public getDefaultConfig(): ResponsiveConfig {
    return { ...this.defaultConfig };
  }

  /**
   * Get breakpoint information by name
   */
  public getBreakpoint(name: BreakpointName): ResponsiveBreakpoint | undefined {
    return this.defaultConfig.breakpoints.find(bp => bp.name === name);
  }

  /**
   * Get all breakpoints for a specific device type
   */
  public getBreakpointsForDevice(device: DeviceType): ResponsiveBreakpoint[] {
    return this.defaultConfig.breakpoints.filter(bp => bp.device === device);
  }

  /**
   * Get the current breakpoint based on viewport width
   */
  public getCurrentBreakpoint(width: number): ResponsiveBreakpoint {
    const breakpoints = [...this.defaultConfig.breakpoints].reverse();
    return breakpoints.find(bp => width >= bp.minWidth) || this.defaultConfig.breakpoints[0];
  }

  /**
   * Create device-specific component variants
   */
  public createDeviceVariants<T>(
    baseConfig: T,
    deviceOverrides: DeviceSpecificConfig<Partial<T>> = {}
  ): ResponsiveComponentVariant[] {
    const variants: ResponsiveComponentVariant[] = [];

    this.defaultConfig.enabledDevices.forEach(device => {
      const deviceBreakpoints = this.getBreakpointsForDevice(device);
      
      deviceBreakpoints.forEach(breakpoint => {
        const deviceConfig = deviceOverrides[device] || {};
        const mergedConfig = { ...baseConfig, ...deviceConfig };

        variants.push({
          device,
          breakpoint: breakpoint.name,
          config: mergedConfig,
          enabled: true,
          inheritFromParent: Object.keys(deviceConfig).length === 0,
          customizations: deviceConfig
        });
      });
    });

    return variants;
  }

  /**
   * Create enhanced device-specific variants with constraint validation
   */
  public createEnhancedDeviceVariants<T>(
    componentType: string,
    baseConfig: T,
    deviceOverrides: DeviceSpecificConfig<Partial<T>> = {},
    constraints?: ComponentConstraints
  ): {
    variants: ResponsiveComponentVariant[];
    validationResults: Record<DeviceType, { valid: boolean; errors: string[]; warnings: string[] }>;
  } {
    const variants = this.createDeviceVariants(baseConfig, deviceOverrides);
    const validationResults: Record<DeviceType, { valid: boolean; errors: string[]; warnings: string[] }> = {} as any;

    // Validate each device variant against constraints
    this.defaultConfig.enabledDevices.forEach(device => {
      const deviceVariants = variants.filter(v => v.device === device);
      const deviceConfig = deviceOverrides[device] || {};
      
      validationResults[device] = this.validateDeviceVariant(
        componentType,
        device,
        { ...baseConfig, ...deviceConfig },
        constraints
      );
    });

    return { variants, validationResults };
  }

  /**
   * Validate device-specific variant against constraints
   */
  private validateDeviceVariant(
    componentType: string,
    device: DeviceType,
    config: any,
    constraints?: ComponentConstraints
  ): { valid: boolean; errors: string[]; warnings: string[] } {
    const errors: string[] = [];
    const warnings: string[] = [];

    if (!constraints) {
      return { valid: true, errors, warnings };
    }

    // Validate responsive constraints for this device
    constraints.responsive.forEach(constraint => {
      if (constraint.device && constraint.device !== device) {
        return; // Skip constraints not applicable to this device
      }

      // Perform device-specific validation
      switch (constraint.type) {
        case 'minWidth':
          if (device === 'mobile' && constraint.value > 320) {
            if (constraint.severity === 'error') {
              errors.push(`Minimum width constraint violated for ${device}: ${constraint.message}`);
            } else {
              warnings.push(`Minimum width recommendation for ${device}: ${constraint.message}`);
            }
          }
          break;

        case 'touchTarget':
          if (device === 'mobile' && constraint.value < 44) {
            if (constraint.severity === 'error') {
              errors.push(`Touch target size too small for ${device}: ${constraint.message}`);
            } else {
              warnings.push(`Touch target size recommendation for ${device}: ${constraint.message}`);
            }
          }
          break;

        case 'textSize':
          if (device === 'mobile' && constraint.value < 16) {
            if (constraint.severity === 'error') {
              errors.push(`Text size too small for ${device}: ${constraint.message}`);
            } else {
              warnings.push(`Text size recommendation for ${device}: ${constraint.message}`);
            }
          }
          break;
      }
    });

    return {
      valid: errors.length === 0,
      errors,
      warnings
    };
  }

  /**
   * Generate component grouping metadata for GrapeJS operations
   */
  public generateComponentGroupingMetadata(
    componentType: string,
    config: any
  ): {
    category: string;
    tags: string[];
    relationships: string[];
    grapeJSCategory: string;
  } {
    const baseMetadata = {
      category: componentType,
      tags: [componentType],
      relationships: [],
      grapeJSCategory: this.getGrapeJSCategoryName(componentType)
    };

    // Add device-specific tags
    if (config.responsive) {
      if (config.responsive.mobile) {
        baseMetadata.tags.push('mobile-optimized');
      }
      if (config.responsive.tablet) {
        baseMetadata.tags.push('tablet-friendly');
      }
      if (config.responsive.desktop) {
        baseMetadata.tags.push('desktop-enhanced');
      }
    }

    // Add accessibility tags
    if (config.accessibility) {
      baseMetadata.tags.push('accessible');
      if (config.accessibility.keyboardNavigation?.focusable) {
        baseMetadata.tags.push('keyboard-accessible');
      }
      if (config.accessibility.motionPreferences?.respectReducedMotion) {
        baseMetadata.tags.push('motion-safe');
      }
    }

    // Add component-specific tags
    switch (componentType) {
      case 'hero':
        baseMetadata.tags.push('landing', 'header', 'banner');
        break;
      case 'forms':
        baseMetadata.tags.push('lead-capture', 'contact', 'interactive');
        break;
      case 'testimonials':
        baseMetadata.tags.push('social-proof', 'reviews', 'trust');
        break;
      case 'statistics':
        baseMetadata.tags.push('data', 'metrics', 'achievements');
        break;
      case 'ctas':
        baseMetadata.tags.push('conversion', 'buttons', 'actions');
        break;
      case 'media':
        baseMetadata.tags.push('visual', 'content', 'gallery');
        break;
    }

    return baseMetadata;
  }

  /**
   * Get GrapeJS category name for component type
   */
  private getGrapeJSCategoryName(componentType: string): string {
    const categoryMap: Record<string, string> = {
      hero: 'Hero Components',
      forms: 'Forms & Inputs',
      testimonials: 'Social Proof',
      statistics: 'Data & Metrics',
      ctas: 'Call-to-Actions',
      media: 'Media & Content'
    };

    return categoryMap[componentType] || 'Components';
  }

  /**
   * Generate responsive component configuration
   */
  public generateResponsiveConfig<T>(
    baseConfig: T,
    deviceOverrides: DeviceSpecificConfig<Partial<T>> = {},
    accessibilityMetadata: any = {},
    constraints: ComponentConstraints | null = null
  ): ResponsiveComponentConfig {
    return {
      base: baseConfig,
      responsive: {
        desktop: deviceOverrides.desktop || {},
        tablet: deviceOverrides.tablet || {},
        mobile: deviceOverrides.mobile || {}
      },
      breakpoints: this.generateBreakpointOverrides(deviceOverrides),
      accessibility: {
        semanticTag: 'div',
        keyboardNavigation: {
          focusable: false
        },
        motionPreferences: {
          respectReducedMotion: true
        },
        ...accessibilityMetadata
      },
      constraints: constraints || this.getDefaultConstraints(),
      tailwindMapping: this.defaultTailwindMappings,
      grapeJSMetadata: {
        deviceManager: {
          desktop: { 
            width: 1200, 
            height: 800, 
            widthMedia: 'min-width: 1024px',
            label: 'Desktop',
            icon: 'desktop'
          },
          tablet: { 
            width: 768, 
            height: 1024, 
            widthMedia: 'min-width: 768px and max-width: 1023px',
            label: 'Tablet',
            icon: 'tablet'
          },
          mobile: { 
            width: 375, 
            height: 667, 
            widthMedia: 'max-width: 767px',
            label: 'Mobile',
            icon: 'smartphone'
          }
        },
        styleManager: {
          sectors: [
            {
              name: 'Layout',
              properties: ['display', 'position', 'top', 'right', 'bottom', 'left', 'z-index', 'overflow']
            },
            {
              name: 'Spacing',
              properties: ['padding', 'margin', 'gap']
            },
            {
              name: 'Sizing',
              properties: ['width', 'height', 'min-width', 'min-height', 'max-width', 'max-height']
            },
            {
              name: 'Typography',
              properties: ['font-family', 'font-size', 'font-weight', 'line-height', 'text-align', 'letter-spacing']
            },
            {
              name: 'Colors',
              properties: ['color', 'background-color', 'border-color', 'opacity']
            },
            {
              name: 'Borders',
              properties: ['border-width', 'border-style', 'border-radius']
            },
            {
              name: 'Effects',
              properties: ['box-shadow', 'transform', 'filter', 'backdrop-filter']
            },
            {
              name: 'Flexbox',
              properties: ['flex-direction', 'justify-content', 'align-items', 'flex-wrap', 'align-content']
            },
            {
              name: 'Grid',
              properties: ['grid-template-columns', 'grid-template-rows', 'grid-gap', 'grid-auto-flow']
            }
          ]
        },
        traitManager: {
          traits: this.generateDefaultTraits()
        }
      }
    };
  }

  /**
   * Generate breakpoint-specific overrides from device overrides
   */
  private generateBreakpointOverrides<T>(
    deviceOverrides: DeviceSpecificConfig<Partial<T>>
  ): { [K in BreakpointName]?: any } {
    const breakpointOverrides: { [K in BreakpointName]?: any } = {};

    // Map device overrides to specific breakpoints
    if (deviceOverrides.mobile) {
      breakpointOverrides.xs = deviceOverrides.mobile;
      breakpointOverrides.sm = deviceOverrides.mobile;
    }

    if (deviceOverrides.tablet) {
      breakpointOverrides.md = deviceOverrides.tablet;
    }

    if (deviceOverrides.desktop) {
      breakpointOverrides.lg = deviceOverrides.desktop;
      breakpointOverrides.xl = deviceOverrides.desktop;
      breakpointOverrides['2xl'] = deviceOverrides.desktop;
    }

    return breakpointOverrides;
  }

  /**
   * Generate default GrapeJS traits for responsive components
   */
  private generateDefaultTraits(): any[] {
    return [
      {
        type: 'checkbox',
        name: 'responsive-enabled',
        label: 'Enable Responsive Design',
        changeProp: true,
        default: true
      },
      {
        type: 'select',
        name: 'responsive-breakpoint',
        label: 'Active Breakpoint',
        options: this.defaultConfig.breakpoints.map(bp => ({
          id: bp.name,
          name: bp.label,
          value: bp.name
        })),
        changeProp: true
      },
      {
        type: 'text',
        name: 'aria-label',
        label: 'ARIA Label',
        placeholder: 'Describe the component for screen readers'
      },
      {
        type: 'select',
        name: 'semantic-tag',
        label: 'Semantic Tag',
        options: [
          { id: 'div', name: 'Div' },
          { id: 'section', name: 'Section' },
          { id: 'article', name: 'Article' },
          { id: 'header', name: 'Header' },
          { id: 'footer', name: 'Footer' },
          { id: 'nav', name: 'Navigation' },
          { id: 'aside', name: 'Aside' },
          { id: 'main', name: 'Main' }
        ]
      },
      {
        type: 'checkbox',
        name: 'keyboard-focusable',
        label: 'Keyboard Focusable',
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'reduced-motion',
        label: 'Respect Reduced Motion',
        changeProp: true,
        default: true
      }
    ];
  }

  /**
   * Get default responsive constraints
   */
  public getDefaultConstraints(): ComponentConstraints {
    return {
      responsive: [
        {
          type: 'minWidth',
          device: 'mobile',
          value: 320,
          unit: 'px',
          message: 'Component must be at least 320px wide on mobile',
          severity: 'warning',
          autoFix: true,
          fixAction: 'Set minimum width to 320px'
        },
        {
          type: 'touchTarget',
          device: 'mobile',
          value: 44,
          unit: 'px',
          message: 'Interactive elements must be at least 44px for touch accessibility',
          severity: 'error',
          autoFix: true,
          fixAction: 'Increase touch target size'
        },
        {
          type: 'textSize',
          device: 'mobile',
          value: 16,
          unit: 'px',
          message: 'Text should be at least 16px on mobile for readability',
          severity: 'warning',
          autoFix: true,
          fixAction: 'Increase font size'
        }
      ],
      accessibility: [
        {
          type: 'contrast',
          requirement: 'WCAG AA (4.5:1)',
          message: 'Text contrast must meet WCAG AA standards',
          severity: 'error',
          autoFix: false
        },
        {
          type: 'focusable',
          requirement: 'Interactive elements must be focusable',
          message: 'All interactive elements must be keyboard accessible',
          severity: 'error',
          autoFix: true
        },
        {
          type: 'semantic',
          requirement: 'Use semantic HTML elements',
          message: 'Components should use appropriate semantic HTML',
          severity: 'warning',
          autoFix: false
        }
      ],
      performance: [
        {
          type: 'imageSize',
          threshold: 500,
          unit: 'KB',
          message: 'Images should be optimized and under 500KB',
          severity: 'warning'
        },
        {
          type: 'loadTime',
          threshold: 3,
          unit: 'seconds',
          message: 'Component should load within 3 seconds',
          severity: 'error'
        }
      ]
    };
  }

  /**
   * Validate responsive configuration against constraints
   */
  public validateResponsiveConfig(
    config: ResponsiveComponentConfig,
    device?: DeviceType
  ): { valid: boolean; errors: string[]; warnings: string[] } {
    const errors: string[] = [];
    const warnings: string[] = [];

    if (!config.constraints) {
      return { valid: true, errors, warnings };
    }

    // Validate responsive constraints
    config.constraints.responsive.forEach(constraint => {
      if (device && constraint.device && constraint.device !== device) {
        return; // Skip constraints not applicable to current device
      }

      const deviceConfig = device ? config.responsive[device] : config.base;
      
      // Perform constraint validation based on type
      switch (constraint.type) {
        case 'minWidth':
          // Implementation would check actual rendered width
          break;
        case 'touchTarget':
          // Implementation would check interactive element sizes
          break;
        case 'textSize':
          // Implementation would check font sizes
          break;
      }

      // Add to appropriate array based on severity
      if (constraint.severity === 'error') {
        // errors.push(constraint.message || 'Constraint validation failed');
      } else if (constraint.severity === 'warning') {
        // warnings.push(constraint.message || 'Constraint validation warning');
      }
    });

    return {
      valid: errors.length === 0,
      errors,
      warnings
    };
  }

  /**
   * Generate Tailwind CSS classes for responsive design
   */
  public generateResponsiveClasses(
    property: string,
    values: DeviceSpecificConfig<string>,
    prefix: string = ''
  ): string[] {
    const classes: string[] = [];

    // Add base class (mobile-first)
    if (values.mobile) {
      classes.push(`${prefix}${values.mobile}`);
    }

    // Add tablet-specific class
    if (values.tablet) {
      classes.push(`md:${prefix}${values.tablet}`);
    }

    // Add desktop-specific class
    if (values.desktop) {
      classes.push(`lg:${prefix}${values.desktop}`);
    }

    return classes;
  }

  /**
   * Get GrapeJS device manager configuration
   */
  public getGrapeJSDeviceConfig() {
    return {
      devices: [
        {
          id: 'desktop',
          name: 'Desktop',
          width: '1200px',
          height: '800px',
          widthMedia: 'min-width: 1024px'
        },
        {
          id: 'tablet',
          name: 'Tablet',
          width: '768px',
          height: '1024px',
          widthMedia: 'min-width: 768px and max-width: 1023px'
        },
        {
          id: 'mobile',
          name: 'Mobile',
          width: '375px',
          height: '667px',
          widthMedia: 'max-width: 767px'
        }
      ]
    };
  }

  /**
   * Convert component configuration to GrapeJS-compatible format
   */
  public convertToGrapeJSFormat(config: ResponsiveComponentConfig): any {
    return {
      style: this.generateGrapeJSStyles(config),
      attributes: this.generateGrapeJSAttributes(config),
      traits: this.generateGrapeJSTraits(config)
    };
  }

  private generateGrapeJSStyles(config: ResponsiveComponentConfig): any {
    const styles: any = {};

    // Generate base styles
    Object.entries(config.base || {}).forEach(([key, value]) => {
      if (this.isCSSProperty(key)) {
        styles[key] = value;
      }
    });

    // Generate responsive styles
    Object.entries(config.responsive || {}).forEach(([device, deviceConfig]) => {
      const breakpoint = this.getBreakpointsForDevice(device as DeviceType)[0];
      if (breakpoint && deviceConfig) {
        const mediaQuery = this.getMediaQuery(breakpoint);
        styles[`@media ${mediaQuery}`] = deviceConfig;
      }
    });

    return styles;
  }

  private generateGrapeJSAttributes(config: ResponsiveComponentConfig): any {
    const attributes: any = {};

    // Add accessibility attributes
    if (config.accessibility) {
      if (config.accessibility.ariaLabel) {
        attributes['aria-label'] = config.accessibility.ariaLabel;
      }
      if (config.accessibility.role) {
        attributes.role = config.accessibility.role;
      }
      if (config.accessibility.tabIndex !== undefined) {
        attributes.tabindex = config.accessibility.tabIndex;
      }
    }

    return attributes;
  }

  private generateGrapeJSTraits(config: ResponsiveComponentConfig): any[] {
    const traits: any[] = [];

    // Add responsive configuration traits
    this.defaultConfig.enabledDevices.forEach(device => {
      traits.push({
        type: 'checkbox',
        name: `responsive-${device}`,
        label: `Enable ${device} customization`,
        changeProp: true
      });
    });

    // Add accessibility traits
    traits.push(
      {
        type: 'text',
        name: 'aria-label',
        label: 'ARIA Label',
        placeholder: 'Describe the component for screen readers'
      },
      {
        type: 'select',
        name: 'semantic-tag',
        label: 'Semantic Tag',
        options: [
          { id: 'div', name: 'Div' },
          { id: 'section', name: 'Section' },
          { id: 'article', name: 'Article' },
          { id: 'header', name: 'Header' },
          { id: 'footer', name: 'Footer' },
          { id: 'nav', name: 'Navigation' },
          { id: 'aside', name: 'Aside' }
        ]
      }
    );

    return traits;
  }

  private getMediaQuery(breakpoint: ResponsiveBreakpoint): string {
    if (breakpoint.maxWidth) {
      return `(min-width: ${breakpoint.minWidth}px) and (max-width: ${breakpoint.maxWidth}px)`;
    }
    return `(min-width: ${breakpoint.minWidth}px)`;
  }

  private isCSSProperty(key: string): boolean {
    // Simple check for CSS properties - could be expanded
    const cssProperties = [
      'width', 'height', 'padding', 'margin', 'color', 'background-color',
      'font-size', 'font-weight', 'line-height', 'text-align', 'display',
      'position', 'top', 'right', 'bottom', 'left', 'z-index', 'opacity'
    ];
    return cssProperties.includes(key);
  }
}

export default ResponsiveConfigService;