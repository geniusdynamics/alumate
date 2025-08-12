<template>
    <div class="mobile-filter-interface">
        <!-- Filter Toggle Button -->
        <button
            @click="toggleFilters"
            class="filter-mobile-toggle w-full"
            :aria-expanded="isExpanded"
            :aria-controls="filterId"
            :aria-label="`${isExpanded ? 'Hide' : 'Show'} filters${activeFiltersCount > 0 ? ` (${activeFiltersCount} active)` : ''}`"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                    <span>Filters</span>
                    <span 
                        v-if="activeFiltersCount > 0" 
                        class="bg-blue-600 text-white text-xs rounded-full px-2 py-0.5"
                        :aria-label="`${activeFiltersCount} active filters`"
                    >
                        {{ activeFiltersCount }}
                    </span>
                </div>
                <ChevronDownIcon 
                    class="h-4 w-4 transition-transform duration-200" 
                    :class="{ 'rotate-180': isExpanded }" 
                    aria-hidden="true"
                />
            </div>
        </button>

        <!-- Filter Panel -->
        <div
            v-if="isExpanded"
            class="filter-mobile-panel"
            :id="filterId"
            role="region"
            aria-label="Filter options"
        >
            <div class="p-4 space-y-6">
                <!-- Quick Filter Chips -->
                <div v-if="quickFilters.length > 0">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Quick Filters</h3>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="filter in quickFilters"
                            :key="filter.id"
                            @click="toggleQuickFilter(filter)"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full border transition-colors touch-target"
                            :class="isQuickFilterActive(filter)
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        >
                            <component v-if="filter.icon" :is="filter.icon" class="h-4 w-4 mr-1.5" />
                            {{ filter.label }}
                        </button>
                    </div>
                </div>

                <!-- Category Filters -->
                <div v-if="showCategoryFilter">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Categories</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="category in categories"
                            :key="category.value"
                            @click="toggleCategory(category.value)"
                            class="flex items-center justify-center p-3 text-sm border rounded-lg transition-colors touch-target"
                            :class="selectedCategories.includes(category.value)
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        >
                            <component :is="category.icon" class="h-4 w-4 mr-2" />
                            {{ category.label }}
                        </button>
                    </div>
                </div>

                <!-- Location Filter -->
                <div v-if="showLocationFilter">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Location</h3>
                    <div class="space-y-3">
                        <input
                            v-model="locationQuery"
                            type="text"
                            placeholder="Enter city, state, or country"
                            class="input-mobile"
                            @input="handleLocationInput"
                            :id="`location-input-${filterId}`"
                            :aria-describedby="locationSuggestions.length > 0 ? `location-suggestions-${filterId}` : undefined"
                            aria-label="Search for location"
                            autocomplete="off"
                            role="combobox"
                            :aria-expanded="locationSuggestions.length > 0"
                            :aria-owns="locationSuggestions.length > 0 ? `location-suggestions-${filterId}` : undefined"
                        />
                        
                        <!-- Location Suggestions -->
                        <div 
                            v-if="locationSuggestions.length > 0" 
                            class="space-y-1"
                            :id="`location-suggestions-${filterId}`"
                            role="listbox"
                            aria-label="Location suggestions"
                        >
                            <button
                                v-for="location in locationSuggestions"
                                :key="location.id"
                                @click="selectLocation(location)"
                                class="w-full flex items-center p-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                <MapPinIcon class="h-4 w-4 text-gray-400 mr-3" />
                                <div class="flex-1">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ location.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ location.region }}</div>
                                </div>
                            </button>
                        </div>

                        <!-- Selected Locations -->
                        <div v-if="selectedLocations.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="location in selectedLocations"
                                :key="location.id"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 rounded-full"
                            >
                                {{ location.name }}
                                <button
                                    @click="removeLocation(location.id)"
                                    class="ml-1.5 p-0.5 hover:bg-blue-200 dark:hover:bg-blue-800 rounded-full"
                                >
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div v-if="showDateFilter">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Date Range</h3>
                    <div class="space-y-3">
                        <!-- Quick Date Options -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="dateOption in dateOptions"
                                :key="dateOption.value"
                                @click="selectDateOption(dateOption)"
                                class="p-2 text-sm border rounded-lg transition-colors touch-target"
                                :class="selectedDateOption === dateOption.value
                                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'"
                            >
                                {{ dateOption.label }}
                            </button>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">From</label>
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    class="input-mobile text-sm"
                                    @change="handleDateChange"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">To</label>
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    class="input-mobile text-sm"
                                    @change="handleDateChange"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Range Filters (Salary, Experience, etc.) -->
                <div v-if="rangeFilters.length > 0">
                    <div v-for="rangeFilter in rangeFilters" :key="rangeFilter.key" class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ rangeFilter.label }}</h3>
                        <div class="space-y-2">
                            <!-- Range Slider -->
                            <div class="px-2">
                                <input
                                    :value="rangeFilter.value[0]"
                                    type="range"
                                    :min="rangeFilter.min"
                                    :max="rangeFilter.max"
                                    :step="rangeFilter.step"
                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                                    @input="updateRangeMin(rangeFilter.key, $event.target.value)"
                                />
                                <input
                                    :value="rangeFilter.value[1]"
                                    type="range"
                                    :min="rangeFilter.min"
                                    :max="rangeFilter.max"
                                    :step="rangeFilter.step"
                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer -mt-2"
                                    @input="updateRangeMax(rangeFilter.key, $event.target.value)"
                                />
                            </div>
                            
                            <!-- Range Values -->
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>{{ formatRangeValue(rangeFilter, rangeFilter.value[0]) }}</span>
                                <span>{{ formatRangeValue(rangeFilter, rangeFilter.value[1]) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Filters -->
                <div v-if="customFilters.length > 0">
                    <div v-for="customFilter in customFilters" :key="customFilter.key" class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ customFilter.label }}</h3>
                        
                        <!-- Multi-select -->
                        <div v-if="customFilter.type === 'multiselect'" class="space-y-2">
                            <button
                                v-for="option in customFilter.options"
                                :key="option.value"
                                @click="toggleCustomFilterOption(customFilter.key, option.value)"
                                class="w-full flex items-center justify-between p-3 text-left border rounded-lg transition-colors touch-target"
                                :class="isCustomFilterOptionSelected(customFilter.key, option.value)
                                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'"
                            >
                                <span>{{ option.label }}</span>
                                <CheckIcon v-if="isCustomFilterOptionSelected(customFilter.key, option.value)" class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Single select -->
                        <div v-else-if="customFilter.type === 'select'" class="space-y-1">
                            <button
                                v-for="option in customFilter.options"
                                :key="option.value"
                                @click="selectCustomFilterOption(customFilter.key, option.value)"
                                class="w-full flex items-center justify-between p-3 text-left border rounded-lg transition-colors touch-target"
                                :class="customFilter.value === option.value
                                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'"
                            >
                                <span>{{ option.label }}</span>
                                <div v-if="customFilter.value === option.value" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        @click="applyFilters"
                        class="btn-mobile-primary flex-1"
                        :disabled="!hasChanges"
                    >
                        Apply Filters
                        <span v-if="activeFiltersCount > 0" class="ml-1">({{ activeFiltersCount }})</span>
                    </button>
                    <button
                        @click="clearAllFilters"
                        class="btn-mobile-secondary px-4"
                        :disabled="activeFiltersCount === 0"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Backdrop -->
        <div
            v-if="isExpanded"
            class="fixed inset-0 bg-black bg-opacity-25 z-40"
            @click="closeFilters"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useDebouncedRef } from '@/composables/useDebounce'
import {
    FunnelIcon,
    ChevronDownIcon,
    XMarkIcon,
    MapPinIcon,
    CheckIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    AcademicCapIcon,
    BuildingOfficeIcon,
    ClockIcon,
    StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    showCategoryFilter: {
        type: Boolean,
        default: true
    },
    showLocationFilter: {
        type: Boolean,
        default: true
    },
    showDateFilter: {
        type: Boolean,
        default: true
    },
    categories: {
        type: Array,
        default: () => [
            { value: 'alumni', label: 'Alumni', icon: UsersIcon },
            { value: 'jobs', label: 'Jobs', icon: BriefcaseIcon },
            { value: 'events', label: 'Events', icon: CalendarIcon },
            { value: 'companies', label: 'Companies', icon: BuildingOfficeIcon }
        ]
    },
    quickFilters: {
        type: Array,
        default: () => [
            { id: 'recent', label: 'Recent', icon: ClockIcon },
            { id: 'featured', label: 'Featured', icon: StarIcon },
            { id: 'nearby', label: 'Nearby', icon: MapPinIcon }
        ]
    },
    rangeFilters: {
        type: Array,
        default: () => []
    },
    customFilters: {
        type: Array,
        default: () => []
    },
    initialFilters: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits([
    'filters-changed',
    'filters-applied',
    'filters-cleared'
])

const isExpanded = ref(false)
const hasChanges = ref(false)

// Generate unique ID for accessibility
const filterId = computed(() => `filter-panel-${Math.random().toString(36).substr(2, 9)}`)

// Filter states
const selectedCategories = ref([])
const selectedQuickFilters = ref([])
const locationQuery = ref('')
const locationSuggestions = ref([])
const selectedLocations = ref([])
const selectedDateOption = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const rangeValues = ref({})
const customFilterValues = ref({})

const debouncedLocationQuery = useDebouncedRef(locationQuery, 300)

const dateOptions = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' }
]

const activeFiltersCount = computed(() => {
    let count = 0
    count += selectedCategories.value.length
    count += selectedQuickFilters.value.length
    count += selectedLocations.value.length
    count += selectedDateOption.value ? 1 : 0
    count += (dateFrom.value || dateTo.value) ? 1 : 0
    count += Object.keys(rangeValues.value).length
    count += Object.values(customFilterValues.value).filter(v => 
        Array.isArray(v) ? v.length > 0 : v !== null && v !== ''
    ).length
    return count
})

// Watch for location query changes
watch(debouncedLocationQuery, async (newQuery) => {
    if (newQuery.trim()) {
        await fetchLocationSuggestions(newQuery)
    } else {
        locationSuggestions.value = []
    }
})

onMounted(() => {
    initializeFilters()
})

const initializeFilters = () => {
    // Initialize from props
    if (props.initialFilters.categories) {
        selectedCategories.value = [...props.initialFilters.categories]
    }
    if (props.initialFilters.locations) {
        selectedLocations.value = [...props.initialFilters.locations]
    }
    if (props.initialFilters.dateFrom) {
        dateFrom.value = props.initialFilters.dateFrom
    }
    if (props.initialFilters.dateTo) {
        dateTo.value = props.initialFilters.dateTo
    }
    
    // Initialize range filters
    props.rangeFilters.forEach(filter => {
        rangeValues.value[filter.key] = filter.value || [filter.min, filter.max]
    })
    
    // Initialize custom filters
    props.customFilters.forEach(filter => {
        customFilterValues.value[filter.key] = filter.value || (filter.type === 'multiselect' ? [] : null)
    })
}

const toggleFilters = () => {
    isExpanded.value = !isExpanded.value
}

const closeFilters = () => {
    isExpanded.value = false
}

const toggleQuickFilter = (filter) => {
    const index = selectedQuickFilters.value.indexOf(filter.id)
    if (index > -1) {
        selectedQuickFilters.value.splice(index, 1)
    } else {
        selectedQuickFilters.value.push(filter.id)
    }
    hasChanges.value = true
}

const isQuickFilterActive = (filter) => {
    return selectedQuickFilters.value.includes(filter.id)
}

const toggleCategory = (category) => {
    const index = selectedCategories.value.indexOf(category)
    if (index > -1) {
        selectedCategories.value.splice(index, 1)
    } else {
        selectedCategories.value.push(category)
    }
    hasChanges.value = true
}

const handleLocationInput = () => {
    hasChanges.value = true
}

const fetchLocationSuggestions = async (query) => {
    try {
        const response = await fetch('/api/locations/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ query, limit: 5 })
        })
        
        const data = await response.json()
        locationSuggestions.value = data.locations || []
    } catch (error) {
        console.error('Failed to fetch location suggestions:', error)
        locationSuggestions.value = []
    }
}

const selectLocation = (location) => {
    if (!selectedLocations.value.find(l => l.id === location.id)) {
        selectedLocations.value.push(location)
        hasChanges.value = true
    }
    locationQuery.value = ''
    locationSuggestions.value = []
}

const removeLocation = (locationId) => {
    selectedLocations.value = selectedLocations.value.filter(l => l.id !== locationId)
    hasChanges.value = true
}

const selectDateOption = (option) => {
    selectedDateOption.value = option.value
    
    // Clear custom date range when selecting quick option
    dateFrom.value = ''
    dateTo.value = ''
    
    hasChanges.value = true
}

const handleDateChange = () => {
    // Clear quick date option when setting custom dates
    selectedDateOption.value = ''
    hasChanges.value = true
}

const updateRangeMin = (key, value) => {
    if (!rangeValues.value[key]) {
        rangeValues.value[key] = [value, value]
    } else {
        rangeValues.value[key][0] = parseInt(value)
    }
    hasChanges.value = true
}

const updateRangeMax = (key, value) => {
    if (!rangeValues.value[key]) {
        rangeValues.value[key] = [value, value]
    } else {
        rangeValues.value[key][1] = parseInt(value)
    }
    hasChanges.value = true
}

const formatRangeValue = (filter, value) => {
    if (filter.formatter) {
        return filter.formatter(value)
    }
    return filter.prefix ? `${filter.prefix}${value}` : value
}

const toggleCustomFilterOption = (key, value) => {
    if (!customFilterValues.value[key]) {
        customFilterValues.value[key] = []
    }
    
    const index = customFilterValues.value[key].indexOf(value)
    if (index > -1) {
        customFilterValues.value[key].splice(index, 1)
    } else {
        customFilterValues.value[key].push(value)
    }
    hasChanges.value = true
}

const selectCustomFilterOption = (key, value) => {
    customFilterValues.value[key] = value
    hasChanges.value = true
}

const isCustomFilterOptionSelected = (key, value) => {
    const filterValue = customFilterValues.value[key]
    return Array.isArray(filterValue) ? filterValue.includes(value) : filterValue === value
}

const applyFilters = () => {
    const filters = {
        categories: selectedCategories.value,
        quickFilters: selectedQuickFilters.value,
        locations: selectedLocations.value,
        dateOption: selectedDateOption.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
        ranges: rangeValues.value,
        custom: customFilterValues.value
    }
    
    hasChanges.value = false
    isExpanded.value = false
    
    emit('filters-applied', filters)
    emit('filters-changed', filters)
}

const clearAllFilters = () => {
    selectedCategories.value = []
    selectedQuickFilters.value = []
    selectedLocations.value = []
    locationQuery.value = ''
    selectedDateOption.value = ''
    dateFrom.value = ''
    dateTo.value = ''
    rangeValues.value = {}
    customFilterValues.value = {}
    
    // Reset custom filters to default values
    props.customFilters.forEach(filter => {
        customFilterValues.value[filter.key] = filter.type === 'multiselect' ? [] : null
    })
    
    hasChanges.value = false
    
    emit('filters-cleared')
    emit('filters-changed', {
        categories: [],
        quickFilters: [],
        locations: [],
        dateOption: '',
        dateFrom: '',
        dateTo: '',
        ranges: {},
        custom: {}
    })
}

// Expose methods for parent components
defineExpose({
    open: () => { isExpanded.value = true },
    close: closeFilters,
    toggle: toggleFilters,
    clearFilters: clearAllFilters,
    applyFilters
})
</script>

<style scoped>
/* Range slider styling */
input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Filter panel animation */
.filter-mobile-panel {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Touch-friendly interactions */
@media (hover: hover) {
    .mobile-filter-interface button:hover {
        transform: translateY(-1px);
    }
}

.mobile-filter-interface button:active {
    transform: translateY(0);
}
</style>