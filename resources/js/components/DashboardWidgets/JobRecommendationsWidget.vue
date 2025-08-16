<template>
    <div class="card-mobile bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Recommended Jobs</h3>
            <BriefcaseIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
        </div>
        
        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        <div class="flex space-x-2">
                            <div class="h-6 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Job Recommendations -->
            <div v-else-if="jobs.length > 0" class="space-y-4">
                <div 
                    v-for="job in jobs" 
                    :key="job.id"
                    class="p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 transition-colors cursor-pointer"
                    @click="viewJob(job)"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">
                                {{ job.title }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {{ job.company_name }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200">
                                {{ job.match_score }}% match
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400 mb-3">
                        <span class="flex items-center space-x-1">
                            <MapPinIcon class="h-3 w-3" />
                            <span>{{ job.location }}</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <ClockIcon class="h-3 w-3" />
                            <span>{{ job.employment_type }}</span>
                        </span>
                        <span v-if="job.salary_range" class="flex items-center space-x-1">
                            <CurrencyDollarIcon class="h-3 w-3" />
                            <span>{{ job.salary_range }}</span>
                        </span>
                    </div>
                    
                    <!-- Connection Insights -->
                    <div v-if="job.connection_insights" class="mb-3">
                        <div class="flex items-center space-x-2 text-xs text-blue-600 dark:text-blue-400">
                            <UsersIcon class="h-3 w-3" />
                            <span>{{ job.connection_insights }}</span>
                        </div>
                    </div>
                    
                    <!-- Skills Match -->
                    <div v-if="job.matching_skills && job.matching_skills.length > 0" class="flex flex-wrap gap-1 mb-3">
                        <span 
                            v-for="skill in job.matching_skills.slice(0, 3)" 
                            :key="skill"
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200"
                        >
                            {{ skill }}
                        </span>
                        <span 
                            v-if="job.matching_skills.length > 3"
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                        >
                            +{{ job.matching_skills.length - 3 }} more
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Posted {{ formatTimeAgo(job.created_at) }}
                        </span>
                        <button
                            @click.stop="saveJob(job)"
                            :class="[
                                'text-xs font-medium px-3 py-1 rounded transition-colors',
                                job.is_saved 
                                    ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200' 
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                        >
                            {{ job.is_saved ? 'Saved' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-6">
                <BriefcaseIcon class="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No job recommendations</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Complete your profile to get personalized job recommendations
                </p>
            </div>
        </div>
        
        <!-- View All Link -->
        <div v-if="jobs.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Link 
                :href="route('jobs.dashboard')"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center justify-center space-x-1"
            >
                <span>View All Jobs</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    BriefcaseIcon,
    MapPinIcon,
    ClockIcon,
    CurrencyDollarIcon,
    UsersIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const loading = ref(true)
const jobs = ref([])

const props = defineProps({
    limit: {
        type: Number,
        default: 3
    }
})

onMounted(async () => {
    await fetchJobRecommendations()
})

const fetchJobRecommendations = async () => {
    try {
        loading.value = true
        const response = await fetch(`/api/dashboard/job-recommendations?limit=${props.limit}`)
        const data = await response.json()
        jobs.value = data.jobs || []
    } catch (error) {
        console.error('Failed to fetch job recommendations:', error)
        jobs.value = []
    } finally {
        loading.value = false
    }
}

const formatTimeAgo = (timestamp) => {
    const now = new Date()
    const time = new Date(timestamp)
    const diffInSeconds = Math.floor((now - time) / 1000)
    
    if (diffInSeconds < 60) return 'just now'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
    return `${Math.floor(diffInSeconds / 86400)}d ago`
}

const viewJob = (job) => {
    window.location.href = `/jobs/${job.id}`
}

const saveJob = async (job) => {
    try {
        const response = await fetch(`/api/jobs/${job.id}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        if (response.ok) {
            job.is_saved = !job.is_saved
        }
    } catch (error) {
        console.error('Failed to save job:', error)
    }
}
</script>