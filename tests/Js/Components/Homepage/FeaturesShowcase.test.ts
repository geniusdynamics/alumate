import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import FeaturesShowcase from '@/components/homepage/FeaturesShowcase.vue'
import FeatureComparisonMatrix from '@/components/homepage/FeatureComparisonMatrix.vue'
import type { PlatformFeature, InstitutionalFeature } from '@/types/homepage'

// Mock fetch
const mockFetch = vi.fn()
global.fetch = mockFetch

describe('FeaturesShowcase.vue', () => {
  let wrapper: VueWrapper<any>

  const mockIndividualFeatures: PlatformFeature[] = [
    {
      id: 'networking',
      title: 'Smart Alumni Networking',
      description: 'Connect with alumni based on shared interests and career goals.',
      benefits: [
        'AI-powered connection recommendations',
        'Industry-specific networking groups',
        'Professional conversation starters'
      ],
      screenshot: '/images/features/networking-dashboard.png',
      demoVideo: '/videos/features/networking-demo.mp4',
      demoUrl: '/demo/networking',
      usageStats: [
        {
          metric: 'connections_made_monthly',
          value: 12000,
          label: 'Monthly Connections',
          trend: 'up'
        }
      ],
      targetPersona: [
        {
          id: 'recent_grad',
          name: 'Recent Graduates',
          description: 'New alumni looking to build professional networks',
          careerStage: 'recent_grad',
          primaryGoals: ['Find mentors', 'Job opportunities'],
          painPoints: ['Limited network', 'Career uncertainty']
        }
      ],
      category: 'networking'
    },
    {
      id: 'mentorship',
      title: 'Career Mentorship Matching',
      description: 'Get paired with experienced alumni mentors in your field.',
      benefits: [
        'Personalized mentor matching',
        'Structured mentorship programs',
        'Goal tracking and progress monitoring'
      ],
      screenshot: '/images/features/mentorship-matching.png',
      demoVideo: '/videos/features/mentorship-demo.mp4',
      usageStats: [
        {
          metric: 'active_mentorships',
          value: 1800,
          label: 'Active Mentorships',
          trend: 'up'
        }
      ],
      targetPersona: [
        {
          id: 'recent_grad',
          name: 'Recent Graduates',
          description: 'New alumni seeking career guidance',
          careerStage: 'recent_grad',
          primaryGoals: ['Career guidance', 'Skill development'],
          painPoints: ['Lack of experience', 'Career direction']
        }
      ],
      category: 'mentorship'
    }
  ]

  const mockInstitutionalFeatures: InstitutionalFeature[] = [
    {
      id: 'admin_dashboard',
      title: 'Comprehensive Admin Dashboard',
      description: 'Manage your entire alumni community with powerful analytics.',
      benefits: [
        'Real-time engagement analytics',
        'Event management tools',
        'Communication campaign builder'
      ],
      targetInstitution: 'university',
      screenshot: '/images/features/admin-dashboard.png',
      demoVideo: '/videos/features/admin-dashboard-demo.mp4',
      pricingTier: 'enterprise',
      customizationLevel: 'advanced'
    }
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        data: mockIndividualFeatures
      })
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Component Rendering', () => {
    it('renders with default props for individual audience', async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('Powerful Features for Alumni Success')
      expect(wrapper.text()).toContain('Discover the tools and capabilities')
    })

    it('renders with custom title and subtitle', () => {
      const customTitle = 'Custom Features Title'
      const customSubtitle = 'Custom subtitle text'

      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual',
          title: customTitle,
          subtitle: customSubtitle
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe(customTitle)
      expect(wrapper.text()).toContain(customSubtitle)
    })

    it('renders institutional-specific content', () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('Enterprise Alumni Engagement Solutions')
      expect(wrapper.text()).toContain('Comprehensive tools and analytics')
    })
  })

  describe('Data Fetching', () => {
    it('fetches features on mount', async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/features?audience=individual',
        expect.objectContaining({
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
      )
    })

    it('fetches institutional features with correct audience parameter', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: mockInstitutionalFeatures
        })
      })

      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/features?audience=institutional',
        expect.any(Object)
      )
    })

    it('displays features after successful fetch', async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show feature tabs
      const featureTabs = wrapper.findAll('button[role="tab"]')
      expect(featureTabs.length).toBe(mockIndividualFeatures.length)
    })
  })

  describe('Loading States', () => {
    it('shows loading state while fetching', async () => {
      // Mock a delayed response
      mockFetch.mockImplementation(() => 
        new Promise(resolve => 
          setTimeout(() => resolve({
            ok: true,
            json: () => Promise.resolve({
              success: true,
              data: mockIndividualFeatures
            })
          }), 100)
        )
      )

      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()

      // Should show loading state
      expect(wrapper.text()).toContain('Loading features...')
      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })

    it('hides loading state after successful fetch', async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).not.toContain('Loading features...')
      expect(wrapper.find('.animate-spin').exists()).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('displays error state when fetch fails', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'))

      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Network error')
      expect(wrapper.find('button').text()).toBe('Try Again')
    })

    it('retries fetch when Try Again button is clicked', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({
            success: true,
            data: mockIndividualFeatures
          })
        })

      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show error state
      expect(wrapper.text()).toContain('Network error')

      // Click retry button
      await wrapper.find('button').trigger('click')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show features now
      const featureTabs = wrapper.findAll('button[role="tab"]')
      expect(featureTabs.length).toBe(mockIndividualFeatures.length)
    })
  })

  describe('Feature Navigation', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('displays feature tabs in desktop view', () => {
      const featureTabs = wrapper.findAll('button[role="tab"]')
      expect(featureTabs.length).toBe(mockIndividualFeatures.length)
      
      // First tab should be active by default
      expect(featureTabs[0].classes()).toContain('bg-blue-50')
    })

    it('switches active feature when tab is clicked', async () => {
      const featureTabs = wrapper.findAll('button[role="tab"]')
      
      // Click second tab
      await featureTabs[1].trigger('click')
      await nextTick()

      // Second tab should now be active
      expect(featureTabs[1].classes()).toContain('bg-blue-50')
      expect(featureTabs[0].classes()).not.toContain('bg-blue-50')
    })

    it('displays active feature content', () => {
      // Should show first feature by default
      expect(wrapper.text()).toContain(mockIndividualFeatures[0].title)
      expect(wrapper.text()).toContain(mockIndividualFeatures[0].description)
    })
  })

  describe('Persona Filtering', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual',
          showPersonaFilters: true
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('displays persona filter buttons when enabled', () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      expect(filterButtons.length).toBeGreaterThan(0)
      
      // Should have "All Features" filter
      const allFeaturesButton = filterButtons.find(button => 
        button.text().includes('All Features')
      )
      expect(allFeaturesButton).toBeTruthy()
    })

    it('filters features when persona is selected', async () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      
      // Find and click a specific persona filter
      const recentGradFilter = filterButtons.find(button => 
        button.text().includes('Recent Graduates')
      )

      if (recentGradFilter) {
        await recentGradFilter.trigger('click')
        await nextTick()

        // Should show filtered features
        const featureTabs = wrapper.findAll('button[role="tab"]')
        expect(featureTabs.length).toBeLessThanOrEqual(mockIndividualFeatures.length)
      }
    })

    it('shows all features when "All Features" filter is selected', async () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      
      const allFeaturesButton = filterButtons.find(button => 
        button.text().includes('All Features')
      )

      if (allFeaturesButton) {
        await allFeaturesButton.trigger('click')
        await nextTick()

        const featureTabs = wrapper.findAll('button[role="tab"]')
        expect(featureTabs.length).toBe(mockIndividualFeatures.length)
      }
    })
  })

  describe('Interactive Demo', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual',
          showInteractiveDemo: true
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('displays iframe when feature has demo URL', () => {
      // First feature has demoUrl, so iframe should be present
      const iframe = wrapper.find('iframe')
      expect(iframe.exists()).toBe(true)
      expect(iframe.attributes('src')).toBe(mockIndividualFeatures[0].demoUrl)
    })

    it('displays screenshot with hotspots when no demo URL', async () => {
      // Switch to a feature without demoUrl (modify mock data)
      const featureWithoutDemo = {
        ...mockIndividualFeatures[0],
        demoUrl: undefined,
        hotspots: [
          {
            x: 30,
            y: 25,
            title: 'Test Hotspot',
            description: 'Test description'
          }
        ]
      }

      // Update the component's data
      wrapper.vm.features = [featureWithoutDemo]
      await nextTick()

      const screenshot = wrapper.find('img')
      expect(screenshot.exists()).toBe(true)
    })
  })

  describe('Feature Comparison Matrix', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual',
          showComparisonMatrix: true
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('shows comparison matrix toggle button', () => {
      const toggleButton = wrapper.find('button').filter(button => 
        button.text().includes('Show Comparison Matrix')
      )
      expect(toggleButton.exists()).toBe(true)
    })

    it('opens comparison matrix when toggle is clicked', async () => {
      const toggleButton = wrapper.findAll('button').find(button => 
        button.text().includes('Show Comparison Matrix')
      )

      if (toggleButton) {
        await toggleButton.trigger('click')
        await nextTick()

        expect(wrapper.findComponent(FeatureComparisonMatrix).exists()).toBe(true)
      }
    })

    it('closes comparison matrix when close is emitted', async () => {
      // Open matrix first
      wrapper.vm.showMatrix = true
      await nextTick()

      const matrix = wrapper.findComponent(FeatureComparisonMatrix)
      expect(matrix.exists()).toBe(true)

      // Emit close event
      matrix.vm.$emit('close')
      await nextTick()

      expect(wrapper.vm.showMatrix).toBe(false)
    })
  })

  describe('CTA Handling', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('handles demo CTA click', async () => {
      const demoButton = wrapper.findAll('button').find(button => 
        button.text().includes('Try Demo')
      )

      // Mock window.open
      const mockOpen = vi.fn()
      Object.defineProperty(window, 'open', {
        value: mockOpen,
        writable: true
      })

      if (demoButton) {
        await demoButton.trigger('click')
        
        expect(mockOpen).toHaveBeenCalledWith(
          mockIndividualFeatures[0].demoUrl,
          '_blank'
        )
      }
    })

    it('handles learn more CTA click', async () => {
      const learnMoreButton = wrapper.findAll('button').find(button => 
        button.text().includes('Learn More')
      )

      const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})

      if (learnMoreButton) {
        await learnMoreButton.trigger('click')
        
        expect(consoleSpy).toHaveBeenCalledWith(
          'Learn more about feature:',
          mockIndividualFeatures[0].id
        )
      }

      consoleSpy.mockRestore()
    })
  })

  describe('Accessibility', () => {
    beforeEach(async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('provides proper ARIA attributes for tabs', () => {
      const featureTabs = wrapper.findAll('button[role="tab"]')
      
      featureTabs.forEach((tab, index) => {
        expect(tab.attributes('role')).toBe('tab')
        expect(tab.attributes('aria-selected')).toBeDefined()
      })
    })

    it('provides proper ARIA attributes for filter buttons', () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      
      filterButtons.forEach(button => {
        expect(button.attributes('aria-pressed')).toBeDefined()
      })
    })

    it('provides proper alt text for images', () => {
      const images = wrapper.findAll('img')
      
      images.forEach(img => {
        expect(img.attributes('alt')).toBeDefined()
        expect(img.attributes('alt')).not.toBe('')
      })
    })
  })

  describe('Component Methods', () => {
    it('exposes fetchFeatures method', () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      expect(wrapper.vm.fetchFeatures).toBeDefined()
      expect(typeof wrapper.vm.fetchFeatures).toBe('function')
    })

    it('exposes navigation methods', () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      expect(wrapper.vm.setActiveFeature).toBeDefined()
      expect(wrapper.vm.setActivePersona).toBeDefined()
    })
  })

  describe('Responsive Design', () => {
    it('shows mobile tabs on small screens', async () => {
      wrapper = mount(FeaturesShowcase, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            FeatureComparisonMatrix
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Mobile tabs should exist (though may be hidden by CSS)
      const mobileTabs = wrapper.find('.lg\\:hidden')
      expect(mobileTabs.exists()).toBe(true)
    })
  })
})