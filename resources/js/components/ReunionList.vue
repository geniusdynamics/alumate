<template>
  <div class="reunion-list">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">Class Reunions</h2>
      
      <!-- Filters -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Graduation Year
            </label>
            <input
              v-model="filters.graduation_year"
              type="number"
              :min="1900"
              :max="new Date().getFullYear() + 10"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., 2020"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Milestone
            </label>
            <select
              v-model="filters.milestone"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Milestones</option>
              <option value="5">5 Year</option>
              <option value="10">10 Year</option>
              <option value="15">15 Year</option>
              <option value="20">20 Year</option>
              <option value="25">25 Year</option>
              <option value="30">30 Year</option>
              <option value="50">50 Year</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Time Period
            </label>
            <select
              v-model="filters.period"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Events</option>
              <option value="upcoming">Upcoming</option>
              <option value="past">Past</option>
            </select>
          </div>
          
          <div class="flex items-end">
            <button
              @click="loadReunions"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Milestones -->
    <div v-if="upcomingMilestones.length > 0" class="mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Upcoming Reunion Milestones</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="milestone in upcomingMilestones"
          :key="milestone.milestone"
          class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4"
        >
          <div class="text-2xl font-bold text-blue-600">{{ milestone.milestone }} Year</div>
          <div class="text-sm text-gray-600">Class of {{ milestone.graduation_year }}</div>
          <div class="text-sm text-gray-500 mt-1">
            {{ milestone.years_away }} year{{ milestone.years_away !== 1 ? 's' : '' }} away ({{ milestone.year }})
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading reunions...</p>
    </div>

    <!-- Reunions Grid -->
    <div v-else-if="reunions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <ReunionCard
        v-for="reunion in reunions"
        :key="reunion.id"
        :reunion="reunion"
        @view="viewReunion"
        @register="registerForReunion"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-gray-400 mb-4">
        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2h4a2 2 0 012 2v4" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No reunions found</h3>
      <p class="text-gray-600 mb-4">
        {{ hasFilters ? 'Try adjusting your filters to see more results.' : 'No reunions have been scheduled yet.' }}
      </p>
      <button
        v-if="hasFilters"
        @click="clearFilters"
        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
      >
        Clear Filters
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center">
      <nav class="flex items-center space-x-2">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="loadPage(page)"
          :class="[
            'px-3 py-2 rounded-md text-sm font-medium',
            page === pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
          ]"
        >
          {{ page }}
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ReunionCard from './ReunionCard.vue'

interface Reunion {
  id: number
  title: string
  description: string
  graduation_year: number
  class_identifier: string
  reunion_year_milestone: number
  reunion_theme: string
  start_date: string
  end_date: string
  venue_name: string
  venue_address: string
  organizer: {
    id: number
    name: string
    avatar_url: string
  }
  institution: {
    id: number
    name: string
  }
  current_attendees: number
  max_capacity: number
  ticket_price: number
  status: string
  enable_photo_sharing: boolean
  enable_memory_wall: boolean
}

interface Milestone {
  milestone: number
  year: number
  graduation_year: number
  years_away: number
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const reunions = ref<Reunion[]>([])
const upcomingMilestones = ref<Milestone[]>([])
const loading = ref(false)
const pagination = ref<Pagination | null>(null)

const filters = ref({
  graduation_year: '',
  milestone: '',
  period: ''
})

const hasFilters = computed(() => {
  return filters.value.graduation_year || filters.value.milestone || filters.value.period
})

const visiblePages = computed(() => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  // Show up to 5 pages around current page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const loadReunions = async (page = 1) => {
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    if (filters.value.graduation_year) params.append('graduation_year', filters.value.graduation_year)
    if (filters.value.milestone) params.append('milestone', filters.value.milestone)
    if (filters.value.period) params.append('period', filters.value.period)
    params.append('page', page.toString())
    
    const response = await fetch(`/api/reunions?${params}`)
    const data = await response.json()
    
    reunions.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total
    }
  } catch (error) {
    console.error('Error loading reunions:', error)
  } finally {
    loading.value = false
  }
}

const loadMilestones = async () => {
  try {
    const response = await fetch('/api/reunions/milestones')
    const data = await response.json()
    upcomingMilestones.value = data
  } catch (error) {
    console.error('Error loading milestones:', error)
  }
}

const loadPage = (page: number) => {
  loadReunions(page)
}

const clearFilters = () => {
  filters.value = {
    graduation_year: '',
    milestone: '',
    period: ''
  }
  loadReunions()
}

const viewReunion = (reunion: Reunion) => {
  router.visit(`/reunions/${reunion.id}`)
}

const registerForReunion = async (reunion: Reunion) => {
  try {
    const response = await fetch(`/api/events/${reunion.id}/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      // Reload the reunion data to update attendee count
      loadReunions(pagination.value?.current_page || 1)
    }
  } catch (error) {
    console.error('Error registering for reunion:', error)
  }
}

// Watch for filter changes
watch(filters, () => {
  if (hasFilters.value) {
    loadReunions()
  }
}, { deep: true })

onMounted(() => {
  loadReunions()
  loadMilestones()
})
</script>

<style scoped>
.reunion-list {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8;
}
</style>