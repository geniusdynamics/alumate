<template>
    <div class="template-library-modal">
        <div class="modal-overlay" @click="$emit('close')"></div>

        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="header-content">
                    <h2 class="modal-title">Template Library</h2>
                    <p class="modal-subtitle">Choose a template to start with or browse for inspiration</p>
                </div>
                <button @click="$emit('close')" class="close-btn" aria-label="Close template library">
                    <Icon name="x" class="h-6 w-6" />
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-controls">
                    <div class="search-input-container">
                        <Icon name="search" class="search-icon" />
                        <input v-model="searchQuery" type="text" placeholder="Search templates..." class="search-input" @input="handleSearch" />
                        <button v-if="searchQuery" @click="clearSearch" class="clear-search-btn" aria-label="Clear search">
                            <Icon name="x" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="filter-controls">
                        <!-- Category Filter -->
                        <select v-model="selectedCategory" @change="handleCategoryChange" class="filter-select">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }} ({{ category.count }})
                            </option>
                        </select>

                        <!-- Audience Filter -->
                        <select v-model="selectedAudience" @change="handleAudienceChange" class="filter-select">
                            <option value="">All Audiences</option>
                            <option value="individual">Individual Alumni</option>
                            <option value="institutional">Institutions</option>
                            <option value="employer">Employers</option>
                        </select>

                        <!-- Sort Options -->
                        <select v-model="sortBy" @change="handleSortChange" class="filter-select">
                            <option value="name">Name</option>
                            <option value="created_at">Newest</option>
                            <option value="usage_count">Most Popular</option>
                            <option value="conversion_rate">Best Converting</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filters -->
                <div v-if="hasActiveFilters" class="active-filters">
                    <span class="filter-label">Active filters:</span>
                    <div class="filter-tags">
                        <span v-if="selectedCategory" class="filter-tag">
                            Category: {{ getCategoryName(selectedCategory) }}
                            <button @click="selectedCategory = ''" class="remove-filter">
                                <Icon name="x" class="h-3 w-3" />
                            </button>
                        </span>
                        <span v-if="selectedAudience" class="filter-tag">
                            Audience: {{ selectedAudience }}
                            <button @click="selectedAudience = ''" class="remove-filter">
                                <Icon name="x" class="h-3 w-3" />
                            </button>
                        </span>
                        <button @click="clearAllFilters" class="clear-all-filters">Clear all</button>
                    </div>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="templates-section">
                <div v-if="isLoading" class="loading-state">
                    <Icon name="spinner" class="h-8 w-8 animate-spin" />
                    <p>Loading templates...</p>
                </div>

                <div v-else-if="filteredTemplates.length === 0" class="empty-state">
                    <Icon name="template" class="h-16 w-16 text-gray-400" />
                    <h3 class="empty-title">No templates found</h3>
                    <p class="empty-subtitle">
                        {{ searchQuery ? 'Try adjusting your search terms or filters' : 'No templates available with current filters' }}
                    </p>
                    <button @click="clearAllFilters" class="clear-filters-btn">Clear Filters</button>
                </div>

                <div v-else class="templates-grid">
                    <div
                        v-for="template in filteredTemplates"
                        :key="template.id"
                        class="template-card"
                        @click="selectTemplate(template)"
                        :class="{ selected: selectedTemplateId === template.id }"
                    >
                        <!-- Template Preview -->
                        <div class="template-preview">
                            <img
                                v-if="template.preview_image"
                                :src="template.preview_image"
                                :alt="template.name"
                                class="preview-image"
                                @error="handleImageError"
                            />
                            <div v-else class="preview-placeholder">
                                <Icon name="template" class="h-12 w-12" />
                            </div>

                            <!-- Template Actions -->
                            <div class="template-actions">
                                <button @click.stop="previewTemplate(template)" class="action-btn preview-btn" :title="`Preview ${template.name}`">
                                    <Icon name="eye" class="h-4 w-4" />
                                </button>
                                <button @click.stop="useTemplate(template)" class="action-btn use-btn" :title="`Use ${template.name}`">
                                    <Icon name="plus" class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Premium Badge -->
                            <div v-if="template.is_premium" class="premium-badge">
                                <Icon name="star" class="h-3 w-3" />
                                Premium
                            </div>
                        </div>

                        <!-- Template Info -->
                        <div class="template-info">
                            <h3 class="template-name">{{ template.name }}</h3>
                            <p class="template-description">{{ template.description }}</p>

                            <!-- Template Meta -->
                            <div class="template-meta">
                                <span class="category-tag">{{ template.category }}</span>
                                <span class="audience-tag">{{ template.audience_type }}</span>
                                <span class="usage-count">{{ template.usage_count || 0 }} uses</span>
                            </div>

                            <!-- Performance Metrics -->
                            <div v-if="template.performance_metrics" class="performance-metrics">
                                <div class="metric">
                                    <span class="metric-label">Conversion:</span>
                                    <span class="metric-value">{{ (template.performance_metrics.conversion_rate * 100).toFixed(1) }}%</span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Engagement:</span>
                                    <span class="metric-value">{{ (template.performance_metrics.engagement_rate * 100).toFixed(1) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Details Panel -->
            <div v-if="selectedTemplate" class="template-details">
                <div class="details-header">
                    <div class="template-info">
                        <h3>{{ selectedTemplate.name }}</h3>
                        <p>{{ selectedTemplate.description }}</p>
                    </div>
                    <button @click="selectedTemplate = null" class="close-details-btn">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="details-content">
                    <!-- Template Features -->
                    <div v-if="selectedTemplate.features" class="template-features">
                        <h4>Features</h4>
                        <ul class="features-list">
                            <li v-for="feature in selectedTemplate.features" :key="feature" class="feature-item">
                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                {{ feature }}
                            </li>
                        </ul>
                    </div>

                    <!-- Template Components -->
                    <div v-if="selectedTemplate.components" class="template-components">
                        <h4>Included Components</h4>
                        <div class="components-list">
                            <span v-for="component in selectedTemplate.components" :key="component" class="component-tag">
                                {{ component }}
                            </span>
                        </div>
                    </div>

                    <!-- Template Variants -->
                    <div v-if="selectedTemplate.variants" class="template-variants">
                        <h4>Available Variants</h4>
                        <div class="variants-grid">
                            <div
                                v-for="variant in selectedTemplate.variants"
                                :key="variant.id"
                                class="variant-card"
                                @click="selectVariant(variant)"
                                :class="{ selected: selectedVariantId === variant.id }"
                            >
                                <img v-if="variant.preview_image" :src="variant.preview_image" :alt="variant.name" class="variant-preview" />
                                <div class="variant-info">
                                    <span class="variant-name">{{ variant.name }}</span>
                                    <span class="variant-description">{{ variant.description }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="details-actions">
                        <button @click="previewTemplate(selectedTemplate)" class="action-btn secondary-btn">
                            <Icon name="eye" class="h-4 w-4" />
                            Preview
                        </button>
                        <button @click="useTemplate(selectedTemplate)" class="action-btn primary-btn">
                            <Icon name="plus" class="h-4 w-4" />
                            Use This Template
                        </button>
                    </div>
                </div>
            </div>

            <!-- Template Preview Modal -->
            <TemplatePreviewModal
                v-if="showPreviewModal"
                :template="previewingTemplate"
                :variant="selectedVariant"
                @close="closePreview"
                @use="useTemplate"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useTemplateLibrary } from '../../Composables/useTemplateLibrary';
import { useToast } from '../../Composables/useToast';
import Icon from '../ui/Icon.vue';
import TemplatePreviewModal from './TemplatePreviewModal.vue';

// Emits
const emit = defineEmits<{
    close: [];
    templateSelected: [template: any];
    templateApplied: [template: any];
}>();

// Composables
const { showToast } = useToast();
const {
    templates,
    categories,
    isLoading,
    searchQuery,
    selectedCategory,
    selectedAudience,
    sortBy,
    loadTemplates,
    searchTemplates,
    getTemplatesByCategory,
    getTemplatesByAudience,
} = useTemplateLibrary();

// State
const selectedTemplateId = ref<string | null>(null);
const selectedTemplate = ref<any>(null);
const selectedVariantId = ref<string | null>(null);
const selectedVariant = ref<any>(null);
const showPreviewModal = ref(false);
const previewingTemplate = ref<any>(null);

// Computed
const filteredTemplates = computed(() => {
    let filtered = templates.value;

    // Apply filters
    if (selectedCategory.value) {
        filtered = filtered.filter((t) => t.category === selectedCategory.value);
    }

    if (selectedAudience.value) {
        filtered = filtered.filter((t) => t.audience_type === selectedAudience.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (t) => t.name.toLowerCase().includes(query) || t.description.toLowerCase().includes(query) || t.category.toLowerCase().includes(query),
        );
    }

    // Apply sorting
    return filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'name':
                return a.name.localeCompare(b.name);
            case 'created_at':
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            case 'usage_count':
                return (b.usage_count || 0) - (a.usage_count || 0);
            case 'conversion_rate':
                return (b.performance_metrics?.conversion_rate || 0) - (a.performance_metrics?.conversion_rate || 0);
            default:
                return 0;
        }
    });
});

const hasActiveFilters = computed(() => {
    return selectedCategory.value || selectedAudience.value || searchQuery.value;
});

// Methods
const handleSearch = async () => {
    if (searchQuery.value.length > 2) {
        await searchTemplates(searchQuery.value);
    } else if (searchQuery.value.length === 0) {
        await loadTemplates();
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    loadTemplates();
};

const handleCategoryChange = async () => {
    if (selectedCategory.value) {
        await getTemplatesByCategory(selectedCategory.value);
    } else {
        await loadTemplates();
    }
};

const handleAudienceChange = async () => {
    if (selectedAudience.value) {
        await getTemplatesByAudience(selectedAudience.value);
    } else {
        await loadTemplates();
    }
};

const handleSortChange = () => {
    // Sorting is handled in computed property
};

const clearAllFilters = () => {
    selectedCategory.value = '';
    selectedAudience.value = '';
    searchQuery.value = '';
    loadTemplates();
};

const getCategoryName = (categoryId: string) => {
    const category = categories.value.find((c) => c.id === categoryId);
    return category?.name || categoryId;
};

const selectTemplate = (template: any) => {
    selectedTemplateId.value = template.id;
    selectedTemplate.value = template;
    selectedVariantId.value = null;
    selectedVariant.value = null;
    emit('templateSelected', template);
};

const selectVariant = (variant: any) => {
    selectedVariantId.value = variant.id;
    selectedVariant.value = variant;
};

const previewTemplate = (template: any) => {
    previewingTemplate.value = template;
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewingTemplate.value = null;
};

const useTemplate = async (template: any) => {
    try {
        const templateToUse = selectedVariant.value || template;

        // Track template usage
        await trackTemplateUsage(template.id);

        emit('templateApplied', templateToUse);
        showToast(`${template.name} template applied`, 'success');
    } catch (error) {
        console.error('Failed to apply template:', error);
        showToast('Failed to apply template', 'error');
    }
};

const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
};

const trackTemplateUsage = async (templateId: string) => {
    try {
        await fetch(`/api/templates/${templateId}/track-usage`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
    } catch (error) {
        console.error('Failed to track template usage:', error);
    }
};

// Lifecycle
onMounted(async () => {
    await loadTemplates();
});
</script>

<style scoped>
.template-library-modal {
    @apply fixed inset-0 z-50 flex items-center justify-center;
}

.modal-overlay {
    @apply absolute inset-0 bg-black bg-opacity-50;
}

.modal-content {
    @apply relative mx-4 flex max-h-[90vh] w-full max-w-7xl flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800;
}

.modal-header {
    @apply flex items-start justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.header-content {
    @apply flex-1;
}

.modal-title {
    @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.modal-subtitle {
    @apply mt-1 text-gray-600 dark:text-gray-400;
}

.close-btn {
    @apply rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300;
}

.search-section {
    @apply border-b border-gray-200 p-6 dark:border-gray-700;
}

.search-controls {
    @apply mb-4 flex flex-col gap-4 lg:flex-row;
}

.search-input-container {
    @apply relative flex-1;
}

.search-icon {
    @apply absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 transform text-gray-400;
}

.search-input {
    @apply w-full rounded-lg border border-gray-300 bg-white py-3 pl-10 pr-10 text-gray-900 placeholder-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400;
}

.clear-search-btn {
    @apply absolute right-3 top-1/2 -translate-y-1/2 transform text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.filter-controls {
    @apply flex gap-3;
}

.filter-select {
    @apply rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.active-filters {
    @apply flex flex-wrap items-center gap-3;
}

.filter-label {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.filter-tags {
    @apply flex flex-wrap items-center gap-2;
}

.filter-tag {
    @apply flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.remove-filter {
    @apply text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200;
}

.clear-all-filters {
    @apply text-sm text-gray-600 underline hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200;
}

.templates-section {
    @apply flex-1 overflow-y-auto p-6;
}

.loading-state {
    @apply flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-400;
}

.empty-state {
    @apply flex flex-col items-center justify-center py-16 text-center;
}

.empty-title {
    @apply mt-4 text-xl font-semibold text-gray-900 dark:text-white;
}

.empty-subtitle {
    @apply mb-4 mt-2 text-gray-600 dark:text-gray-400;
}

.clear-filters-btn {
    @apply rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700;
}

.templates-grid {
    @apply grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4;
}

.template-card {
    @apply cursor-pointer overflow-hidden rounded-lg border border-gray-200 bg-white transition-all hover:shadow-lg dark:border-gray-700 dark:bg-gray-800;
}

.template-card.selected {
    @apply border-blue-500 ring-2 ring-blue-500;
}

.template-preview {
    @apply relative aspect-video bg-gray-100 dark:bg-gray-700;
}

.preview-image {
    @apply h-full w-full object-cover;
}

.preview-placeholder {
    @apply flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500;
}

.template-actions {
    @apply absolute right-2 top-2 flex space-x-1 opacity-0 transition-opacity group-hover:opacity-100;
}

.template-card:hover .template-actions {
    @apply opacity-100;
}

.action-btn {
    @apply rounded-md border border-gray-200 bg-white p-2 text-gray-600 shadow-sm transition-colors hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white;
}

.premium-badge {
    @apply absolute left-2 top-2 flex items-center space-x-1 rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.template-info {
    @apply p-4;
}

.template-name {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.template-description {
    @apply mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400;
}

.template-meta {
    @apply mt-3 flex items-center space-x-2;
}

.category-tag,
.audience-tag {
    @apply rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400;
}

.usage-count {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.performance-metrics {
    @apply mt-2 flex space-x-4;
}

.metric {
    @apply flex items-center space-x-1 text-xs;
}

.metric-label {
    @apply text-gray-500 dark:text-gray-400;
}

.metric-value {
    @apply font-medium text-gray-700 dark:text-gray-300;
}

.template-details {
    @apply border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900;
}

.details-header {
    @apply flex items-start justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.details-content {
    @apply p-6;
}

.template-features h4,
.template-components h4,
.template-variants h4 {
    @apply mb-3 text-lg font-semibold text-gray-900 dark:text-white;
}

.features-list {
    @apply mb-6 space-y-2;
}

.feature-item {
    @apply flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300;
}

.components-list {
    @apply mb-6 flex flex-wrap gap-2;
}

.component-tag {
    @apply rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.variants-grid {
    @apply mb-6 grid grid-cols-2 gap-4;
}

.variant-card {
    @apply cursor-pointer overflow-hidden rounded-lg border border-gray-200 transition-shadow hover:shadow-md dark:border-gray-700;
}

.variant-card.selected {
    @apply border-blue-500 ring-2 ring-blue-500;
}

.variant-preview {
    @apply aspect-video w-full object-cover;
}

.variant-info {
    @apply p-3;
}

.variant-name {
    @apply text-sm font-medium text-gray-900 dark:text-white;
}

.variant-description {
    @apply mt-1 text-xs text-gray-600 dark:text-gray-400;
}

.details-actions {
    @apply flex space-x-3 border-t border-gray-200 pt-6 dark:border-gray-700;
}

.secondary-btn {
    @apply flex items-center space-x-2 rounded-md border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700;
}

.primary-btn {
    @apply flex items-center space-x-2 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700;
}
</style>
