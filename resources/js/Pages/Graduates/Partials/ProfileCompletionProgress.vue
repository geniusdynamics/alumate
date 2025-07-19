<script setup>
import { computed } from 'vue';

const props = defineProps({
    graduate: Object,
});

const completionPercentage = computed(() => {
    return Math.round(props.graduate.profile_completion_percentage || 0);
});

const completedFields = computed(() => {
    return props.graduate.profile_completion_fields || [];
});

const getProgressColor = (percentage) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getProgressTextColor = (percentage) => {
    if (percentage >= 80) return 'text-green-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getProgressMessage = (percentage) => {
    if (percentage >= 80) return 'Excellent! Your profile is well completed.';
    if (percentage >= 60) return 'Good progress. Consider adding more details.';
    return 'Your profile needs more information to be complete.';
};

const missingFields = computed(() => {
    const requiredFields = [
        { key: 'name', label: 'Full Name', priority: 'high' },
        { key: 'email', label: 'Email Address', priority: 'high' },
        { key: 'phone', label: 'Phone Number', priority: 'medium' },
        { key: 'address', label: 'Address', priority: 'medium' },
        { key: 'graduation_year', label: 'Graduation Year', priority: 'high' },
        { key: 'course_id', label: 'Course', priority: 'high' },
        { key: 'employment_status', label: 'Employment Status', priority: 'high' },
        { key: 'gpa', label: 'GPA', priority: 'medium' },
        { key: 'skills', label: 'Skills', priority: 'high' },
        { key: 'bio', label: 'Biography', priority: 'low' },
        { key: 'certifications', label: 'Certifications', priority: 'medium' },
    ];

    return requiredFields.filter(field => !completedFields.value.includes(field.key));
});

const highPriorityMissing = computed(() => {
    return missingFields.value.filter(field => field.priority === 'high');
});

const getFieldPriorityColor = (priority) => {
    switch (priority) {
        case 'high': return 'text-red-600';
        case 'medium': return 'text-yellow-600';
        case 'low': return 'text-gray-600';
        default: return 'text-gray-600';
    }
};

const getFieldPriorityIcon = (priority) => {
    switch (priority) {
        case 'high': return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z';
        case 'medium': return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'low': return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        default: return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Profile Completion</h3>
                <span :class="['text-2xl font-bold', getProgressTextColor(completionPercentage)]">
                    {{ completionPercentage }}%
                </span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                <div :class="['h-3 rounded-full transition-all duration-300', getProgressColor(completionPercentage)]" 
                     :style="`width: ${completionPercentage}%`"></div>
            </div>
            
            <p :class="['text-sm mb-4', getProgressTextColor(completionPercentage)]">
                {{ getProgressMessage(completionPercentage) }}
            </p>

            <!-- Completed Fields -->
            <div v-if="completedFields.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Completed Sections</h4>
                <div class="flex flex-wrap gap-1">
                    <span v-for="field in completedFields" :key="field"
                          class="inline-flex px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                        {{ field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                    </span>
                </div>
            </div>

            <!-- High Priority Missing Fields -->
            <div v-if="highPriorityMissing.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-red-700 mb-2">Critical Missing Information</h4>
                <div class="space-y-1">
                    <div v-for="field in highPriorityMissing" :key="field.key"
                         class="flex items-center text-sm">
                        <svg :class="['h-4 w-4 mr-2', getFieldPriorityColor(field.priority)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getFieldPriorityIcon(field.priority)" />
                        </svg>
                        <span :class="getFieldPriorityColor(field.priority)">{{ field.label }}</span>
                    </div>
                </div>
            </div>

            <!-- All Missing Fields -->
            <div v-if="missingFields.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-2">All Missing Information</h4>
                <div class="space-y-1">
                    <div v-for="field in missingFields.slice(0, 8)" :key="field.key"
                         class="flex items-center text-sm">
                        <svg :class="['h-4 w-4 mr-2', getFieldPriorityColor(field.priority)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getFieldPriorityIcon(field.priority)" />
                        </svg>
                        <span :class="getFieldPriorityColor(field.priority)">{{ field.label }}</span>
                        <span class="ml-auto text-xs text-gray-400 capitalize">{{ field.priority }}</span>
                    </div>
                    <div v-if="missingFields.length > 8" class="text-xs text-gray-500 ml-6">
                        +{{ missingFields.length - 8 }} more fields
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <a :href="route('graduates.edit', graduate.id)" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Complete Profile
                </a>
            </div>
        </div>
    </div>
</template>