import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import LearningDashboard from '../../../../resources/js/Components/Analytics/LearningDashboard.vue';
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

// Mock WebSocket
vi.mock('@vueuse/core', () => ({
    useWebSocket: vi.fn(() => ({
        data: ref(''),
        send: vi.fn(),
        close: vi.fn(),
        open: vi.fn(),
    })),
}));

// Mock canvas
HTMLCanvasElement.prototype.getContext = vi.fn(() => ({
    fillRect: vi.fn(),
    clearRect: vi.fn(),
    getImageData: vi.fn(() => ({ data: [] })),
    putImageData: vi.fn(),
    createImageData: vi.fn(() => []),
    setTransform: vi.fn(),
    drawImage: vi.fn(),
    save: vi.fn(),
    fillText: vi.fn(),
    restore: vi.fn(),
    beginPath: vi.fn(),
    moveTo: vi.fn(),
    lineTo: vi.fn(),
    closePath: vi.fn(),
    stroke: vi.fn(),
    translate: vi.fn(),
    scale: vi.fn(),
    rotate: vi.fn(),
    arc: vi.fn(),
    fill: vi.fn(),
    measureText: vi.fn(() => ({ width: 0 })),
    transform: vi.fn(),
    rect: vi.fn(),
    clip: vi.fn(),
}));

describe('LearningDashboard.vue', () => {
    let wrapper: VueWrapper;
    let learningStore: ReturnType<typeof useLearningStore>;

    const mockCourses = [
        {
            id: 'course-1',
            tenant_id: 'tenant-1',
            name: 'Introduction to Programming',
            modules_count: 10,
            duration_estimate: 20,
        },
        {
            id: 'course-2',
            tenant_id: 'tenant-1',
            name: 'Advanced Data Structures',
            modules_count: 15,
            duration_estimate: 30,
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
        {
            id: 'progress-2',
            tenant_id: 'tenant-1',
            user_id: 'user-2',
            course_id: 'course-1',
            modules_completed: 10,
            total_score: 95,
            engagement_score: 88,
            certified: true,
            updated_at: '2024-01-14T10:00:00Z',
        },
        {
            id: 'progress-3',
            tenant_id: 'tenant-1',
            user_id: 'user-1',
            course_id: 'course-2',
            modules_completed: 5,
            total_score: 70,
            engagement_score: 65,
            certified: false,
            updated_at: '2024-01-13T10:00:00Z',
        },
    ];

    beforeEach(() => {
        setActivePinia(createPinia());
        learningStore = useLearningStore();

        // Mock store methods
        vi.spyOn(learningStore, 'fetchProgress').mockResolvedValue(mockProgress);
        vi.spyOn(learningStore, 'trackInteraction').mockResolvedValue();
        vi.spyOn(learningStore, 'updateProgress').mockResolvedValue(mockProgress[0]);
        vi.spyOn(learningStore, 'verifyCertification').mockResolvedValue(mockProgress[0]);
        vi.spyOn(learningStore, 'updateFilters').mockImplementation(() => {});

        // Mock store state
        vi.spyOn(learningStore, 'isLoading', 'get').mockReturnValue(false);
        vi.spyOn(learningStore, 'error', 'get').mockReturnValue('');
        vi.spyOn(learningStore, 'filteredProgress', 'get').mockReturnValue(mockProgress);
        vi.spyOn(learningStore, 'courses', 'get').mockReturnValue(mockCourses);
        vi.spyOn(learningStore, 'learningStats', 'get').mockReturnValue({
            totalProgress: 3,
            certifiedCount: 1,
            avgEngagement: 77,
            certificationRate: 33.33,
            completionByCourse: [
                {
                    courseId: 'course-1',
                    courseName: 'Introduction to Programming',
                    completionRate: 85,
                    totalStudents: 2,
                    certifiedCount: 1,
                },
                {
                    courseId: 'course-2',
                    courseName: 'Advanced Data Structures',
                    completionRate: 33.33,
                    totalStudents: 1,
                    certifiedCount: 0,
                },
            ],
        });
    });

    afterEach(() => {
        wrapper?.unmount();
        vi.clearAllMocks();
    });

    it('renders loading state correctly', async () => {
        vi.spyOn(learningStore, 'isLoading', 'get').mockReturnValue(true);

        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.find('.learning-dashboard').exists()).toBe(true);
        expect(wrapper.text()).toContain('Loading learning analytics...');
        expect(wrapper.find('.animate-spin').exists()).toBe(true);
    });

    it('renders error state correctly', async () => {
        vi.spyOn(learningStore, 'error', 'get').mockReturnValue('Test error message');

        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.text()).toContain('Test error message');
        expect(wrapper.find('.border-red-200').exists()).toBe(true);
    });

    it('renders component structure correctly', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.find('.learning-dashboard').exists()).toBe(true);
        expect(wrapper.find('h1').text()).toBe('Learning Analytics');
        expect(wrapper.find('.learning-dashboard-container').exists()).toBe(true);
    });

    it('displays filters panel with correct controls', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const filtersPanel = wrapper.find('.rounded-lg.border .grid.grid-cols-1.md\\:grid-cols-5');
        expect(filtersPanel.exists()).toBe(true);

        // Check date inputs
        const dateFrom = wrapper.find('#date-from');
        const dateTo = wrapper.find('#date-to');
        expect(dateFrom.exists()).toBe(true);
        expect(dateTo.exists()).toBe(true);

        // Check course selector
        const courseSelect = wrapper.find('#course-filter');
        expect(courseSelect.exists()).toBe(true);
        expect(courseSelect.find('option[value="course-1"]').text()).toBe('Introduction to Programming');

        // Check user input
        const userInput = wrapper.find('#user-filter');
        expect(userInput.exists()).toBe(true);

        // Check search input
        const searchInput = wrapper.find('#search');
        expect(searchInput.exists()).toBe(true);
    });

    it('renders aggregate visualization charts', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const chartSection = wrapper.find('.grid.grid-cols-1.gap-6.lg\\:grid-cols-3');
        expect(chartSection.exists()).toBe(true);

        const charts = chartSection.findAll('.rounded-lg.border');
        expect(charts.length).toBe(3);

        expect(charts[0].find('h3').text()).toBe('Completion Rates by Course');
        expect(charts[1].find('h3').text()).toBe('Engagement Trends');
        expect(charts[2].find('h3').text()).toBe('Certification Overview');
    });

    it('displays certification overview metrics', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const overviewCard = wrapper.findAll('.rounded-lg.border')[2];
        expect(overviewCard.text()).toContain('Certification Overview');
        expect(overviewCard.text()).toContain('Total Progress Records');
        expect(overviewCard.text()).toContain('3');
        expect(overviewCard.text()).toContain('Certified');
        expect(overviewCard.text()).toContain('1');
        expect(overviewCard.text()).toContain('Certification Rate');
        expect(overviewCard.text()).toContain('33.3%');
        expect(overviewCard.text()).toContain('Avg Engagement');
        expect(overviewCard.text()).toContain('77');
    });

    it('renders progress cards correctly', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const progressCards = wrapper.findAll('.rounded-lg.border').filter(card =>
            card.text().includes('Introduction to Programming') ||
            card.text().includes('Advanced Data Structures')
        );
        expect(progressCards.length).toBe(3);

        // Check first progress card
        const firstCard = progressCards[0];
        expect(firstCard.text()).toContain('Introduction to Programming');
        expect(firstCard.text()).toContain('7/10');
        expect(firstCard.text()).toContain('78/100');
    });

    it('displays certified badges correctly', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const certifiedBadges = wrapper.findAll('.inline-flex.items-center.px-2\\.5.py-0\\.5.rounded-full.text-xs.font-medium.bg-green-100');
        expect(certifiedBadges.length).toBe(1);
        expect(certifiedBadges[0].text()).toContain('Certified');
    });

    it('displays empty state when no progress found', async () => {
        vi.spyOn(learningStore, 'filteredProgress', 'get').mockReturnValue([]);

        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        expect(wrapper.text()).toContain('No learning progress found');
        expect(wrapper.find('.text-center').exists()).toBe(true);
    });

    it('handles date range changes', async () => {
        wrapper = mount(LearningDashboard, {
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
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const courseSelect = wrapper.find('#course-filter');
        await courseSelect.setValue('course-1');

        expect(learningStore.updateFilters).toHaveBeenCalledWith({ courseId: 'course-1' });
    });

    it('handles user filter changes', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const userInput = wrapper.find('#user-filter');
        await userInput.setValue('user-123');

        expect(learningStore.updateFilters).toHaveBeenCalledWith({ userId: 'user-123' });
    });

    it('handles search input', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const searchInput = wrapper.find('#search');
        await searchInput.setValue('programming');

        // Search is handled client-side in store, so no direct API call expected
        expect(searchInput.element.value).toBe('programming');
    });

    it('opens details modal when details button clicked', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const detailsButton = wrapper.findAll('button').find(btn => btn.attributes('aria-label') === 'View detailed progress');
        await detailsButton?.trigger('click');

        const modal = wrapper.find('[role="dialog"]');
        expect(modal.exists()).toBe(true);
        expect(modal.text()).toContain('Introduction to Programming Progress');
    });

    it('verifies certification when button clicked', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const verifyButton = wrapper.findAll('button').find(btn => btn.attributes('aria-label') === 'Verify certification');
        await verifyButton?.trigger('click');

        expect(learningStore.verifyCertification).toHaveBeenCalledWith('progress-1');
    });

    it('handles refresh button click', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const refreshButton = wrapper.find('button[aria-label="Refresh learning analytics data"]');
        await refreshButton.trigger('click');

        expect(learningStore.fetchProgress).toHaveBeenCalled();
    });

    it('applies correct accessibility attributes', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Main region
        expect(wrapper.attributes('role')).toBe('region');
        expect(wrapper.attributes('aria-label')).toBe('Learning analytics dashboard');

        // Chart accessibility
        const charts = wrapper.findAll('canvas');
        expect(charts.length).toBeGreaterThan(0);
        expect(charts[0].attributes('aria-label')).toBe('Course completion rates chart');

        // Buttons have aria-labels
        const buttons = wrapper.findAll('button');
        expect(buttons.some(btn => btn.attributes('aria-label'))).toBe(true);

        // Form controls have labels
        const inputs = wrapper.findAll('input, select');
        inputs.forEach(input => {
            const label = wrapper.find(`label[for="${input.attributes('id')}"]`);
            expect(label.exists()).toBe(true);
        });

        // Progress cards exist
        const progressCards = wrapper.findAll('.rounded-lg.border').filter(card =>
            card.text().includes('Introduction to Programming') ||
            card.text().includes('Advanced Data Structures')
        );
        expect(progressCards.length).toBeGreaterThan(0);
    });

    it('supports keyboard navigation', async () => {
        wrapper = mount(LearningDashboard, {
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
        wrapper = mount(LearningDashboard, {
            props: {
                progressData: mockProgress,
                courses: mockCourses,
                filters: {
                    dateRange: { from: '2024-01-01', to: '2024-01-31' },
                    courseId: 'course-1',
                    userId: 'user-1',
                },
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

    it('closes details modal correctly', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        // Open details modal
        const detailsButton = wrapper.findAll('button').find(btn => btn.attributes('aria-label') === 'View detailed progress');
        await detailsButton?.trigger('click');

        let modal = wrapper.find('[role="dialog"]');
        expect(modal.exists()).toBe(true);

        // Close modal
        const closeButton = modal.findAll('button').find(btn => btn.text().includes('Close'));
        await closeButton?.trigger('click');

        // Modal should be closed
        modal = wrapper.find('[role="dialog"]');
        expect(modal.exists()).toBe(false);
    });

    it('handles API errors gracefully', async () => {
        vi.spyOn(learningStore, 'fetchProgress').mockRejectedValue(new Error('API Error'));

        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const refreshButton = wrapper.find('button[aria-label="Refresh learning analytics data"]');
        await refreshButton.trigger('click');

        // Should not throw unhandled error
        expect(learningStore.error).toBe('');
    });

    it('shows verify certification button only for eligible progress', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const verifyButtons = wrapper.findAll('button').filter(btn => btn.attributes('aria-label') === 'Verify certification');
        // Only progress-1 should be eligible (7/10 modules = 70%, engagement 78 >= 70)
        expect(verifyButtons.length).toBe(1);
    });

    it('displays progress bars with correct percentages', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const progressBars = wrapper.findAll('[aria-label]');
        const completionBar = progressBars.find(bar => bar.attributes('aria-label')?.includes('70% complete'));
        expect(completionBar).toBeDefined();
    });

    it('validates filter inputs', async () => {
        wrapper = mount(LearningDashboard, {
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
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const courseSelect = wrapper.find('#course-filter');
        const options = courseSelect.findAll('option');

        expect(options.length).toBe(3); // All Courses + 2 courses
        expect(options[0].attributes('value')).toBe('');
        expect(options[1].text()).toBe('Introduction to Programming');
        expect(options[2].text()).toBe('Advanced Data Structures');
    });

    it('displays engagement scores correctly', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const engagementScores = wrapper.findAll('.text-blue-600');
        expect(engagementScores.some(score => score.text().includes('78/100'))).toBe(true);
        expect(engagementScores.some(score => score.text().includes('88/100'))).toBe(true);
    });

    it('handles uncertified progress display', async () => {
        wrapper = mount(LearningDashboard, {
            global: {
                stubs: ['canvas'],
            },
        });

        const allCards = wrapper.findAll('.rounded-lg.border').filter(card =>
            card.text().includes('Introduction to Programming') ||
            card.text().includes('Advanced Data Structures')
        );
        const uncertifiedCards = allCards.filter(card =>
            !card.text().includes('Certified')
        );
        expect(uncertifiedCards.length).toBe(2);
    });
});