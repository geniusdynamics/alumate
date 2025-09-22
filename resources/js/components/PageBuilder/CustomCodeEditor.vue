<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('cancel')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-6xl sm:align-middle"
            >
                <!-- Header -->
                <div class="border-b border-gray-200 bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                {{ isNew ? 'Create' : 'Edit' }} {{ codeTypeLabel }} Code
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ isNew ? 'Create a new' : 'Edit the' }} custom {{ codeTypeLabel.toLowerCase() }} code snippet
                            </p>
                        </div>
                        <button
                            @click="$emit('cancel')"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Code Editor -->
                        <div class="lg:col-span-2">
                            <!-- Code Metadata -->
                            <div class="mb-4 space-y-4">
                                <div>
                                    <label for="code-name" class="block text-sm font-medium text-gray-700">Name</label>
                                    <input
                                        id="code-name"
                                        v-model="localCode.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter code name"
                                    />
                                </div>
                                <div>
                                    <label for="code-description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea
                                        id="code-description"
                                        v-model="localCode.description"
                                        rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter code description"
                                    ></textarea>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="localCode.isActive"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Active</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="localCode.isDraft"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Draft</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Code Editor -->
                            <div class="overflow-hidden rounded-lg border border-gray-300">
                                <div class="flex items-center justify-between border-b border-gray-300 bg-gray-50 px-3 py-2">
                                    <div class="flex items-center space-x-2">
                                        <component :is="codeTypeIcon" class="h-4 w-4 text-gray-600" />
                                        <span class="text-sm font-medium text-gray-700">{{ codeTypeLabel }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="formatCode"
                                            class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                                        >
                                            Format
                                        </button>
                                        <button
                                            @click="validateCode"
                                            class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                                        >
                                            Validate
                                        </button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <textarea
                                        ref="codeEditor"
                                        v-model="localCode.code"
                                        :placeholder="getPlaceholder()"
                                        class="h-96 w-full resize-none border-0 p-4 font-mono text-sm focus:outline-none focus:ring-0"
                                        @input="handleCodeChange"
                                    ></textarea>

                                    <!-- Syntax highlighting overlay (simplified) -->
                                    <div
                                        v-if="highlightedCode"
                                        class="pointer-events-none absolute inset-0 whitespace-pre-wrap p-4 font-mono text-sm"
                                        v-html="highlightedCode"
                                    ></div>
                                </div>
                            </div>

                            <!-- Code Actions -->
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">
                                        Lines: {{ codeStats.lines }} | Characters: {{ codeStats.characters }} | Size:
                                        {{ formatBytes(codeStats.bytes) }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button @click="insertTemplate" class="text-sm text-indigo-600 hover:text-indigo-800">Insert Template</button>
                                    <button @click="previewCode" class="text-sm text-indigo-600 hover:text-indigo-800">Preview</button>
                                </div>
                            </div>
                        </div>

                        <!-- Validation & Tools Panel -->
                        <div class="lg:col-span-1">
                            <!-- Validation Results -->
                            <div v-if="validationResults" class="mb-6">
                                <h4 class="mb-3 text-sm font-medium text-gray-900">Validation Results</h4>
                                <div class="space-y-2">
                                    <!-- Errors -->
                                    <div v-if="validationResults.errors.length > 0">
                                        <div class="mb-1 flex items-center text-sm font-medium text-red-600">
                                            <ExclamationTriangleIcon class="mr-1 h-4 w-4" />
                                            Errors ({{ validationResults.errors.length }})
                                        </div>
                                        <div class="space-y-1">
                                            <div
                                                v-for="error in validationResults.errors.slice(0, 3)"
                                                :key="`error-${error.line}-${error.column}`"
                                                class="rounded border border-red-200 bg-red-50 p-2 text-xs text-red-600"
                                            >
                                                Line {{ error.line }}: {{ error.message }}
                                            </div>
                                            <div v-if="validationResults.errors.length > 3" class="text-xs text-gray-500">
                                                +{{ validationResults.errors.length - 3 }} more errors
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Warnings -->
                                    <div v-if="validationResults.warnings.length > 0">
                                        <div class="mb-1 flex items-center text-sm font-medium text-yellow-600">
                                            <ExclamationTriangleIcon class="mr-1 h-4 w-4" />
                                            Warnings ({{ validationResults.warnings.length }})
                                        </div>
                                        <div class="space-y-1">
                                            <div
                                                v-for="warning in validationResults.warnings.slice(0, 3)"
                                                :key="`warning-${warning.line}-${warning.column}`"
                                                class="rounded border border-yellow-200 bg-yellow-50 p-2 text-xs text-yellow-600"
                                            >
                                                Line {{ warning.line }}: {{ warning.message }}
                                            </div>
                                            <div v-if="validationResults.warnings.length > 3" class="text-xs text-gray-500">
                                                +{{ validationResults.warnings.length - 3 }} more warnings
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Security Issues -->
                                    <div v-if="validationResults.securityIssues.length > 0">
                                        <div class="mb-1 flex items-center text-sm font-medium text-red-600">
                                            <ShieldExclamationIcon class="mr-1 h-4 w-4" />
                                            Security Issues ({{ validationResults.securityIssues.length }})
                                        </div>
                                        <div class="space-y-1">
                                            <div
                                                v-for="issue in validationResults.securityIssues.slice(0, 2)"
                                                :key="issue.message"
                                                class="rounded border border-red-200 bg-red-50 p-2 text-xs text-red-600"
                                            >
                                                <div class="font-medium">{{ issue.severity.toUpperCase() }}: {{ issue.message }}</div>
                                                <div v-if="issue.remediation" class="mt-1 text-gray-600">{{ issue.remediation }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Success State -->
                                    <div v-if="validationResults.isValid" class="flex items-center text-sm text-green-600">
                                        <CheckCircleIcon class="mr-1 h-4 w-4" />
                                        Code is valid
                                    </div>
                                </div>
                            </div>

                            <!-- Code Templates -->
                            <div class="mb-6">
                                <h4 class="mb-3 text-sm font-medium text-gray-900">Templates</h4>
                                <div class="space-y-2">
                                    <button
                                        v-for="template in getTemplates()"
                                        :key="template.name"
                                        @click="insertCodeTemplate(template)"
                                        class="w-full rounded border border-gray-200 p-2 text-left text-xs hover:bg-gray-50"
                                    >
                                        <div class="font-medium">{{ template.name }}</div>
                                        <div class="text-gray-500">{{ template.description }}</div>
                                    </button>
                                </div>
                            </div>

                            <!-- Code Snippets -->
                            <div>
                                <h4 class="mb-3 text-sm font-medium text-gray-900">Common Snippets</h4>
                                <div class="space-y-2">
                                    <button
                                        v-for="snippet in getSnippets()"
                                        :key="snippet.name"
                                        @click="insertSnippet(snippet)"
                                        class="w-full rounded border border-gray-200 p-2 text-left text-xs hover:bg-gray-50"
                                    >
                                        {{ snippet.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button
                        @click="handleSave"
                        :disabled="!canSave"
                        :class="[
                            canSave ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500' : 'cursor-not-allowed bg-gray-300',
                            'inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm',
                        ]"
                    >
                        {{ isNew ? 'Create' : 'Save' }}
                    </button>
                    <button
                        @click="$emit('cancel')"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <CodePreviewModal v-if="showPreview" :code="localCode" @close="showPreview = false" />
    </div>
</template>

<script setup lang="ts">
import type { CustomCode } from '@/services/CustomCodeStorageService';
import { customCodeValidationService, type ValidationResult } from '@/services/CustomCodeValidationService';
import {
    CheckCircleIcon,
    CpuChipIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    SwatchIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, nextTick, ref, watch } from 'vue';
import CodePreviewModal from './CodePreviewModal.vue';

// Props & Emits
interface Props {
    code: CustomCode | null;
    isNew: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    save: [code: CustomCode];
    cancel: [];
}>();

// Reactive state
const localCode = ref<CustomCode>({ ...props.code! });
const validationResults = ref<ValidationResult | null>(null);
const highlightedCode = ref<string>('');
const showPreview = ref(false);
const codeEditor = ref<HTMLTextAreaElement>();

// Computed properties
const codeTypeLabel = computed(() => {
    switch (localCode.value.type) {
        case 'html':
            return 'HTML';
        case 'css':
            return 'CSS';
        case 'javascript':
            return 'JavaScript';
        default:
            return 'Code';
    }
});

const codeTypeIcon = computed(() => {
    switch (localCode.value.type) {
        case 'html':
            return DocumentTextIcon;
        case 'css':
            return SwatchIcon;
        case 'javascript':
            return CpuChipIcon;
        default:
            return DocumentTextIcon;
    }
});

const codeStats = computed(() => {
    const code = localCode.value.code || '';
    return {
        lines: code.split('\n').length,
        characters: code.length,
        bytes: new Blob([code]).size,
    };
});

const canSave = computed(() => {
    return (
        localCode.value.name.trim() !== '' &&
        localCode.value.code.trim() !== '' &&
        (!validationResults.value || validationResults.value.errors.length === 0)
    );
});

// Methods
const handleCodeChange = async () => {
    // Debounced validation
    clearTimeout(validationTimeout);
    validationTimeout = setTimeout(async () => {
        await validateCode();
    }, 500);
};

let validationTimeout: number;

const validateCode = async () => {
    if (!localCode.value.code.trim()) {
        validationResults.value = null;
        return;
    }

    try {
        const results = await customCodeValidationService.validateCode(localCode.value.code, localCode.value.type);
        validationResults.value = results;
    } catch (error) {
        console.error('Validation failed:', error);
    }
};

const formatCode = () => {
    // Basic code formatting
    let formatted = localCode.value.code;

    if (localCode.value.type === 'html') {
        // Basic HTML formatting
        formatted = formatted.replace(/></g, '>\n<').replace(/^\s+|\s+$/gm, '');
    } else if (localCode.value.type === 'css') {
        // Basic CSS formatting
        formatted = formatted
            .replace(/\{/g, ' {\n  ')
            .replace(/\}/g, '\n}\n')
            .replace(/;/g, ';\n  ')
            .replace(/^\s+|\s+$/gm, '');
    } else if (localCode.value.type === 'javascript') {
        // Basic JS formatting
        formatted = formatted
            .replace(/\{/g, ' {\n  ')
            .replace(/\}/g, '\n}\n')
            .replace(/;/g, ';\n')
            .replace(/^\s+|\s+$/gm, '');
    }

    localCode.value.code = formatted;
};

const getPlaceholder = (): string => {
    switch (localCode.value.type) {
        case 'html':
            return 'Enter your HTML code here...\n\nExample:\n<div class="my-component">\n  <h2>Hello World</h2>\n</div>';
        case 'css':
            return 'Enter your CSS code here...\n\nExample:\n.my-component {\n  color: #333;\n  padding: 1rem;\n}';
        case 'javascript':
            return 'Enter your JavaScript code here...\n\nExample:\nconsole.log("Hello World");\n\ndocument.addEventListener("DOMContentLoaded", function() {\n  // Your code here\n});';
        default:
            return 'Enter your code here...';
    }
};

const getTemplates = () => {
    const templates = {
        html: [
            {
                name: 'Basic Component',
                description: 'A basic HTML component structure',
                code: '<div class="component">\n  <h2 class="component-title">Title</h2>\n  <p class="component-content">Content goes here</p>\n</div>',
            },
            {
                name: 'Form Element',
                description: 'A form with validation',
                code: '<form class="custom-form">\n  <div class="form-group">\n    <label for="email">Email:</label>\n    <input type="email" id="email" required>\n  </div>\n  <button type="submit">Submit</button>\n</form>',
            },
        ],
        css: [
            {
                name: 'Flexbox Layout',
                description: 'Basic flexbox container',
                code: '.flex-container {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  gap: 1rem;\n}',
            },
            {
                name: 'Card Component',
                description: 'Styled card component',
                code: '.card {\n  background: white;\n  border-radius: 8px;\n  box-shadow: 0 2px 4px rgba(0,0,0,0.1);\n  padding: 1.5rem;\n  margin: 1rem 0;\n}',
            },
        ],
        javascript: [
            {
                name: 'Event Listener',
                description: 'Basic event handling',
                code: 'document.addEventListener("DOMContentLoaded", function() {\n  const button = document.querySelector(".my-button");\n  button.addEventListener("click", function() {\n    console.log("Button clicked!");\n  });\n});',
            },
            {
                name: 'Form Handler',
                description: 'Form submission handler',
                code: 'function handleFormSubmit(event) {\n  event.preventDefault();\n  const formData = new FormData(event.target);\n  \n  // Process form data\n  console.log("Form submitted:", Object.fromEntries(formData));\n}',
            },
        ],
    };

    return templates[localCode.value.type] || [];
};

const getSnippets = () => {
    const snippets = {
        html: [
            { name: 'Div with class', code: '<div class=""></div>' },
            { name: 'Button', code: '<button type="button" class="">Click me</button>' },
            { name: 'Input field', code: '<input type="text" class="" placeholder="">' },
        ],
        css: [
            { name: 'Center content', code: 'display: flex;\njustify-content: center;\nalign-items: center;' },
            { name: 'Responsive width', code: 'width: 100%;\nmax-width: 1200px;\nmargin: 0 auto;' },
            { name: 'Hover effect', code: 'transition: all 0.3s ease;\n\n&:hover {\n  transform: translateY(-2px);\n}' },
        ],
        javascript: [
            { name: 'Query selector', code: 'const element = document.querySelector("");' },
            { name: 'Add event listener', code: 'element.addEventListener("click", function() {\n  // Your code here\n});' },
            { name: 'Fetch API', code: 'fetch("/api/endpoint")\n  .then(response => response.json())\n  .then(data => console.log(data));' },
        ],
    };

    return snippets[localCode.value.type] || [];
};

const insertTemplate = () => {
    // Show template selection (simplified)
    const templates = getTemplates();
    if (templates.length > 0) {
        insertCodeTemplate(templates[0]);
    }
};

const insertCodeTemplate = (template: any) => {
    localCode.value.code = template.code;
    nextTick(() => {
        validateCode();
    });
};

const insertSnippet = (snippet: any) => {
    const textarea = codeEditor.value;
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = localCode.value.code;

        localCode.value.code = text.substring(0, start) + snippet.code + text.substring(end);

        nextTick(() => {
            textarea.focus();
            textarea.setSelectionRange(start + snippet.code.length, start + snippet.code.length);
        });
    }
};

const previewCode = () => {
    showPreview.value = true;
};

const handleSave = () => {
    if (canSave.value) {
        emit('save', { ...localCode.value });
    }
};

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Watchers
watch(
    () => props.code,
    (newCode) => {
        if (newCode) {
            localCode.value = { ...newCode };
        }
    },
    { immediate: true },
);

watch(
    () => localCode.value.code,
    () => {
        handleCodeChange();
    },
    { immediate: true },
);
</script>













