<template>
  <div class="location-filter">
    <div class="filter-section">
      <label class="filter-label">
        <i class="fas fa-calendar-alt"></i>
        Graduation Year
      </label>
      <select 
        v-model="localFilters.graduation_year"
        multiple
        class="form-select form-select-sm"
        @change="emitFilters"
      >
        <option 
          v-for="year in graduationYears" 
          :key="year" 
          :value="year"
        >
          {{ year }}
        </option>
      </select>
    </div>

    <div class="filter-section">
      <label class="filter-label">
        <i class="fas fa-industry"></i>
        Industry
      </label>
      <select 
        v-model="localFilters.industry"
        multiple
        class="form-select form-select-sm"
        @change="emitFilters"
      >
        <option 
          v-for="industry in industries" 
          :key="industry" 
          :value="industry"
        >
          {{ industry }}
        </option>
      </select>
    </div>

    <div class="filter-section">
      <label class="filter-label">
        <i class="fas fa-globe"></i>
        Country
      </label>
      <select 
        v-model="localFilters.country"
        multiple
        class="form-select form-select-sm"
        @change="emitFilters"
      >
        <option 
          v-for="country in countries" 
          :key="country" 
          :value="country"
        >
          {{ country }}
        </option>
      </select>
    </div>

    <div class="filter-section">
      <label class="filter-label">
        <i class="fas fa-map"></i>
        State/Province
      </label>
      <select 
        v-model="localFilters.state"
        multiple
        class="form-select form-select-sm"
        @change="emitFilters"
        :disabled="!localFilters.country.length"
      >
        <option 
          v-for="state in filteredStates" 
          :key="state" 
          :value="state"
        >
          {{ state }}
        </option>
      </select>
    </div>

    <div class="filter-actions">
      <button 
        class="btn btn-sm btn-outline-secondary"
        @click="clearFilters"
        :disabled="!hasActiveFilters"
      >
        <i class="fas fa-times"></i>
        Clear All
      </button>
      
      <button 
        class="btn btn-sm btn-primary"
        @click="toggleAdvanced"
      >
        <i class="fas fa-cog"></i>
        {{ showAdvanced ? 'Simple' : 'Advanced' }}
      </button>
    </div>

    <!-- Advanced Filters -->
    <div v-if="showAdvanced" class="advanced-filters">
      <div class="filter-section">
        <label class="filter-label">
          <i class="fas fa-search"></i>
          Search Alumni
        </label>
        <input 
          v-model="localFilters.search"
          type="text"
          class="form-control form-control-sm"
          placeholder="Search by name, company, or position..."
          @input="debounceEmitFilters"
        />
      </div>

      <div class="filter-section">
        <label class="filter-label">
          <i class="fas fa-building"></i>
          Company
        </label>
        <input 
          v-model="localFilters.company"
          type="text"
          class="form-control form-control-sm"
          placeholder="Filter by company..."
          @input="debounceEmitFilters"
        />
      </div>

      <div class="filter-section">
        <label class="filter-label">
          <i class="fas fa-briefcase"></i>
          Position
        </label>
        <input 
          v-model="localFilters.position"
          type="text"
          class="form-control form-control-sm"
          placeholder="Filter by position..."
          @input="debounceEmitFilters"
        />
      </div>

      <div class="filter-section">
        <label class="filter-label">
          <i class="fas fa-ruler"></i>
          Distance (km)
        </label>
        <div class="distance-filter">
          <input 
            v-model.number="localFilters.distance"
            type="range"
            min="5"
            max="500"
            step="5"
            class="form-range"
            @input="emitFilters"
          />
          <span class="distance-value">{{ localFilters.distance }}km</span>
        </div>
      </div>

      <div class="filter-section">
        <label class="filter-label">
          <i class="fas fa-eye"></i>
          Profile Visibility
        </label>
        <div class="visibility-options">
          <div class="form-check form-check-inline">
            <input 
              id="visibility-public"
              v-model="localFilters.visibility"
              class="form-check-input"
              type="checkbox"
              value="public"
              @change="emitFilters"
            />
            <label class="form-check-label" for="visibility-public">
              Public
            </label>
          </div>
          <div class="form-check form-check-inline">
            <input 
              id="visibility-alumni"
              v-model="localFilters.visibility"
              class="form-check-input"
              type="checkbox"
              value="alumni_only"
              @change="emitFilters"
            />
            <label class="form-check-label" for="visibility-alumni">
              Alumni Only
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters" class="active-filters">
      <h6>Active Filters:</h6>
      <div class="filter-tags">
        <span 
          v-for="year in localFilters.graduation_year" 
          :key="`year-${year}`"
          class="filter-tag"
        >
          {{ year }}
          <button @click="removeFilter('graduation_year', year)">×</button>
        </span>
        
        <span 
          v-for="industry in localFilters.industry" 
          :key="`industry-${industry}`"
          class="filter-tag"
        >
          {{ industry }}
          <button @click="removeFilter('industry', industry)">×</button>
        </span>
        
        <span 
          v-for="country in localFilters.country" 
          :key="`country-${country}`"
          class="filter-tag"
        >
          {{ country }}
          <button @click="removeFilter('country', country)">×</button>
        </span>
        
        <span 
          v-for="state in localFilters.state" 
          :key="`state-${state}`"
          class="filter-tag"
        >
          {{ state }}
          <button @click="removeFilter('state', state)">×</button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface LocationFilters {
  graduation_year: number[]
  industry: string[]
  country: string[]
  state: string[]
  search?: string
  company?: string
  position?: string
  distance?: number
  visibility?: string[]
}

interface LocationFilterProps {
  filters: LocationFilters
}

const props = defineProps<LocationFilterProps>()

const emit = defineEmits<{
  'update:filters': [filters: LocationFilters]
}>()

// Reactive data
const showAdvanced = ref(false)
const localFilters = ref<LocationFilters>({
  graduation_year: [],
  industry: [],
  country: [],
  state: [],
  search: '',
  company: '',
  position: '',
  distance: 50,
  visibility: ['public', 'alumni_only']
})

// Filter options (would typically come from API)
const graduationYears = ref<number[]>([])
const industries = ref<string[]>([])
const countries = ref<string[]>([])
const states = ref<string[]>([])

// Computed properties
const hasActiveFilters = computed(() => {
  return localFilters.value.graduation_year.length > 0 ||
         localFilters.value.industry.length > 0 ||
         localFilters.value.country.length > 0 ||
         localFilters.value.state.length > 0 ||
         (localFilters.value.search && localFilters.value.search.length > 0) ||
         (localFilters.value.company && localFilters.value.company.length > 0) ||
         (localFilters.value.position && localFilters.value.position.length > 0)
})

const filteredStates = computed(() => {
  if (localFilters.value.country.length === 0) {
    return states.value
  }
  
  // Filter states based on selected countries
  // This would typically involve a more complex mapping
  return states.value
})

// Methods
const loadFilterOptions = async () => {
  try {
    const response = await fetch('/api/alumni/filter-options')
    const data = await response.json()
    
    graduationYears.value = data.graduation_years || []
    industries.value = data.industries || []
    countries.value = data.countries || []
    states.value = data.states || []
  } catch (error) {
    console.error('Error loading filter options:', error)
    
    // Fallback data
    graduationYears.value = Array.from(
      { length: 50 }, 
      (_, i) => new Date().getFullYear() - i
    )
    
    industries.value = [
      'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
      'Consulting', 'Marketing', 'Sales', 'Engineering', 'Research'
    ]
    
    countries.value = [
      'United States', 'Canada', 'United Kingdom', 'Germany', 'France',
      'Australia', 'Japan', 'Singapore', 'India', 'Brazil'
    ]
    
    states.value = [
      'California', 'New York', 'Texas', 'Florida', 'Illinois',
      'Pennsylvania', 'Ohio', 'Georgia', 'North Carolina', 'Michigan'
    ]
  }
}

const emitFilters = () => {
  emit('update:filters', { ...localFilters.value })
}

let debounceTimeout: number
const debounceEmitFilters = () => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(emitFilters, 300)
}

const clearFilters = () => {
  localFilters.value = {
    graduation_year: [],
    industry: [],
    country: [],
    state: [],
    search: '',
    company: '',
    position: '',
    distance: 50,
    visibility: ['public', 'alumni_only']
  }
  emitFilters()
}

const removeFilter = (filterType: keyof LocationFilters, value: any) => {
  const filterArray = localFilters.value[filterType] as any[]
  const index = filterArray.indexOf(value)
  if (index > -1) {
    filterArray.splice(index, 1)
    emitFilters()
  }
}

const toggleAdvanced = () => {
  showAdvanced.value = !showAdvanced.value
}

// Watch for prop changes
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true, immediate: true })

// Initialize
onMounted(() => {
  loadFilterOptions()
})
</script>

<style scoped>
.location-filter {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  max-width: 300px;
}

.filter-section {
  margin-bottom: 1rem;
}

.filter-section:last-child {
  margin-bottom: 0;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9em;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
}

.filter-label i {
  width: 14px;
  color: #6c757d;
}

.form-select,
.form-control {
  font-size: 0.85em;
}

.form-select[multiple] {
  height: auto;
  min-height: 80px;
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.filter-actions .btn {
  flex: 1;
  font-size: 0.8em;
}

.advanced-filters {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.distance-filter {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.distance-value {
  font-size: 0.8em;
  color: #6c757d;
  min-width: 50px;
}

.visibility-options {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-check-inline {
  margin-right: 0;
}

.active-filters {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.active-filters h6 {
  font-size: 0.9em;
  margin-bottom: 0.5rem;
  color: #495057;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.filter-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: #e9ecef;
  color: #495057;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75em;
}

.filter-tag button {
  background: none;
  border: none;
  color: #6c757d;
  font-size: 1.2em;
  line-height: 1;
  cursor: pointer;
  padding: 0;
  margin-left: 0.25rem;
}

.filter-tag button:hover {
  color: #dc3545;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .location-filter {
    max-width: none;
    width: 100%;
  }
  
  .filter-actions {
    flex-direction: column;
  }
  
  .filter-actions .btn {
    flex: none;
  }
}

/* Custom scrollbar for multi-select */
.form-select[multiple]::-webkit-scrollbar {
  width: 6px;
}

.form-select[multiple]::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.form-select[multiple]::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.form-select[multiple]::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>