<template>
    <AppLayout title="Alumni Stories">
        <Head title="Alumni Stories Discovery" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Discover Alumni Success Stories</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Get inspired by alumni journeys and connect with graduates who share your interests and career goals
                </p>
            </div>

            <!-- Quick Filters -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="filter in quickFilters"
                        :key="filter.key"
                        @click="applyQuickFilter(filter.key)"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                            activeQuickFilter === filter.key
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                        ]"
                    >
                        <component :is="filter.icon" class="w-4 h-4 inline mr-2" />
                        {{ filter.label }}
                    </button>
                </div>
            </div>

            <!-- Search and Advanced Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="searchStories" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Stories
                            </label>
                            <div class="relative">
                                <input
                                    id="search"
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Search by career path, company, skills, or keywords..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                                <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <!-- Filter Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Career Field -->
                            <div>
                                <label for="career_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Career Field
                                </label>
                                <select
                                    id="career_field"
                                    v-model="searchForm.career_field"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Fields</option>
                                    <option v-for="field in careerFields" :key="field" :value="field">
                                        {{ field }}
                                    </option>
                                </select>
                            </div>

                            <!-- Graduation Year Range -->
                            <div>
                                <label for="graduation_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Graduation Year
                                </label>
                                <select
                                    id="graduation_year"
                                    v-model="searchForm.graduation_year"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Years</option>
                                    <option value="recent">Last 5 Years</option>
                                    <option value="mid">5-10 Years Ago</option>
                                    <option value="experienced">10+ Years Ago</option>
                                </select>
                            </div>

                            <!-- Company Type -->
                            <div>
                                <label for="company_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Company Type
                                </label>
                                <select
                                    id="company_type"
                                    v-model="searchForm.company_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Types</option>
                                    <option value="startup">Startup</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="nonprofit">Non-Profit</option>
                                    <option value="government">Government</option>
                                    <option value="freelance">Freelance/Consulting</option>
                                </select>
                            </div>

                            <!-- Story Type -->
                            <div>
                                <label for="story_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Story Type
                                </label>
                                <select
                                    id="story_type"
                                    v-model="searchForm.story_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Stories</option>
                                    <option value="career_change">Career Change</option>
                                    <option value="entrepreneurship">Entrepreneurship</option>
                                    <option value="leadership">Leadership</option>
                                    <option value="innovation">Innovation</option>
                                    <option value="social_impact">Social Impact</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button
                                type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors"
                            >
                                Search Stories
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md font-medium transition-colors"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stories Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ stories.total }} Stories Found
                        </h2>
                        <div class="flex items-center space-x-2">
                            <label for="sort" class="text-sm text-gray-600 dark:text-gray-400">Sort by:</label>
                            <select
                                id="sort"
                                v-model="searchForm.sort"
                                @change="searchStories"
                                class="text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="relevance">Relevance</option>
                                <option value="recent">Most Recent</option>
                                <option value="popular">Most Popular</option>
                                <option value="graduation_year">Graduation Year</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="stories.data.length === 0" class="text-center py-12">
                        <BookOpenIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No stories found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Try adjusting your search criteria or explore different career fields</p>
                        <button
                            @click="clearFilters"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            View All Stories
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <StudentStoryCard
                            v-for="story in stories.data"
                            :key="story.id"
                            :story="story"
                            @connect-alumni="handleConnectAlumni"
                            @save-story="handleSaveStory"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="stories.last_page > 1" class="mt-8">
                        <Pagination :links="stories.links" />
                    </div>
                </div>
            </div>

            <!-- Suggested Connections -->
            <div v-if="suggestedConnections.length > 0" class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Alumni You Might Want to Connect With</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <AlumniConnectionCard
                            v-for="alumni in suggestedConnections"
                            :key="alumni.id"
                            :alumni="alumni"
                            @send-connection="handleSendConnection"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import StudentStoryCard from '@/Components/StudentStoryCard.vue'
import AlumniConnectionCard from '@/Components/AlumniConnectionCard.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    MagnifyingGlassIcon,
    BookOpenIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    StarIcon,
    TrophyIcon,
    HeartIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stories: Object,
    suggestedConnections: Array,
    careerFields: Array,
    filters: Object,
})

const activeQuickFilter = ref('')

const quickFilters = [
    { key: 'my_field', label: 'My Field', icon: BriefcaseIcon },
    { key: 'recent_grads', label: 'Recent Grads', icon: AcademicCapIcon },
    { key: 'top_rated', label: 'Top Rated', icon: StarIcon },
    { key: 'entrepreneurs', label: 'Entrepreneurs', icon: LightBulbIcon },
    { key: 'award_winners', label: 'Award Winners', icon: TrophyIcon },
    { key: 'social_impact', label: 'Social Impact', icon: HeartIcon },
]

const searchForm = reactive({
    search: props.filters.search || '',
    career_field: props.filters.career_field || '',
    graduation_year: props.filters.graduation_year || '',
    company_type: props.filters.company_type || '',
    story_type: props.filters.story_type || '',
    sort: props.filters.sort || 'relevance',
})

const applyQuickFilter = (filterKey) => {
    activeQuickFilter.value = filterKey
    searchForm.quick_filter = filterKey
    searchStories()
}

const searchStories = () => {
    router.get(route('students.stories.discovery'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    Object.keys(searchForm).forEach(key => {
        if (key !== 'sort') {
            searchForm[key] = ''
        }
    })
    searchForm.sort = 'relevance'
    activeQuickFilter.value = ''
    searchStories()
}

const handleConnectAlumni = (alumniId) => {
    router.post(route('api.connections.request'), {
        user_id: alumniId
    }, {
        preserveState: true
    })
}

const handleSaveStory = (storyId) => {
    router.post(route('api.stories.save', storyId), {}, {
        preserveState: true
    })
}

const handleSendConnection = (alumniId) => {
    router.post(route('api.connections.request'), {
        user_id: alumniId
    }, {
        preserveState: true
    })
}
</script>
