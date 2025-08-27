import type { HeroSampleData, HeroComponentConfig } from '@/types/components'

export const heroSampleData: HeroSampleData = {
  individual: {
    headline: "Your Success Story Starts Here",
    subheading: "Join 25,000+ alumni who transformed their careers through meaningful connections",
    description: "From recent graduates landing dream jobs to seasoned professionals making career pivots - our alumni network has been the catalyst for countless success stories. Your next chapter begins with a single connection.",
    ctaButtons: [
      {
        id: 'join-network',
        text: 'Start My Journey',
        url: '/register',
        style: 'primary',
        size: 'lg',
        icon: 'rocket',
        trackingParams: {
          source: 'hero',
          audience: 'individual',
          variant: 'success-story'
        }
      },
      {
        id: 'success-stories',
        text: 'Read Success Stories',
        url: '/success-stories',
        style: 'outline',
        size: 'lg',
        icon: 'star',
        trackingParams: {
          source: 'hero',
          audience: 'individual',
          variant: 'success-story'
        }
      }
    ],
    statistics: [
      {
        id: 'alumni-count',
        value: 25000,
        label: 'Active Alumni',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'connections-made',
        value: 150000,
        label: 'Connections Made',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'job-placements',
        value: 8500,
        label: 'Job Placements',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'avg-salary-increase',
        value: 35,
        label: 'Avg Salary Increase',
        suffix: '%',
        animated: true,
        source: 'manual'
      }
    ],
    backgroundMedia: {
      type: 'gradient',
      gradient: {
        type: 'linear',
        direction: '135deg',
        colors: [
          { color: '#667eea', stop: 0 },
          { color: '#764ba2', stop: 100 }
        ]
      },
      overlay: {
        color: 'rgba(0, 0, 0, 0.3)',
        opacity: 0.3
      },
      lazyLoad: true,
      mobileOptimized: true,
      fallback: {
        type: 'gradient',
        gradient: {
          type: 'linear',
          direction: '135deg',
          colors: [
            { color: '#6366f1', stop: 0 },
            { color: '#8b5cf6', stop: 100 }
          ]
        }
      }
    }
  },

  institution: {
    headline: "Transform Alumni Engagement Into Institutional Excellence",
    subheading: "Partner with us to build the most connected, engaged, and successful alumni network in higher education",
    description: "Leading institutions trust our platform to strengthen alumni bonds, increase giving by 120%, and create lasting partnerships that drive student success. Join 500+ institutions already transforming their alumni engagement strategy.",
    ctaButtons: [
      {
        id: 'partnership-demo',
        text: 'Explore Partnership',
        url: '/institutional-demo',
        style: 'primary',
        size: 'lg',
        icon: 'handshake',
        trackingParams: {
          source: 'hero',
          audience: 'institution',
          variant: 'partnership-focus'
        }
      },
      {
        id: 'roi-calculator',
        text: 'Calculate ROI',
        url: '/roi-calculator',
        style: 'outline',
        size: 'lg',
        icon: 'calculator',
        trackingParams: {
          source: 'hero',
          audience: 'institution',
          variant: 'partnership-focus'
        }
      }
    ],
    statistics: [
      {
        id: 'institutions-served',
        value: 500,
        label: 'Institutions Served',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'engagement-increase',
        value: 85,
        label: 'Engagement Increase',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'donation-growth',
        value: 120,
        label: 'Donation Growth',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'placement-rate',
        value: 94,
        label: 'Placement Rate',
        suffix: '%',
        animated: true,
        source: 'manual'
      }
    ],
    backgroundMedia: {
      type: 'gradient',
      gradient: {
        type: 'linear',
        direction: '120deg',
        colors: [
          { color: '#a8edea', stop: 0 },
          { color: '#fed6e3', stop: 100 }
        ]
      },
      overlay: {
        color: 'rgba(0, 0, 0, 0.2)',
        opacity: 0.2
      },
      lazyLoad: true,
      mobileOptimized: true,
      fallback: {
        type: 'gradient',
        gradient: {
          type: 'linear',
          direction: '120deg',
          colors: [
            { color: '#06b6d4', stop: 0 },
            { color: '#ec4899', stop: 100 }
          ]
        }
      }
    }
  },

  employer: {
    headline: "Hire Smarter, Hire Faster, Hire Better",
    subheading: "Access pre-vetted alumni talent and reduce your time-to-hire by 40% with our intelligent recruitment platform",
    description: "Stop sifting through endless resumes. Our platform connects you directly with qualified alumni professionals who are actively seeking opportunities, complete with verified credentials and peer recommendations.",
    ctaButtons: [
      {
        id: 'start-hiring',
        text: 'Start Hiring Today',
        url: '/employer-onboarding',
        style: 'primary',
        size: 'lg',
        icon: 'zap',
        trackingParams: {
          source: 'hero',
          audience: 'employer',
          variant: 'efficiency-focus'
        }
      },
      {
        id: 'talent-preview',
        text: 'Preview Talent Pool',
        url: '/talent-preview',
        style: 'outline',
        size: 'lg',
        icon: 'users',
        trackingParams: {
          source: 'hero',
          audience: 'employer',
          variant: 'efficiency-focus'
        }
      }
    ],
    statistics: [
      {
        id: 'qualified-candidates',
        value: 50000,
        label: 'Qualified Candidates',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'successful-hires',
        value: 12000,
        label: 'Successful Hires',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'time-to-hire',
        value: 40,
        label: 'Faster Hiring',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'retention-rate',
        value: 92,
        label: 'Retention Rate',
        suffix: '%',
        animated: true,
        source: 'manual'
      }
    ],
    backgroundMedia: {
      type: 'gradient',
      gradient: {
        type: 'linear',
        direction: '45deg',
        colors: [
          { color: '#ff9a9e', stop: 0 },
          { color: '#fecfef', stop: 50 },
          { color: '#fecfef', stop: 100 }
        ]
      },
      overlay: {
        color: 'rgba(0, 0, 0, 0.25)',
        opacity: 0.25
      },
      lazyLoad: true,
      mobileOptimized: true,
      fallback: {
        type: 'gradient',
        gradient: {
          type: 'linear',
          direction: '45deg',
          colors: [
            { color: '#f59e0b', stop: 0 },
            { color: '#ef4444', stop: 100 }
          ]
        }
      }
    }
  }
}

export const getHeroConfigForAudience = (audienceType: 'individual' | 'institution' | 'employer'): HeroComponentConfig => {
  const sampleData = heroSampleData[audienceType]
  
  return {
    headline: sampleData.headline,
    subheading: sampleData.subheading,
    description: sampleData.description,
    audienceType,
    backgroundMedia: sampleData.backgroundMedia,
    ctaButtons: sampleData.ctaButtons,
    statistics: sampleData.statistics,
    layout: 'centered',
    textAlignment: 'center',
    contentPosition: 'center',
    mobileLayout: 'stacked',
    headingLevel: 1,
    animations: {
      enabled: true,
      entrance: 'fade',
      duration: 800,
      delay: 200
    },
    lazyLoad: true,
    preloadImages: false
  }
}

// Enhanced sample configurations with different media types
export const heroMediaSamples = {
  videoBackground: {
    type: 'video' as const,
    video: {
      id: 'hero-video-1',
      type: 'video' as const,
      url: 'https://example.com/videos/hero-background.mp4',
      alt: 'Alumni success stories montage',
      mimeType: 'video/mp4',
      autoplay: true,
      muted: true,
      loop: true,
      poster: 'https://example.com/images/video-poster.jpg',
      preload: 'metadata' as const,
      disableOnMobile: false,
      quality: 'auto' as const,
      adaptiveBitrate: true,
      mobileVideo: {
        id: 'hero-video-mobile-1',
        type: 'video' as const,
        url: 'https://example.com/videos/hero-background-mobile.mp4',
        mimeType: 'video/mp4'
      }
    },
    overlay: {
      color: 'rgba(0, 0, 0, 0.4)',
      opacity: 0.4
    },
    lazyLoad: true,
    mobileOptimized: true,
    fallback: {
      type: 'image' as const,
      image: {
        id: 'hero-fallback-1',
        type: 'image' as const,
        url: 'https://example.com/images/hero-fallback.jpg',
        alt: 'Alumni networking event'
      }
    }
  },

  imageBackground: {
    type: 'image' as const,
    image: {
      id: 'hero-image-1',
      type: 'image' as const,
      url: 'https://example.com/images/hero-background.jpg',
      alt: 'Diverse group of successful alumni',
      width: 1920,
      height: 1080,
      srcSet: [
        { url: 'https://example.com/images/hero-background-320.webp', width: 320, format: 'webp' as const },
        { url: 'https://example.com/images/hero-background-640.webp', width: 640, format: 'webp' as const },
        { url: 'https://example.com/images/hero-background-1024.webp', width: 1024, format: 'webp' as const },
        { url: 'https://example.com/images/hero-background-1920.webp', width: 1920, format: 'webp' as const }
      ],
      mobileUrl: 'https://example.com/images/hero-background-mobile.jpg',
      mobileSrcSet: [
        { url: 'https://example.com/images/hero-background-mobile-320.webp', width: 320, format: 'webp' as const },
        { url: 'https://example.com/images/hero-background-mobile-640.webp', width: 640, format: 'webp' as const }
      ],
      fallbackUrl: 'https://example.com/images/hero-background-fallback.jpg',
      placeholder: 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
      cdnUrl: 'https://cdn.example.com',
      optimized: true
    },
    overlay: {
      color: 'rgba(0, 0, 0, 0.3)',
      opacity: 0.3
    },
    lazyLoad: true,
    preload: false,
    mobileOptimized: true,
    fallback: {
      type: 'gradient' as const,
      gradient: {
        type: 'linear' as const,
        direction: '135deg',
        colors: [
          { color: '#3b82f6', stop: 0 },
          { color: '#1d4ed8', stop: 100 }
        ]
      }
    }
  }
}

export const defaultHeroConfig: HeroComponentConfig = {
  headline: "Welcome to Our Platform",
  subheading: "Discover amazing opportunities",
  description: "Join our community and unlock your potential with powerful tools and connections.",
  audienceType: 'individual',
  ctaButtons: [
    {
      id: 'get-started',
      text: 'Get Started',
      url: '/register',
      style: 'primary',
      size: 'lg'
    }
  ],
  layout: 'centered',
  textAlignment: 'center',
  contentPosition: 'center',
  headingLevel: 1,
  animations: {
    enabled: true,
    entrance: 'fade',
    duration: 600
  },
  lazyLoad: true
}