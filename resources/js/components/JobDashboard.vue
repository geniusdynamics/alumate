<template>
  <div class="job-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1 class="dashboard-title">Job Recommendations</h1>
        <p class="dashboard-subtitle">
          Discover opportunities through your alumni network
        </p>
      </div>
      
      <div class="header-stats">
        <div class="stat-card">
          <div class="stat-number">{{ meta.matched_jobs }}</div>
          <div class="stat-label">Matched Jobs</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">{{ meta.user_applications }}</div>
          <div class="stat-label">Applications</div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label class="filter-label">Location</label>
          <input
            v-model="filters.location"
            type="text"
            placeholder="Enter city or remote"
            class="filter-input"
            @input="debouncedSearch"
          />
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Min Match Score</label>
          <select v-model="filters.minScore" class="filter-select" @change="searchJobs">
            <option value="50">50%+</option>
            <option value="60">60%+</option>
            <option value="70">70%+</option>
            <option value="80">80%+</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-checkbox">
            <input
              v-model="filters.remoteOnly"
              type="checkbox"
              @change="searchJobs"
            />
            <span class="checkmark"></span>
            Remote Only
          </label>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Finding your perfect matches...</p>
    </div>

    <!-- Jobs List -->
    <div v-else-if="jobs.length > 0" class="jobs-container">
      <div class="jobs-grid">
        <JobCard
          v-for="job in jobs"
          :key="job.id"
          :job="job"
          @view-details="viewJobDetails"
          @apply="handleApply"
        />
      </div>

      <!-- Load More Button -->
      <div v-if="hasMoreJobs" class="load-more-container">
        <button
          @click="loadMoreJobs"
          :disabled="loadingMore"
          class="load-more-btn"
        >
          <span v-if="loadingMore">Loading...</span>
          <span v-else>Load More Jobs</span>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">🎯</div>
      <h3>No matching jobs found</h3>
      <p>Try adjusting your filters or check back later for new opportunities.</p>
      <button @click="resetFilters" class="reset-filters-btn">
        Reset Filters
      </button>
    </div>

    <!-- Job Details Modal -->
    <JobDetailsModal
      v-if="selectedJob"
      :job="selectedJob"
      :show="showJobModal"
      @close="closeJobModal"
      @apply="handleApply"
      @request-introduction="handleIntroductionRequest"
    />

    <!-- Application Modal -->
    <ApplicationModal
      v-if="applicationJob"
      :job="applicationJob"
      :show="showApplicationModal"
      @close="closeApplicationModal"
      @submit="submitApplication"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { debounce } from 'lodash'
import JobCard from './JobCard.vue'
import JobDetailsModal from './JobDetailsModal.vue'
import ApplicationModal from './ApplicationModal.vue'

// Reactive data
const jobs = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const selectedJob = ref(null)
const applicationJob = ref(null)
const showJobModal = ref(false)
const showApplicationModal = ref(false)

const meta = reactive({
  total_jobs: 0,
  matched_jobs: 0,
  user_applications: 0
})

const filters = reactive({
  location: '',
  minScore: 50,
  remoteOnly: false
})

// Computed properties
const hasMoreJobs = computed(() => currentPage.value < lastPage.value)

// Methods
const searchJobs = async (resetPage = true) => {
  if (resetPage) {
    currentPage.value = 1
    jobs.value = []
  }
  
  loading.value = true
  
  try {
    const params = new URLSearchParams({
      page: currentPage.value,
      per_page: 10,
      min_score: filters.minScore,
      ...(filters.location && { location: filters.location }),
      ...(filters.remoteOnly && { remote_only: true })
    })

    const response = await fetch(`/api/jobs/recommendations?${params}`)
    const data = await response.json()

    if (data.success) {
      if (resetPage) {
        jobs.value = data.data.data
      } else {
        jobs.value.push(...data.data.data)
      }
      
      currentPage.value = data.data.current_page
      lastPage.value = data.data.last_page
      
      Object.assign(meta, data.meta)
    }
  } catch (error) {
    console.error('Error fetching jobs:', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMoreJobs = () => {
  if (hasMoreJobs.value && !loadingMore.value) {
    currentPage.value++
    loadingMore.value = true
    searchJobs(false)
  }
}

const debouncedSearch = debounce(() => {
  searchJobs()
}, 500)

const resetFilters = () => {
  filters.location = ''
  filters.minScore = 50
  filters.remoteOnly = false
  searchJobs()
}

const viewJobDetails = async (job) => {
  try {
    const response = await fetch(`/api/jobs/${job.id}`)
    const data = await response.json()
    
    if (data.success) {
      selectedJob.value = data.data
      showJobModal.value = true
    }
  } catch (error) {
    console.error('Error fetching job details:', error)
  }
}

const closeJobModal = () => {
  showJobModal.value = false
  selectedJob.value = null
}

const handleApply = (job) => {
  applicationJob.value = job
  showApplicationModal.value = true
  closeJobModal()
}

const closeApplicationModal = () => {
  showApplicationModal.value = false
  applicationJob.value = null
}

const submitApplication = async (applicationData) => {
  try {
    const formData = new FormData()
    formData.append('cover_letter', applicationData.coverLetter)
    
    if (applicationData.resume) {
      formData.append('resume', applicationData.resume)
    }
    
    if (applicationData.introductionContactId) {
      formData.append('introduction_contact_id', applicationData.introductionContactId)
    }

    const response = await fetch(`/api/jobs/${applicationJob.value.id}/apply`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    const data = await response.json()
    
    if (data.success) {
      // Update job application status in the list
      const jobIndex = jobs.value.findIndex(j => j.id === applicationJob.value.id)
      if (jobIndex !== -1) {
        jobs.value[jobIndex].has_applied = true
      }
      
      // Show success message
      alert('Application submitted successfully!')
      closeApplicationModal()
    } else {
      alert(data.message || 'Failed to submit application')
    }
  } catch (error) {
    console.error('Error submitting application:', error)
    alert('Failed to submit application. Please try again.')
  }
}

const handleIntroductionRequest = async (contactId, message) => {
  try {
    const response = await fetch(`/api/jobs/${selectedJob.value.id}/request-introduction`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        contact_id: contactId,
        message: message
      })
    })

    const data = await response.json()
    
    if (data.success) {
      alert('Introduction request sent successfully!')
    } else {
      alert(data.message || 'Failed to send introduction request')
    }
  } catch (error) {
    console.error('Error sending introduction request:', error)
    alert('Failed to send introduction request. Please try again.')
  }
}

// Lifecycle
onMounted(() => {
  searchJobs()
})
</script>

<style scoped>
.job-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 0.5rem 0;
}

.header-content p {
  color: #6b7280;
  margin: 0;
}

.header-stats {
  display: flex;
  gap: 1rem;
}

.stat-card {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  min-width: 100px;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #3b82f6;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.filters-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 0.75rem;
}

.filters-row {
  display: flex;
  gap: 1.5rem;
  align-items: end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.filter-input,
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  min-width: 200px;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.filter-checkbox input[type="checkbox"] {
  margin: 0;
}

.loading-container {
  text-align: center;
  padding: 4rem 2rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.jobs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.load-more-container {
  text-align: center;
}

.load-more-btn {
  padding: 0.75rem 2rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.load-more-btn:hover:not(:disabled) {
  background: #2563eb;
}

.load-more-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #6b7280;
  margin-bottom: 2rem;
}

.reset-filters-btn {
  padding: 0.75rem 1.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.reset-filters-btn:hover {
  background: #2563eb;
}

@media (max-width: 768px) {
  .job-dashboard {
    padding: 1rem;
  }
  
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-stats {
    align-self: stretch;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-input,
  .filter-select {
    min-width: auto;
  }
  
  .jobs-grid {
    grid-template-columns: 1fr;
  }
}
</style>