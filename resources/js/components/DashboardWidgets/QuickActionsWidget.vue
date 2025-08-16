<template>
    <div class="card-mobile bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Quick Actions</h3>
            <BoltIcon class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
        </div>
        
        <div class="grid grid-cols-2 gap-3">
            <!-- Create Post -->
            <button
                @click="openPostCreator"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group"
            >
                <PlusCircleIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-blue-500 dark:group-hover:text-blue-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                    Create Post
                </span>
            </button>
            
            <!-- Find Alumni -->
            <Link
                :href="route('alumni.directory')"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-green-400 dark:hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors group"
            >
                <MagnifyingGlassIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-green-500 dark:group-hover:text-green-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400">
                    Find Alumni
                </span>
            </Link>
            
            <!-- Browse Jobs -->
            <Link
                :href="route('jobs.dashboard')"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors group"
            >
                <BriefcaseIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-purple-500 dark:group-hover:text-purple-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                    Browse Jobs
                </span>
            </Link>
            
            <!-- View Events -->
            <Link
                :href="route('events.index')"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors group"
            >
                <CalendarDaysIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                    View Events
                </span>
            </Link>
            
            <!-- Update Profile -->
            <Link
                :href="route('profile.show')"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-orange-400 dark:hover:border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group"
            >
                <UserCircleIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-orange-500 dark:group-hover:text-orange-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-600 dark:group-hover:text-orange-400">
                    Update Profile
                </span>
            </Link>
            
            <!-- Find Mentors -->
            <Link
                :href="route('mentorship.index')"
                class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-teal-400 dark:hover:border-teal-500 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors group"
            >
                <AcademicCapIcon class="h-8 w-8 text-gray-400 dark:text-gray-500 group-hover:text-teal-500 dark:group-hover:text-teal-400 mb-2" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-teal-600 dark:group-hover:text-teal-400">
                    Find Mentors
                </span>
            </Link>
        </div>
        
        <!-- Feature Discovery for New Users -->
        <div v-if="showFeatureDiscovery" class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <LightBulbIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">
                        Discover More Features
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                        Explore success stories, join alumni groups, and track your career progress.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('success-stories.index')"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
                        >
                            Success Stories
                        </Link>
                        <Link
                            :href="route('career.timeline')"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
                        >
                            Career Timeline
                        </Link>
                        <Link
                            :href="route('alumni.map')"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
                        >
                            Alumni Map
                        </Link>
                    </div>
                </div>
                <button
                    @click="dismissFeatureDiscovery"
                    class="flex-shrink-0 text-blue-400 dark:text-blue-500 hover:text-blue-600 dark:hover:text-blue-300"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
    
    <!-- Post Creator Modal -->
    <PostCreatorModal 
        v-if="showPostCreator"
        @close="showPostCreator = false"
        @posted="onPostCreated"
    />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    BoltIcon,
    PlusCircleIcon,
    MagnifyingGlassIcon,
    BriefcaseIcon,
    CalendarDaysIcon,
    UserCircleIcon,
    AcademicCapIcon,
    LightBulbIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import PostCreatorModal from '@/Components/PostCreatorModal.vue'

const page = usePage()
const showPostCreator = ref(false)
const showFeatureDiscovery = ref(false)

// Check if user is new (registered within last 30 days)
const isNewUser = computed(() => {
    const user = page.props.auth?.user
    if (!user?.created_at) return false
    
    const createdAt = new Date(user.created_at)
    const thirtyDaysAgo = new Date()
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
    
    return createdAt > thirtyDaysAgo
})

onMounted(() => {
    // Show feature discovery for new users who haven't dismissed it
    const dismissed = localStorage.getItem('feature-discovery-dismissed')
    showFeatureDiscovery.value = isNewUser.value && !dismissed
})

const openPostCreator = () => {
    showPostCreator.value = true
}

const onPostCreated = () => {
    showPostCreator.value = false
    // Optionally refresh dashboard data or show success message
}

const dismissFeatureDiscovery = () => {
    showFeatureDiscovery.value = false
    localStorage.setItem('feature-discovery-dismissed', 'true')
}
</script>