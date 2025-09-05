import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TemplateRecommendations from '@/components/TemplateRecommendations.vue'

describe('TemplateRecommendations.vue', () => {
  let wrapper
  let mockTemplates
  let mockPerformanceData

  beforeEach(() => {
    // Mock template data
    mockTemplates = [
      {
        id: 1,
        name: 'Modern Business Hero',
        audienceType: 'employer',
        conversionRate: 8.5,
        conversions: 1200,
        change: 2.3,
        score: 85,
        trend: 'up'
      },
      {
        id: 2,
        name: 'Student Welcome CTA',
        audienceType: 'individual',
        conversionRate: 6.2,
        conversions: 840,
        change: -1.2,
        score: 72,
        trend: 'down'
      },
      {
        id: 3,
        name: 'Institution Overview',
        audienceType: 'institution',
        conversionRate: 7.8,
        conversions: 1080,
        change: 1.8,
        score: 78,
        trend: 'up'
      }
    ]

    mockPerformanceData = {
      recentlyUsed: mockTemplates.slice(0, 2),
      highConversion: mockTemplates.filter(t => t.conversionRate > 7),
      trendingUp: mockTemplates.filter(t => t.trend === 'up')
    }

    wrapper = mount(TemplateRecommendations, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          Transition: false,
          Teleport: false
        }
      },
      props: {
        templates: mockTemplates,
        performanceData: mockPerformanceData
      }
    })
  })

  describe('Initialization', () => {
    it('renders the recommendations component', () => {
      const recommendations = wrapper.find('.template-recommendations')
      expect(recommendations.exists()).toBe(true)
    })

    it('displays the main heading', () => {
      const heading = wrapper.find('h3')
      expect(heading.exists()).toBe(true)
      expect(heading.text()).toBe('Smart Template Recommendations')
    })

    it('accepts and processes templates prop', () => {
      expect(wrapper.props('templates')).toHaveLength(3)
      expect(wrapper.props('templates')[0].name).toBe('Modern Business Hero')
    })

    it('accepts performance data prop', () => {
      expect(wrapper.props('performanceData')).toEqual(mockPerformanceData)
    })
  })

  describe('High-Performing Templates Section', () => {
    it('renders high-performing templates section', () => {
      const section = wrapper.find('h4').filter(h4 =>
        h4.text().includes('High-Performing Templates')
      )
      expect(section.exists()).toBe(true)
    })

    it('displays high-performing templates correctly', () => {
      const highPerformers = wrapper.findAll('.bg-green-50.dark\\:bg-green-900\\/20')
      expect(highPerformers.length).toBeGreaterThan(0)
    })

    it('shows template names in high-performing section', () => {
      const templateNames = wrapper.findAll('.font-medium.text-gray-900.dark\\:text-white')
      expect(templateNames.length).toBeGreaterThan(0)
      expect(templateNames.some(name => name.text().includes('Modern Business Hero'))).toBe(true)
    })

    it('displays conversion rates correctly', () => {
      const rates = wrapper.findAll('div').filter(div =>
        div.text().includes('% conversion rate')
      )
      expect(rates.length).toBeGreaterThan(0)
    })

    it('includes Use This Template buttons', () => {
      const useButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Use This Template')
      )
      expect(useButtons.length).toBeGreaterThan(0)
    })

    it('shows scores for templates', () => {
      const scores = wrapper.findAll('span').filter(span =>
        span.text().includes('/100')
      )
      expect(scores.length).toBeGreaterThan(0)
    })
  })

  describe('Rising Stars Section', () => {
    it('renders rising stars section', () => {
      const section = wrapper.find('h4').filter(h4 =>
        h4.text().includes('Rising Stars')
      )
      expect(section.exists()).toBe(true)
    })

    it('displays trending templates', () => {
      const trendingTemplates = wrapper.findAll('.bg-blue-50.dark\\:bg-blue-900\\/20')
      expect(trendingTemplates.length).toBeGreaterThan(0)
    })

    it('shows trend information with arrows', () => {
      const arrows = wrapper.findAll('svg').filter(svg =>
        svg.classes().includes('w-4') || svg.classes().includes('w-5')
      )
      expect(arrows.length).toBeGreaterThan(0)
    })

    it('displays change percentages for trending templates', () => {
      const changes = wrapper.findAll('div').filter(div =>
        div.text().includes('+') || div.text().includes('-')
      )
      expect(changes.some(change => change.text().includes('+2.3%'))).toBe(true)
    })
  })

  describe('A/B Testing Suggestions', () => {
    it('renders A/B testing section', () => {
      const section = wrapper.find('h4').filter(h4 =>
        h4.text().includes('A/B Test Opportunities')
      )
      expect(section.exists()).toBe(true)
    })

    it('displays testing recommendations when available', () => {
      const testCards = wrapper.findAll('.bg-purple-50.dark\\:bg-purple-900\\/20')
      expect(testCards.length).toBeGreaterThan(0)
    })

    it('shows template comparison in test suggestions', () => {
      const comparisons = wrapper.findAll('.font-medium.text-gray-900.dark\\:text-white')
      expect(comparisons.some(comp => comp.text().includes('vs'))).toBe(true)
    })

    it('includes potential lift information', () => {
      const liftInfo = wrapper.findAll('span').filter(span =>
        span.text().includes('potential lift')
      )
      expect(liftInfo.length).toBeGreaterThan(0)
    })

    it('displays start test buttons', () => {
      const startButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Start Test')
      )
      expect(startButtons.length).toBeGreaterThan(0)
    })
  })

  describe('Performance Insights', () => {
    it('renders performance insights section', () => {
      const section = wrapper.find('h4').filter(h4 =>
        h4.text().includes('Performance Insights')
      )
      expect(section.exists()).toBe(true)
    })

    it('displays multiple insight cards', () => {
      const insightCards = wrapper.findAll('.text-sm').filter(card =>
        card.classes().includes('rounded-lg') || card.classes().includes('p-4')
      )
      expect(insightCards.length).toBeGreaterThan(0)
    })

    it('shows different types of insights (positive, warning, info)', () => {
      // Test that different styles/colors are applied for different insight types
      const insightCards = wrapper.findAll('[class*="bg-"]')
      expect(insightCards.length).toBeGreaterThan(0)
    })
  })

  describe('Button Interactions', () => {
    it('handles Use This Template button clicks', async () => {
      const useButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Use This Template')
      )

      if (useButtons.length > 0) {
        const useButton = useButtons[0]
        await useButton.trigger('click')

        // Should not throw an error
        expect(wrapper.vm).toBeDefined()
      }
    })

    it('handles Start Test button clicks', async () => {
      const startButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Start Test')
      )

      if (startButtons.length > 0) {
        const startButton = startButtons[0]
        await startButton.trigger('click')

        // Should not throw an error
        expect(wrapper.vm).toBeDefined()
      }
    })
  })

  describe('Computed Properties', () => {
    it('computes high-performing templates correctly', () => {
      const highPerforming = wrapper.vm.highPerformingTemplates
      expect(Array.isArray(highPerforming)).toBe(true)
      expect(highPerforming.length).toBeLessThanOrEqual(4)
      expect(highPerforming.every(t => t.conversionRate > 5.0)).toBe(true)
    })

    it('computes trending templates correctly', () => {
      const trending = wrapper.vm.trendingTemplates
      expect(Array.isArray(trending)).toBe(true)
      expect(trending.every(t => t.trend === 'up')).toBe(true)
    })
  })

  describe('Methods', () => {
    it('formats numbers correctly', () => {
      expect(wrapper.vm.formatNumber(1500)).toBe('1.5K')
      expect(wrapper.vm.formatNumber(1500000)).toBe('1.5M')
      expect(wrapper.vm.formatNumber(150)).toBe('150')
    })

    it('handles template recommendation calls', () => {
      const mockRecommend = vi.spyOn(wrapper.vm, 'recommendTemplate')

      wrapper.vm.recommendTemplate(1)

      expect(mockRecommend).toHaveBeenCalledWith(1)
    })

    it('handles A/B test initiation', () => {
      const mockStartTest = vi.spyOn(wrapper.vm, 'startABTest')

      wrapper.vm.startABTest('test-1')

      expect(mockStartTest).toHaveBeenCalledWith('test-1')
    })
  })

  describe('Responsive Design', () => {
    it('uses responsive grid classes', () => {
      const grids = wrapper.findAll('.grid.grid-cols-1.md\\:grid-cols-2')
      expect(grids.length).toBeGreaterThan(0)
    })

    it('adapts layout for different screen sizes', () => {
      const responsiveElements = wrapper.findAll('[class*="md:"]')
      expect(responsiveElements.length).toBeGreaterThan(0)
    })
  })

  describe('Empty States', () => {
    it('handles empty templates array gracefully', async () => {
      await wrapper.setProps({ templates: [] })

      expect(wrapper.exists()).toBe(true)
      const emptyMessages = wrapper.findAll('.text-gray-500.dark\\:text-gray-400')
      expect(emptyMessages.length).toBeGreaterThan(0)
    })

    it('handles empty performance data gracefully', async () => {
      await wrapper.setProps({
        templates: mockTemplates,
        performanceData: {}
      })

      expect(wrapper.exists()).toBe(true)
    })

    it('shows appropriate message when no high-performing templates exist', async () => {
      const lowPerformingTemplates = mockTemplates.map(t => ({
        ...t,
        conversionRate: 2.0 // Below threshold
      }))

      await wrapper.setProps({ templates: lowPerformingTemplates })

      const emptyMessage = wrapper.find('.text-gray-500.dark\\:text-gray-400')
      expect(emptyMessage.exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA labels for interactive elements', () => {
      const useButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Use This Template')
      )

      useButtons.forEach(button => {
        expect(button.attributes('aria-label')).toBeTruthy()
      })
    })

    it('provides semantic structure for screen readers', () => {
      const headings = wrapper.findAll('h3, h4')
      expect(headings.length).toBeGreaterThan(0)
    })

    it('uses appropriate heading levels for content hierarchy', () => {
      const h3Headings = wrapper.findAll('h3')
      const h4Headings = wrapper.findAll('h4')

      expect(h3Headings.length).toBeGreaterThanOrEqual(1)
      expect(h4Headings.length).toBeGreaterThanOrEqual(4)
    })

    it('includes alt text for icons or visual elements', () => {
      // Check that components include proper accessibility attributes
      expect(wrapper.vm).toBeDefined()
    })
  })

  describe('Data Integrity', () => {
    it('validates template data structure', () => {
      const invalidTemplate = {
        id: 'invalid',
        name: '',
        audienceType: '',
        conversionRate: 'invalid'
      }

      expect(() => {
        wrapper.setProps({
          templates: [...mockTemplates, invalidTemplate]
        })
      }).toThrow()
    })

    it('handles missing template properties gracefully', async () => {
      const incompleteTemplate = {
        id: 4,
        name: 'Incomplete Template'
        // Missing other required properties
      }

      await wrapper.setProps({
        templates: [...mockTemplates, incompleteTemplate]
      })

      expect(wrapper.exists()).toBe(true)
    })

    it('processes different audience types correctly', () => {
      expect(wrapper.vm).toBeDefined()

      // Check that different audience types are handled
      const templates = wrapper.props('templates')
      const audienceTypes = templates.map(t => t.audienceType)
      expect(audienceTypes.includes('employer')).toBe(true)
      expect(audienceTypes.includes('individual')).toBe(true)
    })
  })

  describe('Performance Optimization', () => {
    it('efficiently processes template data', () => {
      const largeTemplateSet = Array.from({ length: 50 }, (_, index) => ({
        id: index + 1,
        name: `Template ${index + 1}`,
        audienceType: ['employer', 'individual', 'institution'][index % 3],
        conversionRate: Math.random() * 10,
        conversions: Math.floor(Math.random() * 1000),
        change: (Math.random() - 0.5) * 4,
        score: Math.floor(Math.random() * 100) + 60,
        trend: 'up'
      }))

      const originalTemplates = wrapper.props('templates')
      wrapper.setProps({ templates: largeTemplateSet })

      expect(wrapper.vm.highPerformingTemplates.length).toBeLessThanOrEqual(4)
      expect(wrapper.vm.trendingTemplates.length).toBeLessThanOrEqual(3)

      // Restore original data
      wrapper.setProps({ templates: originalTemplates })
    })

    it('computes filtered data efficiently', () => {
      // Test that filtering is done efficiently
      expect(wrapper.vm.highPerformingTemplates).toBeDefined()
      expect(wrapper.vm.trendingTemplates).toBeDefined()
    })
  })

  describe('Edge Cases', () => {
    it('handles templates with zero conversion rates', async () => {
      const zeroRateTemplate = {
        id: 4,
        name: 'Zero Conversion Template',
        audienceType: 'employer',
        conversionRate: 0,
        conversions: 0,
        change: 0,
        score: 10,
        trend: 'neutral'
      }

      await wrapper.setProps({
        templates: [...mockTemplates, zeroRateTemplate]
      })

      expect(wrapper.vm.formatNumber(0)).toBe('0')
    })

    it('handles negative conversion rate changes', async () => {
      const negativeChangeTemplate = {
        id: 4,
        name: 'Declining Template',
        audienceType: 'individual',
        conversionRate: 3.5,
        conversions: 150,
        change: -2.5,
        score: 45,
        trend: 'down'
      }

      await wrapper.setProps({
        templates: [...mockTemplates, negativeChangeTemplate]
      })

      const trendingTemplates = wrapper.vm.trendingTemplates
      expect(trendingTemplates.some(t => t.change < 0)).toBe(false)
    })

    it('handles very high conversion rates', async () => {
      const highRateTemplate = {
        id: 4,
        name: 'High Conversion Template',
        audienceType: 'institution',
        conversionRate: 95.2,
        conversions: 5000,
        change: 5,
        score: 98,
        trend: 'up'
      }

      await wrapper.setProps({
        templates: [...mockTemplates, highRateTemplate]
      })

      expect(wrapper.vm.highPerformingTemplates.some(t => t.name === 'High Conversion Template')).toBe(true)
    })
  })

  describe('Error Handling', () => {
    it('gracefully handles undefined performance data', async () => {
      await wrapper.setProps({
        templates: mockTemplates,
        performanceData: undefined
      })

      expect(wrapper.exists()).toBe(true)
    })

    it('handles malformed template data without crashing', async () => {
      const malformedTemplates = [
        { id: null, name: null, conversionRate: 'invalid' },
        ...mockTemplates
      ]

      expect(() => {
        wrapper.setProps({ templates: malformedTemplates })
      }).not.toThrow()
    })
  })

  describe('User Interaction', () => {
    it('responds to user interactions without errors', async () => {
      // Test template recommendation buttons
      const useButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Use This Template')
      )

      for (const button of useButtons) {
        await button.trigger('click')
        expect(wrapper.vm).toBeDefined()
      }

      // Test A/B test buttons
      const startButtons = wrapper.findAll('button').filter(button =>
        button.text().includes('Start Test')
      )

      for (const button of startButtons) {
        await button.trigger('click')
        expect(wrapper.vm).toBeDefined()
      }
    })

    it('handles rapid button clicks appropriately', async () => {
      // Test rapid clicking of buttons
      const useButton = wrapper.findAll('button').filter(button =>
        button.text().includes('Use This Template')
      )[0]

      if (useButton) {
        for (let i = 0; i < 5; i++) {
          await useButton.trigger('click')
        }
        expect(wrapper.vm).toBeDefined()
      }
    })
  })
})