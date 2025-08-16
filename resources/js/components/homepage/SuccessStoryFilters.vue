<template>
  <div class="success-story-filters">
    <div class="filters-header">
      <h3 class="filters-title">Filter Success Stories</h3>
      <button 
        v-if="hasActiveFilters"
        @click="clearAllFilters"
        class="clear-filters-button"
        aria-label="Clear all filters"
      >
        Clear All
      </button>
    </div>

    <div class="filters-grid">
      <!-- Industry Filter -->
      <div class="filter-group">
        <label for="industry-filter" class="filter-label">Industry</label>
        <select 
          id="industry-filter"
          v-model="selectedIndustry"
          @change="updateFilters"
          class="filter-select"
        >
          <option value="">All Industries</option>
          <option 
            v-for="option in industryOptions" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }} ({{ option.count }})
          </option>
        </select>
      </div>

      <!-- Graduation Year Filter -->
      <div class="filter-group">
        <label for="graduation-year-filter" class="filter-label">Graduation Year</label>
        <select 
          id="graduation-year-filter"
          v-model="selectedGraduationYear"
          @change="updateFilters"
          class="filter-select"
        >
          <option value="">All Years</option>
          <option 
            v-for="option in graduationYearOptions" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }} ({{ option.count }})
          </option>
        </select>
      </div>

      <!-- Career Stage Filter -->
      <div class="filter-group">
        <label for="career-stage-filter" class="filter-label">Career Stage</label>
        <select 
          id="career-stage-filter"
          v-model="selectedCareerStage"
          @change="updateFilters"
          class="filter-select"
        >
          <option value="">All Stages</option>
          <option 
            v-for="option in careerStageOptions" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }} ({{ option.count }})
          </option>
        </select>
      </div>

      <!-- Success Type Filter -->
      <div class="filter-group">
        <label for="success-type-filter" class="filter-label">Success Type</label>
        <select 
          id="success-type-filter"
          v-model="selectedSuccessType"
          @change="updateFilters"
          class="filter-select"
        >
          <option value="">All Types</option>
          <option 
            v-for="option in successTypeOptions" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }} ({{ option.count }})
          </option>
        </select>
      </div>
    </div>

    <!-- Mobile Filter Toggle -->
    <div class="mobile-filter-toggle md:hidden">
      <button 
        @click="toggleMobileFilters"
        class="mobile-toggle-button"
        :aria-expanded="showMobileFilters"
        aria-controls="mobile-filters"
      >
        <svg class="filter-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
        </svg>
        <span>Filters</span>
        <span v-if="activeFiltersCount > 0" class="filter-count">{{ activeFiltersCount }}</span>
        <svg 
          class="chevron-icon" 
          :class="{ 'rotated': showMobileFilters }"
          viewBox="0 0 24 24" 
          fill="currentColor"
        >
          <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
        </svg>
      </button>
      
      <div 
        v-show="showMobileFilters"
        id="mobile-filters"
        class="mobile-filters-panel"
      >
        <div class="mobile-filters-grid">
          <!-- Mobile Industry Filter -->
          <div class="mobile-filter-group">
            <label for="mobile-industry-filter" class="mobile-filter-label">Industry</label>
            <select 
              id="mobile-industry-filter"
              v-model="selectedIndustry"
              @change="updateFilters"
              class="mobile-filter-select"
            >
              <option value="">All Industries</option>
              <option 
                v-for="option in industryOptions" 
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }} ({{ option.count }})
              </option>
            </select>
          </div>

          <!-- Mobile Graduation Year Filter -->
          <div class="mobile-filter-group">
            <label for="mobile-graduation-year-filter" class="mobile-filter-label">Graduation Year</label>
            <select 
              id="mobile-graduation-year-filter"
              v-model="selectedGraduationYear"
              @change="updateFilters"
              class="mobile-filter-select"
            >
              <option value="">All Years</option>
              <option 
                v-for="option in graduationYearOptions" 
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }} ({{ option.count }})
              </option>
            </select>
          </div>

          <!-- Mobile Career Stage Filter -->
          <div class="mobile-filter-group">
            <label for="mobile-career-stage-filter" class="mobile-filter-label">Career Stage</label>
            <select 
              id="mobile-career-stage-filter"
              v-model="selectedCareerStage"
              @change="updateFilters"
              class="mobile-filter-select"
            >
              <option value="">All Stages</option>
              <option 
                v-for="option in careerStageOptions" 
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }} ({{ option.count }})
              </option>
            </select>
          </div>

          <!-- Mobile Success Type Filter -->
          <div class="mobile-filter-group">
            <label for="mobile-success-type-filter" class="mobile-filter-label">Success Type</label>
            <select 
              id="mobile-success-type-filter"
              v-model="selectedSuccessType"
              @change="updateFilters"
              class="mobile-filter-select"
            >
              <option value="">All Types</option>
              <option 
                v-for="option in successTypeOptions" 
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }} ({{ option.count }})
              </option>
            </select>
          </div>
        </div>

        <div class="mobile-filter-actions">
          <button 
            @click="clearAllFilters"
            class="mobile-clear-button"
            :disabled="!hasActiveFilters"
          >
            Clear All
          </button>
          <button 
            @click="toggleMobileFilters"
            class="mobile-apply-button"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters" class="active-filters">
      <h4 class="active-filters-title">Active Filters:</h4>
      <div class="active-filters-list">
        <span 
          v-if="selectedIndustry"
          class="active-filter-tag"
        >
          Industry: {{ getIndustryLabel(selectedIndustry) }}
          <button 
            @click="clearFilter('industry')"
            class="remove-filter-button"
            aria-label="Remove industry filter"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </span>
        
        <span 
          v-if="selectedGraduationYear"
          class="active-filter-tag"
        >
          Year: {{ getGraduationYearLabel(selectedGraduationYear) }}
          <button 
            @click="clearFilter('graduationYear')"
            class="remove-filter-button"
            aria-label="Remove graduation year filter"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </span>
        
        <span 
          v-if="selectedCareerStage"
          class="active-filter-tag"
        >
          Stage: {{ getCareerStageLabel(selectedCareerStage) }}
          <button 
            @click="clearFilter('careerStage')"
            class="remove-filter-button"
            aria-label="Remove career stage filter"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </span>
        
        <span 
          v-if="selectedSuccessType"
          class="active-filter-tag"
        >
          Type: {{ getSuccessTypeLabel(selectedSuccessType) }}
          <button 
            @click="clearFilter('successType')"
            class="remove-filter-button"
            aria-label="Remove success type filter"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </span>
      </div>
    </div>

    <!-- Results Count -->
    <div class="results-count">
      <p class="count-text">
        Showing {{ filteredCount }} of {{ totalCount }} success stories
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { StoryFilter, FilterOption } from '@/types/homepage'

interface Props {
  filters: StoryFilter[]
  totalCount: number
  filteredCount: number
}

interface Emits {
  (e: 'filter-change', filters: Record<string, string>): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Filter state
const selectedIndustry = ref('')
const selectedGraduationYear = ref('')
const selectedCareerStage = ref('')
const selectedSuccessType = ref('')
const showMobileFilters = ref(false)

// Computed filter options
const industryOptions = computed(() => {
  const industryFilter = props.filters.find(f => f.key === 'industry')
  return industryFilter?.options || []
})

const graduationYearOptions = computed(() => {
  const yearFilter = props.filters.find(f => f.key === 'graduationYear')
  return yearFilter?.options || []
})

const careerStageOptions = computed(() => {
  const stageFilter = props.filters.find(f => f.key === 'careerStage')
  return stageFilter?.options || []
})

const successTypeOptions = computed(() => {
  const typeFilter = props.filters.find(f => f.key === 'successType')
  return typeFilter?.options || []
})

// Active filters tracking
const hasActiveFilters = computed(() => {
  return !!(selectedIndustry.value || selectedGraduationYear.value || 
           selectedCareerStage.value || selectedSuccessType.value)
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (selectedIndustry.value) count++
  if (selectedGraduationYear.value) count++
  if (selectedCareerStage.value) count++
  if (selectedSuccessType.value) count++
  return count
})

// Methods
const updateFilters = () => {
  const filters = {
    industry: selectedIndustry.value,
    graduationYear: selectedGraduationYear.value,
    careerStage: selectedCareerStage.value,
    successType: selectedSuccessType.value
  }
  
  emit('filter-change', filters)
}

const clearAllFilters = () => {
  selectedIndustry.value = ''
  selectedGraduationYear.value = ''
  selectedCareerStage.value = ''
  selectedSuccessType.value = ''
  updateFilters()
}

const clearFilter = (filterType: string) => {
  switch (filterType) {
    case 'industry':
      selectedIndustry.value = ''
      break
    case 'graduationYear':
      selectedGraduationYear.value = ''
      break
    case 'careerStage':
      selectedCareerStage.value = ''
      break
    case 'successType':
      selectedSuccessType.value = ''
      break
  }
  updateFilters()
}

const toggleMobileFilters = () => {
  showMobileFilters.value = !showMobileFilters.value
}

// Label getters for active filters display
const getIndustryLabel = (value: string): string => {
  const option = industryOptions.value.find(opt => opt.value === value)
  return option?.label || value
}

const getGraduationYearLabel = (value: string): string => {
  const option = graduationYearOptions.value.find(opt => opt.value === value)
  return option?.label || value
}

const getCareerStageLabel = (value: string): string => {
  const option = careerStageOptions.value.find(opt => opt.value === value)
  return option?.label || value
}

const getSuccessTypeLabel = (value: string): string => {
  const option = successTypeOptions.value.find(opt => opt.value === value)
  return option?.label || value
}

// Watch for external filter changes
watch(() => props.filters, () => {
  // Reset filters if filter options change
}, { deep: true })
</script>

<style scoped>
.success-story-filters {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8;
}

.filters-header {
  @apply flex justify-between items-center mb-6;
}

.filters-title {
  @apply text-xl font-semibold text-gray-900;
}

.clear-filters-button {
  @apply text-blue-600 hover:text-blue-800 font-medium transition-colors;
}

.filters-grid {
  @apply hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6;
}

.filter-group {
  @apply space-y-2;
}

.filter-label {
  @apply block text-sm font-medium text-gray-700;
}

.filter-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.mobile-filter-toggle {
  @apply mb-6;
}

.mobile-toggle-button {
  @apply w-full flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors;
}

.filter-icon {
  @apply w-5 h-5 text-gray-600;
}

.filter-count {
  @apply bg-blue-600 text-white text-xs rounded-full px-2 py-1 min-w-5 h-5 flex items-center justify-center;
}

.chevron-icon {
  @apply w-5 h-5 text-gray-400 transition-transform duration-200;
}

.chevron-icon.rotated {
  @apply rotate-180;
}

.mobile-filters-panel {
  @apply mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200;
}

.mobile-filters-grid {
  @apply space-y-4 mb-4;
}

.mobile-filter-group {
  @apply space-y-2;
}

.mobile-filter-label {
  @apply block text-sm font-medium text-gray-700;
}

.mobile-filter-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.mobile-filter-actions {
  @apply flex gap-3 pt-4 border-t border-gray-200;
}

.mobile-clear-button {
  @apply flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.mobile-apply-button {
  @apply flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors;
}

.active-filters {
  @apply mb-6;
}

.active-filters-title {
  @apply text-sm font-medium text-gray-700 mb-3;
}

.active-filters-list {
  @apply flex flex-wrap gap-2;
}

.active-filter-tag {
  @apply inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full;
}

.remove-filter-button {
  @apply text-blue-600 hover:text-blue-800 transition-colors;
}

.remove-filter-button svg {
  @apply w-4 h-4;
}

.results-count {
  @apply pt-4 border-t border-gray-200;
}

.count-text {
  @apply text-sm text-gray-600 font-medium;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .success-story-filters {
    @apply p-4;
  }
  
  .filters-header {
    @apply mb-4;
  }
  
  .filters-title {
    @apply text-lg;
  }
}
</style>