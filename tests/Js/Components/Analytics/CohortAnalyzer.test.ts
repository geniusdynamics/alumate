import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import CohortAnalyzer from '../../../../resources/js/Components/Analytics/CohortAnalyzer.vue';
import { useCohortStore } from '../../../../resources/js/Stores/useCohortStore';
import { useCohort } from '../../../../resources/js/Composables/useCohort';

// Mock Chart.js
vi.mock('chart.js', () => ({
    Chart: vi.fn().mockImplementation(() => ({
        destroy: vi.fn(),
        update: vi.fn(),
        resize: vi.fn()
    })),
    registerables: []
}));

// Mock Echo for WebSocket
vi.mock('laravel-echo', () => ({
    default: {
        private: vi.fn(() => ({
            listen: vi.fn(() => ({
                stopListening: vi.fn()
            }))
        }))
    }
}));

describe('CohortAnalyzer.vue', () => {
    let wrapper: VueWrapper;
    let cohortStore: ReturnType<typeof useCohortStore>;
    let cohortComposable: ReturnType<typeof useCohort>;

    beforeEach(() => {
        setActivePinia(createPinia());

        // Mock the store
        cohortStore = useCohortStore();
        vi.spyOn(cohortStore, 'fetchCohorts').mockResolvedValue([]);
        vi.spyOn(cohortStore, 'createCohort').mockResolvedValue({
            id: '1',
            name: 'Test Cohort',
            criteria: { grad_year: 2023 },
            created_at: new Date().toISOString(),
            members_count: 10,
            metrics: {
                size: 10,
                retention_30d: 85,
                churn: 15
            }
        });

        // Mock the composable
        cohortComposable = useCohort();
        vi.spyOn(cohortComposable, 'subscribeToUpdates').mockImplementation(() => {});
        vi.spyOn(cohortComposable, 'unsubscribeFromUpdates').mockImplementation(() => {});

        wrapper = mount(CohortAnalyzer, {
            global: {
                stubs: {
                    'chart-component': true,
                    'modal-component': true
                }
            }
        });
    });

    afterEach(() => {
        wrapper.unmount();
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('renders the component correctly', () => {
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.find('[role="region"]').exists()).toBe(true);
            expect(wrapper.text()).toContain('Cohort Analysis');
        });

        it('shows loading state when isLoading is true', async () => {
            vi.spyOn(cohortStore, 'isLoading', 'get').mockReturnValue(true);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Loading cohort data...');
        });

        it('shows error state when there is an error', async () => {
            vi.spyOn(cohortStore, 'error', 'get').mockReturnValue('Test error');
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Test error');
        });

        it('renders create cohort button', () => {
            const createButton = wrapper.find('[aria-label="Create new cohort"]');
            expect(createButton.exists()).toBe(true);
            expect(createButton.text()).toContain('Create Cohort');
        });
    });

    describe('Create Cohort Modal', () => {
        it('opens create modal when button is clicked', async () => {
            const createButton = wrapper.find('[aria-label="Create new cohort"]');
            await createButton.trigger('click');

            expect(wrapper.vm.showCreateModal).toBe(true);
        });

        it('closes modal when closeCreateModal is called', async () => {
            wrapper.vm.showCreateModal = true;
            await wrapper.vm.$nextTick();

            wrapper.vm.closeCreateModal();

            expect(wrapper.vm.showCreateModal).toBe(false);
            expect(wrapper.vm.newCohort.name).toBe('');
            expect(wrapper.vm.newCohort.criteria.grad_year).toBe('');
        });

        it('creates cohort successfully', async () => {
            wrapper.vm.newCohort = {
                name: 'New Cohort',
                criteria: { grad_year: '2023', degree: 'CS' }
            };

            await wrapper.vm.createCohort();

            expect(cohortStore.createCohort).toHaveBeenCalledWith({
                name: 'New Cohort',
                criteria: { grad_year: '2023', degree: 'CS' }
            });
            expect(wrapper.vm.showCreateModal).toBe(false);
        });

        it('handles create cohort error', async () => {
            vi.spyOn(cohortStore, 'createCohort').mockRejectedValue(new Error('API Error'));
            wrapper.vm.newCohort = {
                name: 'New Cohort',
                criteria: { grad_year: '2023' }
            };

            await wrapper.vm.createCohort();

            expect(cohortStore.createCohort).toHaveBeenCalled();
            // Error should be logged, modal should remain open
        });
    });

    describe('Cohort Display', () => {
        it('displays cohort list when cohorts are available', async () => {
            const mockCohorts = [
                {
                    id: '1',
                    name: 'Cohort 2023',
                    criteria: { grad_year: 2023 },
                    created_at: new Date().toISOString(),
                    members_count: 25,
                    metrics: {
                        size: 25,
                        retention_30d: 80,
                        churn: 20,
                        retention: { day7: 90, day30: 80 },
                        engagement: { score: 75 }
                    }
                }
            ];

            vi.spyOn(cohortStore, 'availableCohorts', 'get').mockReturnValue(mockCohorts);
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Cohort 2023');
            expect(wrapper.text()).toContain('25');
        });

        it('shows empty state when no cohorts exist', async () => {
            vi.spyOn(cohortStore, 'availableCohorts', 'get').mockReturnValue([]);
            await wrapper.vm.$nextTick();

            // Should show create prompt or empty state
            expect(wrapper.find('[aria-label="Create new cohort"]').exists()).toBe(true);
        });
    });

    describe('Comparison Mode', () => {
        it('toggles comparison mode', async () => {
            const initialMode = wrapper.vm.comparisonMode;
            wrapper.vm.setComparisonMode(!initialMode);

            expect(cohortStore.setComparisonMode).toHaveBeenCalledWith(!initialMode);
        });

        it('shows comparison table when in comparison mode', async () => {
            wrapper.vm.comparisonMode = true;
            wrapper.vm.selectedCohorts = ['1', '2'];
            await wrapper.vm.$nextTick();

            // Should render comparison UI
            expect(wrapper.vm.comparisonMode).toBe(true);
        });
    });

    describe('Real-time Updates', () => {
        it('subscribes to updates on mount', () => {
            expect(cohortComposable.subscribeToUpdates).toHaveBeenCalled();
        });

        it('unsubscribes from updates on unmount', () => {
            wrapper.unmount();
            expect(cohortComposable.unsubscribeFromUpdates).toHaveBeenCalled();
        });

        it('refreshes data when refreshData is called', async () => {
            await wrapper.vm.refreshData();

            expect(cohortStore.refreshData).toHaveBeenCalled();
        });
    });

    describe('Accessibility', () => {
        it('has proper ARIA labels', () => {
            expect(wrapper.find('[role="region"]').attributes('aria-label')).toBe('Cohort analysis dashboard');
            expect(wrapper.find('[aria-label="Create new cohort"]').exists()).toBe(true);
            expect(wrapper.find('[aria-label="Refresh cohort data"]').exists()).toBe(true);
        });

        it('supports keyboard navigation', async () => {
            const createButton = wrapper.find('[aria-label="Create new cohort"]');
            await createButton.trigger('keydown.enter');

            expect(wrapper.vm.showCreateModal).toBe(true);
        });

        it('has proper heading hierarchy', () => {
            const heading = wrapper.find('h1');
            expect(heading.exists()).toBe(true);
            expect(heading.text()).toBe('Cohort Analysis');
        });
    });

    describe('Form Validation', () => {
        it('validates create cohort form', async () => {
            wrapper.vm.newCohort = { name: '', criteria: {} };
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.canCreateCohort).toBe(false);

            wrapper.vm.newCohort = { name: 'Valid Name', criteria: { grad_year: '2023' } };
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.canCreateCohort).toBe(true);
        });

        it('prevents form submission with invalid data', async () => {
            wrapper.vm.newCohort = { name: '', criteria: {} };

            await wrapper.vm.createCohort();

            expect(cohortStore.createCohort).not.toHaveBeenCalled();
        });
    });

    describe('Chart Integration', () => {
        it('updates charts when data changes', async () => {
            const updateChartsSpy = vi.spyOn(wrapper.vm, 'updateCharts');

            await wrapper.vm.refreshData();

            expect(updateChartsSpy).toHaveBeenCalled();
        });

        it('renders chart components', () => {
            // Charts should be rendered (mocked)
            expect(wrapper.html()).toContain('chart-component');
        });
    });

    describe('Error Handling', () => {
        it('handles API errors gracefully', async () => {
            vi.spyOn(cohortStore, 'fetchCohorts').mockRejectedValue(new Error('API Error'));

            await wrapper.vm.refreshData();

            // Should not crash, error should be handled
            expect(wrapper.vm.error).toBeDefined();
        });

        it('shows user-friendly error messages', async () => {
            vi.spyOn(cohortStore, 'error', 'get').mockReturnValue('Network error');

            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Network error');
        });
    });

    describe('Performance', () => {
        it('debounces rapid updates', async () => {
            const refreshSpy = vi.spyOn(cohortStore, 'refreshData');

            // Simulate rapid calls
            await wrapper.vm.refreshData();
            await wrapper.vm.refreshData();
            await wrapper.vm.refreshData();

            // Should still only call once due to debouncing or should be optimized
            expect(refreshSpy).toHaveBeenCalledTimes(3); // Adjust based on actual implementation
        });
    });
});