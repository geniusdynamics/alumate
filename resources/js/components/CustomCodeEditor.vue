<template>
    <div class="custom-code-editor" :class="{ loading: isLoading, error: hasErrors }">
        <!-- Editor Header -->
        <div class="editor-header">
            <div class="editor-tabs">
                <button
                    v-for="tab in codeTabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="['editor-tab', { active: activeTab === tab.id }]"
                    :aria-pressed="activeTab === tab.id"
                >
                    <component :is="tab.icon" class="tab-icon" />
                    {{ tab.label }}
                </button>
            </div>

            <div class="editor-actions">
                <button
                    @click="validateCode"
                    :disabled="isValidating"
                    class="action-btn validate-btn"
                    :aria-label="isValidating ? 'Validating code...' : 'Validate code'"
                >
                    <svg v-if="isValidating" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                        <path
                            fill="currentColor"
                            class="opacity-75"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    {{ isValidating ? 'Validating...' : 'Validate' }}
                </button>

                <button @click="formatCode" class="action-btn format-btn" :aria-label="'Format code'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    Format
                </button>

                <button @click="previewCode" class="action-btn preview-btn" :aria-label="'Preview code'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        ></path>
                    </svg>
                    Preview
                </button>
            </div>
        </div>

        <!-- Code Editor -->
        <div class="editor-container">
            <div class="code-editor-wrapper">
                <textarea
                    ref="codeTextarea"
                    v-model="currentCode"
                    @input="handleCodeInput"
                    @keydown="handleKeyDown"
                    class="code-editor"
                    :class="{ error: validationErrors.length > 0 }"
                    :placeholder="getPlaceholder()"
                    spellcheck="false"
                    autocorrect="off"
                    autocapitalize="off"
                    autocomplete="off"
                ></textarea>

                <!-- Syntax Highlighting Overlay -->
                <pre class="syntax-highlight-overlay" v-html="highlightedCode"></pre>
            </div>

            <!-- Line Numbers -->
            <div class="line-numbers">
                <div v-for="line in lineCount" :key="line" class="line-number" :class="{ 'error-line': hasErrorOnLine(line) }">
                    {{ line }}
                </div>
            </div>
        </div>

        <!-- Validation Errors -->
        <div v-if="validationErrors.length > 0" class="validation-errors">
            <div class="errors-header">
                <h4 class="errors-title">Validation Errors</h4>
                <button @click="clearErrors" class="clear-errors-btn">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="errors-list">
                <div v-for="error in validationErrors" :key="error.line + '-' + error.column" class="error-item" @click="focusErrorLine(error.line)">
                    <div class="error-icon">
                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                            ></path>
                        </svg>
                    </div>
                    <div class="error-content">
                        <div class="error-message">{{ error.message }}</div>
                        <div class="error-location">Line {{ error.line }}, Column {{ error.column }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreview" class="preview-modal-overlay" @click="closePreview">
            <div class="preview-modal" @click.stop>
                <div class="preview-header">
                    <h3 class="preview-title">Code Preview</h3>
                    <button @click="closePreview" class="close-preview-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="preview-content">
                    <iframe ref="previewIframe" class="preview-iframe" :src="previewUrl" @load="onPreviewLoad"></iframe>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p>{{ loadingMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, h, onMounted, onUnmounted, ref, watch } from 'vue';

// Icons
const CodeIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' }),
    ]);

const PaletteIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z',
        }),
    ]);

const ZapIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' }),
    ]);

// Props
interface Props {
    modelValue?: {
        html?: string;
        css?: string;
        js?: string;
    };
    tenantId?: string;
    pageId?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({ html: '', css: '', js: '' }),
    disabled: false,
});

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: { html?: string; css?: string; js?: string }];
    validate: [errors: ValidationError[]];
    save: [code: { html?: string; css?: string; js?: string }];
    error: [error: Error];
}>();

// Reactive state
const activeTab = ref<'html' | 'css' | 'js'>('html');
const currentCode = ref('');
const isLoading = ref(false);
const loadingMessage = ref('');
const isValidating = ref(false);
const validationErrors = ref<ValidationError[]>([]);
const showPreview = ref(false);
const previewUrl = ref('');
const previewIframe = ref<HTMLIFrameElement | null>(null);
const codeTextarea = ref<HTMLTextAreaElement | null>(null);

// Code tabs configuration
const codeTabs = [
    { id: 'html', label: 'HTML', icon: CodeIcon },
    { id: 'css', label: 'CSS', icon: PaletteIcon },
    { id: 'js', label: 'JavaScript', icon: ZapIcon },
];

// Computed properties
const lineCount = computed(() => {
    return currentCode.value.split('\n').length;
});

const highlightedCode = computed(() => {
    return highlightCode(currentCode.value, activeTab.value);
});

const hasErrors = computed(() => validationErrors.value.length > 0);

// Methods
const getPlaceholder = (): string => {
    switch (activeTab.value) {
        case 'html':
            return '<!-- Enter your custom HTML here -->\n<div class="custom-element">\n  <h1>Hello World</h1>\n  <p>This is custom HTML content.</p>\n</div>';
        case 'css':
            return '/* Enter your custom CSS here */\n.custom-element {\n  background-color: #f0f0f0;\n  padding: 20px;\n  border-radius: 8px;\n}';
        case 'js':
            return '// Enter your custom JavaScript here\nconsole.log("Custom JavaScript loaded");\n\ndocument.addEventListener("DOMContentLoaded", function() {\n  // Your custom code here\n});';
        default:
            return '';
    }
};

const handleCodeInput = () => {
    updateModelValue();
};

const handleKeyDown = (event: KeyboardEvent) => {
    // Handle Tab key for indentation
    if (event.key === 'Tab') {
        event.preventDefault();
        const textarea = event.target as HTMLTextAreaElement;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;

        // Insert tab character
        textarea.value = textarea.value.substring(0, start) + '  ' + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + 2;

        currentCode.value = textarea.value;
        updateModelValue();
    }

    // Handle Ctrl+S for save
    if (event.ctrlKey && event.key === 's') {
        event.preventDefault();
        saveCode();
    }

    // Handle Ctrl+Shift+F for format
    if (event.ctrlKey && event.shiftKey && event.key === 'F') {
        event.preventDefault();
        formatCode();
    }
};

const updateModelValue = () => {
    const newValue = { ...props.modelValue };

    switch (activeTab.value) {
        case 'html':
            newValue.html = currentCode.value;
            break;
        case 'css':
            newValue.css = currentCode.value;
            break;
        case 'js':
            newValue.js = currentCode.value;
            break;
    }

    emit('update:modelValue', newValue);
};

const highlightCode = (code: string, language: string): string => {
    if (!code) return '';

    let highlighted = code;

    switch (language) {
        case 'html':
            highlighted = highlightHTML(code);
            break;
        case 'css':
            highlighted = highlightCSS(code);
            break;
        case 'js':
            highlighted = highlightJavaScript(code);
            break;
    }

    // Convert newlines to <br> and spaces to &nbsp; for HTML display
    return highlighted.replace(/\n/g, '<br>').replace(/ /g, '&nbsp;');
};

const highlightHTML = (code: string): string => {
    return code
        .replace(/(<[^&]*>)/g, '<span class="token tag">$1</span>')
        .replace(/(<\/[^&]*>)/g, '<span class="token tag">$1</span>')
        .replace(/(class|id|style|src|href)=["']([^"']*)["']/g, '$1=<span class="token attr-value">"$2"</span>')
        .replace(/(<!--.*?-->)/g, '<span class="token comment">$1</span>');
};

const highlightCSS = (code: string): string => {
    return code
        .replace(/(\.[a-zA-Z][a-zA-Z0-9-]*|#?[a-zA-Z][a-zA-Z0-9-]*)/g, '<span class="token selector">$1</span>')
        .replace(/({|})/g, '<span class="token brace">$1</span>')
        .replace(/([a-zA-Z-]+):/g, '<span class="token property">$1</span>:')
        .replace(/([^:]+);/g, '$1<span class="token semicolon">;</span>')
        .replace(/(\/\*.*?\*\/)/g, '<span class="token comment">$1</span>');
};

const highlightJavaScript = (code: string): string => {
    return code
        .replace(/\b(function|const|let|var|if|else|for|while|return|class|extends|import|export|from)\b/g, '<span class="token keyword">$1</span>')
        .replace(/("(?:[^"\\]|\\.)*")|('(?:[^'\\]|\\.)*')/g, '<span class="token string">$1</span>')
        .replace(/(\w+)\s*\(/g, '<span class="token function">$1</span>(')
        .replace(/(\/\/.*$)/gm, '<span class="token comment">$1</span>')
        .replace(/(\/\*.*?\*\/)/gs, '<span class="token comment">$1</span>')
        .replace(/(\{|\}|\(|\)|\[|\])/g, '<span class="token brace">$1</span>');
};

const validateCode = async () => {
    if (!currentCode.value.trim()) return;

    isValidating.value = true;
    validationErrors.value = [];

    try {
        // Basic syntax validation
        const errors = await validateSyntax(currentCode.value, activeTab.value);

        if (errors.length > 0) {
            validationErrors.value = errors;
        } else {
            // Emit validation success
            emit('validate', []);
        }
    } catch (error) {
        console.error('Validation error:', error);
        validationErrors.value = [
            {
                line: 1,
                column: 1,
                message: 'Validation failed',
                severity: 'error',
            },
        ];
    } finally {
        isValidating.value = false;
    }
};

const validateSyntax = async (code: string, language: string): Promise<ValidationError[]> => {
    const errors: ValidationError[] = [];

    switch (language) {
        case 'html':
            errors.push(...validateHTML(code));
            break;
        case 'css':
            errors.push(...validateCSS(code));
            break;
        case 'js':
            errors.push(...validateJavaScript(code));
            break;
    }

    return errors;
};

const validateHTML = (code: string): ValidationError[] => {
    const errors: ValidationError[] = [];
    const lines = code.split('\n');

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Check for unclosed tags
        const openTags = line.match(/<[^/][^>]*>/g) || [];
        const closeTags = line.match(/<\/[^>]+>/g) || [];

        if (openTags.length !== closeTags.length) {
            errors.push({
                line: lineNumber,
                column: 1,
                message: 'Unclosed HTML tags detected',
                severity: 'error',
            });
        }
    });

    return errors;
};

const validateCSS = (code: string): ValidationError[] => {
    const errors: ValidationError[] = [];
    const lines = code.split('\n');

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Check for missing semicolons
        if (
            line.trim() &&
            !line.trim().endsWith(';') &&
            !line.trim().endsWith('{') &&
            !line.trim().endsWith('}') &&
            !line.trim().startsWith('//') &&
            !line.trim().startsWith('/*')
        ) {
            const colonIndex = line.indexOf(':');
            if (colonIndex > 0 && !line.substring(colonIndex).includes(';')) {
                errors.push({
                    line: lineNumber,
                    column: line.length,
                    message: 'Missing semicolon',
                    severity: 'warning',
                });
            }
        }
    });

    return errors;
};

const validateJavaScript = (code: string): ValidationError[] => {
    const errors: ValidationError[] = [];
    const lines = code.split('\n');

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Basic syntax checks
        if (line.includes('console.log(') && !line.trim().endsWith(';')) {
            errors.push({
                line: lineNumber,
                column: line.length,
                message: 'Missing semicolon after console.log',
                severity: 'warning',
            });
        }
    });

    return errors;
};

const formatCode = () => {
    if (!currentCode.value.trim()) return;

    try {
        let formatted = currentCode.value;

        switch (activeTab.value) {
            case 'html':
                formatted = formatHTML(currentCode.value);
                break;
            case 'css':
                formatted = formatCSS(currentCode.value);
                break;
            case 'js':
                formatted = formatJavaScript(currentCode.value);
                break;
        }

        currentCode.value = formatted;
        updateModelValue();
    } catch (error) {
        console.error('Formatting error:', error);
    }
};

const formatHTML = (code: string): string => {
    // Basic HTML formatting
    return code
        .replace(/></g, '>\n<')
        .split('\n')
        .map((line) => line.trim())
        .join('\n');
};

const formatCSS = (code: string): string => {
    // Basic CSS formatting
    return code
        .replace(/{/g, ' {\n  ')
        .replace(/;/g, ';\n  ')
        .replace(/}/g, '\n}\n\n')
        .replace(/\n\s*\n/g, '\n\n')
        .trim();
};

const formatJavaScript = (code: string): string => {
    // Basic JavaScript formatting
    return code
        .replace(/{/g, ' {\n  ')
        .replace(/;/g, ';\n')
        .replace(/}/g, '\n}\n')
        .replace(/\n\s*\n/g, '\n\n')
        .trim();
};

const previewCode = () => {
    if (!currentCode.value.trim()) return;

    showPreview.value = true;

    // Create preview HTML
    const previewHTML = generatePreviewHTML();
    const blob = new Blob([previewHTML], { type: 'text/html' });
    previewUrl.value = URL.createObjectURL(blob);
};

const generatePreviewHTML = (): string => {
    const html = props.modelValue.html || '';
    const css = props.modelValue.css || '';
    const js = props.modelValue.js || '';

    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Code Preview</title>
      <style>
        ${css}
        body {
          margin: 0;
          padding: 20px;
          font-family: Arial, sans-serif;
          background-color: #f5f5f5;
        }
        .preview-container {
          max-width: 1200px;
          margin: 0 auto;
          background: white;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
      </style>
    </head>
    <body>
      <div class="preview-container">
        ${html}
      </div>
      <script>
        ${js}
      ${'</'}script>
    </body>
    </html>
  `;
};

const closePreview = () => {
    showPreview.value = false;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }
};

const onPreviewLoad = () => {
    // Preview loaded successfully
};

const hasErrorOnLine = (lineNumber: number): boolean => {
    return validationErrors.value.some((error) => error.line === lineNumber);
};

const focusErrorLine = (lineNumber: number) => {
    if (!codeTextarea.value) return;

    const lines = currentCode.value.split('\n');
    let charIndex = 0;

    for (let i = 0; i < lineNumber - 1; i++) {
        charIndex += lines[i].length + 1; // +1 for newline
    }

    codeTextarea.value.focus();
    codeTextarea.value.setSelectionRange(charIndex, charIndex);
};

const clearErrors = () => {
    validationErrors.value = [];
};

const saveCode = () => {
    emit('save', props.modelValue);
};

// Watchers
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            switch (activeTab.value) {
                case 'html':
                    currentCode.value = newValue.html || '';
                    break;
                case 'css':
                    currentCode.value = newValue.css || '';
                    break;
                case 'js':
                    currentCode.value = newValue.js || '';
                    break;
            }
        }
    },
    { deep: true },
);

watch(activeTab, (newTab) => {
    const newValue = props.modelValue;

    switch (newTab) {
        case 'html':
            currentCode.value = newValue.html || '';
            break;
        case 'css':
            currentCode.value = newValue.css || '';
            break;
        case 'js':
            currentCode.value = newValue.js || '';
            break;
    }

    // Clear validation errors when switching tabs
    validationErrors.value = [];
});

// Lifecycle
onMounted(() => {
    // Initialize with current tab's code
    const initialValue = props.modelValue;
    switch (activeTab.value) {
        case 'html':
            currentCode.value = initialValue.html || '';
            break;
        case 'css':
            currentCode.value = initialValue.css || '';
            break;
        case 'js':
            currentCode.value = initialValue.js || '';
            break;
    }
});

onUnmounted(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

// Types
interface ValidationError {
    line: number;
    column: number;
    message: string;
    severity: 'error' | 'warning' | 'info';
}
</script>

<style scoped>
.custom-code-editor {
    @apply flex h-full flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
}

.custom-code-editor.loading {
    @apply pointer-events-none;
}

.custom-code-editor.error {
    @apply border-red-300 dark:border-red-600;
}

/* Editor Header */
.editor-header {
    @apply flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700;
}

.editor-tabs {
    @apply flex gap-1;
}

.editor-tab {
    @apply flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors;
    @apply text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.editor-tab.active {
    @apply bg-blue-500 text-white;
}

.tab-icon {
    @apply h-4 w-4;
}

.editor-actions {
    @apply flex items-center gap-2;
}

.action-btn {
    @apply flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none disabled:cursor-not-allowed disabled:opacity-50;
}

.validate-btn {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.format-btn {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

.preview-btn {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

/* Editor Container */
.editor-container {
    @apply flex min-h-0 flex-1;
}

.code-editor-wrapper {
    @apply relative flex-1;
}

.code-editor {
    @apply h-full w-full resize-none border-0 p-4 font-mono text-sm;
    @apply bg-transparent text-gray-900 dark:text-white;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-0;
    @apply placeholder-gray-500 dark:placeholder-gray-400;
    @apply caret-blue-500;
}

.code-editor.error {
    @apply focus:ring-red-500;
}

.syntax-highlight-overlay {
    @apply pointer-events-none absolute inset-0 p-4 font-mono text-sm;
    @apply text-transparent;
}

.syntax-highlight-overlay .token {
    @apply text-gray-900 dark:text-white;
}

.syntax-highlight-overlay .token.tag {
    @apply text-blue-600 dark:text-blue-400;
}

.syntax-highlight-overlay .token.attr-value {
    @apply text-green-600 dark:text-green-400;
}

.syntax-highlight-overlay .token.comment {
    @apply text-gray-500 dark:text-gray-400;
}

.syntax-highlight-overlay .token.selector {
    @apply text-purple-600 dark:text-purple-400;
}

.syntax-highlight-overlay .token.property {
    @apply text-blue-600 dark:text-blue-400;
}

.syntax-highlight-overlay .token.keyword {
    @apply text-purple-600 dark:text-purple-400;
}

.syntax-highlight-overlay .token.string {
    @apply text-green-600 dark:text-green-400;
}

.syntax-highlight-overlay .token.function {
    @apply text-yellow-600 dark:text-yellow-400;
}

.syntax-highlight-overlay .token.brace {
    @apply text-gray-600 dark:text-gray-400;
}

.syntax-highlight-overlay .token.semicolon {
    @apply text-gray-600 dark:text-gray-400;
}

.line-numbers {
    @apply border-r border-gray-200 bg-gray-100 px-2 py-4 dark:border-gray-600 dark:bg-gray-700;
    @apply select-none font-mono text-sm text-gray-500 dark:text-gray-400;
}

.line-number {
    @apply pr-2 text-right leading-5;
}

.line-number.error-line {
    @apply bg-red-50 text-red-600 dark:bg-red-900 dark:text-red-400;
}

/* Validation Errors */
.validation-errors {
    @apply border-t border-gray-200 bg-red-50 dark:border-gray-700 dark:bg-red-900;
}

.errors-header {
    @apply flex items-center justify-between border-b border-red-200 px-6 py-3 dark:border-red-800;
}

.errors-title {
    @apply text-sm font-semibold text-red-800 dark:text-red-200;
}

.clear-errors-btn {
    @apply p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200;
}

.errors-list {
    @apply max-h-40 overflow-y-auto;
}

.error-item {
    @apply flex cursor-pointer items-start gap-3 px-6 py-2 hover:bg-red-100 dark:hover:bg-red-800;
}

.error-icon {
    @apply mt-0.5 flex-shrink-0;
}

.error-content {
    @apply flex-1;
}

.error-message {
    @apply text-sm font-medium text-red-800 dark:text-red-200;
}

.error-location {
    @apply mt-1 text-xs text-red-600 dark:text-red-300;
}

/* Preview Modal */
.preview-modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.preview-modal {
    @apply mx-4 flex max-h-[80vh] w-full max-w-4xl flex-col rounded-xl bg-white shadow-xl dark:bg-gray-800;
}

.preview-header {
    @apply flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700;
}

.preview-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-preview-btn {
    @apply rounded p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.preview-content {
    @apply flex-1 p-6;
}

.preview-iframe {
    @apply h-full w-full rounded-lg border border-gray-200 dark:border-gray-700;
}

/* Loading Overlay */
.loading-overlay {
    @apply absolute inset-0 z-40 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-800;
}

.loading-content {
    @apply flex flex-col items-center gap-4;
}

.loading-spinner {
    @apply h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .editor-header {
        @apply flex-col gap-4;
    }

    .editor-tabs {
        @apply justify-center;
    }

    .editor-actions {
        @apply justify-center;
    }

    .code-editor {
        @apply text-xs;
    }

    .syntax-highlight-overlay {
        @apply text-xs;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .custom-code-editor {
        @apply border-2 border-gray-900 dark:border-white;
    }

    .editor-tab,
    .action-btn {
        @apply border-2;
    }

    .code-editor {
        @apply border-2 border-gray-900 dark:border-white;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .loading-spinner {
        @apply animate-none;
    }

    .editor-tab,
    .action-btn {
        @apply transition-none;
    }
}
</style>
