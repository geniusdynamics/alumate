import type {
  AccessibilityMetadata,
  ComponentConstraints,
  ResponsiveComponentConfig
} from '@/types/components';

export interface AccessibilityValidationResult {
  valid: boolean;
  score: number; // 0-100
  issues: AccessibilityIssue[];
  recommendations: AccessibilityRecommendation[];
}

export interface AccessibilityIssue {
  type: 'error' | 'warning' | 'info';
  category: 'contrast' | 'keyboard' | 'semantic' | 'aria' | 'focus' | 'motion';
  message: string;
  element?: string;
  wcagGuideline?: string;
  autoFixable: boolean;
  fixSuggestion?: string;
}

export interface AccessibilityRecommendation {
  category: string;
  message: string;
  impact: 'high' | 'medium' | 'low';
  implementation: string;
}

/**
 * Validates component accessibility for GrapeJS screen reader support
 * and WCAG compliance
 */
export class AccessibilityValidator {
  private static instance: AccessibilityValidator;

  // WCAG 2.1 AA contrast ratios
  private readonly contrastRequirements = {
    normal: 4.5,
    large: 3.0,
    graphical: 3.0
  };

  // Minimum touch target sizes (iOS/Android guidelines)
  private readonly touchTargetSizes = {
    minimum: 44, // 44px minimum
    recommended: 48 // 48px recommended
  };

  public static getInstance(): AccessibilityValidator {
    if (!AccessibilityValidator.instance) {
      AccessibilityValidator.instance = new AccessibilityValidator();
    }
    return AccessibilityValidator.instance;
  }

  /**
   * Validate component accessibility configuration
   */
  public validateComponent(
    config: ResponsiveComponentConfig,
    componentType: string
  ): AccessibilityValidationResult {
    const issues: AccessibilityIssue[] = [];
    const recommendations: AccessibilityRecommendation[] = [];

    // Validate accessibility metadata
    this.validateAccessibilityMetadata(config.accessibility, issues, recommendations);

    // Validate semantic structure
    this.validateSemanticStructure(config, componentType, issues, recommendations);

    // Validate keyboard navigation
    this.validateKeyboardNavigation(config.accessibility, issues, recommendations);

    // Validate ARIA attributes
    this.validateAriaAttributes(config.accessibility, issues, recommendations);

    // Validate motion preferences
    this.validateMotionPreferences(config.accessibility, issues, recommendations);

    // Validate responsive accessibility
    this.validateResponsiveAccessibility(config, issues, recommendations);

    // Calculate accessibility score
    const score = this.calculateAccessibilityScore(issues);

    return {
      valid: issues.filter(issue => issue.type === 'error').length === 0,
      score,
      issues,
      recommendations
    };
  }

  /**
   * Validate accessibility metadata completeness
   */
  private validateAccessibilityMetadata(
    metadata: AccessibilityMetadata,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    // Check for required ARIA labels
    if (!metadata.ariaLabel && !metadata.ariaLabelledBy) {
      issues.push({
        type: 'warning',
        category: 'aria',
        message: 'Component should have an accessible name (aria-label or aria-labelledby)',
        wcagGuideline: 'WCAG 4.1.2',
        autoFixable: false,
        fixSuggestion: 'Add an aria-label attribute describing the component\'s purpose'
      });
    }

    // Check semantic tag usage
    if (!metadata.semanticTag || metadata.semanticTag === 'div') {
      recommendations.push({
        category: 'semantic',
        message: 'Consider using semantic HTML elements instead of generic div',
        impact: 'medium',
        implementation: 'Use elements like <section>, <article>, <header>, <nav>, etc.'
      });
    }

    // Check heading hierarchy
    if (metadata.headingLevel && metadata.headingLevel > 1) {
      recommendations.push({
        category: 'semantic',
        message: 'Ensure heading levels follow a logical hierarchy',
        impact: 'high',
        implementation: 'Headings should not skip levels (h1 → h2 → h3, not h1 → h3)'
      });
    }

    // Check keyboard navigation
    if (!metadata.keyboardNavigation) {
      issues.push({
        type: 'error',
        category: 'keyboard',
        message: 'Keyboard navigation configuration is missing',
        wcagGuideline: 'WCAG 2.1.1',
        autoFixable: true,
        fixSuggestion: 'Add keyboard navigation configuration'
      });
    }
  }

  /**
   * Validate semantic HTML structure
   */
  private validateSemanticStructure(
    config: ResponsiveComponentConfig,
    componentType: string,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    const semanticRecommendations: Record<string, string> = {
      hero: 'header or section',
      forms: 'form with fieldset and legend',
      testimonials: 'section with blockquote',
      statistics: 'section with appropriate headings',
      ctas: 'nav for navigation CTAs',
      media: 'figure with figcaption'
    };

    const recommendedTag = semanticRecommendations[componentType];
    if (recommendedTag) {
      recommendations.push({
        category: 'semantic',
        message: `Consider using ${recommendedTag} for ${componentType} components`,
        impact: 'medium',
        implementation: `Wrap component content in appropriate semantic elements`
      });
    }

    // Check for landmark roles
    if (!config.accessibility.landmarkRole && ['hero', 'forms', 'media'].includes(componentType)) {
      recommendations.push({
        category: 'semantic',
        message: 'Consider adding a landmark role for better navigation',
        impact: 'medium',
        implementation: 'Add role="banner", role="main", or role="complementary" as appropriate'
      });
    }
  }

  /**
   * Validate keyboard navigation support
   */
  private validateKeyboardNavigation(
    metadata: AccessibilityMetadata,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    if (!metadata.keyboardNavigation) {
      return;
    }

    const { keyboardNavigation } = metadata;

    // Check focusable elements
    if (keyboardNavigation.focusable && keyboardNavigation.tabIndex === undefined) {
      issues.push({
        type: 'warning',
        category: 'keyboard',
        message: 'Focusable elements should have explicit tab order',
        wcagGuideline: 'WCAG 2.4.3',
        autoFixable: true,
        fixSuggestion: 'Set tabIndex to 0 for natural tab order or positive number for custom order'
      });
    }

    // Check for skip links
    if (keyboardNavigation.skipLink === undefined) {
      recommendations.push({
        category: 'keyboard',
        message: 'Consider adding skip links for complex components',
        impact: 'low',
        implementation: 'Add "Skip to main content" or similar navigation aids'
      });
    }

    // Validate keyboard shortcuts
    if (keyboardNavigation.keyboardShortcuts) {
      keyboardNavigation.keyboardShortcuts.forEach((shortcut, index) => {
        if (!shortcut.description) {
          issues.push({
            type: 'warning',
            category: 'keyboard',
            message: `Keyboard shortcut ${shortcut.key} lacks description`,
            autoFixable: false,
            fixSuggestion: 'Add description for screen reader users'
          });
        }
      });
    }
  }

  /**
   * Validate ARIA attributes
   */
  private validateAriaAttributes(
    metadata: AccessibilityMetadata,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    // Check for conflicting ARIA attributes
    if (metadata.ariaLabel && metadata.ariaLabelledBy) {
      issues.push({
        type: 'warning',
        category: 'aria',
        message: 'Both aria-label and aria-labelledby are present. aria-labelledby takes precedence.',
        wcagGuideline: 'WCAG 4.1.2',
        autoFixable: false,
        fixSuggestion: 'Use either aria-label or aria-labelledby, not both'
      });
    }

    // Check role validity
    if (metadata.role) {
      const validRoles = [
        'button', 'link', 'heading', 'banner', 'main', 'navigation',
        'complementary', 'contentinfo', 'search', 'form', 'region'
      ];
      
      if (!validRoles.includes(metadata.role)) {
        issues.push({
          type: 'error',
          category: 'aria',
          message: `Invalid ARIA role: ${metadata.role}`,
          wcagGuideline: 'WCAG 4.1.2',
          autoFixable: false,
          fixSuggestion: 'Use a valid ARIA role or remove the role attribute'
        });
      }
    }

    // Check for screen reader text
    if (!metadata.screenReaderText && metadata.role === 'button') {
      recommendations.push({
        category: 'aria',
        message: 'Interactive elements should have descriptive text for screen readers',
        impact: 'high',
        implementation: 'Add screen reader text or ensure visible text is descriptive'
      });
    }
  }

  /**
   * Validate motion and animation preferences
   */
  private validateMotionPreferences(
    metadata: AccessibilityMetadata,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    if (!metadata.motionPreferences) {
      issues.push({
        type: 'warning',
        category: 'motion',
        message: 'Motion preferences not configured',
        wcagGuideline: 'WCAG 2.3.3',
        autoFixable: true,
        fixSuggestion: 'Add motion preferences to respect prefers-reduced-motion'
      });
      return;
    }

    if (!metadata.motionPreferences.respectReducedMotion) {
      issues.push({
        type: 'error',
        category: 'motion',
        message: 'Component does not respect reduced motion preferences',
        wcagGuideline: 'WCAG 2.3.3',
        autoFixable: true,
        fixSuggestion: 'Enable respectReducedMotion and provide alternative content'
      });
    }

    if (!metadata.motionPreferences.alternativeContent) {
      recommendations.push({
        category: 'motion',
        message: 'Provide alternative content for users with motion sensitivity',
        impact: 'medium',
        implementation: 'Add static alternative or reduced motion version'
      });
    }
  }

  /**
   * Validate responsive accessibility considerations
   */
  private validateResponsiveAccessibility(
    config: ResponsiveComponentConfig,
    issues: AccessibilityIssue[],
    recommendations: AccessibilityRecommendation[]
  ): void {
    // Check touch target sizes on mobile
    if (config.responsive.mobile) {
      recommendations.push({
        category: 'responsive',
        message: 'Ensure touch targets are at least 44px on mobile devices',
        impact: 'high',
        implementation: 'Use min-height: 44px and min-width: 44px for interactive elements'
      });
    }

    // Check text scaling
    recommendations.push({
      category: 'responsive',
      message: 'Ensure text can scale up to 200% without horizontal scrolling',
      impact: 'high',
      implementation: 'Use relative units (rem, em) and test with browser zoom'
    });

    // Check color contrast across devices
    if (config.accessibility.colorContrast) {
      const { ratio, level } = config.accessibility.colorContrast;
      const requiredRatio = level === 'AAA' ? 7.0 : 4.5;
      
      if (ratio < requiredRatio) {
        issues.push({
          type: 'error',
          category: 'contrast',
          message: `Color contrast ratio ${ratio}:1 does not meet ${level} standards (${requiredRatio}:1 required)`,
          wcagGuideline: level === 'AAA' ? 'WCAG 1.4.6' : 'WCAG 1.4.3',
          autoFixable: false,
          fixSuggestion: 'Adjust colors to improve contrast ratio'
        });
      }
    }
  }

  /**
   * Calculate accessibility score based on issues
   */
  private calculateAccessibilityScore(issues: AccessibilityIssue[]): number {
    let score = 100;
    
    issues.forEach(issue => {
      switch (issue.type) {
        case 'error':
          score -= 15;
          break;
        case 'warning':
          score -= 5;
          break;
        case 'info':
          score -= 1;
          break;
      }
    });

    return Math.max(0, score);
  }

  /**
   * Generate accessibility metadata for GrapeJS screen reader support
   */
  public generateScreenReaderMetadata(
    componentType: string,
    config: any
  ): AccessibilityMetadata {
    const baseMetadata: AccessibilityMetadata = {
      semanticTag: this.getRecommendedSemanticTag(componentType),
      keyboardNavigation: {
        focusable: this.isInteractiveComponent(componentType),
        tabOrder: 0
      },
      motionPreferences: {
        respectReducedMotion: true
      }
    };

    // Component-specific metadata
    switch (componentType) {
      case 'hero':
        return {
          ...baseMetadata,
          semanticTag: 'header',
          landmarkRole: 'banner',
          ariaLabel: 'Hero section',
          headingLevel: 1,
          screenReaderText: 'Main hero section with primary call-to-action',
          keyboardNavigation: {
            focusable: true,
            tabOrder: 1,
            keyboardShortcuts: [
              {
                key: 'Enter',
                action: 'activate-primary-cta',
                description: 'Activate primary call-to-action button'
              }
            ]
          }
        };

      case 'forms':
        return {
          ...baseMetadata,
          semanticTag: 'form',
          landmarkRole: 'form',
          ariaLabel: 'Contact form',
          screenReaderText: 'Form for submitting contact information or inquiries',
          keyboardNavigation: {
            focusable: true,
            tabOrder: 0,
            skipLink: true,
            keyboardShortcuts: [
              {
                key: 'Tab',
                action: 'navigate-fields',
                description: 'Navigate between form fields'
              },
              {
                key: 'Enter',
                action: 'submit-form',
                description: 'Submit the form'
              },
              {
                key: 'Escape',
                action: 'clear-form',
                description: 'Clear form data'
              }
            ]
          }
        };

      case 'testimonials':
        return {
          ...baseMetadata,
          semanticTag: 'section',
          ariaLabel: 'Customer testimonials',
          role: 'region',
          screenReaderText: 'Section containing customer testimonials and reviews',
          keyboardNavigation: {
            focusable: false,
            tabOrder: 0,
            keyboardShortcuts: [
              {
                key: 'ArrowLeft',
                action: 'previous-testimonial',
                description: 'Navigate to previous testimonial'
              },
              {
                key: 'ArrowRight',
                action: 'next-testimonial',
                description: 'Navigate to next testimonial'
              }
            ]
          }
        };

      case 'statistics':
        return {
          ...baseMetadata,
          semanticTag: 'section',
          ariaLabel: 'Statistics and metrics',
          role: 'region',
          screenReaderText: 'Section displaying key statistics and performance metrics',
          keyboardNavigation: {
            focusable: false,
            tabOrder: 0
          }
        };

      case 'ctas':
        return {
          ...baseMetadata,
          semanticTag: 'nav',
          landmarkRole: 'navigation',
          ariaLabel: 'Call to action buttons',
          screenReaderText: 'Navigation section with call-to-action buttons',
          keyboardNavigation: {
            focusable: true,
            tabOrder: 0,
            keyboardShortcuts: [
              {
                key: 'Enter',
                action: 'activate-cta',
                description: 'Activate focused call-to-action button'
              },
              {
                key: 'Space',
                action: 'activate-cta',
                description: 'Activate focused call-to-action button'
              }
            ]
          }
        };

      case 'media':
        return {
          ...baseMetadata,
          semanticTag: 'figure',
          ariaLabel: 'Media content',
          screenReaderText: 'Media gallery or content display',
          keyboardNavigation: {
            focusable: true,
            tabOrder: 0,
            keyboardShortcuts: [
              {
                key: 'Enter',
                action: 'open-media',
                description: 'Open media in full view'
              },
              {
                key: 'ArrowLeft',
                action: 'previous-media',
                description: 'Navigate to previous media item'
              },
              {
                key: 'ArrowRight',
                action: 'next-media',
                description: 'Navigate to next media item'
              },
              {
                key: 'Escape',
                action: 'close-media',
                description: 'Close media viewer'
              }
            ]
          }
        };

      default:
        return baseMetadata;
    }
  }

  /**
   * Generate GrapeJS-specific accessibility traits
   */
  public generateGrapeJSAccessibilityTraits(componentType: string): any[] {
    const baseTraits = [
      {
        type: 'text',
        name: 'aria-label',
        label: 'ARIA Label',
        placeholder: 'Describe the component for screen readers',
        category: 'Accessibility'
      },
      {
        type: 'text',
        name: 'aria-describedby',
        label: 'ARIA Described By',
        placeholder: 'ID of element that describes this component',
        category: 'Accessibility'
      },
      {
        type: 'select',
        name: 'semantic-tag',
        label: 'Semantic HTML Tag',
        category: 'Accessibility',
        options: [
          { id: 'div', name: 'Div (Generic)' },
          { id: 'section', name: 'Section' },
          { id: 'article', name: 'Article' },
          { id: 'header', name: 'Header' },
          { id: 'footer', name: 'Footer' },
          { id: 'nav', name: 'Navigation' },
          { id: 'aside', name: 'Aside' },
          { id: 'main', name: 'Main Content' },
          { id: 'figure', name: 'Figure' }
        ]
      },
      {
        type: 'select',
        name: 'landmark-role',
        label: 'Landmark Role',
        category: 'Accessibility',
        options: [
          { id: '', name: 'None' },
          { id: 'banner', name: 'Banner' },
          { id: 'main', name: 'Main' },
          { id: 'navigation', name: 'Navigation' },
          { id: 'complementary', name: 'Complementary' },
          { id: 'contentinfo', name: 'Content Info' },
          { id: 'search', name: 'Search' },
          { id: 'form', name: 'Form' },
          { id: 'region', name: 'Region' }
        ]
      },
      {
        type: 'checkbox',
        name: 'keyboard-focusable',
        label: 'Keyboard Focusable',
        category: 'Accessibility',
        changeProp: true
      },
      {
        type: 'number',
        name: 'tab-index',
        label: 'Tab Index',
        category: 'Accessibility',
        min: -1,
        max: 999,
        placeholder: '0 for natural order, -1 to exclude'
      },
      {
        type: 'checkbox',
        name: 'reduced-motion',
        label: 'Respect Reduced Motion',
        category: 'Accessibility',
        changeProp: true,
        default: true
      },
      {
        type: 'textarea',
        name: 'screen-reader-text',
        label: 'Screen Reader Description',
        category: 'Accessibility',
        placeholder: 'Additional context for screen reader users'
      }
    ];

    // Add component-specific traits
    const componentSpecificTraits = this.getComponentSpecificAccessibilityTraits(componentType);
    
    return [...baseTraits, ...componentSpecificTraits];
  }

  /**
   * Get component-specific accessibility traits
   */
  private getComponentSpecificAccessibilityTraits(componentType: string): any[] {
    switch (componentType) {
      case 'hero':
        return [
          {
            type: 'select',
            name: 'heading-level',
            label: 'Heading Level',
            category: 'Accessibility',
            options: [
              { id: '1', name: 'H1 (Main heading)' },
              { id: '2', name: 'H2 (Section heading)' },
              { id: '3', name: 'H3 (Subsection heading)' },
              { id: '4', name: 'H4' },
              { id: '5', name: 'H5' },
              { id: '6', name: 'H6' }
            ],
            default: '1'
          }
        ];

      case 'forms':
        return [
          {
            type: 'checkbox',
            name: 'form-validation-live',
            label: 'Live Validation Announcements',
            category: 'Accessibility',
            changeProp: true,
            default: true
          },
          {
            type: 'text',
            name: 'form-instructions',
            label: 'Form Instructions',
            category: 'Accessibility',
            placeholder: 'Instructions for completing the form'
          }
        ];

      case 'media':
        return [
          {
            type: 'checkbox',
            name: 'alt-text-required',
            label: 'Require Alt Text',
            category: 'Accessibility',
            changeProp: true,
            default: true
          },
          {
            type: 'checkbox',
            name: 'captions-available',
            label: 'Video Captions Available',
            category: 'Accessibility',
            changeProp: true
          }
        ];

      case 'ctas':
        return [
          {
            type: 'text',
            name: 'button-purpose',
            label: 'Button Purpose Description',
            category: 'Accessibility',
            placeholder: 'Describe what happens when button is activated'
          }
        ];

      default:
        return [];
    }
  }

  /**
   * Validate component accessibility for GrapeJS integration
   */
  public validateForGrapeJS(
    componentType: string,
    config: any,
    grapeJSData: any
  ): {
    valid: boolean;
    score: number;
    issues: AccessibilityIssue[];
    grapeJSCompatibility: {
      deviceManager: boolean;
      styleManager: boolean;
      traitManager: boolean;
    };
  } {
    // Run standard accessibility validation
    const standardValidation = this.validateComponent(config, componentType);
    
    // Check GrapeJS-specific compatibility
    const grapeJSCompatibility = {
      deviceManager: this.validateDeviceManagerCompatibility(config),
      styleManager: this.validateStyleManagerCompatibility(config),
      traitManager: this.validateTraitManagerCompatibility(config)
    };

    // Add GrapeJS-specific issues
    const grapeJSIssues: AccessibilityIssue[] = [];
    
    if (!grapeJSCompatibility.deviceManager) {
      grapeJSIssues.push({
        type: 'warning',
        category: 'responsive',
        message: 'Component may not display correctly across all device sizes in GrapeJS',
        autoFixable: true,
        fixSuggestion: 'Add responsive breakpoint configurations'
      });
    }

    if (!grapeJSCompatibility.traitManager) {
      grapeJSIssues.push({
        type: 'info',
        category: 'accessibility',
        message: 'Some accessibility features may not be configurable in GrapeJS interface',
        autoFixable: false
      });
    }

    return {
      valid: standardValidation.valid && Object.values(grapeJSCompatibility).every(Boolean),
      score: standardValidation.score,
      issues: [...standardValidation.issues, ...grapeJSIssues],
      grapeJSCompatibility
    };
  }

  private validateDeviceManagerCompatibility(config: any): boolean {
    return !!(config.responsive && (config.responsive.mobile || config.responsive.tablet || config.responsive.desktop));
  }

  private validateStyleManagerCompatibility(config: any): boolean {
    return !!(config.tailwindMapping || config.grapeJSMetadata?.styleManager);
  }

  private validateTraitManagerCompatibility(config: any): boolean {
    return !!(config.accessibility && config.grapeJSMetadata?.traitManager);
  }

  private getRecommendedSemanticTag(componentType: string): AccessibilityMetadata['semanticTag'] {
    const tagMap: Record<string, AccessibilityMetadata['semanticTag']> = {
      hero: 'header',
      forms: 'section',
      testimonials: 'section',
      statistics: 'section',
      ctas: 'nav',
      media: 'section'
    };

    return tagMap[componentType] || 'div';
  }

  private isInteractiveComponent(componentType: string): boolean {
    return ['forms', 'ctas'].includes(componentType);
  }

  /**
   * Auto-fix accessibility issues where possible
   */
  public autoFixIssues(
    config: ResponsiveComponentConfig,
    issues: AccessibilityIssue[]
  ): ResponsiveComponentConfig {
    const fixedConfig = { ...config };

    issues.forEach(issue => {
      if (!issue.autoFixable) return;

      switch (issue.category) {
        case 'keyboard':
          if (!fixedConfig.accessibility.keyboardNavigation) {
            fixedConfig.accessibility.keyboardNavigation = {
              focusable: false,
              tabOrder: 0
            };
          }
          break;

        case 'motion':
          if (!fixedConfig.accessibility.motionPreferences) {
            fixedConfig.accessibility.motionPreferences = {
              respectReducedMotion: true
            };
          }
          break;

        case 'aria':
          if (!fixedConfig.accessibility.ariaLabel && issue.message.includes('accessible name')) {
            fixedConfig.accessibility.ariaLabel = 'Component';
          }
          break;
      }
    });

    return fixedConfig;
  }
}

export default AccessibilityValidator;