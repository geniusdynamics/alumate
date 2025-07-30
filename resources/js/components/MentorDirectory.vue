<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Find a Mentor</h1>
      <p class="mt-2 text-gray-600">Connect with experienced alumni who can guide your career journey</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Expertise Area Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Expertise Area</label>
          <select
            v-model="filters.expertise_area"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Areas</option>
            <option v-for="area in expertiseAreas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Availability Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
          <select
            v-model="filters.availability"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Any Availability</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        <!-- Industry Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
          <select
            v-model="filters.industry"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Industries</option>
            <option v-for="industry in industries" :key="industry" :value="industry">
              {{ industry }}
            </option>
          </select>
        </div>

        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input
            v-model="searchQuery"
            @input="debounceSearch"
            type="text"
            placeholder="Search mentors..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Clear Filters -->
      <div class="mt-4 flex justify-between items-center">
        <button
          @click="clearFilters"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          Clear all filters
        </button>
        <span class="text-sm text-gray-500">
          {{ mentors.length }} mentor{{ mentors.length !== 1 ? 's' : '' }} found
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Mentors Grid -->
    <div v-else-if="mentors.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <MentorCard
        v-for="mentor in mentors"
        :key="mentor.id"
        :mentor="mentor"
        @request-mentorship="handleMentorshipRequest"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No mentors found</h3>
      <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or search terms.</p>
    </div>

    <!-- Mentorship Request Modal -->
    <MentorshipRequestModal
      v-if="showRequestModal"
      :mentor="selectedMentor"
      @close="showRequestModal = false"
      @request-sent="handleRequestSent"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import MentorCard from './MentorCard.vue'
import MentorshipRequestModal from './MentorshipRequestModal.vue'

// Reactive data
const mentors = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showRequestModal = ref(false)
const selectedMentor = ref(null)

const filters = ref({
  expertise_area: '',
  availability: '',
  industry: ''
})

const expertiseAreas = ref([
  'Software Engineering',
  'Product Management',
  'Data Science',
  'Marketing',
  'Sales',
  'Finance',
  'Consulting',
  'Entrepreneurship',
  'Design',
  'Operations'
])

const industries = ref([
  'Technology',
  'Healthcare',
  'Finance',
  'Education',
  'Consulting',
  'Manufacturing',
  'Retail',
  'Media',
  'Non-profit',
  'Government'
])

// Computed
const filteredMentors = computed(() => {
  let result = mentors.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(mentor => 
      mentor.user.name.toLowerCase().includes(query) ||
      mentor.bio.toLowerCase().includes(query) ||
      mentor.expertise_areas.some(area => area.toLowerCase().includes(query))
    )
  }

  return result
})

// Methods
const loadMentors = async () => {
  loading.value = true
  try {
    const params = {
      ...filters.value,
      limit: 50
    }
    
    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (!params[key]) delete params[key]
    })

    const response = await axios.get('/api/mentorships/find-mentors', { params })
    mentors.value = response.data.mentors
  } catch (error) {
    console.error('Failed to load mentors:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  loadMentors()
}

const clearFilters = () => {
  filters.value = {
    expertise_area: '',
    availability: '',
    industry: ''
  }
  searchQuery.value = ''
  loadMentors()
}

let searchTimeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // Search is handled by computed property
  }, 300)
}

const handleMentorshipRequest = (mentor) => {
  selectedMentor.value = mentor
  showRequestModal.value = true
}

const handleRequestSent = () => {
  showRequestModal.value = false
  selectedMentor.value = null
  // Show success message or refresh data
}

// Lifecycle
onMounted(() => {
  loadMentors()
})
</script>