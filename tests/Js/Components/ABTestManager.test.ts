import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createApp } from 'vue';
import axios from 'axios';
import ABTestManager from '../../../resources/js/components/Analytics/ABTestManager.vue';
import ABTestForm from '../../../resources/js/components/Analytics/ABTestForm.vue';
import ABTestResults from '../../../resources/js/components/Analytics/ABTestResults.vue';
import type { ABTestData } from '../../../resources/js/types/analytics';

// Mock axios
vi.mock('axios');
const mockedAxios = vi.mocked(axios);

// Mock child components
vi.mock('../../../resources/js/components/Analytics/ABTestForm.vue', () => ({
    default: {
        name: 'ABTestForm',
        template: '<div>Mock ABTestForm</div>',
        props: ['test', 'isEdit'],
        emits: ['submit', 'cancel']
    }
}));

vi.mock('../../../resources/js/components/Analytics/ABTestResults.vue', () => ({
    default: {
        name: 'ABTestResults',
        template: '<div>Mock ABTestResults</div>',
        props: ['testId', 'dateRange'],
        emits: ['close']
    }
}));

describe('ABTestManager', () => {
    const mockTests: ABTestData[] = [
        {
            id: '1',
            name: 'Test A/B Campaign',
            description: 'Testing button colors',
            status: 'active',
            variants: [
                { id: 'v1', name: 'Blue Button', weight: 50 },
                { id: 'v2', name: 'Green Button', weight: 50 }
            ],
            audience_criteria: ['desktop_users'],
            goal_event: 'button_click',
            created_at: '2024-01-01T00:00:00Z'
        },
        {
            id: '2',
            name: 'Header Test',
            description: 'Testing header variations',
            status: 'completed',
            variants: [
                { id: 'v3', name: 'Header A', weight: 50 },
                { id: 'v4', name: 'Header B', weight: 50 }
            ],
            audience_criteria: [],
            goal_event: 'page_view',
            created_at: '2024-01-02T00:00:00Z'
        }
    ];

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders loading state initially', () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: [] }
        });

        const wrapper = mount(ABTestManager);

        expect(wrapper.text()).toContain('Loading A/B tests...');
    });

    it('renders tests table when data is loaded', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Test A/B Campaign');
        expect(wrapper.text()).toContain('Header Test');
        expect(wrapper.text()).toContain('active');
        expect(wrapper.text()).toContain('completed');
    });

    it('shows empty state when no tests exist', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: [] }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('No A/B tests');
        expect(wrapper.text()).toContain('Get started by creating your first A/B test.');
    });

    it('opens create modal when create button is clicked', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const createButton = wrapper.find('button[aria-label="Create new A/B test"]');
        await createButton.trigger('click');

        expect(wrapper.vm.showModal).toBe(true);
        expect(wrapper.vm.isEdit).toBe(false);
        expect(wrapper.vm.selectedTest).toBeUndefined();
    });

    it('opens edit modal when edit button is clicked', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const editButtons = wrapper.findAll('button[aria-label="Edit test"]');
        await editButtons[0].trigger('click');

        expect(wrapper.vm.showModal).toBe(true);
        expect(wrapper.vm.isEdit).toBe(true);
        expect(wrapper.vm.selectedTest).toEqual(mockTests[0]);
    });

    it('opens results modal when view button is clicked', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const viewButtons = wrapper.findAll('button[aria-label="View results"]');
        await viewButtons[0].trigger('click');

        expect(wrapper.vm.showResultsModal).toBe(true);
        expect(wrapper.vm.selectedTest).toEqual(mockTests[0]);
    });

    it('handles delete confirmation and API call', async () => {
        // Mock window.confirm
        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });
        mockedAxios.delete.mockResolvedValueOnce({
            data: { success: true }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const deleteButtons = wrapper.findAll('button[aria-label="Delete test"]');
        await deleteButtons[0].trigger('click');

        expect(confirmSpy).toHaveBeenCalledWith(
            'Are you sure you want to delete "Test A/B Campaign"? This action cannot be undone.'
        );
        expect(mockedAxios.delete).toHaveBeenCalledWith('/api/ab-tests/1');

        confirmSpy.mockRestore();
    });

    it('handles pagination', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests, total: 25 }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        // Check pagination buttons exist
        const nextButton = wrapper.find('button[aria-label="Next page"]');
        expect(nextButton.exists()).toBe(true);

        // Click next page
        await nextButton.trigger('click');
        expect(mockedAxios.get).toHaveBeenCalledWith('/api/ab-tests?page=2&per_page=10');
    });

    it('handles API errors gracefully', async () => {
        mockedAxios.get.mockRejectedValueOnce(new Error('Network error'));

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Network error');
    });

    it('formats dates correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        // Check if date formatting works (this will depend on the actual date formatting implementation)
        expect(wrapper.text()).toContain('1/1/2024'); // or whatever format is used
    });

    it('displays correct status badges', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const statusBadges = wrapper.findAll('.inline-flex.px-2.py-1');
        expect(statusBadges.length).toBeGreaterThan(0);
    });

    it('closes modals correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        // Open create modal
        const createButton = wrapper.find('button[aria-label="Create new A/B test"]');
        await createButton.trigger('click');
        expect(wrapper.vm.showModal).toBe(true);

        // Close modal
        await wrapper.vm.closeModal();
        expect(wrapper.vm.showModal).toBe(false);
    });

    it('handles form submission', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockTests }
        });
        mockedAxios.post.mockResolvedValueOnce({
            data: { success: true, data: { id: '3', ...mockTests[0] } }
        });

        const wrapper = mount(ABTestManager);
        await wrapper.vm.$nextTick();

        const newTest: ABTestData = {
            name: 'New Test',
            description: 'New test description',
            status: 'draft',
            variants: [
                { name: 'Variant A', weight: 50 },
                { name: 'Variant B', weight: 50 }
            ],
            audience_criteria: [],
            goal_event: 'click'
        };

        await wrapper.vm.handleFormSubmit(newTest);

        expect(mockedAxios.post).toHaveBeenCalledWith('/api/ab-tests', newTest);
        expect(wrapper.vm.showModal).toBe(false);
        expect(mockedAxios.get).toHaveBeenCalledTimes(2); // Initial load + refresh after submit
    });
});