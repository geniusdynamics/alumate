import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Analytics from '@/Pages/SuperAdmin/Analytics.vue';

const mockAnalytics = {
  platform_benchmarks: [
    { institution_id: 'tenant1', employment_rate: 85, average_salary: 60000 },
    { institution_id: 'tenant2', employment_rate: 75, average_salary: 55000 },
  ],
};

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('SuperAdmin/Analytics.vue', () => {
  it('renders the platform benchmarking card and table', async () => {
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

    // Check for the card title
    expect(wrapper.text()).toContain('Platform Benchmarking');

    // Check for table headers
    expect(wrapper.text()).toContain('Institution (Anonymized)');
    expect(wrapper.text()).toContain('Employment Rate');
    expect(wrapper.text()).toContain('Average Salary (Year 1)');

    // Check for table data
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    expect(rows[0].text()).toContain('Institution 1');
    expect(rows[0].text()).toContain('85%');
    expect(rows[0].text()).toContain('$60,000');
    expect(rows[1].text()).toContain('Institution 2');
    expect(rows[1].text()).toContain('75%');
  });
});
