import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Analytics from '@/Pages/InstitutionAdmin/Analytics.vue';

const mockAnalytics = {
  graduatesByYear: [],
  employmentRates: [],
  salaryRanges: [],
  topEmployers: [],
  courseOutcomes: [],
  jobApplicationTrends: [],
  timeToEmployment: {
    average_days: 90,
    median_days: 80,
    under_6_months_percentage: 75,
  },
  salaryProgression: {
    year_1: { average: 50000 },
    year_3: { average: 70000 },
    year_5: { average: 90000 },
  },
  employmentByLocation: [
    { location: 'New York, NY', count: 150 },
    { location: 'San Francisco, CA', count: 120 },
  ],
};

// Mock the Head and Link components from Inertia
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('Analytics.vue', () => {
  it('renders the new graduate outcome cards', () => {
    const wrapper = mount(Analytics, {
      props: {
        analytics: mockAnalytics,
      },
      global: {
        stubs: {
            ...mockInertiaComponents,
        },
        mocks: {
          route: () => '/',
          $page: {
            props: {
              auth: {
                user: { name: 'Test Admin' }
              }
            }
          }
        },
      },
    });

    // Check for the new card titles
    expect(wrapper.text()).toContain('Time to Employment');
    expect(wrapper.text()).toContain('Salary Progression');
    expect(wrapper.text()).toContain('Employment by Location');

    // Check for some of the new data
    expect(wrapper.text()).toContain('Average: 90 days');
    expect(wrapper.text()).toContain('1 Year Avg: $50,000');
    expect(wrapper.text()).toContain('New York, NY');
    expect(wrapper.text()).toContain('150');
  });
});
