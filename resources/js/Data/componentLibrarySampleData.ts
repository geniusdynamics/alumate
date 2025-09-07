import type { Component, ComponentCategory, HeroComponentConfig, FormComponentConfig, TestimonialComponentConfig, StatisticsComponentConfig, CTAComponentConfig, MediaComponentConfig } from '@/types/components'

// Sample Hero Components
const sampleHeroComponents: Component[] = [
  {
    id: 'hero-individual-1',
    tenantId: 'sample-tenant',
    name: 'Individual Alumni Hero',
    slug: 'individual-alumni-hero',
    category: 'hero',
    type: 'individual',
    description: 'Hero section designed for individual alumni showcasing personal success stories',
    config: {
      headline: 'Advance Your Career with Alumni Connections',
      subheading: 'Connect with 50,000+ professionals from your alma mater',
      description: 'Join our exclusive alumni network and unlock opportunities for career growth, mentorship, and professional development.',
      audienceType: 'individual',
      backgroundMedia: {
        type: 'image',
        image: {
          id: 'hero-bg-1',
          type: 'image',
          url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
          alt: 'Professional networking event'
        },
        overlay: {
          color: '#000000',
          opacity: 0.4
        }
      },
      ctaButtons: [
        {
          id: 'cta-1',
          text: 'Join Network',
          url: '/signup',
          style: 'primary',
          size: 'lg',
          trackingParams: {
            utm_source: 'hero',
            utm_campaign: 'individual_signup'
          }
        }
      ],
      layout: 'centered',
      textAlignment: 'center',
      contentPosition: 'center',
      headingLevel: 1
    } as HeroComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-15T10:00:00Z',
    updatedAt: '2024-01-20T14:30:00Z'
  },
  {
    id: 'hero-institution-1',
    tenantId: 'sample-tenant',
    name: 'Institution Partnership Hero',
    slug: 'institution-partnership-hero',
    category: 'hero',
    type: 'institution',
    description: 'Hero section for institutions highlighting partnership benefits',
    config: {
      headline: 'Strengthen Alumni Engagement',
      subheading: 'Partner with us to build lasting connections',
      description: 'Enhance your alumni relations with our comprehensive platform designed for educational institutions.',
      audienceType: 'institution',
      backgroundMedia: {
        type: 'gradient',
        gradient: {
          type: 'linear',
          direction: '135deg',
          colors: [
            { color: '#667eea', stop: 0 },
            { color: '#764ba2', stop: 100 }
          ]
        }
      },
      ctaButtons: [
        {
          id: 'cta-2',
          text: 'Schedule Demo',
          url: '/demo',
          style: 'primary',
          size: 'lg',
          trackingParams: {
            utm_source: 'hero',
            utm_campaign: 'institution_demo'
          }
        }
      ],
      layout: 'left-aligned',
      textAlignment: 'left',
      contentPosition: 'center',
      headingLevel: 1
    } as HeroComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-16T09:00:00Z',
    updatedAt: '2024-01-21T11:15:00Z'
  }
]

// Sample Form Components
const sampleFormComponents: Component[] = [
  {
    id: 'form-signup-1',
    tenantId: 'sample-tenant',
    name: 'Alumni Signup Form',
    slug: 'alumni-signup-form',
    category: 'forms',
    type: 'signup',
    description: 'Registration form for new alumni members',
    config: {
      title: 'Join Our Alumni Network',
      description: 'Connect with fellow graduates and expand your professional network',
      fields: [
        {
          id: 'first_name',
          type: 'text',
          name: 'first_name',
          label: 'First Name',
          placeholder: 'Enter your first name',
          required: true,
          validation: [
            { rule: 'required', message: 'First name is required' }
          ],
          width: 'half'
        },
        {
          id: 'last_name',
          type: 'text',
          name: 'last_name',
          label: 'Last Name',
          placeholder: 'Enter your last name',
          required: true,
          validation: [
            { rule: 'required', message: 'Last name is required' }
          ],
          width: 'half'
        },
        {
          id: 'email',
          type: 'email',
          name: 'email',
          label: 'Email Address',
          placeholder: 'Enter your email',
          required: true,
          validation: [
            { rule: 'required', message: 'Email is required' },
            { rule: 'email', message: 'Please enter a valid email' }
          ],
          width: 'full'
        }
      ],
      layout: 'two-column',
      spacing: 'default',
      submission: {
        method: 'POST',
        action: '/api/signup',
        successMessage: 'Welcome to our alumni network!',
        redirectUrl: '/dashboard'
      },
      theme: 'modern'
    } as FormComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-17T08:00:00Z',
    updatedAt: '2024-01-22T16:45:00Z'
  }
]

// Sample Testimonial Components
const sampleTestimonialComponents: Component[] = [
  {
    id: 'testimonial-carousel-1',
    tenantId: 'sample-tenant',
    name: 'Alumni Success Stories',
    slug: 'alumni-success-stories',
    category: 'testimonials',
    type: 'carousel',
    description: 'Rotating testimonials from successful alumni',
    config: {
      layout: 'carousel',
      testimonials: [
        {
          id: 'testimonial-1',
          author: {
            id: 'author-1',
            name: 'Sarah Johnson',
            title: 'Senior Software Engineer',
            company: 'Tech Corp',
            graduationYear: 2018,
            photo: {
              id: 'photo-1',
              type: 'image',
              url: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80',
              alt: 'Sarah Johnson headshot'
            }
          },
          content: {
            id: 'content-1',
            quote: 'The alumni network helped me land my dream job. The connections I made were invaluable.',
            rating: 5,
            type: 'text',
            dateCreated: '2024-01-10T00:00:00Z'
          },
          audienceType: 'individual',
          featured: true,
          approved: true
        }
      ],
      carouselConfig: {
        autoplay: true,
        autoplaySpeed: 5000,
        showDots: true,
        showArrows: true,
        slidesToShow: 1
      },
      showAuthorPhoto: true,
      showAuthorTitle: true,
      showAuthorCompany: true,
      showRating: true,
      theme: 'card'
    } as TestimonialComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-18T12:00:00Z',
    updatedAt: '2024-01-23T09:20:00Z'
  }
]

// Sample Statistics Components
const sampleStatisticsComponents: Component[] = [
  {
    id: 'stats-counters-1',
    tenantId: 'sample-tenant',
    name: 'Network Statistics',
    slug: 'network-statistics',
    category: 'statistics',
    type: 'counters',
    description: 'Animated counters showing network growth and engagement',
    config: {
      title: 'Our Growing Community',
      displayType: 'counters',
      layout: 'grid',
      theme: 'modern',
      spacing: 'default',
      counterSize: 'lg',
      showLabels: true,
      showValues: true,
      gridColumns: {
        desktop: 4,
        tablet: 2,
        mobile: 1
      },
      animation: {
        enabled: true,
        trigger: 'scroll',
        duration: 2000,
        delay: 0,
        stagger: 200,
        easing: 'ease-out'
      },
      accessibility: {
        ariaLabel: 'Alumni network statistics',
        announceUpdates: true,
        respectReducedMotion: true
      }
    } as StatisticsComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-19T15:00:00Z',
    updatedAt: '2024-01-24T13:10:00Z'
  }
]

// Sample CTA Components
const sampleCTAComponents: Component[] = [
  {
    id: 'cta-button-1',
    tenantId: 'sample-tenant',
    name: 'Primary CTA Button',
    slug: 'primary-cta-button',
    category: 'ctas',
    type: 'button',
    description: 'Primary call-to-action button for key conversions',
    config: {
      type: 'button',
      buttonConfig: {
        text: 'Get Started Today',
        url: '/signup',
        style: 'primary',
        size: 'lg',
        icon: {
          name: 'arrow-right',
          position: 'right',
          size: 'md'
        },
        animation: {
          hover: 'lift',
          click: 'ripple'
        },
        trackingParams: {
          utm_source: 'cta_button',
          utm_campaign: 'primary_conversion'
        }
      },
      theme: 'modern',
      colorScheme: 'primary',
      trackingEnabled: true
    } as CTAComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-20T11:00:00Z',
    updatedAt: '2024-01-25T14:30:00Z'
  }
]

// Sample Media Components
const sampleMediaComponents: Component[] = [
  {
    id: 'media-gallery-1',
    tenantId: 'sample-tenant',
    name: 'Alumni Photo Gallery',
    slug: 'alumni-photo-gallery',
    category: 'media',
    type: 'image-gallery',
    description: 'Interactive photo gallery showcasing alumni events and achievements',
    config: {
      type: 'image-gallery',
      title: 'Alumni Moments',
      description: 'Celebrating our community through shared experiences',
      layout: 'grid',
      theme: 'card',
      spacing: 'default',
      gridColumns: {
        desktop: 3,
        tablet: 2,
        mobile: 1
      },
      mediaAssets: [
        {
          id: 'gallery-img-1',
          type: 'image',
          url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          alt: 'Alumni networking event',
          thumbnail: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
        }
      ],
      lightbox: {
        enabled: true,
        showThumbnails: true,
        showCaptions: true,
        keyboardControls: true,
        touchGestures: true
      },
      optimization: {
        webpSupport: true,
        lazyLoading: true,
        responsiveImages: true,
        cdnEnabled: true,
        compressionLevel: 'medium'
      },
      accessibility: {
        altTextRequired: true,
        keyboardNavigation: true,
        screenReaderSupport: true,
        focusManagement: true
      },
      mobileOptimized: true
    } as MediaComponentConfig,
    version: '1.0.0',
    isActive: true,
    createdAt: '2024-01-21T10:00:00Z',
    updatedAt: '2024-01-26T12:15:00Z'
  }
]

// Combine all sample components
export const sampleComponents: Component[] = [
  ...sampleHeroComponents,
  ...sampleFormComponents,
  ...sampleTestimonialComponents,
  ...sampleStatisticsComponents,
  ...sampleCTAComponents,
  ...sampleMediaComponents
]

// Sample components organized by category
export const sampleComponentsByCategory: Record<ComponentCategory, Component[]> = {
  hero: sampleHeroComponents,
  forms: sampleFormComponents,
  testimonials: sampleTestimonialComponents,
  statistics: sampleStatisticsComponents,
  ctas: sampleCTAComponents,
  media: sampleMediaComponents
}

// Utility functions
export function getComponentsByCategory(category: ComponentCategory): Component[] {
  return sampleComponentsByCategory[category] || []
}

export function getComponentById(id: string): Component | undefined {
  return sampleComponents.find(component => component.id === id)
}

export function searchComponents(query: string): Component[] {
  const searchTerm = query.toLowerCase().trim()
  if (!searchTerm) return sampleComponents

  return sampleComponents.filter(component =>
    component.name.toLowerCase().includes(searchTerm) ||
    component.description?.toLowerCase().includes(searchTerm) ||
    component.type.toLowerCase().includes(searchTerm) ||
    component.category.toLowerCase().includes(searchTerm)
  )
}

export function getPopularComponents(limit: number = 6): Component[] {
  // In a real implementation, this would be based on usage statistics
  // For now, return featured components
  return sampleComponents
    .filter(component => component.isActive)
    .slice(0, limit)
}

export function getRecentComponents(limit: number = 6): Component[] {
  return sampleComponents
    .filter(component => component.isActive)
    .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime())
    .slice(0, limit)
}

// Component metadata for thumbnails and previews
export const componentMetadata: Record<string, { thumbnailUrl?: string; tags?: string[] }> = {
  'hero-individual-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['individual', 'networking', 'career']
  },
  'hero-institution-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['institution', 'partnership', 'education']
  },
  'form-signup-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['signup', 'registration', 'form']
  },
  'testimonial-carousel-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['testimonial', 'success', 'carousel']
  },
  'stats-counters-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['statistics', 'counters', 'metrics']
  },
  'cta-button-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['cta', 'button', 'conversion']
  },
  'media-gallery-1': {
    thumbnailUrl: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
    tags: ['gallery', 'photos', 'events']
  }
}

export default sampleComponents