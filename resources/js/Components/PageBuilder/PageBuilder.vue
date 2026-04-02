<template>
    <div class="page-builder" :class="{ loading: isLoading }">
        <!-- Page Builder Header -->
        <div class="page-builder-header">
            <div class="header-left">
                <h1 class="page-title">{{ pageData?.name || 'New Page' }}</h1>
                <div class="page-status">
                    <span :class="['status-badge', pageData?.status || 'draft']">
                        {{ (pageData?.status || 'draft').toUpperCase() }}
                    </span>
                    <span v-if="lastSaved" class="last-saved"> Last saved: {{ formatTime(lastSaved) }} </span>
                </div>
            </div>

            <div class="header-right">
                <div class="header-actions">
                    <!-- Template Actions -->
                    <button @click="showTemplateLibrary = true" class="header-btn template-btn" :title="'Browse templates'">
                        <Icon name="template" class="h-4 w-4" />
                        Templates
                    </button>

                    <!-- Component Actions -->
                    <button @click="showComponentLibrary = true" class="header-btn component-btn" :title="'Browse Components'">
                        <Icon name="component" class="h-4 w-4" />
                        Components
                    </button>

                    <!-- Advanced Styling Actions -->
                    <button
                        @click="showAdvancedStyling = !showAdvancedStyling"
                        :class="['header-btn styling-btn', { active: showAdvancedStyling }]"
                        :title="'Advanced styling'"
                    >
                        <Icon name="palette" class="h-4 w-4" />
                        Styling
                    </button>

                    <!-- Custom Code Actions -->
                    <button @click="showCustomCodePanel = true" class="header-btn custom-code-btn" :title="'Custom code'">
                        <Icon name="code" class="h-4 w-4" />
                        Custom Code
                    </button>

                    <!-- Responsive Design Tools -->
                    <button
                        @click="showResponsiveTools = !showResponsiveTools"
                        :class="['header-btn responsive-btn', { active: showResponsiveTools }]"
                        :title="'Responsive design tools'"
                    >
                        <Icon name="smartphone" class="h-4 w-4" />
                        Responsive
                    </button>

                    <!-- Live Preview -->
                    <button
                        @click="showLivePreview = !showLivePreview"
                        :class="['header-btn preview-btn', { active: showLivePreview }]"
                        :title="'Live preview'"
                    >
                        <Icon name="eye" class="h-4 w-4" />
                        Live Preview
                    </button>

                    <!-- Save Actions -->
                    <button @click="savePage" :disabled="isSaving" class="header-btn save-btn" :title="isSaving ? 'Saving...' : 'Save page'">
                        <Icon v-if="isSaving" name="spinner" class="h-4 w-4 animate-spin" />
                        <Icon v-else name="save" class="h-4 w-4" />
                        {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>

                    <!-- Preview Actions -->
                    <button @click="openPreview" class="header-btn preview-btn" :title="'Preview page'">
                        <Icon name="eye" class="h-4 w-4" />
                        Preview
                    </button>

                    <!-- Publish Actions -->
                    <button
                        @click="showPublishDialog = true"
                        :disabled="isPublishing"
                        class="header-btn publish-btn"
                        :title="isPublishing ? 'Publishing...' : 'Publish page'"
                    >
                        <Icon v-if="isPublishing" name="spinner" class="h-4 w-4 animate-spin" />
                        <Icon v-else name="upload" class="h-4 w-4" />
                        {{ isPublishing ? 'Publishing...' : 'Publish' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Undo/Redo Toolbar -->
        <UndoRedoToolbar :grapeJS-editor="grapeJSEditor" @history-change="onHistoryChange" @save="savePage" />

        <!-- Main Page Builder Content -->
        <div class="page-builder-content">
            <!-- Left Sidebar - Component Library -->
            <div v-if="showComponentLibrary" class="left-sidebar">
                <ComponentLibraryPanel
                    @close="showComponentLibrary = false"
                    @component-selected="onComponentSelected"
                    @component-drag-start="onComponentDragStart"
                />
            </div>

            <!-- Main Editor Area -->
            <div class="main-editor">
                <VueGrapeJSWrapper
                    ref="grapeJSEditor"
                    :page-data="pageData"
                    :config="editorConfig"
                    @save="onEditorSave"
                    @publish="onEditorPublish"
                    @component-selected="onEditorComponentSelected"
                    @page-updated="onPageUpdated"
                    @editor-ready="onEditorReady"
                />
            </div>

            <!-- Right Sidebar - Properties Panel -->
            <div v-if="showPropertiesPanel" class="right-sidebar">
                <PropertyPanel
                    :selected-component="selectedComponent"
                    :grapeJS-editor="grapeJSEditor?.editor"
                    @close="showPropertiesPanel = false"
                    @property-changed="onPropertyChanged"
                    @style-changed="onStyleChanged"
                />
            </div>

            <!-- Right Sidebar - Advanced Styling Panel -->
            <div v-if="showAdvancedStyling" class="right-sidebar">
                <AdvancedStylingPanel
                    :selected-component="selectedComponent"
                    :grapeJS-editor="grapeJSEditor?.editor"
                    @close="showAdvancedStyling = false"
                    @style-updated="onAdvancedStyleUpdated"
                />
            </div>

            <!-- Right Sidebar - Custom Code Panel -->
            <div v-if="showCustomCodePanel" class="right-sidebar">
                <CustomCodePanel
                    :tenant-id="tenantId"
                    :page-id="pageData?.id"
                    @close="showCustomCodePanel = false"
                    @code-applied="onCustomCodeApplied"
                />
            </div>

            <!-- Right Sidebar - Responsive Design Tools -->
            <div v-if="showResponsiveTools" class="right-sidebar">
                <ResponsiveDesignTools
                    :grapeJS-editor="grapeJSEditor"
                    :selected-component="selectedComponent"
                    @device-change="onDeviceChange"
                    @responsive-styles-update="onResponsiveStylesUpdate"
                />
            </div>
        </div>

        <!-- Live Preview Panel -->
        <div v-if="showLivePreview" class="live-preview-panel">
            <LivePreview
                :page-id="pageData?.id"
                :grapeJS-editor="grapeJSEditor"
                :auto-update="isRealTimeEnabled"
                @interaction-detected="onInteractionDetected"
                @performance-update="onPerformanceUpdate"
            />
        </div>

        <!-- Template Library Modal -->
        <TemplateLibraryPanel
            v-if="showTemplateLibrary"
            @close="showTemplateLibrary = false"
            @template-selected="onTemplateSelected"
            @template-applied="onTemplateApplied"
        />

        <!-- Publish Dialog -->
        <PublishDialog
            v-if="showPublishDialog"
            :page-data="pageData"
            :is-publishing="isPublishing"
            @close="showPublishDialog = false"
            @publish="publishPage"
        />

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-content">
                <Icon name="spinner" class="h-8 w-8 animate-spin" />
                <p>{{ loadingMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePageBuilder } from '../../Composables/usePageBuilder';
import { useToast } from '../../Composables/useToast';
import { autoSaveService } from '../../Services/AutoSaveService';
import { realTimeEditingService } from '../../Services/RealTimeEditingService';
import Icon from '../ui/Icon.vue';
import VueGrapeJSWrapper from '../VueGrapeJSWrapper.vue';
import AdvancedStylingPanel from './AdvancedStylingPanel.vue';
import ComponentLibraryPanel from './ComponentLibraryPanel.vue';
import CustomCodePanel from './CustomCodePanel.vue';
import LivePreview from './LivePreview.vue';
import PropertyPanel from './PropertyPanel.vue';
import PublishDialog from './PublishDialog.vue';
import ResponsiveDesignTools from './ResponsiveDesignTools.vue';
import TemplateLibraryPanel from './TemplateLibraryPanel.vue';
import UndoRedoToolbar from './UndoRedoToolbar.vue';

// Props
interface Props {
    pageId?: string | number;
    initialData?: any;
    mode?: 'create' | 'edit';
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'edit',
});

// Composables
const route = useRoute();
const router = useRouter();
const { props: pageProps } = usePage();
const { showToast } = useToast();
const {
    pageData,
    isLoading,
    isSaving,
    isPublishing,
    loadingMessage,
    savePage: savePageData,
    publishPage: publishPageData,
    loadPage,
} = usePageBuilder();

// Refs
const grapeJSEditor = ref<InstanceType<typeof VueGrapeJSWrapper>>();

// State
const showComponentLibrary = ref(true);
const showTemplateLibrary = ref(false);
const showPropertiesPanel = ref(false);
const showAdvancedStyling = ref(false);
const showCustomCodePanel = ref(false);
const showPublishDialog = ref(false);
const showResponsiveTools = ref(false);
const showLivePreview = ref(false);
const selectedComponent = ref<any>(null);
const lastSaved = ref<Date | null>(null);

// Real-time editing state
const deviceMode = ref('desktop');
const isRealTimeEnabled = ref(true);

// Tenant ID (would come from auth context or props)
const tenantId = computed(() => pageProps.auth?.user?.tenant_id || 'default');

// Editor Configuration
const editorConfig = reactive({
    height: 'calc(100vh - 120px)',
    showDeviceManager: true,
    showLayersManager: true,
    showStyleManager: true,
    showTraitsManager: true,
    showBlockManager: true,
    plugins: [
        'gjs-blocks-basic',
        'grapesjs-plugin-forms',
        'grapesjs-component-countdown',
        'grapesjs-plugin-export',
        'grapesjs-tabs',
        'grapesjs-custom-code',
        'grapesjs-touch',
        'grapesjs-parser-postcss',
        'grapesjs-tooltip',
        'grapesjs-tui-image-editor',
        'grapesjs-typed',
        'grapesjs-style-bg',
    ],
    pluginsOpts: {
        'gjs-blocks-basic': { flexGrid: true },
        'grapesjs-plugin-forms': {
            blocks: ['form', 'input', 'textarea', 'select', 'button', 'label', 'checkbox', 'radio'],
        },
    },
});

// Computed
const canSave = computed(() => {
    return !isSaving.value && pageData.value && hasUnsavedChanges.value;
});

const hasUnsavedChanges = computed(() => {
    // Logic to detect if there are unsaved changes
    return true; // Simplified for now
});

// Methods
const initializePageBuilder = async () => {
    try {
        isLoading.value = true;
        loadingMessage.value = 'Initializing page builder...';

        if (props.pageId) {
            await loadPage(props.pageId);
        } else if (props.initialData) {
            pageData.value = props.initialData;
        } else {
            // Create new page
            pageData.value = {
                name: 'New Page',
                status: 'draft',
                content: '',
                styles: '',
                Components: [],
            };
        }

        // Initialize GrapeJS editor
        await nextTick();
        if (grapeJSEditor.value) {
            await grapeJSEditor.value.initialize();
        }
    } catch (error) {
        console.error('Failed to initialize page builder:', error);
        showToast('Failed to initialize page builder', 'error');
    } finally {
        isLoading.value = false;
    }
};

const onEditorReady = (editor: any) => {
    // Initialize real-time editing service
    realTimeEditingService.initialize(editor);

    // Initialize auto-save service
    autoSaveService.initialize(async (data) => {
        await savePageData({
            ...pageData.value,
            content: data.html,
            styles: data.css,
            Components: data.Components,
        });
    });

    // Setup real-time updates
    setupRealTimeUpdates();
};

const savePage = async () => {
    try {
        if (!grapeJSEditor.value) return;

        const editorData = grapeJSEditor.value.getEditorData();
        await savePageData({
            ...pageData.value,
            content: editorData.html,
            styles: editorData.css,
            Components: editorData.Components,
        });

        lastSaved.value = new Date();
        showToast('Page saved successfully', 'success');
    } catch (error) {
        console.error('Failed to save page:', error);
        showToast('Failed to save page', 'error');
    }
};

const publishPage = async (publishOptions: any) => {
    try {
        await savePage(); // Save first
        await publishPageData(publishOptions);
        showToast('Page published successfully', 'success');
        showPublishDialog.value = false;
    } catch (error) {
        console.error('Failed to publish page:', error);
        showToast('Failed to publish page', 'error');
    }
};

const openPreview = () => {
    if (!grapeJSEditor.value) return;

    const editorData = grapeJSEditor.value.getEditorData();
    const previewUrl = `/preview/${pageData.value?.id || 'new'}`;

    // Open preview in new tab
    window.open(previewUrl, '_blank');
};

// Event Handlers
const onComponentSelected = (component: any) => {
    selectedComponent.value = component;
    showPropertiesPanel.value = true;
};

const onComponentDragStart = (component: any) => {
    if (grapeJSEditor.value) {
        grapeJSEditor.value.addComponent(component);
    }
};

const onEditorSave = async (data: any) => {
    await savePage();
};

const onEditorPublish = async (data: any) => {
    showPublishDialog.value = true;
};

const onCustomCodeApplied = (codeData: any) => {
    if (grapeJSEditor.value) {
        // Apply custom code to the editor
        grapeJSEditor.value.applyCustomCode(codeData);
    }
    showToast('Custom code applied successfully', 'success');
};

const onEditorComponentSelected = (component: any) => {
    selectedComponent.value = component;
    showPropertiesPanel.value = !!component;
};

const onPageUpdated = (data: any) => {
    // Handle page updates from editor
    Object.assign(pageData.value, data);
};

const onTemplateSelected = (template: any) => {
    // Handle template selection
    showTemplateLibrary.value = false;
};

const onTemplateApplied = (template: any) => {
    if (grapeJSEditor.value) {
        grapeJSEditor.value.loadTemplate(template);
    }
    showTemplateLibrary.value = false;
    showToast('Template applied successfully', 'success');
};

const onPropertyChanged = (property: string, value: any) => {
    if (grapeJSEditor.value && selectedComponent.value) {
        grapeJSEditor.value.updateComponentProperty(selectedComponent.value.id, property, value);
    }
};

const onStyleChanged = (styles: any) => {
    if (grapeJSEditor.value && selectedComponent.value) {
        grapeJSEditor.value.updateComponentStyles(selectedComponent.value.id, styles);
    }
};

const onAdvancedStyleUpdated = (componentId: string, styles: any) => {
    if (grapeJSEditor.value) {
        grapeJSEditor.value.updateComponentStyles(componentId, styles);
    }
    showToast('Advanced styles applied successfully', 'success');
};

const onDeviceChange = (device: string) => {
    deviceMode.value = device;
    realTimeEditingService.switchDevice(device);
};

const onResponsiveStylesUpdate = (styles: any) => {
    // Handle responsive styles update
    showToast('Responsive styles updated', 'success');
};

const onInteractionDetected = (interaction: any) => {
    // Handle interaction detection in live preview
    logger.log('Interaction detected:', interaction);
};

const onPerformanceUpdate = (metrics: any) => {
    // Handle performance metrics update
    logger.log('Performance metrics:', metrics);
};

const onHistoryChange = (index: number) => {
    // Handle history navigation
    showToast('History updated', 'info');
};

const setupRealTimeUpdates = () => {
    // Listen for auto-save requests
    window.addEventListener('autosave:request-data', () => {
        if (grapeJSEditor.value && realTimeEditingService.editingState.hasUnsavedChanges) {
            const data = realTimeEditingService.getEditorData();
            if (data) {
                autoSaveService.triggerSave({
                    html: data.html,
                    css: data.css,
                    Components: data.Components,
                    timestamp: Date.now(),
                });
            }
        }
    });

    // Listen for auto-save events
    window.addEventListener('autosave:success', (event: any) => {
        lastSaved.value = event.detail.timestamp;
        showToast('Auto-saved successfully', 'success');
    });

    window.addEventListener('autosave:error', (event: any) => {
        showToast(`Auto-save failed: ${event.detail.error}`, 'error');
    });
};

const formatTime = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
};

// Auto-save functionality
let autoSaveInterval: NodeJS.Timeout | null = null;

const startAutoSave = () => {
    if (autoSaveInterval) return;

    autoSaveInterval = setInterval(async () => {
        if (hasUnsavedChanges.value && !isSaving.value) {
            await savePage();
        }
    }, 30000); // Auto-save every 30 seconds
};

const stopAutoSave = () => {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
        autoSaveInterval = null;
    }
};

// Keyboard shortcuts
const handleKeyboardShortcuts = (event: KeyboardEvent) => {
    if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
            case 's':
                event.preventDefault();
                savePage();
                break;
            case 'p':
                event.preventDefault();
                openPreview();
                break;
        }
    }
};

// Lifecycle
onMounted(async () => {
    await initializePageBuilder();
    startAutoSave();
    document.addEventListener('keydown', handleKeyboardShortcuts);
});

onUnmounted(() => {
    stopAutoSave();
    document.removeEventListener('keydown', handleKeyboardShortcuts);

    // Cleanup Services
    realTimeEditingService.destroy();
    autoSaveService.destroy();
});

// Watch for route changes
watch(
    () => route.params.id,
    async (newId) => {
        if (newId && newId !== props.pageId) {
            await loadPage(newId);
        }
    },
);
</script>

<style scoped>
.page-builder {
    @apply flex h-screen flex-col bg-gray-50 dark:bg-gray-900;
}

.page-builder.loading {
    @apply pointer-events-none;
}

.page-builder-header {
    @apply flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800;
}

.header-left {
    @apply flex items-center space-x-4;
}

.page-title {
    @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.page-status {
    @apply flex items-center space-x-2;
}

.status-badge {
    @apply rounded-full px-2 py-1 text-xs font-medium;
}

.status-badge.draft {
    @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300;
}

.status-badge.published {
    @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300;
}

.last-saved {
    @apply text-sm text-gray-500 dark:text-gray-400;
}

.header-right {
    @apply flex items-center;
}

.header-actions {
    @apply flex items-center space-x-2;
}

.header-btn {
    @apply flex items-center space-x-2 rounded-md px-3 py-2 text-sm font-medium transition-colors;
}

.template-btn {
    @apply bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800;
}

.component-btn {
    @apply bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800;
}

.save-btn {
    @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.save-btn:disabled {
    @apply cursor-not-allowed opacity-50;
}

.preview-btn {
    @apply bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800;
}

.publish-btn {
    @apply bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800;
}

.publish-btn:disabled {
    @apply cursor-not-allowed opacity-50;
}

.responsive-btn {
    @apply bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800;
}

.responsive-btn.active {
    @apply bg-purple-200 dark:bg-purple-800;
}

.header-btn.active {
    @apply ring-2 ring-blue-500 ring-opacity-50;
}

.page-builder-content {
    @apply flex flex-1 overflow-hidden;
}

.left-sidebar {
    @apply w-80 overflow-y-auto border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
}

.main-editor {
    @apply flex-1 overflow-hidden;
}

.right-sidebar {
    @apply w-80 overflow-y-auto border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
}

.loading-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.loading-content {
    @apply flex flex-col items-center space-y-4 rounded-lg bg-white p-6 dark:bg-gray-800;
}

.loading-content p {
    @apply text-gray-600 dark:text-gray-400;
}

.live-preview-panel {
    @apply fixed bottom-0 left-0 right-0 z-40 h-96 border-t border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800;
}
</style>
