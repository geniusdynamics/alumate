<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('cancel')"></div>

            <!-- Modal panel -->
            <div
                class="my-8 inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ isNew ? 'Add New Color' : 'Edit Color' }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ isNew ? 'Add a new color to your brand palette' : 'Update color properties and settings' }}
                        </p>
                    </div>
                    <button @click="$emit('cancel')" class="btn-icon">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit">
                    <div class="space-y-6">
                        <!-- Color Preview -->
                        <div class="color-preview-section">
                            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Preview </label>
                            <div class="flex items-center gap-4">
                                <div class="color-preview-large" :style="{ backgroundColor: form.value }"></div>
                                <div class="color-info">
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ form.name || 'Untitled Color' }}
                                    </p>
                                    <p class="font-mono text-sm text-gray-600 dark:text-gray-400">
                                        {{ form.value }}
                                    </p>
                                    <div class="color-formats mt-2">
                                        <div class="space-y-1 text-xs text-gray-500">
                                            <div>HEX: {{ hexValue }}</div>
                                            <div>RGB: {{ rgbValue }}</div>
                                            <div>HSL: {{ hslValue }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Name * </label>
                                <input v-model="form.name" type="text" required class="form-input" placeholder="e.g., Primary Blue" />
                                <p class="mt-1 text-xs text-gray-500">A descriptive name for this color</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Type </label>
                                <select v-model="form.type" class="form-select">
                                    <option value="primary">Primary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="accent">Accent</option>
                                    <option value="neutral">Neutral</option>
                                    <option value="semantic">Semantic</option>
                                </select>
                            </div>
                        </div>

                        <!-- Color Value Input -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Color Value * </label>
                            <div class="color-input-group">
                                <div class="color-picker-wrapper">
                                    <input v-model="form.value" type="color" class="color-picker" @input="updateColorFromPicker" />
                                </div>
                                <input
                                    v-model="form.value"
                                    type="text"
                                    required
                                    class="form-input flex-1"
                                    placeholder="#3B82F6"
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    @input="validateColorValue"
                                />
                                <button type="button" @click="generateRandomColor" class="btn-secondary" title="Generate Random Color">
                                    <Icon name="refresh" class="h-4 w-4" />
                                </button>
                            </div>
                            <div v-if="colorError" class="mt-1 text-xs text-red-600">
                                {{ colorError }}
                            </div>
                        </div>

                        <!-- Color Palette Suggestions -->
                        <div v-if="isNew">
                            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"> Suggested Colors </label>
                            <div class="color-suggestions">
                                <button
                                    v-for="suggestion in colorSuggestions"
                                    :key="suggestion.value"
                                    type="button"
                                    class="color-suggestion"
                                    :style="{ backgroundColor: suggestion.value }"
                                    @click="applySuggestion(suggestion)"
                                    :title="suggestion.name"
                                ></button>
                            </div>
                        </div>

                        <!-- Accessibility Check -->
                        <div class="accessibility-section">
                            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"> Accessibility Check </label>
                            <div class="contrast-checks">
                                <div v-for="check in contrastChecks" :key="check.background" class="contrast-check-item">
                                    <div class="contrast-preview">
                                        <div
                                            class="contrast-sample"
                                            :style="{
                                                backgroundColor: check.background,
                                                color: form.value,
                                            }"
                                        >
                                            Aa
                                        </div>
                                    </div>
                                    <div class="contrast-info">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">On {{ check.name }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Ratio: {{ check.ratio.toFixed(2) }}:1</p>
                                    </div>
                                    <div class="contrast-status">
                                        <span class="status-badge" :class="getContrastStatusClass(check.level)">
                                            {{ check.level }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usage Guidelines -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Usage Guidelines (Optional) </label>
                            <textarea
                                v-model="form.usageGuidelines"
                                class="form-textarea"
                                rows="3"
                                placeholder="Describe when and how this color should be used..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <button type="button" @click="$emit('cancel')" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="!isFormValid">
                            {{ isNew ? 'Add Color' : 'Update Color' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Common/Icon.vue';
<<<<<<< HEAD:resources/js/components/ComponentLibrary/Theme/ColorEditorModal.vue
import type { BrandColor } from '@/Types/components';
=======
import type { BrandColor } from '@/types/Components';
>>>>>>> origin/db1:resources/js/Components/ComponentLibrary/Theme/ColorEditorModal.vue
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    color?: BrandColor | null;
    isNew: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    color: null,
});

const emit = defineEmits<{
    save: [colorData: any];
    cancel: [];
}>();

// Form state
const form = ref({
    name: '',
    value: '#3B82F6',
    type: 'primary' as const,
    usageGuidelines: '',
});

const colorError = ref('');

// Color suggestions for new colors
const colorSuggestions = ref([
    { name: 'Blue', value: '#3B82F6' },
    { name: 'Green', value: '#10B981' },
    { name: 'Purple', value: '#8B5CF6' },
    { name: 'Red', value: '#EF4444' },
    { name: 'Yellow', value: '#F59E0B' },
    { name: 'Pink', value: '#EC4899' },
    { name: 'Indigo', value: '#6366F1' },
    { name: 'Gray', value: '#6B7280' },
]);

// Computed properties
const hexValue = computed(() => {
    return form.value.toUpperCase();
});

const rgbValue = computed(() => {
    const hex = form.value.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    return `rgb(${r}, ${g}, ${b})`;
});

const hslValue = computed(() => {
    const hex = form.value.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

        switch (max) {
            case r:
                h = (g - b) / d + (g < b ? 6 : 0);
                break;
            case g:
                h = (b - r) / d + 2;
                break;
            case b:
                h = (r - g) / d + 4;
                break;
        }
        h /= 6;
    }

    return `hsl(${Math.round(h * 360)}, ${Math.round(s * 100)}%, ${Math.round(l * 100)}%)`;
});

const contrastChecks = computed(() => {
    const backgrounds = [
        { name: 'White', value: '#FFFFFF' },
        { name: 'Light Gray', value: '#F3F4F6' },
        { name: 'Dark Gray', value: '#374151' },
        { name: 'Black', value: '#000000' },
    ];

    return backgrounds.map((bg) => {
        const ratio = calculateContrastRatio(form.value, bg.value);
        let level = 'Fail';

        if (ratio >= 7) level = 'AAA';
        else if (ratio >= 4.5) level = 'AA';
        else if (ratio >= 3) level = 'AA Large';

        return {
            name: bg.name,
            background: bg.value,
            ratio,
            level,
        };
    });
});

const isFormValid = computed(() => {
    return form.value.name.trim() !== '' && form.value.value.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/) && !colorError.value;
});

// Methods
const calculateContrastRatio = (color1: string, color2: string): number => {
    const getLuminance = (hex: string): number => {
        const rgb = hex.replace('#', '').match(/.{2}/g);
        if (!rgb) return 0;

        const [r, g, b] = rgb.map((x) => {
            const val = parseInt(x, 16) / 255;
            return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
        });

        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    const lum1 = getLuminance(color1);
    const lum2 = getLuminance(color2);
    const brightest = Math.max(lum1, lum2);
    const darkest = Math.min(lum1, lum2);

    return (brightest + 0.05) / (darkest + 0.05);
};

const validateColorValue = () => {
    const value = form.value.value;
    if (!value.match(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/)) {
        colorError.value = 'Please enter a valid hex color (e.g., #3B82F6)';
    } else {
        colorError.value = '';
    }
};

const updateColorFromPicker = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.value.value = target.value;
    colorError.value = '';
};

const generateRandomColor = () => {
    const colors = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += colors[Math.floor(Math.random() * colors.length)];
    }
    form.value.value = color;
    colorError.value = '';
};

const applySuggestion = (suggestion: { name: string; value: string }) => {
    form.value.value = suggestion.value;
    if (!form.value.name) {
        form.value.name = suggestion.name;
    }
    colorError.value = '';
};

const getContrastStatusClass = (level: string): string => {
    switch (level) {
        case 'AAA':
            return 'bg-green-100 text-green-800';
        case 'AA':
            return 'bg-blue-100 text-blue-800';
        case 'AA Large':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-red-100 text-red-800';
    }
};

const handleSubmit = () => {
    if (!isFormValid.value) return;

    const colorData = {
        name: form.value.name.trim(),
        value: form.value.value,
        type: form.value.type,
        usageGuidelines: form.value.usageGuidelines.trim() || undefined,
        accessibility: {
            wcagCompliant: contrastChecks.value.some((check) => check.level === 'AA' || check.level === 'AAA'),
            contrastIssues: contrastChecks.value
                .filter((check) => check.level === 'Fail')
                .map((check) => `Poor contrast on ${check.name} background`),
        },
    };

    emit('save', colorData);
};

// Initialize form with existing color data
onMounted(() => {
    if (props.color) {
        form.value = {
            name: props.color.name,
            value: props.color.value,
            type: props.color.type,
            usageGuidelines: props.color.usageGuidelines || '',
        };
    }
});

// Watch for color prop changes
watch(
    () => props.color,
    (newColor) => {
        if (newColor) {
            form.value = {
                name: newColor.name,
                value: newColor.value,
                type: newColor.type,
                usageGuidelines: newColor.usageGuidelines || '',
            };
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

.color-preview-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.color-preview-large {
    @apply h-24 w-24 flex-shrink-0 rounded-lg border-4 border-white shadow-lg;
}

.color-info {
    @apply flex-1;
}

.color-formats {
    @apply mt-2;
}

.color-input-group {
    @apply flex items-center gap-3;
}

.color-picker-wrapper {
    @apply relative;
}

.color-picker {
    @apply h-10 w-12 cursor-pointer rounded-md border-2 border-gray-300 dark:border-gray-600;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background: none;
}

.color-picker::-webkit-color-swatch-wrapper {
    padding: 0;
}

.color-picker::-webkit-color-swatch {
    border: none;
    border-radius: 6px;
}

.color-suggestions {
    @apply flex flex-wrap gap-2;
}

.color-suggestion {
    @apply h-8 w-8 cursor-pointer rounded-md border-2 border-white shadow-sm transition-transform duration-200 hover:scale-110;
}

.accessibility-section {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.contrast-checks {
    @apply space-y-3;
}

.contrast-check-item {
    @apply flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800;
}

.contrast-preview {
    @apply flex-shrink-0;
}

.contrast-sample {
    @apply flex h-12 w-12 items-center justify-center rounded-md border border-gray-200 text-lg font-bold;
}

.contrast-info {
    @apply flex-1;
}

.contrast-status {
    @apply flex-shrink-0;
}

.status-badge {
    @apply rounded-full px-2 py-1 text-xs font-medium;
}
</style>















