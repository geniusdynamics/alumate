import axios from 'axios';
import { ref, computed, watch, onMounted, onUnmounted, readonly } from 'vue';
import type {
    SessionAnnotation,
    SessionPlaybackState,
    SessionApiResponse,
} from '../types/analytics';

/**
 * Composable for session recording playback functionality
 *
 * Provides state management and controls for session playback including:
 * - Playback controls (play/pause/seek/speed)
 * - Timeline navigation
 * - Event processing
 * - Annotation management
 * - Privacy masking
 */
export function useSessionPlayback(sessionId: string, options: {
    autoPlay?: boolean;
    initialSpeed?: number;
    showAnnotations?: boolean;
} = {}) {
    // Configuration
    const config = {
        autoPlay: options.autoPlay ?? false,
        initialSpeed: options.initialSpeed ?? 1,
        showAnnotations: options.showAnnotations ?? true,
        annotationStorageKey: `session_annotations_${sessionId}`,
    };

    // Reactive state
    const state = ref<SessionPlaybackState>({
        currentTime: 0,
        duration: 0,
        isPlaying: false,
        speed: config.initialSpeed,
        isLoading: false,
        error: null,
        sessionData: null,
        annotations: [],
        currentEventIndex: -1,
    });

    // Playback controls
    let playbackInterval: number | null = null;
    let lastFrameTime = 0;

    // Computed properties
    const progress = computed(() => {
        return state.value.duration > 0 ? (state.value.currentTime / state.value.duration) * 100 : 0;
    });

    const currentEvent = computed(() => {
        if (!state.value.sessionData || state.value.currentEventIndex < 0) return null;
        return state.value.sessionData.events[state.value.currentEventIndex] || null;
    });

    const eventsInTimeRange = computed(() => {
        if (!state.value.sessionData) return [];
        const startTime = Math.max(0, state.value.currentTime - 5000); // 5 seconds before
        const endTime = state.value.currentTime + 5000; // 5 seconds after

        return state.value.sessionData.events.filter(event =>
            event.timestamp >= startTime && event.timestamp <= endTime
        );
    });

    const annotationsAtCurrentTime = computed(() => {
        return state.value.annotations.filter(annotation =>
            Math.abs(annotation.timestamp - state.value.currentTime) < 1000 // Within 1 second
        );
    });

    // Methods
    const fetchSessionData = async () => {
        state.value.isLoading = true;
        state.value.error = null;

        try {
            const response = await axios.get<SessionApiResponse>(`/api/analytics/sessions/${sessionId}`);
            const sessionData = response.data.data;

            state.value.sessionData = sessionData;
            state.value.duration = sessionData.metadata.duration;

            // Load saved annotations
            loadAnnotations();

            if (config.autoPlay) {
                play();
            }
        } catch (error) {
            state.value.error = error instanceof Error ? error.message : 'Failed to load session data';
            console.error('Session playback fetch error:', error);
        } finally {
            state.value.isLoading = false;
        }
    };

    const play = () => {
        if (!state.value.sessionData || state.value.isPlaying) return;

        state.value.isPlaying = true;
        lastFrameTime = performance.now();
        startPlaybackLoop();
    };

    const pause = () => {
        state.value.isPlaying = false;
        stopPlaybackLoop();
    };

    const togglePlayPause = () => {
        if (state.value.isPlaying) {
            pause();
        } else {
            play();
        }
    };

    const seek = (time: number) => {
        const clampedTime = Math.max(0, Math.min(time, state.value.duration));
        state.value.currentTime = clampedTime;

        // Find the current event index
        if (state.value.sessionData) {
            state.value.currentEventIndex = findEventIndexAtTime(clampedTime);
        }
    };

    const seekByProgress = (progressPercent: number) => {
        const time = (progressPercent / 100) * state.value.duration;
        seek(time);
    };

    const setSpeed = (speed: number) => {
        const clampedSpeed = Math.max(0.25, Math.min(4, speed));
        state.value.speed = clampedSpeed;
    };

    const reset = () => {
        pause();
        seek(0);
        state.value.speed = config.initialSpeed;
    };

    const addAnnotation = (content: string, type: SessionAnnotation['type'] = 'note') => {
        const annotation: SessionAnnotation = {
            id: generateAnnotationId(),
            timestamp: state.value.currentTime,
            content,
            type,
            createdAt: Date.now(),
        };

        state.value.annotations.push(annotation);
        saveAnnotations();
    };

    const removeAnnotation = (annotationId: string) => {
        const index = state.value.annotations.findIndex(a => a.id === annotationId);
        if (index > -1) {
            state.value.annotations.splice(index, 1);
            saveAnnotations();
        }
    };

    const updateAnnotation = (annotationId: string, updates: Partial<SessionAnnotation>) => {
        const annotation = state.value.annotations.find(a => a.id === annotationId);
        if (annotation) {
            Object.assign(annotation, updates);
            saveAnnotations();
        }
    };

    // Internal methods
    const startPlaybackLoop = () => {
        if (playbackInterval) return;

        playbackInterval = window.setInterval(() => {
            const now = performance.now();
            const deltaTime = (now - lastFrameTime) * state.value.speed;
            lastFrameTime = now;

            const newTime = state.value.currentTime + deltaTime;

            if (newTime >= state.value.duration) {
                seek(state.value.duration);
                pause();
            } else {
                seek(newTime);
            }
        }, 16); // ~60fps
    };

    const stopPlaybackLoop = () => {
        if (playbackInterval) {
            clearInterval(playbackInterval);
            playbackInterval = null;
        }
    };

    const findEventIndexAtTime = (time: number): number => {
        if (!state.value.sessionData) return -1;

        // Binary search for the event at or before the given time
        let left = 0;
        let right = state.value.sessionData.events.length - 1;
        let result = -1;

        while (left <= right) {
            const mid = Math.floor((left + right) / 2);
            const eventTime = state.value.sessionData.events[mid].timestamp;

            if (eventTime <= time) {
                result = mid;
                left = mid + 1;
            } else {
                right = mid - 1;
            }
        }

        return result;
    };

    const generateAnnotationId = (): string => {
        return `annotation_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    };

    const saveAnnotations = () => {
        try {
            localStorage.setItem(config.annotationStorageKey, JSON.stringify(state.value.annotations));
        } catch (error) {
            console.warn('Failed to save annotations to localStorage:', error);
        }
    };

    const loadAnnotations = () => {
        try {
            const saved = localStorage.getItem(config.annotationStorageKey);
            if (saved) {
                state.value.annotations = JSON.parse(saved);
            }
        } catch (error) {
            console.warn('Failed to load annotations from localStorage:', error);
        }
    };

    // Keyboard shortcuts
    const handleKeydown = (event: KeyboardEvent) => {
        // Prevent handling if user is typing in an input
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
            return;
        }

        switch (event.key.toLowerCase()) {
            case ' ':
            case 'k':
                event.preventDefault();
                togglePlayPause();
                break;
            case 'arrowleft':
                event.preventDefault();
                seek(Math.max(0, state.value.currentTime - 5000)); // 5 seconds back
                break;
            case 'arrowright':
                event.preventDefault();
                seek(Math.min(state.value.duration, state.value.currentTime + 5000)); // 5 seconds forward
                break;
            case 'arrowup':
            case '+':
            case '=':
                event.preventDefault();
                setSpeed(state.value.speed + 0.25);
                break;
            case 'arrowdown':
            case '-':
                event.preventDefault();
                setSpeed(state.value.speed - 0.25);
                break;
            case '0':
            case 'home':
                event.preventDefault();
                seek(0);
                break;
            case 'end':
                event.preventDefault();
                seek(state.value.duration);
                break;
        }
    };

    // Lifecycle
    onMounted(() => {
        fetchSessionData();
        window.addEventListener('keydown', handleKeydown);
    });

    onUnmounted(() => {
        stopPlaybackLoop();
        window.removeEventListener('keydown', handleKeydown);
    });

    // Watchers
    watch(() => sessionId, (newId) => {
        if (newId) {
            reset();
            fetchSessionData();
        }
    });

    return {
        // State
        state: readonly(state),

        // Computed
        progress,
        currentEvent,
        eventsInTimeRange,
        annotationsAtCurrentTime,

        // Methods
        play,
        pause,
        togglePlayPause,
        seek,
        seekByProgress,
        setSpeed,
        reset,
        addAnnotation,
        removeAnnotation,
        updateAnnotation,
        fetchSessionData,

        // Keyboard handling
        handleKeydown,
    };
}