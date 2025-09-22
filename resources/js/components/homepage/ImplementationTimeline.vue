<template>
    <div class="implementation-timeline rounded-lg bg-white p-6 shadow-lg">
        <!-- Header -->
        <div class="mb-8">
            <h3 class="mb-2 text-xl font-bold text-gray-900">{{ title }}</h3>
            <p v-if="subtitle" class="text-gray-600">{{ subtitle }}</p>
            <div class="mt-2 flex items-center text-sm text-gray-500">
                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        clip-rule="evenodd"
                    />
                </svg>
                Total Duration: {{ totalDuration }}
            </div>
        </div>

        <!-- Timeline -->
        <div class="relative">
            <!-- Timeline Line -->
            <div class="absolute bottom-0 left-8 top-0 w-0.5 bg-gray-200"></div>

            <!-- Timeline Items -->
            <div class="space-y-8">
                <div v-for="(phase, index) in phases" :key="phase.id" class="relative flex items-start">
                    <!-- Timeline Node -->
                    <div class="relative z-10 flex items-center justify-center">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white shadow-lg"
                            :class="getPhaseStatusColor(phase.status)"
                        >
                            <component :is="getPhaseIcon(phase.status)" class="h-6 w-6 text-white" />
                        </div>
                    </div>

                    <!-- Phase Content -->
                    <div class="ml-6 flex-1">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 transition-colors duration-200 hover:border-blue-300">
                            <!-- Phase Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ phase.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ phase.description }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">{{ phase.duration }}</div>
                                    <div
                                        class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="getStatusBadgeColor(phase.status)"
                                    >
                                        {{ formatStatus(phase.status) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Deliverables -->
                            <div class="mb-4">
                                <h5 class="mb-2 font-medium text-gray-900">Key Deliverables:</h5>
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <div v-for="deliverable in phase.deliverables" :key="deliverable" class="flex items-center text-sm text-gray-700">
                                        <svg class="mr-2 h-4 w-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ deliverable }}
                                    </div>
                                </div>
                            </div>

                            <!-- Milestones -->
                            <div v-if="phase.milestones.length > 0" class="mb-4">
                                <h5 class="mb-2 font-medium text-gray-900">Milestones:</h5>
                                <div class="space-y-2">
                                    <div
                                        v-for="milestone in phase.milestones"
                                        :key="milestone.id"
                                        class="flex items-center justify-between rounded border bg-white p-2"
                                    >
                                        <div class="flex items-center">
                                            <div class="mr-3 h-3 w-3 rounded-full" :class="getMilestoneStatusColor(milestone.status)"></div>
                                            <span class="text-sm font-medium text-gray-900">{{ milestone.name }}</span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ milestone.dueDate }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dependencies -->
                            <div v-if="phase.dependencies.length > 0" class="text-sm text-gray-600">
                                <strong>Dependencies:</strong> {{ phase.dependencies.join(', ') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Summary -->
        <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="mb-1 font-semibold text-gray-900">Implementation Progress</h4>
                    <p class="text-sm text-gray-600">{{ completedPhases }} of {{ totalPhases }} phases completed</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-600">{{ progressPercentage }}%</div>
                    <div class="text-sm text-gray-600">Complete</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-3">
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div
                        class="h-2 rounded-full bg-blue-600 transition-all duration-1000 ease-out"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { DevelopmentPhase } from '@/Types/homepage';
import { computed } from 'vue';

interface Props {
    title: string;
    subtitle?: string;
    phases: DevelopmentPhase[];
    totalDuration: string;
}

const props = defineProps<Props>();

const totalPhases = computed(() => props.phases.length);

const completedPhases = computed(() => props.phases.filter((phase) => phase.status === 'completed').length);

const progressPercentage = computed(() => {
    if (totalPhases.value === 0) return 0;
    return Math.round((completedPhases.value / totalPhases.value) * 100);
});

const getPhaseStatusColor = (status: string): string => {
    const colors = {
        pending: 'bg-gray-400',
        in_progress: 'bg-blue-500',
        completed: 'bg-green-500',
        delayed: 'bg-red-500',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-400';
};

const getStatusBadgeColor = (status: string): string => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        delayed: 'bg-red-100 text-red-800',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
};

const getMilestoneStatusColor = (status: string): string => {
    const colors = {
        pending: 'bg-gray-300',
        in_progress: 'bg-blue-400',
        completed: 'bg-green-400',
        delayed: 'bg-red-400',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-300';
};

const getPhaseIcon = (status: string) => {
    // Return SVG component based on status
    return 'svg'; // Placeholder - would be actual icon components
};

const formatStatus = (status: string): string => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};
</script>

<style scoped>
/* Timeline animations */
.implementation-timeline .relative:hover .bg-gray-50 {
    @apply border-blue-300 bg-blue-50;
}

/* Smooth transitions for all interactive elements */
.implementation-timeline * {
    @apply transition-colors duration-200;
}

/* Timeline line gradient effect */
.implementation-timeline::before {
    content: '';
    position: absolute;
    left: 2rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #3b82f6, #10b981);
    opacity: 0.3;
}
</style>














