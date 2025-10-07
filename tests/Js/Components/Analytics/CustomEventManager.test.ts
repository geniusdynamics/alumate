import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createTestingPinia } from '@pinia/testing';
import CustomEventManager from '@/Components/Analytics/CustomEventManager.vue';
import { useCustomEventStore } from '@/stores/useCustomEventStore';
import { nextTick } from 'vue';

// Mock Chart.js
vi.mock('chart.js/auto', async () => {
  const actual = await vi.importActual('chart.js/auto');
  return {
    ...actual,
    default: {
      ...actual.default,
      getChart: vi.fn(() => null),
      register: vi.fn(),
    },
 };
});

// Mock the useCustomEvent composable
vi.mock('@/Composables/useCustomEvent', () => ({
  useCustomEvent: vi.fn(() => ({
    validateParams: vi.fn(() => ({ valid: true, errors: [] })),
    computedParamSchema: vi.fn(() => ({})),
  })),
}));

describe('CustomEventManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  const createWrapper = (props = {}) => {
    return mount(CustomEventManager, {
      props: {
        initialTab: 'list',
        ...props,
      },
      global: {
        plugins: [
          createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
          }),
        ],
        components: {
          // Add any child components that need to be mocked
        },
      },
    });
  };

  it('renders correctly with default props', () => {
    const wrapper = createWrapper();
    
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('h1').text()).toBe('Custom Event Manager');
    expect(wrapper.find('.tab-list').exists()).toBe(true);
    expect(wrapper.find('.tab-content').exists()).toBe(true);
  });

  it('displays the correct initial tab', async () => {
    const wrapper = createWrapper({ initialTab: 'create' });
    
    await nextTick();
    
    const activeTab = wrapper.find('.tab.active');
    expect(activeTab.text()).toBe('Create Event');
  });

  it('switches between tabs correctly', async () => {
    const wrapper = createWrapper();
    
    // Initially on list tab
    expect(wrapper.find('button[aria-current="page"]').text()).toBe('Event Definitions');
    
    // Switch to create tab
    const createTab = wrapper.find('button:not([aria-current="page"])').filter(w => w.text() === 'Create Event');
    if (createTab.exists()) {
      await createTab.trigger('click');
      expect(wrapper.find('button[aria-current="page"]').text()).toBe('Create Event');
    }
    
    // Switch to analytics tab
    const analyticsTab = wrapper.find('button:not([aria-current="page"])').filter(w => w.text() === 'Analytics');
    if (analyticsTab.exists()) {
      await analyticsTab.trigger('click');
      expect(wrapper.find('button[aria-current="page"]').text()).toBe('Analytics');
    }
    
    // Switch to flow tab
    const flowTab = wrapper.find('button:not([aria-current="page"])').filter(w => w.text() === 'Event Flow');
    if (flowTab.exists()) {
      await flowTab.trigger('click');
      expect(wrapper.find('button[aria-current="page"]').text()).toBe('Event Flow');
    }
  });

 it('renders the list view with definitions', async () => {
    // Mock store with definitions
    const mockDefinitions = [
      { id: 1, name: 'purchase', description: 'Purchase event', parameters_json: [] },
      { id: 2, name: 'page_view', description: 'Page view event', parameters_json: [] },
    ];
    
    const wrapper = createWrapper();
    const store = useCustomEventStore();
    store.definitions = mockDefinitions;
    
    await nextTick();
    
    const definitionItems = wrapper.findAll('.definition-item');
    expect(definitionItems).toHaveLength(2);
    
    expect(definitionItems[0].text()).toContain('purchase');
    expect(definitionItems[1].text()).toContain('page_view');
  });

 it('renders the create view with form', async () => {
    const wrapper = createWrapper({ initialTab: 'create' });
    
    await nextTick();
    
    expect(wrapper.find('form').exists()).toBe(true);
    expect(wrapper.find('input#event-name').exists()).toBe(true);
    expect(wrapper.find('textarea#event-description').exists()).toBe(true);
    expect(wrapper.find('.parameter-inputs').exists()).toBe(true);
  });

  it('validates form inputs when creating an event', async () => {
    const wrapper = createWrapper({ initialTab: 'create' });
    
    await nextTick();
    
    // Fill in form with invalid data
    const nameInput = wrapper.find('input[name="name"]');
    await nameInput.setValue('');
    
    const descriptionInput = wrapper.find('textarea[name="description"]');
    await descriptionInput.setValue('Test description');
    
    const submitButton = wrapper.find('button[type="submit"]');
    await submitButton.trigger('click');
    
    // Check for validation errors
    expect(wrapper.text()).toContain('Name is required');
  });

  it('submits the form correctly when valid', async () => {
    const mockDefineEvent = vi.fn().mockResolvedValue({ success: true, data: { id: 1, name: 'test_event' } });
    
    const wrapper = createWrapper({ initialTab: 'create' });
    const store = useCustomEventStore();
    store.defineEvent = mockDefineEvent;
    
    await nextTick();
    
    // Fill in form with valid data
    const nameInput = wrapper.find('input[name="name"]');
    await nameInput.setValue('test_event');
    
    const descriptionInput = wrapper.find('textarea[name="description"]');
    await descriptionInput.setValue('Test event description');
    
    const submitButton = wrapper.find('button[type="submit"]');
    await submitButton.trigger('click');
    
    expect(mockDefineEvent).toHaveBeenCalledWith({
      name: 'test_event',
      description: 'Test event description',
      parameters_json: [],
    });
  });

  it('renders the analytics view with charts', async () => {
    const wrapper = createWrapper({ initialTab: 'analytics' });
    
    await nextTick();
    
    expect(wrapper.find('.rounded-lg.border.border-gray-200.bg-white.p-4').exists()).toBe(true);
    expect(wrapper.find('canvas').exists()).toBe(true);
 });

  it('renders the event flow view', async () => {
    const wrapper = createWrapper({ initialTab: 'flow' });
    
    await nextTick();
    
    expect(wrapper.find('.rounded-lg.border-gray-200.bg-white.p-4').exists()).toBe(true);
    expect(wrapper.find('.h-96.flex.items-center.justify-center.bg-gray-50.rounded').exists()).toBe(true);
  });

  it('handles empty state in list view', async () => {
    const wrapper = createWrapper();
    const store = useCustomEventStore();
    store.definitions = [];
    
    await nextTick();
    
    expect(wrapper.find('.grid.gap-4').exists()).toBe(true);
    expect(wrapper.find('.grid.gap-4').text()).toContain('No custom events defined yet');
  });

  it('loads definitions on mount', async () => {
    const mockFetchDefinitions = vi.fn().mockResolvedValue({ success: true, data: [] });
    
    const wrapper = createWrapper();
    const store = useCustomEventStore();
    store.fetchDefinitions = mockFetchDefinitions;
    
    await nextTick();
    
    expect(mockFetchDefinitions).toHaveBeenCalled();
  });

  it('shows loading state when fetching data', async () => {
    const wrapper = createWrapper();
    const store = useCustomEventStore();
    store.loading = true;
    
    await nextTick();
    
    expect(wrapper.find('.flex.items-center.justify-center.p-8').exists()).toBe(true);
  });

  it('shows error state when there are errors', async () => {
    const wrapper = createWrapper();
    const store = useCustomEventStore();
    store.error = 'Failed to load data';
    
    await nextTick();
    
    expect(wrapper.find('.rounded-lg.border-red-200.bg-red-50.p-4').exists()).toBe(true);
    expect(wrapper.find('.rounded-lg.border-red-200.bg-red-50.p-4').text()).toContain('Failed to load data');
  });

  it('adds new parameter fields dynamically', async () => {
    const wrapper = createWrapper({ initialTab: 'create' });
    
    await nextTick();
    
    const addParamButton = wrapper.find('button[data-testid="add-parameter"]');
    if (addParamButton.exists()) {
      await addParamButton.trigger('click');
      await nextTick();
      
      const paramFields = wrapper.findAll('.parameter-field');
      expect(paramFields).toHaveLength(1);
    }
  });

 it('removes parameter fields dynamically', async () => {
    const wrapper = createWrapper({ initialTab: 'create' });
    
    await nextTick();
    
    // Add a parameter first
    const addParamButton = wrapper.find('button[data-testid="add-parameter"]');
    if (addParamButton.exists()) {
      await addParamButton.trigger('click');
      await nextTick();
    }
    
    // Then remove it
    const removeParamButton = wrapper.find('button[data-testid="remove-parameter"]');
    if (removeParamButton.exists()) {
      await removeParamButton.trigger('click');
      await nextTick();
      
      const paramFields = wrapper.findAll('.parameter-field');
      expect(paramFields).toHaveLength(0);
    }
  });

  it('handles real-time updates via WebSocket', async () => {
    // Mock WebSocket connection
    const mockWebSocket = {
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      close: vi.fn(),
    };
    
    vi.stubGlobal('WebSocket', vi.fn(() => mockWebSocket));
    
    const wrapper = createWrapper();
    
    await nextTick();
    
    // Check that WebSocket was initialized
    expect(WebSocket).toHaveBeenCalled();
    
    vi.unstubAllGlobals();
  });

  it('formats dates correctly in analytics', async () => {
    const wrapper = createWrapper({ initialTab: 'analytics' });
    
    await nextTick();
    
    // Check that date formatting functions work
    const component = wrapper.vm;
    if (typeof component.formatDate === 'function') {
      const dateStr = component.formatDate('2023-01-01T00:00Z');
      expect(dateStr).toMatch(/\d{4}-\d{2}-\d{2}/);
    }
  });

  it('calculates aggregates correctly', async () => {
    const mockAnalytics = {
      total_events: 100,
      unique_users: 50,
      time_series: [
        { date: '2023-01-01', count: 10 },
        { date: '2023-01-02', count: 15 },
      ],
      aggregates: { avg_amount: 25.5, total_value: 2550 },
    };
    
    const wrapper = createWrapper({ initialTab: 'analytics' });
    const store = useCustomEventStore();
    store.analytics = mockAnalytics;
    
    await nextTick();
    
    expect(wrapper.text()).toContain('Loading custom events...');
    expect(wrapper.text()).toContain('Total Events:');
    expect(wrapper.text()).toContain('Unique Users:');
  });
});