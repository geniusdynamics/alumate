<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h4 class="text-base font-medium text-gray-900">{{ job.title }}</h4>
                <p class="text-sm text-gray-600">{{ job.company }} • {{ job.location }}</p>
                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                    <span>{{ job.job_type }}</span>
                    <span v-if="job.salary_range">{{ job.salary_range }}</span>
                    <span>{{ formatDate(job.created_at) }}</span>
                </div>
            </div>
            <div class="ml-4 flex items-center space-x-3">
                <div v-if="showMatchScore && job.match_score" class="text-sm font-medium text-green-600">
                    {{ Math.round(job.match_score) }}% match
                </div>
                <button class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                    View Job
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    job: {
        type: Object,
        required: true
    },
    showMatchScore: {
        type: Boolean,
        default: false
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>