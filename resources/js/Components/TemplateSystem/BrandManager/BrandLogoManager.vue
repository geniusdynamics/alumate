<script setup lang="ts">
import { ref } from 'vue';
import { useBrandStore } from '@/Stores/brand';

const brandStore = useBrandStore();
const showUpload = ref(false);
const selectedFile = ref<File | null>(null);
const logoName = ref('');
const logoType = ref<'primary' | 'secondary' | 'favicon' | 'social'>('primary');

function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.[0]) {
        selectedFile.value = input.files[0];
    }
}

async function uploadLogo() {
    if (!selectedFile.value) return;

    const formData = new FormData();
    formData.append('logo', selectedFile.value);
    formData.append('name', logoName.value || selectedFile.value.name);
    formData.append('type', logoType.value);

    await brandStore.uploadLogo(formData);
    showUpload.value = false;
    selectedFile.value = null;
    logoName.value = '';
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Brand Logos</h2>
            <button
                @click="showUpload = true"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
                Upload Logo
            </button>
        </div>

        <!-- Upload Modal -->
        <div v-if="showUpload" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upload Logo</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="logoName" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Logo name" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select v-model="logoType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="favicon">Favicon</option>
                            <option value="social">Social</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File</label>
                        <input type="file" accept="image/*" @change="handleFileSelect" class="w-full text-sm" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showUpload = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">Cancel</button>
                    <button @click="uploadLogo" :disabled="!selectedFile" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">Upload</button>
                </div>
            </div>
        </div>

        <!-- Logo Grid -->
        <div v-if="brandStore.logos.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            No logos uploaded yet. Upload your first logo to get started.
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
                v-for="logo in brandStore.logos"
                :key="logo.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
            >
                <img :src="logo.url" :alt="logo.name" class="w-full h-24 object-contain mb-3" />
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ logo.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ logo.type }}</p>
                    </div>
                    <div class="flex gap-1">
                        <button
                            v-if="!logo.is_primary"
                            @click="brandStore.setPrimaryLogo(logo.id)"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800"
                        >
                            Set Primary
                        </button>
                        <button
                            @click="brandStore.deleteLogo(logo.id)"
                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-800"
                        >
                            Delete
                        </button>
                    </div>
                </div>
                <span v-if="logo.is_primary" class="mt-2 inline-block px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                    Primary
                </span>
            </div>
        </div>
    </div>
</template>
