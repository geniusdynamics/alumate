import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import GamificationDashboard from '../../../resources/js/components/Analytics/GamificationDashboard.vue';
import type { GamificationMetrics, LeaderboardEntry, ActivityTimelineEntry } from '../../../resources/js/types/gamification';

// Mock the gamification store
const mockMetrics: GamificationMetrics = {
  totalEvents: 1250,
  uniqueUsers: 89,
  totalPointsAwarded: 15420,
  totalBadgesEarned: 234,
  period: 'weekly',
};

const mockLeaderboard: LeaderboardEntry[] = [
  { rank: 1, userId: 'user1', totalPoints: 1250, totalEvents: 45 },
  { rank: 2, userId: 'user2', totalPoints: 1180, totalEvents: 42 },
  { rank: 3, userId: 'user3', totalPoints: 1100, totalEvents: 38 },
  { rank: 4, userId: 'user4', totalPoints: 1050, totalEvents: 35 },
  { rank: 5, userId: 'user5', totalPoints: 980, totalEvents: 32 },
];

const mockActivityTimeline: ActivityTimelineEntry[] = [
  { period: '2024-01-01', totalEvents: 45, uniqueUsers: 12, pointsAwarded: 1250 },
  { period: '2024-01-02', totalEvents: 52, uniqueUsers: 15, pointsAwarded: 1380 },
  { period: '2024-01-03', totalEvents: 38, uniqueUsers: 10, pointsAwarded: 980 },
  { period: '2024-01-04', totalEvents: 61, uniqueUsers: 18, pointsAwarded: 1520 },
  { period: '2024-01-05', totalEvents: 49, uniqueUsers: 14, pointsAwarded: 1180 },
  { period: '2024-01-06', totalEvents: 55, uniqueUsers: 16, pointsAwarded: 1420 },
  { period: '2024-01-07', totalEvents: 43, uniqueUsers: 13, pointsAwarded: 1100 },
];

// Large dataset for performance testing
const largeLeaderboard: LeaderboardEntry[] = Array.from({ length: 1000 }, (_, i) => ({
  rank: i + 1,
  userId: `user${i + 1}`,
  totalPoints: Math.floor(Math.random() * 2000) + 500,
  totalEvents: Math.floor(Math.random() * 50) + 10,
}));

const mockGamificationStore = {
  isLoading: false,
  hasError: false,
  error: null,
  metrics: mockMetrics,
  leaderboard: mockLeaderboard,
  activityTimeline: mockActivityTimeline,

  fetchMetrics: vi.fn().mockResolvedValue(undefined),
  fetchLeaderboard: vi.fn().mockResolvedValue(undefined),
  fetchActivityTimeline: vi.fn().mockResolvedValue(undefined),
  clearError: vi.fn(),
};

// Mock the useGamificationStore composable
vi.mock('../../../resources/js/stores/gamification', () => ({
  useGamificationStore: () => mockGamificationStore,
}));

describe('GamificationDashboard', () => {
  let wrapper: any;

  beforeEach(() => {
    setActivePinia(createPinia());

    // Reset mocks
    vi.clearAllMocks();
    mockGamificationStore.isLoading = false;
    mockGamificationStore.hasError = false;
    mockGamificationStore.error = null;
    mockGamificationStore.metrics = mockMetrics;
    mockGamificationStore.leaderboard = mockLeaderboard;
    mockGamificationStore.activityTimeline = mockActivityTimeline;
  });

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount();
    }
    vi.restoreAllMocks();
  });

  describe('Component Mounting Performance', () => {
    it('mounts within performance threshold (<100ms)', async () => {
      const startTime = performance.now();

      wrapper = mount(GamificationDashboard);

      const mountTime = performance.now() - startTime;

      expect(mountTime).toBeLessThan(100);
      expect(wrapper.exists()).toBe(true);
    });

    it('mounts with large leaderboard dataset within performance threshold', async () => {
      mockGamificationStore.leaderboard = largeLeaderboard;

      const startTime = performance.now();

      wrapper = mount(GamificationDashboard);

      const mountTime = performance.now() - startTime;

      expect(mountTime).toBeLessThan(100);
      expect(wrapper.exists()).toBe(true);
    });
  });

  describe('Component Update Performance', () => {
    it('updates within performance threshold (<100ms)', async () => {
      wrapper = mount(GamificationDashboard);

      const startTime = performance.now();

      // Trigger reactive update
      mockGamificationStore.metrics = { ...mockMetrics, totalEvents: 1500 };
      await wrapper.vm.$nextTick();

      const updateTime = performance.now() - startTime;

      expect(updateTime).toBeLessThan(100);
    });

    it('updates with large dataset within performance threshold', async () => {
      wrapper = mount(GamificationDashboard);

      const startTime = performance.now();

      // Update with large leaderboard
      mockGamificationStore.leaderboard = largeLeaderboard;
      await wrapper.vm.$nextTick();

      const updateTime = performance.now() - startTime;

      expect(updateTime).toBeLessThan(100);
    });
  });

  describe('Memory Usage', () => {
    it('maintains memory usage under 5MB during operations', async () => {
      const initialMemory = (performance as any).memory?.usedJSHeapSize || 0;

      wrapper = mount(GamificationDashboard);

      // Perform operations that might allocate memory
      mockGamificationStore.leaderboard = largeLeaderboard;
      await wrapper.vm.$nextTick();

      // Trigger multiple updates
      for (let i = 0; i < 10; i++) {
        mockGamificationStore.metrics = { ...mockMetrics, totalEvents: mockMetrics.totalEvents + i };
        await wrapper.vm.$nextTick();
      }

      const finalMemory = (performance as any).memory?.usedJSHeapSize || 0;
      const memoryIncrease = finalMemory - initialMemory;

      // Convert bytes to MB
      const memoryIncreaseMB = memoryIncrease / (1024 * 1024);

      expect(memoryIncreaseMB).toBeLessThan(5);
    });
  });

  describe('Data Fetching on Mount', () => {
    it('fetches all required data on component mount', async () => {
      wrapper = mount(GamificationDashboard);

      // Wait for next tick to allow async operations
      await wrapper.vm.$nextTick();

      expect(mockGamificationStore.fetchMetrics).toHaveBeenCalledTimes(1);
      expect(mockGamificationStore.fetchLeaderboard).toHaveBeenCalledTimes(1);
      expect(mockGamificationStore.fetchActivityTimeline).toHaveBeenCalledTimes(1);
    });

    it('displays loading state initially', () => {
      mockGamificationStore.isLoading = true;

      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('Loading gamification data...');
    });

    it('displays error state when data fetching fails', () => {
      mockGamificationStore.hasError = true;
      mockGamificationStore.error = 'Failed to load data';

      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('Failed to load data');
      expect(wrapper.find('.border-red-200').exists()).toBe(true);
    });
  });

  describe('Data Rendering', () => {
    it('renders metrics overview correctly', () => {
      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('Total Events');
      expect(wrapper.text()).toContain('1,250');
      expect(wrapper.text()).toContain('Active Users');
      expect(wrapper.text()).toContain('89');
      expect(wrapper.text()).toContain('Points Awarded');
      expect(wrapper.text()).toContain('15,420');
      expect(wrapper.text()).toContain('Badges Earned');
      expect(wrapper.text()).toContain('234');
    });

    it('renders leaderboard correctly', () => {
      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('Leaderboard');
      expect(wrapper.text()).toContain('Top performers by points');

      // Check if leaderboard entries are rendered
      const leaderboardItems = wrapper.findAll('.flex.items-center.justify-between');
      expect(leaderboardItems.length).toBeGreaterThan(0);
    });

    it('renders activity timeline correctly', () => {
      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('Activity Timeline');
      expect(wrapper.text()).toContain('Gamification events over time');

      // Check if timeline entries are rendered (last 7 days)
      const timelineItems = wrapper.findAll('.flex.items-center.justify-between');
      expect(timelineItems.length).toBeGreaterThan(0);
    });

    it('handles empty leaderboard gracefully', () => {
      mockGamificationStore.leaderboard = [];

      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('No leaderboard data available');
    });

    it('handles empty activity timeline gracefully', () => {
      mockGamificationStore.activityTimeline = [];

      wrapper = mount(GamificationDashboard);

      expect(wrapper.text()).toContain('No activity data available');
    });
  });

  describe('Accessibility Performance', () => {
    it('includes screen reader description', () => {
      wrapper = mount(GamificationDashboard);

      const srDescription = wrapper.find('#gamification-description');
      expect(srDescription.exists()).toBe(true);
      expect(srDescription.classes()).toContain('sr-only');
      expect(srDescription.text()).toContain('Gamification analytics dashboard');
    });

    it('has proper heading structure', () => {
      wrapper = mount(GamificationDashboard);

      const headings = wrapper.findAll('h2, h3');
      expect(headings.length).toBeGreaterThan(0);

      // Check main heading
      const mainHeading = wrapper.find('h2');
      expect(mainHeading.text()).toBe('Gamification Analytics');
    });

    it('loads fast enough for accessibility compliance', async () => {
      const startTime = performance.now();

      wrapper = mount(GamificationDashboard);

      // Wait for data to load
      await wrapper.vm.$nextTick();
      await wrapper.vm.$nextTick();

      const loadTime = performance.now() - startTime;

      // WCAG recommends interfaces be usable within reasonable time
      // Fast loads contribute to better accessibility
      expect(loadTime).toBeLessThan(100);
    });
  });

  describe('Error Handling', () => {
    it('handles error dismissal', async () => {
      mockGamificationStore.hasError = true;
      mockGamificationStore.error = 'Test error';

      wrapper = mount(GamificationDashboard);

      const dismissButton = wrapper.find('button');
      expect(dismissButton.exists()).toBe(true);

      await dismissButton.trigger('click');

      expect(mockGamificationStore.clearError).toHaveBeenCalledTimes(1);
    });
  });

  describe('Large Dataset Performance', () => {
    it('renders 1000 leaderboard items within time limits', async () => {
      mockGamificationStore.leaderboard = largeLeaderboard;

      const startTime = performance.now();

      wrapper = mount(GamificationDashboard);

      // Wait for rendering
      await wrapper.vm.$nextTick();

      const renderTime = performance.now() - startTime;

      expect(renderTime).toBeLessThan(100);
      expect(wrapper.exists()).toBe(true);
    });

    it('maintains performance with frequent updates', async () => {
      wrapper = mount(GamificationDashboard);

      const updateTimes: number[] = [];

      // Perform 50 rapid updates
      for (let i = 0; i < 50; i++) {
        const startTime = performance.now();

        mockGamificationStore.metrics = {
          ...mockMetrics,
          totalEvents: mockMetrics.totalEvents + i,
          totalPointsAwarded: mockMetrics.totalPointsAwarded + (i * 10),
        };

        await wrapper.vm.$nextTick();

        const updateTime = performance.now() - startTime;
        updateTimes.push(updateTime);
      }

      const avgUpdateTime = updateTimes.reduce((a, b) => a + b, 0) / updateTimes.length;
      const maxUpdateTime = Math.max(...updateTimes);

      expect(avgUpdateTime).toBeLessThan(50);
      expect(maxUpdateTime).toBeLessThan(100);
    });
  });

  describe('Performance Thresholds Summary', () => {
    it('meets all performance requirements', async () => {
      // Test mount performance
      const mountStart = performance.now();
      wrapper = mount(GamificationDashboard);
      const mountTime = performance.now() - mountStart;

      // Test update performance
      const updateStart = performance.now();
      mockGamificationStore.metrics = { ...mockMetrics, totalEvents: 2000 };
      await wrapper.vm.$nextTick();
      const updateTime = performance.now() - updateStart;

      // Test large dataset performance
      const largeDataStart = performance.now();
      mockGamificationStore.leaderboard = largeLeaderboard;
      await wrapper.vm.$nextTick();
      const largeDataTime = performance.now() - largeDataStart;

      // Assert all thresholds are met
      expect(mountTime).toBeLessThan(100);
      expect(updateTime).toBeLessThan(100);
      expect(largeDataTime).toBeLessThan(100);

      // Memory check (if available)
      if ((performance as any).memory) {
        const memoryUsage = (performance as any).memory.usedJSHeapSize / (1024 * 1024);
        expect(memoryUsage).toBeLessThan(50); // Reasonable memory usage
      }
    });
  });
});