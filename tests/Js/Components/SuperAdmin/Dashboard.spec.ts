import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Dashboard from '@/Pages/SuperAdmin/Dashboard.vue';

const mockSystemStats = {
  new_users: 15,
  new_institutions: 3,
  user_growth_data: [
    { date: '2025-08-16', count: 5 },
    { date: '2025-08-17', count: 10 },
  ],
  total_users: 100,
  pending_verifications: 5,
  total_institutions: 10,
};

const mockEmptyStats = {
    user_growth_data: []
};

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('SuperAdmin/Dashboard.vue', () => {
  it('renders the system growth chart and updated KPI cards', async () => {
    const wrapper = mount(Dashboard, {
      props: {
        systemStats: mockSystemStats,
        institutionStats: [],
        employerStats: {},
        jobStats: { top_job_types: [] },
        recentActivity: [],
        systemHealth: {},
      },
      global: {
        stubs: {
            ...mockInertiaComponents,
            AdminLayout: { template: '<div><slot /></div>' },
            DarkStatCard: {
                props: ['title', 'value'],
                template: '<div>{{ title }}: {{ value }}</div>'
            }
        },
        mocks: {
          route: () => '/',
        },
      },
    });

    // Check for the new chart title
    expect(wrapper.text()).toContain('User Growth (Last 30 Days)');

    // Check for updated KPI cards
    expect(wrapper.text()).toContain('New Users (30 Days): 15');
    expect(wrapper.text()).toContain('New Institutions (30 Days): 3');
  });
});
