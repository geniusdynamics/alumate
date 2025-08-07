<template>
    <AppLayout title="Success Stories">
        <Head title="Success Stories" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Success Stories</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Inspiring stories from our alumni community</p>
            </div>

            <!-- Featured Stories -->
            <div v-if="featuredStories.length > 0" class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Featured Stories</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <SuccessStoryCard
                        v-for="story in featuredStories"
                        :key="story.id"
                        :story="story"
                        :featured="true"
                        class="featured-story"
                    />
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Stories
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by title, content, or achievements..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Category Filter -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Category
                                </label>
                                <select
                                    id="category"
                                    v-model="searchForm.category"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Categories</option>
                                    <option v-for="category in categories" :key="category" :value="category">
                                        {{ formatCategory(category) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Course Filter -->
                            <div>
                                <label for="course" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Course
                                </label>
                                <select
                                    id="course"
                                    v-model="searchForm.course_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Institution Filter -->
                            <div>
                                <label for="institution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Institution
                                </label>
                                <select
                                    id="institution"
                                    v-model="searchForm.institution_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Sort By -->
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Sort By
                                </label>
                                <select
                                    id="sort"
                                    v-model="searchForm.sort"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="latest">Latest</option>
                                    <option value="popular">Most Popular</option>
                                    <option value="views">Most Viewed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Apply Filters
                                </button>
                                <button
                                    type="button"
                                    @click="clearFilters"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Clear All
                                </button>
                            </div>
                            
                            <Link
                                :href="route('stories.create')"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                            >
                                Share Your Story
                            </Link>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stories Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ stories.total }} Stories Found
                    </h2>
                </div>

                <div class="p-6">
                    <div v-if="stories.data.length === 0" class="text-center py-12">
                        <StarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No stories found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Try adjusting your search criteria or be the first to share a story!</p>
                        <Link
                            :href="route('stories.create')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Share Your Story
                        </Link>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <SuccessStoryCard
                            v-for="story in stories.data"
                            :key="story.id"
                            :story="story"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="stories.last_page > 1" class="mt-8">
                        <Pagination :links="stories.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SuccessStoryCard from '@/components/SuccessStories/SuccessStoryCard.vue'
import Pagination from '@/components/Pagination.vue'
import { StarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    stories: Object,
    featuredStories: Array,
    courses: Array,
    institutions: Array,
    categories: Array,
    filters: Object,
})

const searchForm = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
    course_id: props.filters.course_id || '',
    institution_id: props.filters.institution_id || '',
    sort: props.filters.sort || 'latest',
})

const formatCategory = (category) => {
    return category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const applyFilters = () => {
    router.get(route('stories.index'), searchForm, {
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
    searchForm.sort = 'latest'
    applyFilters()
}
</script>

<style scoped>
.featured-story {
    @apply border-2 border-yellow-200 dark:border-yellow-600;
}
</style>
