<template>
    <div class="search-filters">
        <div class="filters-header">
            <h3 class="filters-title">Filters</h3>
            <button @click="$emit('clear-filters')" class="clear-filters-btn" :disabled="!hasActiveFilters">Clear All</button>
        </div>

        <div class="filters-grid">
            <!-- Content Types Filter -->
            <div class="filter-group">
                <label class="filter-label">Content Types</label>
                <div class="checkbox-group">
                    <label v-for="type in contentTypes" :key="type.value" class="checkbox-item">
                        <input v-model="localFilters.types" type="checkbox" :value="type.value" class="checkbox-input" @change="updateFilters" />
                        <span class="checkbox-label">{{ type.label }}</span>
                        <span v-if="getAggregationCount('types', type.value)" class="checkbox-count">
                            ({{ getAggregationCount('types', type.value) }})
                        </span>
                    </label>
                </div>
            </div>

            <!-- Location Filter -->
            <div class="filter-group">
                <label class="filter-label" for="location-filter">Location</label>
                <div class="filter-input-container">
                    <input
                        id="location-filter"
                        v-model="localFilters.location"
                        type="text"
                        placeholder="Enter location..."
                        class="filter-input"
                        @input="debouncedUpdate"
                        list="location-suggestions"
                    />
                    <datalist id="location-suggestions">
                        <option v-for="location in locationSuggestions" :key="location.key" :value="location.key">
                            {{ location.key }} ({{ location.doc_count }})
                        </option>
                    </datalist>
                </div>
            </div>

            <!-- Graduation Year Filter -->
            <div class="filter-group">
                <label class="filter-label" for="graduation-year-filter">Graduation Year</label>
                <select id="graduation-year-filter" v-model="localFilters.graduation_year" class="filter-select" @change="updateFilters">
                    <option value="">All Years</option>
                    <option v-for="year in graduationYearOptions" :key="year.key" :value="year.key">{{ year.key }} ({{ year.doc_count }})</option>
                </select>
            </div>

            <!-- Industry Filter -->
            <div class="filter-group">
                <label class="filter-label">Industries</label>
                <div class="multi-select-container">
                    <div class="selected-items" v-if="localFilters.industry.length > 0">
                        <span v-for="industry in localFilters.industry" :key="industry" class="selected-item">
                            {{ industry }}
                            <button @click="removeIndustry(industry)" class="remove-item-btn" :aria-label="`Remove ${industry}`">×</button>
                        </span>
                    </div>
                    <div class="industry-options">
                        <label
                            v-for="industry in industryOptions.slice(0, showAllIndustries ? undefined : 10)"
                            :key="industry.key"
                            class="checkbox-item"
                        >
                            <input
                                v-model="localFilters.industry"
                                type="checkbox"
                                :value="industry.key"
                                class="checkbox-input"
                                @change="updateFilters"
                            />
                            <span class="checkbox-label">{{ industry.key }}</span>
                            <span class="checkbox-count">({{ industry.doc_count }})</span>
                        </label>
                    </div>
                    <button v-if="industryOptions.length > 10" @click="showAllIndustries = !showAllIndustries" class="show-more-btn">
                        {{ showAllIndustries ? 'Show Less' : `Show ${industryOptions.length - 10} More` }}
                    </button>
                </div>
            </div>

            <!-- Skills Filter -->
            <div class="filter-group">
                <label class="filter-label">Skills</label>
                <div class="skills-input-container">
                    <input
                        v-model="skillsInput"
                        type="text"
                        placeholder="Type to search skills..."
                        class="filter-input"
                        @input="handleSkillsInput"
                        @keydown.enter="addSkill"
                        list="skills-suggestions"
                    />
                    <datalist id="skills-suggestions">
                        <option v-for="skill in skillsSuggestions" :key="skill.key" :value="skill.key">
                            {{ skill.key }} ({{ skill.doc_count }})
                        </option>
                    </datalist>
                </div>
                <div class="selected-items" v-if="localFilters.skills.length > 0">
                    <span v-for="skill in localFilters.skills" :key="skill" class="selected-item">
                        {{ skill }}
                        <button @click="removeSkill(skill)" class="remove-item-btn" :aria-label="`Remove ${skill}`">×</button>
                    </span>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="filter-group">
                <label class="filter-label">Date Range</label>
                <div class="date-range-container">
                    <div class="date-input-group">
                        <label for="date-from" class="date-label">From</label>
                        <input id="date-from" v-model="localFilters.date_range.from" type="date" class="date-input" @change="updateFilters" />
                    </div>
                    <div class="date-input-group">
                        <label for="date-to" class="date-label">To</label>
                        <input id="date-to" v-model="localFilters.date_range.to" type="date" class="date-input" @change="updateFilters" />
                    </div>
                </div>
                <div class="date-presets">
                    <button v-for="preset in datePresets" :key="preset.label" @click="applyDatePreset(preset)" class="date-preset-btn">
                        {{ preset.label }}
                    </button>
                </div>
            </div>

            <!-- School Filter -->
            <div class="filter-group" v-if="schoolOptions.length > 0">
                <label class="filter-label" for="school-filter">School</label>
                <select id="school-filter" v-model="localFilters.school" class="filter-select" @change="updateFilters">
                    <option value="">All Schools</option>
                    <option v-for="school in schoolOptions" :key="school.key" :value="school.key">{{ school.key }} ({{ school.doc_count }})</option>
                </select>
            </div>
        </div>

        <!-- Active Filters Summary -->
        <div v-if="hasActiveFilters" class="active-filters">
            <h4 class="active-filters-title">Active Filters:</h4>
            <div class="active-filters-list">
                <span v-for="filter in activeFiltersList" :key="filter.key" class="active-filter-item">
                    {{ filter.label }}: {{ filter.value }}
                    <button @click="removeFilter(filter.key)" class="remove-filter-btn" :aria-label="`Remove ${filter.label} filter`">×</button>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { debounce } from 'lodash-es';
import { computed, reactive, ref, watch } from 'vue';

interface SearchFilters {
    types: string[];
    location: string;
    graduation_year: string;
    industry: string[];
    skills: string[];
    date_range: {
        from: string;
        to: string;
    };
    school?: string;
}

interface AggregationBucket {
    key: string;
    doc_count: number;
}

const props = defineProps<{
    filters: SearchFilters;
    aggregations: Record<string, { buckets: AggregationBucket[] }>;
}>();

const emit = defineEmits<{
    'update:filters': [filters: Partial<SearchFilters>];
    'clear-filters': [];
}>();

// Local state
const localFilters = reactive<SearchFilters>({ ...props.filters });
const showAllIndustries = ref(false);
const skillsInput = ref('');

// Content types configuration
const contentTypes = [
    { value: 'user', label: 'Alumni' },
    { value: 'post', label: 'Posts' },
    { value: 'job', label: 'Jobs' },
    { value: 'event', label: 'Events' },
];

// Date presets
const datePresets = [
    { label: 'Last Week', days: 7 },
    { label: 'Last Month', days: 30 },
    { label: 'Last 3 Months', days: 90 },
    { label: 'Last Year', days: 365 },
];

// Computed properties
const locationSuggestions = computed(() => props.aggregations.locations?.buckets || []);

const graduationYearOptions = computed(() => (props.aggregations.graduation_years?.buckets || []).sort((a, b) => parseInt(b.key) - parseInt(a.key)));

const industryOptions = computed(() => (props.aggregations.industries?.buckets || []).sort((a, b) => b.doc_count - a.doc_count));

const skillsSuggestions = computed(() =>
    (props.aggregations.skills?.buckets || [])
        .filter((skill) => skill.key.toLowerCase().includes(skillsInput.value.toLowerCase()) && !localFilters.skills.includes(skill.key))
        .slice(0, 10),
);

const schoolOptions = computed(() => props.aggregations.schools?.buckets || []);

const hasActiveFilters = computed(() => {
    return (
        localFilters.types.length < contentTypes.length ||
        localFilters.location ||
        localFilters.graduation_year ||
        localFilters.industry.length > 0 ||
        localFilters.skills.length > 0 ||
        localFilters.date_range.from ||
        localFilters.date_range.to ||
        localFilters.school
    );
});

const activeFiltersList = computed(() => {
    const filters = [];

    if (localFilters.types.length < contentTypes.length) {
        filters.push({
            key: 'types',
            label: 'Content Types',
            value: localFilters.types.join(', '),
        });
    }

    if (localFilters.location) {
        filters.push({
            key: 'location',
            label: 'Location',
            value: localFilters.location,
        });
    }

    if (localFilters.graduation_year) {
        filters.push({
            key: 'graduation_year',
            label: 'Graduation Year',
            value: localFilters.graduation_year,
        });
    }

    if (localFilters.industry.length > 0) {
        filters.push({
            key: 'industry',
            label: 'Industries',
            value: localFilters.industry.join(', '),
        });
    }

    if (localFilters.skills.length > 0) {
        filters.push({
            key: 'skills',
            label: 'Skills',
            value: localFilters.skills.join(', '),
        });
    }

    if (localFilters.date_range.from || localFilters.date_range.to) {
        const from = localFilters.date_range.from || 'Beginning';
        const to = localFilters.date_range.to || 'Now';
        filters.push({
            key: 'date_range',
            label: 'Date Range',
            value: `${from} to ${to}`,
        });
    }

    if (localFilters.school) {
        filters.push({
            key: 'school',
            label: 'School',
            value: localFilters.school,
        });
    }

    return filters;
});

// Methods
const updateFilters = () => {
    emit('update:filters', { ...localFilters });
};

const debouncedUpdate = debounce(updateFilters, 300);

const getAggregationCount = (aggregationType: string, value: string): number => {
    const buckets = props.aggregations[aggregationType]?.buckets || [];
    const bucket = buckets.find((b) => b.key === value);
    return bucket?.doc_count || 0;
};

const removeIndustry = (industry: string) => {
    const index = localFilters.industry.indexOf(industry);
    if (index > -1) {
        localFilters.industry.splice(index, 1);
        updateFilters();
    }
};

const removeSkill = (skill: string) => {
    const index = localFilters.skills.indexOf(skill);
    if (index > -1) {
        localFilters.skills.splice(index, 1);
        updateFilters();
    }
};

const handleSkillsInput = debounce(() => {
    // Trigger suggestions update
}, 200);

const addSkill = () => {
    const skill = skillsInput.value.trim();
    if (skill && !localFilters.skills.includes(skill)) {
        localFilters.skills.push(skill);
        skillsInput.value = '';
        updateFilters();
    }
};

const applyDatePreset = (preset: { label: string; days: number }) => {
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - preset.days);

    localFilters.date_range.from = from.toISOString().split('T')[0];
    localFilters.date_range.to = to.toISOString().split('T')[0];
    updateFilters();
};

const removeFilter = (filterKey: string) => {
    switch (filterKey) {
        case 'types':
            localFilters.types = [...contentTypes.map((t) => t.value)];
            break;
        case 'location':
            localFilters.location = '';
            break;
        case 'graduation_year':
            localFilters.graduation_year = '';
            break;
        case 'industry':
            localFilters.industry = [];
            break;
        case 'skills':
            localFilters.skills = [];
            break;
        case 'date_range':
            localFilters.date_range = { from: '', to: '' };
            break;
        case 'school':
            localFilters.school = '';
            break;
    }
    updateFilters();
};

// Watch for prop changes
watch(
    () => props.filters,
    (newFilters) => {
        Object.assign(localFilters, newFilters);
    },
    { deep: true },
);
</script>

<style scoped>
.search-filters {
    @apply mb-6 rounded-lg border border-gray-200 bg-gray-50 p-6;
}

.filters-header {
    @apply mb-4 flex items-center justify-between;
}

.filters-title {
    @apply text-lg font-semibold text-gray-900;
}

.clear-filters-btn {
    @apply px-3 py-1 text-sm text-blue-600 hover:text-blue-800;
    @apply disabled:cursor-not-allowed disabled:text-gray-400;
}

.filters-grid {
    @apply grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3;
}

.filter-group {
    @apply space-y-2;
}

.filter-label {
    @apply block text-sm font-medium text-gray-700;
}

.filter-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2;
    @apply focus:border-transparent focus:ring-2 focus:ring-blue-500;
}

.filter-select {
    @apply w-full rounded-md border border-gray-300 px-3 py-2;
    @apply focus:border-transparent focus:ring-2 focus:ring-blue-500;
}

.checkbox-group {
    @apply space-y-2;
}

.checkbox-item {
    @apply flex cursor-pointer items-center space-x-2;
}

.checkbox-input {
    @apply rounded border-gray-300 text-blue-600;
    @apply focus:ring-2 focus:ring-blue-500;
}

.checkbox-label {
    @apply text-sm text-gray-700;
}

.checkbox-count {
    @apply text-xs text-gray-500;
}

.multi-select-container {
    @apply space-y-3;
}

.selected-items {
    @apply flex flex-wrap gap-2;
}

.selected-item {
    @apply inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-sm text-blue-800;
}

.remove-item-btn {
    @apply ml-1 font-bold text-blue-600 hover:text-blue-800;
}

.industry-options {
    @apply space-y-2;
}

.show-more-btn {
    @apply text-sm text-blue-600 hover:text-blue-800;
}

.skills-input-container {
    @apply relative;
}

.date-range-container {
    @apply grid grid-cols-2 gap-3;
}

.date-input-group {
    @apply space-y-1;
}

.date-label {
    @apply block text-xs text-gray-600;
}

.date-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm;
    @apply focus:border-transparent focus:ring-2 focus:ring-blue-500;
}

.date-presets {
    @apply mt-2 flex flex-wrap gap-2;
}

.date-preset-btn {
    @apply rounded bg-gray-200 px-2 py-1 text-xs text-gray-700;
    @apply transition-colors hover:bg-gray-300;
}

.active-filters {
    @apply mt-6 border-t border-gray-200 pt-4;
}

.active-filters-title {
    @apply mb-2 text-sm font-medium text-gray-700;
}

.active-filters-list {
    @apply flex flex-wrap gap-2;
}

.active-filter-item {
    @apply inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-700;
}

.remove-filter-btn {
    @apply ml-2 font-bold text-blue-500 hover:text-blue-700;
}
</style>
