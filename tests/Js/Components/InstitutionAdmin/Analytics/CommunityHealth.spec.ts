import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import CommunityHealth from '@/Pages/InstitutionAdmin/Analytics/CommunityHealth.vue';
import axios from 'axios';

// Mock axios
vi.mock('axios');

const mockHealthData = {
  daily_active_users: [{ date: '2025-08-17', count: 100 }],
  post_activity: [{ date: '2025-08-17', count: 20 }],
  engagement_trends: [],
  group_participation: [],
  events_attended: 50,
  connections_made: 75,
};

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('CommunityHealth.vue', () => {
  it('fetches data and renders the cards correctly', async () => {
    (axios.get as any).mockResolvedValue({ data: mockHealthData });

    const wrapper = mount(CommunityHealth, {
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
    expect(wrapper.text()).toContain('Daily Active Users');
    expect(wrapper.text()).toContain('Post Activity');
    expect(wrapper.text()).toContain('New Connections');

    // Check for some of the new data
    expect(wrapper.text()).toContain('100');
    expect(wrapper.text()).toContain('20');
    expect(wrapper.text()).toContain('75');
  });
});
