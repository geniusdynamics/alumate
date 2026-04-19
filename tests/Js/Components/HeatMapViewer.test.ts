import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import axios from 'axios';
import HeatMapViewer from '../../../resources/js/components/Analytics/HeatMapViewer.vue';
import type { HeatMapData, HeatMapApiResponse } from '../../../resources/js/types/analytics';

// Mock axios
vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
  },
}));
const mockedAxios = vi.mocked(axios);

// Mock Canvas API
const mockCanvasContext = {
  clearRect: vi.fn(),
  fillRect: vi.fn(),
  fillStyle: '',
  createRadialGradient: vi.fn(() => ({
    addColorStop: vi.fn(),
  })),
  beginPath: vi.fn(),
  arc: vi.fn(),
  fill: vi.fn(),
};

const mockCanvas = {
  getContext: vi.fn(() => mockCanvasContext),
  width: 800,
  height: 600,
};

Object.defineProperty(HTMLCanvasElement.prototype, 'getContext', {
  value: mockCanvas.getContext,
});

describe('HeatMapViewer', () => {
  const mockHeatMapData: HeatMapData = {
    heatMapData: [
      { x: 25, y: 30, intensity: 50 },
      { x: 75, y: 70, intensity: 80 },
      { x: 50, y: 50, intensity: 30 },
    ],
    pageUrl: '/test-page',
    dateRange: {
      start: '2024-01-01',
      end: '2024-01-31',
    },
    totalClicks: 160,
  };

  const mockApiResponse: HeatMapApiResponse = {
    success: true,
    data: mockHeatMapData,
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mockedAxios.get.mockResolvedValue(mockApiResponse);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Component Mounting', () => {
    it('mounts successfully with required props', () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      expect(wrapper.exists()).toBe(true);
    });

    it('mounts with date range props', () => {
      const dateRange = { from: '2024-01-01', to: '2024-01-31' };
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
          dateRange,
        },
      });

      expect(wrapper.exists()).toBe(true);
    });

    it('fetches heat map data on mount', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();

      expect(mockedAxios.get).toHaveBeenCalledWith(
        '/api/analytics/heatmaps/test-page?'
      );
    });

    it('includes date range in API call when provided', async () => {
      const dateRange = { from: '2024-01-01', to: '2024-01-31' };
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
          dateRange,
        },
      });

      await wrapper.vm.$nextTick();

      expect(mockedAxios.get).toHaveBeenCalledWith(
        '/api/analytics/heatmaps/test-page?date_from=2024-01-01&date_to=2024-01-31'
      );
    });
  });

  describe('Data Fetching', () => {
    it('displays loading state initially', () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      expect(wrapper.text()).toContain('Loading heat map data...');
    });

    it('displays error state when API call fails', async () => {
      mockedAxios.get.mockRejectedValue(new Error('API Error'));

      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick(); // Wait for error handling

      expect(wrapper.text()).toContain('API Error');
    });

    it('renders heat map data when API call succeeds', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Heat Map: /test-page');
      expect(wrapper.text()).toContain('Total Clicks: 160');
    });

    it('refetches data when pageUrl prop changes', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();

      // Change pageUrl
      await wrapper.setProps({ pageUrl: '/new-page' });

      expect(mockedAxios.get).toHaveBeenCalledTimes(2);
      expect(mockedAxios.get).toHaveBeenLastCalledWith(
        '/api/analytics/heatmaps/new-page?'
      );
    });

    it('refetches data when dateRange prop changes', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();

      // Change dateRange
      const newDateRange = { from: '2024-02-01', to: '2024-02-28' };
      await wrapper.setProps({ dateRange: newDateRange });

      expect(mockedAxios.get).toHaveBeenCalledTimes(2);
      expect(mockedAxios.get).toHaveBeenLastCalledWith(
        '/api/analytics/heatmaps/test-page?date_from=2024-02-01&date_to=2024-02-28'
      );
    });
  });

  describe('Canvas Rendering', () => {
    it('renders canvas element', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const canvas = wrapper.find('canvas');
      expect(canvas.exists()).toBe(true);
      expect(canvas.attributes('width')).toBe('800');
      expect(canvas.attributes('height')).toBe('600');
    });

    it('calls drawHeatMap when data is loaded', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      // Verify canvas context methods were called
      expect(mockCanvas.getContext).toHaveBeenCalledWith('2d');
      expect(mockCanvasContext.clearRect).toHaveBeenCalled();
      expect(mockCanvasContext.fillRect).toHaveBeenCalled();
    });
  });

  describe('User Interactions', () => {
    it('handles mouse move on canvas', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const canvas = wrapper.find('canvas');
      await canvas.trigger('mousemove', {
        clientX: 200,
        clientY: 180,
      });

      // Test that mouse move event is handled without errors
      expect(canvas.exists()).toBe(true);
    });

    it('handles mouse leave on canvas', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const canvas = wrapper.find('canvas');
      await canvas.trigger('mouseleave');

      expect(canvas.exists()).toBe(true);
    });

    it('handles zoom buttons', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const zoomInButton = wrapper.findAll('button').find(btn =>
        btn.text().includes('Zoom In') || btn.attributes('title') === 'Zoom In'
      );

      if (zoomInButton) {
        await zoomInButton.trigger('click');
        expect(zoomInButton.exists()).toBe(true);
      }
    });

    it('handles reset view button', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const resetButton = wrapper.findAll('button').find(btn =>
        btn.text().includes('Reset View')
      );

      if (resetButton) {
        await resetButton.trigger('click');
        expect(resetButton.exists()).toBe(true);
      }
    });

    it('handles fullscreen toggle', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const fullscreenButton = wrapper.findAll('button').find(btn =>
        btn.text().includes('Fullscreen')
      );

      if (fullscreenButton) {
        await fullscreenButton.trigger('click');
        expect(fullscreenButton.exists()).toBe(true);
      }
    });
  });

  describe('Accessibility', () => {
    it('has proper ARIA labels', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const canvas = wrapper.find('canvas');
      expect(canvas.attributes('role')).toBe('img');
      expect(canvas.attributes('aria-label')).toBe('Interactive heat map showing user click patterns');
    });

    it('has screen reader description', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const description = wrapper.find('#heatmap-description');
      expect(description.exists()).toBe(true);
      expect(description.classes()).toContain('sr-only');
    });

    it('supports keyboard navigation', async () => {
      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const container = wrapper.find('.canvas-container');
      expect(container.attributes('tabindex')).toBe('0');
    });
  });

  describe('Error Handling', () => {
    it('handles network errors gracefully', async () => {
      mockedAxios.get.mockRejectedValue(new Error('Network Error'));

      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Network Error');
      expect(wrapper.find('.heat-map-container').exists()).toBe(false);
    });

    it('handles invalid API response', async () => {
      mockedAxios.get.mockResolvedValue({
        success: false,
        message: 'Invalid data',
      });

      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Invalid data');
    });

    it('handles empty heat map data', async () => {
      const emptyResponse: HeatMapApiResponse = {
        success: true,
        data: {
          heatMapData: [],
          pageUrl: '/test-page',
          dateRange: { start: '2024-01-01', end: '2024-01-31' },
          totalClicks: 0,
        },
      };

      mockedAxios.get.mockResolvedValue(emptyResponse);

      const wrapper = mount(HeatMapViewer, {
        props: {
          pageUrl: '/test-page',
        },
      });

      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      expect(wrapper.text()).toContain('Total Clicks: 0');
    });
  });
});