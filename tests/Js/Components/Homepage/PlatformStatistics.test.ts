import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import PlatformStatistics from '@/components/homepage/PlatformStatistics.vue'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import type { PlatformStatistic } from '@/types/homepage'

// Mock fetch
const mockFetch = vi.fn()
global.fetch = mockFetch

// Mock intersection observer
const mockIntersectionObserver = vi.fn()
mockIntersectionObserver.mockReturnValue({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
})
global.IntersectionObserver = mockIntersectionObserver

// Mock @vueuse/core
vi.mock('@vueuse/core', () => ({
  useIntersectionObserver: vi.fn((target, callback) => {
    // Simulate intersection immediately for testing
    setTimeout(() => {
      callback([{ isIntersecting: true }])
    }, 0)
    return { stop: vi.fn() }
  })
}))

describe('PlatformStatistics.vue', () => {
  let wrapper: VueWrapper<any>

  const mockStatistics: PlatformStatistic[] = [
    {
      key: 'total_alumni',
      value: 25000,
      label: 'Alumni Connected',
      icon: 'users',
      animateOnScroll: true,
      format: 'number',
      suffix: '+'
    },
    {
      key: 'job_placements',
      value: 3200,
      label: 'Job Placements',
      icon: 'briefcase',
      animateOnScroll: true,
      format: 'number',
      suffix: '+'
    },
    {
      key: 'average_salary_increase',
      value: 42,
      label: 'Average Salary Increase',
      icon: 'trending-up',
      animateOnScroll: true,
      format: 'percentage'
    }
  ]

  const mockApiResponse = {
    success: true,
    data: {
      statistics: mockStatistics,
      last_updated: '2024-01-15T10:30:00Z'
    }
  }

  beforeEach(() => {
    vi.clearAllMocks()
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve(mockApiResponse)
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Component Rendering', () => {
    it('renders with default props', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('Trusted by Alumni Worldwide')
      expect(wrapper.find('p').text()).toContain('Join thousands of professionals')
    })

    it('renders with custom title and subtitle', () => {
      const customTitle = 'Custom Statistics Title'
      const customSubtitle = 'Custom subtitle text'

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual',
          title: customTitle,
          subtitle: customSubtitle
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe(customTitle)
      expect(wrapper.find('p').text()).toBe(customSubtitle)
    })

    it('renders institutional-specific content', () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('Trusted by Leading Institutions')
      expect(wrapper.find('p').text()).toContain('universities and organizations')
    })
  })

  describe('Data Fetching', () => {
    it('fetches statistics on mount when autoFetch is true', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual',
          autoFetch: true
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/statistics?audience=individual',
        expect.objectContaining({
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
      )
    })

    it('does not fetch statistics when autoFetch is false', () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual',
          autoFetch: false
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(mockFetch).not.toHaveBeenCalled()
    })

    it('displays statistics after successful fetch', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      const statisticElements = wrapper.findAllComponents(AnimatedCounter)
      expect(statisticElements).toHaveLength(mockStatistics.length)
    })

    it('fetches institutional statistics with correct audience parameter', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/statistics?audience=institutional',
        expect.any(Object)
      )
    })
  })

  describe('Loading States', () => {
    it('shows loading state while fetching', async () => {
      // Mock a delayed response
      mockFetch.mockImplementation(() => 
        new Promise(resolve => 
          setTimeout(() => resolve({
            ok: true,
            json: () => Promise.resolve(mockApiResponse)
          }), 100)
        )
      )

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()

      // Should show loading state
      expect(wrapper.find('.animate-pulse').exists()).toBe(true)
      expect(wrapper.findAllComponents(AnimatedCounter)).toHaveLength(0)
    })

    it('hides loading state after successful fetch', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.find('.animate-pulse').exists()).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('displays error state when fetch fails', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'))

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Network error')
      expect(wrapper.find('button').text()).toBe('Try Again')
    })

    it('displays error state when API returns error', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ message: 'Server error' })
      })

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('HTTP error! status: 500')
    })

    it('retries fetch when Try Again button is clicked', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve(mockApiResponse)
        })

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
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

      // Should show statistics now
      expect(wrapper.findAllComponents(AnimatedCounter)).toHaveLength(mockStatistics.length)
    })

    it('falls back to mock data in development environment', async () => {
      const originalEnv = process.env.NODE_ENV
      process.env.NODE_ENV = 'development'

      mockFetch.mockRejectedValue(new Error('Network error'))

      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show mock statistics instead of error
      expect(wrapper.findAllComponents(AnimatedCounter).length).toBeGreaterThan(0)

      process.env.NODE_ENV = originalEnv
    })
  })

  describe('Statistics Display', () => {
    beforeEach(async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('displays correct number of statistics', () => {
      const statisticElements = wrapper.findAllComponents(AnimatedCounter)
      expect(statisticElements).toHaveLength(mockStatistics.length)
    })

    it('passes correct props to AnimatedCounter components', () => {
      const counterComponents = wrapper.findAllComponents(AnimatedCounter)
      
      counterComponents.forEach((counter, index) => {
        const stat = mockStatistics[index]
        expect(counter.props('targetValue')).toBe(stat.value)
        expect(counter.props('format')).toBe(stat.format)
        expect(counter.props('suffix')).toBe(stat.suffix)
      })
    })

    it('displays statistic labels correctly', () => {
      mockStatistics.forEach(stat => {
        expect(wrapper.text()).toContain(stat.label)
      })
    })

    it('displays last updated timestamp', () => {
      expect(wrapper.text()).toContain('Last updated:')
    })
  })

  describe('Responsive Design', () => {
    it('applies correct grid classes for responsive layout', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      const gridContainer = wrapper.find('.grid')
      expect(gridContainer.classes()).toContain('grid-cols-2')
      expect(gridContainer.classes()).toContain('md:grid-cols-4')
    })
  })

  describe('Accessibility', () => {
    beforeEach(async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('provides proper ARIA labels for counters', () => {
      const counterComponents = wrapper.findAllComponents(AnimatedCounter)
      
      counterComponents.forEach((counter, index) => {
        const stat = mockStatistics[index]
        const expectedLabel = `${stat.label}: ${stat.value}${stat.suffix || ''}`
        expect(counter.props('ariaLabel')).toBe(expectedLabel)
      })
    })

    it('uses semantic HTML structure', () => {
      expect(wrapper.find('h2').exists()).toBe(true)
      expect(wrapper.find('section').exists()).toBe(false) // Component wraps in div, not section
    })
  })

  describe('Component Methods', () => {
    it('exposes fetchStatistics method', () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual',
          autoFetch: false
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(wrapper.vm.fetchStatistics).toBeDefined()
      expect(typeof wrapper.vm.fetchStatistics).toBe('function')
    })

    it('exposes refresh method', () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual',
          autoFetch: false
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      expect(wrapper.vm.refresh).toBeDefined()
      expect(typeof wrapper.vm.refresh).toBe('function')
    })
  })

  describe('Animation Triggers', () => {
    it('triggers animations when component becomes visible', async () => {
      wrapper = mount(PlatformStatistics, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            AnimatedCounter
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Check that animation classes are applied
      const statisticElements = wrapper.findAll('.group')
      statisticElements.forEach(element => {
        expect(element.classes()).toContain('animate-fade-in')
      })
    })
  })
})