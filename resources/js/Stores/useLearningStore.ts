import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    LearningProgress,
    Course,
    LearningApiResponse
} from '../Types/analytics';

export const useLearningStore = defineStore('learning', () => {
    // State
    const progress = ref<LearningProgress[]>([]);
    const courses = ref<Course[]>([]);
    const loading = ref(false);
    const error = ref('');
    const filters = ref<{
        dateRange?: { from: string; to: string };
        courseId?: string;
        userId?: string;
        search?: string;
    }>({});

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);

    const filteredProgress = computed(() => {
        let filtered = [...progress.value];

        // Filter by course
        if (filters.value.courseId) {
            filtered = filtered.filter(p => p.course_id === filters.value.courseId);
        }

        // Filter by user
        if (filters.value.userId) {
            filtered = filtered.filter(p => p.user_id === filters.value.userId);
        }

        // Filter by search
        if (filters.value.search) {
            const searchTerm = filters.value.search.toLowerCase();
            filtered = filtered.filter(p => {
                const course = courses.value.find(c => c.id === p.course_id);
                return course?.name.toLowerCase().includes(searchTerm) ||
                       p.user_id.toLowerCase().includes(searchTerm);
            });
        }

        // Filter by date range
        if (filters.value.dateRange) {
            const fromDate = new Date(filters.value.dateRange.from);
            const toDate = new Date(filters.value.dateRange.to);
            filtered = filtered.filter(p => {
                if (!p.updated_at) return false;
                const updatedDate = new Date(p.updated_at);
                return updatedDate >= fromDate && updatedDate <= toDate;
            });
        }

        return filtered;
    });

    const learningStats = computed(() => {
        const filtered = filteredProgress.value;
        const totalProgress = filtered.length;
        const certifiedCount = filtered.filter(p => p.certified).length;
        const avgEngagement = filtered.length > 0
            ? filtered.reduce((sum, p) => sum + (p.engagement_score || 0), 0) / filtered.length
            : 0;
        const certificationRate = totalProgress > 0 ? (certifiedCount / totalProgress) * 100 : 0;

        // Completion by course
        const completionByCourse = courses.value.map(course => {
            const courseProgress = filtered.filter(p => p.course_id === course.id);
            const totalStudents = courseProgress.length;
            const certifiedCount = courseProgress.filter(p => p.certified).length;
            const completionRate = totalStudents > 0
                ? (courseProgress.reduce((sum, p) => sum + (p.modules_completed / course.modules_count), 0) / totalStudents) * 100
                : 0;

            return {
                courseId: course.id,
                courseName: course.name,
                completionRate: Math.round(completionRate * 10) / 10,
                totalStudents,
                certifiedCount,
            };
        });

        return {
            totalProgress,
            certifiedCount,
            avgEngagement: Math.round(avgEngagement),
            certificationRate: Math.round(certificationRate * 10) / 10,
            completionByCourse,
        };
    });

    // Actions
    const fetchProgress = async (params?: {
        date_from?: string;
        date_to?: string;
        course_id?: string;
        user_id?: string;
        search?: string;
    }): Promise<LearningProgress[]> => {
        loading.value = true;
        error.value = '';

        try {
            const queryParams = new URLSearchParams();

            if (params?.date_from) queryParams.append('date_from', params.date_from);
            if (params?.date_to) queryParams.append('date_to', params.date_to);
            if (params?.course_id) queryParams.append('course_id', params.course_id);
            if (params?.user_id) queryParams.append('user_id', params.user_id);
            if (params?.search) queryParams.append('search', params.search);

            const response = await axios.get<LearningApiResponse>(`/api/analytics/learning?${queryParams}`);
            const data = response.data.data as LearningProgress[];
            progress.value = data;

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch learning progress';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const trackInteraction = async (interaction: {
        progress_id: string;
        interaction_type: 'view' | 'complete' | 'quiz' | 'assignment';
        metadata?: Record<string, any>;
    }): Promise<void> => {
        try {
            await axios.post('/api/analytics/learning/interactions', interaction);

            // Optimistic update - could increment engagement score
            const progressItem = progress.value.find(p => p.id === interaction.progress_id);
            if (progressItem && progressItem.engagement_score) {
                progressItem.engagement_score = Math.min(100, progressItem.engagement_score + 1);
                progressItem.updated_at = new Date().toISOString();
            }
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to track interaction';
            throw err;
        }
    };

    const updateProgress = async (progressId: string, updates: Partial<LearningProgress>): Promise<LearningProgress> => {
        try {
            const response = await axios.patch(`/api/analytics/learning/${progressId}`, updates);
            const updatedProgress = response.data.data as LearningProgress;

            // Optimistic update
            const index = progress.value.findIndex(p => p.id === progressId);
            if (index !== -1) {
                progress.value[index] = { ...progress.value[index], ...updatedProgress };
            }

            return updatedProgress;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update progress';
            throw err;
        }
    };

    const verifyCertification = async (progressId: string): Promise<LearningProgress> => {
        try {
            const response = await axios.post(`/api/analytics/learning/${progressId}/verify-certification`);
            const updatedProgress = response.data.data as LearningProgress;

            // Optimistic update
            const index = progress.value.findIndex(p => p.id === progressId);
            if (index !== -1) {
                progress.value[index] = { ...progress.value[index], ...updatedProgress };
            }

            return updatedProgress;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to verify certification';
            throw err;
        }
    };

    const updateFilters = (newFilters: Partial<typeof filters.value>) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const clearError = () => {
        error.value = '';
    };

    const clearData = () => {
        progress.value = [];
        courses.value = [];
        error.value = '';
    };

    return {
        // State
        progress,
        courses,
        loading,
        error,
        filters,

        // Getters
        isLoading,
        hasError,
        filteredProgress,
        learningStats,

        // Actions
        fetchProgress,
        trackInteraction,
        updateProgress,
        verifyCertification,
        updateFilters,
        clearError,
        clearData,
    };
});