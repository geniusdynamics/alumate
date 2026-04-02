<template>
    <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="mb-2 flex items-center gap-3">
                        <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getMethodColor(endpoint.method)]">
                            {{ endpoint.method }}
                        </span>
                        <code class="rounded bg-gray-100 px-2 py-1 font-mono text-sm text-gray-900 dark:bg-gray-700 dark:text-white">
                            {{ endpoint.path }}
                        </code>
                        <button
                            v-if="endpoint.deprecated"
                            class="inline-flex items-center rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                        >
                            Deprecated
                        </button>
                    </div>
                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ endpoint.description }}
                    </p>

                    <!-- Tags -->
                    <div v-if="endpoint.tags" class="mb-3 flex flex-wrap gap-2">
                        <span
                            v-for="tag in endpoint.tags"
                            :key="tag"
                            class="inline-flex items-center rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <div class="ml-4 flex items-center gap-2">
                    <button
                        @click="toggleExpanded"
                        class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                    >
                        {{ expanded ? 'Collapse' : 'Expand' }}
                    </button>
                    <button @click="tryEndpoint" class="rounded bg-blue-600 px-3 py-1 text-sm font-medium text-white hover:bg-blue-700">
                        Try it
                    </button>
                </div>
            </div>
        </div>

        <!-- Expanded Content -->
        <div v-if="expanded" class="space-y-6 p-6">
            <!-- Authentication -->
            <div v-if="endpoint.auth" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                <h4 class="mb-2 text-sm font-medium text-yellow-800 dark:text-yellow-200">Authentication Required</h4>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    {{ endpoint.auth.description || 'This endpoint requires authentication.' }}
                </p>
            </div>

            <!-- Parameters -->
            <div v-if="endpoint.parameters && endpoint.parameters.length > 0">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Parameters</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Name
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Type
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Required
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Description
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-for="param in endpoint.parameters" :key="param.name">
                                <td class="px-4 py-2 font-mono text-sm text-blue-600 dark:text-blue-400">
                                    {{ param.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ param.type || 'string' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                            param.required
                                                ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        ]"
                                    >
                                        {{ param.required ? 'Required' : 'Optional' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ param.description }}
                                    <span v-if="param.default" class="text-xs text-gray-500 dark:text-gray-400">
                                        (default: {{ param.default }})
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Request Body -->
            <div v-if="endpoint.requestBody">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Request Body</h4>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ endpoint.requestBody.contentType || 'application/json' }}
                        </span>
                        <button
                            @click="copyToClipboard(JSON.stringify(endpoint.requestBody.example, null, 2))"
                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                        >
                            Copy
                        </button>
                    </div>
                    <pre
                        class="overflow-x-auto text-sm text-gray-800 dark:text-gray-200"
                    ><code>{{ JSON.stringify(endpoint.requestBody.example, null, 2) }}</code></pre>
                </div>
            </div>

            <!-- Response Examples -->
            <div v-if="endpoint.responses">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Response Examples</h4>
                <div class="space-y-4">
                    <div
                        v-for="(response, statusCode) in endpoint.responses"
                        :key="statusCode"
                        class="rounded-lg border border-gray-200 dark:border-gray-600"
                    >
                        <div class="rounded-t-lg border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-600 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span :class="['inline-flex items-center rounded px-2 py-1 text-xs font-medium', getStatusColor(statusCode)]">
                                        {{ statusCode }}
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ response.description }}
                                    </span>
                                </div>
                                <button
                                    @click="copyToClipboard(JSON.stringify(response.example, null, 2))"
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                                >
                                    Copy
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <pre
                                class="overflow-x-auto text-sm text-gray-800 dark:text-gray-200"
                            ><code>{{ JSON.stringify(response.example, null, 2) }}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Code Examples -->
            <div v-if="endpoint.codeExamples">
                <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Code Examples</h4>
                <div class="space-y-4">
                    <div
                        v-for="example in endpoint.codeExamples"
                        :key="example.language"
                        class="rounded-lg border border-gray-200 dark:border-gray-600"
                    >
                        <div class="rounded-t-lg border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-600 dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ example.language }}
                                </span>
                                <button
                                    @click="copyToClipboard(example.code)"
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                                >
                                    Copy
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <pre class="overflow-x-auto text-sm text-gray-800 dark:text-gray-200"><code>{{ example.code }}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/Utils/logger';
import { ref } from 'vue';

const props = defineProps({
    endpoint: {
        type: Object,
        required: true,
    },
});

const expanded = ref(false);

const toggleExpanded = () => {
    expanded.value = !expanded.value;
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

const getStatusColor = (status) => {
    const code = parseInt(status);
    if (code >= 200 && code < 300) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } else if (code >= 400 && code < 500) {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } else if (code >= 500) {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        // Could emit an event or show a toast notification here
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const tryEndpoint = () => {
    // Emit event to parent to open API testing modal
    // This would integrate with the API testing functionality
    logger.log('Try endpoint:', props.endpoint);
};
</script>

