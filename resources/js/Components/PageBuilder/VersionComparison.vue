<template>
    <div class="version-comparison">
        <div class="mb-6">
            <h4 class="mb-4 text-lg font-semibold">Compare Versions</h4>

            <div class="mb-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700"> Version A </label>
                    <select
                        v-model="selectedVersion1"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="version in versions" :key="version.id" :value="version">
                            Version {{ version.version_number }} - {{ version.change_summary || 'No description' }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700"> Version B </label>
                    <select
                        v-model="selectedVersion2"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option v-for="version in versions" :key="version.id" :value="version">
                            Version {{ version.version_number }} - {{ version.change_summary || 'No description' }}
                        </option>
                    </select>
                </div>
            </div>

            <button
                @click="compareVersions"
                :disabled="!selectedVersion1 || !selectedVersion2 || isComparing"
                class="flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <Icon v-if="isComparing" name="spinner" class="h-4 w-4 animate-spin" />
                <Icon v-else name="compare" class="h-4 w-4" />
                {{ isComparing ? 'Comparing...' : 'Compare' }}
            </button>
        </div>

        <div v-if="comparison" class="comparison-results">
            <div class="mb-6 grid grid-cols-2 gap-6">
                <!-- Version A Details -->
                <div class="rounded-lg bg-gray-50 p-4">
                    <h5 class="mb-3 font-semibold text-gray-900">Version {{ comparison.version_1.number }}</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span>{{ formatDate(comparison.version_1.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Creator:</span>
                            <span>{{ comparison.version_1.creator }}</span>
                        </div>
                    </div>
                </div>

                <!-- Version B Details -->
                <div class="rounded-lg bg-gray-50 p-4">
                    <h5 class="mb-3 font-semibold text-gray-900">Version {{ comparison.version_2.number }}</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span>{{ formatDate(comparison.version_2.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Creator:</span>
                            <span>{{ comparison.version_2.creator }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Differences -->
            <div class="differences">
                <h5 class="mb-3 font-semibold text-gray-900">Differences</h5>

                <div v-if="Object.keys(comparison.differences).length === 0" class="py-8 text-center">
                    <Icon name="check-circle" class="mx-auto mb-3 h-12 w-12 text-green-500" />
                    <p class="text-gray-600">No differences found</p>
                    <p class="text-sm text-gray-500">These versions are identical</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="(difference, key) in comparison.differences" :key="key" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <Icon name="alert-triangle" class="h-5 w-5 text-yellow-600" />
                            <span class="font-medium capitalize text-yellow-800">{{ key }} Changed</span>
                        </div>
                        <p class="text-sm text-yellow-700">{{ difference }}</p>
                    </div>
                </div>
            </div>

            <!-- Side-by-side Preview -->
            <div class="mt-8">
                <h5 class="mb-3 font-semibold text-gray-900">Visual Comparison</h5>

                <div class="grid grid-cols-2 gap-4">
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">Version {{ comparison.version_1.number }}</div>
                        <div class="min-h-64 bg-white p-4">
                            <div v-html="renderPreview(comparison.version_1.data)" class="version-preview"></div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">Version {{ comparison.version_2.number }}</div>
                        <div class="min-h-64 bg-white p-4">
                            <div v-html="renderPreview(comparison.version_2.data)" class="version-preview"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-3 border-t border-gray-200 pt-6">
                <button
                    @click="rollbackToVersion(selectedVersion1)"
                    class="flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    <Icon name="arrow-left" class="h-4 w-4" />
                    Rollback to Version {{ selectedVersion1?.version_number }}
                </button>

                <button
                    @click="rollbackToVersion(selectedVersion2)"
                    class="flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    <Icon name="arrow-left" class="h-4 w-4" />
                    Rollback to Version {{ selectedVersion2?.version_number }}
                </button>

                <button @click="$emit('close')" class="ml-auto rounded-md bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/ui/Icon.vue';
import { useVersionControl } from '@/Composables/useVersionControl';
import { ref } from 'vue';

interface PageVersion {
    id: number;
    version_number: number;
    change_summary?: string;
    is_published: boolean;
    created_at: string;
    published_at?: string;
    creator: {
        id: number;
        name: string;
        email: string;
    };
    grapejs_data: any;
}

interface VersionComparison {
    version_1: {
        number: number;
        created_at: string;
        creator: string;
        data: any;
    };
    version_2: {
        number: number;
        created_at: string;
        creator: string;
        data: any;
    };
    differences: Record<string, string>;
}

const props = defineProps<{
    versions: PageVersion[];
    selectedVersion?: PageVersion | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const selectedVersion1 = ref<PageVersion | null>(props.selectedVersion || null);
const selectedVersion2 = ref<PageVersion | null>(null);
const comparison = ref<VersionComparison | null>(null);
const isComparing = ref(false);

const { compareVersions: compareVersionsAction, rollbackToVersion } = useVersionControl(props.versions[0]?.page_id || 0);

const compareVersions = async () => {
    if (!selectedVersion1.value || !selectedVersion2.value) return;

    isComparing.value = true;

    try {
        const result = await compareVersionsAction(selectedVersion1.value, selectedVersion2.value);
        comparison.value = result;
    } catch (error) {
        console.error('Error comparing versions:', error);
    } finally {
        isComparing.value = false;
    }
};

const renderPreview = (grapeJSData: any): string => {
    if (!grapeJSData) return '<p class="text-gray-500">No preview available</p>';

    // Simple preview rendering - in production you might want more sophisticated rendering
    const html = grapeJSData.html || '';
    const css = grapeJSData.css || '';

    return `
    <style scoped>
      ${css}
    </style>
    <div class="grapejs-preview">
      ${html}
    </div>
  `;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

// Auto-select the second most recent version if only one is selected
if (selectedVersion1.value && !selectedVersion2.value && props.versions.length > 1) {
    const currentIndex = props.versions.findIndex((v) => v.id === selectedVersion1.value?.id);
    if (currentIndex >= 0 && currentIndex < props.versions.length - 1) {
        selectedVersion2.value = props.versions[currentIndex + 1];
    } else if (currentIndex > 0) {
        selectedVersion2.value = props.versions[currentIndex - 1];
    }
}
</script>

<style scoped>
.version-preview {
    max-height: 300px;
    overflow-y: auto;
    font-size: 0.875rem;
}

.version-preview :deep(*) {
    max-width: 100%;
}

.version-preview :deep(img) {
    max-width: 100%;
    height: auto;
}
</style>
