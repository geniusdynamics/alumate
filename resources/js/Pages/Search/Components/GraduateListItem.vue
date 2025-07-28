<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h4 class="text-base font-medium text-gray-900">{{ graduate.name }}</h4>
                <p class="text-sm text-gray-600">{{ graduate.course }} • {{ graduate.graduation_year }}</p>
                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                    <span :class="getEmploymentStatusClass(graduate.employment_status)">
                        {{ formatEmploymentStatus(graduate.employment_status) }}
                    </span>
                    <span v-if="graduate.current_position">{{ graduate.current_position }}</span>
                </div>
            </div>
            <div class="ml-4 flex items-center space-x-3">
                <div v-if="showMatchScore && graduate.match_score" class="text-sm font-medium text-green-600">
                    {{ Math.round(graduate.match_score) }}% match
                </div>
                <button class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                    View Profile
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    graduate: {
        type: Object,
        required: true
    },
    showMatchScore: {
        type: Boolean,
        default: false
    }
});

const getEmploymentStatusClass = (status) => {
    const classes = {
        'employed': 'text-green-600',
        'self_employed': 'text-blue-600',
        'seeking': 'text-yellow-600',
        'not_seeking': 'text-gray-600',
        'further_study': 'text-purple-600'
    };
    return classes[status] || 'text-gray-600';
};

const formatEmploymentStatus = (status) => {
    const labels = {
        'employed': 'Employed',
        'self_employed': 'Self Employed',
        'seeking': 'Job Seeking',
        'not_seeking': 'Not Seeking',
        'further_study': 'Further Study'
    };
    return labels[status] || status;
};
</script>