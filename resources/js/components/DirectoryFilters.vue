<template>
  <div class="directory-filters bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="filters-header flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
      <button
        v-if="hasActiveFilters"
        @click="$emit('clear-filters')"
        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
      >
        Clear All
      </button>
    </div>

    <div class="filters-content space-y-6">
      <!-- Graduation Year Range -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Graduation Year
        </label>
        <div class="graduation-year-filter">
          <div class="flex items-center space-x-3 mb-3">
            <input
              v-model.number="localFilters.graduation_year_from"
              @change="updateFilters"
              type="number"
              :min="filters.graduation_years?.min || 1950"
              :max="filters.graduation_years?.max || new Date().getFullYear()"
              placeholder="From"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <span class="text-gray-500">to</span>
            <input
              v-model.number="localFilters.graduation_year_to"
              @change="updateFilters"
              type="number"
              :min="filters.graduation_years?.min || 1950"
              :max="filters.graduation_years?.max || new Date().getFullYear()"
              placeholder="To"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          
          <!-- Range Slider -->
          <div class="range-slider-container">
            <input
              v-model.number="localFilters.graduation_year_from"
              @input="updateFilters"
              type="range"
              :min="filters.graduation_years?.min || 1950"
              :max="filters.graduation_years?.max || new Date().getFullYear()"
              class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider-thumb"
            />
          </div>
        </div>
      </div>

      <!-- Location Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Location
        </label>
        <div class="location-filter relative">
          <input
            v-model="locationQuery"
            @input="searchLocations"
            @focus="showLocationSuggestions = true"
            type="text"
            placeholder="Search locations..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          
          <!-- Location Suggestions Dropdown -->
          <div
            v-if="showLocationSuggestions && locationSuggestions.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
          >
            <button
              v-for="location in locationSuggestions"
              :key="location.value"
              @click="selectLocation(location.value)"
              class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex justify-between items-center"
            >
              <span>{{ location.value }}</span>
              <span class="text-gray-500 text-xs">{{ location.count }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Industry Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Industry
        </label>
        <div class="industry-filter">
          <div class="max-h-48 overflow-y-auto space-y-2">
            <label
              v-for="industry in filters.industries?.slice(0, 15) || []"
              :key="industry.industry"
              class="flex items-center"
            >
              <input
                v-model="localFilters.industries"
                :value="industry.industry"
                @change="updateFilters"
                type="checkbox"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700 flex-1">{{ industry.industry }}</span>
              <span class="text-xs text-gray-500">{{ industry.count }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Company Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Company
        </label>
        <div class="company-filter relative">
          <input
            v-model="companyQuery"
            @input="searchCompanies"
            @focus="showCompanySuggestions = true"
            type="text"
            placeholder="Search companies..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          
          <!-- Company Suggestions Dropdown -->
          <div
            v-if="showCompanySuggestions && companySuggestions.length > 0"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
          >
            <button
              v-for="company in companySuggestions"
              :key="company.value"
              @click="selectCompany(company.value)"
              class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex justify-between items-center"
            >
              <span>{{ company.value }}</span>
              <span class="text-gray-500 text-xs">{{ company.count }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Skills Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Skills
        </label>
        <div class="skills-filter">
          <div class="selected-skills mb-3">
            <div v-if="localFilters.skills?.length > 0" class="flex flex-wrap gap-2">
              <span
                v-for="skill in localFilters.skills"
                :key="skill"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ skill }}
                <button
                  @click="removeSkill(skill)"
                  class="ml-1 inline-flex items-center justify-center w-4 h-4 text-blue-400 hover:text-blue-600"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </span>
            </div>
          </div>
          
          <div class="skill-search relative">
            <input
              v-model="skillQuery"
              @input="searchSkills"
              @focus="showSkillSuggestions = true"
              type="text"
              placeholder="Search skills..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            
            <!-- Skill Suggestions Dropdown -->
            <div
              v-if="showSkillSuggestions && skillSuggestions.length > 0"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="skill in skillSuggestions"
                :key="skill.value"
                @click="addSkill(skill.value)"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex justify-between items-center"
              >
                <span>{{ skill.value }}</span>
                <span class="text-gray-500 text-xs">{{ skill.count }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Institution Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Institution
        </label>
        <div class="institution-filter">
          <div class="max-h-48 overflow-y-auto space-y-2">
            <label
              v-for="institution in filters.institutions?.slice(0, 10) || []"
              :key="institution.id"
              class="flex items-center"
            >
              <input
                v-model="localFilters.institutions"
                :value="institution.id"
                @change="updateFilters"
                type="checkbox"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700 flex-1">{{ institution.name }}</span>
              <span class="text-xs text-gray-500">{{ institution.alumni_count }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Circles Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Circles
        </label>
        <div class="circles-filter">
          <div class="max-h-48 overflow-y-auto space-y-2">
            <label
              v-for="circle in filters.circles?.slice(0, 10) || []"
              :key="circle.id"
              class="flex items-center"
            >
              <input
                v-model="localFilters.circles"
                :value="circle.id"
                @change="updateFilters"
                type="checkbox"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700 flex-1">{{ circle.name }}</span>
              <span class="text-xs text-gray-500">{{ circle.member_count }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Groups Filter -->
      <div class="filter-section">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Groups
        </label>
        <div class="groups-filter">
          <div class="max-h-48 overflow-y-auto space-y-2">
            <label
              v-for="group in filters.groups?.slice(0, 10) || []"
              :key="group.id"
              class="flex items-center"
            >
              <input
                v-model="localFilters.groups"
                :value="group.id"
                @change="updateFilters"
                type="checkbox"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700 flex-1">{{ group.name }}</span>
              <span class="text-xs text-gray-500">{{ group.member_count }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
import { debounce } from 'lodash'

export default {
  name: 'DirectoryFilters',
  props: {
    filters: {
      type: Object,
      default: () => ({})
    },
    activeFilters: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update-filters', 'clear-filters'],
  setup(props, { emit }) {
    // Local filter state
    const localFilters = reactive({
      graduation_year_from: props.activeFilters.graduation_year_from || null,
      graduation_year_to: props.activeFilters.graduation_year_to || null,
      location: props.activeFilters.location || '',
      industries: props.activeFilters.industries || [],
      company: props.activeFilters.company || '',
      skills: props.activeFilters.skills || [],
      institutions: props.activeFilters.institutions || [],
      circles: props.activeFilters.circles || [],
      groups: props.activeFilters.groups || []
    })

    // Search states
    const locationQuery = ref('')
    const companyQuery = ref('')
    const skillQuery = ref('')
    
    // Suggestion states
    const locationSuggestions = ref([])
    const companySuggestions = ref([])
    const skillSuggestions = ref([])
    
    // Dropdown visibility
    const showLocationSuggestions = ref(false)
    const showCompanySuggestions = ref(false)
    const showSkillSuggestions = ref(false)

    // Computed
    const hasActiveFilters = computed(() => {
      return Object.values(localFilters).some(value => {
        if (Array.isArray(value)) return value.length > 0
        return value !== null && value !== ''
      })
    })

    // Methods
    const updateFilters = () => {
      const cleanFilters = {}
      
      Object.entries(localFilters).forEach(([key, value]) => {
        if (Array.isArray(value) && value.length > 0) {
          cleanFilters[key] = value
        } else if (value !== null && value !== '') {
          cleanFilters[key] = value
        }
      })
      
      emit('update-filters', cleanFilters)
    }

    const searchLocations = debounce(async () => {
      if (locationQuery.value.length < 2) {
        locationSuggestions.value = []
        return
      }
      
      try {
        const response = await fetch(`/api/alumni/search?query=${encodeURIComponent(locationQuery.value)}&type=location`)
        const data = await response.json()
        locationSuggestions.value = data.data
      } catch (error) {
        console.error('Error searching locations:', error)
      }
    }, 300)

    const searchCompanies = debounce(async () => {
      if (companyQuery.value.length < 2) {
        companySuggestions.value = []
        return
      }
      
      try {
        const response = await fetch(`/api/alumni/search?query=${encodeURIComponent(companyQuery.value)}&type=company`)
        const data = await response.json()
        companySuggestions.value = data.data
      } catch (error) {
        console.error('Error searching companies:', error)
      }
    }, 300)

    const searchSkills = debounce(async () => {
      if (skillQuery.value.length < 2) {
        skillSuggestions.value = []
        return
      }
      
      try {
        const response = await fetch(`/api/alumni/search?query=${encodeURIComponent(skillQuery.value)}&type=skill`)
        const data = await response.json()
        skillSuggestions.value = data.data.filter(skill => 
          !localFilters.skills.includes(skill.value)
        )
      } catch (error) {
        console.error('Error searching skills:', error)
      }
    }, 300)

    const selectLocation = (location) => {
      localFilters.location = location
      locationQuery.value = location
      showLocationSuggestions.value = false
      updateFilters()
    }

    const selectCompany = (company) => {
      localFilters.company = company
      companyQuery.value = company
      showCompanySuggestions.value = false
      updateFilters()
    }

    const addSkill = (skill) => {
      if (!localFilters.skills.includes(skill)) {
        localFilters.skills.push(skill)
        updateFilters()
      }
      skillQuery.value = ''
      showSkillSuggestions.value = false
    }

    const removeSkill = (skill) => {
      const index = localFilters.skills.indexOf(skill)
      if (index > -1) {
        localFilters.skills.splice(index, 1)
        updateFilters()
      }
    }

    // Close dropdowns when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.location-filter')) {
        showLocationSuggestions.value = false
      }
      if (!event.target.closest('.company-filter')) {
        showCompanySuggestions.value = false
      }
      if (!event.target.closest('.skill-search')) {
        showSkillSuggestions.value = false
      }
    }

    // Watch for changes in active filters prop
    watch(() => props.activeFilters, (newFilters) => {
      Object.assign(localFilters, {
        graduation_year_from: newFilters.graduation_year_from || null,
        graduation_year_to: newFilters.graduation_year_to || null,
        location: newFilters.location || '',
        industries: newFilters.industries || [],
        company: newFilters.company || '',
        skills: newFilters.skills || [],
        institutions: newFilters.institutions || [],
        circles: newFilters.circles || [],
        groups: newFilters.groups || []
      })
    }, { deep: true })

    // Add event listener for clicking outside
    document.addEventListener('click', handleClickOutside)

    return {
      localFilters,
      locationQuery,
      companyQuery,
      skillQuery,
      locationSuggestions,
      companySuggestions,
      skillSuggestions,
      showLocationSuggestions,
      showCompanySuggestions,
      showSkillSuggestions,
      hasActiveFilters,
      updateFilters,
      searchLocations,
      searchCompanies,
      searchSkills,
      selectLocation,
      selectCompany,
      addSkill,
      removeSkill
    }
  }
}
</script>

<style scoped>
.range-slider-container input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
  cursor: pointer;
}

.range-slider-container input[type="range"]::-webkit-slider-track {
  background: #e5e7eb;
  height: 8px;
  border-radius: 4px;
}

.range-slider-container input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  background: #3b82f6;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  cursor: pointer;
}

.range-slider-container input[type="range"]::-moz-range-track {
  background: #e5e7eb;
  height: 8px;
  border-radius: 4px;
  border: none;
}

.range-slider-container input[type="range"]::-moz-range-thumb {
  background: #3b82f6;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}
</style>