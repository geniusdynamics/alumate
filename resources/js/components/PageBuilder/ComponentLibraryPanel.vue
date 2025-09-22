<template>
    <div class="component-library-panel">
        <!-- Panel Header -->
        <div class="panel-header">
            <div class="header-content">
                <h3 class="panel-title">Component Library</h3>
                <p class="panel-subtitle">Drag components to add them to your page</p>
            </div>
            <button @click="$emit('close')" class="close-btn" aria-label="Close component library">
                <Icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="search-section">
            <div class="search-input-container">
                <Icon name="search" class="search-icon" />
                <input v-model="searchQuery" type="text" placeholder="Search components..." class="search-input" @input="handleSearch" />
                <button v-if="searchQuery" @click="clearSearch" class="clear-search-btn" aria-label="Clear search">
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <!-- Category Filters -->
            <div class="category-filters">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="selectCategory(category.id)"
                    :class="['category-btn', { active: selectedCategory === category.id }]"
                    :title="category.description"
                >
                    <Icon :name="category.icon" class="h-4 w-4" />
                    <span>{{ category.name }}</span>
                    <span class="component-count">{{ category.count }}</span>
                </button>
            </div>
        </div>

        <!-- Component Grid -->
        <div class="components-section">
            <div v-if="isLoading" class="loading-state">
                <Icon name="spinner" class="h-6 w-6 animate-spin" />
                <p>Loading components...</p>
            </div>

            <div v-else-if="filteredComponents.length === 0" class="empty-state">
                <Icon name="component" class="h-12 w-12 text-gray-400" />
                <p class="empty-title">No components found</p>
                <p class="empty-subtitle">
                    {{ searchQuery ? 'Try adjusting your search terms' : 'No components available in this category' }}
                </p>
            </div>

            <div v-else class="components-grid">
                <div
                    v-for="component in filteredComponents"
                    :key="component.id"
                    class="component-card"
                    :draggable="true"
                    @dragstart="handleDragStart($event, component)"
                    @click="selectComponent(component)"
                    :class="{ selected: selectedComponentId === component.id }"
                >
                    <!-- Component Preview -->
                    <div class="component-preview">
                        <img
                            v-if="component.preview_image"
                            :src="component.preview_image"
                            :alt="component.name"
                            class="preview-image"
                            @error="handleImageError"
                        />
                        <div v-else class="preview-placeholder">
                            <Icon :name="component.icon || 'component'" class="h-8 w-8" />
                        </div>

                        <!-- Component Actions -->
                        <div class="component-actions">
                            <button @click.stop="previewComponent(component)" class="action-btn preview-btn" :title="`Preview ${component.name}`">
                                <Icon name="eye" class="h-4 w-4" />
                            </button>
                            <button @click.stop="addComponent(component)" class="action-btn add-btn" :title="`Add ${component.name}`">
                                <Icon name="plus" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Component Info -->
                    <div class="component-info">
                        <h4 class="component-name">{{ component.name }}</h4>
                        <p class="component-description">{{ component.description }}</p>

                        <!-- Component Meta -->
                        <div class="component-meta">
                            <span class="category-tag">{{ component.category }}</span>
                            <span v-if="component.is_premium" class="premium-tag">Premium</span>
                            <span class="usage-count">{{ component.usage_count || 0 }} uses</span>
                        </div>

                        <!-- Component Features -->
                        <div v-if="component.features" class="component-features">
                            <span v-for="feature in component.features.slice(0, 3)" :key="feature" class="feature-tag">
                                {{ feature }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Component Preview Modal -->
        <ComponentPreviewModal v-if="showPreviewModal" :component="previewingComponent" @close="closePreview" @add="addComponent" />

        <!-- Component Details Panel -->
        <div v-if="selectedComponent" class="component-details">
            <div class="details-header">
                <h4>{{ selectedComponent.name }}</h4>
                <button @click="selectedComponent = null" class="close-details-btn">
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="details-content">
                <p class="details-description">{{ selectedComponent.description }}</p>

                <!-- Configuration Options -->
                <div v-if="selectedComponent.config_schema" class="config-options">
                    <h5>Configuration Options</h5>
                    <div class="config-list">
                        <div v-for="option in selectedComponent.config_schema" :key="option.key" class="config-item">
                            <span class="config-name">{{ option.label }}</span>
                            <span class="config-type">{{ option.type }}</span>
                        </div>
                    </div>
                </div>

                <!-- Usage Examples -->
                <div v-if="selectedComponent.examples" class="usage-examples">
                    <h5>Usage Examples</h5>
                    <div class="examples-list">
                        <div v-for="example in selectedComponent.examples" :key="example.id" class="example-item">
                            <span class="example-name">{{ example.name }}</span>
                            <button @click="applyExample(example)" class="apply-example-btn">Apply</button>
                        </div>
                    </div>
                </div>

                <!-- Add Component Button -->
                <div class="details-actions">
                    <button @click="addComponent(selectedComponent)" class="add-component-btn">
                        <Icon name="plus" class="h-4 w-4" />
                        Add to Page
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useComponentLibrary } from '../../Composables/useComponentLibrary';
import { useToast } from '../../Composables/useToast';
import Icon from '../ui/Icon.vue';
import ComponentPreviewModal from './ComponentPreviewModal.vue';

// Emits
const emit = defineEmits<{
    close: [];
    componentSelected: [component: any];
    componentDragStart: [component: any];
}>();

// Composables
const { showToast } = useToast();
const { components, categories, isLoading, searchQuery, selectedCategory, loadComponents, searchComponents, getComponentsByCategory } =
    useComponentLibrary();

// State
const selectedComponentId = ref<string | null>(null);
const selectedComponent = ref<any>(null);
const showPreviewModal = ref(false);
const previewingComponent = ref<any>(null);

// Computed
const filteredComponents = computed(() => {
    let filtered = components.value;

    // Filter by category
    if (selectedCategory.value && selectedCategory.value !== 'all') {
        filtered = filtered.filter((c) => c.category === selectedCategory.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (c) => c.name.toLowerCase().includes(query) || c.description.toLowerCase().includes(query) || c.category.toLowerCase().includes(query),
        );
    }

    return filtered;
});

// Methods
const handleSearch = async () => {
    if (searchQuery.value.length > 2) {
        await searchComponents(searchQuery.value);
    } else if (searchQuery.value.length === 0) {
        await loadComponents();
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    loadComponents();
};

const selectCategory = async (categoryId: string) => {
    selectedCategory.value = categoryId;
    if (categoryId === 'all') {
        await loadComponents();
    } else {
        await getComponentsByCategory(categoryId);
    }
};

const selectComponent = (component: any) => {
    selectedComponentId.value = component.id;
    selectedComponent.value = component;
    emit('componentSelected', component);
};

const handleDragStart = (event: DragEvent, component: any) => {
    if (!event.dataTransfer) return;

    event.dataTransfer.setData('application/json', JSON.stringify(component));
    event.dataTransfer.effectAllowed = 'copy';

    emit('componentDragStart', component);
};

const addComponent = (component: any) => {
    try {
        // Emit component selection for the page builder to handle
        emit('componentSelected', component);
        showToast(`${component.name} added to page`, 'success');

        // Track usage
        trackComponentUsage(component.id);
    } catch (error) {
        console.error('Failed to add component:', error);
        showToast('Failed to add component', 'error');
    }
};

const previewComponent = (component: any) => {
    previewingComponent.value = component;
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewingComponent.value = null;
};

const applyExample = (example: any) => {
    if (selectedComponent.value) {
        const componentWithExample = {
            ...selectedComponent.value,
            config: example.config,
        };
        addComponent(componentWithExample);
    }
};

const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
};

const trackComponentUsage = async (componentId: string) => {
    try {
        await fetch(`/api/components/${componentId}/track-usage`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
    } catch (error) {
        console.error('Failed to track component usage:', error);
    }
};

// Lifecycle
onMounted(async () => {
    await loadComponents();
});

// Watch for category changes to update counts
watch(
    components,
    () => {
        // Update category counts
        categories.value.forEach((category) => {
            if (category.id === 'all') {
                category.count = components.value.length;
            } else {
                category.count = components.value.filter((c) => c.category === category.id).length;
            }
        });
    },
    { deep: true },
);
</script>

<style scoped>
.component-library-panel {
    @apply flex h-full flex-col bg-white dark:bg-gray-800;
}

.panel-header {
    @apply flex items-start justify-between border-b border-gray-200 p-4 dark:border-gray-700;
}

.header-content {
    @apply flex-1;
}

.panel-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.panel-subtitle {
    @apply mt-1 text-sm text-gray-500 dark:text-gray-400;
}

.close-btn {
    @apply rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300;
}

.search-section {
    @apply border-b border-gray-200 p-4 dark:border-gray-700;
}

.search-input-container {
    @apply relative mb-4;
}

.search-icon {
    @apply absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-gray-400;
}

.search-input {
    @apply w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-10 text-gray-900 placeholder-gray-500 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400;
}

.clear-search-btn {
    @apply absolute right-3 top-1/2 -translate-y-1/2 transform text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.category-filters {
    @apply flex flex-wrap gap-2;
}

.category-btn {
    @apply flex items-center space-x-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.category-btn.active {
    @apply border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-300;
}

.component-count {
    @apply rounded-full bg-gray-200 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-600 dark:text-gray-400;
}

.components-section {
    @apply flex-1 overflow-y-auto p-4;
}

.loading-state {
    @apply flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400;
}

.empty-state {
    @apply flex flex-col items-center justify-center py-12 text-center;
}

.empty-title {
    @apply mt-4 text-lg font-medium text-gray-900 dark:text-white;
}

.empty-subtitle {
    @apply mt-2 text-sm text-gray-500 dark:text-gray-400;
}

.components-grid {
    @apply grid grid-cols-1 gap-4;
}

.component-card {
    @apply cursor-pointer overflow-hidden rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800;
}

.component-card.selected {
    @apply border-blue-500 ring-2 ring-blue-500;
}

.component-preview {
    @apply relative aspect-video bg-gray-100 dark:bg-gray-700;
}

.preview-image {
    @apply h-full w-full object-cover;
}

.preview-placeholder {
    @apply flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500;
}

.component-actions {
    @apply absolute right-2 top-2 flex space-x-1 opacity-0 transition-opacity group-hover:opacity-100;
}

.component-card:hover .component-actions {
    @apply opacity-100;
}

.action-btn {
    @apply rounded-md border border-gray-200 bg-white p-1.5 text-gray-600 shadow-sm hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white;
}

.component-info {
    @apply p-3;
}

.component-name {
    @apply text-sm font-medium text-gray-900 dark:text-white;
}

.component-description {
    @apply mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400;
}

.component-meta {
    @apply mt-2 flex items-center space-x-2;
}

.category-tag {
    @apply rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400;
}

.premium-tag {
    @apply rounded bg-yellow-100 px-2 py-1 text-xs text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300;
}

.usage-count {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.component-features {
    @apply mt-2 flex flex-wrap gap-1;
}

.feature-tag {
    @apply rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300;
}

.component-details {
    @apply border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900;
}

.details-header {
    @apply flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700;
}

.details-content {
    @apply p-4;
}

.details-description {
    @apply mb-4 text-sm text-gray-600 dark:text-gray-400;
}

.config-options h5,
.usage-examples h5 {
    @apply mb-2 text-sm font-medium text-gray-900 dark:text-white;
}

.config-list,
.examples-list {
    @apply mb-4 space-y-2;
}

.config-item,
.example-item {
    @apply flex items-center justify-between text-sm;
}

.config-name,
.example-name {
    @apply text-gray-700 dark:text-gray-300;
}

.config-type {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.apply-example-btn {
    @apply text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300;
}

.details-actions {
    @apply border-t border-gray-200 pt-4 dark:border-gray-700;
}

.add-component-btn {
    @apply flex w-full items-center justify-center space-x-2 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700;
}
</style>
