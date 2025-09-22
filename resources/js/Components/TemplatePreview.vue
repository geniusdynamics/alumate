<template>
    <div class="template-preview">
        <!-- Preview Modal -->
        <div v-if="showPreview" class="preview-modal" @click="closePreview">
            <div class="preview-container" @click.stop>
                <!-- Header -->
                <div class="preview-header">
                    <div class="header-info">
                        <h3 class="preview-title">{{ selectedTemplate?.name || 'Template Preview' }}</h3>
                        <div class="header-badges">
                            <span v-if="selectedTemplate?.isPremium" class="premium-badge">
                                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                                Premium
                            </span>
                            <span class="category-badge" :class="'category-' + (selectedTemplate?.category || '')">
                                {{ selectedTemplate?.category }}
                            </span>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="preview-controls">
                        <!-- Viewport Switcher -->
                        <div class="viewport-controls">
                            <button
                                v-for="viewport in viewports"
                                :key="viewport.type"
                                @click="changeViewport(viewport.type)"
                                :class="{ active: currentViewport === viewport.type }"
                                class="viewport-btn"
                                :aria-pressed="currentViewport === viewport.type"
                                :aria-label="'Switch to ' + viewport.label + ' view (' + viewport.width + ')'"
                            >
                                <component :is="viewport.icon" class="h-5 w-5" />
                                <span class="viewport-label">{{ viewport.label }}</span>
                                <span class="viewport-size">{{ viewport.width }}</span>
                            </button>
                        </div>

                        <!-- Close Button -->
                        <button @click="closePreview" class="close-btn" aria-label="Close preview">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="preview-content">
                    <div v-if="previewLoading" class="preview-loading">
                        <div class="loading-spinner"></div>
                        <p>Generating preview...</p>
                    </div>

                    <div v-else-if="previewError" class="preview-error">
                        <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                        <h4 class="error-title">Preview Error</h4>
                        <p class="error-message">{{ previewError }}</p>
                        <button @click="generatePreview" class="retry-btn">Retry</button>
                    </div>

                    <!-- Responsive preview with different viewports -->
                    <div v-else class="preview-iframe-container" :class="currentViewportClass">
                        <iframe
                            v-if="responsivePreview && responsivePreview[currentViewport]?.html"
                            :srcdoc="currentPreviewSrcDoc"
                            class="preview-iframe"
                            :title="selectedTemplate?.name + ' preview'"
                            sandbox="allow-scripts"
                        ></iframe>

                        <div v-else class="preview-placeholder">
                            <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h4 class="placeholder-title">No Preview Available</h4>
                            <p class="placeholder-text">This template doesn't have a preview available for the selected viewport.</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="preview-footer">
                    <div class="template-stats">
                        <div class="stat-item">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            {{ selectedTemplate?.usageCount.toLocaleString() || 0 }} uses
                        </div>
                        <div v-if="selectedTemplate?.lastUsedAt" class="stat-item">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Last used {{ formatDate(selectedTemplate.lastUsedAt) }}
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button @click="emit('templateSelected', selectedTemplate!)" class="select-btn">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Select Template
                        </button>
                        <button @click="copyEmbedCode" class="embed-btn" :disabled="!previewHtml">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                            Copy Embed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { templateService } from '@/Services/TemplateService';
import type { Template, TemplatePreviewConfig, ViewportType } from '@/types/Components';
import { ComputerDesktopIcon, DevicePhoneMobileIcon, DeviceTabletIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref, watch } from 'vue';

// Props
interface Props {
    modelValue: boolean;
    template?: Template | null;
    initialViewport?: ViewportType;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    template: null,
    initialViewport: 'desktop',
});

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    templateSelected: [template: Template];
}>();

// Reactive state
const selectedTemplate = ref<Template | null>(props.template);
const currentViewport = ref<ViewportType>(props.initialViewport);
const previewLoading = ref(false);
const previewError = ref('');
const previewHtml = ref('');
const previewCss = ref('');
const responsivePreview = ref<{ [key: string]: { html: string; width: number; height: number } } | null>(null);
const previewAssets = ref<{
    styles: string[];
    scripts: string[];
    css: string;
} | null>(null);

// Viewport configurations
const viewports = [
    {
        type: 'desktop' as ViewportType,
        label: 'Desktop',
        icon: ComputerDesktopIcon,
        width: '100%',
    },
    {
        type: 'tablet' as ViewportType,
        label: 'Tablet',
        icon: DeviceTabletIcon,
        width: '768px',
    },
    {
        type: 'mobile' as ViewportType,
        label: 'Mobile',
        icon: DevicePhoneMobileIcon,
        width: '375px',
    },
];

// Computed properties
const showPreview = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const currentViewportClass = computed(() => {
    switch (currentViewport.value) {
        case 'desktop':
            return 'viewport-desktop';
        case 'tablet':
            return 'viewport-tablet';
        case 'mobile':
            return 'viewport-mobile';
        default:
            return 'viewport-desktop';
    }
});

const previewSrcDoc = computed(() => {
    if (!previewHtml.value || !previewCss.value) return '';

    const parts = [];
    parts.push('<!DOCTYPE html>');
    parts.push('<html>');
    parts.push('<head>');
    parts.push('<meta charset="UTF-8">');
    parts.push('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
    parts.push('<style>');
    parts.push(previewCss.value);
    parts.push('</style>');
    parts.push('</head>');
    parts.push('<body>');
    parts.push(previewHtml.value);
    parts.push('</body>');
    parts.push('</html>');

    return parts.join('');
});

const changeViewport = (type: ViewportType) => {
    currentViewport.value = type;
};

const currentPreviewSrcDoc = computed(() => {
    if (!responsivePreview.value || !responsivePreview.value[currentViewport.value]) return '';

    const currentPreview = responsivePreview.value[currentViewport.value];
    const assets = previewAssets.value;

    const parts = [];
    parts.push('<!DOCTYPE html>');
    parts.push('<html>');
    parts.push('<head>');
    parts.push('<meta charset="UTF-8">');
    parts.push('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

    if (assets?.css) {
        parts.push('<' + 'style>');
        parts.push(assets.css);
        parts.push('<' + '/style>');
    }

    if (assets?.styles && assets.styles.length > 0) {
        assets.styles.forEach((style) => {
            parts.push('<' + 'link rel="stylesheet" href="' + style + '">');
        });
    }

    parts.push('</head>');
    parts.push('<body>');
    parts.push(currentPreview.html);
    parts.push('</body>');

    if (assets?.scripts && assets.scripts.length > 0) {
        assets.scripts.forEach((script) => {
            parts.push('<' + 'script src="' + script + '"><' + '/script>');
        });
    }

    parts.push('</html>');

    return parts.join('');
});

// Methods
const closePreview = () => {
    showPreview.value = false;
    previewError.value = '';
    previewHtml.value = '';
    previewCss.value = '';
};

const generatePreview = async () => {
    if (!selectedTemplate.value) return;

    try {
        previewLoading.value = true;
        previewError.value = '';

        const config: TemplatePreviewConfig = {
            templateId: selectedTemplate.value.id,
            viewport: currentViewport.value,
            showControls: false,
            interactive: false,
        };

        const result = await templateService.generatePreview(selectedTemplate.value.id, config);

        previewHtml.value = result.html;
        previewCss.value = result.css;
    } catch (err) {
        previewError.value = err instanceof Error ? err.message : 'Failed to generate preview';
        console.error('Error generating preview:', err);
    } finally {
        previewLoading.value = false;
    }
};

const copyEmbedCode = async () => {
    if (!previewHtml.value) return;

    const parts = [];
    parts.push('<' + 'iframe srcdoc="');
    parts.push(previewSrcDoc.value.replace(/"/g, '"'));
    parts.push('" width="100%" height="400" frameborder="0"><' + '/iframe>');

    const embedCode = parts.join('');

    try {
        await navigator.clipboard.writeText(embedCode);
        // Could emit a toast notification here
        alert('Embed code copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy embed code:', err);
        alert('Failed to copy embed code');
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - date.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) return 'today';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
    return date.toLocaleDateString();
};

// Watchers
watch(
    () => props.template,
    (newTemplate) => {
        selectedTemplate.value = newTemplate;
        if (newTemplate && showPreview.value) {
            generatePreview();
        }
    },
);

watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue && selectedTemplate.value) {
            generatePreview();
        }
    },
);

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
    if (!showPreview.value) return;

    if (event.key === 'Escape') {
        closePreview();
    }
};

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

// Cleanup
const cleanup = () => {
    document.removeEventListener('keydown', handleKeydown);
};

// Watch for component unmount
watch(
    () => showPreview.value,
    (newValue) => {
        if (!newValue) {
            cleanup();
        }
    },
);
</script>

<style scoped>
.template-preview {
    @apply relative;
}

/* Modal */
.preview-modal {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4;
}

.preview-container {
    @apply flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800;
}

/* Header */
.preview-header {
    @apply flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.header-info {
    @apply flex-1;
}

.preview-title {
    @apply mb-2 text-xl font-semibold text-gray-900 dark:text-white;
}

.header-badges {
    @apply mt-2 flex items-center gap-2;
}

.premium-badge {
    @apply inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-sm font-medium text-amber-600 dark:bg-amber-900/20;
}

.category-badge {
    @apply rounded-full px-2 py-1 text-sm font-medium;
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

.preview-controls {
    @apply flex items-center gap-4;
}

.viewport-controls {
    @apply flex items-center gap-2;
}

.viewport-btn {
    @apply flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700;
}

.viewport-btn.active {
    @apply border-blue-600 bg-blue-600 text-white;
}

.close-btn {
    @apply rounded-full p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100;
}

/* Content */
.preview-content {
    @apply min-h-0 flex-1;
}

.preview-loading {
    @apply flex h-96 flex-col items-center justify-center;
}

.loading-spinner {
    @apply mb-4 h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent;
}

.preview-error {
    @apply flex h-96 flex-col items-center justify-center px-8 text-center;
}

.error-icon {
    @apply mb-4 h-12 w-12 text-red-500;
}

.error-title {
    @apply mb-2 text-xl font-semibold text-gray-900 dark:text-white;
}

.error-message {
    @apply mb-4 text-gray-600 dark:text-gray-400;
}

.retry-btn {
    @apply rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700;
}

.preview-iframe-container {
    @apply m-6 overflow-hidden rounded-lg bg-gray-100 transition-all duration-300 dark:bg-gray-900;
}

.preview-iframe-container.viewport-desktop {
    @apply p-2;
}

.preview-iframe-container.viewport-tablet {
    @apply p-4;
    max-width: 768px;
    margin: 1.5rem auto;
}

.preview-iframe-container.viewport-mobile {
    @apply p-2;
    max-width: 375px;
    margin: 1.5rem auto;
}

.preview-iframe {
    @apply h-96 w-full rounded border border-gray-200 bg-white;
}

.preview-placeholder {
    @apply flex h-96 flex-col items-center justify-center px-8 text-center;
}

.placeholder-icon {
    @apply mb-4 h-12 w-12 text-gray-400;
}

.placeholder-title {
    @apply mb-2 text-xl font-semibold text-gray-900 dark:text-white;
}

.placeholder-text {
    @apply text-gray-600 dark:text-gray-400;
}

/* Footer */
.preview-footer {
    @apply flex items-center justify-between border-t border-gray-200 p-6 dark:border-gray-700;
}

.template-stats {
    @apply flex items-center gap-6;
}

.stat-item {
    @apply inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400;
}

.action-buttons {
    @apply flex items-center gap-3;
}

.select-btn {
    @apply flex items-center rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700;
}

.embed-btn {
    @apply flex items-center rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .preview-modal {
        @apply bg-black bg-opacity-70;
    }

    .preview-container {
        @apply bg-gray-800;
    }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .preview-modal {
        @apply p-2;
    }

    .preview-container {
        @apply max-h-[95vh];
    }

    .preview-header {
        @apply flex-col gap-4;
    }

    .viewport-controls {
        @apply overflow-x-auto pb-2;
    }

    .preview-iframe-container.viewport-tablet,
    .preview-iframe-container.viewport-mobile {
        @apply m-4;
    }

    .preview-footer {
        @apply flex-col gap-4;
    }

    .template-stats {
        @apply justify-center;
    }

    .action-buttons {
        @apply justify-center;
    }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
    .preview-container {
        @apply transition-none;
    }

    .viewport-btn,
    .close-btn,
    .select-btn,
    .embed-btn {
        @apply transition-none;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .preview-container {
        @apply border-2 border-black dark:border-white;
    }

    .viewport-btn.active {
        @apply border-2 border-current bg-current;
    }
}
</style>















