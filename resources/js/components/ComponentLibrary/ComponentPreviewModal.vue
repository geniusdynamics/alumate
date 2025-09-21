<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="component-preview-modal fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            :aria-labelledby="`preview-modal-title-${component.id}`"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="handleBackdropClick" aria-hidden="true"></div>

            <!-- Modal Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.stop>
                    <!-- Modal Header -->
                    <header class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <Icon
                                    :name="getCategoryIcon(component.category)"
                                    class="h-6 w-6 text-gray-500 dark:text-gray-400"
                                    aria-hidden="true"
                                />
                                <div>
                                    <h2 :id="`preview-modal-title-${component.id}`" class="text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ component.name }}
                                    </h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ getCategoryName(component.category) }} Component</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <!-- Device Preview Toggle -->
                            <div class="flex rounded-md shadow-sm" role="group" aria-label="Device preview options">
                                <button
                                    v-for="device in devices"
                                    :key="device.id"
                                    @click="setPreviewDevice(device.id)"
                                    :class="getDeviceButtonClasses(device.id)"
                                    :aria-pressed="previewDevice === device.id"
                                    :aria-label="`Preview on ${device.name}`"
                                >
                                    <Icon :name="device.icon" class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Close Button -->
                            <button
                                @click="handleClose"
                                class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                                aria-label="Close preview"
                            >
                                <Icon name="x" class="h-5 w-5" />
                            </button>
                        </div>
                    </header>

                    <!-- Modal Body -->
                    <div class="flex flex-1 overflow-hidden">
                        <!-- Preview Area -->
                        <div class="flex-1 overflow-auto p-6">
                            <div class="flex min-h-[400px] items-center justify-center">
                                <div
                                    :class="getPreviewContainerClasses()"
                                    class="overflow-hidden rounded-lg bg-gray-50 shadow-inner dark:bg-gray-900"
                                >
                                    <!-- Component Preview -->
                                    <div class="component-preview-content">
                                        <!-- Hero Component Preview -->
                                        <HeroBase v-if="component.category === 'hero'" :config="getPreviewConfig()" :sample-data="true" />

                                        <!-- Form Component Preview -->
                                        <FormBase v-else-if="component.category === 'forms'" :config="getPreviewConfig()" :sample-data="true" />

                                        <!-- Testimonial Component Preview -->
                                        <TestimonialBase
                                            v-else-if="component.category === 'testimonials'"
                                            :config="getPreviewConfig()"
                                            :sample-data="true"
                                        />

                                        <!-- Statistics Component Preview -->
                                        <StatisticsBase
                                            v-else-if="component.category === 'statistics'"
                                            :config="getPreviewConfig()"
                                            :sample-data="true"
                                        />

                                        <!-- CTA Component Preview -->
                                        <CTABase v-else-if="component.category === 'ctas'" :config="getPreviewConfig()" :sample-data="true" />

                                        <!-- Media Component Preview -->
                                        <MediaBase v-else-if="component.category === 'media'" :config="getPreviewConfig()" :sample-data="true" />

                                        <!-- Fallback for unknown component types -->
                                        <div v-else class="flex h-64 items-center justify-center text-gray-500 dark:text-gray-400">
                                            <div class="text-center">
                                                <Icon name="exclamation-triangle" class="mx-auto mb-4 h-12 w-12" />
                                                <p>Preview not available for this component type</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <aside class="w-80 overflow-auto border-l border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                            <div class="p-6">
                                <!-- Component Info -->
                                <div class="mb-6">
                                    <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">Component Details</h3>

                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ component.type }}</dd>
                                        </div>

                                        <div v-if="component.version">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ component.version }}</dd>
                                        </div>

                                        <div v-if="component.description">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ component.description }}</dd>
                                        </div>

                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ formatDate(component.updatedAt) }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Configuration Options -->
                                <div class="mb-6">
                                    <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">Preview Options</h3>

                                    <!-- Sample Data Toggle -->
                                    <div class="mb-4 flex items-center justify-between">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Use Sample Data </label>
                                        <button
                                            @click="toggleSampleData"
                                            :class="getSampleDataToggleClasses()"
                                            role="switch"
                                            :aria-checked="useSampleData"
                                            aria-label="Toggle sample data"
                                        >
                                            <span
                                                :class="getSampleDataToggleThumbClasses()"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            ></span>
                                        </button>
                                    </div>

                                    <!-- Theme Selection -->
                                    <div class="mb-4">
                                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Theme </label>
                                        <select
                                            v-model="previewTheme"
                                            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="default">Default</option>
                                            <option value="minimal">Minimal</option>
                                            <option value="modern">Modern</option>
                                            <option value="classic">Classic</option>
                                        </select>
                                    </div>

                                    <!-- Color Scheme Selection -->
                                    <div class="mb-4">
                                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Scheme </label>
                                        <select
                                            v-model="previewColorScheme"
                                            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="default">Default</option>
                                            <option value="primary">Primary</option>
                                            <option value="secondary">Secondary</option>
                                            <option value="accent">Accent</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="space-y-3">
                                    <button
                                        @click="handleSelect"
                                        class="flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        <Icon name="plus" class="mr-2 h-4 w-4" />
                                        Add to Page
                                    </button>

                                    <button
                                        @click="handleCopyConfig"
                                        class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        <Icon name="clipboard" class="mr-2 h-4 w-4" />
                                        Copy Config
                                    </button>

                                    <button
                                        @click="handleExportComponent"
                                        class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        <Icon name="arrow-down-tray" class="mr-2 h-4 w-4" />
                                        Export
                                    </button>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import Icon from '@/components/Common/Icon.vue';
import type { Component, ComponentCategory } from '@/types/components';
import { onMounted, onUnmounted, ref } from 'vue';

// Import component bases for preview
import CTABase from './CTAs/CTABase.vue';
import FormBase from './Forms/FormBase.vue';
import HeroBase from './Hero/HeroBase.vue';
import MediaBase from './Media/MediaBase.vue';
import StatisticsBase from './Statistics/StatisticsBase.vue';
import TestimonialBase from './Testimonials/TestimonialBase.vue';

interface Props {
    component: Component;
    isOpen: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'select', component: Component): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Reactive state
const previewDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop');
const useSampleData = ref(true);
const previewTheme = ref('default');
const previewColorScheme = ref('default');

// Device configurations
const devices = [
    { id: 'desktop' as const, name: 'Desktop', icon: 'computer-desktop', width: '100%', height: 'auto' },
    { id: 'tablet' as const, name: 'Tablet', icon: 'device-tablet', width: '768px', height: '1024px' },
    { id: 'mobile' as const, name: 'Mobile', icon: 'device-phone-mobile', width: '375px', height: '667px' },
];

// Computed properties
const getPreviewContainerClasses = () => {
    const device = devices.find((d) => d.id === previewDevice.value);
    if (!device) return 'w-full';

    return {
        'w-full': previewDevice.value === 'desktop',
        'w-[768px] h-[600px]': previewDevice.value === 'tablet',
        'w-[375px] h-[600px]': previewDevice.value === 'mobile',
    };
};

const getSampleDataToggleClasses = () => [
    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
    useSampleData.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700',
];

const getSampleDataToggleThumbClasses = () => [useSampleData.value ? 'translate-x-5' : 'translate-x-0'];

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
        hero: 'Hero Section',
        forms: 'Form',
        testimonials: 'Testimonial',
        statistics: 'Statistics',
        ctas: 'Call to Action',
        media: 'Media',
    };
    return nameMap[category] || category;
};

const getDeviceButtonClasses = (deviceId: string) => [
    'px-3 py-2 text-sm font-medium border focus:outline-none focus:ring-1 focus:ring-indigo-500',
    deviceId === 'desktop' ? 'rounded-l-md' : deviceId === 'mobile' ? 'rounded-r-md -ml-px' : '-ml-px',
    previewDevice.value === deviceId
        ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300'
        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600',
];

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getPreviewConfig = () => {
    // Create a preview configuration with sample data and current theme/color scheme
    const baseConfig = { ...props.component.config };

    // Apply preview theme and color scheme
    return {
        ...baseConfig,
        theme: previewTheme.value,
        colorScheme: previewColorScheme.value,
        sampleData: useSampleData.value,
    };
};

const setPreviewDevice = (deviceId: 'desktop' | 'tablet' | 'mobile') => {
    previewDevice.value = deviceId;
};

const toggleSampleData = () => {
    useSampleData.value = !useSampleData.value;
};

const handleClose = () => {
    emit('close');
};

const handleBackdropClick = () => {
    emit('close');
};

const handleSelect = () => {
    emit('select', props.component);
    emit('close');
};

const handleCopyConfig = async () => {
    try {
        const config = JSON.stringify(getPreviewConfig(), null, 2);
        await navigator.clipboard.writeText(config);
        // You might want to show a toast notification here
    } catch (error) {
        console.error('Failed to copy configuration:', error);
    }
};

const handleExportComponent = () => {
    const config = getPreviewConfig();
    const dataStr = JSON.stringify(
        {
            component: props.component,
            config,
        },
        null,
        2,
    );

    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);

    const link = document.createElement('a');
    link.href = url;
    link.download = `${props.component.name.toLowerCase().replace(/\s+/g, '-')}-component.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
};

// Keyboard event handler
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        handleClose();
    }
};

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    // Restore body scroll
    document.body.style.overflow = '';
});
</script>

<style scoped>
.component-preview-modal {
    backdrop-filter: blur(4px);
}

.component-preview-content {
    @apply h-full w-full overflow-auto;
}

/* Ensure modal content is properly contained */
.component-preview-content > * {
    @apply max-w-full;
}

/* Device-specific preview styles */
.component-preview-content:has(.w-\[375px\]) {
    @apply overflow-hidden;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .component-preview-modal {
        @apply contrast-125;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .component-preview-modal *,
    .component-preview-modal *::before,
    .component-preview-modal *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
