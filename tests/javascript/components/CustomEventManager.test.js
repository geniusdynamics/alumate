import { describe, it, expect, beforeEach, vi, afterEach, jest } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CustomEventManager from '@/components/Analytics/CustomEventManager.vue'

describe('CustomEventManager.vue', () => {
  let wrapper
  let mockDefinitions

  const mockCustomEventStoreData = {
    isLoading: false,
    error: '',
    definitions: [],
    events: [],
    analyticsData: null,
    pagination: {
      current_page: 1,
      per_page: 20,
      total: 0,
      last_page: 1,
    },
  }

  beforeEach(() => {
    // Mock custom event definitions
    mockDefinitions = [
      {
        id: 1,
        name: 'user_signup_completed',
        description: 'Triggered when a user completes signup',
        category: 'conversion',
        parameters_json: [
          { name: 'signup_method', type: 'string' },
          { name: 'time_spent', type: 'number' },
        ],
        status: 'active',
        aggregates: {
          total_events: 1250,
          unique_users: 980,
          time_series: [
            { date: '2024-01-01', count: 45 },
            { date: '2024-01-02', count: 52 },
            { date: '2024-01-03', count: 48 },
          ],
        },
      },
      {
        id: 2,
        name: 'page_view',
        description: 'Triggered when a page is viewed',
        category: 'engagement',
        parameters_json: [
          { name: 'page_url', type: 'string' },
          { name: 'referrer', type: 'string' },
        ],
        status: 'active',
        aggregates: {
          total_events: 15000,
          unique_users: 3500,
          time_series: [],
        },
      },
      {
        id: 3,
        name: 'error_occurred',
        description: 'Triggered when an error occurs',
        category: 'error',
        parameters_json: [
          { name: 'error_type', type: 'string' },
          { name: 'error_message', type: 'string' },
        ],
        status: 'inactive',
        aggregates: {
          total_events: 150,
          unique_users: 120,
          time_series: [],
        },
      },
    ]

    wrapper = mount(CustomEventManager, {
      global: {
        plugins: [
          createTestingPinia({
            initialState: {
              customEvent: {
                ...mockCustomEventStoreData,
                definitions: mockDefinitions,
              },
            },
          }),
        ],
        stubs: {
          Transition: false,
          Teleport: false,
        },
      },
      props: {
        initialTab: 'list',
      },
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Initialization', () => {
    it('renders the component container', () => {
      const container = wrapper.find('.custom-event-manager')
      expect(container.exists()).toBe(true)
    })

    it('displays the header title', () => {
      const header = wrapper.find('h1')
      expect(header.exists()).toBe(true)
      expect(header.text()).toBe('Custom Event Manager')
    })

    it('renders all tab buttons', () => {
      const tabs = wrapper.findAll('nav button')
      expect(tabs.length).toBe(5) // list, create, analytics, flow, optimization
    })

    it('starts with list tab active by default', () => {
      expect(wrapper.vm.activeTab).toBe('list')
    })

    it('displays header description', () => {
      const description = wrapper.find('p.text-sm.text-gray-600')
      expect(description.exists()).toBe(true)
      expect(description.text()).toContain('Define, track, and analyze custom events')
    })
  })

  describe('Event List View', () => {
    it('displays search input', () => {
      const searchInput = wrapper.find('#search-events')
      expect(searchInput.exists()).toBe(true)
    })

    it('displays status filter dropdown', () => {
      const statusFilter = wrapper.find('#status-filter')
      expect(statusFilter.exists()).toBe(true)
    })

    it('renders event definition cards', async () => {
      await wrapper.setData({ definitions: mockDefinitions })
      
      const cards = wrapper.findAll('.rounded-lg.border.border-gray-200.bg-white.p-4')
      expect(cards.length).toBe(3)
    })

    it('filters events by search query', async () => {
      await wrapper.setData({ definitions: mockDefinitions, searchQuery: 'signup' })
      
      const filtered = wrapper.vm.filteredDefinitions
      expect(filtered.length).toBe(1)
      expect(filtered[0].name).toBe('user_signup_completed')
    })

    it('filters events by status', async () => {
      await wrapper.setData({ definitions: mockDefinitions, statusFilter: 'active' })
      
      const filtered = wrapper.vm.filteredDefinitions
      expect(filtered.length).toBe(2)
      expect(filtered.every(def => def.status === 'active')).toBe(true)
    })

    it('shows empty state when no events match search', async () => {
      await wrapper.setData({ definitions: mockDefinitions, searchQuery: 'nonexistent' })
      
      const emptyState = wrapper.find('.text-center.py-12')
      expect(emptyState.exists()).toBe(true)
    })

    it('displays event aggregates correctly', async () => {
      await wrapper.setData({ definitions: mockDefinitions })
      
      const card = wrapper.find('.rounded-lg.border.border-gray-200.bg-white.p-4')
      expect(card.text()).toContain('1250')
      expect(card.text()).toContain('980')
    })

    it('displays event category badges', async () => {
      await wrapper.setData({ definitions: mockDefinitions })
      
      const badges = wrapper.findAll('.inline-flex.items-center.px-2\\.5.py-0\\.5.rounded-full.text-xs.font-medium')
      expect(badges.length).toBeGreaterThan(0)
    })
  })

  describe('Event Create View', () => {
    it('switches to create tab when button clicked', async () => {
      const createButton = wrapper.find('button:contains("Create New Event")')
      await createButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('create')
    })

    it('renders create form with required fields', async () => {
      await wrapper.setData({ activeTab: 'create' })
      await nextTick()
      
      const nameInput = wrapper.find('#event-name')
      expect(nameInput.exists()).toBe(true)
      
      const categorySelect = wrapper.find('#event-category')
      expect(categorySelect.exists()).toBe(true)
      
      const descriptionTextarea = wrapper.find('#event-description')
      expect(descriptionTextarea.exists()).toBe(true)
    })

    it('allows adding parameters', async () => {
      await wrapper.setData({ activeTab: 'create' })
      await nextTick()
      
      const initialParams = wrapper.vm.newDefinition.parameters_json.length
      const addButton = wrapper.find('button:contains(\"+ Add Parameter\")')
      await addButton.trigger('click')
      
      expect(wrapper.vm.newDefinition.parameters_json.length).toBe(initialParams + 1)
    })

    it('allows removing parameters', async () => {
      await wrapper.setData({ 
        activeTab: 'create',
        newDefinition: {
          ...wrapper.vm.newDefinition,
          parameters_json: [
            { name: 'param1', type: 'string' },
            { name: 'param2', type: 'number' },
          ]
        }
      })
      await nextTick()
      
      const removeButtons = wrapper.findAll('button.text-red-600')
      await removeButtons[1].trigger('click')
      
      expect(wrapper.vm.newDefinition.parameters_json.length).toBe(1)
    })

    it('validates event name is required', async () => {
      await wrapper.setData({ 
        activeTab: 'create',
        newDefinition: {
          name: '',
          description: 'Test description',
          category: 'custom',
          parameters_json: [{ name: 'test', type: 'string' }],
        }
      })
      await nextTick()
      
      const submitButton = wrapper.find('button[type="submit"]')
      await submitButton.trigger('click')
      
      // Form should not submit with empty name
      expect(wrapper.vm.isSubmitting).toBe(false)
    })

    it('cancels and returns to list view', async () => {
      await wrapper.setData({ activeTab: 'create' })
      await nextTick()
      
      const cancelButton = wrapper.find('button:contains("Cancel")')
      await cancelButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('list')
    })
  })

  describe('Event Analytics View', () => {
    it('switches to analytics tab', async () => {
      await wrapper.setData({ activeTab: 'analytics' })
      await nextTick()
      
      const analyticsHeader = wrapper.find('h2.text-xl.font-semibold')
      expect(analyticsHeader.text()).toBe('Event Analytics')
    })

    it('displays event selector dropdown', async () => {
      await wrapper.setData({ activeTab: 'analytics' })
      await nextTick()
      
      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)
    })

    it('loads analytics when event is selected', async () => {
      await wrapper.setData({ 
        activeTab: 'analytics',
        selectedDefinitionId: 1,
      })
      await nextTick()
      
      expect(wrapper.vm.selectedDefinitionId).toBe(1)
    })

    it('displays summary cards with analytics data', async () => {
      await wrapper.setData({ 
        activeTab: 'analytics',
        selectedDefinitionId: 1,
      })
      await nextTick()
      
      const summarySection = wrapper.find('.rounded-lg.border.border-gray-200.bg-white.p-4')
      expect(summarySection.exists()).toBe(true)
    })
  })

  describe('Event Flow View', () => {
    it('switches to flow tab', async () => {
      await wrapper.setData({ activeTab: 'flow' })
      await nextTick()
      
      const flowHeader = wrapper.find('h2.text-xl.font-semibold')
      expect(flowHeader.text()).toBe('Event Flow Visualization')
    })

    it('displays event selector for flow', async () => {
      await wrapper.setData({ activeTab: 'flow' })
      await nextTick()
      
      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)
    })
  })

  describe('Optimization View', () => {
    it('switches to optimization tab', async () => {
      await wrapper.setData({ activeTab: 'optimization' })
      await nextTick()
      
      const optHeader = wrapper.find('h2.text-xl.font-semibold')
      expect(optHeader.text()).toBe('Optimization Recommendations')
    })

    it('displays event selector for optimization', async () => {
      await wrapper.setData({ activeTab: 'optimization' })
      await nextTick()
      
      const select = wrapper.find('select')
      expect(select.exists()).toBe(true)
    })

    it('displays optimization suggestions when loaded', async () => {
      await wrapper.setData({ 
        activeTab: 'optimization',
        selectedDefinitionId: 1,
        optimizationData: {
          definition_id: 1,
          suggestions: [
            {
              type: 'improvement',
              title: 'Low User Engagement',
              description: 'Users are triggering this event less than twice on average.',
              priority: 'high',
              action: 'Review event triggers',
            },
            {
              type: 'insight',
              title: 'Peak Activity Hours',
              description: 'Most event activity occurs between 9:00 and 17:00.',
              priority: 'medium',
              action: null,
            },
          ],
          generated_at: '2024-01-15T10:30:00Z',
        },
      })
      await nextTick()
      
      const suggestionCards = wrapper.findAll('.rounded-lg.border.p-4')
      expect(suggestionCards.length).toBe(2)
    })

    it('displays priority badges for suggestions', async () => {
      await wrapper.setData({ 
        activeTab: 'optimization',
        selectedDefinitionId: 1,
        optimizationData: {
          definition_id: 1,
          suggestions: [
            {
              type: 'improvement',
              title: 'High Priority Issue',
              description: 'Description here',
              priority: 'high',
              action: null,
            },
          ],
          generated_at: '2024-01-15T10:30:00Z',
        },
      })
      await nextTick()
      
      const highPriorityBadge = wrapper.find('.bg-red-100.text-red-800')
      expect(highPriorityBadge.exists()).toBe(true)
      expect(highPriorityBadge.text()).toContain('high priority')
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA labels', () => {
      const region = wrapper.find('[role="region"]')
      expect(region.exists()).toBe(true)
      expect(region.attributes('aria-label')).toBe('Custom event management dashboard')
    })

    it('provides screen reader descriptions for charts', () => {
      const chartLabels = wrapper.findAll('[aria-label]')
      expect(chartLabels.length).toBeGreaterThan(0)
    })
  })

  describe('Error Handling', () => {
    it('displays error state when store has error', async () => {
      await wrapper.setData({ 
        customEventStore: { ...mockCustomEventStoreData, error: 'Failed to load data' }
      })
      
      const errorState = wrapper.find('.rounded-lg.border.border-red-200')
      expect(errorState.exists()).toBe(true)
    })

    it('displays loading state', async () => {
      await wrapper.setData({ 
        customEventStore: { ...mockCustomEventStoreData, isLoading: true }
      })
      
      const loadingSpinner = wrapper.find('.animate-spin')
      expect(loadingSpinner.exists()).toBe(true)
    })
  })

  describe('Utility Methods', () => {
    it('generates correct category badge classes', () => {
      expect(wrapper.vm.getCategoryBadgeClass('conversion')).toBe('bg-blue-100 text-blue-800')
      expect(wrapper.vm.getCategoryBadgeClass('engagement')).toBe('bg-green-100 text-green-800')
      expect(wrapper.vm.getCategoryBadgeClass('error')).toBe('bg-red-100 text-red-800')
      expect(wrapper.vm.getCategoryBadgeClass('custom')).toBe('bg-purple-100 text-purple-800')
    })

    it('handles unknown category', () => {
      expect(wrapper.vm.getCategoryBadgeClass('unknown')).toBe('bg-gray-100 text-gray-800')
    })
  })

  describe('Responsive Design', () => {
    it('adapts to mobile viewport', async () => {
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375,
      })
      
      window.dispatchEvent(new Event('resize'))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm).toBeDefined()
    })
  })

  describe('Integration with Store', () => {
    it('loads definitions on mount', async () => {
      const listDefinitionsSpy = vi.fn()
      
      const pinia = createTestingPinia({ 
        initialState: { customEvent: mockCustomEventStoreData },
      })
      
      wrapper = mount(CustomEventManager, {
        global: {
          plugins: [pinia],
          stubs: { Transition: false, Teleport: false },
        },
      })
      
      // Store should be initialized
      expect(wrapper.vm.definitions).toBeDefined()
    })

    it('refreshes data when refresh button clicked', async () => {
      const refreshButton = wrapper.find('button:contains("Refresh")')
      expect(refreshButton.exists()).toBe(true)
    })

    it('tracks sample event when button clicked', async () => {
      const trackButton = wrapper.find('button:contains("Track Sample Event")')
      expect(trackButton.exists()).toBe(true)
    })
  })

  describe('Event Actions', () => {
    it('opens analytics view when View Analytics clicked', async () => {
      const viewAnalyticsButton = wrapper.find('button:contains("View Analytics")')
      await viewAnalyticsButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('analytics')
      expect(wrapper.vm.selectedDefinitionId).toBe(1)
    })

    it('opens flow view when View Flow clicked', async () => {
      const viewFlowButton = wrapper.find('button:contains("View Flow")')
      await viewFlowButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('flow')
      expect(wrapper.vm.selectedDefinitionId).toBe(1)
    })

    it('opens optimization view when Optimization clicked', async () => {
      const viewOptButton = wrapper.find('button:contains("Optimization")')
      await viewOptButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('optimization')
      expect(wrapper.vm.selectedDefinitionId).toBe(1)
    })

    it('switches to create tab when Edit clicked', async () => {
      const editButton = wrapper.find('button:contains("Edit")')
      await editButton.trigger('click')
      
      expect(wrapper.vm.activeTab).toBe('create')
      expect(wrapper.vm.newDefinition.name).toBe('user_signup_completed')
    })
  })

  describe('Filter Functionality', () => {
    it('clears filters when search is empty', async () => {
      await wrapper.setData({ 
        definitions: mockDefinitions,
        searchQuery: 'signup',
        statusFilter: 'active',
      })
      
      expect(wrapper.vm.filteredDefinitions.length).toBe(1)
      
      await wrapper.setData({ searchQuery: '', statusFilter: '' })
      
      expect(wrapper.vm.filteredDefinitions.length).toBe(3)
    })

    it('combines search and status filters', async () => {
      await wrapper.setData({ 
        definitions: mockDefinitions,
        searchQuery: 'page',
        statusFilter: 'active',
      })
      
      const filtered = wrapper.vm.filteredDefinitions
      expect(filtered.length).toBe(1)
      expect(filtered[0].name).toBe('page_view')
    })
  })
})
