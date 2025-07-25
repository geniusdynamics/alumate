<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-2">
            <h4 class="font-medium text-gray-900">{{ search.name }}</h4>
            <div class="flex space-x-1">
                <button
                    @click="$emit('execute', search)"
                    class="text-indigo-600 hover:text-indigo-500 text-sm"
                    title="Execute Search"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <button
                    @click="$emit('edit', search)"
                    class="text-gray-400 hover:text-gray-600 text-sm"
                    title="Edit Search"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button
                    @click="$emit('delete', search)"
                    class="text-red-400 hover:text-red-600 text-sm"
                    title="Delete Search"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="text-sm text-gray-600 mb-3">
            <span class="capitalize">{{ search.search_type }}</span> search
            <span v-if="search.results_count !== null" class="ml-2">
                ({{ search.results_count }} results)
            </span>
        </div>

        <div class="text-xs text-gray-500 mb-3">
            {{ getSearchSummary(search.search_criteria) }}
        </div>

        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <button
                    @click="$emit('toggle-alert', search)"
                    :class="[
                        'flex items-center space-x-1 text-xs px-2 py-1 rounded',
                        search.alert_enabled
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 4.828A4 4 0 015.5 4H9v1H5.5a3 3 0 00-2.121.879l-.707.707A3 3 0 002 8.5V12h1V8.5a2 2 0 01.586-1.414l.707-.707A2 2 0 015.5 6H9V5H5.5a3 3 0 00-2.121.879z" />
                    </svg>
                    <span>{{ search.alert_enabled ? 'Alerts On' : 'Alerts Off' }}</span>
                </button>
                
                <span v-if="search.alert_enabled" class="text-xs text-gray-500">
                    {{ search.alert_frequency }}
                </span>
            </div>

            <span class="text-xs text-gray-400">
                {{ formatDate(search.updated_at) }}
            </span>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        search: Object,
    },

    emits: ['execute', 'edit', 'delete', 'toggle-alert'],

    methods: {
        getSearchSummary(criteria) {
            const parts = []
            
            if (criteria.keywords) {
                parts.push(`"${criteria.keywords}"`)
            }
            
            if (criteria.location) {
                parts.push(`in ${criteria.location}`)
            }
            
            if (criteria.skills && criteria.skills.length > 0) {
                parts.push(`skills: ${criteria.skills.slice(0, 2).join(', ')}${criteria.skills.length > 2 ? '...' : ''}`)
            }
            
            if (criteria.job_type) {
                parts.push(`${criteria.job_type.replace('_', ' ')}`)
            }
            
            if (criteria.salary_min) {
                parts.push(`min salary: $${criteria.salary_min.toLocaleString()}`)
            }
            
            return parts.length > 0 ? parts.join(' • ') : 'All results'
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        },
    },
}
</script>