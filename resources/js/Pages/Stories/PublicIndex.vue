<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Success Stories</h1>
          <p class="mt-4 text-lg text-gray-600">
            Inspiring journeys from our accomplished alumni
          </p>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Featured Stories -->
      <div v-if="featuredStories.length > 0" class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Stories</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="story in featuredStories"
            :key="story.id"
            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
          >
            <div v-if="story.featured_image" class="h-48 bg-gray-200">
              <img
                :src="story.featured_image"
                :alt="story.title"
                class="w-full h-full object-cover"
              >
            </div>
            <div class="p-6">
              <div class="flex items-center mb-3">
                <img
                  :src="story.user.avatar || '/default-avatar.png'"
                  :alt="story.user.name"
                  class="w-10 h-10 rounded-full object-cover mr-3"
                >
                <div>
                  <p class="font-medium text-gray-900">{{ story.user.name }}</p>
                  <p class="text-sm text-gray-600">{{ story.user.graduate?.course?.name }}</p>
                </div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ story.title }}</h3>
              <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ story.excerpt }}</p>
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ formatCategory(story.category) }}
                </span>
                <Link
                  :href="route('login')"
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Login to Read
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input
              id="search"
              v-model="searchForm.search"
              type="text"
              placeholder="Search stories..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="debouncedSearch"
            >
          </div>

          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select
              id="category"
              v-model="searchForm.category"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="applyFilters"
            >
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category" :value="category">
                {{ formatCategory(category) }}
              </option>
            </select>
          </div>

          <div>
            <label for="course" class="block text-sm font-medium text-gray-700 mb-2">Course</label>
            <select
              id="course"
              v-model="searchForm.course_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="applyFilters"
            >
              <option value="">All Courses</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">
                {{ course.name }}
              </option>
            </select>
          </div>

          <div>
            <label for="institution" class="block text-sm font-medium text-gray-700 mb-2">Institution</label>
            <select
              id="institution"
              v-model="searchForm.institution_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="applyFilters"
            >
              <option value="">All Institutions</option>
              <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                {{ institution.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <p class="text-sm text-gray-600">
            Showing {{ stories.data.length }} of {{ stories.total }} stories
          </p>
          <button
            @click="clearFilters"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Stories Grid -->
      <div v-if="stories.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="story in stories.data"
          :key="story.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          <div v-if="story.featured_image" class="h-48 bg-gray-200">
            <img
              :src="story.featured_image"
              :alt="story.title"
              class="w-full h-full object-cover"
            >
          </div>
          <div class="p-6">
            <div class="flex items-center mb-3">
              <img
                :src="story.user.avatar || '/default-avatar.png'"
                :alt="story.user.name"
                class="w-10 h-10 rounded-full object-cover mr-3"
              >
              <div>
                <p class="font-medium text-gray-900">{{ story.user.name }}</p>
                <p class="text-sm text-gray-600">{{ story.user.graduate?.course?.name }}</p>
              </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ story.title }}</h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ story.excerpt }}</p>
            
            <div class="flex items-center justify-between mb-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ formatCategory(story.category) }}
              </span>
              <div class="flex items-center text-sm text-gray-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                {{ story.view_count || 0 }}
              </div>
            </div>

            <Link
              :href="route('login')"
              class="w-full bg-blue-600 text-white text-center py-2 px-4 rounded-md hover:bg-blue-700 transition-colors block"
            >
              Login to Read Full Story
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No stories found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
      </div>

      <!-- Pagination -->
      <div v-if="stories.data.length > 0" class="mt-8">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <Link
              v-if="stories.prev_page_url"
              :href="stories.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Previous
            </Link>
            <Link
              v-if="stories.next_page_url"
              :href="stories.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Next
            </Link>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing {{ stories.from }} to {{ stories.to }} of {{ stories.total }} results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <Link
                  v-if="stories.prev_page_url"
                  :href="stories.prev_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                >
                  Previous
                </Link>
                <Link
                  v-if="stories.next_page_url"
                  :href="stories.next_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
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
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Share Your Success Story</h2>
        <p class="text-xl text-blue-100 mb-8">
          Inspire others by sharing your journey and achievements.
        </p>
        <div class="space-x-4">
          <Link
            :href="route('register')"
            class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
          >
            Join Now
          </Link>
          <Link
            :href="route('login')"
            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
          >
            Sign In
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

interface Props {
  stories: {
    data: any[]
    total: number
    from: number
    to: number
    prev_page_url?: string
    next_page_url?: string
  }
  featuredStories: any[]
  courses: any[]
  institutions: any[]
  categories: string[]
  filters: Record<string, any>
  auth_required: boolean
}

const props = defineProps<Props>()

const searchForm = reactive({
  search: props.filters.search || '',
  category: props.filters.category || '',
  course_id: props.filters.course_id || '',
  institution_id: props.filters.institution_id || ''
})

const debouncedSearch = debounce(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  router.get(route('stories.public.index'), searchForm, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  Object.keys(searchForm).forEach(key => {
    searchForm[key] = ''
  })
  applyFilters()
}

const formatCategory = (category: string) => {
  return category.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>