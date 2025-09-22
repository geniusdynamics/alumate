<template>
    <div class="card-mobile border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Quick Actions</h3>
            <BoltIcon class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <!-- Create Post -->
            <button
                @click="openPostCreator"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-blue-400 hover:bg-blue-50 dark:border-gray-600 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
            >
                <PlusCircleIcon class="mb-2 h-8 w-8 text-gray-400 group-hover:text-blue-500 dark:text-gray-500 dark:group-hover:text-blue-400" />
                <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 dark:text-gray-300 dark:group-hover:text-blue-400">
                    Create Post
                </span>
            </button>

            <!-- Find Alumni -->
            <Link
                :href="route('alumni.directory')"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-green-400 hover:bg-green-50 dark:border-gray-600 dark:hover:border-green-500 dark:hover:bg-green-900/20"
            >
                <MagnifyingGlassIcon
                    class="mb-2 h-8 w-8 text-gray-400 group-hover:text-green-500 dark:text-gray-500 dark:group-hover:text-green-400"
                />
                <span class="text-sm font-medium text-gray-700 group-hover:text-green-600 dark:text-gray-300 dark:group-hover:text-green-400">
                    Find Alumni
                </span>
            </Link>

            <!-- Browse Jobs -->
            <Link
                :href="route('jobs.dashboard')"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-purple-400 hover:bg-purple-50 dark:border-gray-600 dark:hover:border-purple-500 dark:hover:bg-purple-900/20"
            >
                <BriefcaseIcon class="mb-2 h-8 w-8 text-gray-400 group-hover:text-purple-500 dark:text-gray-500 dark:group-hover:text-purple-400" />
                <span class="text-sm font-medium text-gray-700 group-hover:text-purple-600 dark:text-gray-300 dark:group-hover:text-purple-400">
                    Browse Jobs
                </span>
            </Link>

            <!-- View Events -->
            <Link
                :href="route('events.index')"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-indigo-400 hover:bg-indigo-50 dark:border-gray-600 dark:hover:border-indigo-500 dark:hover:bg-indigo-900/20"
            >
                <CalendarDaysIcon
                    class="mb-2 h-8 w-8 text-gray-400 group-hover:text-indigo-500 dark:text-gray-500 dark:group-hover:text-indigo-400"
                />
                <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600 dark:text-gray-300 dark:group-hover:text-indigo-400">
                    View Events
                </span>
            </Link>

            <!-- Update Profile -->
            <Link
                :href="route('profile.show')"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-orange-400 hover:bg-orange-50 dark:border-gray-600 dark:hover:border-orange-500 dark:hover:bg-orange-900/20"
            >
                <UserCircleIcon class="mb-2 h-8 w-8 text-gray-400 group-hover:text-orange-500 dark:text-gray-500 dark:group-hover:text-orange-400" />
                <span class="text-sm font-medium text-gray-700 group-hover:text-orange-600 dark:text-gray-300 dark:group-hover:text-orange-400">
                    Update Profile
                </span>
            </Link>

            <!-- Find Mentors -->
            <Link
                :href="route('mentorship.index')"
                class="group flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-4 transition-colors hover:border-teal-400 hover:bg-teal-50 dark:border-gray-600 dark:hover:border-teal-500 dark:hover:bg-teal-900/20"
            >
                <AcademicCapIcon class="mb-2 h-8 w-8 text-gray-400 group-hover:text-teal-500 dark:text-gray-500 dark:group-hover:text-teal-400" />
                <span class="text-sm font-medium text-gray-700 group-hover:text-teal-600 dark:text-gray-300 dark:group-hover:text-teal-400">
                    Find Mentors
                </span>
            </Link>
        </div>

        <!-- Feature Discovery for New Users -->
        <div
            v-if="showFeatureDiscovery"
            class="mt-6 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20"
        >
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <LightBulbIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <h4 class="mb-1 text-sm font-medium text-blue-900 dark:text-blue-100">Discover More Features</h4>
                    <p class="mb-3 text-sm text-blue-700 dark:text-blue-300">
                        Explore success stories, join alumni groups, and track your career progress.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('success-stories.index')"
                            class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 transition-colors hover:bg-blue-200 dark:bg-blue-800 dark:text-blue-200 dark:hover:bg-blue-700"
                        >
                            Success Stories
                        </Link>
                        <Link
                            :href="route('career.timeline')"
                            class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 transition-colors hover:bg-blue-200 dark:bg-blue-800 dark:text-blue-200 dark:hover:bg-blue-700"
                        >
                            Career Timeline
                        </Link>
                        <Link
                            :href="route('alumni.map')"
                            class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 transition-colors hover:bg-blue-200 dark:bg-blue-800 dark:text-blue-200 dark:hover:bg-blue-700"
                        >
                            Alumni Map
                        </Link>
                    </div>
                </div>
                <button
                    @click="dismissFeatureDiscovery"
                    class="flex-shrink-0 text-blue-400 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-300"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>

    <!-- Post Creator Modal -->
    <PostCreatorModal v-if="showPostCreator" @close="showPostCreator = false" @posted="onPostCreated" />
</template>

<script setup>
import PostCreatorModal from '@/Components/PostCreatorModal.vue';
import {
    AcademicCapIcon,
    BoltIcon,
    BriefcaseIcon,
    CalendarDaysIcon,
    LightBulbIcon,
    MagnifyingGlassIcon,
    PlusCircleIcon,
    UserCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const showPostCreator = ref(false);
const showFeatureDiscovery = ref(false);

// Check if user is new (registered within last 30 days)
const isNewUser = computed(() => {
    const user = page.props.auth?.user;
    if (!user?.created_at) return false;

    const createdAt = new Date(user.created_at);
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

    return createdAt > thirtyDaysAgo;
});

onMounted(() => {
    // Show feature discovery for new users who haven't dismissed it
    const dismissed = localStorage.getItem('feature-discovery-dismissed');
    showFeatureDiscovery.value = isNewUser.value && !dismissed;
});

const openPostCreator = () => {
    showPostCreator.value = true;
};

const onPostCreated = () => {
    showPostCreator.value = false;
    // Optionally refresh dashboard data or show success message
};

const dismissFeatureDiscovery = () => {
    showFeatureDiscovery.value = false;
    localStorage.setItem('feature-discovery-dismissed', 'true');
};
</script>











