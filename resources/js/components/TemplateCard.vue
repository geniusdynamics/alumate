<template>
  <div
    class="template-card"
    :class="{ 'template-card--list': viewMode === 'list' }"
    @click="handleCardClick"
    @keydown.enter="handleCardClick"
    @keydown.space.prevent="handleCardClick"
    role="button"
    tabindex="0"
    :aria-label="`View template ${template.name}`"
    :aria-selected="false"
  >
    <!-- Card Content -->
    <div class="card-content">
      <!-- Template Preview Image -->
      <div class="preview-container">
        <img
          v-if="template.previewImage"
          :src="template.previewImage"
          :alt="`${template.name} preview`"
          class="preview-image"
          loading="lazy"
        />
        <div
          v-else
          class="preview-placeholder"
          :style="{ background: `linear-gradient(135deg, ${placeholderColors[0]}, ${placeholderColors[1]})` }"
        >
          <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>

        <!-- Overlay badges -->
        <div class="overlay-badges">
          <span v-if="template.isPremium" class="premium-badge" aria-label="Premium template">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            Premium
          </span>
        </div>

        <!-- Hover overlay -->
        <div class="hover-overlay">
          <div class="overlay-actions" @click.stop>
            <button
              @click="handlePreview"
              class="preview-btn"
              :aria-label="`Preview ${template.name}`"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Preview
            </button>
          </div>
        </div>
      </div>

      <!-- Template Info -->
      <div class="template-info">
        <!-- Name and Category -->
        <div class="template-header">
          <h3 class="template-name" :title="template.name">
            {{ template.name }}
          </h3>
          <span
            v-if="template.category"
            class="category-badge"
            :class="`category-${template.category}`"
          >
            {{ template.category }}
          </span>
        </div>

        <!-- Description -->
        <p v-if="template.description" class="template-description">
          {{ template.description }}
        </p>

        <!-- Audience and Campaign Types -->
        <div class="template-meta">
          <div v-if="template.audienceType" class="meta-item">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            {{ template.audienceType }}
          </div>
          <div v-if="template.campaignType" class="meta-item">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            {{ template.campaignType.replace('_', ' ') }}
          </div>
        </div>

        <!-- Tags -->
        <div v-if="template.tags?.length" class="template-tags">
          <span
            v-for="tag in template.tags.slice(0, 3)"
            :key="tag"
            class="tag-badge"
          >
            #{{ tag }}
          </span>
          <span v-if="template.tags.length > 3" class="tags-more">
            +{{ template.tags.length - 3 }} more
          </span>
        </div>

        <!-- Usage Stats -->
        <div class="template-stats">
          <div class="usage-count">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ template.usageCount.toLocaleString() }} uses
          </div>
          <div v-if="isPopular" class="popular-badge">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            Popular
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="template-actions">
          <button
            @click.stop="handlePreview"
            class="action-btn action-btn--secondary"
            :aria-label="`Preview ${template.name} template`"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Preview
          </button>
          <button
            @click.stop="handleSelect"
            class="action-btn action-btn--primary"
            :aria-label="`Select ${template.name} template`"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Select
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { templateService } from '@/services/TemplateService'
import type { Template, ViewMode } from '@/types/components'

// Props
interface Props {
  template: Template
  viewMode?: ViewMode
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid'
})

// Emits
const emit = defineEmits<{
  preview: [template: Template]
  select: [template: Template]
}>()

// Computed properties
const placeholderColors = computed(() => {
  const colors = [
    ['#667eea', '#764ba2'],
    ['#f093fb', '#f5576c'],
    ['#4facfe', '#00f2fe'],
    ['#43e97b', '#38f9d7'],
    ['#fa709a', '#fee140']
  ]
  return colors[props.template.id % colors.length]
})

const isPopular = computed(() => {
  return props.template.usageCount > 100
})

// Methods
const handlePreview = () => {
  emit('preview', props.template)
}

const handleSelect = async () => {
  // Update usage count automatically
  try {
    await templateService.updateTemplateUsage(props.template.id)
  } catch (error) {
    console.warn('Failed to update template usage:', error)
  }
  emit('select', props.template)
}

const handleCardClick = () => {
  handleSelect()
}
</script>

<style scoped>
.template-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-105;
  height: fit-content;
}

.template-card:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800;
}

.template-card--list {
  @apply flex;
}

/* Preview Container */
.preview-container {
  @apply relative aspect-video bg-gray-100 dark:bg-gray-700 overflow-hidden;
}

.preview-image {
  @apply w-full h-full object-cover transition-transform duration-200 group-hover:scale-105;
}

.preview-placeholder {
  @apply w-full h-full flex items-center justify-center;
}

.placeholder-icon {
  @apply w-12 h-12 text-gray-400;
}

/* Overlay Badges */
.overlay-badges {
  @apply absolute top-3 right-3;
}

.premium-badge {
  @apply inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-gradient-to-r from-yellow-500 to-orange-500 rounded-full shadow-lg;
}

/* Hover Overlay */
.hover-overlay {
  @apply absolute inset-0 bg-black bg-opacity-0 opacity-0 transition-all duration-200 flex items-center justify-center;
}

.template-card:hover .hover-overlay {
  @apply opacity-100 bg-opacity-40;
}

.overlay-actions {
  @apply flex gap-3;
}

.preview-btn {
  @apply px-4 py-2 bg-white/90 backdrop-blur-sm text-gray-900 rounded-lg text-sm font-medium hover:bg-white transition-colors;
}

/* Card Content */
.card-content {
  @apply p-6;
}

/* Template Info */
.template-info {
  @apply space-y-4;
}

.template-header {
  @apply flex items-start justify-between gap-3;
}

.template-name {
  @apply text-lg font-semibold text-gray-900 dark:text-white leading-tight flex-1;
}

.category-badge {
  @apply px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap;
}

.category-landing {
  @apply bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200;
}

.category-homepage {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.category-form {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.category-email {
  @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.category-social {
  @apply bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200;
}

.template-description {
  @apply text-sm text-gray-600 dark:text-gray-400 line-clamp-2;
}

/* Meta Information */
.template-meta {
  @apply flex flex-wrap gap-4;
}

.meta-item {
  @apply inline-flex items-center text-xs text-gray-500 dark:text-gray-400;
}

/* Tags */
.template-tags {
  @apply flex items-center gap-2 flex-wrap;
}

.tag-badge {
  @apply px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded;
}

.tags-more {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

/* Usage Stats */
.template-stats {
  @apply flex items-center justify-between;
}

.usage-count {
  @apply inline-flex items-center text-xs text-gray-500 dark:text-gray-400;
}

.popular-badge {
  @apply inline-flex items-center text-xs text-yellow-600 dark:text-yellow-400 font-medium;
}

/* Action Buttons */
.template-actions {
  @apply flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-700;
}

.action-btn {
  @apply flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg transition-colors;
}

.action-btn--primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

.action-btn--secondary {
  @apply bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600;
}

/* List View Adjustments */
.template-card--list .card-content {
  @apply flex-1 flex;
}

.template-card--list .preview-container {
  @apply w-48 flex-shrink-0;
}

.template-card--list .template-info {
  @apply flex-1 ml-6;
}

.template-card--list .template-actions {
  @apply flex-row;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .template-card {
    @apply bg-gray-800 border-gray-700;
  }

  .premium-badge {
    @apply bg-gradient-to-r from-yellow-500 to-orange-500;
  }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .template-card--list {
    @apply block;
  }

  .template-card--list .card-content {
    @apply block;
  }

  .template-card--list .preview-container {
    @apply w-full;
  }

  .template-card--list .template-info {
    @apply ml-0 mt-4;
  }

  .template-actions {
    @apply flex-col gap-2;
  }

  .action-btn {
    @apply w-full;
  }
}

/* Focus Styles for Accessibility */
.template-card:focus-visible {
  @apply ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800;
}

.action-btn:focus-visible,
.preview-btn:focus-visible {
  @apply ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800;
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .template-card {
    @apply transition-none hover:scale-100;
  }

  .hover-overlay {
    @apply transition-none;
  }

  .preview-image {
    @apply transition-none;
  }
}
</style>