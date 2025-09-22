<template>
    <div class="theme-manager">
        <!-- Header -->
        <div class="theme-manager-header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Theme Manager</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage themes compatible with GrapeJS page builder</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showImportModal = true" class="btn-secondary">
                        <Icon name="upload" class="mr-2 h-4 w-4" />
                        Import Theme
                    </button>
                    <button @click="createNewTheme" class="btn-primary">
                        <Icon name="plus" class="mr-2 h-4 w-4" />
                        New Theme
                    </button>
                </div>
            </div>
        </div>

        <!-- Theme Grid -->
        <div class="theme-grid">
            <div
                v-for="theme in themes"
                :key="theme.id"
                class="theme-card"
                :class="{ 'theme-card--active': selectedTheme?.id === theme.id }"
                @click="selectTheme(theme)"
            >
                <div class="theme-card__preview">
                    <div class="theme-preview" :style="getThemePreviewStyle(theme)">
                        <div class="preview-header" :style="{ backgroundColor: theme.cssVariables['--theme-color-primary'] }">
                            <div class="preview-title">{{ theme.name }}</div>
                        </div>
                        <div class="preview-content">
                            <div class="preview-text" :style="{ color: theme.cssVariables['--theme-color-text'] }">
                                Sample content with theme colors
                            </div>
                            <div
                                class="preview-button"
                                :style="{
                                    backgroundColor: theme.cssVariables['--theme-color-primary'],
                                    borderRadius: theme.cssVariables['--theme-border-radius'],
                                }"
                            >
                                Button
                            </div>
                        </div>
                    </div>
                </div>

                <div class="theme-card__info">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ theme.name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ theme.isDefault ? 'Default Theme' : 'Custom Theme' }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button @click.stop="editTheme(theme)" class="btn-icon" title="Edit Theme">
                                <Icon name="edit" class="h-4 w-4" />
                            </button>
                            <button @click.stop="duplicateTheme(theme)" class="btn-icon" title="Duplicate Theme">
                                <Icon name="copy" class="h-4 w-4" />
                            </button>
                            <button
                                v-if="!theme.isDefault"
                                @click.stop="deleteTheme(theme)"
                                class="btn-icon text-red-600 hover:text-red-700"
                                title="Delete Theme"
                            >
                                <Icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Compatibility Status -->
                    <div class="mt-3">
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full" :class="getCompatibilityStatusClass(theme)"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                {{ getCompatibilityStatus(theme) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Editor Modal -->
        <ThemeEditorModal v-if="showEditor" :theme="editingTheme" :is-new="isNewTheme" @save="handleThemeSave" @cancel="closeEditor" />

        <!-- Import Modal -->
        <ThemeImportModal v-if="showImportModal" @import="handleThemeImport" @cancel="showImportModal = false" />

        <!-- Theme Preview Modal -->
        <ThemePreviewModal v-if="showPreview" :theme="previewTheme" @close="showPreview = false" @apply="applyTheme" />
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Common/Icon.vue';
import { useNotifications } from '@/composables/useNotifications';
import type { GrapeJSThemeData } from '@/types/components';
import { computed, onMounted, ref } from 'vue';
import ThemeEditorModal from './ThemeEditorModal.vue';
import ThemeImportModal from './ThemeImportModal.vue';
import ThemePreviewModal from './ThemePreviewModal.vue';

interface Props {
    initialThemes?: GrapeJSThemeData[];
    selectedThemeId?: number;
}

const props = withDefaults(defineProps<Props>(), {
    initialThemes: () => [],
    selectedThemeId: undefined,
});

const emit = defineEmits<{
    themeSelected: [theme: GrapeJSThemeData];
    themeUpdated: [theme: GrapeJSThemeData];
}>();

// State
const themes = ref<GrapeJSThemeData[]>(props.initialThemes);
const selectedTheme = ref<GrapeJSThemeData | null>(null);
const showEditor = ref(false);
const showImportModal = ref(false);
const showPreview = ref(false);
const editingTheme = ref<GrapeJSThemeData | null>(null);
const previewTheme = ref<GrapeJSThemeData | null>(null);
const isNewTheme = ref(false);
const loading = ref(false);

const { showNotification } = useNotifications();

// Computed
const defaultTheme = computed(() => themes.value.find((theme) => theme.isDefault));

// Methods
const loadThemes = async () => {
    try {
        loading.value = true;
        const response = await fetch('/api/component-themes/grapejs');
        const data = await response.json();
        themes.value = data.themes;

        // Select initial theme
        if (props.selectedThemeId) {
            selectedTheme.value = themes.value.find((t) => t.id === props.selectedThemeId) || null;
        } else if (defaultTheme.value) {
            selectedTheme.value = defaultTheme.value;
        }
    } catch (error) {
        console.error('Failed to load themes:', error);
        showNotification('Failed to load themes', 'error');
    } finally {
        loading.value = false;
    }
};

const selectTheme = (theme: GrapeJSThemeData) => {
    selectedTheme.value = theme;
    emit('themeSelected', theme);
};

const createNewTheme = () => {
    editingTheme.value = null;
    isNewTheme.value = true;
    showEditor.value = true;
};

const editTheme = (theme: GrapeJSThemeData) => {
    editingTheme.value = theme;
    isNewTheme.value = false;
    showEditor.value = true;
};

const duplicateTheme = async (theme: GrapeJSThemeData) => {
    try {
        const response = await fetch(`/api/component-themes/${theme.id}/duplicate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const newTheme = await response.json();
            themes.value.push(newTheme);
            showNotification('Theme duplicated successfully', 'success');
        }
    } catch (error) {
        console.error('Failed to duplicate theme:', error);
        showNotification('Failed to duplicate theme', 'error');
    }
};

const deleteTheme = async (theme: GrapeJSThemeData) => {
    if (!confirm(`Are you sure you want to delete "${theme.name}"?`)) {
        return;
    }

    try {
        const response = await fetch(`/api/component-themes/${theme.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            themes.value = themes.value.filter((t) => t.id !== theme.id);
            if (selectedTheme.value?.id === theme.id) {
                selectedTheme.value = defaultTheme.value || null;
            }
            showNotification('Theme deleted successfully', 'success');
        }
    } catch (error) {
        console.error('Failed to delete theme:', error);
        showNotification('Failed to delete theme', 'error');
    }
};

const handleThemeSave = async (themeData: any) => {
    try {
        const url = isNewTheme.value ? '/api/component-themes' : `/api/component-themes/${editingTheme.value?.id}`;
        const method = isNewTheme.value ? 'POST' : 'PUT';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(themeData),
        });

        if (response.ok) {
            const savedTheme = await response.json();

            if (isNewTheme.value) {
                themes.value.push(savedTheme);
            } else {
                const index = themes.value.findIndex((t) => t.id === savedTheme.id);
                if (index !== -1) {
                    themes.value[index] = savedTheme;
                }
            }

            emit('themeUpdated', savedTheme);
            showNotification(`Theme ${isNewTheme.value ? 'created' : 'updated'} successfully`, 'success');
            closeEditor();
        }
    } catch (error) {
        console.error('Failed to save theme:', error);
        showNotification('Failed to save theme', 'error');
    }
};

const handleThemeImport = async (importData: any) => {
    try {
        const response = await fetch('/api/component-themes/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(importData),
        });

        if (response.ok) {
            const importedTheme = await response.json();
            themes.value.push(importedTheme);
            showNotification('Theme imported successfully', 'success');
            showImportModal.value = false;
        }
    } catch (error) {
        console.error('Failed to import theme:', error);
        showNotification('Failed to import theme', 'error');
    }
};

const closeEditor = () => {
    showEditor.value = false;
    editingTheme.value = null;
    isNewTheme.value = false;
};

const applyTheme = async (theme: GrapeJSThemeData) => {
    try {
        const response = await fetch(`/api/component-themes/${theme.id}/apply`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            selectTheme(theme);
            showNotification('Theme applied successfully', 'success');
            showPreview.value = false;
        }
    } catch (error) {
        console.error('Failed to apply theme:', error);
        showNotification('Failed to apply theme', 'error');
    }
};

const getThemePreviewStyle = (theme: GrapeJSThemeData) => {
    return {
        backgroundColor: theme.cssVariables['--theme-color-background'] || '#ffffff',
        fontFamily: theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
    };
};

const getCompatibilityStatus = (theme: GrapeJSThemeData) => {
    const issues = theme.accessibility || [];
    if (issues.length === 0) {
        return 'GrapeJS Compatible';
    } else if (issues.length <= 2) {
        return 'Minor Issues';
    } else {
        return 'Compatibility Issues';
    }
};

const getCompatibilityStatusClass = (theme: GrapeJSThemeData) => {
    const issues = theme.accessibility || [];
    if (issues.length === 0) {
        return 'bg-green-500';
    } else if (issues.length <= 2) {
        return 'bg-yellow-500';
    } else {
        return 'bg-red-500';
    }
};

// Lifecycle
onMounted(() => {
    if (props.initialThemes.length === 0) {
        loadThemes();
    } else {
        if (props.selectedThemeId) {
            selectedTheme.value = themes.value.find((t) => t.id === props.selectedThemeId) || null;
        }
    }
});
</script>

<style scoped>
.theme-manager {
    @apply rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800;
}

.theme-manager-header {
    @apply mb-6 border-b border-gray-200 pb-4 dark:border-gray-700;
}

.theme-grid {
    @apply grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3;
}

.theme-card {
    @apply cursor-pointer rounded-lg border-2 border-transparent bg-gray-50 transition-all duration-200 hover:border-blue-200 dark:bg-gray-700 dark:hover:border-blue-600;
}

.theme-card--active {
    @apply border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20;
}

.theme-card__preview {
    @apply p-4;
}

.theme-preview {
    @apply h-32 overflow-hidden rounded-md border border-gray-200 dark:border-gray-600;
}

.preview-header {
    @apply flex h-8 items-center px-3;
}

.preview-title {
    @apply truncate text-xs font-medium text-white;
}

.preview-content {
    @apply space-y-2 p-3;
}

.preview-text {
    @apply text-xs;
}

.preview-button {
    @apply rounded px-2 py-1 text-center text-xs text-white;
}

.theme-card__info {
    @apply p-4 pt-0;
}

.btn-primary {
    @apply flex items-center rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700;
}

.btn-secondary {
    @apply flex items-center rounded-md bg-gray-100 px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.btn-icon {
    @apply rounded-md p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white;
}
</style>

















