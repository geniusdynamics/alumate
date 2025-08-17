import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import CourseROI from '@/Pages/InstitutionAdmin/Analytics/CourseROI.vue';
import axios from 'axios';

// Mock axios
vi.mock('axios');

const mockRoiData = [
  {
    course_name: 'Computer Science',
    total_graduates: 50,
    average_salary: 85000,
    estimated_roi_percentage: 240,
  },
  {
    course_name: 'Business Administration',
    total_graduates: 75,
    average_salary: 72000,
    estimated_roi_percentage: 188,
  },
];

// Mock Inertia components
const mockInertiaComponents = {
    Head: { template: '<div></div>' },
    Link: { template: '<a><slot /></a>' }
};

describe('CourseROI.vue', () => {
  it('fetches data and renders the table correctly', async () => {
    (axios.get as any).mockResolvedValue({ data: mockRoiData });

    const wrapper = mount(CourseROI, {
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
    await wrapper.vm.$nextTick(); // Wait for re-render

    // Check that loading text is gone
    expect(wrapper.text()).not.toContain('Loading ROI data...');

    // Check table headers
    expect(wrapper.text()).toContain('Course');
    expect(wrapper.text()).toContain('Average Salary');
    expect(wrapper.text()).toContain('Estimated 5-Year ROI');

    // Check table rows
    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
    expect(rows[0].text()).toContain('Computer Science');
    expect(rows[0].text()).toContain('$85,000');
    expect(rows[0].text()).toContain('240%');
    expect(rows[1].text()).toContain('Business Administration');
    expect(rows[1].text()).toContain('188%');
  });

   it('shows a message when no data is available', async () => {
    (axios.get as any).mockResolvedValue({ data: [] });

    const wrapper = mount(CourseROI, {
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

    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('No course ROI data available yet.');
  });
});
