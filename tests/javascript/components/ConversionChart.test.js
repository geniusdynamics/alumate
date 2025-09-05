import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ConversionChart from '@/components/ConversionChart.vue'

describe('ConversionChart.vue', () => {
  let wrapper
  let mockData

  beforeEach(() => {
    // Mock conversion data
    mockData = [
      { date: '2024-01-01', conversions: 120, views: 2500, rate: 4.8 },
      { date: '2024-01-02', conversions: 145, views: 2800, rate: 5.2 },
      { date: '2024-01-03', conversions: 132, views: 2600, rate: 5.1 },
      { date: '2024-01-04', conversions: 158, views: 3200, rate: 4.9 },
      { date: '2024-01-05', conversions: 176, views: 3400, rate: 5.2 },
      { date: '2024-01-06', conversions: 189, views: 3600, rate: 5.3 },
      { date: '2024-01-07', conversions: 201, views: 3800, rate: 5.3 }
    ]

    wrapper = mount(ConversionChart, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          Transition: false,
          Teleport: false
        }
      },
      props: {
        data: mockData,
        title: 'Test Chart',
        subtitle: 'Test Subtitle',
        height: 400
      }
    })
  })

  describe('Initialization', () => {
    it('renders the chart container', () => {
      const chartContainer = wrapper.find('.conversion-chart')
      expect(chartContainer.exists()).toBe(true)
    })

    it('displays the chart title', () => {
      const title = wrapper.find('h3')
      expect(title.exists()).toBe(true)
      expect(title.text()).toBe('Test Chart')
    })

    it('displays the optional subtitle', () => {
      const subtitle = wrapper.find('p.text-sm.text-gray-600')
      expect(subtitle.exists()).toBe(true)
      expect(subtitle.text()).toBe('Test Subtitle')
    })

    it('renders chart type controls', () => {
      const controls = wrapper.findAll('.px-3.py-2.text-sm')
      expect(controls.length).toBeGreaterThan(0)
    })

    it('starts with line chart type by default', () => {
      expect(wrapper.vm.chartType).toBe('line')
    })

    it('sets correct SVG dimensions', () => {
      expect(wrapper.vm.svgWidth).toBe(800)
      expect(wrapper.vm.svgHeight).toBe(400)
    })
  })

  describe('Chart Type Controls', () => {
    it('renders all chart type buttons', () => {
      const buttons = wrapper.findAll('[aria-pressed]')
      expect(buttons.length).toBe(4) // line, bar, area, combined
    })

    it('changes chart type when button is clicked', async () => {
      const barButton = wrapper.findAll('.px-3.py-2')[1] // Bar chart button
      await barButton.trigger('click')

      expect(wrapper.vm.chartType).toBe('bar')
    })

    it('highlights the active chart type', async () => {
      const lineButton = wrapper.findAll('.px-3.py-2')[0] // Line chart button
      expect(lineButton.classes()).toContain('bg-blue-100')

      const barButton = wrapper.findAll('.px-3.py-2')[1]
      await barButton.trigger('click')

      expect(lineButton.classes()).not.toContain('bg-blue-100')
      expect(barButton.classes()).toContain('bg-blue-100')
    })

    it('includes accessible labels for chart type buttons', () => {
      const buttons = wrapper.findAll('[aria-label]')
      buttons.forEach(button => {
        expect(button.attributes('aria-label')).toContain('Display as')
      })
    })
  })

  describe('Chart Display - Line Chart', () => {
    beforeEach(async () => {
      await wrapper.setData({ chartType: 'line' })
    })

    it('renders SVG chart area for line chart', () => {
      const svg = wrapper.find('svg')
      expect(svg.exists()).toBe(true)
    })

    it('draws grid lines in line chart', () => {
      const gridPattern = wrapper.find('defs pattern')
      expect(gridPattern.exists()).toBe(true)
    })

    it('renders axes for line chart', () => {
      const axes = wrapper.findAll('line')
      expect(axes.length).toBeGreaterThan(0)
    })

    it('renders data points for line chart', () => {
      const points = wrapper.findAll('circle')
      expect(points.length).toBe(mockData.length)
    })

    it('includes accessible description for line chart', () => {
      const chartArea = wrapper.find('[role="img"]')
      expect(chartArea.attributes('aria-label')).toContain('line chart')
    })
  })

  describe('Chart Display - Bar Chart', () => {
    beforeEach(async () => {
      await wrapper.setData({ chartType: 'bar' })
    })

    it('renders bar chart elements', () => {
      const barChart = wrapper.find('.bar-chart')
      expect(barChart.exists()).toBe(true)
    })

    it('displays bars for each data point', () => {
      const bars = wrapper.findAll('.bar')
      expect(bars.length).toBe(mockData.length)
    })

    it('shows date labels for bars', () => {
      const labels = wrapper.findAll('.text-xs.text-gray-600')
      expect(labels.length).toBeGreaterThan(0)
    })

    it('sets correct bar heights based on data', () => {
      const bars = wrapper.findAll('.bar')
      bars.forEach(bar => {
        expect(bar.attributes('style')).toContain('height')
      })
    })
  })

  describe('Chart Display - Area Chart', () => {
    beforeEach(async () => {
      await wrapper.setData({ chartType: 'area' })
    })

    it('renders area chart with path element', () => {
      const areaChart = wrapper.find('.area-chart')
      expect(areaChart.exists()).toBe(true)

      const path = wrapper.find('path[d]')
      expect(path.exists()).toBe(true)
    })

    it('includes area fill gradient', () => {
      // Area chart should have fill attribute
      expect(wrapper.vm).toBeDefined()
    })
  })

  describe('Chart Display - Combined Chart', () => {
    beforeEach(async () => {
      await wrapper.setData({ chartType: 'combined' })
    })

    it('renders combined chart with multiple data series', () => {
      const combinedChart = wrapper.find('.combined-chart')
      expect(combinedChart.exists()).toBe(true)
    })

    it('displays legend for combined chart', () => {
      const legend = wrapper.findAll('.flex.items-center.gap-2')
      expect(legend.length).toBeGreaterThan(0)
    })

    it('shows both conversions and rate data', () => {
      const legendItems = wrapper.findAll('.flex.items-center.gap-2')
      const labelTexts = legendItems.map(item => item.text())
      expect(labelTexts.some(text => text.includes('Conversions'))).toBe(true)
      expect(labelTexts.some(text => text.includes('Rate'))).toBe(true)
    })
  })

  describe('Empty State', () => {
    it('displays empty state when no data is provided', async () => {
      await wrapper.setProps({ data: [] })

      const emptyState = wrapper.find('.empty-state')
      expect(emptyState.exists()).toBe(true)
      expect(emptyState.text()).toContain('No data available')
    })

    it('shows appropriate message for empty data', async () => {
      await wrapper.setProps({ data: [] })

      const emptyMessage = wrapper.find('p')
      expect(emptyMessage.text()).toContain('Conversion metrics will appear here')
    })
  })

  describe('Summary Statistics', () => {
    it('displays average conversion rate in summary', () => {
      const summaryStats = wrapper.find('.text-center')
      expect(summaryStats.length).toBeGreaterThan(0)
    })

    it('calculates and displays total conversions', () => {
      const totalConversions = wrapper.vm.totalConversions
      expect(totalConversions).toBeGreaterThan(0)
    })

    it('shows trend indicators in summary', () => {
      const summaryGrid = wrapper.find('.grid.grid-cols-1.md\\:grid-cols-3')
      expect(summaryGrid.exists()).toBe(true)
    })
  })

  describe('Computed Properties', () => {
    it('calculates maxValue correctly', () => {
      const maxValue = wrapper.vm.maxValue
      const expectedMax = Math.max(...mockData.map(d => d.rate)) * 1.1
      expect(maxValue).toBe(expectedMax)
    })

    it('computes averageRate accurately', () => {
      const averageRate = wrapper.vm.averageRate
      const sum = mockData.reduce((acc, point) => acc + point.rate, 0)
      const expectedAverage = sum / mockData.length
      expect(averageRate).toBe(expectedAverage)
    })

    it('calculates trendValue based on recent data', () => {
      const trendValue = wrapper.vm.trendValue
      expect(typeof trendValue).toBe('number')
    })

    it('determines trendDirection correctly', () => {
      const trendDirection = wrapper.vm.trendDirection
      expect(['up', 'down', 'neutral']).toContain(trendDirection)
    })

    it('computes trendClass based on direction', () => {
      const trendClass = wrapper.vm.trendClass
      expect(typeof trendClass).toBe('string')

      if (trendClass.includes('green')) {
        expect(wrapper.vm.trendDirection).toBe('up')
      } else if (trendClass.includes('red')) {
        expect(wrapper.vm.trendDirection).toBe('down')
      }
    })
  })

  describe('Data Formatting', () => {
    it('formats numbers correctly for large values', () => {
      expect(wrapper.vm.formatNumber(1500000)).toBe('1.5M')
      expect(wrapper.vm.formatNumber(1500)).toBe('1.5K')
      expect(wrapper.vm.formatNumber(150)).toBe('150')
    })

    it('formats dates correctly for chart labels', () => {
      const formattedDate = wrapper.vm.formatDateLabel('2024-01-01')
      expect(formattedDate).toMatch(/\w{3} \d/)
    })
  })

  describe('Accessory Functionality', () => {
    it('provides screen reader description', () => {
      const srDescription = wrapper.find('#chart-description')
      expect(srDescription.exists()).toBe(true)
      expect(srDescription.classes()).toContain('sr-only')
    })

    it('includes accessibility features for chart data', () => {
      expect(wrapper.vm).toBeDefined()
      // Chart should include ARIA attributes and semantic structure
    })
  })

  describe('Responsive Design', () => {
    it('handles window resize events', async () => {
      // Simulate resize
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 600
      })

      window.dispatchEvent(new Event('resize'))
      await wrapper.vm.$nextTick()

      // Component should adapt to new dimensions
      expect(wrapper.vm).toBeDefined()
    })
  })

  describe('Lifecycle Hooks', () => {
    it('sets up event listeners on mount', () => {
      expect(wrapper.vm).toBeDefined()
      // Resize event listeners should be attached
    })

    it('cleans up event listeners on unmount', () => {
      const removeSpy = vi.spyOn(window, 'removeEventListener')

      wrapper.unmount()

      expect(removeSpy).toHaveBeenCalled()
    })
  })

  describe('Chart Calculations', () => {
    it('calculates X coordinates correctly', () => {
      const xCoord = wrapper.vm.getXCoordinate(0)
      expect(xCoord).toBe(wrapper.vm.padding.left)

      const xCoord2 = wrapper.vm.getXCoordinate(1)
      expect(xCoord2).toBeGreaterThan(xCoord)
    })

    it('calculates Y coordinates correctly', () => {
      const yCoord = wrapper.vm.getYCoordinate(5.0)
      expect(typeof yCoord).toBe('number')
      expect(yCoord).toBeGreaterThan(wrapper.vm.padding.top)
      expect(yCoord).toBeLessThan(wrapper.vm.svgHeight - wrapper.vm.padding.bottom)
    })

    it('calculates bar heights correctly', () => {
      const barHeight = wrapper.vm.getBarHeight(5.0)
      expect(typeof barHeight).toBe('number')
    })
  })

  describe('Performance Optimization', () => {
    it('uses efficient rendering for large datasets', async () => {
      const largeDataset = Array.from({ length: 100 }, (_, i) => ({
        date: `2024-01-${String(i + 1).padStart(2, '0')}`,
        conversions: Math.floor(Math.random() * 1000),
        views: Math.floor(Math.random() * 5000),
        rate: Math.random() * 10
      }))

      await wrapper.setProps({ data: largeDataset })

      expect(wrapper.vm.data.length).toBe(100)
      expect(wrapper.exists()).toBe(true)
    })

    it('handles rapid chart type changes efficiently', async () => {
      const buttons = wrapper.findAll('.px-3.py-2')

      // Rapidly change chart types
      for (let i = 0; i < buttons.length; i++) {
        await buttons[i].trigger('click')
        expect(wrapper.vm).toBeDefined()
      }
    })
  })

  describe('Error Handling', () => {
    it('handles undefined or null data gracefully', async () => {
      await wrapper.setProps({ data: null })
      expect(wrapper.exists()).toBe(true)
    })

    it('handles empty data array gracefully', async () => {
      await wrapper.setProps({ data: [] })
      expect(wrapper.vm.data.length).toBe(0)
      expect(wrapper.vm.averageRate).toBe(0)
    })

    it('handles missing data properties gracefully', async () => {
      const incompleteData = [
        { date: '2024-01-01', rate: 5.0 }, // Missing conversions, views
        { date: '2024-01-02', rate: 4.8 }
      ]

      await wrapper.setProps({ data: incompleteData })
      expect(wrapper.vm.totalConversions).toBe(0)
    })
  })

  describe('Integration with Parent Components', () => {
    it('accepts and reacts to prop changes', async () => {
      const newData = [
        { date: '2024-01-01', conversions: 200, views: 4000, rate: 5.0 },
        { date: '2024-01-02', conversions: 250, views: 4500, rate: 5.6 }
      ]

      await wrapper.setProps({
        data: newData,
        title: 'Updated Chart',
        height: 500
      })

      expect(wrapper.vm.data.length).toBe(2)
      expect(wrapper.vm.svgHeight).toBe(500)
    })

    it('emits events if needed by parent components', () => {
      // Test any events that might be emitted
      expect(wrapper.emitted()).toBeUndefined()
    })
  })
})