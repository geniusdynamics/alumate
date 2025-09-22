<template>
    <div class="custom-code-panel flex h-full flex-col border-l border-gray-200 bg-white">
        <!-- Header -->
        <div class="flex-shrink-0 border-b border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Custom Code</h3>
                <button
                    @click="createNewCode"
                    class="inline-flex items-center rounded border border-transparent bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <PlusIcon class="mr-1 h-4 w-4" />
                    New Code
                </button>
            </div>

            <!-- Code Type Tabs -->
            <div class="mt-4">
                <nav class="flex space-x-1" aria-label="Code Types">
                    <button
                        v-for="type in codeTypes"
                        :key="type.id"
                        @click="activeCodeType = type.id"
                        :class="[
                            activeCodeType === type.id ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700',
                            'rounded-md px-3 py-2 text-sm font-medium',
                        ]"
                    >
                        <component :is="type.icon" class="mr-2 inline h-4 w-4" />
                        {{ type.label }}
                    </button>
                </nav>
            </div>
        </div>

        <!-- Code List -->
        <div class="flex-1 overflow-y-auto">
            <div class="space-y-3 p-4">
                <div
                    v-for="code in filteredCodes"
                    :key="code.id"
                    @click="selectCode(code)"
                    :class="[
                        selectedCode?.id === code.id ? 'bg-indigo-50 ring-2 ring-indigo-500' : 'hover:bg-gray-50',
                        'cursor-pointer rounded-lg border border-gray-200 p-3 transition-colors',
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h4 class="truncate text-sm font-medium text-gray-900">
                                {{ code.name }}
                            </h4>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ code.description || 'No description' }}
                            </p>
                            <div class="mt-2 flex items-center space-x-2">
                                <span
                                    :class="[
                                        code.type === 'html'
                                            ? 'bg-orange-100 text-orange-800'
                                            : code.type === 'css'
                                              ? 'bg-blue-100 text-blue-800'
                                              : 'bg-green-100 text-green-800',
                                        'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                    ]"
                                >
                                    {{ code.type.toUpperCase() }}
                                </span>
                                <span
                                    :class="[
                                        code.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                                        'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                    ]"
                                >
                                    {{ code.isActive ? 'Active' : 'Inactive' }}
                                </span>
                                <span
                                    v-if="code.isDraft"
                                    class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800"
                                >
                                    Draft
                                </span>
                            </div>
                        </div>
                        <div class="ml-2 flex items-center space-x-1">
                            <button @click.stop="editCode(code)" class="p-1 text-gray-400 hover:text-gray-600">
                                <PencilIcon class="h-4 w-4" />
                            </button>
                            <button @click.stop="deleteCode(code)" class="p-1 text-gray-400 hover:text-red-600">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="filteredCodes.length === 0" class="py-8 text-center">
                    <CodeBracketIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No custom code</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first {{ activeCodeType }} code snippet.</p>
                    <div class="mt-6">
                        <button
                            @click="createNewCode"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        >
                            <PlusIcon class="mr-2 h-4 w-4" />
                            New {{ activeCodeType.toUpperCase() }} Code
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Editor Modal -->
        <CustomCodeEditor v-if="showEditor" :code="editingCode" :is-new="isNewCode" @save="handleSaveCode" @cancel="handleCancelEdit" />

        <!-- Validation Results -->
        <ValidationPanel v-if="selectedCode && validationResults" :results="validationResults" :code="selectedCode" @fix-issue="handleFixIssue" />
    </div>
</template>

<script setup lang="ts">
import { customCodeStorageService, type CustomCode } from '@/Services/CustomCodeStorageService';
import { customCodeValidationService, type ValidationResult } from '@/Services/CustomCodeValidationService';
import { CodeBracketIcon, CpuChipIcon, DocumentTextIcon, PencilIcon, PlusIcon, SwatchIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref, watch } from 'vue';
import CustomCodeEditor from './CustomCodeEditor.vue';
import ValidationPanel from './ValidationPanel.vue';

// Props
interface Props {
    tenantId: string;
    pageId?: string;
}

const props = defineProps<Props>();

// Reactive state
const customCodes = ref<CustomCode[]>([]);
const selectedCode = ref<CustomCode | null>(null);
const editingCode = ref<CustomCode | null>(null);
const showEditor = ref(false);
const isNewCode = ref(false);
const activeCodeType = ref<'html' | 'css' | 'javascript'>('html');
const validationResults = ref<ValidationResult | null>(null);
const isLoading = ref(false);

// Code types configuration
const codeTypes = [
    { id: 'html' as const, label: 'HTML', icon: DocumentTextIcon },
    { id: 'css' as const, label: 'CSS', icon: SwatchIcon },
    { id: 'javascript' as const, label: 'JavaScript', icon: CpuChipIcon },
];

// Computed properties
const filteredCodes = computed(() => {
    return customCodes.value.filter((code) => code.type === activeCodeType.value);
});

// Methods
const loadCustomCodes = async () => {
    try {
        isLoading.value = true;
        const codes = await customCodeStorageService.getCustomCodes({
            tenantId: props.tenantId,
            pageId: props.pageId,
            includeInactive: true,
        });
        customCodes.value = codes;
    } catch (error) {
        console.error('Failed to load custom codes:', error);
    } finally {
        isLoading.value = false;
    }
};

const selectCode = async (code: CustomCode) => {
    selectedCode.value = code;

    // Validate the selected code
    try {
        const results = await customCodeValidationService.validateCode(code.code, code.type);
        validationResults.value = results;
    } catch (error) {
        console.error('Failed to validate code:', error);
        validationResults.value = null;
    }
};

const createNewCode = () => {
    editingCode.value = {
        id: '',
        tenantId: props.tenantId,
        pageId: props.pageId,
        type: activeCodeType.value,
        name: `New ${activeCodeType.value.toUpperCase()} Code`,
        description: '',
        code: getDefaultCode(activeCodeType.value),
        version: 1,
        isActive: false,
        isDraft: true,
        tags: [],
        metadata: {},
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'current-user', // Would come from auth context
        updatedBy: 'current-user',
    };
    isNewCode.value = true;
    showEditor.value = true;
};

const editCode = (code: CustomCode) => {
    editingCode.value = { ...code };
    isNewCode.value = false;
    showEditor.value = true;
};

const handleSaveCode = async (savedCode: CustomCode) => {
    try {
        let result: CustomCode;

        if (isNewCode.value) {
            result = await customCodeStorageService.storeCustomCode(savedCode);
            customCodes.value.push(result);
        } else {
            result = await customCodeStorageService.updateCustomCode(savedCode.id, savedCode);
            const index = customCodes.value.findIndex((c) => c.id === savedCode.id);
            if (index !== -1) {
                customCodes.value[index] = result;
            }
        }

        // Update selected code if it's the same one
        if (selectedCode.value?.id === result.id) {
            selectedCode.value = result;
            // Re-validate
            const validationResult = await customCodeValidationService.validateCode(result.code, result.type);
            validationResults.value = validationResult;
        }

        showEditor.value = false;
        editingCode.value = null;
    } catch (error) {
        console.error('Failed to save code:', error);
        // Handle error (show notification, etc.)
    }
};

const handleCancelEdit = () => {
    showEditor.value = false;
    editingCode.value = null;
    isNewCode.value = false;
};

const deleteCode = async (code: CustomCode) => {
    if (!confirm(`Are you sure you want to delete "${code.name}"?`)) {
        return;
    }

    try {
        await customCodeStorageService.deleteCustomCode(code.id);
        customCodes.value = customCodes.value.filter((c) => c.id !== code.id);

        if (selectedCode.value?.id === code.id) {
            selectedCode.value = null;
            validationResults.value = null;
        }
    } catch (error) {
        console.error('Failed to delete code:', error);
    }
};

const handleFixIssue = async (issue: any) => {
    if (!selectedCode.value) return;

    try {
        const lintResult = await customCodeValidationService.lintCode(selectedCode.value.code, selectedCode.value.type);

        if (lintResult.fixedCode && lintResult.fixedCode !== selectedCode.value.code) {
            // Update the code with the fixed version
            const updatedCode = await customCodeStorageService.updateCustomCode(selectedCode.value.id, { code: lintResult.fixedCode });

            // Update local state
            const index = customCodes.value.findIndex((c) => c.id === selectedCode.value!.id);
            if (index !== -1) {
                customCodes.value[index] = updatedCode;
            }
            selectedCode.value = updatedCode;

            // Re-validate
            const validationResult = await customCodeValidationService.validateCode(updatedCode.code, updatedCode.type);
            validationResults.value = validationResult;
        }
    } catch (error) {
        console.error('Failed to fix issue:', error);
    }
};

const getDefaultCode = (type: 'html' | 'css' | 'javascript'): string => {
    switch (type) {
        case 'html':
            return '<div class="custom-element">\n  <!-- Your HTML content here -->\n</div>';
        case 'css':
            return '.custom-element {\n  /* Your CSS styles here */\n  color: #333;\n  padding: 1rem;\n}';
        case 'javascript':
            return '// Your JavaScript code here\nconsole.log("Custom code loaded");';
        default:
            return '';
    }
};

// Watchers
watch(() => props.tenantId, loadCustomCodes, { immediate: true });
watch(() => props.pageId, loadCustomCodes);

// Lifecycle
onMounted(() => {
    loadCustomCodes();
});

// Expose methods for parent component
defineExpose({
    loadCustomCodes,
    getActiveCodes: () => customCodes.value.filter((c) => c.isActive),
    getCodesByType: (type: 'html' | 'css' | 'javascript') => customCodes.value.filter((c) => c.type === type && c.isActive),
});
</script>
