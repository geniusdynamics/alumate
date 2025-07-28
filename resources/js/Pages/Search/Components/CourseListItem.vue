<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h4 class="text-base font-medium text-gray-900">{{ course.name }}</h4>
                <p class="text-sm text-gray-600">{{ course.code }} • {{ course.department }}</p>
                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                    <span>{{ course.level }}</span>
                    <span>{{ course.duration }} {{ course.duration_unit }}</span>
                    <span v-if="course.graduates_count">{{ course.graduates_count }} graduates</span>
                </div>
            </div>
            <div class="ml-4 flex items-center space-x-3">
                <div v-if="showMatchScore && course.match_score" class="text-sm font-medium text-green-600">
                    {{ Math.round(course.match_score) }}% match
                </div>
                <button class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                    View Details
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    course: {
        type: Object,
        required: true
    },
    showMatchScore: {
        type: Boolean,
        default: false
    }
});
</script>