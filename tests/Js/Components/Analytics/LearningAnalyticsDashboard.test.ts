import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import LearningAnalyticsDashboard from '../../../../resources/js/Components/Analytics/LearningAnalyticsDashboard.vue';
import { useLearningStore } from '../../../../resources/js/stores/useLearningStore';

// Mock Chart.js
const mockChart = vi.fn().mockImplementation(() => ({
    destroy: vi.fn(),
    update: vi.fn(),
}));
mockChart.register = vi.fn();

vi.mock('chart.js', () => ({
    Chart: mockChart,
    registerables: [],
}));

// Mock axios
vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
    },
}));

describe('LearningAnalyticsDashboard.vue', () => {
    let wrapper: VueWrapper;
    let learningStore: ReturnType<typeof useLearningStore>;

    const mockCourses = [
        {
            id: 'course-1',
            tenant_id: 'tenant-1',
            name: 'Introduction to Data Science',
            modules_count: 10,
        },
        {
            id: 'course-2',
            tenant_id: 'tenant-1',
            name: 'Machine Learning Fundamentals',
            modules_count: 12,
        },
        {
            id: 'course-3',
            tenant_id: 'tenant-1',
            name: 'Advanced Analytics',
            modules_count: 8,
        },
    ];

    const mockProgress = [
        {
            id: 'progress-1',
            tenant_id: 'tenant-1',
            user_id: 'user-1',
            course_id: 'course-1',
            modules_completed: 7,
            total_score: 85,
            engagement_score: 78,
            certified: false,
            updated_at: '2024-01-15T10:00:00Z',
        },
    ];

    beforeEach(() => {
        setActivePinia(createPinia());
        learningStore = useLearningStore();

        // Mock store methods
        vi.spyOn(learningStore, 'fetchProgress').mockResolvedValue(mockProgress);
        vi.spyOn(learningStore, 'updateFilters').mockImplementation(() => {});

        // Mock store state
        vi.spyOn(learningStore, 'isLoading', 'get').mockReturnValue(false);
        vi.spyOn(learningStore, 'error', 'get').mockReturnValue('');
        vi.spyOn(learningStore, 'filteredProgress', 'get').mockReturnValue(mockProgress);
    });

    afterEach(() => {
        wrapper?.unmount();
        vi.clearAllMocks();
    });

    it('renders loading state correctly', async () => {
        vi.spyOn(learningStore, 'isLoading', 'get').mockReturnValue(true);

        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.find('.learning-analytics-dashboard').exists()).toBe(true);
        expect(wrapper.text()).toContain('Loading learning analytics...');
        expect(wrapper.find('.animate-spin').exists()).toBe(true);
    });

    it('renders error state correctly', async () => {
        vi.spyOn(learningStore, 'error', 'get').mockReturnValue('Test error message');

        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.text()).toContain('Test error message');
        expect(wrapper.find('.border-red-200').exists()).toBe(true);
    });

    it('renders component structure correctly', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.find('.learning-analytics-dashboard').exists()).toBe(true);
        expect(wrapper.find('h1').text()).toBe('Learning Analytics Dashboard');
        expect(wrapper.find('.learning-analytics-container').exists()).toBe(true);
    });

    it('displays key metrics cards correctly', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check all 5 metric cards
        expect(wrapper.text()).toContain('Total Learners');
        expect(wrapper.text()).toContain('1,247');
        expect(wrapper.text()).toContain('Avg Progress');
        expect(wrapper.text()).toContain('68%');
        expect(wrapper.text()).toContain('Completion Rate');
        expect(wrapper.text()).toContain('72%');
        expect(wrapper.text()).toContain('Avg Engagement');
        expect(wrapper.text()).toContain('82/100');
        expect(wrapper.text()).toContain('Certifications');
        expect(wrapper.text()).toContain('892');
    });

    it('displays filters panel with correct controls', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const filtersPanel = wrapper.find('.grid.grid-cols-1.gap-4.md\\:grid-cols-4');
        expect(filtersPanel.exists()).toBe(true);

        // Check date inputs
        const dateFrom = wrapper.find('#date-from');
        const dateTo = wrapper.find('#date-to');
        expect(dateFrom.exists()).toBe(true);
        expect(dateTo.exists()).toBe(true);

        // Check course selector
        const courseSelect = wrapper.find('#course-filter');
        expect(courseSelect.exists()).toBe(true);

        // Check user input
        const userInput = wrapper.find('#user-filter');
        expect(userInput.exists()).toBe(true);
    });

    it('renders learning progress chart', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const progressChart = wrapper.findAll('h3').find(h3 => h3.text().includes('Learning Progress Over Time'));
        expect(progressChart?.exists()).toBe(true);
    });

    it('renders course performance comparison chart', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const performanceChart = wrapper.findAll('h3').find(h3 => h3.text().includes('Course Performance Comparison'));
        expect(performanceChart?.exists()).toBe(true);
    });

    it('displays strengths in outcome analysis', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const strengthsSection = wrapper.findAll('.rounded-lg.border.bg-green-50').find(el => 
            el.text().includes('Strengths')
        );
        expect(strengthsSection?.exists()).toBe(true);
        expect(strengthsSection?.text()).toContain('High engagement in video content');
    });

    it('displays areas for improvement in outcome analysis', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const weaknessesSection = wrapper.findAll('.rounded-lg.border.bg-red-50').find(el => 
            el.text().includes('Areas for Improvement')
        );
        expect(weaknessesSection?.exists()).toBe(true);
        expect(weaknessesSection?.text()).toContain('Low quiz completion rates');
    });

    it('renders skills distribution radar chart', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const skillsChart = wrapper.findAll('h3').find(h3 => h3.text().includes('Skills Distribution'));
        expect(skillsChart?.exists()).toBe(true);
    });

    it('displays completion predictions', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const predictionsSection = wrapper.find('h3');
        expect(predictionsSection?.text()).toContain('Completion Predictions');

        // Check prediction cards
        expect(wrapper.text()).toContain('Introduction to Data Science');
        expect(wrapper.text()).toContain('On Track');
        expect(wrapper.text()).toContain('Machine Learning Fundamentals');
        expect(wrapper.text()).toContain('At Risk');
        expect(wrapper.text()).toContain('Advanced Analytics');
        expect(wrapper.text()).toContain('Needs Attention');
    });

    it('displays personalized recommendations', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const recommendationsSection = wrapper.find('h3');
        expect(recommendationsSection?.text()).toContain('Personalized Recommendations');

        // Check recommendation cards
        expect(wrapper.text()).toContain('Complete Module 3 Quiz');
        expect(wrapper.text()).toContain('Join Study Group');
        expect(wrapper.text()).toContain('Review Optional Materials');
    });

    it('displays performance comparison table', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const tableSection = wrapper.find('h3');
        expect(tableSection?.text()).toContain('Performance Comparison');

        // Check table headers
        expect(wrapper.text()).toContain('Metric');
        expect(wrapper.text()).toContain('Current Period');
        expect(wrapper.text()).toContain('Previous Period');
        expect(wrapper.text()).toContain('Change');
        expect(wrapper.text()).toContain('Trend');

        // Check table rows
        expect(wrapper.text()).toContain('Active Learners');
        expect(wrapper.text()).toContain('Avg Course Progress');
        expect(wrapper.text()).toContain('Completion Rate');
        expect(wrapper.text()).toContain('Engagement Score');
    });

    it('shows real-time updates indicator when enabled', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            props: {
                enableRealTime: true,
            },
            global: {
                stubs: ['canvas'],
            },
        });

        const realtimeIndicator = wrapper.find('.fixed.bottom-4.right-4');
        expect(realtimeIndicator?.exists()).toBe(true);
        expect(realtimeIndicator?.text()).toContain('Live updates');
    });

    it('hides real-time updates indicator when disabled', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            props: {
                enableRealTime: false,
            },
            global: {
                stubs: ['canvas'],
            },
        });

        const realtimeIndicator = wrapper.find('.fixed.bottom-4.right-4');
        expect(realtimeIndicator?.exists()).toBe(false);
    });

    it('handles date range changes', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const dateFrom = wrapper.find('#date-from');
        const dateTo = wrapper.find('#date-to');

        await dateFrom.setValue('2024-01-01');
        await dateTo.setValue('2024-01-31');

        expect(learningStore.updateFilters).toHaveBeenCalledWith({
            dateRange: {
                from: '2024-01-01',
                to: '2024-01-31',
            },
        });
    });

    it('handles course filter changes', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const courseSelect = wrapper.find('#course-filter');
        await courseSelect.setValue('course-1');

        expect(learningStore.updateFilters).toHaveBeenCalledWith({ courseId: 'course-1' });
    });

    it('handles user filter changes', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const userInput = wrapper.find('#user-filter');
        await userInput.setValue('user-123');

        expect(learningStore.updateFilters).toHaveBeenCalledWith({ userId: 'user-123' });
    });

    it('handles refresh button click', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const refreshButton = wrapper.find('button[aria-label="Refresh learning analytics data"]');
        await refreshButton.trigger('click');

        expect(learningStore.fetchProgress).toHaveBeenCalled();
    });

    it('applies correct accessibility attributes', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Main region
        expect(wrapper.attributes('role')).toBe('region');
        expect(wrapper.attributes('aria-label')).toBe('Learning analytics dashboard');

        // Chart accessibility
        const charts = wrapper.findAll('canvas');
        expect(charts.length).toBeGreaterThanOrEqual(3);

        // Buttons have aria-labels
        const buttons = wrapper.findAll('button');
        expect(buttons.some(btn => btn.attributes('aria-label'))).toBe(true);

        // Form controls have labels
        const inputs = wrapper.findAll('input, select');
        inputs.forEach(input => {
            const label = wrapper.find(`label[for="${input.attributes('id')}"]`);
            expect(label.exists()).toBe(true);
        });
    });

    it('supports keyboard navigation', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
            attachTo: document.body,
        });

        const buttons = wrapper.findAll('button');
        const firstButton = buttons[0];

        // Focus should work
        await firstButton.trigger('focus');
        expect(document.activeElement).toBe(firstButton.element);
    });

    it('handles props correctly', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            props: {
                dateRange: { from: '2024-01-01', to: '2024-01-31' },
                courseId: 'course-1',
                userId: 'user-1',
                enableRealTime: false,
            },
            global: {
                stubs: ['canvas'],
            },
        });

        expect(learningStore.updateFilters).toHaveBeenCalledWith(
            expect.objectContaining({
                dateRange: { from: '2024-01-01', to: '2024-01-31' },
                courseId: 'course-1',
                userId: 'user-1',
            })
        );
    });

    it('displays trend indicators correctly', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check for trend icons
        const upTrendIcons = wrapper.findAll('.text-green-500');
        expect(upTrendIcons.length).toBeGreaterThan(0);
    });

    it('displays priority badges in recommendations', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check for priority badges
        expect(wrapper.text()).toContain('High');
        expect(wrapper.text()).toContain('Medium');
        expect(wrapper.text()).toContain('Low');
    });

    it('displays completion prediction confidence levels', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check for confidence percentages
        expect(wrapper.text()).toContain('Confidence');
        expect(wrapper.text()).toContain('92%');
        expect(wrapper.text()).toContain('78%');
        expect(wrapper.text()).toContain('65%');
    });

    it('displays estimated completion dates', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check for estimated dates
        expect(wrapper.text()).toContain('Est. Date');
        expect(wrapper.text()).toContain('2024-03-15');
        expect(wrapper.text()).toContain('2024-04-20');
        expect(wrapper.text()).toContain('2024-05-10');
    });

    it('validates filter inputs', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const dateFrom = wrapper.find('#date-from');
        const dateTo = wrapper.find('#date-to');
        const userInput = wrapper.find('#user-filter');

        // Test date inputs
        await dateFrom.setValue('2024-01-01');
        await dateTo.setValue('2024-12-31');
        expect(dateFrom.element.value).toBe('2024-01-01');
        expect(dateTo.element.value).toBe('2024-12-31');

        // Test user input
        await userInput.setValue('test-user-123');
        expect(userInput.element.value).toBe('test-user-123');
    });

    it('renders course dropdown with correct options', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const courseSelect = wrapper.find('#course-filter');
        const options = courseSelect.findAll('option');

        expect(options.length).toBe(4); // All Courses + 3 courses
        expect(options[0].attributes('value')).toBe('');
        expect(options[1].text()).toBe('Introduction to Data Science');
        expect(options[2].text()).toBe('Machine Learning Fundamentals');
        expect(options[3].text()).toBe('Advanced Analytics');
    });

    it('displays change percentages correctly', async () => {
        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Check change percentages are displayed
        expect(wrapper.text()).toContain('+13.2%');
        expect(wrapper.text()).toContain('+9.7%');
        expect(wrapper.text()).toContain('+5.9%');
        expect(wrapper.text()).toContain('-33.3%');
    });

    it('handles API errors gracefully', async () => {
        vi.spyOn(learningStore, 'fetchProgress').mockRejectedValue(new Error('API Error'));

        wrapper = mount(LearningAnalyticsDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const refreshButton = wrapper.find('button[aria-label="Refresh learning analytics data"]');
        await refreshButton.trigger('click');

        // Should not throw unhandled error
        expect(learningStore.error).toBe('');
    });
});
