<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('cancel')"></div>

            <!-- Modal panel -->
            <div
                class="my-8 inline-block w-full max-w-4xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ isNew ? 'Create Brand Template' : 'Edit Brand Template' }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{
                                isNew ? 'Create a new brand template with colors, fonts, and styling' : 'Update template properties and configuration'
                            }}
                        </p>
                    </div>
                    <button @click="$emit('cancel')" class="btn-icon">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit">
                    <div class="space-y-8">
                        <!-- Template Preview -->
                        <div class="template-preview-section">
                            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"> Template Preview </label>
                            <div class="template-preview-container">
                                <div class="preview-card" :style="previewStyles">
                                    <div class="preview-header">
                                        <h3 class="preview-title">{{ form.name || 'Template Name' }}</h3>
                                        <p class="preview-subtitle">Sample component with template styling</p>
                                    </div>
                                    <div class="preview-content">
                                        <div class="preview-colors">
                                            <div
                                                v-for="color in selectedColors"
                                                :key="color.id"
                                                class="preview-color-swatch"
                                                :style="{ backgroundColor: color.value }"
                                                :title="color.name"
                                            ></div>
                                        </div>
                                        <div class="preview-text">
                                            <p class="preview-body-text">This is how body text will appear with the selected typography settings.</p>
                                            <button class="preview-button" :style="buttonStyles">Sample Button</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Template Name * </label>
                                <input v-model="form.name" type="text" required class="form-input" placeholder="e.g., Corporate Blue" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Template Tags </label>
                                <input
                                    v-model="tagsInput"
                                    type="text"
                                    class="form-input"
                                    placeholder="e.g., professional, modern, blue"
                                    @input="updateTags"
                                />
                                <p class="mt-1 text-xs text-gray-500">Comma-separated tags for categorization</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Description </label>
                            <textarea
                                v-model="form.description"
                                class="form-textarea"
                                rows="3"
                                placeholder="Describe when and how this template should be used..."
                            ></textarea>
                        </div>

                        <!-- Color Selection -->
                        <div class="color-selection-section">
                            <div class="section-header">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Color Palette</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Select colors from your brand assets to include in this template
                                </p>
                            </div>

                            <div class="color-grid">
                                <div
                                    v-for="color in brandAssets.colors"
                                    :key="color.id"
                                    class="color-option"
                                    :class="{ 'color-option--selected': isColorSelected(color) }"
                                    @click="toggleColor(color)"
                                >
                                    <div class="color-swatch" :style="{ backgroundColor: color.value }"></div>
                                    <div class="color-info">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ color.name }}
                                        </p>
                                        <p class="font-mono text-xs text-gray-500">
                                            {{ color.value }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ color.type }}
                                        </p>
                                    </div>
                                    <div class="color-selection-indicator">
                                        <Icon v-if="isColorSelected(color)" name="check-circle" class="h-5 w-5 text-blue-600" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedColors.length === 0" class="empty-state">
                                <Icon name="color-swatch" class="mb-2 h-8 w-8 text-gray-400" />
                                <p class="text-sm text-gray-500">Select colors to include in this template</p>
                            </div>
                        </div>

                        <!-- Typography Selection -->
                        <div class="typography-selection-section">
                            <div class="section-header">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Typography</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Choose primary and secondary fonts for this template</p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Primary Font </label>
                                    <select v-model="form.primaryFont" class="form-select">
                                        <option value="">Select primary font</option>
                                        <option v-for="font in brandAssets.fonts" :key="font.id" :value="font.family">
                                            {{ font.name }} ({{ font.type }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Secondary Font (Optional) </label>
                                    <select v-model="form.secondaryFont" class="form-select">
                                        <option value="">Select secondary font</option>
                                        <option v-for="font in brandAssets.fonts" :key="font.id" :value="font.family">
                                            {{ font.name }} ({{ font.type }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Font Preview -->
                            <div v-if="form.primaryFont" class="font-preview">
                                <div class="font-sample" :style="{ fontFamily: form.primaryFont }">
                                    <h4 class="text-lg font-semibold">Primary Font Sample</h4>
                                    <p class="text-sm">The quick brown fox jumps over the lazy dog</p>
                                </div>
                                <div v-if="form.secondaryFont" class="font-sample" :style="{ fontFamily: form.secondaryFont }">
                                    <h4 class="text-lg font-semibold">Secondary Font Sample</h4>
                                    <p class="text-sm">The quick brown fox jumps over the lazy dog</p>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Variant Selection -->
                        <div class="logo-selection-section">
                            <div class="section-header">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Logo Variant</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Choose which logo variant works best with this template</p>
                            </div>

                            <div class="logo-grid">
                                <div
                                    v-for="logo in brandAssets.logos"
                                    :key="logo.id"
                                    class="logo-option"
                                    :class="{ 'logo-option--selected': form.logoVariant === logo.id }"
                                    @click="form.logoVariant = logo.id"
                                >
                                    <div class="logo-preview">
                                        <img :src="logo.url" :alt="logo.alt" class="max-h-12 max-w-full object-contain" />
                                    </div>
                                    <div class="logo-info">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ logo.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ logo.type }}
                                        </p>
                                    </div>
                                    <div class="logo-selection-indicator">
                                        <Icon v-if="form.logoVariant === logo.id" name="check-circle" class="h-5 w-5 text-blue-600" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Template Settings -->
                        <div class="template-settings-section">
                            <div class="section-header">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">Template Settings</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Additional configuration for this template</p>
                            </div>

                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label class="flex items-center">
                                        <input v-model="form.isDefault" type="checkbox" class="form-checkbox" />
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"> Set as default template </span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">This template will be automatically applied to new Components</p>
                                </div>

                                <div class="setting-item">
                                    <label class="flex items-center">
                                        <input v-model="form.autoApplyToExisting" type="checkbox" class="form-checkbox" />
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"> Apply to existing Components </span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">Update existing Components to use this template</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                        <div class="flex gap-3">
                            <button v-if="!isNew" type="button" @click="previewTemplate" class="btn-secondary">
                                <Icon name="eye" class="mr-2 h-4 w-4" />
                                Preview Template
                            </button>
                            <button type="button" @click="exportTemplate" class="btn-secondary">
                                <Icon name="download" class="mr-2 h-4 w-4" />
                                Export Template
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="$emit('cancel')" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary" :disabled="!isFormValid">
                                {{ isNew ? 'Create Template' : 'Update Template' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Common/Icon.vue';
import type { BrandAssets, BrandColor, BrandTemplate } from '@/types/Components';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    template?: BrandTemplate | null;
    isNew: boolean;
    brandAssets: BrandAssets;
}

const props = withDefaults(defineProps<Props>(), {
    template: null,
});

const emit = defineEmits<{
    save: [templateData: any];
    cancel: [];
    preview: [template: BrandTemplate];
    export: [template: BrandTemplate];
}>();

// Form state
const form = ref({
    name: '',
    description: '',
    colors: [] as string[],
    primaryFont: '',
    secondaryFont: '',
    logoVariant: '',
    tags: [] as string[],
    isDefault: false,
    autoApplyToExisting: false,
});

const tagsInput = ref('');

// Computed properties
const selectedColors = computed(() => {
    return props.brandAssets.colors.filter((color) => form.value.colors.includes(color.id));
});

const primaryColor = computed(() => {
    return selectedColors.value.find((color) => color.type === 'primary') || selectedColors.value[0];
});

const previewStyles = computed(() => {
    const styles: Record<string, string> = {};

    if (form.value.primaryFont) {
        styles.fontFamily = form.value.primaryFont;
    }

    if (primaryColor.value) {
        styles.borderColor = primaryColor.value.value;
    }

    return styles;
});

const buttonStyles = computed(() => {
    const styles: Record<string, string> = {};

    if (primaryColor.value) {
        styles.backgroundColor = primaryColor.value.value;
        styles.color = '#ffffff';
    }

    return styles;
});

const isFormValid = computed(() => {
    return form.value.name.trim() !== '' && selectedColors.value.length > 0 && form.value.primaryFont !== '';
});

// Methods
const isColorSelected = (color: BrandColor): boolean => {
    return form.value.colors.includes(color.id);
};

const toggleColor = (color: BrandColor) => {
    const index = form.value.colors.indexOf(color.id);
    if (index > -1) {
        form.value.colors.splice(index, 1);
    } else {
        form.value.colors.push(color.id);
    }
};

const updateTags = () => {
    form.value.tags = tagsInput.value
        .split(',')
        .map((tag) => tag.trim())
        .filter((tag) => tag !== '');
};

const previewTemplate = () => {
    const templateData = createTemplateData();
    emit('preview', templateData);
};

const exportTemplate = () => {
    const templateData = createTemplateData();
    emit('export', templateData);
};

const createTemplateData = () => {
    return {
        id: props.template?.id || '',
        name: form.value.name,
        description: form.value.description,
        colors: selectedColors.value,
        primaryFont: form.value.primaryFont,
        secondaryFont: form.value.secondaryFont,
        logoVariant: form.value.logoVariant,
        tags: form.value.tags,
        isDefault: form.value.isDefault,
        previewImage: '', // Will be generated server-side
        usageCount: props.template?.usageCount || 0,
    };
};

const handleSubmit = () => {
    if (!isFormValid.value) return;

    const templateData = {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        colorIds: form.value.colors,
        primaryFont: form.value.primaryFont,
        secondaryFont: form.value.secondaryFont || undefined,
        logoVariant: form.value.logoVariant || undefined,
        tags: form.value.tags,
        isDefault: form.value.isDefault,
        autoApplyToExisting: form.value.autoApplyToExisting,
    };

    emit('save', templateData);
};

// Initialize form with existing template data
onMounted(() => {
    if (props.template) {
        form.value = {
            name: props.template.name,
            description: props.template.description,
            colors: props.template.colors.map((c) => c.id),
            primaryFont: props.template.primaryFont,
            secondaryFont: props.template.secondaryFont || '',
            logoVariant: props.template.logoVariant || '',
            tags: props.template.tags || [],
            isDefault: props.template.isDefault || false,
            autoApplyToExisting: false,
        };

        tagsInput.value = form.value.tags.join(', ');
    }
});

// Watch for template prop changes
watch(
    () => props.template,
    (newTemplate) => {
        if (newTemplate) {
            form.value = {
                name: newTemplate.name,
                description: newTemplate.description,
                colors: newTemplate.colors.map((c) => c.id),
                primaryFont: newTemplate.primaryFont,
                secondaryFont: newTemplate.secondaryFont || '',
                logoVariant: newTemplate.logoVariant || '',
                tags: newTemplate.tags || [],
                isDefault: newTemplate.isDefault || false,
                autoApplyToExisting: false,
            };

            tagsInput.value = form.value.tags.join(', ');
        }
    },
);
</script>

<style scoped>
.btn-icon {
    @apply rounded-md p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white;
}

.btn-primary {
    @apply rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400;
}

.btn-secondary {
    @apply flex items-center rounded-md bg-gray-100 px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.form-input {
    @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.form-select {
    @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.form-textarea {
    @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.form-checkbox {
    @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.template-preview-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.template-preview-container {
    @apply rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-600 dark:bg-gray-800;
}

.preview-card {
    @apply rounded-lg border-2 border-gray-200 bg-white p-6 transition-all duration-200 dark:border-gray-600 dark:bg-gray-800;
}

.preview-header {
    @apply mb-4;
}

.preview-title {
    @apply text-xl font-bold text-gray-900 dark:text-white;
}

.preview-subtitle {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.preview-content {
    @apply space-y-4;
}

.preview-colors {
    @apply flex gap-2;
}

.preview-color-swatch {
    @apply h-6 w-6 rounded border border-gray-200;
}

.preview-text {
    @apply space-y-3;
}

.preview-body-text {
    @apply text-gray-700 dark:text-gray-300;
}

.preview-button {
    @apply rounded-md px-4 py-2 font-medium transition-colors duration-200;
}

.section-header {
    @apply mb-4;
}

.color-selection-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.color-grid {
    @apply grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.color-option {
    @apply flex cursor-pointer items-center gap-3 rounded-lg border-2 border-gray-200 bg-white p-3 transition-all duration-200 hover:border-blue-300 dark:border-gray-600 dark:bg-gray-800;
}

.color-option--selected {
    @apply border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20;
}

.color-swatch {
    @apply h-8 w-8 flex-shrink-0 rounded border border-gray-200;
}

.color-info {
    @apply min-w-0 flex-1;
}

.color-selection-indicator {
    @apply flex-shrink-0;
}

.empty-state {
    @apply flex flex-col items-center justify-center py-8 text-center;
}

.typography-selection-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.font-preview {
    @apply mt-4 space-y-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-600 dark:bg-gray-800;
}

.font-sample {
    @apply space-y-2;
}

.logo-selection-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.logo-grid {
    @apply grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3;
}

.logo-option {
    @apply flex cursor-pointer items-center gap-3 rounded-lg border-2 border-gray-200 bg-white p-4 transition-all duration-200 hover:border-blue-300 dark:border-gray-600 dark:bg-gray-800;
}

.logo-option--selected {
    @apply border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20;
}

.logo-preview {
    @apply flex h-16 w-16 items-center justify-center rounded bg-gray-100 dark:bg-gray-600;
}

.logo-info {
    @apply min-w-0 flex-1;
}

.logo-selection-indicator {
    @apply flex-shrink-0;
}

.template-settings-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.settings-grid {
    @apply space-y-4;
}

.setting-item {
    @apply space-y-1;
}
</style>
