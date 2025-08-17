<template>
  <AppLayout title="Alumni Directory">
    <Head title="Alumni Directory" />
    
    <!-- Mobile Hamburger Menu -->
    <MobileHamburgerMenu class="lg:hidden" />
    
    <!-- Pull to Refresh -->
    <PullToRefresh @refresh="refreshDirectory" class="alumni-directory theme-bg-secondary">
      <!-- Mobile Header -->
      <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 safe-area-top">
        <div class="p-4">
          <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Alumni Directory</h1>
          
          <!-- Mobile Search Interface -->
          <MobileSearchInterface
            placeholder="Search alumni by name, company, location..."
            search-type="alumni"
            :has-filters="true"
            :filters="activeFilters"
            @search="handleSearch"
            @filter-change="handleFilterChange"
          >
            <template #filters="{ filters, updateFilter }">
              <DirectoryFilters
                :filters="availableFilters"
                :active-filters="filters"
                @update-filters="updateFilter"
                mobile
              />
            </template>
          </MobileSearchInterface>
        </div>
      </div>
      
      <!-- Desktop Header -->
      <div class="hidden lg:block directory-header mobile-container">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Alumni Directory</h1>
        
        <!-- Desktop Search Bar -->
        <div class="search-section mb-6">
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Search alumni by name, company, location, or skills..."
              class="w-full px-4 py-3 pl-12 pr-4 text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
          </div>
        </div>
      </div>

      <div class="directory-content flex gap-6 mobile-container lg:px-6">
        <!-- Desktop Filters Sidebar -->
        <div class="hidden lg:block filters-sidebar w-80 flex-shrink-0">
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
          <div class="results-header flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <div class="results-info">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ meta.from }}-{{ meta.to }} of {{ meta.total }} alumni
              </p>
            </div>
            
            <!-- Mobile Sort Controls -->
            <div class="sort-controls flex items-center space-x-2">
              <select
                v-model="sortBy"
                @change="loadAlumni"
                class="input-mobile text-sm flex-1 sm:flex-none"
              >
                <option value="name">Sort by Name</option>
                <option value="graduation_year">Sort by Graduation Year</option>
                <option value="location">Sort by Location</option>
                <option value="created_at">Sort by Join Date</option>
              </select>
              
              <button
                @click="toggleSortOrder"
                class="btn-mobile-secondary p-2 flex-shrink-0"
                :title="sortOrder === 'asc' ? 'Sort descending' : 'Sort ascending'"
              >
                <ArrowUpIcon v-if="sortOrder === 'asc'" class="h-4 w-4" />
                <ArrowDownIcon v-else class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="loading-mobile">
            <div class="mobile-grid lg:grid-cols-2 xl:grid-cols-3 gap-4">
              <div v-for="i in 9" :key="i" class="card-mobile">
                <div class="flex items-center space-x-3 mb-3">
                  <div class="skeleton-mobile-avatar"></div>
                  <div class="flex-1">
                    <div class="skeleton-mobile-title w-3/4"></div>
                    <div class="skeleton-mobile-text w-1/2"></div>
                  </div>
                </div>
                <div class="space-y-2">
                  <div class="skeleton-mobile-text"></div>
                  <div class="skeleton-mobile-text w-2/3"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Alumni Cards Grid -->
          <div v-else-if="alumni.length > 0" class="alumni-cards-grid">
            <div class="mobile-grid lg:grid-cols-2 xl:grid-cols-3 gap-4">
              <AlumniCard
                v-for="alumnus in alumni"
                :key="alumnus.id"
                :alumni="alumnus"
                @view-profile="viewProfile"
                @connect="openConnectionModal"
                mobile-optimized
              />
            </div>

            <!-- Mobile Pagination -->
            <div v-if="meta.last_page > 1" class="pagination mt-8">
              <nav class="flex justify-center">
                <div class="flex flex-wrap justify-center gap-2">
                  <button
                    v-if="meta.current_page > 1"
                    @click="goToPage(meta.current_page - 1)"
                    class="btn-mobile-secondary text-sm"
                  >
                    Previous
                  </button>
                  
                  <button
                    v-for="page in visiblePages"
                    :key="page"
                    @click="goToPage(page)"
                    class="btn-mobile text-sm min-w-[44px]"
                    :class="page === meta.current_page
                      ? 'bg-blue-600 text-white hover:bg-blue-700'
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                  >
                    {{ page }}
                  </button>
                  
                  <button
                    v-if="meta.current_page < meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                    class="btn-mobile-secondary text-sm"
                  >
                    Next
                  </button>
                </div>
              </nav>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="error-mobile">
            <UsersIcon class="error-mobile-icon" />
            <h3 class="error-mobile-title">No alumni found</h3>
            <p class="error-mobile-message">
              Try adjusting your search criteria or filters.
            </p>
            <button
              @click="clearFilters"
              class="btn-mobile-primary"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Connection Request Modal -->
      <ConnectionRequestModal
        v-if="showConnectionModal"
        :alumni="selectedAlumni"
        @close="closeConnectionModal"
        @send="sendConnectionRequest"
        mobile-optimized
      />
    </PullToRefresh>
  </AppLayout>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue'
import PullToRefresh from '@/components/PullToRefresh.vue'
import MobileSearchInterface from '@/components/MobileSearchInterface.vue'
import DirectoryFilters from '../Components/DirectoryFilters.vue'
import AlumniCard from '../Components/AlumniCard.vue'
import ConnectionRequestModal from '../Components/ConnectionRequestModal.vue'
import {
  MagnifyingGlassIcon,
  UsersIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'AlumniDirectory',
  components: {
    Head,
    AppLayout,
    MobileHamburgerMenu,
    PullToRefresh,
    MobileSearchInterface,
    DirectoryFilters,
    AlumniCard,
    ConnectionRequestModal,
    MagnifyingGlassIcon,
    UsersIcon,
    ArrowUpIcon,
    ArrowDownIcon
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

    const handleSearch = (query) => {
      searchQuery.value = query
      loadAlumni(1)
    }

    const handleFilterChange = (filters) => {
      updateFilters(filters)
    }

    const refreshDirectory = async () => {
      await loadAlumni(meta.value.current_page)
    }

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
      sendConnectionRequest,
      handleSearch,
      handleFilterChange,
      refreshDirectory
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