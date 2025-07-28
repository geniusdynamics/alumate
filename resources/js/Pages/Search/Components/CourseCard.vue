<template>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ course.name }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ course.code }} • {{ course.level }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    {{ course.department }} • {{ course.institution }}
                </p>
                
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ course.duration }} {{ course.duration_unit }}
                    </span>
                    <span v-if="course.status" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getStatusClass(course.status)">
                        {{ formatStatus(course.status) }}
                    </span>
                </div>
                
                <div v-if="course.description" class="mt-3">
                    <p class="text-sm text-gray-600 line-clamp-2">
                        {{ course.description }}
                    </p>
                </div>
                
                <div v-if="course.skills && course.skills.length" class="mt-3">
                    <div class="flex flex-wrap gap-1">
                        <span v-for="skill in course.skills.slice(0, 4)" 
                              :key="skill"
                              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                            {{ skill }}
                        </span>
                        <span v-if="course.skills.length > 4" 
                              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                            +{{ course.skills.length - 4 }} more
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="ml-4 flex-shrink-0">
                <div v-if="showMatchScore && course.match_score" class="text-right mb-2">
                    <span class="text-sm font-medium text-green-600">
                        {{ Math.round(course.match_score) }}% match
                    </span>
                </div>
                
                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    View Details
                </button>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div>
                    <span class="font-medium">Graduates:</span>
                    {{ course.graduates_count || 0 }}
                </div>
                <div v-if="course.employment_rate">
                    <span class="font-medium">Employment Rate:</span>
                    <span :class="getEmploymentRateClass(course.employment_rate)">
                        {{ course.employment_rate }}%
                    </span>
                </div>
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

const getStatusClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-red-100 text-red-800',
        'archived': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatStatus = (status) => {
    const labels = {
        'active': 'Active',
        'inactive': 'Inactive',
        'archived': 'Archived'
    };
    return labels[status] || status;
};

const getEmploymentRateClass = (rate) => {
    if (rate >= 80) return 'text-green-600 font-medium';
    if (rate >= 60) return 'text-yellow-600 font-medium';
    return 'text-red-600 font-medium';
};
</script>