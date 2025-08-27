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