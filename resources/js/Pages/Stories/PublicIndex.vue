<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900">Success Stories</h1>
                    <p class="mt-4 text-lg text-gray-600">Inspiring journeys from our accomplished alumni</p>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Featured Stories -->
            <div v-if="featuredStories.length > 0" class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-gray-900">Featured Stories</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        v-for="story in featuredStories"
                        :key="story.id"
                        class="overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div v-if="story.featured_image" class="h-48 bg-gray-200">
                            <img :src="story.featured_image" :alt="story.title" class="h-full w-full object-cover" />
                        </div>
                        <div class="p-6">
                            <div class="mb-3 flex items-center">
                                <img
                                    :src="story.user.avatar || '/default-avatar.png'"
                                    :alt="story.user.name"
                                    class="mr-3 h-10 w-10 rounded-full object-cover"
                                />
                                <div>
                                    <p class="font-medium text-gray-900">{{ story.user.name }}</p>
                                    <p class="text-sm text-gray-600">{{ story.user.graduate?.course?.name }}</p>
                                </div>
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ story.title }}</h3>
                            <p class="mb-4 line-clamp-3 text-sm text-gray-600">{{ story.excerpt }}</p>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    {{ formatCategory(story.category) }}
                                </span>
                                <Link :href="route('login')" class="text-sm font-medium text-blue-600 hover:text-blue-800"> Login to Read </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="search" class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <input
                            id="search"
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Search stories..."
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <div>
                        <label for="category" class="mb-2 block text-sm font-medium text-gray-700">Category</label>
                        <select
                            id="category"
                            v-model="searchForm.category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ formatCategory(category) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="course" class="mb-2 block text-sm font-medium text-gray-700">Course</label>
                        <select
                            id="course"
                            v-model="searchForm.course_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="institution" class="mb-2 block text-sm font-medium text-gray-700">Institution</label>
                        <select
                            id="institution"
                            v-model="searchForm.institution_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Institutions</option>
                            <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                {{ institution.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <p class="text-sm text-gray-600">Showing {{ stories.data.length }} of {{ stories.total }} stories</p>
                    <button @click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800">Clear Filters</button>
                </div>
            </div>

            <!-- Stories Grid -->
            <div v-if="stories.data.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="story in stories.data"
                    :key="story.id"
                    class="overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md"
                >
                    <div v-if="story.featured_image" class="h-48 bg-gray-200">
                        <img :src="story.featured_image" :alt="story.title" class="h-full w-full object-cover" />
                    </div>
                    <div class="p-6">
                        <div class="mb-3 flex items-center">
                            <img
                                :src="story.user.avatar || '/default-avatar.png'"
                                :alt="story.user.name"
                                class="mr-3 h-10 w-10 rounded-full object-cover"
                            />
                            <div>
                                <p class="font-medium text-gray-900">{{ story.user.name }}</p>
                                <p class="text-sm text-gray-600">{{ story.user.graduate?.course?.name }}</p>
                            </div>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ story.title }}</h3>
                        <p class="mb-4 line-clamp-3 text-sm text-gray-600">{{ story.excerpt }}</p>

                        <div class="mb-4 flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                {{ formatCategory(story.category) }}
                            </span>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    ></path>
                                </svg>
                                {{ story.view_count || 0 }}
                            </div>
                        </div>

                        <Link
                            :href="route('login')"
                            class="block w-full rounded-md bg-blue-600 px-4 py-2 text-center text-white transition-colors hover:bg-blue-700"
                        >
                            Login to Read Full Story
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    ></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No stories found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
            </div>

            <!-- Pagination -->
            <div v-if="stories.data.length > 0" class="mt-8">
                <nav class="flex items-center justify-between">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <Link
                            v-if="stories.prev_page_url"
                            :href="stories.prev_page_url"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="stories.next_page_url"
                            :href="stories.next_page_url"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">Showing {{ stories.from }} to {{ stories.to }} of {{ stories.total }} results</p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                <Link
                                    v-if="stories.prev_page_url"
                                    :href="stories.prev_page_url"
                                    class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="stories.next_page_url"
                                    :href="stories.next_page_url"
                                    class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </nav>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-blue-600 py-12">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-4 text-3xl font-bold text-white">Share Your Success Story</h2>
                <p class="mb-8 text-xl text-blue-100">Inspire others by sharing your journey and achievements.</p>
                <div class="space-x-4">
                    <Link
                        :href="route('register')"
                        class="rounded-lg bg-white px-8 py-3 font-semibold text-blue-600 transition-colors hover:bg-gray-100"
                    >
                        Join Now
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-lg border-2 border-white px-8 py-3 font-semibold text-white transition-colors hover:bg-white hover:text-blue-600"
                    >
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive } from 'vue';

interface Props {
    stories: {
        data: any[];
        total: number;
        from: number;
        to: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    featuredStories: any[];
    courses: any[];
    institutions: any[];
    categories: string[];
    filters: Record<string, any>;
    auth_required: boolean;
}

const props = defineProps<Props>();

const searchForm = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
    course_id: props.filters.course_id || '',
    institution_id: props.filters.institution_id || '',
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 300);

const applyFilters = () => {
    router.get(route('stories.public.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    Object.keys(searchForm).forEach((key) => {
        searchForm[key] = '';
    });
    applyFilters();
};

const formatCategory = (category: string) => {
    return category
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
