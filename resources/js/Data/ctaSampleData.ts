import type { 
  CTAComponentConfig, 
  CTAButtonConfig, 
  CTABannerConfig, 
  CTAInlineLinkConfig,
  CTAABTestVariant,
  AudienceType 
} from '@/types/components'

// Sample CTA Button Configurations
export const sampleCTAButtons: Record<string, CTAButtonConfig> = {
  primarySignup: {
    text: 'Join Our Network',
    url: '/signup',
    style: 'primary',
    size: 'lg',
    icon: {
      name: 'arrow-right',
      position: 'right',
      size: 'md'
    },
    trackingParams: {
      utm_source: 'homepage',
      utm_medium: 'cta_button',
      utm_campaign: 'signup_drive',
      utm_content: 'primary_cta'
    },
    conversionEvents: [{
      eventName: 'signup_initiated',
      category: 'conversion',
      action: 'click',
      label: 'primary_signup_button'
    }],
    animation: {
      hover: 'lift',
      click: 'ripple',
      loading: 'spinner'
    },
    accessibility: {
      ariaLabel: 'Join our alumni network - opens signup form'
    }
  },

  secondaryDemo: {
    text: 'Request Demo',
    url: '/demo',
    style: 'outline',
    size: 'md',
    icon: {
      name: 'play',
      position: 'left',
      size: 'sm'
    },
    trackingParams: {
      utm_source: 'homepage',
      utm_medium: 'cta_button',
      utm_campaign: 'demo_requests',
      utm_content: 'secondary_cta'
    },
    conversionEvents: [{
      eventName: 'demo_requested',
      category: 'lead_generation',
      action: 'click',
      label: 'demo_request_button'
    }],
    animation: {
      hover: 'scale',
      click: 'bounce'
    }
  },

  ghostLearnMore: {
    text: 'Learn More',
    url: '/about',
    style: 'ghost',
    size: 'md',
    trackingParams: {
      utm_source: 'homepage',
      utm_medium: 'cta_button',
      utm_campaign: 'information',
      utm_content: 'learn_more'
    }
  },

  downloadApp: {
    text: 'Download App',
    url: '/app/download',
    style: 'primary',
    size: 'lg',
    icon: {
      name: 'download',
      position: 'left',
      size: 'md'
    },
    fullWidth: true,
    trackingParams: {
      utm_source: 'mobile_banner',
      utm_medium: 'cta_button',
      utm_campaign: 'app_downloads'
    },
    conversionEvents: [{
      eventName: 'app_download_started',
      category: 'app_engagement',
      action: 'download',
      label: 'mobile_app'
    }]
  }
}

// Sample CTA Banner Configurations
export const sampleCTABanners: Record<string, CTABannerConfig> = {
  heroSignup: {
    title: 'Connect with 50,000+ Alumni Worldwide',
    subtitle: 'Your next career opportunity is just one connection away',
    description: 'Join the largest professional alumni network and unlock exclusive job opportunities, mentorship programs, and industry insights.',
    layout: 'center-aligned',
    height: 'large',
    textAlignment: 'center',
    contentPosition: 'center',
    backgroundColor: '#1e40af',
    textColor: '#ffffff',
    primaryCTA: sampleCTAButtons.primarySignup,
    secondaryCTA: sampleCTAButtons.secondaryDemo,
    overlay: {
      enabled: true,
      color: '#000000',
      opacity: 0.4
    },
    trackingParams: {
      utm_source: 'homepage',
      utm_medium: 'hero_banner',
      utm_campaign: 'signup_drive'
    },
    accessibility: {
      ariaLabel: 'Main call-to-action banner for alumni network signup'
    }
  },

  institutionPartnership: {
    title: 'Partner with Leading Universities',
    subtitle: 'Enhance your alumni engagement strategy',
    description: 'Connect your graduates with career opportunities and build stronger alumni relationships through our comprehensive platform.',
    layout: 'left-aligned',
    height: 'medium',
    textAlignment: 'left',
    backgroundColor: '#059669',
    textColor: '#ffffff',
    primaryCTA: {
      text: 'Schedule Consultation',
      url: '/institutions/consultation',
      style: 'primary',
      size: 'lg',
      trackingParams: {
        utm_source: 'institutions_page',
        utm_medium: 'cta_banner',
        utm_campaign: 'consultation_requests'
      }
    },
    secondaryCTA: {
      text: 'View Case Studies',
      url: '/institutions/case-studies',
      style: 'outline',
      size: 'md',
      trackingParams: {
        utm_source: 'institutions_page',
        utm_medium: 'cta_banner',
        utm_campaign: 'case_study_views'
      }
    }
  },

  employerRecruitment: {
    title: 'Find Top Talent from Elite Universities',
    subtitle: 'Access pre-qualified candidates',
    description: 'Connect with motivated alumni from top institutions who are actively seeking new opportunities in your industry.',
    layout: 'right-aligned',
    height: 'medium',
    textAlignment: 'right',
    backgroundColor: '#7c3aed',
    textColor: '#ffffff',
    primaryCTA: {
      text: 'Post a Job',
      url: '/employers/post-job',
      style: 'primary',
      size: 'lg',
      icon: {
        name: 'plus',
        position: 'left'
      },
      trackingParams: {
        utm_source: 'employers_page',
        utm_medium: 'cta_banner',
        utm_campaign: 'job_postings'
      }
    },
    mobileLayout: 'stacked'
  }
}

// Sample CTA Inline Link Configurations
export const sampleCTAInlineLinks: Record<string, CTAInlineLinkConfig> = {
  learnMore: {
    text: 'Learn more about our platform',
    url: '/about',
    style: 'arrow',
    size: 'base',
    weight: 'medium',
    animation: {
      hover: 'underline',
      transition: 'normal'
    },
    trackingParams: {
      utm_source: 'content',
      utm_medium: 'inline_link',
      utm_campaign: 'information'
    }
  },

  externalResource: {
    text: 'View our latest research report',
    url: 'https://research.example.com/alumni-trends-2024',
    style: 'external',
    size: 'base',
    openInNewTab: true,
    trackingParams: {
      utm_source: 'blog',
      utm_medium: 'inline_link',
      utm_campaign: 'research_report'
    }
  },

  downloadGuide: {
    text: 'Download Career Guide',
    url: '/downloads/career-guide.pdf',
    style: 'button-like',
    size: 'sm',
    downloadAttribute: 'career-guide.pdf',
    icon: {
      name: 'download',
      position: 'left'
    },
    trackingParams: {
      utm_source: 'resources',
      utm_medium: 'inline_link',
      utm_campaign: 'guide_downloads'
    }
  },

  contactSupport: {
    text: 'Contact our support team',
    url: '/support',
    style: 'underline',
    size: 'sm',
    color: '#6b7280',
    trackingParams: {
      utm_source: 'help_section',
      utm_medium: 'inline_link',
      utm_campaign: 'support_requests'
    }
  }
}

// Sample A/B Test Variants
export const sampleABTestVariants: Record<string, CTAABTestVariant[]> = {
  signupButtonTest: [
    {
      id: 'control',
      name: 'Original Button',
      weight: 50,
      config: {
        buttonConfig: {
          ...sampleCTAButtons.primarySignup,
          text: 'Join Our Network'
        }
      }
    },
    {
      id: 'variant_a',
      name: 'Action-Focused',
      weight: 50,
      config: {
        buttonConfig: {
          ...sampleCTAButtons.primarySignup,
          text: 'Start Networking Today',
          style: 'primary' as const,
          animation: {
            hover: 'glow' as const,
            click: 'ripple' as const
          }
        }
      }
    }
  ],

  bannerHeadlineTest: [
    {
      id: 'control',
      name: 'Network Focus',
      weight: 33,
      config: {
        bannerConfig: {
          ...sampleCTABanners.heroSignup,
          title: 'Connect with 50,000+ Alumni Worldwide'
        }
      }
    },
    {
      id: 'variant_a',
      name: 'Career Focus',
      weight: 33,
      config: {
        bannerConfig: {
          ...sampleCTABanners.heroSignup,
          title: 'Accelerate Your Career with Alumni Connections'
        }
      }
    },
    {
      id: 'variant_b',
      name: 'Opportunity Focus',
      weight: 34,
      config: {
        bannerConfig: {
          ...sampleCTABanners.heroSignup,
          title: 'Unlock Hidden Job Opportunities'
        }
      }
    }
  ]
}

// Complete CTA Component Configurations
export const sampleCTAComponents: Record<AudienceType, CTAComponentConfig[]> = {
  individual: [
    {
      type: 'button',
      buttonConfig: sampleCTAButtons.primarySignup,
      theme: 'modern',
      colorScheme: 'primary',
      trackingEnabled: true,
      conversionGoal: 'signup',
      abTest: {
        enabled: true,
        testId: 'signup_button_test',
        variants: sampleABTestVariants.signupButtonTest
      },
      context: {
        pageType: 'homepage',
        section: 'hero',
        position: 1,
        audienceType: 'individual'
      }
    },
    {
      type: 'banner',
      bannerConfig: sampleCTABanners.heroSignup,
      theme: 'modern',
      colorScheme: 'primary',
      trackingEnabled: true,
      conversionGoal: 'signup',
      context: {
        pageType: 'homepage',
        section: 'hero',
        audienceType: 'individual'
      }
    },
    {
      type: 'inline-link',
      inlineLinkConfig: sampleCTAInlineLinks.learnMore,
      theme: 'minimal',
      trackingEnabled: true,
      context: {
        pageType: 'about',
        section: 'content',
        audienceType: 'individual'
      }
    }
  ],

  institution: [
    {
      type: 'banner',
      bannerConfig: sampleCTABanners.institutionPartnership,
      theme: 'professional',
      colorScheme: 'secondary',
      trackingEnabled: true,
      conversionGoal: 'consultation',
      context: {
        pageType: 'institutions',
        section: 'hero',
        audienceType: 'institution'
      }
    },
    {
      type: 'button',
      buttonConfig: {
        text: 'Schedule Demo',
        url: '/institutions/demo',
        style: 'primary',
        size: 'lg',
        trackingParams: {
          utm_source: 'institutions_page',
          utm_medium: 'cta_button',
          utm_campaign: 'demo_requests'
        }
      },
      theme: 'professional',
      colorScheme: 'secondary',
      trackingEnabled: true,
      context: {
        audienceType: 'institution'
      }
    }
  ],

  employer: [
    {
      type: 'banner',
      bannerConfig: sampleCTABanners.employerRecruitment,
      theme: 'modern',
      colorScheme: 'accent',
      trackingEnabled: true,
      conversionGoal: 'job_posting',
      context: {
        pageType: 'employers',
        section: 'hero',
        audienceType: 'employer'
      }
    },
    {
      type: 'button',
      buttonConfig: {
        text: 'Browse Candidates',
        url: '/employers/candidates',
        style: 'outline',
        size: 'md',
        icon: {
          name: 'search',
          position: 'left'
        },
        trackingParams: {
          utm_source: 'employers_page',
          utm_medium: 'cta_button',
          utm_campaign: 'candidate_browsing'
        }
      },
      theme: 'modern',
      colorScheme: 'accent',
      trackingEnabled: true,
      context: {
        audienceType: 'employer'
      }
    }
  ]
}

// Utility function to get sample data by audience type
export function getCTASampleData(audienceType: AudienceType): CTAComponentConfig[] {
  return sampleCTAComponents[audienceType] || sampleCTAComponents.individual
}

// Utility function to get sample CTA by type
export function getSampleCTAByType(type: 'button' | 'banner' | 'inline-link'): CTAComponentConfig {
  const allSamples = Object.values(sampleCTAComponents).flat()
  return allSamples.find(cta => cta.type === type) || allSamples[0]
}