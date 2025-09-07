import type { 
  ComponentCategory, 
  AudienceType, 
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig,
  CTAButton,
  StatisticCounter,
  Testimonial,
  MediaAsset,
  StatisticsItem
} from '@/types/components'

interface SampleDataVariation {
  variation: 'default' | 'minimal' | 'rich' | 'localized'
  contentLength: 'short' | 'medium' | 'long'
}

// Sample data templates for different audience types and variations
const heroSampleData = {
  individual: {
    default: {
      short: {
        headline: 'Advance Your Career',
        subheading: 'Connect with alumni',
        description: 'Join our network of professionals.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Join Now',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const
          }
        ]
      },
      medium: {
        headline: 'Advance Your Career with Alumni Connections',
        subheading: 'Connect with 50,000+ professionals from your alma mater',
        description: 'Join our exclusive alumni network and unlock opportunities for career growth, mentorship, and professional development.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Join Network',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'Learn More',
            url: '/about',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      },
      long: {
        headline: 'Transform Your Career Journey with Powerful Alumni Connections',
        subheading: 'Connect with 50,000+ successful professionals from your alma mater and unlock unlimited opportunities',
        description: 'Join our exclusive alumni network and discover a world of possibilities. Connect with industry leaders, find mentors, explore career opportunities, and build lasting professional relationships that will accelerate your career growth and open doors to success.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Join Network Today',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'Explore Success Stories',
            url: '/testimonials',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      }
    },
    minimal: {
      short: {
        headline: 'Alumni Network',
        subheading: 'Connect. Grow. Succeed.',
        description: 'Professional networking made simple.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Start',
            url: '/signup',
            style: 'primary' as const,
            size: 'md' as const
          }
        ]
      },
      medium: {
        headline: 'Professional Alumni Network',
        subheading: 'Connect. Grow. Succeed.',
        description: 'Simple, effective professional networking for alumni.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Get Started',
            url: '/signup',
            style: 'primary' as const,
            size: 'md' as const
          }
        ]
      },
      long: {
        headline: 'Professional Alumni Network',
        subheading: 'Connect. Grow. Succeed.',
        description: 'A clean, simple approach to professional networking. Connect with fellow alumni, share opportunities, and grow your career through meaningful relationships.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Get Started',
            url: '/signup',
            style: 'primary' as const,
            size: 'md' as const
          }
        ]
      }
    },
    rich: {
      short: {
        headline: '🚀 Supercharge Your Career',
        subheading: '✨ Elite Alumni Network',
        description: '🎯 Connect with top professionals and unlock exclusive opportunities.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: '🚀 Launch Career',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const,
            icon: {
              name: 'rocket-launch',
              position: 'right' as const
            }
          }
        ]
      },
      medium: {
        headline: '🚀 Supercharge Your Career with Elite Alumni Connections',
        subheading: '✨ Join 50,000+ industry leaders and innovators from your alma mater',
        description: '🎯 Unlock exclusive opportunities, find world-class mentors, and accelerate your professional growth through our premium alumni network.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: '🚀 Launch Your Journey',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const,
            icon: {
              name: 'rocket-launch',
              position: 'right' as const
            }
          },
          {
            id: 'cta-2',
            text: '📊 View Success Metrics',
            url: '/stats',
            style: 'outline' as const,
            size: 'lg' as const
          }
        ]
      },
      long: {
        headline: '🚀 Transform Your Career Trajectory with Our Elite Alumni Network',
        subheading: '✨ Connect with 50,000+ industry leaders, innovators, and game-changers from your alma mater',
        description: '🎯 Step into a world of unlimited possibilities. Our premium alumni network connects you with C-suite executives, startup founders, industry pioneers, and thought leaders. Unlock exclusive job opportunities, find world-class mentors, access insider knowledge, and accelerate your professional growth through meaningful relationships that last a lifetime.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: '🚀 Launch Your Journey',
            url: '/signup',
            style: 'primary' as const,
            size: 'lg' as const,
            icon: {
              name: 'rocket-launch',
              position: 'right' as const
            }
          },
          {
            id: 'cta-2',
            text: '📊 View Success Metrics',
            url: '/stats',
            style: 'outline' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-3',
            text: '🎥 Watch Demo',
            url: '/demo',
            style: 'ghost' as const,
            size: 'lg' as const
          }
        ]
      }
    }
  },
  institution: {
    default: {
      short: {
        headline: 'Strengthen Alumni Relations',
        subheading: 'Comprehensive engagement platform',
        description: 'Build lasting connections with your graduates.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Schedule Demo',
            url: '/demo',
            style: 'primary' as const,
            size: 'lg' as const
          }
        ]
      },
      medium: {
        headline: 'Strengthen Alumni Engagement',
        subheading: 'Comprehensive platform for educational institutions',
        description: 'Enhance your alumni relations with our comprehensive platform designed specifically for educational institutions.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Schedule Demo',
            url: '/demo',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'View Features',
            url: '/features',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      },
      long: {
        headline: 'Transform Alumni Engagement with Our Comprehensive Platform',
        subheading: 'Purpose-built solution for educational institutions to strengthen lifelong connections',
        description: 'Enhance your alumni relations with our comprehensive platform designed specifically for educational institutions. Increase engagement, boost donations, facilitate networking, and build a thriving alumni community that supports your institution\'s mission and growth.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Schedule Demo',
            url: '/demo',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'View Case Studies',
            url: '/case-studies',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      }
    }
  },
  employer: {
    default: {
      short: {
        headline: 'Find Top Talent',
        subheading: 'Access qualified alumni',
        description: 'Connect with skilled professionals from top universities.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Post Jobs',
            url: '/jobs/post',
            style: 'primary' as const,
            size: 'lg' as const
          }
        ]
      },
      medium: {
        headline: 'Find Top Talent Through Alumni Networks',
        subheading: 'Access qualified professionals from leading universities',
        description: 'Connect with skilled professionals and recent graduates from top universities. Streamline your recruitment process and find the perfect candidates.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Post Jobs',
            url: '/jobs/post',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'Browse Talent',
            url: '/talent',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      },
      long: {
        headline: 'Discover Exceptional Talent Through Premier Alumni Networks',
        subheading: 'Access a curated pool of qualified professionals and recent graduates from leading universities worldwide',
        description: 'Transform your recruitment strategy by tapping into the power of alumni networks. Connect with skilled professionals, innovative thinkers, and ambitious graduates from top universities. Streamline your hiring process, reduce recruitment costs, and find candidates who are not just qualified, but also culturally aligned with your organization\'s values and mission.',
        ctaButtons: [
          {
            id: 'cta-1',
            text: 'Post Jobs Now',
            url: '/jobs/post',
            style: 'primary' as const,
            size: 'lg' as const
          },
          {
            id: 'cta-2',
            text: 'Browse Talent Pool',
            url: '/talent',
            style: 'secondary' as const,
            size: 'lg' as const
          }
        ]
      }
    }
  }
}

const statisticsSampleData = {
  default: {
    short: [
      { id: 'stat-1', value: 50000, label: 'Alumni', suffix: '+' },
      { id: 'stat-2', value: 95, label: 'Success Rate', suffix: '%' },
      { id: 'stat-3', value: 1200, label: 'Companies', suffix: '+' }
    ],
    medium: [
      { id: 'stat-1', value: 50000, label: 'Active Alumni', suffix: '+' },
      { id: 'stat-2', value: 95, label: 'Job Placement Rate', suffix: '%' },
      { id: 'stat-3', value: 1200, label: 'Partner Companies', suffix: '+' },
      { id: 'stat-4', value: 85, label: 'Satisfaction Score', suffix: '%' }
    ],
    long: [
      { id: 'stat-1', value: 50000, label: 'Active Alumni Members', suffix: '+' },
      { id: 'stat-2', value: 95, label: 'Job Placement Success Rate', suffix: '%' },
      { id: 'stat-3', value: 1200, label: 'Partner Companies Worldwide', suffix: '+' },
      { id: 'stat-4', value: 85, label: 'Member Satisfaction Score', suffix: '%' },
      { id: 'stat-5', value: 250, label: 'Countries Represented', suffix: '+' },
      { id: 'stat-6', value: 15, label: 'Years of Excellence', suffix: '+' }
    ]
  }
}

const testimonialSampleData = {
  individual: {
    default: {
      short: [
        {
          id: 'testimonial-1',
          author: {
            id: 'author-1',
            name: 'Sarah Johnson',
            title: 'Software Engineer',
            company: 'Tech Corp',
            graduationYear: 2018,
            photo: {
              id: 'photo-1',
              type: 'image' as const,
              url: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80',
              alt: 'Sarah Johnson headshot'
            }
          },
          content: {
            id: 'content-1',
            quote: 'The alumni network helped me land my dream job.',
            rating: 5,
            type: 'text' as const,
            dateCreated: '2024-01-10T00:00:00Z'
          },
          audienceType: 'individual' as const,
          featured: true,
          approved: true
        }
      ],
      medium: [
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
              type: 'image' as const,
              url: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80',
              alt: 'Sarah Johnson headshot'
            }
          },
          content: {
            id: 'content-1',
            quote: 'The alumni network helped me land my dream job. The connections I made were invaluable for my career growth.',
            rating: 5,
            type: 'text' as const,
            dateCreated: '2024-01-10T00:00:00Z'
          },
          audienceType: 'individual' as const,
          featured: true,
          approved: true
        },
        {
          id: 'testimonial-2',
          author: {
            id: 'author-2',
            name: 'Michael Chen',
            title: 'Product Manager',
            company: 'Innovation Labs',
            graduationYear: 2019,
            photo: {
              id: 'photo-2',
              type: 'image' as const,
              url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80',
              alt: 'Michael Chen headshot'
            }
          },
          content: {
            id: 'content-2',
            quote: 'Amazing platform for professional networking. Found my mentor here!',
            rating: 5,
            type: 'text' as const,
            dateCreated: '2024-01-15T00:00:00Z'
          },
          audienceType: 'individual' as const,
          featured: false,
          approved: true
        }
      ]
    }
  }
}

const mediaSampleData = {
  default: {
    short: [
      {
        id: 'media-1',
        type: 'image' as const,
        url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        alt: 'Alumni networking event',
        thumbnail: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
      }
    ],
    medium: [
      {
        id: 'media-1',
        type: 'image' as const,
        url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        alt: 'Alumni networking event',
        thumbnail: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
      },
      {
        id: 'media-2',
        type: 'image' as const,
        url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        alt: 'Graduation ceremony',
        thumbnail: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
      },
      {
        id: 'media-3',
        type: 'image' as const,
        url: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        alt: 'Professional conference',
        thumbnail: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
      }
    ]
  }
}

/**
 * Generate sample data for a component based on category, audience type, and variation
 */
export function generateSampleData(
  category: ComponentCategory,
  audienceType: AudienceType = 'individual',
  variation: 'default' | 'minimal' | 'rich' | 'localized' = 'default',
  contentLength: 'short' | 'medium' | 'long' = 'medium'
): any {
  switch (category) {
    case 'hero':
      return generateHeroSampleData(audienceType, variation, contentLength)
    
    case 'forms':
      return generateFormSampleData(audienceType, variation, contentLength)
    
    case 'testimonials':
      return generateTestimonialSampleData(audienceType, variation, contentLength)
    
    case 'statistics':
      return generateStatisticsSampleData(variation, contentLength)
    
    case 'ctas':
      return generateCTASampleData(audienceType, variation, contentLength)
    
    case 'media':
      return generateMediaSampleData(variation, contentLength)
    
    default:
      return null
  }
}

function generateHeroSampleData(
  audienceType: AudienceType,
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<HeroComponentConfig> {
  const data = heroSampleData[audienceType]?.[variation]?.[contentLength] || 
                heroSampleData[audienceType]?.default?.[contentLength] ||
                heroSampleData.individual.default[contentLength]
  
  return {
    ...data,
    audienceType,
    backgroundMedia: {
      type: 'image',
      image: {
        id: 'hero-bg-1',
        type: 'image',
        url: getBackgroundImageForAudience(audienceType),
        alt: `${audienceType} hero background`
      },
      overlay: {
        color: '#000000',
        opacity: 0.4
      }
    },
    layout: 'centered',
    textAlignment: 'center',
    contentPosition: 'center',
    headingLevel: 1
  }
}

function generateFormSampleData(
  audienceType: AudienceType,
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<FormComponentConfig> {
  const baseFields = [
    {
      id: 'first_name',
      type: 'text' as const,
      name: 'first_name',
      label: 'First Name',
      placeholder: 'Enter your first name',
      required: true,
      width: 'half' as const
    },
    {
      id: 'last_name',
      type: 'text' as const,
      name: 'last_name',
      label: 'Last Name',
      placeholder: 'Enter your last name',
      required: true,
      width: 'half' as const
    },
    {
      id: 'email',
      type: 'email' as const,
      name: 'email',
      label: 'Email Address',
      placeholder: 'Enter your email',
      required: true,
      width: 'full' as const
    }
  ]
  
  const additionalFields = contentLength === 'long' ? [
    {
      id: 'company',
      type: 'text' as const,
      name: 'company',
      label: 'Company',
      placeholder: 'Enter your company',
      required: false,
      width: 'full' as const
    },
    {
      id: 'graduation_year',
      type: 'number' as const,
      name: 'graduation_year',
      label: 'Graduation Year',
      placeholder: 'Enter graduation year',
      required: false,
      width: 'half' as const
    }
  ] : []
  
  return {
    title: getFormTitleForAudience(audienceType, contentLength),
    description: getFormDescriptionForAudience(audienceType, contentLength),
    fields: [...baseFields, ...additionalFields],
    layout: 'two-column',
    spacing: 'default',
    submission: {
      method: 'POST',
      action: '/api/signup',
      successMessage: 'Thank you for joining our network!',
      redirectUrl: '/dashboard'
    },
    theme: variation === 'minimal' ? 'minimal' : 'modern'
  }
}

function generateTestimonialSampleData(
  audienceType: AudienceType,
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<TestimonialComponentConfig> {
  const testimonials = testimonialSampleData[audienceType]?.default?.[contentLength] ||
                      testimonialSampleData.individual.default.medium
  
  return {
    layout: 'carousel',
    testimonials: testimonials as Testimonial[],
    showAuthorPhoto: true,
    showAuthorTitle: true,
    showAuthorCompany: true,
    showRating: variation !== 'minimal',
    theme: variation === 'minimal' ? 'minimal' : 'card',
    carouselConfig: {
      autoplay: true,
      autoplaySpeed: 5000,
      showDots: true,
      showArrows: true,
      slidesToShow: 1
    }
  }
}

function generateStatisticsSampleData(
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<StatisticsComponentConfig> {
  const stats = statisticsSampleData.default[contentLength]
  
  return {
    title: contentLength === 'short' ? 'Our Impact' : 'Growing Community Impact',
    displayType: 'counters',
    layout: 'grid',
    theme: variation === 'minimal' ? 'minimal' : 'modern',
    spacing: 'default',
    counterSize: 'lg',
    showLabels: true,
    showValues: true,
    gridColumns: {
      desktop: Math.min(stats.length, 4),
      tablet: Math.min(stats.length, 2),
      mobile: 1
    },
    animation: {
      enabled: variation !== 'minimal',
      trigger: 'scroll',
      duration: 2000,
      delay: 0,
      stagger: 200,
      easing: 'ease-out'
    }
  }
}

function generateCTASampleData(
  audienceType: AudienceType,
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<CTAComponentConfig> {
  const buttonText = getCTATextForAudience(audienceType, contentLength)
  
  return {
    type: 'button',
    buttonConfig: {
      text: buttonText,
      url: getCTAUrlForAudience(audienceType),
      style: variation === 'minimal' ? 'outline' : 'primary',
      size: contentLength === 'short' ? 'md' : 'lg',
      icon: variation === 'rich' ? {
        name: 'arrow-right',
        position: 'right'
      } : undefined,
      animation: {
        hover: variation === 'rich' ? 'lift' : 'scale',
        click: 'ripple'
      }
    },
    theme: variation === 'minimal' ? 'minimal' : 'modern',
    colorScheme: 'primary'
  }
}

function generateMediaSampleData(
  variation: 'default' | 'minimal' | 'rich' | 'localized',
  contentLength: 'short' | 'medium' | 'long'
): Partial<MediaComponentConfig> {
  const mediaAssets = mediaSampleData.default[contentLength] as MediaAsset[]
  
  return {
    type: 'image-gallery',
    title: contentLength === 'short' ? 'Gallery' : 'Alumni Moments',
    description: contentLength === 'long' ? 'Celebrating our community through shared experiences and achievements' : 'Our community in action',
    layout: 'grid',
    theme: variation === 'minimal' ? 'minimal' : 'card',
    spacing: 'default',
    gridColumns: {
      desktop: Math.min(mediaAssets.length, 3),
      tablet: Math.min(mediaAssets.length, 2),
      mobile: 1
    },
    mediaAssets,
    lightbox: {
      enabled: variation !== 'minimal',
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
  }
}

// Helper functions
function getBackgroundImageForAudience(audienceType: AudienceType): string {
  const images = {
    individual: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
    institution: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
    employer: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'
  }
  return images[audienceType]
}

function getFormTitleForAudience(audienceType: AudienceType, contentLength: 'short' | 'medium' | 'long'): string {
  const titles = {
    individual: {
      short: 'Join Network',
      medium: 'Join Our Alumni Network',
      long: 'Join Our Professional Alumni Network'
    },
    institution: {
      short: 'Partner With Us',
      medium: 'Become an Institutional Partner',
      long: 'Transform Your Alumni Engagement Strategy'
    },
    employer: {
      short: 'Find Talent',
      medium: 'Access Top Talent',
      long: 'Discover Exceptional Talent Through Alumni Networks'
    }
  }
  return titles[audienceType][contentLength]
}

function getFormDescriptionForAudience(audienceType: AudienceType, contentLength: 'short' | 'medium' | 'long'): string {
  const descriptions = {
    individual: {
      short: 'Connect with fellow graduates',
      medium: 'Connect with fellow graduates and expand your professional network',
      long: 'Connect with fellow graduates, expand your professional network, and unlock new career opportunities through meaningful relationships'
    },
    institution: {
      short: 'Enhance alumni relations',
      medium: 'Enhance your alumni relations with our comprehensive platform',
      long: 'Enhance your alumni relations with our comprehensive platform designed to increase engagement, boost donations, and build thriving communities'
    },
    employer: {
      short: 'Access qualified candidates',
      medium: 'Access qualified candidates from top universities',
      long: 'Access a curated pool of qualified candidates from leading universities and streamline your recruitment process'
    }
  }
  return descriptions[audienceType][contentLength]
}

function getCTATextForAudience(audienceType: AudienceType, contentLength: 'short' | 'medium' | 'long'): string {
  const texts = {
    individual: {
      short: 'Join Now',
      medium: 'Join Network',
      long: 'Join Network Today'
    },
    institution: {
      short: 'Get Demo',
      medium: 'Schedule Demo',
      long: 'Schedule Your Demo'
    },
    employer: {
      short: 'Post Jobs',
      medium: 'Post Job Opening',
      long: 'Post Your Job Opening'
    }
  }
  return texts[audienceType][contentLength]
}

function getCTAUrlForAudience(audienceType: AudienceType): string {
  const urls = {
    individual: '/signup',
    institution: '/demo',
    employer: '/jobs/post'
  }
  return urls[audienceType]
}

export default generateSampleData