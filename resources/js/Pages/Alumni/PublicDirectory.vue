<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Alumni Directory</h1>
          <p class="mt-4 text-lg text-gray-600">
            Connect with our accomplished alumni community
          </p>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Search and Filters -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input
              id="search"
              v-model="searchForm.search"
              type="text"
              placeholder="Search by name..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="debouncedSearch"
            >
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

          <div>
            <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Graduation Year</label>
            <select
              id="year"
              v-model="searchForm.graduation_year"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="applyFilters"
            >
              <option value="">All Years</option>
              <option v-for="year in graduationYears" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <p class="text-sm text-gray-600">
            Showing {{ alumni.data.length }} of {{ alumni.total }} alumni
          </p>
          <button
            @click="clearFilters"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Alumni Grid -->
      <div v-if="alumni.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="alumnus in alumni.data"
          :key="alumnus.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-6"
        >
          <div class="flex items-center mb-4">
            <img
              :src="alumnus.user.avatar || '/default-avatar.png'"
              :alt="alumnus.user.name"
              class="w-16 h-16 rounded-full object-cover"
            >
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">{{ alumnus.user.name }}</h3>
              <p class="text-sm text-gray-600">{{ alumnus.course?.name }}</p>
              <p class="text-sm text-gray-500">Class of {{ alumnus.graduation_year }}</p>
            </div>
          </div>

          <div class="space-y-2 mb-4">
            <div v-if="alumnus.current_job_title" class="flex items-center text-sm text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              {{ alumnus.current_job_title }}
            </div>

            <div v-if="alumnus.current_company" class="flex items-center text-sm text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10"></path>
              </svg>
              {{ alumnus.current_company }}
            </div>

            <div v-if="alumnus.current_location" class="flex items-center text-sm text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              {{ alumnus.current_location }}
            </div>
          </div>

          <!-- Login prompt for connections -->
          <div class="pt-4 border-t border-gray-200">
            <Link
              :href="route('login')"
              class="w-full bg-blue-600 text-white text-center py-2 px-4 rounded-md hover:bg-blue-700 transition-colors block"
            >
              Login to Connect
            </Link>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No alumni found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
      </div>

      <!-- Pagination -->
      <div v-if="alumni.data.length > 0" class="mt-8">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <Link
              v-if="alumni.prev_page_url"
              :href="alumni.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Previous
            </Link>
            <Link
              v-if="alumni.next_page_url"
              :href="alumni.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Next
            </Link>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing {{ alumni.from }} to {{ alumni.to }} of {{ alumni.total }} results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <Link
                  v-if="alumni.prev_page_url"
                  :href="alumni.prev_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                >
                  Previous
                </Link>
                <Link
                  v-if="alumni.next_page_url"
                  :href="alumni.next_page_url"
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
        <h2 class="text-3xl font-bold text-white mb-4">Join Our Alumni Network</h2>
        <p class="text-xl text-blue-100 mb-8">
          Connect with fellow graduates, share your success story, and discover new opportunities.
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
import { reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

interface Props {
  alumni: {
    data: any[]
    total: number
    from: number
    to: number
    prev_page_url?: string
    next_page_url?: string
  }
  courses: any[]
  institutions: any[]
  graduationYears: number[]
  filters: Record<string, any>
  auth_required: boolean
}

const props = defineProps<Props>()

const searchForm = reactive({
  search: props.filters.search || '',
  course_id: props.filters.course_id || '',
  institution_id: props.filters.institution_id || '',
  graduation_year: props.filters.graduation_year || '',
  location: props.filters.location || '',
  industry: props.filters.industry || ''
})

const debouncedSearch = debounce(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  router.get(route('alumni.public.directory'), searchForm, {
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
</script>