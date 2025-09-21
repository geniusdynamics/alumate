<template>
    <div class="validation-panel border-t border-gray-200 bg-white p-4">
        <div class="mb-4 flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-900">Code Validation</h4>
            <div class="flex items-center space-x-2">
                <button
                    @click="runValidation"
                    :disabled="isValidating"
                    class="rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
                >
                    {{ isValidating ? 'Validating...' : 'Re-validate' }}
                </button>
                <button
                    @click="autoFix"
                    :disabled="!canAutoFix"
                    class="rounded bg-indigo-600 px-2 py-1 text-xs text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    Auto Fix
                </button>
            </div>
        </div>

        <!-- Validation Summary -->
        <div class="mb-4">
            <div class="flex items-center space-x-4 text-sm">
                <div :class="[results.isValid ? 'text-green-600' : 'text-red-600', 'flex items-center']">
                    <component :is="results.isValid ? CheckCircleIcon : XCircleIcon" class="mr-1 h-4 w-4" />
                    {{ results.isValid ? 'Valid' : 'Invalid' }}
                </div>
                <div v-if="results.errors.length > 0" class="text-red-600">
                    {{ results.errors.length }} error{{ results.errors.length !== 1 ? 's' : '' }}
                </div>
                <div v-if="results.warnings.length > 0" class="text-yellow-600">
                    {{ results.warnings.length }} warning{{ results.warnings.length !== 1 ? 's' : '' }}
                </div>
                <div v-if="results.securityIssues.length > 0" class="text-red-600">
                    {{ results.securityIssues.length }} security issue{{ results.securityIssues.length !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>

        <!-- Validation Tabs -->
        <div class="mb-4 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button
                    v-for="tab in validationTabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        activeTab === tab.id
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium',
                    ]"
                >
                    <component :is="tab.icon" class="mr-1 inline h-4 w-4" />
                    {{ tab.label }}
                    <span
                        v-if="tab.count > 0"
                        :class="[
                            activeTab === tab.id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-900',
                            'ml-2 rounded-full px-2 py-0.5 text-xs font-medium',
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Validation Content -->
        <div class="max-h-64 overflow-y-auto">
            <!-- Errors Tab -->
            <div v-if="activeTab === 'errors'" class="space-y-2">
                <div
                    v-for="error in results.errors"
                    :key="`error-${error.line}-${error.column}`"
                    class="rounded-lg border border-red-200 bg-red-50 p-3"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center text-sm font-medium text-red-800">
                                <ExclamationTriangleIcon class="mr-1 h-4 w-4" />
                                Line {{ error.line }}, Column {{ error.column }}
                            </div>
                            <p class="mt-1 text-sm text-red-700">{{ error.message }}</p>
                            <div class="mt-1 text-xs text-red-600">
                                Code: {{ error.code }}
                                <span v-if="error.ruleId"> | Rule: {{ error.ruleId }}</span>
                            </div>
                        </div>
                        <button @click="goToLine(error.line, error.column)" class="text-xs text-red-600 hover:text-red-800">Go to line</button>
                    </div>
                </div>
                <div v-if="results.errors.length === 0" class="py-4 text-center text-sm text-gray-500">No errors found</div>
            </div>

            <!-- Warnings Tab -->
            <div v-if="activeTab === 'warnings'" class="space-y-2">
                <div
                    v-for="warning in results.warnings"
                    :key="`warning-${warning.line}-${warning.column}`"
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-3"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center text-sm font-medium text-yellow-800">
                                <ExclamationTriangleIcon class="mr-1 h-4 w-4" />
                                Line {{ warning.line }}, Column {{ warning.column }}
                            </div>
                            <p class="mt-1 text-sm text-yellow-700">{{ warning.message }}</p>
                            <div class="mt-1 text-xs text-yellow-600">
                                Code: {{ warning.code }}
                                <span v-if="warning.ruleId"> | Rule: {{ warning.ruleId }}</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="fixIssue(warning)" class="text-xs text-yellow-600 hover:text-yellow-800">Fix</button>
                            <button @click="goToLine(warning.line, warning.column)" class="text-xs text-yellow-600 hover:text-yellow-800">
                                Go to line
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="results.warnings.length === 0" class="py-4 text-center text-sm text-gray-500">No warnings found</div>
            </div>

            <!-- Security Tab -->
            <div v-if="activeTab === 'security'" class="space-y-2">
                <div v-for="issue in results.securityIssues" :key="issue.message" class="rounded-lg border border-red-200 bg-red-50 p-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center text-sm font-medium text-red-800">
                                <ShieldExclamationIcon class="mr-1 h-4 w-4" />
                                <span
                                    :class="[
                                        issue.severity === 'critical'
                                            ? 'text-red-800'
                                            : issue.severity === 'high'
                                              ? 'text-red-700'
                                              : issue.severity === 'medium'
                                                ? 'text-yellow-700'
                                                : 'text-gray-700',
                                        'mr-2 text-xs font-bold uppercase',
                                    ]"
                                >
                                    {{ issue.severity }}
                                </span>
                                <span v-if="issue.line">Line {{ issue.line }}</span>
                            </div>
                            <p class="mt-1 text-sm text-red-700">{{ issue.message }}</p>
                            <div v-if="issue.remediation" class="mt-2 rounded bg-red-100 p-2 text-xs text-red-600">
                                <strong>Remediation:</strong> {{ issue.remediation }}
                            </div>
                            <div v-if="issue.cwe" class="mt-1 text-xs text-red-600">CWE: {{ issue.cwe }}</div>
                        </div>
                        <button v-if="issue.line" @click="goToLine(issue.line, issue.column)" class="text-xs text-red-600 hover:text-red-800">
                            Go to line
                        </button>
                    </div>
                </div>
                <div v-if="results.securityIssues.length === 0" class="py-4 text-center text-sm text-gray-500">No security issues found</div>
            </div>

            <!-- Performance Tab -->
            <div v-if="activeTab === 'performance'" class="space-y-2">
                <div v-for="issue in results.performanceIssues" :key="issue.message" class="rounded-lg border border-orange-200 bg-orange-50 p-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center text-sm font-medium text-orange-800">
                                <BoltIcon class="mr-1 h-4 w-4" />
                                <span
                                    :class="[
                                        issue.severity === 'high'
                                            ? 'text-red-700'
                                            : issue.severity === 'medium'
                                              ? 'text-yellow-700'
                                              : 'text-gray-700',
                                        'mr-2 text-xs font-bold uppercase',
                                    ]"
                                >
                                    {{ issue.severity }}
                                </span>
                                <span class="capitalize">{{ issue.type }}</span>
                                <span v-if="issue.line"> | Line {{ issue.line }}</span>
                            </div>
                            <p class="mt-1 text-sm text-orange-700">{{ issue.message }}</p>
                            <div v-if="issue.recommendation" class="mt-2 rounded bg-orange-100 p-2 text-xs text-orange-600">
                                <strong>Recommendation:</strong> {{ issue.recommendation }}
                            </div>
                        </div>
                        <button v-if="issue.line" @click="goToLine(issue.line, issue.column)" class="text-xs text-orange-600 hover:text-orange-800">
                            Go to line
                        </button>
                    </div>
                </div>
                <div v-if="results.performanceIssues.length === 0" class="py-4 text-center text-sm text-gray-500">No performance issues found</div>
            </div>

            <!-- Info Tab -->
            <div v-if="activeTab === 'info'" class="space-y-2">
                <div v-for="info in results.info" :key="`info-${info.line}-${info.column}`" class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center text-sm font-medium text-blue-800">
                                <InformationCircleIcon class="mr-1 h-4 w-4" />
                                Line {{ info.line }}, Column {{ info.column }}
                            </div>
                            <p class="mt-1 text-sm text-blue-700">{{ info.message }}</p>
                            <div class="mt-1 text-xs text-blue-600">
                                Code: {{ info.code }}
                                <span v-if="info.ruleId"> | Rule: {{ info.ruleId }}</span>
                            </div>
                        </div>
                        <button @click="goToLine(info.line, info.column)" class="text-xs text-blue-600 hover:text-blue-800">Go to line</button>
                    </div>
                </div>
                <div v-if="results.info.length === 0" class="py-4 text-center text-sm text-gray-500">No info messages</div>
            </div>
        </div>

        <!-- Code Analysis Summary -->
        <div v-if="codeAnalysis" class="mt-4 border-t border-gray-200 pt-4">
            <h5 class="mb-2 text-sm font-medium text-gray-900">Code Analysis</h5>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Complexity:</span>
                    <span
                        :class="[
                            codeAnalysis.complexity > 10 ? 'text-red-600' : codeAnalysis.complexity > 5 ? 'text-yellow-600' : 'text-green-600',
                            'ml-1 font-medium',
                        ]"
                    >
                        {{ codeAnalysis.complexity }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600">Maintainability:</span>
                    <span
                        :class="[
                            codeAnalysis.maintainability < 50
                                ? 'text-red-600'
                                : codeAnalysis.maintainability < 75
                                  ? 'text-yellow-600'
                                  : 'text-green-600',
                            'ml-1 font-medium',
                        ]"
                    >
                        {{ codeAnalysis.maintainability }}%
                    </span>
                </div>
            </div>
            <div v-if="codeAnalysis.suggestions.length > 0" class="mt-2">
                <h6 class="mb-1 text-xs font-medium text-gray-700">Suggestions:</h6>
                <ul class="space-y-1 text-xs text-gray-600">
                    <li v-for="suggestion in codeAnalysis.suggestions" :key="suggestion" class="flex items-start">
                        <span class="mr-1 text-gray-400">•</span>
                        {{ suggestion }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CustomCode } from '@/services/CustomCodeStorageService';
import { customCodeValidationService, type ValidationResult } from '@/services/CustomCodeValidationService';
import {
    BoltIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ShieldExclamationIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

// Props & Emits
interface Props {
    results: ValidationResult;
    code: CustomCode;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    fixIssue: [issue: any];
    goToLine: [line: number, column?: number];
}>();

// Reactive state
const activeTab = ref<'errors' | 'warnings' | 'security' | 'performance' | 'info'>('errors');
const isValidating = ref(false);
const codeAnalysis = ref<any>(null);

// Computed properties
const validationTabs = computed(() => [
    {
        id: 'errors' as const,
        label: 'Errors',
        icon: ExclamationTriangleIcon,
        count: props.results.errors.length,
    },
    {
        id: 'warnings' as const,
        label: 'Warnings',
        icon: ExclamationTriangleIcon,
        count: props.results.warnings.length,
    },
    {
        id: 'security' as const,
        label: 'Security',
        icon: ShieldExclamationIcon,
        count: props.results.securityIssues.length,
    },
    {
        id: 'performance' as const,
        label: 'Performance',
        icon: BoltIcon,
        count: props.results.performanceIssues.length,
    },
    {
        id: 'info' as const,
        label: 'Info',
        icon: InformationCircleIcon,
        count: props.results.info.length,
    },
]);

const canAutoFix = computed(() => {
    return props.results.warnings.some((w) => w.code === 'MISSING_SEMICOLON' || w.code === 'UNQUOTED_ATTRIBUTE');
});

// Methods
const runValidation = async () => {
    isValidating.value = true;
    try {
        // Re-run validation (would emit to parent to trigger re-validation)
        await new Promise((resolve) => setTimeout(resolve, 500)); // Simulate validation
    } finally {
        isValidating.value = false;
    }
};

const autoFix = async () => {
    if (!canAutoFix.value) return;

    try {
        const lintResult = await customCodeValidationService.lintCode(props.code.code, props.code.type);

        if (lintResult.fixedCode && lintResult.fixedCode !== props.code.code) {
            // Emit fix event to parent
            emit('fixIssue', { type: 'auto-fix', fixedCode: lintResult.fixedCode });
        }
    } catch (error) {
        console.error('Auto-fix failed:', error);
    }
};

const fixIssue = (issue: any) => {
    emit('fixIssue', issue);
};

const goToLine = (line: number, column?: number) => {
    emit('goToLine', line, column);
};

const loadCodeAnalysis = async () => {
    try {
        const analysis = await customCodeValidationService.analyzeCode(props.code.code, props.code.type);
        codeAnalysis.value = analysis;
    } catch (error) {
        console.error('Failed to analyze code:', error);
    }
};

// Set default active tab based on what has issues
const setDefaultActiveTab = () => {
    if (props.results.errors.length > 0) {
        activeTab.value = 'errors';
    } else if (props.results.securityIssues.length > 0) {
        activeTab.value = 'security';
    } else if (props.results.warnings.length > 0) {
        activeTab.value = 'warnings';
    } else if (props.results.performanceIssues.length > 0) {
        activeTab.value = 'performance';
    } else if (props.results.info.length > 0) {
        activeTab.value = 'info';
    }
};

// Lifecycle
onMounted(() => {
    setDefaultActiveTab();
    loadCodeAnalysis();
});
</script>
