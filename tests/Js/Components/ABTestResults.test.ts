import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import axios from 'axios';
import ABTestResults from '../../../resources/js/components/Analytics/ABTestResults.vue';
import type { ABTestResults as ABTestResultsType } from '../../../resources/js/types/analytics';

// Mock Chart.js
vi.mock('chart.js/auto', () => ({
    default: vi.fn().mockImplementation(() => ({
        destroy: vi.fn(),
        update: vi.fn()
    }))
}));

// Mock axios
vi.mock('axios');
const mockedAxios = vi.mocked(axios);

describe('ABTestResults', () => {
    const mockResults: ABTestResultsType = {
        test_id: '1',
        date_range: {
            start: '2024-01-01',
            end: '2024-01-31'
        },
        variants: [
            {
                variant_id: 'v1',
                variant_name: 'Control',
                participants: 1000,
                conversions: 100,
                conversion_rate: 0.1,
                confidence_interval: {
                    lower: 0.08,
                    upper: 0.12
                }
            },
            {
                variant_id: 'v2',
                variant_name: 'Variant A',
                participants: 1000,
                conversions: 120,
                conversion_rate: 0.12,
                confidence_interval: {
                    lower: 0.10,
                    upper: 0.14
                }
            }
        ],
        significance: {
            p_value: 0.032,
            is_significant: true,
            winner_variant: 'Variant A',
            effect_size: 0.02
        },
        total_participants: 2000,
        total_conversions: 220
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders loading state initially', () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        expect(wrapper.text()).toContain('Loading results...');
    });

    it('displays summary statistics correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('2,000'); // Total participants
        expect(wrapper.text()).toContain('220'); // Total conversions
        expect(wrapper.text()).toContain('11.00%'); // Overall conversion rate
    });

    it('shows significance indicator correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('0.0320'); // p-value
        expect(wrapper.text()).toContain('Significant');
        expect(wrapper.text()).toContain('Winner: Variant A');
    });

    it('displays non-significant results correctly', async () => {
        const nonSignificantResults = {
            ...mockResults,
            significance: {
                p_value: 0.15,
                is_significant: false
            }
        };

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: nonSignificantResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Not Significant');
    });

    it('renders charts with correct data', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // Check if canvas elements exist
        const canvases = wrapper.findAll('canvas');
        expect(canvases.length).toBe(2); // Conversion rate and participants charts
    });

    it('displays detailed results table correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Control');
        expect(wrapper.text()).toContain('Variant A');
        expect(wrapper.text()).toContain('1,000'); // Participants
        expect(wrapper.text()).toContain('10.00%'); // Conversion rate
        expect(wrapper.text()).toContain('8.00% - 12.00%'); // Confidence interval
    });

    it('handles date range changes', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1',
                dateRange: { from: '2024-01-01', to: '2024-01-31' }
            }
        });

        await wrapper.vm.$nextTick();

        // Change date range
        const dateInputs = wrapper.findAll('input[type="date"]');
        await dateInputs[0].setValue('2024-02-01');
        await dateInputs[1].setValue('2024-02-28');

        // Trigger refresh
        const refreshButton = wrapper.find('button[aria-label="Refresh results"]');
        await refreshButton.trigger('click');

        expect(mockedAxios.get).toHaveBeenCalledWith('/api/ab-tests/1/results?date_from=2024-02-01&date_to=2024-02-28');
    });

    it('handles API errors gracefully', async () => {
        mockedAxios.get.mockRejectedValueOnce(new Error('Network error'));

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Network error');
    });

    it('shows no results state when no data available', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: null }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('No results available');
        expect(wrapper.text()).toContain('Results will appear once the test has collected data.');
    });

    it('displays empty confidence intervals as N/A', async () => {
        const resultsWithoutCI = {
            ...mockResults,
            variants: mockResults.variants.map(v => ({
                ...v,
                confidence_interval: undefined
            }))
        };

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: resultsWithoutCI }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('N/A');
    });

    it('formats numbers correctly', async () => {
        const largeNumbersResults = {
            ...mockResults,
            total_participants: 150000,
            total_conversions: 15000
        };

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: largeNumbersResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('150,000');
        expect(wrapper.text()).toContain('15,000');
    });

    it('calculates conversion rates correctly', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // 220 conversions / 2000 participants = 11%
        expect(wrapper.text()).toContain('11.00%');
    });

    it('handles missing effect size', async () => {
        const resultsWithoutEffectSize = {
            ...mockResults,
            significance: {
                ...mockResults.significance,
                effect_size: undefined
            }
        };

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: resultsWithoutEffectSize }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // Should not crash and should still display other significance data
        expect(wrapper.text()).toContain('Significant');
    });

    it('refreshes data when testId changes', async () => {
        mockedAxios.get.mockResolvedValue({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // Change testId
        await wrapper.setProps({ testId: '2' });

        expect(mockedAxios.get).toHaveBeenCalledTimes(2);
        expect(mockedAxios.get).toHaveBeenLastCalledWith('/api/ab-tests/2/results?date_from=2024-01-01&date_to=2024-01-31');
    });

    it('disables refresh button during loading', async () => {
        mockedAxios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        const refreshButton = wrapper.find('button[aria-label="Refresh results"]');
        expect(refreshButton.attributes('disabled')).toBeDefined();
    });

    it('cleans up charts on unmount', async () => {
        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // Mock chart destroy calls
        const destroySpy = vi.fn();
        (wrapper.vm as any).conversionChart = { destroy: destroySpy };
        (wrapper.vm as any).participantsChart = { destroy: destroySpy };

        wrapper.unmount();

        expect(destroySpy).toHaveBeenCalledTimes(2);
    });

    it('handles chart creation errors gracefully', async () => {
        // Mock Chart constructor to throw
        const originalChart = await import('chart.js/auto');
        const mockChart = vi.fn().mockImplementation(() => {
            throw new Error('Chart creation failed');
        });

        vi.doMock('chart.js/auto', () => ({ default: mockChart }));

        mockedAxios.get.mockResolvedValueOnce({
            data: { success: true, data: mockResults }
        });

        const wrapper = mount(ABTestResults, {
            props: {
                testId: '1'
            }
        });

        await wrapper.vm.$nextTick();

        // Should not crash, charts just won't render
        expect(wrapper.text()).toContain('A/B Test Results');
    });
});