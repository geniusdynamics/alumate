// Component Library Types

export type ComponentCategory = 'hero' | 'forms' | 'testimonials' | 'statistics' | 'ctas' | 'media';

export type AudienceType = 'individual' | 'institution' | 'employer';

export type BackgroundMediaType = 'image' | 'video' | 'gradient';

export interface MediaAsset {
    id: string;
    type: 'image' | 'video';
    url: string;
    alt?: string;
    thumbnail?: string;
    width?: number;
    height?: number;
    size?: number;
    mimeType?: string;
    // Enhanced responsive image support
    srcSet?: Array<{
        url: string;
        width: number;
        format?: 'webp' | 'avif' | 'jpeg' | 'png';
    }>;
    // CDN and optimization
    cdnUrl?: string;
    optimized?: boolean;
    // Mobile-specific variants
    mobileUrl?: string;
    mobileSrcSet?: Array<{
        url: string;
        width: number;
        format?: 'webp' | 'avif' | 'jpeg' | 'png';
    }>;
    // Fallback options
    fallbackUrl?: string;
    placeholder?: string; // Base64 or low-quality placeholder
}

export interface GradientConfig {
    type: 'linear' | 'radial';
    direction?: string;
    colors: Array<{
        color: string;
        stop: number;
    }>;
}

export interface BackgroundMedia {
    type: BackgroundMediaType;
    image?: MediaAsset;
    video?: MediaAsset & {
        autoplay?: boolean;
        muted?: boolean;
        loop?: boolean;
        // Enhanced video options
        poster?: string; // Poster image URL
        preload?: 'none' | 'metadata' | 'auto';
        // Mobile-specific video handling
        mobileVideo?: MediaAsset;
        disableOnMobile?: boolean;
        // Bandwidth considerations
        quality?: 'low' | 'medium' | 'high' | 'auto';
        adaptiveBitrate?: boolean;
    };
    gradient?: GradientConfig;
    overlay?: {
        color: string;
        opacity: number;
    };
    // Fallback and performance options
    fallback?: {
        type: 'image' | 'gradient';
        image?: MediaAsset;
        gradient?: GradientConfig;
    };
    // Performance settings
    lazyLoad?: boolean;
    preload?: boolean;
    // Mobile-specific settings
    mobileOptimized?: boolean;
    reducedMotion?: boolean; // Respect prefers-reduced-motion
}

export interface CTAButton {
    id: string;
    text: string;
    url: string;
    style: 'primary' | 'secondary' | 'outline' | 'ghost';
    size: 'sm' | 'md' | 'lg';
    icon?: string;
    trackingParams?: Record<string, string>;
    abTestVariant?: string;
}

export interface StatisticCounter {
    id: string;
    value: number | string;
    label: string;
    suffix?: string;
    prefix?: string;
    animated?: boolean;
    source?: 'manual' | 'api';
    apiEndpoint?: string;
}

export interface ABTestConfig {
    enabled: boolean;
    testId?: string;
    variant?: string;
    variants?: Array<{
        id: string;
        name: string;
        weight: number;
        config: Partial<HeroComponentConfig>;
    }>;
}

export interface HeroComponentConfig {
    // Content
    headline: string;
    subheading?: string;
    description?: string;
    
    // Audience targeting
    audienceType: AudienceType;
    
    // Background media
    backgroundMedia?: BackgroundMedia;
    
    // Call-to-action buttons
    ctaButtons: CTAButton[];
    
    // Statistics (optional)
    statistics?: StatisticCounter[];
    
    // Layout and styling
    layout: 'centered' | 'left-aligned' | 'right-aligned' | 'split';
    textAlignment: 'left' | 'center' | 'right';
    contentPosition: 'top' | 'center' | 'bottom';
    
    // Responsive settings
    mobileLayout?: 'stacked' | 'overlay';
    
    // Accessibility
    headingLevel: 1 | 2 | 3 | 4 | 5 | 6;
    
    // Animation settings
    animations?: {
        enabled: boolean;
        entrance?: 'fade' | 'slide' | 'zoom';
        duration?: number;
        delay?: number;
    };
    
    // Performance
    lazyLoad?: boolean;
    preloadImages?: boolean;
    
    // A/B Testing
    abTest?: ABTestConfig;
    
    // Variant-specific styling
    variantStyling?: {
        colorScheme?: 'default' | 'warm' | 'cool' | 'professional' | 'energetic';
        typography?: 'default' | 'modern' | 'classic' | 'bold';
        spacing?: 'compact' | 'default' | 'spacious';
    };
}

export interface ComponentInstance {
    id: string;
    componentId: string;
    config: HeroComponentConfig;
    position: number;
    pageId: string;
    pageType: string;
    customConfig?: Partial<HeroComponentConfig>;
    createdAt: string;
    updatedAt: string;
}

export interface Component {
    id: string;
    tenantId: string;
    name: string;
    slug: string;
    category: ComponentCategory;
    type: string;
    description?: string;
    config: HeroComponentConfig;
    metadata?: Record<string, unknown>;
    version: string;
    isActive: boolean;
    createdAt: string;
    updatedAt: string;
}

// Form Component Types
export type FormFieldType = 'text' | 'email' | 'phone' | 'select' | 'checkbox' | 'textarea' | 'radio' | 'number' | 'url' | 'date';

export type FormValidationRule = 
  | 'required'
  | 'email'
  | 'phone'
  | 'url'
  | 'min'
  | 'max'
  | 'minLength'
  | 'maxLength'
  | 'pattern'
  | 'custom';

export interface FormValidationConfig {
  rule: FormValidationRule;
  value?: string | number;
  message?: string;
  customValidator?: (value: any) => boolean | string;
}

export interface FormFieldOption {
  label: string;
  value: string | number;
  disabled?: boolean;
}

export interface FormField {
  id: string;
  type: FormFieldType;
  name: string;
  label: string;
  placeholder?: string;
  helpText?: string;
  required?: boolean;
  disabled?: boolean;
  readonly?: boolean;
  defaultValue?: string | number | boolean;
  
  // Field-specific options
  options?: FormFieldOption[]; // For select, radio, checkbox groups
  multiple?: boolean; // For select fields
  rows?: number; // For textarea
  min?: number; // For number, date fields
  max?: number; // For number, date fields
  step?: number; // For number fields
  pattern?: string; // For text fields
  
  // Validation
  validation?: FormValidationConfig[];
  
  // Layout and styling
  width?: 'full' | 'half' | 'third' | 'quarter';
  className?: string;
  
  // Accessibility
  ariaLabel?: string;
  ariaDescribedBy?: string;
  
  // Conditional logic
  showWhen?: {
    field: string;
    operator: 'equals' | 'not_equals' | 'contains' | 'not_contains';
    value: any;
  };
}

export interface FormSubmissionConfig {
  method: 'POST' | 'PUT' | 'PATCH';
  action: string;
  successMessage?: string;
  errorMessage?: string;
  redirectUrl?: string;
  
  // CRM Integration
  crmIntegration?: {
    enabled: boolean;
    provider: 'salesforce' | 'hubspot' | 'pipedrive' | 'custom';
    endpoint?: string;
    mapping?: Record<string, string>;
    leadScore?: number;
    tags?: string[];
  };
  
  // Email notifications
  notifications?: {
    enabled: boolean;
    recipients?: string[];
    template?: string;
    subject?: string;
  };
}

export interface FormComponentConfig {
  // Basic form settings
  title?: string;
  description?: string;
  
  // Form fields
  fields: FormField[];
  
  // Layout settings
  layout: 'single-column' | 'two-column' | 'grid' | 'custom';
  spacing: 'compact' | 'default' | 'spacious';
  
  // Submission settings
  submission: FormSubmissionConfig;
  
  // Styling
  theme?: 'default' | 'minimal' | 'modern' | 'classic';
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent';
  
  // Behavior
  showProgress?: boolean;
  allowSaveProgress?: boolean;
  enableAutoSave?: boolean;
  autoSaveInterval?: number; // in seconds
  
  // Validation
  validateOnBlur?: boolean;
  validateOnChange?: boolean;
  showValidationSummary?: boolean;
  
  // Accessibility
  ariaLabel?: string;
  screenReaderInstructions?: string;
  
  // Anti-spam
  honeypot?: boolean;
  recaptcha?: {
    enabled: boolean;
    siteKey?: string;
    theme?: 'light' | 'dark';
  };
  
  // Analytics
  trackingEnabled?: boolean;
  trackingEvents?: string[];
}

export interface FormTemplate {
  id: string;
  name: string;
  description: string;
  category: 'lead-capture' | 'contact' | 'demo-request' | 'newsletter' | 'survey' | 'custom';
  audienceType?: AudienceType;
  config: FormComponentConfig;
  previewImage?: string;
  tags?: string[];
}

// Sample data interfaces for different audience types
export interface HeroSampleData {
    individual: {
    headline: string;
    subheading: string;
    description: string;
    ctaButtons: CTAButton[];
    statistics?: StatisticCounter[];
    backgroundMedia?: BackgroundMedia;
  };
  institution: {
    headline: string;
    subheading: string;
    description: string;
    ctaButtons: CTAButton[];
    statistics?: StatisticCounter[];
    backgroundMedia?: BackgroundMedia;
  };
  employer: {
    headline: string;
    subheading: string;
    description: string;
    ctaButtons: CTAButton[];
    statistics?: StatisticCounter[];
    backgroundMedia?: BackgroundMedia;
  };
}

// GrapeJS Integration Types
export interface GrapeJSBlockMetadata {
  id: string;
  label: string;
  category: string;
  media: string;
  content: string | GrapeJSComponentDefinition;
  attributes: Record<string, any>;
  activate?: boolean;
  select?: boolean;
  disable?: boolean;
}

export interface GrapeJSComponentDefinition {
  type: string;
  tagName?: string;
  attributes?: Record<string, any>;
  components?: string | GrapeJSComponentDefinition[];
  traits?: GrapeJSTrait[];
  style?: Record<string, any>;
  void?: boolean;
  droppable?: boolean;
  draggable?: boolean;
  copyable?: boolean;
  removable?: boolean;
  badgable?: boolean;
  stylable?: boolean;
  highlightable?: boolean;
  selectable?: boolean;
  hoverable?: boolean;
  layerable?: boolean;
}

export interface GrapeJSTrait {
  type: 'text' | 'number' | 'select' | 'checkbox' | 'radio' | 'color' | 'slider' | 'file';
  name: string;
  label?: string;
  placeholder?: string;
  default?: any;
  min?: number;
  max?: number;
  step?: number;
  options?: Array<{ id: string; name: string; value?: any }>;
  changeProp?: boolean;
  full?: boolean;
}

export interface ComponentGrapeJSMetadata {
  blockDefinition: GrapeJSBlockMetadata;
  componentDefinition: GrapeJSComponentDefinition;
  previewImage?: string;
  category: string;
  tags: string[];
  usageCount?: number;
  lastUsed?: string;
  documentation?: {
    description: string;
    examples: string[];
    properties: Record<string, string>;
  };
}

export interface GrapeJSSerializationData {
  html: string;
  css: string;
  components: any[];
  styles: any[];
  assets: any[];
}

export interface ComponentLibraryBridgeInterface {
  convertToGrapeJSBlock(component: Component): GrapeJSBlockMetadata;
  convertFromGrapeJSData(data: GrapeJSSerializationData): Component[];
  syncComponentUpdates(componentId: string): Promise<void>;
  generatePreviewImage(component: Component): Promise<string>;
  validateGrapeJSCompatibility(component: Component): { valid: boolean; errors: string[] };
}