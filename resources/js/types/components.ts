// Component Library Types

export type ComponentCategory = 'hero' | 'forms' | 'testimonials' | 'statistics' | 'ctas' | 'media';

export type AudienceType = 'individual' | 'institution' | 'employer';

// Responsive Design Types for GrapeJS Integration
export type DeviceType = 'desktop' | 'tablet' | 'mobile';
export type BreakpointName = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';

export interface ResponsiveBreakpoint {
  name: BreakpointName;
  minWidth: number;
  maxWidth?: number;
  device: DeviceType;
  label: string;
  icon?: string;
  grapeJSDevice?: string; // Maps to GrapeJS device manager
}

export interface ResponsiveConfig {
  breakpoints: ResponsiveBreakpoint[];
  defaultBreakpoint: BreakpointName;
  enabledDevices: DeviceType[];
}

export interface DeviceSpecificConfig<T = any> {
  desktop?: T;
  tablet?: T;
  mobile?: T;
}

export interface ResponsiveComponentVariant {
  device: DeviceType;
  breakpoint: BreakpointName;
  config: any; // Component-specific configuration
  enabled: boolean;
  inheritFromParent?: boolean;
  customizations?: Record<string, any>;
}

// Accessibility Metadata for GrapeJS
export interface AccessibilityMetadata {
  ariaLabel?: string;
  ariaDescribedBy?: string;
  ariaLabelledBy?: string;
  role?: string;
  tabIndex?: number;
  semanticTag?: 'header' | 'main' | 'section' | 'article' | 'aside' | 'nav' | 'footer' | 'div';
  headingLevel?: 1 | 2 | 3 | 4 | 5 | 6;
  landmarkRole?: 'banner' | 'main' | 'navigation' | 'complementary' | 'contentinfo' | 'search' | 'form';
  screenReaderText?: string;
  keyboardNavigation?: {
    focusable: boolean;
    tabOrder?: number;
    skipLink?: boolean;
    keyboardShortcuts?: Array<{
      key: string;
      action: string;
      description: string;
    }>;
  };
  colorContrast?: {
    ratio: number;
    level: 'AA' | 'AAA';
    validated: boolean;
  };
  motionPreferences?: {
    respectReducedMotion: boolean;
    alternativeContent?: string;
  };
}

// Component Grouping and Relationships for GrapeJS
export interface ComponentRelationship {
  type: 'parent' | 'child' | 'sibling' | 'dependency' | 'variant';
  componentId: string;
  relationshipId: string;
  metadata?: Record<string, any>;
}

export interface ComponentGroup {
  id: string;
  name: string;
  description?: string;
  components: string[]; // Component IDs
  category?: ComponentCategory;
  tags?: string[];
  grapeJSCategory?: string;
  sortOrder?: number;
  icon?: string;
  color?: string;
}

// Tailwind CSS Class Mapping for GrapeJS Style Manager
export interface TailwindClassMapping {
  property: string; // CSS property name
  tailwindClasses: Array<{
    class: string;
    value: string;
    label: string;
    category?: 'spacing' | 'colors' | 'typography' | 'layout' | 'effects' | 'responsive';
    responsive?: boolean;
    variants?: string[]; // hover, focus, etc.
  }>;
  grapeJSProperty?: string; // Maps to GrapeJS style manager property
  responsive?: boolean;
  important?: boolean;
}

export interface TailwindStyleMapping {
  [componentType: string]: {
    [elementSelector: string]: TailwindClassMapping[];
  };
}

// Component Constraints for Responsive Design Compliance
export interface ResponsiveConstraint {
  type: 'minWidth' | 'maxWidth' | 'aspectRatio' | 'textSize' | 'spacing' | 'touchTarget' | 'custom';
  device?: DeviceType;
  breakpoint?: BreakpointName;
  value: number | string;
  unit?: 'px' | 'rem' | 'em' | '%' | 'vw' | 'vh';
  message?: string;
  severity: 'error' | 'warning' | 'info';
  autoFix?: boolean;
  fixAction?: string;
}

export interface ComponentConstraints {
  responsive: ResponsiveConstraint[];
  accessibility: Array<{
    type: 'contrast' | 'focusable' | 'semantic' | 'keyboard' | 'screenReader';
    requirement: string;
    message?: string;
    severity: 'error' | 'warning' | 'info';
    autoFix?: boolean;
  }>;
  performance: Array<{
    type: 'imageSize' | 'loadTime' | 'bundleSize' | 'renderTime';
    threshold: number;
    unit: string;
    message?: string;
    severity: 'error' | 'warning' | 'info';
  }>;
}

// Enhanced Component Configuration with Responsive Support
export interface ResponsiveComponentConfig {
  // Base configuration (applies to all devices)
  base: any;
  
  // Device-specific overrides
  responsive: {
    [K in DeviceType]?: any;
  };
  
  // Breakpoint-specific overrides
  breakpoints?: {
    [K in BreakpointName]?: any;
  };
  
  // Accessibility metadata
  accessibility: AccessibilityMetadata;
  
  // Component relationships
  relationships?: ComponentRelationship[];
  
  // Tailwind class mappings
  tailwindMapping?: TailwindStyleMapping;
  
  // Constraints validation
  constraints?: ComponentConstraints;
  
  // GrapeJS specific metadata
  grapeJSMetadata?: {
    deviceManager?: {
      [K in DeviceType]?: {
        width: number;
        height?: number;
        widthMedia?: string;
      };
    };
    styleManager?: {
      sectors: Array<{
        name: string;
        properties: string[];
      }>;
    };
    traitManager?: {
      traits: GrapeJSTrait[];
    };
  };
}

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

// Testimonial Component Types
export type TestimonialLayout = 'single' | 'carousel' | 'grid' | 'masonry';

export interface TestimonialAuthor {
  id: string;
  name: string;
  title?: string;
  company?: string;
  graduationYear?: number;
  industry?: string;
  photo?: MediaAsset;
  location?: string;
  linkedinUrl?: string;
  // Filtering attributes
  audienceType?: AudienceType;
  tags?: string[];
}

export interface TestimonialContent {
  id: string;
  quote: string;
  rating?: number; // 1-5 star rating
  type: 'text' | 'video';
  
  // Video testimonial specific
  videoAsset?: MediaAsset & {
    transcript?: string;
    captions?: string; // WebVTT file URL
    chapters?: Array<{
      time: number;
      title: string;
    }>;
    duration?: number; // Video duration in seconds
    // Multiple quality sources
    qualities?: Array<{
      label: string;
      src: string;
      type: string;
      bandwidth?: number;
      width?: number;
      height?: number;
    }>;
    // Bandwidth-aware loading
    adaptiveBitrate?: boolean;
    // Analytics metadata
    engagementMetrics?: {
      averageWatchTime?: number;
      completionRate?: number;
      dropOffPoints?: number[];
    };
  };
  
  // Content metadata
  featured?: boolean;
  verified?: boolean;
  dateCreated: string;
  lastUpdated?: string;
  
  // Analytics
  viewCount?: number;
  shareCount?: number;
  likeCount?: number;
}

export interface Testimonial {
  id: string;
  author: TestimonialAuthor;
  content: TestimonialContent;
  
  // Filtering and categorization
  category?: string;
  tags?: string[];
  audienceType?: AudienceType;
  industry?: string;
  graduationYear?: number;
  
  // Display settings
  featured?: boolean;
  approved?: boolean;
  priority?: number; // For ordering
  
  // A/B testing
  abTestVariant?: string;
  
  // Accessibility
  ariaLabel?: string;
}

export interface TestimonialFilterConfig {
  audienceType?: AudienceType[];
  industry?: string[];
  graduationYear?: {
    min?: number;
    max?: number;
    ranges?: Array<{ label: string; min: number; max: number }>;
  };
  tags?: string[];
  rating?: {
    min?: number;
    max?: number;
  };
  type?: ('text' | 'video')[];
  featured?: boolean;
  verified?: boolean;
}

export interface TestimonialCarouselConfig {
  autoplay?: boolean;
  autoplaySpeed?: number; // milliseconds
  pauseOnHover?: boolean;
  showDots?: boolean;
  showArrows?: boolean;
  infinite?: boolean;
  slidesToShow?: number;
  slidesToScroll?: number;
  responsive?: Array<{
    breakpoint: number;
    settings: {
      slidesToShow: number;
      slidesToScroll: number;
    };
  }>;
  // Touch/swipe settings
  swipe?: boolean;
  touchThreshold?: number;
  // Accessibility
  ariaLabel?: string;
  announceSlideChanges?: boolean;
}

export interface TestimonialGridConfig {
  columns?: {
    desktop: number;
    tablet: number;
    mobile: number;
  };
  gap?: 'sm' | 'md' | 'lg';
  masonry?: boolean;
  equalHeight?: boolean;
}

export interface TestimonialComponentConfig {
  // Layout settings
  layout: TestimonialLayout;
  
  // Testimonials data
  testimonials: Testimonial[];
  
  // Display settings
  showAuthorPhoto?: boolean;
  showAuthorTitle?: boolean;
  showAuthorCompany?: boolean;
  showGraduationYear?: boolean;
  showRating?: boolean;
  showDate?: boolean;
  
  // Filtering
  enableFiltering?: boolean;
  filterConfig?: TestimonialFilterConfig;
  defaultFilters?: Partial<TestimonialFilterConfig>;
  
  // Layout-specific configurations
  carouselConfig?: TestimonialCarouselConfig;
  gridConfig?: TestimonialGridConfig;
  
  // Styling
  theme?: 'default' | 'minimal' | 'modern' | 'classic' | 'card';
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent';
  
  // Performance
  lazyLoad?: boolean;
  itemsPerPage?: number; // For pagination
  enableInfiniteScroll?: boolean;
  
  // Accessibility
  ariaLabel?: string;
  announceUpdates?: boolean;
  respectReducedMotion?: boolean;
  
  // Video testimonial settings
  videoSettings?: {
    autoplay?: boolean;
    muted?: boolean;
    showControls?: boolean;
    showCaptions?: boolean;
    preload?: 'none' | 'metadata' | 'auto';
  };
  
  // Analytics
  trackingEnabled?: boolean;
  trackViews?: boolean;
  trackInteractions?: boolean;
  
  // A/B Testing
  abTest?: ABTestConfig;
}

// Video Settings Interface
export interface VideoSettings {
  autoplay?: boolean;
  muted?: boolean;
  showControls?: boolean;
  showCaptions?: boolean;
  preload?: 'none' | 'metadata' | 'auto';
  loop?: boolean;
  playsinline?: boolean;
}

// Statistics Component Types
export type StatisticsDisplayType = 'counters' | 'progress' | 'charts' | 'mixed';
export type StatisticsFormat = 'number' | 'currency' | 'percentage' | 'duration';
export type ChartType = 'bar' | 'before-after' | 'competitive';

export interface StatisticsTrend {
  direction: 'up' | 'down' | 'neutral';
  value: number | string;
  label?: string;
}

export interface StatisticsSegment {
  threshold: number;
  width: number;
  color?: string;
  label?: string;
}

export interface StatisticsMilestone {
  value: number;
  label: string;
  showLabel?: boolean;
}

export interface StatisticsDataSource {
  id: string;
  endpoint: string;
  method?: 'GET' | 'POST';
  headers?: Record<string, string>;
  params?: Record<string, any>;
  transform?: (data: any) => any;
  refreshInterval?: number;
  retryAttempts?: number;
  retryDelay?: number;
}

export interface StatisticsItem {
  id: string;
  value: number | string;
  label: string;
  description?: string;
  prefix?: string;
  suffix?: string;
  format?: StatisticsFormat;
  type?: 'counter' | 'progress' | 'chart';
  source?: 'manual' | 'api';
  apiEndpoint?: string;
  color?: string;
  icon?: string;
  
  // Counter-specific
  trend?: StatisticsTrend;
  
  // Progress-specific
  target?: number;
  segments?: StatisticsSegment[];
  milestones?: StatisticsMilestone[];
  
  // Chart-specific
  chartType?: ChartType;
  chartData?: ChartDataItem[];
  showLegend?: boolean;
  legend?: ChartLegendItem[];
  dataSource?: string;
}

export interface StatisticsComponentConfig {
  title?: string;
  description?: string;
  displayType: StatisticsDisplayType;
  layout: 'grid' | 'row' | 'column';
  theme: 'default' | 'minimal' | 'modern' | 'card';
  spacing: 'compact' | 'default' | 'spacious';
  
  // Size configurations
  counterSize: 'sm' | 'md' | 'lg' | 'xl';
  progressSize: 'sm' | 'md' | 'lg';
  chartSize: 'sm' | 'md' | 'lg';
  
  // Display options
  showLabels: boolean;
  showValues: boolean;
  showTargets: boolean;
  
  // Grid configuration
  gridColumns: {
    desktop: number;
    tablet: number;
    mobile: number;
  };
  
  // Animation settings
  animation: {
    enabled: boolean;
    trigger: 'immediate' | 'scroll' | 'hover';
    duration: number;
    delay: number;
    stagger: number;
    easing: string;
  };
  
  // Real-time data
  realTimeData: {
    enabled: boolean;
    sources: string[];
    refreshInterval: number;
  };
  
  // Accessibility
  accessibility: {
    ariaLabel?: string;
    announceUpdates: boolean;
    respectReducedMotion: boolean;
  };
  
  // Data refresh
  dataRefresh: {
    enabled: boolean;
    interval: number;
    retryAttempts: number;
  };
  
  // Error handling
  errorHandling: {
    showErrors: boolean;
    errorMessage: string;
    allowRetry: boolean;
  };
}

export interface ChartDataItem {
  id?: string;
  label: string;
  value: number;
  beforeValue?: number;
  afterValue?: number;
  color?: string;
  highlighted?: boolean;
  description?: string;
}

export interface ChartLegendItem {
  label: string;
  color: string;
}

// Media Component Types
export type MediaType = 'image-gallery' | 'video-embed' | 'interactive-demo';
export type MediaLayout = 'grid' | 'masonry' | 'carousel' | 'single' | 'full-width' | 'contained' | 'wide' | 'column' | 'row';

export interface MediaOptimization {
  webpSupport: boolean;
  avifSupport: boolean;
  lazyLoading: boolean;
  responsiveImages: boolean;
  cdnEnabled: boolean;
  compressionLevel: 'low' | 'medium' | 'high';
  maxWidth?: number;
  maxHeight?: number;
}

export interface MediaAccessibility {
  ariaLabel?: string;
  ariaDescribedBy?: string;
  altTextRequired: boolean;
  captionsRequired: boolean;
  keyboardNavigation: boolean;
  screenReaderSupport: boolean;
  highContrastMode: boolean;
  focusManagement: boolean;
}

export interface MediaPerformance {
  lazyLoading: boolean;
  preloading: boolean;
  caching: boolean;
  compressionEnabled: boolean;
  cdnDelivery: boolean;
  bandwidthAdaptive: boolean;
  mobileOptimization: boolean;
}

export interface LightboxConfig {
  enabled: boolean;
  showThumbnails: boolean;
  showCaptions: boolean;
  showCounter: boolean;
  enableZoom: boolean;
  enableFullscreen: boolean;
  autoplay: boolean;
  autoplaySpeed: number;
  keyboardControls: boolean;
  touchGestures: boolean;
  closeOnBackdropClick: boolean;
  showCloseButton: boolean;
  showNavigationArrows: boolean;
  theme: 'dark' | 'light' | 'auto';
}

export interface TouchGestureConfig {
  swipeEnabled: boolean;
  pinchZoomEnabled: boolean;
  doubleTapZoomEnabled: boolean;
  swipeThreshold: number;
  pinchSensitivity: number;
  gestureDelay: number;
}

export interface MediaComponentConfig {
  type: MediaType;
  title?: string;
  description?: string;
  
  // Layout and styling
  layout: MediaLayout;
  theme: 'default' | 'minimal' | 'modern' | 'card';
  spacing: 'compact' | 'default' | 'spacious';
  titleSize?: 'sm' | 'md' | 'lg';
  textAlignment?: 'left' | 'center' | 'right';
  
  // Grid configuration
  gridColumns?: {
    desktop: number;
    tablet: number;
    mobile: number;
  };
  gridGap?: 'sm' | 'md' | 'lg';
  
  // Media assets
  mediaAssets: MediaAsset[];
  
  // Optimization settings
  optimization: MediaOptimization;
  
  // Performance settings
  performance: MediaPerformance;
  
  // Accessibility settings
  accessibility: MediaAccessibility;
  
  // Lightbox configuration
  lightbox?: LightboxConfig;
  
  // Touch gesture configuration
  touchGestures?: TouchGestureConfig;
  
  // Video-specific settings
  videoSettings?: VideoSettings;
  
  // Interactive demo settings
  demoSettings?: {
    autoStart: boolean;
    showControls: boolean;
    enableInteraction: boolean;
    mobileCompatible: boolean;
    touchSupport: boolean;
    keyboardSupport: boolean;
  };
  
  // Analytics
  trackingEnabled?: boolean;
  trackViews?: boolean;
  trackInteractions?: boolean;
  trackEngagement?: boolean;
  
  // A/B Testing
  abTest?: ABTestConfig;
  
  // Mobile-specific settings
  mobileOptimized: boolean;
  mobileLayout?: MediaLayout;
  mobileGridColumns?: {
    mobile: number;
  };
  
  // CDN and delivery
  cdnConfig?: {
    enabled: boolean;
    provider: string;
    baseUrl: string;
    regions: string[];
  };
}

// CTA Component Types
export type CTAType = 'button' | 'banner' | 'inline-link';
export type CTAStyle = 'primary' | 'secondary' | 'outline' | 'ghost' | 'link';
export type CTASize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

export interface CTATrackingParams {
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  [key: string]: string | undefined;
}

export interface CTAConversionEvent {
  eventName: string;
  category: string;
  action: string;
  label?: string;
  value?: number;
  customProperties?: Record<string, any>;
}

export interface CTAABTestVariant {
  id: string;
  name: string;
  weight: number;
  config: Partial<CTAComponentConfig>;
  conversionRate?: number;
  impressions?: number;
  conversions?: number;
}

export interface CTAAccessibilityConfig {
  ariaLabel?: string;
  ariaDescribedBy?: string;
  role?: string;
  tabIndex?: number;
  keyboardShortcut?: string;
}

export interface CTAButtonConfig {
  text: string;
  url: string;
  style: CTAStyle;
  size: CTASize;
  icon?: {
    name: string;
    position: 'left' | 'right' | 'only';
    size?: 'sm' | 'md' | 'lg';
  };
  disabled?: boolean;
  loading?: boolean;
  fullWidth?: boolean;
  openInNewTab?: boolean;
  
  // Styling
  customColors?: {
    background?: string;
    text?: string;
    border?: string;
    hover?: {
      background?: string;
      text?: string;
      border?: string;
    };
  };
  
  // Animation
  animation?: {
    hover?: 'scale' | 'lift' | 'glow' | 'pulse' | 'none';
    click?: 'ripple' | 'bounce' | 'none';
    loading?: 'spinner' | 'dots' | 'pulse';
  };
  
  // Tracking
  trackingParams?: CTATrackingParams;
  conversionEvents?: CTAConversionEvent[];
  
  // A/B Testing
  abTestVariant?: string;
  
  // Accessibility
  accessibility?: CTAAccessibilityConfig;
}

export interface CTABannerConfig {
  title?: string;
  subtitle?: string;
  description?: string;
  backgroundImage?: MediaAsset;
  backgroundColor?: string;
  textColor?: string;
  
  // Layout
  layout: 'left-aligned' | 'center-aligned' | 'right-aligned' | 'split';
  height?: 'compact' | 'medium' | 'large' | 'full-screen';
  padding?: 'none' | 'sm' | 'md' | 'lg' | 'xl';
  
  // Content positioning
  contentPosition?: 'top' | 'center' | 'bottom';
  textAlignment?: 'left' | 'center' | 'right';
  
  // CTA buttons
  primaryCTA?: CTAButtonConfig;
  secondaryCTA?: CTAButtonConfig;
  
  // Overlay
  overlay?: {
    enabled: boolean;
    color: string;
    opacity: number;
  };
  
  // Animation
  parallax?: boolean;
  animateOnScroll?: boolean;
  
  // Responsive
  mobileLayout?: 'stacked' | 'overlay' | 'hidden';
  
  // Tracking
  trackingParams?: CTATrackingParams;
  conversionEvents?: CTAConversionEvent[];
  
  // A/B Testing
  abTestVariant?: string;
  
  // Accessibility
  accessibility?: CTAAccessibilityConfig;
}

export interface CTAInlineLinkConfig {
  text: string;
  url: string;
  style?: 'default' | 'underline' | 'button-like' | 'arrow' | 'external';
  color?: string;
  weight?: 'normal' | 'medium' | 'semibold' | 'bold';
  size?: 'xs' | 'sm' | 'base' | 'lg' | 'xl';
  
  // Icon
  icon?: {
    name: string;
    position: 'left' | 'right';
    size?: 'sm' | 'md' | 'lg';
  };
  
  // Behavior
  openInNewTab?: boolean;
  downloadAttribute?: string;
  
  // Animation
  animation?: {
    hover?: 'underline' | 'color-change' | 'scale' | 'none';
    transition?: 'fast' | 'normal' | 'slow';
  };
  
  // Tracking
  trackingParams?: CTATrackingParams;
  conversionEvents?: CTAConversionEvent[];
  
  // A/B Testing
  abTestVariant?: string;
  
  // Accessibility
  accessibility?: CTAAccessibilityConfig;
}

export interface CTAComponentConfig {
  type: CTAType;
  
  // Type-specific configurations
  buttonConfig?: CTAButtonConfig;
  bannerConfig?: CTABannerConfig;
  inlineLinkConfig?: CTAInlineLinkConfig;
  
  // Global settings
  theme?: 'default' | 'minimal' | 'modern' | 'classic';
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent' | 'custom';
  
  // Analytics
  trackingEnabled?: boolean;
  conversionGoal?: string;
  
  // A/B Testing
  abTest?: {
    enabled: boolean;
    testId?: string;
    variants?: CTAABTestVariant[];
    trafficSplit?: Record<string, number>;
  };
  
  // Performance
  preloadDestination?: boolean;
  lazyLoad?: boolean;
  
  // Accessibility
  respectReducedMotion?: boolean;
  highContrast?: boolean;
  
  // Context
  context?: {
    pageType?: string;
    section?: string;
    position?: number;
    audienceType?: AudienceType;
  };
}