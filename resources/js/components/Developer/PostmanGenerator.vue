<template>
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Postman Collection</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Generate and download Postman collections for API testing</p>
                </div>
                <img src="https://www.postman.com/assets/logos/postman-logo-horizontal-orange.svg" alt="Postman" class="h-8" />
            </div>
        </div>

        <div class="p-6">
            <!-- Collection Configuration -->
            <div class="mb-6 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Collection Name </label>
                    <input
                        v-model="config.name"
                        type="text"
                        placeholder="Alumni Platform API"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Base URL </label>
                    <input
                        v-model="config.baseUrl"
                        type="url"
                        placeholder="https://your-domain.com/api"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Description </label>
                    <textarea
                        v-model="config.description"
                        rows="3"
                        placeholder="API collection for the Alumni Platform..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                    ></textarea>
                </div>
            </div>

            <!-- Environment Variables -->
            <div class="mb-6">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Environment Variables</h4>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"> Include environment variables </span>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input v-model="config.includeEnvironment" type="checkbox" class="peer sr-only" />
                                <div
                                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-blue-800"
                                ></div>
                            </label>
                        </div>

                        <div v-if="config.includeEnvironment" class="space-y-2">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Environment variables will be included for easy configuration:</div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <div class="font-mono text-blue-600 dark:text-blue-400">{{ baseUrl }}</div>
                                    <div class="font-mono text-blue-600 dark:text-blue-400">{{ apiToken }}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-gray-600 dark:text-gray-400">API base URL</div>
                                    <div class="text-gray-600 dark:text-gray-400">Authentication token</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Endpoint Selection -->
            <div class="mb-6">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Include Endpoints</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Select all</span>
                        <button
                            @click="toggleAllEndpoints"
                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                        >
                            {{ allSelected ? 'Deselect All' : 'Select All' }}
                        </button>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <div v-for="category in endpointCategories" :key="category.id" class="space-y-2">
                            <label class="flex items-center space-x-2 font-medium">
                                <input
                                    v-model="config.selectedCategories"
                                    :value="category.id"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ category.name }} ({{ category.endpoints.length }} endpoints)
                                </span>
                            </label>

                            <!-- Individual endpoints -->
                            <div v-if="config.selectedCategories.includes(category.id)" class="ml-6 space-y-1">
                                <label
                                    v-for="endpoint in category.endpoints"
                                    :key="`${endpoint.method}-${endpoint.path}`"
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <input
                                        v-model="config.selectedEndpoints"
                                        :value="`${category.id}.${endpoint.method}.${endpoint.path}`"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span
                                        :class="['inline-flex items-center rounded px-2 py-0.5 text-xs font-medium', getMethodColor(endpoint.method)]"
                                    >
                                        {{ endpoint.method }}
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ endpoint.path }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="mb-6">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Advanced Options</h4>
                <div class="space-y-3">
                    <label class="flex items-center space-x-2">
                        <input v-model="config.includeTests" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-700 dark:text-gray-300"> Include basic response tests </span>
                    </label>

                    <label class="flex items-center space-x-2">
                        <input v-model="config.includeExamples" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-700 dark:text-gray-300"> Include request/response examples </span>
                    </label>

                    <label class="flex items-center space-x-2">
                        <input v-model="config.includeAuth" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm text-gray-700 dark:text-gray-300"> Include authentication setup </span>
                    </label>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span v-if="selectedEndpointCount > 0"> {{ selectedEndpointCount }} endpoints selected </span>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="previewCollection"
                        :disabled="selectedEndpointCount === 0"
                        class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Preview
                    </button>
                    <button
                        @click="generateCollection"
                        :disabled="selectedEndpointCount === 0 || generating"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="generating">Generating...</span>
                        <span v-else>Download Collection</span>
                    </button>
                </div>
            </div>

            <!-- Preview Modal -->
            <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="mx-4 max-h-[80vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Collection Preview</h3>
                            <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto p-6">
                        <pre
                            class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-900 dark:text-gray-200"
                        ><code>{{ previewData }}</code></pre>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 p-6 dark:border-gray-700">
                        <button
                            @click="showPreview = false"
                            class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                        >
                            Close
                        </button>
                        <button
                            @click="downloadFromPreview"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Download
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { apiEndpoints } from '@/Data/apiDocumentation.js';
import { computed, reactive, ref } from 'vue';

const generating = ref(false);
const showPreview = ref(false);
const previewData = ref('');

const config = reactive({
    name: 'Alumni Platform API',
    baseUrl: 'https://your-domain.com/api',
    description: 'Complete API collection for the Alumni Platform with authentication, endpoints, and examples.',
    includeEnvironment: true,
    includeTests: true,
    includeExamples: true,
    includeAuth: true,
    selectedCategories: [],
    selectedEndpoints: [],
});

const endpointCategories = computed(() => {
    return Object.entries(apiEndpoints).map(([id, category]) => ({
        id,
        name: category.name,
        description: category.description,
        endpoints: category.endpoints,
    }));
});

const selectedEndpointCount = computed(() => {
    return config.selectedEndpoints.length;
});

const allSelected = computed(() => {
    const totalEndpoints = endpointCategories.value.reduce((total, category) => {
        return total + category.endpoints.length;
    }, 0);
    return config.selectedEndpoints.length === totalEndpoints;
});

const toggleAllEndpoints = () => {
    if (allSelected.value) {
        config.selectedCategories = [];
        config.selectedEndpoints = [];
    } else {
        config.selectedCategories = endpointCategories.value.map((cat) => cat.id);
        config.selectedEndpoints = [];
        endpointCategories.value.forEach((category) => {
            category.endpoints.forEach((endpoint) => {
                config.selectedEndpoints.push(`${category.id}.${endpoint.method}.${endpoint.path}`);
            });
        });
    }
};

const getMethodColor = (method) => {
    const colors = {
        GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        PATCH: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[method] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};

const generatePostmanCollection = () => {
    const collection = {
        info: {
            name: config.name,
            description: config.description,
            schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        },
        auth: config.includeAuth
            ? {
                  type: 'bearer',
                  bearer: [
                      {
                          key: 'token',
                          value: '{{apiToken}}',
                          type: 'string',
                      },
                  ],
              }
            : undefined,
        variable: config.includeEnvironment
            ? [
                  {
                      key: 'baseUrl',
                      value: config.baseUrl,
                      type: 'string',
                  },
                  {
                      key: 'apiToken',
                      value: 'your-api-token-here',
                      type: 'string',
                  },
              ]
            : undefined,
        item: [],
    };

    // Add folders for each category
    config.selectedCategories.forEach((categoryId) => {
        const category = apiEndpoints[categoryId];
        if (!category) return;

        const folder = {
            name: category.name,
            description: category.description,
            item: [],
        };

        // Add endpoints to folder
        category.endpoints.forEach((endpoint) => {
            const endpointKey = `${categoryId}.${endpoint.method}.${endpoint.path}`;
            if (!config.selectedEndpoints.includes(endpointKey)) return;

            const request = {
                name: `${endpoint.method} ${endpoint.path}`,
                request: {
                    method: endpoint.method,
                    header: [
                        {
                            key: 'Accept',
                            value: 'application/json',
                            type: 'text',
                        },
                    ],
                    url: {
                        raw: `{{baseUrl}}${endpoint.path}`,
                        host: ['{{baseUrl}}'],
                        path: endpoint.path.split('/').filter((p) => p),
                    },
                    description: endpoint.description,
                },
            };

            // Add request body for POST/PUT/PATCH
            if (['POST', 'PUT', 'PATCH'].includes(endpoint.method) && endpoint.requestBody) {
                request.request.header.push({
                    key: 'Content-Type',
                    value: endpoint.requestBody.contentType || 'application/json',
                    type: 'text',
                });

                if (config.includeExamples && endpoint.requestBody.example) {
                    request.request.body = {
                        mode: 'raw',
                        raw: JSON.stringify(endpoint.requestBody.example, null, 2),
                        options: {
                            raw: {
                                language: 'json',
                            },
                        },
                    };
                }
            }

            // Add basic tests
            if (config.includeTests) {
                request.event = [
                    {
                        listen: 'test',
                        script: {
                            exec: [
                                'pm.test("Status code is successful", function () {',
                                '    pm.expect(pm.response.code).to.be.oneOf([200, 201, 202, 204]);',
                                '});',
                                '',
                                'pm.test("Response has success field", function () {',
                                '    const jsonData = pm.response.json();',
                                '    pm.expect(jsonData).to.have.property("success");',
                                '});',
                            ],
                            type: 'text/javascript',
                        },
                    },
                ];
            }

            folder.item.push(request);
        });

        if (folder.item.length > 0) {
            collection.item.push(folder);
        }
    });

    return collection;
};

const previewCollection = () => {
    const collection = generatePostmanCollection();
    previewData.value = JSON.stringify(collection, null, 2);
    showPreview.value = true;
};

const generateCollection = async () => {
    generating.value = true;

    try {
        // Simulate generation time
        await new Promise((resolve) => setTimeout(resolve, 1000));

        const collection = generatePostmanCollection();
        downloadCollection(collection);
    } finally {
        generating.value = false;
    }
};

const downloadFromPreview = () => {
    const collection = JSON.parse(previewData.value);
    downloadCollection(collection);
    showPreview.value = false;
};

const downloadCollection = (collection) => {
    const blob = new Blob([JSON.stringify(collection, null, 2)], {
        type: 'application/json',
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${config.name.replace(/\s+/g, '-').toLowerCase()}.postman_collection.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>
