import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Analytics from '@/Pages/SuperAdmin/Analytics.vue';

const mockAnalytics = {
  platform_benchmarks: [
    { institution_id: 'tenant1', employment_rate: 85, average_salary: 60000 },
    { institution_id: 'tenant2', employment_rate: 75, average_salary: 55000 },
  ],
  market_trends: {
    top_skills: [{ skill: 'PHP', count: 50 }],
    top_industries: [{ industry: 'Technology', jobs_count: 100 }],
  },
};

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('SuperAdmin/Analytics.vue', () => {
  it('renders the platform benchmarking and market trends cards', async () => {
    const wrapper = mount(Analytics, {
      props: {
        analytics: mockAnalytics,
      },
      global: {
        stubs: {
            ...mockInertiaComponents,
            AdminLayout: { template: '<div><slot /></div>' }
        },
        mocks: {
          route: () => '/',
        },
      },
    });

    // Check for Benchmarking Card
    expect(wrapper.text()).toContain('Platform Benchmarking');
    expect(wrapper.text()).toContain('Institution 1');
    expect(wrapper.text()).toContain('85%');

    // Check for Market Trends Card
    expect(wrapper.text()).toContain('Top In-Demand Skills');
    expect(wrapper.text()).toContain('PHP');
    expect(wrapper.text()).toContain('50 mentions');
    expect(wrapper.text()).toContain('Top Hiring Industries');
    expect(wrapper.text()).toContain('Technology');
    expect(wrapper.text()).toContain('100 jobs');
  });
});
