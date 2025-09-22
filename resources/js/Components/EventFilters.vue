<template>
    <div class="rounded-lg border bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Filters</h3>

        <!-- Search -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Search Events </label>
            <div class="relative">
                <input
                    v-model="localFilters.search"
                    type="text"
                    placeholder="Search by title, description, or venue..."
                    class="w-full rounded-md border border-gray-300 py-2 pl-10 pr-4 focus:border-blue-500 focus:ring-blue-500"
                    @input="debouncedUpdate"
                />
                <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            </div>
        </div>

        <!-- Event Type -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Event Type </label>
            <select
                v-model="localFilters.type"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                @change="updateFilters"
            >
                <option value="">All Types</option>
                <option value="networking">Networking</option>
                <option value="reunion">Reunion</option>
                <option value="webinar">Webinar</option>
                <option value="workshop">Workshop</option>
                <option value="social">Social</option>
                <option value="professional">Professional</option>
                <option value="fundraising">Fundraising</option>
                <option value="other">Other</option>
            </select>
        </div>

        <!-- Event Format -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Format </label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input
                        v-model="localFilters.format"
                        type="radio"
                        value=""
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateFilters"
                    />
                    <span class="ml-2 text-sm text-gray-700">All Formats</span>
                </label>
                <label class="flex items-center">
                    <input
                        v-model="localFilters.format"
                        type="radio"
                        value="in_person"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateFilters"
                    />
                    <span class="ml-2 text-sm text-gray-700">In Person</span>
                </label>
                <label class="flex items-center">
                    <input
                        v-model="localFilters.format"
                        type="radio"
                        value="virtual"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateFilters"
                    />
                    <span class="ml-2 text-sm text-gray-700">Virtual</span>
                </label>
                <label class="flex items-center">
                    <input
                        v-model="localFilters.format"
                        type="radio"
                        value="hybrid"
                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateFilters"
                    />
                    <span class="ml-2 text-sm text-gray-700">Hybrid</span>
                </label>
            </div>
        </div>

        <!-- Date Range -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> When </label>
            <select
                v-model="localFilters.date_range"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                @change="updateFilters"
            >
                <option value="">Any Time</option>
                <option value="today">Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="this_week">This Week</option>
                <option value="next_week">Next Week</option>
                <option value="this_month">This Month</option>
                <option value="next_month">Next Month</option>
            </select>
        </div>

        <!-- Location Filter -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Location </label>
            <div class="space-y-3">
                <input
                    v-model="locationSearch"
                    type="text"
                    placeholder="Enter city, state, or address..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                    @input="handleLocationSearch"
                />

                <!-- Location Suggestions -->
                <div v-if="locationSuggestions.length > 0" class="max-h-32 overflow-y-auto rounded-md border border-gray-200">
                    <button
                        v-for="suggestion in locationSuggestions"
                        :key="suggestion.place_id"
                        @click="selectLocation(suggestion)"
                        class="w-full border-b border-gray-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-gray-50"
                    >
                        {{ suggestion.description }}
                    </button>
                </div>

                <!-- Selected Location -->
                <div v-if="localFilters.location" class="flex items-center justify-between rounded-md bg-blue-50 p-2">
                    <span class="text-sm text-blue-800">{{ selectedLocationName }}</span>
                    <button @click="clearLocation" class="text-blue-600 hover:text-blue-800">
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>

                <!-- Radius Slider -->
                <div v-if="localFilters.location" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700"> Within {{ localFilters.radius }} miles </label>
                    <input
                        v-model="localFilters.radius"
                        type="range"
                        min="5"
                        max="100"
                        step="5"
                        class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200"
                        @input="updateFilters"
                    />
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>5 mi</span>
                        <span>100 mi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags Filter -->
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Tags </label>
            <div class="space-y-2">
                <input
                    v-model="tagInput"
                    type="text"
                    placeholder="Add tags..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                    @keydown.enter="addTag"
                />

                <!-- Selected Tags -->
                <div v-if="localFilters.tags.length > 0" class="flex flex-wrap gap-2">
                    <span
                        v-for="tag in localFilters.tags"
                        :key="tag"
                        class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                    >
                        {{ tag }}
                        <button @click="removeTag(tag)" class="ml-1 text-blue-600 hover:text-blue-800">
                            <XMarkIcon class="h-3 w-3" />
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Clear Filters -->
        <div class="border-t pt-4">
            <button
                @click="clearAllFilters"
                class="w-full rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
            >
                Clear All Filters
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { debounce } from 'lodash-es';
import { reactive, ref, watch } from 'vue';

interface LocationSuggestion {
    place_id: string;
    description: string;
    geometry: {
        location: {
            lat: number;
            lng: number;
        };
    };
}

interface Filters {
    type: string;
    format: string;
    date_range: string;
    location: { lat: number; lng: number } | null;
    radius: number;
    tags: string[];
    search: string;
}

interface Props {
    filters: Filters;
    loading?: boolean;
}

interface Emits {
    (e: 'update:filters', filters: Filters): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Local state
const localFilters = reactive<Filters>({ ...props.filters });
const locationSearch = ref('');
const selectedLocationName = ref('');
const locationSuggestions = ref<LocationSuggestion[]>([]);
const tagInput = ref('');

// Watch for external filter changes
watch(
    () => props.filters,
    (newFilters) => {
        Object.assign(localFilters, newFilters);
    },
    { deep: true },
);

// Debounced update function
const debouncedUpdate = debounce(() => {
    updateFilters();
}, 300);

// Methods
const updateFilters = () => {
    emit('update:filters', { ...localFilters });
};

const handleLocationSearch = debounce(async (event: Event) => {
    const query = (event.target as HTMLInputElement).value;

    if (query.length < 3) {
        locationSuggestions.value = [];
        return;
    }

    try {
        // This would integrate with Google Places API or similar
        // For now, we'll simulate with a mock response
        locationSuggestions.value = [
            {
                place_id: '1',
                description: `${query}, CA, USA`,
                geometry: {
                    location: { lat: 37.7749, lng: -122.4194 },
                },
            },
            {
                place_id: '2',
                description: `${query}, NY, USA`,
                geometry: {
                    location: { lat: 40.7128, lng: -74.006 },
                },
            },
        ];
    } catch (error) {
        console.error('Failed to fetch location suggestions:', error);
    }
}, 300);

const selectLocation = (suggestion: LocationSuggestion) => {
    localFilters.location = suggestion.geometry.location;
    selectedLocationName.value = suggestion.description;
    locationSearch.value = '';
    locationSuggestions.value = [];
    updateFilters();
};

const clearLocation = () => {
    localFilters.location = null;
    selectedLocationName.value = '';
    locationSearch.value = '';
    updateFilters();
};

const addTag = () => {
    const tag = tagInput.value.trim();
    if (tag && !localFilters.tags.includes(tag)) {
        localFilters.tags.push(tag);
        tagInput.value = '';
        updateFilters();
    }
};

const removeTag = (tag: string) => {
    const index = localFilters.tags.indexOf(tag);
    if (index > -1) {
        localFilters.tags.splice(index, 1);
        updateFilters();
    }
};

const clearAllFilters = () => {
    localFilters.type = '';
    localFilters.format = '';
    localFilters.date_range = '';
    localFilters.location = null;
    localFilters.radius = 50;
    localFilters.tags = [];
    localFilters.search = '';

    locationSearch.value = '';
    selectedLocationName.value = '';
    locationSuggestions.value = [];
    tagInput.value = '';

    updateFilters();
};
</script>
