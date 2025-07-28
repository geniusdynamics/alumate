<template>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ graduate.name }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ graduate.course }} • {{ graduate.graduation_year }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    {{ graduate.institution }}
                </p>
                
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getEmploymentStatusClass(graduate.employment_status)">
                        {{ formatEmploymentStatus(graduate.employment_status) }}
                    </span>
                </div>
                
                <div v-if="graduate.skills && graduate.skills.length" class="mt-3">
                    <div class="flex flex-wrap gap-1">
                        <span v-for="skill in graduate.skills.slice(0, 3)" 
                              :key="skill"
                              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                            {{ skill }}
                        </span>
                        <span v-if="graduate.skills.length > 3" 
                              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                            +{{ graduate.skills.length - 3 }} more
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="ml-4 flex-shrink-0">
                <div v-if="showMatchScore && graduate.match_score" class="text-right mb-2">
                    <span class="text-sm font-medium text-green-600">
                        {{ Math.round(graduate.match_score) }}% match
                    </span>
                </div>
                
                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    View Profile
                </button>
            </div>
        </div>
        
        <div v-if="graduate.current_position" class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                <span class="font-medium">Current Position:</span>
                {{ graduate.current_position }}
                <span v-if="graduate.current_company"> at {{ graduate.current_company }}</span>
            </p>
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
        'employed': 'bg-green-100 text-green-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'seeking': 'bg-yellow-100 text-yellow-800',
        'not_seeking': 'bg-gray-100 text-gray-800',
        'further_study': 'bg-purple-100 text-purple-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
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