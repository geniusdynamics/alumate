<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    courses: Array,
    templateUrl: String,
});

const form = useForm({
    file: null,
});

const dragOver = ref(false);
const fileInput = ref(null);

const submit = () => {
    form.post(route('graduates.import.preview'));
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        form.file = files[0];
    }
};

const handleFileSelect = (e) => {
    form.file = e.target.files[0];
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const removeFile = () => {
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const downloadTemplate = () => {
    window.open(route('graduates.import.template'));
};
</script>

<template>
    <Head title="Import Graduates" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Import Graduates</h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('graduates.import.history')"
                        class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400"
                    >
                        Import History
                    </Link>
                    <Link :href="route('graduates.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                        Back to Graduates
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Instructions -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Import Instructions</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-inside list-disc space-y-1">
                                    <li>Download the template file to see the required format</li>
                                    <li>Fill in your graduate data following the template structure</li>
                                    <li>Required fields: Name, Email, Graduation Year, Course Name, Employment Status</li>
                                    <li>Skills should be comma-separated (e.g., "PHP, JavaScript, Vue.js")</li>
                                    <li>Certifications format: "Name|Issuer|Date" separated by semicolons</li>
                                    <li>Maximum file size: 10MB</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Download -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Download Template</h3>
                                <p class="mt-1 text-sm text-gray-600">Get the Excel template with sample data and proper formatting</p>
                            </div>
                            <button
                                @click="downloadTemplate"
                                class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-green-700 focus:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 active:bg-green-900"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Download Template
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Upload Graduate Data</h3>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- File Drop Zone -->
                            <div
                                @drop="handleDrop"
                                @dragover.prevent="dragOver = true"
                                @dragleave="dragOver = false"
                                @click="triggerFileInput"
                                :class="[
                                    'cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors',
                                    dragOver ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400',
                                ]"
                            >
                                <input ref="fileInput" type="file" accept=".xlsx,.xls" @change="handleFileSelect" class="hidden" />

                                <div v-if="!form.file">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <div class="mt-4">
                                        <p class="text-lg font-medium text-gray-900">Drop your Excel file here</p>
                                        <p class="text-sm text-gray-600">or click to browse</p>
                                        <p class="mt-2 text-xs text-gray-500">Supports .xlsx and .xls files up to 10MB</p>
                                    </div>
                                </div>

                                <div v-else class="flex items-center justify-center space-x-4">
                                    <div class="flex items-center space-x-2">
                                        <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ form.file.name }}</p>
                                            <p class="text-sm text-gray-500">{{ (form.file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                        </div>
                                    </div>
                                    <button type="button" @click.stop="removeFile" class="text-red-600 hover:text-red-800">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Error Display -->
                            <div v-if="form.errors.file" class="text-sm text-red-600">
                                {{ form.errors.file }}
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="!form.file || form.processing"
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-900 disabled:opacity-50"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="-ml-1 mr-3 h-4 w-4 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    {{ form.processing ? 'Processing...' : 'Preview Import' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Available Courses -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Available Courses</h3>
                        <p class="mb-4 text-sm text-gray-600">Make sure your Excel file uses these exact course names:</p>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                            <span
                                v-for="course in courses"
                                :key="course.id"
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-800"
                            >
                                {{ course.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>













