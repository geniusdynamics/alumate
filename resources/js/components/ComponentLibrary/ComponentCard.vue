<template>
    <div
        :class="cardClasses"
        :draggable="true"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        role="article"
        :aria-labelledby="`component-${component.id}-title`"
        :aria-describedby="`component-${component.id}-description`"
    >
        <!-- Component Preview/Thumbnail -->
        <div class="component-card__preview">
            <div
                class="group relative aspect-video cursor-pointer overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                @click="handlePreview"
                :aria-label="`Preview ${component.name} component`"
                role="button"
                tabindex="0"
                @keydown.enter="handlePreview"
                @keydown.space.prevent="handlePreview"
            >
                <!-- Thumbnail Image -->
                <img
                    v-if="thumbnailUrl"
                    :src="thumbnailUrl"
                    :alt="`${component.name} component preview`"
                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                    loading="lazy"
                />

                <!-- Fallback Icon -->
                <div v-else class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-500">
                    <Icon :name="getCategoryIcon(component.category)" class="h-12 w-12" aria-hidden="true" />
                </div>

                <!-- Preview Overlay -->
                <div
                    class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 transition-all duration-200 group-hover:bg-opacity-20"
                >
                    <div class="opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                        <div class="rounded-full bg-white p-2 shadow-lg dark:bg-gray-800">
                            <Icon name="eye" class="h-5 w-5 text-gray-700 dark:text-gray-300" aria-hidden="true" />
                        </div>
                    </div>
                </div>

                <!-- Component Category Badge -->
                <div class="absolute left-2 top-2">
                    <span
                        class="inline-flex items-center rounded-full bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-300"
                    >
                        <Icon :name="getCategoryIcon(component.category)" class="mr-1 h-3 w-3" aria-hidden="true" />
                        {{ getCategoryName(component.category) }}
                    </span>
                </div>

                <!-- Recently Used Badge -->
                <div v-if="recentlyUsed" class="absolute right-2 top-2">
                    <span
                        class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                    >
                        <Icon name="clock" class="mr-1 h-3 w-3" aria-hidden="true" />
                        Recent
                    </span>
                </div>
            </div>
        </div>

        <!-- Component Info -->
        <div class="component-card__content">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <h3 :id="`component-${component.id}-title`" class="truncate text-sm font-medium text-gray-900 dark:text-white">
                        {{ component.name }}
                    </h3>

                    <p
                        v-if="component.description"
                        :id="`component-${component.id}-description`"
                        class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        {{ component.description }}
                    </p>

                    <!-- Component Metadata -->
                    <div class="mt-2 flex items-center space-x-3 text-xs text-gray-400 dark:text-gray-500">
                        <span class="flex items-center">
                            <Icon name="tag" class="mr-1 h-3 w-3" aria-hidden="true" />
                            {{ component.type }}
                        </span>

                        <span v-if="component.version" class="flex items-center">
                            <Icon name="code-bracket" class="mr-1 h-3 w-3" aria-hidden="true" />
                            v{{ component.version }}
                        </span>

                        <span v-if="component.updatedAt" class="flex items-center" :title="formatDate(component.updatedAt)">
                            <Icon name="calendar" class="mr-1 h-3 w-3" aria-hidden="true" />
                            {{ formatRelativeDate(component.updatedAt) }}
                        </span>
                    </div>
                </div>

                <!-- Favorite Button -->
                <button
                    @click.stop="handleFavorite"
                    :class="favoriteButtonClasses"
                    :aria-label="isFavorite ? `Remove ${component.name} from favorites` : `Add ${component.name} to favorites`"
                    :aria-pressed="isFavorite"
                >
                    <Icon name="heart" :class="favoriteIconClasses" aria-hidden="true" />
                </button>
            </div>
        </div>

        <!-- Actions (List View Only) -->
        <div v-if="viewMode === 'list'" class="component-card__actions">
            <div class="flex items-center space-x-2">
                <button
                    @click="handlePreview"
                    class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    <Icon name="eye" class="mr-1 h-3 w-3" aria-hidden="true" />
                    Preview
                </button>

                <button
                    @click="handleSelect"
                    class="inline-flex items-center rounded border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <Icon name="plus" class="mr-1 h-3 w-3" aria-hidden="true" />
                    Add
                </button>
            </div>
        </div>

        <!-- Quick Add Button (Grid View Only) -->
        <div v-if="viewMode === 'grid'" class="component-card__quick-add">
            <button
                @click="handleSelect"
                class="absolute bottom-2 right-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white opacity-0 shadow-lg transition-opacity duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 group-hover:opacity-100"
                :aria-label="`Add ${component.name} component`"
            >
                <Icon name="plus" class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>

        <!-- Drag Handle -->
        <div class="component-card__drag-handle">
            <div class="absolute right-2 top-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                <div class="cursor-grab rounded bg-white p-1 shadow-sm active:cursor-grabbing dark:bg-gray-800">
                    <Icon name="bars-3" class="h-3 w-3 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Common/Icon.vue';
import type { Component, ComponentCategory } from '@/types/components';
import { computed } from 'vue';

interface Props {
    component: Component;
    viewMode: 'grid' | 'list';
    isFavorite: boolean;
    recentlyUsed: boolean;
}

interface Emits {
    (e: 'preview', component: Component): void;
    (e: 'favorite', component: Component): void;
    (e: 'select', component: Component): void;
    (e: 'drag-start', component: Component): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties
const cardClasses = computed(() => [
    'component-card group relative',
    'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700',
    'hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600',
    'transition-all duration-200 cursor-pointer',
    {
        'p-4': props.viewMode === 'grid',
        'p-4 flex items-center space-x-4': props.viewMode === 'list',
    },
]);

const favoriteButtonClasses = computed(() => [
    'p-1 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
    props.isFavorite
        ? 'text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300'
        : 'text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400',
]);

const favoriteIconClasses = computed(() => ['h-4 w-4 transition-colors', props.isFavorite ? 'fill-current' : '']);

const thumbnailUrl = computed(() => {
    // In a real implementation, this would come from the component metadata
    // For now, we'll return null to show the fallback icon
    return props.component.metadata?.thumbnailUrl || null;
});

// Methods
const getCategoryIcon = (category: ComponentCategory): string => {
    const iconMap: Record<ComponentCategory, string> = {
        hero: 'star',
        forms: 'document-text',
        testimonials: 'chat-bubble-left-right',
        statistics: 'chart-bar',
        ctas: 'cursor-arrow-rays',
        media: 'photo',
    };
    return iconMap[category] || 'square-3-stack-3d';
};

const getCategoryName = (category: ComponentCategory): string => {
    const nameMap: Record<ComponentCategory, string> = {
        hero: 'Hero',
        forms: 'Form',
        testimonials: 'Testimonial',
        statistics: 'Stats',
        ctas: 'CTA',
        media: 'Media',
    };
    return nameMap[category] || category;
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const formatRelativeDate = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

    if (diffInDays === 0) return 'Today';
    if (diffInDays === 1) return 'Yesterday';
    if (diffInDays < 7) return `${diffInDays}d ago`;
    if (diffInDays < 30) return `${Math.floor(diffInDays / 7)}w ago`;
    if (diffInDays < 365) return `${Math.floor(diffInDays / 30)}mo ago`;
    return `${Math.floor(diffInDays / 365)}y ago`;
};

const handlePreview = () => {
    emit('preview', props.component);
};

const handleFavorite = () => {
    emit('favorite', props.component);
};

const handleSelect = () => {
    emit('select', props.component);
};

const handleDragStart = (event: DragEvent) => {
    if (event.dataTransfer) {
        event.dataTransfer.setData(
            'application/json',
            JSON.stringify({
                type: 'component',
                component: props.component,
            }),
        );
        event.dataTransfer.effectAllowed = 'copy';
    }

    emit('drag-start', props.component);
};

const handleDragEnd = () => {
    // Clean up any drag-related state if needed
};
</script>

<style scoped>
.component-card {
    container-type: inline-size;
}

.component-card__preview {
    @apply mb-3;
}

.component-card__content {
    @apply flex-1;
}

.component-card__actions {
    @apply ml-4 flex-shrink-0;
}

/* List view specific styles */
.component-card:has(.component-card__actions) .component-card__preview {
    @apply mb-0 h-16 w-24 flex-shrink-0;
}

.component-card:has(.component-card__actions) .component-card__preview .aspect-video {
    @apply aspect-auto h-full;
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Drag handle positioning */
.component-card__drag-handle {
    @apply pointer-events-none;
}

.component-card__drag-handle > div {
    @apply pointer-events-auto;
}

/* Focus styles */
.component-card:focus-within {
    @apply ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .component-card {
        @apply border-2;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .component-card,
    .component-card *,
    .component-card *::before,
    .component-card *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Container queries for responsive card design */
@container (max-width: 200px) {
    .component-card__content h3 {
        @apply text-xs;
    }

    .component-card__content p {
        @apply hidden;
    }
}
</style>
