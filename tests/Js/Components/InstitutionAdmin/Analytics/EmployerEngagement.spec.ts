import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import EmployerEngagement from '@/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue';
import axios from 'axios';

// Mock axios
vi.mock('axios');

const mockEngagementData = {
  top_engaging_employers: [
    { company_name: 'Tech Corp', jobs_posted: 10, total_hires: 5 },
  ],
  most_in_demand_skills: [
    { skill: 'JavaScript', count: 25 },
  ],
  hiring_trends_by_industry: [
    { industry: 'Technology', hires: 50 },
  ],
};

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('EmployerEngagement.vue', () => {
  it('fetches data and renders the tables correctly', async () => {
    (axios.get as any).mockResolvedValue({ data: mockEngagementData });

    const wrapper = mount(EmployerEngagement, {
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

    // Wait for the component to mount and fetch data
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    // Check for card titles
    expect(wrapper.text()).toContain('Top Engaging Employers');
    expect(wrapper.text()).toContain('Most In-Demand Skills');
    expect(wrapper.text()).toContain('Hiring Trends by Industry');

    // Check for table data
    expect(wrapper.text()).toContain('Tech Corp');
    expect(wrapper.text()).toContain('JavaScript');
    expect(wrapper.text()).toContain('Technology');
    expect(wrapper.text()).toContain('50');
  });
});
