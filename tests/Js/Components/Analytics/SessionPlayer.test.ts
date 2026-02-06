import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import SessionPlayer from '../../../../resources/js/Components/Analytics/SessionPlayer.vue';
import type { SessionData } from '../../../../resources/js/Types/analytics';

// Mock the composable
vi.mock('../../../../resources/js/Composables/useSessionPlayback', () => ({
    useSessionPlayback: vi.fn(() => ({
        state: {
            value: {
                currentTime: 0,
                duration: 60000, // 1 minute
                isPlaying: false,
                speed: 1,
                isLoading: false,
                error: null,
                sessionData: null,
                annotations: [],
                currentEventIndex: -1,
            }
        },
        progress: { value: 0 },
        currentEvent: { value: null },
        eventsInTimeRange: { value: [] },
        annotationsAtCurrentTime: { value: [] },
        play: vi.fn(),
        pause: vi.fn(),
        togglePlayPause: vi.fn(),
        seek: vi.fn(),
        seekByProgress: vi.fn(),
        setSpeed: vi.fn(),
        reset: vi.fn(),
        addAnnotation: vi.fn(),
        removeAnnotation: vi.fn(),
        fetchSessionData: vi.fn(),
    }))
}));

describe('SessionPlayer', () => {
    const mockSessionData: SessionData = {
        events: [
            {
                type: 'page_view',
                timestamp: 0,
                url: '/home',
            },
            {
                type: 'click',
                timestamp: 5000,
                x: 100,
                y: 200,
                element: 'button',
            },
            {
                type: 'scroll',
                timestamp: 10000,
                x: 0,
                y: 500,
            },
        ],
        metadata: {
            duration: 60000,
            pages: ['/home', '/about'],
            insights: ['High engagement on homepage'],
            privacyMasked: true,
        },
    };

    const defaultProps = {
        sessionId: 'test-session-123',
        autoPlay: false,
        initialSpeed: 1,
        showAnnotations: true,
    };

    beforeEach(() => {
        vi.clearAllMocks();
        // Reset localStorage mock
        Object.defineProperty(window, 'localStorage', {
            value: {
                getItem: vi.fn(() => null),
                setItem: vi.fn(() => null),
                removeItem: vi.fn(() => null),
                clear: vi.fn(() => null),
            },
            writable: true,
        });
    });

    afterEach(() => {
        vi.clearAllTimers();
    });

    it('renders loading state initially', () => {
        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('Loading session data...');
    });

    it('renders error state when API fails', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 0,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: 'Failed to load session data',
                    sessionData: null,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('Failed to load session data');
    });

    it('renders session player with controls when data is loaded', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 15000,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: 1,
                }
            },
            progress: { value: 25 },
            currentEvent: { value: mockSessionData.events[1] },
            eventsInTimeRange: { value: [mockSessionData.events[1]] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('Session Playback');
        expect(wrapper.text()).toContain('Duration: 1:00');
        expect(wrapper.text()).toContain('Events: 3');
        expect(wrapper.text()).toContain('0:15 / 1:00');
    });

    it('displays play/pause button with correct aria-label', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        const playButton = wrapper.find('button[aria-label="Start playback"]');
        expect(playButton.exists()).toBe(true);
    });

    it('calls togglePlayPause when play button is clicked', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);
        const mockTogglePlayPause = vi.fn();

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: mockTogglePlayPause,
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        const playButton = wrapper.find('button[aria-label="Start playback"]');
        await playButton.trigger('click');

        expect(mockTogglePlayPause).toHaveBeenCalled();
    });

    it('displays progress bar and handles seeking', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);
        const mockSeekByProgress = vi.fn();

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 15000,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: 1,
                }
            },
            progress: { value: 25 },
            currentEvent: { value: mockSessionData.events[1] },
            eventsInTimeRange: { value: [mockSessionData.events[1]] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: mockSeekByProgress,
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        const progressInput = wrapper.find('input[type="range"]');
        expect(progressInput.exists()).toBe(true);

        await progressInput.setValue(50);
        await progressInput.trigger('input');

        expect(mockSeekByProgress).toHaveBeenCalledWith(50);
    });

    it('displays speed control and handles speed changes', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);
        const mockSetSpeed = vi.fn();

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: mockSetSpeed,
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        const speedSelect = wrapper.find('#speed-control');
        expect(speedSelect.exists()).toBe(true);

        await speedSelect.setValue('2');
        await speedSelect.trigger('change');

        expect(mockSetSpeed).toHaveBeenCalledWith(2);
    });

    it('renders timeline with event markers', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 5000,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: 1,
                }
            },
            progress: { value: 8.33 },
            currentEvent: { value: mockSessionData.events[1] },
            eventsInTimeRange: { value: [mockSessionData.events[1]] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        // Check for event markers
        const eventMarkers = wrapper.findAll('[role="button"][tabindex="0"]');
        expect(eventMarkers.length).toBeGreaterThan(0);

        // Check for current time indicator
        const currentTimeIndicator = wrapper.find('.bg-red-500');
        expect(currentTimeIndicator.exists()).toBe(true);
    });

    it('displays current event details', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 5000,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: 1,
                }
            },
            progress: { value: 8.33 },
            currentEvent: { value: mockSessionData.events[1] },
            eventsInTimeRange: { value: [mockSessionData.events[1]] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('Current Event');
        expect(wrapper.text()).toContain('Click');
        expect(wrapper.text()).toContain('button');
        expect(wrapper.text()).toContain('(100, 200)');
    });

    it('handles annotation management', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);
        const mockAddAnnotation = vi.fn();
        const mockRemoveAnnotation = vi.fn();

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [
                        {
                            id: 'annotation-1',
                            timestamp: 10000,
                            content: 'Test annotation',
                            type: 'note',
                            createdAt: Date.now(),
                        },
                    ],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: mockAddAnnotation,
            removeAnnotation: mockRemoveAnnotation,
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        // Check annotation display
        expect(wrapper.text()).toContain('Test annotation');

        // Test adding annotation
        const addButton = wrapper.find('button[aria-label="Add new annotation"]');
        await addButton.trigger('click');

        const modal = wrapper.find('[role="dialog"]');
        expect(modal.exists()).toBe(true);

        // Test removing annotation
        const removeButton = wrapper.find('button[aria-label="Remove annotation: Test annotation"]');
        await removeButton.trigger('click');

        expect(mockRemoveAnnotation).toHaveBeenCalledWith('annotation-1');
    });

    it('displays privacy masking warning when applicable', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('This session contains privacy masking for sensitive data.');
    });

    it('handles keyboard shortcuts', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);
        const mockTogglePlayPause = vi.fn();
        const mockSeek = vi.fn();

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 30000,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: 2,
                }
            },
            progress: { value: 50 },
            currentEvent: { value: mockSessionData.events[2] },
            eventsInTimeRange: { value: [mockSessionData.events[2]] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: mockTogglePlayPause,
            seek: mockSeek,
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        // Test spacebar for play/pause
        await wrapper.trigger('keydown', { key: ' ' });
        expect(mockTogglePlayPause).toHaveBeenCalled();

        // Test arrow keys for seeking
        await wrapper.trigger('keydown', { key: 'ArrowLeft' });
        expect(mockSeek).toHaveBeenCalledWith(10000); // 30s - 20s = 10s

        await wrapper.trigger('keydown', { key: 'ArrowRight' });
        expect(mockSeek).toHaveBeenCalledWith(50000); // 30s + 20s = 50s
    });

    it('has proper accessibility attributes', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 0,
                    duration: 60000,
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 0 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        // Check main container accessibility
        const mainContainer = wrapper.find('[role="region"]');
        expect(mainContainer.attributes('aria-label')).toBe('Session recording player');

        // Check progress bar accessibility
        const progressInput = wrapper.find('input[type="range"]');
        expect(progressInput.attributes('aria-label')).toBe('Seek through session timeline');

        // Check speed control accessibility
        const speedSelect = wrapper.find('#speed-control');
        expect(speedSelect.attributes('aria-label')).toBe('Playback speed');
    });

    it('formats time correctly', async () => {
        const mockComposable = await import('../../../../resources/js/Composables/useSessionPlayback');
        const useSessionPlaybackMock = vi.mocked(mockComposable.useSessionPlayback);

        useSessionPlaybackMock.mockReturnValue({
            state: {
                value: {
                    currentTime: 90000, // 1.5 minutes
                    duration: 360000, // 6 minutes
                    isPlaying: false,
                    speed: 1,
                    isLoading: false,
                    error: null,
                    sessionData: mockSessionData,
                    annotations: [],
                    currentEventIndex: -1,
                }
            },
            progress: { value: 25 },
            currentEvent: { value: null },
            eventsInTimeRange: { value: [] },
            annotationsAtCurrentTime: { value: [] },
            play: vi.fn(),
            pause: vi.fn(),
            togglePlayPause: vi.fn(),
            seek: vi.fn(),
            seekByProgress: vi.fn(),
            setSpeed: vi.fn(),
            reset: vi.fn(),
            addAnnotation: vi.fn(),
            removeAnnotation: vi.fn(),
            fetchSessionData: vi.fn(),
        });

        const wrapper = mount(SessionPlayer, {
            props: defaultProps,
        });

        expect(wrapper.text()).toContain('1:30 / 6:00');
    });
});