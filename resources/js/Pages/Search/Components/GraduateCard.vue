<template>
    <div class="rounded-lg bg-white p-6 shadow-md transition-shadow hover:shadow-lg">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ graduate.name }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">{{ graduate.course }} â€¢ {{ graduate.graduation_year }}</p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ graduate.institution }}
                </p>

                <div class="mt-3">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="getEmploymentStatusClass(graduate.employment_status)"
                    >
                        {{ formatEmploymentStatus(graduate.employment_status) }}
                    </span>
                </div>

                <div v-if="graduate.skills && graduate.skills.length" class="mt-3">
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="skill in graduate.skills.slice(0, 3)"
                            :key="skill"
                            class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                        >
                            {{ skill }}
                        </span>
                        <span
                            v-if="graduate.skills.length > 3"
                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                        >
                            +{{ graduate.skills.length - 3 }} more
                        </span>
                    </div>
                </div>
            </div>

            <div class="ml-4 flex-shrink-0">
                <div v-if="showMatchScore && graduate.match_score" class="mb-2 text-right">
                    <span class="text-sm font-medium text-green-600"> {{ Math.round(graduate.match_score) }}% match </span>
                </div>

                <button
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    View Profile
                </button>
            </div>
        </div>

        <div v-if="graduate.current_position" class="mt-4 border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-600">
                <span class="font-medium">Current Position:</span>
                {{ graduate.current_position }}
                <span v-if="graduate.current_company"> at {{ graduate.current_company }}</span>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps({
    graduate: {
        type: Object,
        required: true,
    },
    showMatchScore: {
        type: Boolean,
        default: false,
    },
});

const getEmploymentStatusClass = (status) => {
    const classes = {
        employed: 'bg-green-100 text-green-800',
        self_employed: 'bg-blue-100 text-blue-800',
        seeking: 'bg-yellow-100 text-yellow-800',
        not_seeking: 'bg-gray-100 text-gray-800',
        further_study: 'bg-purple-100 text-purple-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatEmploymentStatus = (status) => {
    const labels = {
        employed: 'Employed',
        self_employed: 'Self Employed',
        seeking: 'Job Seeking',
        not_seeking: 'Not Seeking',
        further_study: 'Further Study',
    };
    return labels[status] || status;
};
</script>

