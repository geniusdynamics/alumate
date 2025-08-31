import type {
  ResponsiveConstraint,
  ComponentConstraints,
  DeviceType,
  ResponsiveComponentConfig
} from '@/types/components';

export interface ConstraintValidationResult {
  valid: boolean;
  errors: ConstraintViolation[];
  warnings: ConstraintViolation[];
  autoFixSuggestions: AutoFixSuggestion[];
}

export interface ConstraintViolation {
  constraint: ResponsiveConstraint;
  actualValue?: any;
  expectedValue?: any;
  device?: DeviceType;
  element?: string;
  severity: 'error' | 'warning' | 'info';
  message: string;
}

export interface AutoFixSuggestion {
  constraint: ResponsiveConstraint;
  action: string;
  description: string;
  cssChanges?: Record<string, string>;
  configChanges?: Record<string, any>;
}

/**
 * Validates responsive design constraints for component compliance
 * Ensures components meet accessibility and usability standards across devices
 */
export class ResponsiveConstraintValidator {
  private static instance: ResponsiveConstraintValidator;

  // Standard constraint definitions for different component types
  private readonly standardConstraints: Record<string, ComponentConstraints> = {
    hero: {
      responsive: [
        {
          type: 'minWidth',
          device: 'mobile',
          value: 320,
          unit: 'px',
          message: 'Hero component must be at least 320px wide on mobile devices',
          severity: 'error',
          autoFix: true,
          fixAction: 'Set min-width: 320px for mobile breakpoint'
        },
        {
          type: 'touchTarget',
          device: 'mobile',
          value: 44,
          unit: 'px',
          message: 'CTA buttons in hero must be at least 44px for touch accessibility',
          severity: 'error',
          autoFix: true,
          fixAction: 'Increase button height and padding'
        },
        {
          type: 'textSize',
          device: 'mobile',
          value: 16,
          unit: 'px',
          message: 'Hero text should be at least 16px on mobile for readability',
          severity: 'warning',
          autoFix: true,
          fixAction: 'Increase font-size to 16px or larger'
        },
        {
          type: 'aspectRatio',
          device: 'mobile',
          value: '16:9',
          message: 'Hero background media should maintain reasonable aspect ratio on mobile',
          severity: 'warning',
          autoFix: false
        }
      ],
      accessibility: [
        {
          type: 'contrast',
          requirement: 'WCAG AA (4.5:1)',
          message: 'Hero text must have sufficient contrast against background',
          severity: 'error',
          autoFix: false
        },
        {
          type: 'focusable',
          requirement: 'Interactive elements must be focusable',
          message: 'Hero CTA buttons must be keyboard accessible',
          severity: 'error',
          autoFix: true
        },
        {
          type: 'semantic',
          requirement: 'Use header tag for hero sections',
          message: 'Hero should use semantic header element',
          severity: 'warning',
          autoFix: true
        }
      ],
      performance: [
        {
          type: 'imageSize',
          threshold: 1000,
          unit: 'KB',
          message: 'Hero background images should be optimized and under 1MB',
          severity: 'warning'
        },
        {
          type: 'loadTime',
          threshold: 2,
          unit: 'seconds',
          message: 'Hero should load within 2 seconds',
          severity: 'error'
        }
      ]
    },

    forms: {
      responsive: [
        {
          type: 'minWidth',
          device: 'mobile',
          value: 280,
          unit: 'px',
          message: 'Form fields must be at least 280px wide on mobile',
          severity: 'error',
          autoFix: true,
          fixAction: 'Set min-width: 280px for form fields'
        },
        {
          type: 'touchTarget',
          device: 'mobile',
          value: 44,
          unit: 'px',
          message: 'Form inputs and buttons must be at least 44px tall for touch',
          severity: 'error',
          autoFix: true,
          fixAction: 'Increase input height and button size'
        },
        {
          type: 'spacing',
          device: 'mobile',
          value: 16,
          unit: 'px',
          message: 'Form fields should have adequate spacing on mobile',
          severity: 'warning',
          autoFix: true,
          fixAction: 'Add margin between form fields'
        }
      ],
      accessibility: [
        {
          type: 'contrast',
          requirement: 'WCAG AA (4.5:1)',
          message: 'Form labels and inputs must have sufficient contrast',
          severity: 'error',
          autoFix: false
        },
        {
          type: 'focusable',
          requirement: 'All form controls must be focusable',
          message: 'Form inputs must be keyboard accessible',
          severity: 'error',
          autoFix: true
        },
        {
          type: 'semantic',
          requirement: 'Use proper form elements and labels',
          message: 'Forms should use semantic form elements with proper labels',
          severity: 'error',
          autoFix: true
        }
      ],
      performance: [
        {
          type: 'loadTime',
          threshold: 1,
          unit: 'seconds',
          message: 'Forms should load and be interactive within 1 second',
          severity: 'error'
        }
      ]
    },

    ctas: {
      responsive: [
        {
          type: 'touchTarget',
          device: 'mobile',
          value: 44,
          unit: 'px',
          message: 'CTA buttons must be at least 44px for touch accessibility',
          severity: 'error',
          autoFix: true,
          fixAction: 'Increase button size to meet touch target requirements'
        },
        {
          type: 'spacing',
          device: 'mobile',
          value: 8,
          unit: 'px',
          message: 'CTA buttons should have adequate spacing between them',
          severity: 'warning',
          autoFix: true,
          fixAction: 'Add margin between buttons'
        }
      ],
      accessibility: [
        {
          type: 'contrast',
          requirement: 'WCAG AA (4.5:1)',
          message: 'CTA button text must have sufficient contrast',
          severity: 'error',
          autoFix: false
        },
        {
          type: 'focusable',
          requirement: 'Buttons must be focusable',
          message: 'CTA buttons must be keyboard accessible',
          severity: 'error',
          autoFix: true
        }
      ],
      performance: [
        {
          type: 'loadTime',
          threshold: 0.5,
          unit: 'seconds',
          message: 'CTA buttons should be interactive within 0.5 seconds',
          severity: 'warning'
        }
      ]
    },

    media: {
      responsive: [
        {
          type: 'maxWidth',
          device: 'mobile',
          value: 100,
          unit: '%',
          message: 'Media should not exceed container width on mobile',
          severity: 'error',
          autoFix: true,
          fixAction: 'Set max-width: 100% for responsive images'
        },
        {
          type: 'aspectRatio',
          device: 'mobile',
          value: '16:9',
          message: 'Media should maintain appropriate aspect ratio on mobile',
          severity: 'warning',
          autoFix: false
        }
      ],
      accessibility: [
        {
          type: 'semantic',
          requirement: 'Images must have alt text',
          message: 'All images must have descriptive alt text',
          severity: 'error',
          autoFix: false
        },
        {
          type: 'focusable',
          requirement: 'Interactive media must be focusable',
          message: 'Clickable media elements must be keyboard accessible',
          severity: 'error',
          autoFix: true
        }
      ],
      performance: [
        {
          type: 'imageSize',
          threshold: 500,
          unit: 'KB',
          message: 'Images should be optimized and under 500KB',
          severity: 'warning'
        }
      ]
    }
  };

  public static getInstance(): ResponsiveConstraintValidator {
    if (!ResponsiveConstraintValidator.instance) {
      ResponsiveConstraintValidator.instance = new ResponsiveConstraintValidator();
    }
    return ResponsiveConstraintValidator.instance;
  }

  /**
   * Validate component configuration against responsive constraints
   */
  public validateComponent(
    componentType: string,
    config: ResponsiveComponentConfig,
    customConstraints?: ComponentConstraints
  ): ConstraintValidationResult {
    const constraints = customConstraints || this.standardConstraints[componentType];
    
    if (!constraints) {
      return {
        valid: true,
        errors: [],
        warnings: [],
        autoFixSuggestions: []
      };
    }

    const errors: ConstraintViolation[] = [];
    const warnings: ConstraintViolation[] = [];
    const autoFixSuggestions: AutoFixSuggestion[] = [];

    // Validate responsive constraints
    this.validateResponsiveConstraints(
      config,
      constraints.responsive,
      errors,
      warnings,
      autoFixSuggestions
    );

    // Validate accessibility constraints
    this.validateAccessibilityConstraints(
      config,
      constraints.accessibility,
      errors,
      warnings,
      autoFixSuggestions
    );

    // Validate performance constraints
    this.validatePerformanceConstraints(
      config,
      constraints.performance,
      errors,
      warnings
    );

    return {
      valid: errors.length === 0,
      errors,
      warnings,
      autoFixSuggestions
    };
  }

  /**
   * Validate responsive design constraints
   */
  private validateResponsiveConstraints(
    config: ResponsiveComponentConfig,
    constraints: ResponsiveConstraint[],
    errors: ConstraintViolation[],
    warnings: ConstraintViolation[],
    autoFixSuggestions: AutoFixSuggestion[]
  ): void {
    constraints.forEach(constraint => {
      const violation = this.checkResponsiveConstraint(config, constraint);
      
      if (violation) {
        if (constraint.severity === 'error') {
          errors.push(violation);
        } else if (constraint.severity === 'warning') {
          warnings.push(violation);
        }

        // Generate auto-fix suggestion if available
        if (constraint.autoFix) {
          const suggestion = this.generateAutoFixSuggestion(constraint, violation);
          if (suggestion) {
            autoFixSuggestions.push(suggestion);
          }
        }
      }
    });
  }

  /**
   * Check individual responsive constraint
   */
  private checkResponsiveConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint
  ): ConstraintViolation | null {
    const deviceConfig = constraint.device 
      ? config.responsive[constraint.device] || config.base
      : config.base;

    switch (constraint.type) {
      case 'minWidth':
        return this.checkMinWidthConstraint(config, constraint, deviceConfig);
      
      case 'maxWidth':
        return this.checkMaxWidthConstraint(config, constraint, deviceConfig);
      
      case 'touchTarget':
        return this.checkTouchTargetConstraint(config, constraint, deviceConfig);
      
      case 'textSize':
        return this.checkTextSizeConstraint(config, constraint, deviceConfig);
      
      case 'spacing':
        return this.checkSpacingConstraint(config, constraint, deviceConfig);
      
      case 'aspectRatio':
        return this.checkAspectRatioConstraint(config, constraint, deviceConfig);
      
      default:
        return null;
    }
  }

  private checkMinWidthConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    // This would typically check actual rendered dimensions
    // For now, we'll check if responsive configuration exists
    if (constraint.device && !config.responsive[constraint.device]) {
      return {
        constraint,
        device: constraint.device,
        severity: constraint.severity,
        message: constraint.message || `Missing responsive configuration for ${constraint.device}`
      };
    }
    return null;
  }

  private checkMaxWidthConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    // Similar to minWidth, would check actual dimensions in real implementation
    return null;
  }

  private checkTouchTargetConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    if (constraint.device === 'mobile') {
      // Check if mobile-specific touch target sizes are configured
      const mobileConfig = config.responsive.mobile;
      if (!mobileConfig || !this.hasTouchFriendlyElements(mobileConfig)) {
        return {
          constraint,
          device: constraint.device,
          severity: constraint.severity,
          message: constraint.message || 'Touch targets may be too small for mobile devices'
        };
      }
    }
    return null;
  }

  private checkTextSizeConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    if (constraint.device === 'mobile') {
      const mobileConfig = config.responsive.mobile;
      if (!mobileConfig || !this.hasReadableTextSize(mobileConfig)) {
        return {
          constraint,
          device: constraint.device,
          severity: constraint.severity,
          message: constraint.message || 'Text size may be too small for mobile readability'
        };
      }
    }
    return null;
  }

  private checkSpacingConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    // Check if adequate spacing is configured for the device
    return null;
  }

  private checkAspectRatioConstraint(
    config: ResponsiveComponentConfig,
    constraint: ResponsiveConstraint,
    deviceConfig: any
  ): ConstraintViolation | null {
    // Check if aspect ratio is maintained across devices
    return null;
  }

  /**
   * Validate accessibility constraints
   */
  private validateAccessibilityConstraints(
    config: ResponsiveComponentConfig,
    constraints: ComponentConstraints['accessibility'],
    errors: ConstraintViolation[],
    warnings: ConstraintViolation[],
    autoFixSuggestions: AutoFixSuggestion[]
  ): void {
    constraints.forEach(constraint => {
      const violation = this.checkAccessibilityConstraint(config, constraint);
      
      if (violation) {
        if (constraint.severity === 'error') {
          errors.push(violation);
        } else if (constraint.severity === 'warning') {
          warnings.push(violation);
        }

        if (constraint.autoFix) {
          const suggestion = this.generateAccessibilityAutoFix(constraint, violation);
          if (suggestion) {
            autoFixSuggestions.push(suggestion);
          }
        }
      }
    });
  }

  private checkAccessibilityConstraint(
    config: ResponsiveComponentConfig,
    constraint: ComponentConstraints['accessibility'][0]
  ): ConstraintViolation | null {
    switch (constraint.type) {
      case 'contrast':
        return this.checkContrastConstraint(config, constraint);
      
      case 'focusable':
        return this.checkFocusableConstraint(config, constraint);
      
      case 'semantic':
        return this.checkSemanticConstraint(config, constraint);
      
      default:
        return null;
    }
  }

  private checkContrastConstraint(
    config: ResponsiveComponentConfig,
    constraint: ComponentConstraints['accessibility'][0]
  ): ConstraintViolation | null {
    // Check if color contrast information is available
    if (!config.accessibility.colorContrast) {
      return {
        constraint: constraint as any,
        severity: constraint.severity,
        message: constraint.message || 'Color contrast not validated'
      };
    }
    return null;
  }

  private checkFocusableConstraint(
    config: ResponsiveComponentConfig,
    constraint: ComponentConstraints['accessibility'][0]
  ): ConstraintViolation | null {
    if (!config.accessibility.keyboardNavigation?.focusable) {
      return {
        constraint: constraint as any,
        severity: constraint.severity,
        message: constraint.message || 'Interactive elements must be keyboard focusable'
      };
    }
    return null;
  }

  private checkSemanticConstraint(
    config: ResponsiveComponentConfig,
    constraint: ComponentConstraints['accessibility'][0]
  ): ConstraintViolation | null {
    if (!config.accessibility.semanticTag || config.accessibility.semanticTag === 'div') {
      return {
        constraint: constraint as any,
        severity: constraint.severity,
        message: constraint.message || 'Component should use semantic HTML elements'
      };
    }
    return null;
  }

  /**
   * Validate performance constraints
   */
  private validatePerformanceConstraints(
    config: ResponsiveComponentConfig,
    constraints: ComponentConstraints['performance'],
    errors: ConstraintViolation[],
    warnings: ConstraintViolation[]
  ): void {
    constraints.forEach(constraint => {
      const violation = this.checkPerformanceConstraint(config, constraint);
      
      if (violation) {
        if (constraint.severity === 'error') {
          errors.push(violation);
        } else if (constraint.severity === 'warning') {
          warnings.push(violation);
        }
      }
    });
  }

  private checkPerformanceConstraint(
    config: ResponsiveComponentConfig,
    constraint: ComponentConstraints['performance'][0]
  ): ConstraintViolation | null {
    // Performance constraints would typically be checked against actual metrics
    // This is a placeholder for the validation logic
    return null;
  }

  /**
   * Generate auto-fix suggestion for responsive constraint
   */
  private generateAutoFixSuggestion(
    constraint: ResponsiveConstraint,
    violation: ConstraintViolation
  ): AutoFixSuggestion | null {
    if (!constraint.autoFix || !constraint.fixAction) {
      return null;
    }

    const suggestion: AutoFixSuggestion = {
      constraint,
      action: constraint.fixAction,
      description: constraint.message || 'Apply responsive design fix'
    };

    // Generate specific CSS or config changes based on constraint type
    switch (constraint.type) {
      case 'minWidth':
        suggestion.cssChanges = {
          'min-width': `${constraint.value}${constraint.unit}`
        };
        break;
      
      case 'touchTarget':
        suggestion.cssChanges = {
          'min-height': `${constraint.value}${constraint.unit}`,
          'min-width': `${constraint.value}${constraint.unit}`
        };
        break;
      
      case 'textSize':
        suggestion.cssChanges = {
          'font-size': `${constraint.value}${constraint.unit}`
        };
        break;
    }

    return suggestion;
  }

  private generateAccessibilityAutoFix(
    constraint: ComponentConstraints['accessibility'][0],
    violation: ConstraintViolation
  ): AutoFixSuggestion | null {
    if (!constraint.autoFix) {
      return null;
    }

    return {
      constraint: constraint as any,
      action: 'Apply accessibility fix',
      description: constraint.message || 'Fix accessibility issue',
      configChanges: this.getAccessibilityConfigChanges(constraint.type)
    };
  }

  private getAccessibilityConfigChanges(type: string): Record<string, any> {
    switch (type) {
      case 'focusable':
        return {
          'accessibility.keyboardNavigation.focusable': true,
          'accessibility.tabIndex': 0
        };
      
      case 'semantic':
        return {
          'accessibility.semanticTag': 'section'
        };
      
      default:
        return {};
    }
  }

  /**
   * Helper methods for constraint checking
   */
  private hasTouchFriendlyElements(config: any): boolean {
    // Check if configuration includes touch-friendly sizing
    return !!(config.touchTargetSize || config.mobileOptimized);
  }

  private hasReadableTextSize(config: any): boolean {
    // Check if text size is configured for mobile readability
    return !!(config.fontSize || config.mobileTextSize);
  }

  /**
   * Get standard constraints for component type
   */
  public getStandardConstraints(componentType: string): ComponentConstraints | null {
    return this.standardConstraints[componentType] || null;
  }

  /**
   * Apply auto-fix suggestions to component configuration
   */
  public applyAutoFixes(
    config: ResponsiveComponentConfig,
    suggestions: AutoFixSuggestion[]
  ): ResponsiveComponentConfig {
    const fixedConfig = { ...config };

    suggestions.forEach(suggestion => {
      // Apply CSS changes
      if (suggestion.cssChanges) {
        // This would typically update the component's style configuration
        // Implementation depends on how styles are stored in the config
      }

      // Apply config changes
      if (suggestion.configChanges) {
        Object.entries(suggestion.configChanges).forEach(([path, value]) => {
          this.setNestedProperty(fixedConfig, path, value);
        });
      }
    });

    return fixedConfig;
  }

  private setNestedProperty(obj: any, path: string, value: any): void {
    const keys = path.split('.');
    let current = obj;
    
    for (let i = 0; i < keys.length - 1; i++) {
      const key = keys[i];
      if (!(key in current)) {
        current[key] = {};
      }
      current = current[key];
    }
    
    current[keys[keys.length - 1]] = value;
  }
}

export default ResponsiveConstraintValidator;