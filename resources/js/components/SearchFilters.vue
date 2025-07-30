<template>
  <div class="search-filters">
    <div class="filters-header">
      <h3 class="filters-title">Filters</h3>
      <button @click="clearAllFilters" class="clear-filters-button">
        Clear All
      </button>
    </div>

    <div class="filters-grid">
      <!-- Graduation Year Filter -->
      <div class="filter-group">
        <label class="filter-label">Graduation Year</label>
        <div class="filter-content">
          <div class="year-range">
            <input
              v-model="localFilters.graduation_year.min"
              type="number"
              placeholder="From"
              class="year-input"
              :min="1950"
              :max="currentYear"
              @input="updateFilters"
            />
            <span class="year-separator">to</span>
            <input
              v-model="localFilters.graduation_year.max"
              type="number"
              placeholder="To"
              class="year-input"
              :min="1950"
              :max="currentYear"
              @input="updateFilters"
            />
          </div>
          <div v-if="aggregations.graduation_years" class="filter-options">
            <button
              v-for="year in aggregations.graduation_years.slice(0, 5)"
              :key="year.key"
              @click="selectYear(year.key)"
              class="filter-option"
              :class="{ active: isYearSelected(year.key) }"
            >
              {{ year.key }} ({{ year.count }})
            </button>
          </div>
        </div>
      </div>

      <!-- Location Filter -->
      <div class="filter-group">
        <label class="filter-label">Location</label>
        <div class="filter-content">
          <select
            v-model="localFilters.location"
            class="filter-select"
            @change="updateFilters"
          >
            <option value="">All Locations</option>
            <option
              v-for="location in aggregations.locations"
              :key="location.key"
              :value="location.key"
            >
              {{ location.key }} ({{ location.count }})
            </option>
          </select>
        </div>
      </div>

      <!-- Industry Filter -->
      <div class="filter-group">
        <label class="filter-label">Industry</label>
        <div class="filter-content">
          <div class="checkbox-group">
            <label
              v-for="industry in aggregations.industries"
              :key="industry.key"
              class="checkbox-label"
            >
              <input
                type="checkbox"
                :value="industry.key"
                v-model="localFilters.industry"
                @change="updateFilters"
                class="checkbox-input"
              />
              <span class="checkbox-text">
                {{ industry.key }} ({{ industry.count }})
              </span>
            </label>
          </div>
        </div>
      </div>

      <!-- Company Filter -->
      <div class="filter-group">
        <label class="filter-label">Company</label>
        <div class="filter-content">
          <select
            v-model="localFilters.company"
            class="filter-select"
            @change="updateFilters"
          >
            <option value="">All Companies</option>
            <option
              v-for="company in aggregations.companies"
              :key="company.key"
              :value="company.key"
            >
              {{ company.key }} ({{ company.count }})
            </option>
          </select>
        </div>
      </div>

      <!-- School Filter -->
      <div class="filter-group">
        <label class="filter-label">School</label>
        <div class="filter-content">
          <select
            v-model="localFilters.school"
            class="filter-select"
            @change="updateFilters"
          >
            <option value="">All Schools</option>
            <option
              v-for="school in aggregations.schools"
              :key="school.key"
              :value="school.key"
            >
              {{ school.key }} ({{ school.count }})
            </option>
          </select>
        </div>
      </div>

      <!-- Skills Filter -->
      <div class="filter-group">
        <label class="filter-label">Skills</label>
        <div class="filter-content">
          <div class="skills-tags">
            <button
              v-for="skill in aggregations.skills?.slice(0, 10)"
              :key="skill.key"
              @click="toggleSkill(skill.key)"
              class="skill-tag"
              :class="{ active: localFilters.skills?.includes(skill.key) }"
            >
              {{ skill.key }} ({{ skill.count }})
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Filters Summary -->
    <div v-if="hasActiveFilters" class="active-filters">
      <h4 class="active-filters-title">Active Filters:</h4>
      <div class="active-filters-list">
        <span
          v-for="filter in activeFiltersList"
          :key="filter.key"
          class="active-filter-tag"
        >
          {{ filter.label }}
          <button @click="removeFilter(filter.key)" class="remove-filter">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  aggregations: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'update', 'clear'])

// Reactive data
const currentYear = new Date().getFullYear()
const localFilters = ref({
  graduation_year: { min: null, max: null },
  location: '',
  industry: [],
  company: '',
  school: '',
  skills: [],
  ...props.modelValue
})

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(value => {
    if (Array.isArray(value)) return value.length > 0
    if (typeof value === 'object' && value !== null) {
      return Object.values(value).some(v => v !== null && v !== '')
    }
    return value !== '' && value !== null
  })
})

const activeFiltersList = computed(() => {
  const filters = []
  
  if (localFilters.value.graduation_year?.min || localFilters.value.graduation_year?.max) {
    const min = localFilters.value.graduation_year.min || 'Any'
    const max = localFilters.value.graduation_year.max || 'Any'
    filters.push({
      key: 'graduation_year',
      label: `Graduated: ${min} - ${max}`
    })
  }
  
  if (localFilters.value.location) {
    filters.push({
      key: 'location',
      label: `Location: ${localFilters.value.location}`
    })
  }
  
  if (localFilters.value.industry?.length > 0) {
    filters.push({
      key: 'industry',
      label: `Industry: ${localFilters.value.industry.join(', ')}`
    })
  }
  
  if (localFilters.value.company) {
    filters.push({
      key: 'company',
      label: `Company: ${localFilters.value.company}`
    })
  }
  
  if (localFilters.value.school) {
    filters.push({
      key: 'school',
      label: `School: ${localFilters.value.school}`
    })
  }
  
  if (localFilters.value.skills?.length > 0) {
    filters.push({
      key: 'skills',
      label: `Skills: ${localFilters.value.skills.join(', ')}`
    })
  }
  
  return filters
})

// Methods
const updateFilters = () => {
  emit('update:modelValue', { ...localFilters.value })
  emit('update')
}

const clearAllFilters = () => {
  localFilters.value = {
    graduation_year: { min: null, max: null },
    location: '',
    industry: [],
    company: '',
    school: '',
    skills: []
  }
  updateFilters()
  emit('clear')
}

const selectYear = (year) => {
  localFilters.value.graduation_year = { min: year, max: year }
  updateFilters()
}

const isYearSelected = (year) => {
  return localFilters.value.graduation_year?.min === year && 
         localFilters.value.graduation_year?.max === year
}

const toggleSkill = (skill) => {
  if (!localFilters.value.skills) {
    localFilters.value.skills = []
  }
  
  const index = localFilters.value.skills.indexOf(skill)
  if (index > -1) {
    localFilters.value.skills.splice(index, 1)
  } else {
    localFilters.value.skills.push(skill)
  }
  
  updateFilters()
}

const removeFilter = (filterKey) => {
  switch (filterKey) {
    case 'graduation_year':
      localFilters.value.graduation_year = { min: null, max: null }
      break
    case 'location':
      localFilters.value.location = ''
      break
    case 'industry':
      localFilters.value.industry = []
      break
    case 'company':
      localFilters.value.company = ''
      break
    case 'school':
      localFilters.value.school = ''
      break
    case 'skills':
      localFilters.value.skills = []
      break
  }
  updateFilters()
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localFilters.value = { ...newValue }
}, { deep: true })
</script>

<style scoped>
.search-filters {
  @apply bg-gray-50 border border-gray-200 rounded-lg p-6;
}

.filters-header {
  @apply flex justify-between items-center mb-4;
}

.filters-title {
  @apply text-lg font-semibold text-gray-900;
}

.clear-filters-button {
  @apply text-sm text-blue-600 hover:text-blue-800;
}

.filters-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.filter-group {
  @apply space-y-2;
}

.filter-label {
  @apply block text-sm font-medium text-gray-700;
}

.filter-content {
  @apply space-y-2;
}

.year-range {
  @apply flex items-center gap-2;
}

.year-input {
  @apply flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.year-separator {
  @apply text-gray-500;
}

.filter-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.filter-options {
  @apply flex flex-wrap gap-2;
}

.filter-option {
  @apply px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-gray-100 transition-colors;
}

.filter-option.active {
  @apply bg-blue-100 border-blue-300 text-blue-700;
}

.checkbox-group {
  @apply space-y-2 max-h-32 overflow-y-auto;
}

.checkbox-label {
  @apply flex items-center gap-2 cursor-pointer;
}

.checkbox-input {
  @apply rounded border-gray-300 text-blue-600 focus:ring-blue-500;
}

.checkbox-text {
  @apply text-sm text-gray-700;
}

.skills-tags {
  @apply flex flex-wrap gap-2;
}

.skill-tag {
  @apply px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-gray-100 transition-colors;
}

.skill-tag.active {
  @apply bg-blue-100 border-blue-300 text-blue-700;
}

.active-filters {
  @apply mt-6 pt-4 border-t border-gray-200;
}

.active-filters-title {
  @apply text-sm font-medium text-gray-700 mb-2;
}

.active-filters-list {
  @apply flex flex-wrap gap-2;
}

.active-filter-tag {
  @apply inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full;
}

.remove-filter {
  @apply text-blue-600 hover:text-blue-800;
}
</style>