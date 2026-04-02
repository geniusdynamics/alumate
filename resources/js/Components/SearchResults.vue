<template>
    <div class="search-results">
        <!-- Results Header -->
        <div class="results-header">
            <div class="results-info">
                <h2 class="results-title">
                    Search Results
                    <span v-if="!loading" class="results-count">({{ total.toLocaleString() }} found)</span>
                </h2>
                <div v-if="query || hasActiveFilters" class="search-summary">
                    <span v-if="query" class="search-query">
                        Searching for: <strong>"{{ query }}"</strong>
                    </span>
                    <span v-if="hasActiveFilters" class="active-filters-summary">
                        with {{ activeFiltersCount }} filter{{ activeFiltersCount !== 1 ? 's' : '' }} applied
                    </span>
                </div>
            </div>

            <div class="results-actions">
                <button @click="$emit('export')" class="export-button" :disabled="loading || total === 0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Export Results
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="loading-spinner">
                <svg class="h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>
            <p class="loading-text">Searching alumni...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="total === 0" class="no-results">
            <div class="no-results-icon">
                <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="no-results-title">No alumni found</h3>
            <p class="no-results-message">Try adjusting your search terms or filters to find more results.</p>
        </div>

        <!-- Results List -->
        <div v-else class="results-list">
            <div v-for="user in results" :key="user.id" class="result-item">
                <div class="result-avatar">
                    <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="avatar-image" />
                    <div v-else class="avatar-placeholder">
                        {{ getInitials(user.name) }}
                    </div>
                </div>

                <div class="result-content">
                    <div class="result-header">
                        <h3 class="result-name" v-html="highlightText(user.name, user.highlight?.name)"></h3>
                        <div class="result-score">
                            <span class="score-label">Match:</span>
                            <div class="score-bar">
                                <div class="score-fill" :style="{ width: `${Math.min(user.score * 10, 100)}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <div class="result-details">
                        <div v-if="user.title || user.company" class="result-job">
                            <span v-if="user.title" v-html="highlightText(user.title, user.highlight?.title)"></span>
                            <span v-if="user.title && user.company"> at </span>
                            <span v-if="user.company" v-html="highlightText(user.company, user.highlight?.company)"></span>
                        </div>

                        <div v-if="user.location" class="result-location">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ user.location }}
                        </div>

                        <div v-if="user.graduation_year" class="result-graduation">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                                />
                            </svg>
                            Class of {{ user.graduation_year }}
                        </div>

                        <div v-if="user.bio" class="result-bio">
                            <p v-html="highlightText(user.bio, user.highlight?.bio)"></p>
                        </div>

                        <div v-if="user.skills && user.skills.length > 0" class="result-skills">
                            <span
                                v-for="skill in user.skills.slice(0, 5)"
                                :key="skill"
                                class="skill-tag"
                                v-html="highlightText(skill, user.highlight?.skills)"
                            ></span>
                            <span v-if="user.skills.length > 5" class="more-skills"> +{{ user.skills.length - 5 }} more </span>
                        </div>
                    </div>
                </div>

                <div class="result-actions">
                    <button class="action-button primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                            />
                        </svg>
                        Connect
                    </button>
                    <button class="action-button secondary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        View Profile
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination">
            <button @click="$emit('page-change', currentPage - 1)" :disabled="currentPage === 1" class="pagination-button">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </button>

            <div class="pagination-info">Page {{ currentPage }} of {{ totalPages }}</div>

            <button @click="$emit('page-change', currentPage + 1)" :disabled="currentPage === totalPages" class="pagination-button">
                Next
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Props
const props = defineProps({
    results: {
        type: Array,
        default: () => [],
    },
    total: {
        type: Number,
        default: 0,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    query: {
        type: String,
        default: '',
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    currentPage: {
        type: Number,
        default: 1,
    },
    totalPages: {
        type: Number,
        default: 0,
    },
});

// Emits
const emit = defineEmits(['page-change', 'export']);

// Computed
const hasActiveFilters = computed(() => {
    return Object.values(props.filters).some((value) => {
        if (Array.isArray(value)) return value.length > 0;
        if (typeof value === 'object' && value !== null) {
            return Object.values(value).some((v) => v !== null && v !== '');
        }
        return value !== '' && value !== null;
    });
});

const activeFiltersCount = computed(() => {
    let count = 0;
    Object.values(props.filters).forEach((value) => {
        if (Array.isArray(value) && value.length > 0) count++;
        else if (value && typeof value === 'object' && Object.keys(value).length > 0) count++;
        else if (value && typeof value === 'string') count++;
    });
    return count;
});

// Methods
const getInitials = (name) => {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const highlightText = (text, highlight) => {
    if (!highlight || !highlight.length) {
        return text;
    }

    // Elasticsearch returns highlighted text in an array
    return highlight[0] || text;
};
</script>

<style scoped>
.search-results {
    @apply space-y-6;
}

.results-header {
    @apply flex items-start justify-between;
}

.results-title {
    @apply text-2xl font-bold text-gray-900;
}

.results-count {
    @apply text-lg font-normal text-gray-600;
}

.search-summary {
    @apply mt-2 space-x-2 text-sm text-gray-600;
}

.search-query {
    @apply inline;
}

.active-filters-summary {
    @apply inline;
}

.results-actions {
    @apply flex gap-2;
}

.export-button {
    @apply flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.loading-state {
    @apply flex flex-col items-center justify-center py-12;
}

.loading-spinner {
    @apply mb-4;
}

.loading-text {
    @apply text-gray-600;
}

.no-results {
    @apply py-12 text-center;
}

.no-results-icon {
    @apply mb-4;
}

.no-results-title {
    @apply mb-2 text-xl font-semibold text-gray-900;
}

.no-results-message {
    @apply text-gray-600;
}

.results-list {
    @apply space-y-4;
}

.result-item {
    @apply flex gap-4 rounded-lg border border-gray-200 bg-white p-6 transition-shadow hover:shadow-md;
}

.result-avatar {
    @apply flex-shrink-0;
}

.avatar-image {
    @apply h-16 w-16 rounded-full object-cover;
}

.avatar-placeholder {
    @apply flex h-16 w-16 items-center justify-center rounded-full bg-gray-300 font-semibold text-gray-600;
}

.result-content {
    @apply min-w-0 flex-1;
}

.result-header {
    @apply mb-2 flex items-start justify-between;
}

.result-name {
    @apply text-lg font-semibold text-gray-900;
}

.result-score {
    @apply flex items-center gap-2 text-sm text-gray-500;
}

.score-bar {
    @apply h-2 w-16 overflow-hidden rounded-full bg-gray-200;
}

.score-fill {
    @apply h-full bg-blue-500 transition-all duration-300;
}

.result-details {
    @apply space-y-2;
}

.result-job {
    @apply font-medium text-gray-700;
}

.result-location,
.result-graduation {
    @apply flex items-center gap-1 text-sm text-gray-600;
}

.result-bio {
    @apply line-clamp-2 text-sm text-gray-600;
}

.result-skills {
    @apply flex flex-wrap gap-2;
}

.skill-tag {
    @apply rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800;
}

.more-skills {
    @apply rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600;
}

.result-actions {
    @apply flex flex-col gap-2;
}

.action-button {
    @apply flex items-center gap-2 rounded-lg px-4 py-2 font-medium transition-colors;
}

.action-button.primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-button.secondary {
    @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
}

.pagination {
    @apply flex items-center justify-between;
}

.pagination-button {
    @apply flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.pagination-info {
    @apply text-sm text-gray-600;
}
</style>

