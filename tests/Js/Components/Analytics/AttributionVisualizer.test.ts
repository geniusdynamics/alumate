import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import AttributionVisualizer from '../../../../resources/js/Components/Analytics/AttributionVisualizer.vue';
import { useAttributionStore } from '../../../../resources/js/Stores/useAttributionStore';
import type { AttributionData, ChannelPerformance, BudgetSuggestion } from '../../../../resources/js/Types/analytics';

// Mock Chart.js
const mockChartInstance = {
    destroy: vi.fn(),
    update: vi.fn(),
};

vi.mock('chart.js', () => ({
    Chart: vi.fn().mockImplementation(() => mockChartInstance),
    register: vi.fn(),
    registerables: [],
}));

// Mock axios
vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    },
}));

describe('AttributionVisualizer', () => {
    let wrapper: VueWrapper;
    let store: ReturnType<typeof useAttributionStore>;
    let mockAttributionData: AttributionData;
    let mockChannelPerformance: ChannelPerformance[];
    let mockBudgetRecommendations: BudgetSuggestion[];

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useAttributionStore();

        mockAttributionData = {
            user_id: '123',
            touchpoints: [
                {
                    type: 'ad',
                    channel: 'google',
                    timestamp: 1640995200000,
                    value: 50,
                    conversion_value: 1000,
                },
                {
                    type: 'email',
                    channel: 'direct',
                    timestamp: 1640995260000,
                    value: 75,
                    conversion_value: 1500,
                },
                {
                    type: 'social',
                    channel: 'facebook',
                    timestamp: 1640995320000,
                    value: 25,
                    conversion_value: 500,
                },
            ],
            models: {
                first_click: { google: 100, direct: 0, facebook: 0 },
                last_click: { google: 0, direct: 0, facebook: 100 },
                linear: { google: 33.33, direct: 33.33, facebook: 33.34 },
                time_decay: { google: 20, direct: 30, facebook: 50 },
            },
            channels: [],
            budget_recommendations: [],
        };

        mockChannelPerformance = [
            {
                channel: 'google',
                contribution: 35,
                roi: 2.5,
                conversions: 15,
                total_value: 15000,
            },
            {
                channel: 'facebook',
                contribution: 45,
                roi: 3.2,
                conversions: 22,
                total_value: 22000,
            },
            {
                channel: 'direct',
                contribution: 20,
                roi: 1.8,
                conversions: 8,
                total_value: 8000,
            },
        ];

        mockBudgetRecommendations = [
            {
                channel: 'google',
                current_allocation: 5000,
                recommended_change: 15,
                rationale: 'Strong ROI performance indicates opportunity for increased investment',
                expected_impact: '15-20% increase in conversions',
            },
            {
                channel: 'facebook',
                current_allocation: 3000,
                recommended_change: -5,
                rationale: 'Overperforming relative to budget allocation',
                expected_impact: 'Optimize budget efficiency',
            },
        ];

        // Mock store methods
        vi.spyOn(store, 'initialize').mockResolvedValue();
        vi.spyOn(store, 'refreshData').mockResolvedValue();
        vi.spyOn(store, 'setSelectedModel').mockImplementation(() => {});
    });

    afterEach(() => {
        wrapper?.unmount();
        vi.clearAllMocks();
    });

    const mountComponent = (props = {}) => {
        wrapper = mount(AttributionVisualizer, {
            props,
            global: {
                stubs: {
                    'canvas': true,
                },
            },
        });
    };

    describe('Component Mounting', () => {
        it('mounts successfully with default props', async () => {
            mountComponent();
            await wrapper.vm.$nextTick();

            expect(wrapper.exists()).toBe(true);
            expect(store.initialize).toHaveBeenCalled();
        });

        it('mounts with userId prop', async () => {
            mountComponent({ userId: '123' });
            await wrapper.vm.$nextTick();

            expect(store.initialize).toHaveBeenCalledWith('123');
        });

        it('mounts with defaultModel prop', async () => {
            mountComponent({ defaultModel: 'first_click' });
            await wrapper.vm.$nextTick();

            expect(store.setSelectedModel).toHaveBeenCalledWith('first_click');
        });
    });

    describe('Loading States', () => {
        it('displays loading spinner when isLoading is true', async () => {
            vi.spyOn(store, 'isLoading', 'get').mockReturnValue(true);

            mountComponent();
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Loading attribution data...');
            expect(wrapper.find('.animate-spin').exists()).toBe(true);
        });

        it('displays error message when error exists', async () => {
            vi.spyOn(store, 'error', 'get').mockReturnValue('Test error message');

            mountComponent();
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Test error message');
            expect(wrapper.find('.text-red-800').exists()).toBe(true);
        });
    });

    describe('Model Selection', () => {
        beforeEach(() => {
            mountComponent();
        });

        it('renders all attribution model buttons', () => {
            const modelButtons = wrapper.findAll('button[aria-label*="attribution model"]');
            expect(modelButtons).toHaveLength(4);

            const buttonTexts = modelButtons.map(btn => btn.text());
            expect(buttonTexts).toEqual(['First Click', 'Last Click', 'Linear', 'Time Decay']);
        });

        it('highlights selected model', async () => {
            vi.spyOn(store, 'selectedModel', 'get').mockReturnValue('linear');

            await wrapper.vm.$nextTick();

            const linearButton = wrapper.find('button[aria-label*="Linear attribution model"]');
            expect(linearButton.classes()).toContain('bg-blue-600');
            expect(linearButton.classes()).toContain('text-white');
        });

        it('calls setSelectedModel when model button is clicked', async () => {
            const firstClickButton = wrapper.find('button[aria-label*="First Click attribution model"]');
            await firstClickButton.trigger('click');

            expect(store.setSelectedModel).toHaveBeenCalledWith('first_click');
        });

        it('displays correct model description', () => {
            const description = wrapper.find('.text-sm.text-gray-600');
            expect(description.text()).toContain('Distributes credit equally across all touchpoints');
        });

        it('updates model description when model changes', async () => {
            const description = wrapper.find('.text-sm.text-gray-600');
            expect(description.text()).toContain('Distributes credit equally');

            const firstClickButton = wrapper.find('button[aria-label*="First Click attribution model"]');
            await firstClickButton.trigger('click');

            await wrapper.vm.$nextTick();
            const updatedDescription = wrapper.find('.text-sm.text-gray-600');
            expect(updatedDescription.text()).toContain('first touchpoint');
        });
    });

    describe('Chart Rendering', () => {
        beforeEach(async () => {
            // Mock chart data
            vi.spyOn(store, 'attributionData', 'get').mockReturnValue(mockAttributionData);
            vi.spyOn(store, 'channelPerformance', 'get').mockReturnValue(mockChannelPerformance);

            mountComponent();
            await wrapper.vm.$nextTick();
        });

        it('renders journey flow chart canvas', () => {
            const canvas = wrapper.find('canvas[aria-label="Customer journey Sankey diagram"]');
            expect(canvas.exists()).toBe(true);
        });

        it('renders contribution pie chart canvas', () => {
            const canvas = wrapper.find('canvas[aria-label="Channel contribution pie chart"]');
            expect(canvas.exists()).toBe(true);
        });

        it('renders model comparison bar chart canvas', () => {
            const canvas = wrapper.find('canvas[aria-label="Attribution model comparison bar chart"]');
            expect(canvas.exists()).toBe(true);
        });
    });

    describe('Budget Recommendations Table', () => {
        beforeEach(async () => {
            vi.spyOn(store, 'budgetRecommendations', 'get').mockReturnValue(mockBudgetRecommendations);

            mountComponent();
            await wrapper.vm.$nextTick();
        });

        it('renders budget recommendations table', () => {
            const table = wrapper.find('table[aria-label="Budget recommendations table"]');
            expect(table.exists()).toBe(true);
        });

        it('displays all budget recommendations', () => {
            const rows = wrapper.findAll('tbody tr');
            expect(rows).toHaveLength(2);
        });

        it('displays channel names correctly', () => {
            const firstRow = wrapper.findAll('tbody tr')[0];
            expect(firstRow.text()).toContain('Google');
        });

        it('displays current allocations formatted', () => {
            const cells = wrapper.findAll('tbody td');
            expect(cells.some(cell => cell.text().includes('$5,000'))).toBe(true);
        });

        it('displays recommended changes with correct styling', () => {
            const changeCells = wrapper.findAll('tbody td .font-semibold');
            expect(changeCells.length).toBeGreaterThan(0);

            // Check positive change (green)
            expect(changeCells.some(cell => cell.text().includes('+15%'))).toBe(true);
            // Check negative change (red)
            expect(changeCells.some(cell => cell.text().includes('-5%'))).toBe(true);
        });
    });

    describe('Accessibility Features', () => {
        beforeEach(() => {
            mountComponent();
        });

        it('has proper ARIA labels on main region', () => {
            const mainRegion = wrapper.find('[role="region"]');
            expect(mainRegion.attributes('aria-label')).toBe('Attribution analysis dashboard');
        });

        it('has accessible chart descriptions', () => {
            const charts = wrapper.findAll('canvas[aria-label]');
            expect(charts.length).toBe(3);
            expect(charts[0].attributes('aria-label')).toContain('Customer journey');
            expect(charts[1].attributes('aria-label')).toContain('Channel contribution');
            expect(charts[2].attributes('aria-label')).toContain('Attribution model comparison');
        });

        it('has keyboard accessible model selection buttons', () => {
            const buttons = wrapper.findAll('button[aria-label*="attribution model"]');
            buttons.forEach(button => {
                expect(button.attributes('aria-pressed')).toBeDefined();
            });
        });

        it('has accessible table with proper roles', () => {
            const table = wrapper.find('table[role="table"]');
            expect(table.exists()).toBe(true);

            const headers = wrapper.findAll('th[scope]');
            expect(headers.length).toBeGreaterThan(0);
        });
    });

    describe('Data Refresh', () => {
        it('calls refreshData when refresh button is clicked', async () => {
            mountComponent();
            await wrapper.vm.$nextTick();

            const refreshButton = wrapper.find('button[aria-label="Refresh attribution data"]');
            await refreshButton.trigger('click');

            expect(store.refreshData).toHaveBeenCalled();
        });

        it('disables refresh button when loading', async () => {
            mountComponent();
            await wrapper.vm.$nextTick();

            // Mock loading state after component is mounted
            vi.spyOn(store, 'isLoading', 'get').mockReturnValue(true);
            await wrapper.vm.$nextTick();

            const refreshButton = wrapper.find('button[aria-label="Refresh attribution data"]');
            expect(refreshButton.attributes('disabled')).toBeDefined();
        });
    });

    describe('Error Handling', () => {
        it('displays API error messages', async () => {
            vi.spyOn(store, 'error', 'get').mockReturnValue('Failed to load attribution data');

            mountComponent();
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Failed to load attribution data');
        });

        it('handles empty data gracefully', async () => {
            vi.spyOn(store, 'attributionData', 'get').mockReturnValue(null);
            vi.spyOn(store, 'channelPerformance', 'get').mockReturnValue([]);
            vi.spyOn(store, 'budgetRecommendations', 'get').mockReturnValue([]);

            mountComponent();
            await wrapper.vm.$nextTick();

            // Should not crash and should render empty states appropriately
            expect(wrapper.exists()).toBe(true);
        });
    });

    describe('Responsive Design', () => {
        it('applies responsive grid classes', () => {
            mountComponent();

            const gridContainer = wrapper.find('.grid-cols-1.lg\\:grid-cols-2');
            expect(gridContainer.exists()).toBe(true);
        });

        it('has responsive table styling', () => {
            mountComponent();

            const tableContainer = wrapper.find('.overflow-x-auto');
            expect(tableContainer.exists()).toBe(true);
        });
    });

    describe('Chart Updates', () => {
        it('updates charts when model selection changes', async () => {
            mountComponent();
            await wrapper.vm.$nextTick();

            // Spy on the setSelectedModel method
            const setSelectedModelSpy = vi.spyOn(store, 'setSelectedModel');

            const modelButton = wrapper.find('button[aria-label*="Last Click attribution model"]');
            await modelButton.trigger('click');

            expect(setSelectedModelSpy).toHaveBeenCalledWith('last_click');
        });

        it('updates charts when data changes', async () => {
            mountComponent();
            await wrapper.vm.$nextTick();

            // Verify that charts are rendered initially
            let canvases = wrapper.findAll('canvas');
            expect(canvases.length).toBe(3);

            // Simulate data change by updating the store mock
            vi.spyOn(store, 'attributionData', 'get').mockReturnValue({
                ...mockAttributionData,
                touchpoints: [...mockAttributionData.touchpoints, {
                    type: 'social',
                    channel: 'instagram',
                    timestamp: 1640995380000,
                    value: 30,
                    conversion_value: 600,
                }]
            });

            // Trigger reactivity by changing selected model
            const modelButton = wrapper.find('button[aria-label*="First Click attribution model"]');
            await modelButton.trigger('click');

            // Charts should still be present
            canvases = wrapper.findAll('canvas');
            expect(canvases.length).toBe(3);
        });
    });

    describe('Channel Color Coding', () => {
        it('uses consistent colors for channels', () => {
            mountComponent();

            const component = wrapper.vm as any;
            expect(component.getChannelColor('google')).toBe('#4285F4');
            expect(component.getChannelColor('facebook')).toBe('#1877F2');
            expect(component.getChannelColor('unknown')).toBe('#6B7280');
        });

        it('formats channel names correctly', () => {
            mountComponent();

            const component = wrapper.vm as any;
            expect(component.formatChannelName('google')).toBe('Google');
            expect(component.formatChannelName('direct')).toBe('Direct');
        });
    });

    describe('Model Descriptions', () => {
        it('provides accurate descriptions for each model', () => {
            mountComponent();

            const component = wrapper.vm as any;
            expect(component.getModelDescription('first_click')).toContain('first touchpoint');
            expect(component.getModelDescription('last_click')).toContain('last touchpoint');
            expect(component.getModelDescription('linear')).toContain('equally across all touchpoints');
            expect(component.getModelDescription('time_decay')).toContain('closer to the conversion');
        });
    });
});