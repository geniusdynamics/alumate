import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import BrandedAppsShowcase from '@/components/homepage/BrandedAppsShowcase.vue'
import type { BrandedAppsShowcaseProps, BrandedApp } from '@/types/homepage'

// Mock data
const mockBrandedApp: BrandedApp = {
  id: 'stanford-alumni',
  institutionName: 'Stanford University',
  institutionType: 'university',
  logo: '/images/institutions/stanford-logo.png',
  appIcon: '/images/apps/stanford-app-icon.png',
  appStoreUrl: 'https://apps.apple.com/us/app/stanford-alumni/id123456789',
  playStoreUrl: 'https://play.google.com/store/apps/details?id=edu.stanford.alumni',
  screenshots: [
    {
      id: 'stanford-home',
      url: '/images/apps/stanford/home-screen.png',
      title: 'Home Dashboard',
      description: 'Personalized alumni dashboard with news and updates',
      device: 'iphone',
      category: 'home'
    },
    {
      id: 'stanford-network',
      url: '/images/apps/stanford/networking-screen.png',
      title: 'Alumni Network',
      description: 'Connect with fellow Stanford alumni worldwide',
      device: 'iphone',
      category: 'networking'
    }
  ],
  customizations: [
    {
      category: 'branding',
      name: 'Custom Color Scheme',
      description: 'Stanford Cardinal red throughout the app',
      implemented: true,
      complexity: 'basic'
    }
  ],
  userCount: 15000,
  engagementStats: [
    {
      metric: 'daily_active_users',
      value: 2500,
      unit: 'count',
      trend: 'up',
      period: 'last_30_days'
    },
    {
      metric: 'session_duration',
      value: 12,
      unit: 'minutes',
      trend: 'up',
      period: 'average'
    }
  ],
  launchDate: new Date('2023-09-01'),
  featured: true
}

const mockCustomizationOptions = [
  {
    id: 'branding-options',
    category: 'branding' as const,
    name: 'Visual Branding',
    description: 'Complete visual customization with your institution\'s brand identity',
    options: [
      {
        id: 'logo-placement',
        name: 'Custom Logo Placement',
        description: 'Your logo prominently displayed throughout the app',
        type: 'logo' as const,
        required: true
      }
    ],
    examples: [],
    level: 'basic' as const
  }
]

const mockDevelopmentTimeline = {
  phases: [
    {
      id: 'discovery-planning',
      name: 'Discovery & Planning',
      description: 'Requirements gathering, brand analysis, and technical planning',
      duration: '2-3 weeks',
      deliverables: [
        'Technical requirements document',
        'Brand integration guidelines'
      ],
      dependencies: [],
      milestones: [
        {
          id: 'requirements-complete',
          name: 'Requirements Finalized',
          description: 'All technical and brand requirements documented',
          dueDate: 'Week 2',
          status: 'pending' as const
        }
      ]
    }
  ],
  totalDuration: '14-20 weeks',
  estimatedCost: 'Starting at $75,000',
  maintenanceCost: '$2,000-5,000/month'
}

const mockAppStoreIntegration = {
  appleAppStore: true,
  googlePlayStore: true,
  customDomain: true,
  whiteLabel: true,
  institutionBranding: true,
  reviewManagement: true,
  analyticsIntegration: true
}

const defaultProps: BrandedAppsShowcaseProps = {
  featuredApps: [mockBrandedApp],
  customizationOptions: mockCustomizationOptions,
  appStoreIntegration: mockAppStoreIntegration,
  developmentTimeline: mockDevelopmentTimeline,
  audience: 'institutional'
}

describe('BrandedAppsShowcase', () => {
  let wrapper: VueWrapper<any>

  beforeEach(() => {
    wrapper = mount(BrandedAppsShowcase, {
      props: defaultProps
    })
  })

  describe('Component Structure', () => {
    it('renders the component with correct test id', () => {
      expect(wrapper.find('[data-testid="branded-apps-showcase"]').exists()).toBe(true)
    })

    it('displays the main heading', () => {
      const heading = wrapper.find('h2')
      expect(heading.text()).toBe('Custom Branded Mobile Apps')
    })

    it('displays the subtitle description', () => {
      const subtitle = wrapper.find('p')
      expect(subtitle.text()).toContain('Engage your alumni community with fully customized mobile applications')
    })
  })

  describe('Featured Apps Section', () => {
    it('renders featured apps grid', () => {
      const appCards = wrapper.findAll('[data-testid="branded-app-card"]')
      expect(appCards).toHaveLength(1)
    })

    it('displays app institution information', () => {
      const appCard = wrapper.find('[data-testid="branded-app-card"]')
      expect(appCard.text()).toContain('Stanford University')
      expect(appCard.text()).toContain('university')
    })

    it('shows app store links when available', () => {
      const appStoreLink = wrapper.find('a[href*="apps.apple.com"]')
      const playStoreLink = wrapper.find('a[href*="play.google.com"]')
      
      expect(appStoreLink.exists()).toBe(true)
      expect(playStoreLink.exists()).toBe(true)
      expect(appStoreLink.text()).toContain('App Store')
      expect(playStoreLink.text()).toContain('Google Play')
    })

    it('displays app screenshots', () => {
      const screenshots = wrapper.findAll('img[alt*="Home Dashboard"], img[alt*="Alumni Network"]')
      expect(screenshots.length).toBeGreaterThan(0)
    })

    it('shows engagement statistics', () => {
      const appCard = wrapper.find('[data-testid="branded-app-card"]')
      expect(appCard.text()).toContain('2.5K') // formatted daily active users
      expect(appCard.text()).toContain('12m') // formatted session duration
    })

    it('displays user count', () => {
      const appCard = wrapper.find('[data-testid="branded-app-card"]')
      expect(appCard.text()).toContain('15.0K active alumni users')
    })
  })

  describe('Screenshot Modal', () => {
    it('opens screenshot modal when screenshot is clicked', async () => {
      const screenshot = wrapper.find('img[alt="Home Dashboard"]').closest('.cursor-pointer')
      await screenshot?.trigger('click')
      
      const modal = wrapper.find('[data-testid="screenshot-modal"]')
      expect(modal.exists()).toBe(true)
    })

    it('closes modal when close button is clicked', async () => {
      // Open modal first
      const screenshot = wrapper.find('img[alt="Home Dashboard"]').closest('.cursor-pointer')
      await screenshot?.trigger('click')
      
      // Close modal
      const closeButton = wrapper.find('[data-testid="screenshot-modal"] button')
      await closeButton.trigger('click')
      
      const modal = wrapper.find('[data-testid="screenshot-modal"]')
      expect(modal.exists()).toBe(false)
    })

    it('emits screenshotView event when screenshot is clicked', async () => {
      const screenshot = wrapper.find('img[alt="Home Dashboard"]').closest('.cursor-pointer')
      await screenshot?.trigger('click')
      
      expect(wrapper.emitted('screenshotView')).toBeTruthy()
      expect(wrapper.emitted('screenshotView')?.[0]).toEqual(['stanford-alumni', 'stanford-home'])
    })
  })

  describe('Customization Options Section', () => {
    it('renders customization options', () => {
      const customizationCards = wrapper.findAll('[data-testid="customization-option"]')
      expect(customizationCards).toHaveLength(1)
    })

    it('displays customization category information', () => {
      const customizationCard = wrapper.find('[data-testid="customization-option"]')
      expect(customizationCard.text()).toContain('branding')
      expect(customizationCard.text()).toContain('Visual Branding')
      expect(customizationCard.text()).toContain('Complete visual customization')
    })

    it('shows customization options list', () => {
      const customizationCard = wrapper.find('[data-testid="customization-option"]')
      expect(customizationCard.text()).toContain('Custom Logo Placement')
    })
  })

  describe('App Store Integration Section', () => {
    it('displays app store integration features', () => {
      const integrationSection = wrapper.find('.integration-feature')
      expect(integrationSection.exists()).toBe(true)
    })

    it('shows Apple App Store integration', () => {
      expect(wrapper.text()).toContain('Apple App Store')
      expect(wrapper.text()).toContain('Full App Store optimization')
    })

    it('shows Google Play Store integration', () => {
      expect(wrapper.text()).toContain('Google Play Store')
      expect(wrapper.text()).toContain('Complete Google Play integration')
    })

    it('shows white label solution', () => {
      expect(wrapper.text()).toContain('White Label Solution')
      expect(wrapper.text()).toContain('Complete white-label branding')
    })
  })

  describe('Development Timeline Section', () => {
    it('displays timeline overview information', () => {
      expect(wrapper.text()).toContain('Development Timeline & Process')
      expect(wrapper.text()).toContain('14-20 weeks')
      expect(wrapper.text()).toContain('Starting at $75,000')
      expect(wrapper.text()).toContain('$2,000-5,000/month')
    })

    it('renders development phases', () => {
      const phases = wrapper.findAll('[data-testid="development-phase"]')
      expect(phases).toHaveLength(1)
    })

    it('displays phase information', () => {
      const phase = wrapper.find('[data-testid="development-phase"]')
      expect(phase.text()).toContain('Discovery & Planning')
      expect(phase.text()).toContain('2-3 weeks')
      expect(phase.text()).toContain('Requirements gathering')
    })

    it('shows phase deliverables', () => {
      const phase = wrapper.find('[data-testid="development-phase"]')
      expect(phase.text()).toContain('Technical requirements document')
      expect(phase.text()).toContain('Brand integration guidelines')
    })

    it('displays phase milestones', () => {
      const phase = wrapper.find('[data-testid="development-phase"]')
      expect(phase.text()).toContain('Requirements Finalized')
    })
  })

  describe('CTA Section', () => {
    it('displays CTA section with proper messaging', () => {
      expect(wrapper.text()).toContain('Ready to Launch Your Branded App?')
      expect(wrapper.text()).toContain('Join leading institutions')
    })

    it('shows demo request button', () => {
      const demoButton = wrapper.find('[data-testid="demo-request-button"]')
      expect(demoButton.exists()).toBe(true)
      expect(demoButton.text()).toBe('Request Demo')
    })

    it('shows case studies download button', () => {
      const caseStudiesButton = wrapper.find('[data-testid="case-studies-button"]')
      expect(caseStudiesButton.exists()).toBe(true)
      expect(caseStudiesButton.text()).toBe('Download Case Studies')
    })

    it('emits demoRequest event when demo button is clicked', async () => {
      const demoButton = wrapper.find('[data-testid="demo-request-button"]')
      await demoButton.trigger('click')
      
      expect(wrapper.emitted('demoRequest')).toBeTruthy()
    })

    it('emits caseStudiesDownload event when case studies button is clicked', async () => {
      const caseStudiesButton = wrapper.find('[data-testid="case-studies-button"]')
      await caseStudiesButton.trigger('click')
      
      expect(wrapper.emitted('caseStudiesDownload')).toBeTruthy()
    })
  })

  describe('Event Emissions', () => {
    it('emits appStoreClick event when app store link is clicked', async () => {
      const appStoreLink = wrapper.find('a[href*="apps.apple.com"]')
      await appStoreLink.trigger('click')
      
      expect(wrapper.emitted('appStoreClick')).toBeTruthy()
      expect(wrapper.emitted('appStoreClick')?.[0]).toEqual(['stanford-alumni', 'ios'])
    })

    it('emits appStoreClick event when play store link is clicked', async () => {
      const playStoreLink = wrapper.find('a[href*="play.google.com"]')
      await playStoreLink.trigger('click')
      
      expect(wrapper.emitted('appStoreClick')).toBeTruthy()
      expect(wrapper.emitted('appStoreClick')?.[0]).toEqual(['stanford-alumni', 'android'])
    })
  })

  describe('Data Formatting', () => {
    it('formats metric values correctly', () => {
      // Test the formatMetricValue method indirectly through rendered content
      expect(wrapper.text()).toContain('2.5K') // 2500 formatted as count
      expect(wrapper.text()).toContain('12m') // 12 formatted as minutes
    })

    it('formats large numbers correctly', () => {
      // Test the formatNumber method indirectly through rendered content
      expect(wrapper.text()).toContain('15.0K') // 15000 formatted
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive CSS classes', () => {
      const gridElements = wrapper.findAll('.grid')
      expect(gridElements.length).toBeGreaterThan(0)
      
      // Check for responsive grid classes
      const hasResponsiveClasses = gridElements.some(el => 
        el.classes().some(cls => cls.includes('md:') || cls.includes('lg:'))
      )
      expect(hasResponsiveClasses).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('has proper alt text for images', () => {
      const images = wrapper.findAll('img')
      images.forEach(img => {
        expect(img.attributes('alt')).toBeDefined()
        expect(img.attributes('alt')).not.toBe('')
      })
    })

    it('has proper link attributes for external links', () => {
      const externalLinks = wrapper.findAll('a[href^="http"]')
      externalLinks.forEach(link => {
        expect(link.attributes('target')).toBe('_blank')
        expect(link.attributes('rel')).toBe('noopener noreferrer')
      })
    })

    it('has proper button roles and labels', () => {
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.text().trim()).not.toBe('')
      })
    })
  })

  describe('Error Handling', () => {
    it('handles missing app store URLs gracefully', async () => {
      const propsWithoutUrls = {
        ...defaultProps,
        featuredApps: [{
          ...mockBrandedApp,
          appStoreUrl: undefined,
          playStoreUrl: undefined
        }]
      }
      
      const wrapperWithoutUrls = mount(BrandedAppsShowcase, {
        props: propsWithoutUrls
      })
      
      expect(wrapperWithoutUrls.find('a[href*="apps.apple.com"]').exists()).toBe(false)
      expect(wrapperWithoutUrls.find('a[href*="play.google.com"]').exists()).toBe(false)
    })

    it('handles empty screenshots array', async () => {
      const propsWithoutScreenshots = {
        ...defaultProps,
        featuredApps: [{
          ...mockBrandedApp,
          screenshots: []
        }]
      }
      
      const wrapperWithoutScreenshots = mount(BrandedAppsShowcase, {
        props: propsWithoutScreenshots
      })
      
      const screenshotGrid = wrapperWithoutScreenshots.find('.grid.grid-cols-3.gap-2')
      expect(screenshotGrid.findAll('img')).toHaveLength(0)
    })

    it('handles empty customization options', async () => {
      const propsWithoutCustomizations = {
        ...defaultProps,
        customizationOptions: []
      }
      
      const wrapperWithoutCustomizations = mount(BrandedAppsShowcase, {
        props: propsWithoutCustomizations
      })
      
      const customizationCards = wrapperWithoutCustomizations.findAll('[data-testid="customization-option"]')
      expect(customizationCards).toHaveLength(0)
    })
  })

  describe('Performance', () => {
    it('limits displayed screenshots to 3 per app', () => {
      const appWithManyScreenshots = {
        ...mockBrandedApp,
        screenshots: [
          ...mockBrandedApp.screenshots,
          {
            id: 'extra-1',
            url: '/images/apps/stanford/extra1.png',
            title: 'Extra 1',
            description: 'Extra screenshot 1',
            device: 'iphone' as const,
            category: 'home' as const
          },
          {
            id: 'extra-2',
            url: '/images/apps/stanford/extra2.png',
            title: 'Extra 2',
            description: 'Extra screenshot 2',
            device: 'iphone' as const,
            category: 'home' as const
          }
        ]
      }
      
      const propsWithManyScreenshots = {
        ...defaultProps,
        featuredApps: [appWithManyScreenshots]
      }
      
      const wrapperWithManyScreenshots = mount(BrandedAppsShowcase, {
        props: propsWithManyScreenshots
      })
      
      const screenshotImages = wrapperWithManyScreenshots.find('.grid.grid-cols-3.gap-2').findAll('img')
      expect(screenshotImages).toHaveLength(3)
    })

    it('limits displayed engagement stats to 2 per app', () => {
      const appWithManyStats = {
        ...mockBrandedApp,
        engagementStats: [
          ...mockBrandedApp.engagementStats,
          {
            metric: 'retention_rate' as const,
            value: 85,
            unit: 'percentage' as const,
            trend: 'up' as const,
            period: '30_day'
          }
        ]
      }
      
      const propsWithManyStats = {
        ...defaultProps,
        featuredApps: [appWithManyStats]
      }
      
      const wrapperWithManyStats = mount(BrandedAppsShowcase, {
        props: propsWithManyStats
      })
      
      const statCards = wrapperWithManyStats.find('.grid.grid-cols-2.gap-4').findAll('.text-center.p-3')
      expect(statCards).toHaveLength(2)
    })
  })
})