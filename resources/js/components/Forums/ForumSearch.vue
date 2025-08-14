<template>
  <div class="forum-search">
    <div class="flex items-center space-x-4">
      <!-- Search Input -->
      <div class="flex-1 relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search topics, posts, and discussions..."
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
          @keyup.enter="performSearch"
        />
      </div>

      <!-- Filters -->
      <div class="flex items-center space-x-2">
        <!-- Forum Filter -->
        <select
          v-model="selectedForum"
          class="block w-40 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Forums</option>
          <option v-for="forum in availableForums" :key="forum.id" :value="forum.id">
            {{ forum.name }}
          </option>
        </select>

        <!-- Sort Filter -->
        <select
          v-model="sortBy"
          class="block w-32 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="relevance">Relevance</option>
          <option value="newest">Newest</option>
          <option value="oldest">Oldest</option>
          <option value="popular">Popular</option>
          <option value="activity">Activity</option>
        </select>

        <!-- Search Button -->
        <button
          @click="performSearch"
          :disabled="!searchQuery.trim()"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Search
        </button>
      </div>
    </div>

    <!-- Advanced Filters (Collapsible) -->
    <div v-if="showAdvanced" class="mt-4 p-4 bg-gray-50 rounded-lg">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Tag Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tag</label>
          <input
            v-model="selectedTag"
            type="text"
            placeholder="Enter tag name"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Author Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
          <input
            v-model="selectedAuthor"
            type="text"
            placeholder="Enter author name"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Date Range -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
          <select
            v-model="dateRange"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Any time</option>
            <option value="today">Today</option>
            <option value="week">This week</option>
            <option value="month">This month</option>
            <option value="year">This year</option>
          </select>
        </div>
      </div>

      <div class="mt-4 flex justify-between">
        <button
          @click="clearFilters"
          class="text-sm text-gray-600 hover:text-gray-800"
        >
          Clear all filters
        </button>
        <button
          @click="showAdvanced = false"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          Hide advanced filters
        </button>
      </div>
    </div>

    <!-- Toggle Advanced Filters -->
    <div v-if="!showAdvanced" class="mt-2">
      <button
        @click="showAdvanced = true"
        class="text-sm text-blue-600 hover:text-blue-800"
      >
        Show advanced filters
      </button>
    </div>

    <!-- Search Suggestions -->
    <div v-if="suggestions.length > 0" class="mt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Popular searches:</h4>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="suggestion in suggestions"
          :key="suggestion"
          @click="searchQuery = suggestion; performSearch()"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200"
        >
          {{ suggestion }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

// Emits
const emit = defineEmits(['search'])

// Reactive data
const searchQuery = ref('')
const selectedForum = ref('')
const selectedTag = ref('')
const selectedAuthor = ref('')
const sortBy = ref('relevance')
const dateRange = ref('')
const showAdvanced = ref(false)
const availableForums = ref([])
const suggestions = ref([
  'career advice',
  'networking',
  'job opportunities',
  'mentorship',
  'industry trends',
  'alumni events'
])

// Methods
const performSearch = () => {
  if (!searchQuery.value.trim()) return

  const searchData = {
    query: searchQuery.value.trim(),
    forum_id: selectedForum.value || undefined,
    tag: selectedTag.value || undefined,
    author: selectedAuthor.value || undefined,
    sort: sortBy.value,
    date_range: dateRange.value || undefined,
  }

  emit('search', searchData)
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedForum.value = ''
  selectedTag.value = ''
  selectedAuthor.value = ''
  sortBy.value = 'relevance'
  dateRange.value = ''
}

const loadAvailableForums = async () => {
  try {
    const response = await fetch('/api/forums')
    const data = await response.json()
    
    if (data.success) {
      availableForums.value = data.data
    }
  } catch (error) {
    console.error('Error loading forums:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadAvailableForums()
})
</script>