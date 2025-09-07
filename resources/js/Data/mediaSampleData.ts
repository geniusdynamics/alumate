import type { MediaComponentConfig, MediaAsset } from '@/types/components'

// Sample media assets
export const sampleImages: MediaAsset[] = [
  {
    id: 'img-1',
    type: 'image',
    url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80',
    alt: 'Students collaborating in a modern classroom',
    width: 1471,
    height: 980,
    thumbnail: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80',
    srcSet: [
      { url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&q=80', width: 400, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&q=80', width: 800, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&q=80', width: 1200, format: 'webp' }
    ],
    optimized: true,
    caption: 'Alumni networking event at the university campus'
  },
  {
    id: 'img-2',
    type: 'image',
    url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80',
    alt: 'Graduation ceremony with students in caps and gowns',
    width: 1470,
    height: 980,
    thumbnail: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80',
    srcSet: [
      { url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&q=80', width: 400, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80', width: 800, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&q=80', width: 1200, format: 'webp' }
    ],
    optimized: true,
    caption: 'Class of 2023 graduation ceremony'
  },
  {
    id: 'img-3',
    type: 'image',
    url: 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1498&q=80',
    alt: 'Professional business meeting with alumni',
    width: 1498,
    height: 1000,
    thumbnail: 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80',
    srcSet: [
      { url: 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=400&q=80', width: 400, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=800&q=80', width: 800, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=1200&q=80', width: 1200, format: 'webp' }
    ],
    optimized: true,
    caption: 'Alumni career development workshop'
  },
  {
    id: 'img-4',
    type: 'image',
    url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80',
    alt: 'University campus building exterior',
    width: 1470,
    height: 980,
    thumbnail: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80',
    srcSet: [
      { url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400&q=80', width: 400, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&q=80', width: 800, format: 'webp' },
      { url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1200&q=80', width: 1200, format: 'webp' }
    ],
    optimized: true,
    caption: 'Historic university campus main building'
  }
]

export const sampleVideos: MediaAsset[] = [
  {
    id: 'vid-1',
    type: 'video',
    url: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
    alt: 'Alumni success story video',
    width: 1920,
    height: 1080,
    thumbnail: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/images/BigBuckBunny.jpg',
    mimeType: 'video/mp4',
    title: 'Alumni Success Stories',
    description: 'Hear from our graduates about their career journeys and achievements',
    videoAsset: {
      duration: 596,
      transcript: 'Sample transcript for alumni success stories video...',
      captions: 'https://example.com/captions/alumni-success.vtt',
      chapters: [
        { time: 0, title: 'Introduction' },
        { time: 60, title: 'Career Highlights' },
        { time: 180, title: 'Advice for Students' },
        { time: 300, title: 'Future Goals' }
      ],
      qualities: [
        { label: 'HD', src: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', type: 'video/mp4', width: 1920, height: 1080 },
        { label: 'SD', src: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', type: 'video/mp4', width: 1280, height: 720 }
      ]
    }
  },
  {
    id: 'vid-2',
    type: 'video',
    url: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
    alt: 'University campus tour video',
    width: 1920,
    height: 1080,
    thumbnail: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/images/ElephantsDream.jpg',
    mimeType: 'video/mp4',
    title: 'Virtual Campus Tour',
    description: 'Take a virtual tour of our beautiful campus facilities and student life',
    videoAsset: {
      duration: 653,
      transcript: 'Sample transcript for campus tour video...',
      captions: 'https://example.com/captions/campus-tour.vtt',
      chapters: [
        { time: 0, title: 'Welcome to Campus' },
        { time: 120, title: 'Academic Buildings' },
        { time: 300, title: 'Student Life' },
        { time: 480, title: 'Recreation Facilities' }
      ]
    }
  }
]

// Sample media configurations
export const imageGalleryConfig: MediaComponentConfig = {
  type: 'image-gallery',
  title: 'Alumni Photo Gallery',
  description: 'Explore moments from our vibrant alumni community',
  layout: 'grid',
  theme: 'card',
  spacing: 'default',
  titleSize: 'lg',
  textAlignment: 'center',
  
  gridColumns: {
    desktop: 3,
    tablet: 2,
    mobile: 1
  },
  gridGap: 'md',
  
  mediaAssets: sampleImages,
  
  optimization: {
    webpSupport: true,
    avifSupport: false,
    lazyLoading: true,
    responsiveImages: true,
    cdnEnabled: true,
    compressionLevel: 'medium',
    maxWidth: 1920,
    maxHeight: 1080
  },
  
  performance: {
    lazyLoading: true,
    preloading: false,
    caching: true,
    compressionEnabled: true,
    cdnDelivery: true,
    bandwidthAdaptive: true,
    mobileOptimization: true
  },
  
  accessibility: {
    ariaLabel: 'Alumni photo gallery with lightbox functionality',
    altTextRequired: true,
    captionsRequired: false,
    keyboardNavigation: true,
    screenReaderSupport: true,
    highContrastMode: true,
    focusManagement: true
  },
  
  lightbox: {
    enabled: true,
    showThumbnails: true,
    showCaptions: true,
    showCounter: true,
    enableZoom: true,
    enableFullscreen: true,
    autoplay: false,
    autoplaySpeed: 5000,
    keyboardControls: true,
    touchGestures: true,
    closeOnBackdropClick: true,
    showCloseButton: true,
    showNavigationArrows: true,
    theme: 'dark'
  },
  
  touchGestures: {
    swipeEnabled: true,
    pinchZoomEnabled: true,
    doubleTapZoomEnabled: true,
    swipeThreshold: 50,
    pinchSensitivity: 0.5,
    gestureDelay: 100
  },
  
  trackingEnabled: true,
  trackViews: true,
  trackInteractions: true,
  trackEngagement: true,
  
  mobileOptimized: true,
  mobileLayout: 'grid',
  mobileGridColumns: {
    mobile: 1
  },
  
  cdnConfig: {
    enabled: true,
    provider: 'cloudinary',
    baseUrl: 'https://res.cloudinary.com/demo',
    regions: ['us-east-1', 'eu-west-1']
  }
}

export const videoEmbedConfig: MediaComponentConfig = {
  type: 'video-embed',
  title: 'Featured Videos',
  description: 'Watch our latest alumni stories and campus highlights',
  layout: 'grid',
  theme: 'modern',
  spacing: 'spacious',
  titleSize: 'lg',
  textAlignment: 'left',
  
  gridColumns: {
    desktop: 2,
    tablet: 1,
    mobile: 1
  },
  gridGap: 'lg',
  
  mediaAssets: sampleVideos,
  
  optimization: {
    webpSupport: true,
    avifSupport: false,
    lazyLoading: true,
    responsiveImages: true,
    cdnEnabled: true,
    compressionLevel: 'high',
    maxWidth: 1920,
    maxHeight: 1080
  },
  
  performance: {
    lazyLoading: true,
    preloading: true,
    caching: true,
    compressionEnabled: true,
    cdnDelivery: true,
    bandwidthAdaptive: true,
    mobileOptimization: true
  },
  
  accessibility: {
    ariaLabel: 'Video collection with accessibility controls',
    altTextRequired: true,
    captionsRequired: true,
    keyboardNavigation: true,
    screenReaderSupport: true,
    highContrastMode: true,
    focusManagement: true
  },
  
  videoSettings: {
    autoplay: false,
    muted: true,
    showControls: true,
    showCaptions: true,
    preload: 'metadata',
    loop: false,
    playsinline: true
  },
  
  trackingEnabled: true,
  trackViews: true,
  trackInteractions: true,
  trackEngagement: true,
  
  mobileOptimized: true,
  mobileLayout: 'column',
  
  cdnConfig: {
    enabled: true,
    provider: 'cloudflare',
    baseUrl: 'https://videodelivery.net',
    regions: ['global']
  }
}

export const interactiveDemoConfig: MediaComponentConfig = {
  type: 'interactive-demo',
  title: 'Platform Demo',
  description: 'Experience our alumni platform features interactively',
  layout: 'full-width',
  theme: 'card',
  spacing: 'default',
  titleSize: 'lg',
  textAlignment: 'center',
  
  mediaAssets: [], // Interactive demos don't use mediaAssets in the same way
  
  optimization: {
    webpSupport: true,
    avifSupport: false,
    lazyLoading: true,
    responsiveImages: true,
    cdnEnabled: false,
    compressionLevel: 'medium'
  },
  
  performance: {
    lazyLoading: false,
    preloading: true,
    caching: true,
    compressionEnabled: false,
    cdnDelivery: false,
    bandwidthAdaptive: true,
    mobileOptimization: true
  },
  
  accessibility: {
    ariaLabel: 'Interactive platform demonstration',
    altTextRequired: false,
    captionsRequired: false,
    keyboardNavigation: true,
    screenReaderSupport: true,
    highContrastMode: true,
    focusManagement: true
  },
  
  demoSettings: {
    autoStart: false,
    showControls: true,
    enableInteraction: true,
    mobileCompatible: true,
    touchSupport: true,
    keyboardSupport: true
  },
  
  trackingEnabled: true,
  trackViews: true,
  trackInteractions: true,
  trackEngagement: true,
  
  mobileOptimized: true,
  mobileLayout: 'full-width'
}

// Sample data for different use cases
export const mediaSampleData = {
  imageGallery: {
    individual: {
      ...imageGalleryConfig,
      title: 'My Alumni Journey',
      description: 'Personal photos from my time at university and beyond',
      gridColumns: { desktop: 2, tablet: 2, mobile: 1 }
    },
    institution: {
      ...imageGalleryConfig,
      title: 'Campus Life Gallery',
      description: 'Discover the vibrant community and beautiful campus',
      gridColumns: { desktop: 4, tablet: 3, mobile: 2 }
    },
    employer: {
      ...imageGalleryConfig,
      title: 'Talent Showcase',
      description: 'Meet our accomplished graduates in their professional environments',
      gridColumns: { desktop: 3, tablet: 2, mobile: 1 }
    }
  },
  
  videoEmbed: {
    individual: {
      ...videoEmbedConfig,
      title: 'My Success Story',
      description: 'Watch my journey from student to professional',
      gridColumns: { desktop: 1, tablet: 1, mobile: 1 }
    },
    institution: {
      ...videoEmbedConfig,
      title: 'University Highlights',
      description: 'Explore our programs, facilities, and student achievements',
      gridColumns: { desktop: 2, tablet: 1, mobile: 1 }
    },
    employer: {
      ...videoEmbedConfig,
      title: 'Graduate Testimonials',
      description: 'Hear from our alumni about their career success',
      gridColumns: { desktop: 3, tablet: 2, mobile: 1 }
    }
  },
  
  interactiveDemo: {
    individual: {
      ...interactiveDemoConfig,
      title: 'Personal Dashboard Demo',
      description: 'Explore how to manage your alumni profile and connections'
    },
    institution: {
      ...interactiveDemoConfig,
      title: 'Institution Portal Demo',
      description: 'Discover how to engage with your alumni community'
    },
    employer: {
      ...interactiveDemoConfig,
      title: 'Recruitment Platform Demo',
      description: 'Learn how to find and connect with top talent'
    }
  }
}

export default mediaSampleData