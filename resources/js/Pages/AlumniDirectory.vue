<template>
  <div class="alumni-directory">
    <div class="directory-header">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">Alumni Directory</h1>
      
      <!-- Search Bar -->
      <div class="search-section mb-6">
        <div class="relative">
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            placeholder="Search alumni by name, company, location, or skills..."
            class="w-full px-4 py-3 pl-12 pr-4 text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="directory-content flex gap-6">
      <!-- Filters Sidebar -->
      <div class="filters-sidebar w-80 flex-shrink-0">
        <DirectoryFilters
          :filters="availableFilters"
          :active-filters="activeFilters"
          @update-filters="updateFilters"
          @clear-filters="clearFilters"
        />
      </div>

      <!-- Alumni Grid -->
      <div class="alumni-grid flex-1">
        <!-- Results Header -->
        <div class="results-header flex justify-between items-center mb-6">
          <div class="results-info">
            <p class="text-gray-600">
              Showing {{ meta.from }}-{{ meta.to }} of {{ meta.total }} alumni
            </p>
          </div>
          
          <div class="sort-controls">
            <select
              v-model="sortBy"
              @change="loadAlumni"
              class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            >
              <option value="name">Sort by Name</option>
              <option value="graduation_year">Sort by Graduation Year</option>
              <option value="location">Sort by Location</option>
              <option value="created_at">Sort by Join Date</option>
            </select>
            
            <button
              @click="toggleSortOrder"
              class="ml-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              {{ sortOrder === 'asc' ? '↑' : '↓' }}
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="i in 9" :key="i" class="animate-pulse">
              <div class="bg-gray-200 rounded-lg h-64"></div>
            </div>
          </div>
        </div>

        <!-- Alumni Cards Grid -->
        <div v-else-if="alumni.length > 0" class="alumni-cards-grid">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AlumniCard
              v-for="alumnus in alumni"
              :key="alumnus.id"
              :alumni="alumnus"
              @view-profile="viewProfile"
              @connect="openConnectionModal"
            />
          </div>

          <!-- Pagination -->
          <div v-if="meta.last_page > 1" class="pagination mt-8">
            <nav class="flex justify-center">
              <div class="flex space-x-2">
                <button
                  v-if="meta.current_page > 1"
                  @click="goToPage(meta.current_page - 1)"
                  class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Previous
                </button>
                
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'px-3 py-2 text-sm font-medium border rounded-md',
                    page === meta.current_page
                      ? 'text-blue-600 bg-blue-50 border-blue-500'
                      : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
                
                <button
                  v-if="meta.current_page < meta.last_page"
                  @click="goToPage(meta.current_page + 1)"
                  class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Next
                </button>
              </div>
            </nav>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No alumni found</h3>
          <p class="mt-1 text-sm text-gray-500">
            Try adjusting your search criteria or filters.
          </p>
        </div>
      </div>
    </div>

    <!-- Connection Request Modal -->
    <ConnectionRequestModal
      v-if="showConnectionModal"
      :alumni="selectedAlumni"
      @close="closeConnectionModal"
      @send="sendConnectionRequest"
    />
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash'
import { router } from '@inertiajs/vue3'
import DirectoryFilters from '../Components/DirectoryFilters.vue'
import AlumniCard from '../Components/AlumniCard.vue'
import ConnectionRequestModal from '../Components/ConnectionRequestModal.vue'

export default {
  name: 'AlumniDirectory',
  components: {
    DirectoryFilters,
    AlumniCard,
    ConnectionRequestModal
  },
  setup() {
    // Reactive data
    const alumni = ref([])
    const availableFilters = ref({})
    const activeFilters = reactive({})
    const searchQuery = ref('')
    const sortBy = ref('name')
    const sortOrder = ref('asc')
    const loading = ref(false)
    const showConnectionModal = ref(false)
    const selectedAlumni = ref(null)
    
    const meta = ref({
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
      from: 0,
      to: 0
    })

    // Computed properties
    const visiblePages = computed(() => {
      const current = meta.value.current_page
      const last = meta.value.last_page
      const pages = []
      
      const start = Math.max(1, current - 2)
      const end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    // Methods
    const loadAlumni = async (page = 1) => {
      loading.value = true
      
      try {
        const params = {
          page,
          per_page: meta.value.per_page,
          sort_by: sortBy.value,
          sort_order: sortOrder.value,
          ...activeFilters
        }
        
        if (searchQuery.value) {
          params.search = searchQuery.value
        }
        
        const response = await fetch('/api/alumni?' + new URLSearchParams(params))
        const data = await response.json()
        
        alumni.value = data.data
        meta.value = data.meta
      } catch (error) {
        console.error('Error loading alumni:', error)
      } finally {
        loading.value = false
      }
    }

    const loadFilters = async () => {
      try {
        const response = await fetch('/api/alumni/filters')
        const data = await response.json()
        availableFilters.value = data.data
      } catch (error) {
        console.error('Error loading filters:', error)
      }
    }

    const debouncedSearch = debounce(() => {
      loadAlumni(1)
    }, 300)

    const updateFilters = (newFilters) => {
      Object.assign(activeFilters, newFilters)
      loadAlumni(1)
    }

    const clearFilters = () => {
      Object.keys(activeFilters).forEach(key => {
        delete activeFilters[key]
      })
      searchQuery.value = ''
      loadAlumni(1)
    }

    const toggleSortOrder = () => {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
      loadAlumni()
    }

    const goToPage = (page) => {
      loadAlumni(page)
    }

    const viewProfile = (alumnus) => {
      router.visit(`/alumni/${alumnus.id}`)
    }

    const openConnectionModal = (alumnus) => {
      selectedAlumni.value = alumnus
      showConnectionModal.value = true
    }

    const closeConnectionModal = () => {
      showConnectionModal.value = false
      selectedAlumni.value = null
    }

    const sendConnectionRequest = async (message) => {
      try {
        const response = await fetch(`/api/alumni/${selectedAlumni.value.id}/connect`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ message })
        })
        
        if (response.ok) {
          // Update the alumni card to show connection sent
          const alumnus = alumni.value.find(a => a.id === selectedAlumni.value.id)
          if (alumnus) {
            alumnus.connection_status = 'pending'
          }
          
          closeConnectionModal()
          
          // Show success message
          // This would typically be handled by a toast notification system
          alert('Connection request sent successfully!')
        } else {
          const error = await response.json()
          alert(error.message || 'Failed to send connection request')
        }
      } catch (error) {
        console.error('Error sending connection request:', error)
        alert('Failed to send connection request')
      }
    }

    // Lifecycle
    onMounted(() => {
      loadFilters()
      loadAlumni()
    })

    return {
      alumni,
      availableFilters,
      activeFilters,
      searchQuery,
      sortBy,
      sortOrder,
      loading,
      showConnectionModal,
      selectedAlumni,
      meta,
      visiblePages,
      loadAlumni,
      debouncedSearch,
      updateFilters,
      clearFilters,
      toggleSortOrder,
      goToPage,
      viewProfile,
      openConnectionModal,
      closeConnectionModal,
      sendConnectionRequest
    }
  }
}
</script>

<style scoped>
.alumni-directory {
  max-width: 80rem;
  margin: 0 auto;
  padding: 2rem 1rem;
}

@media (min-width: 640px) {
  .alumni-directory {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .alumni-directory {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

.loading-state .animate-pulse > div {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>