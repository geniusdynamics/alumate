<template>
  <div class="template-customizer">
    <!-- Main Container -->
    <div class="customizer-container">
      <!-- Header -->
      <header class="customizer-header">
        <div class="header-content">
          <div class="template-info">
            <h1 class="template-title">{{ template?.name || 'Custom Template' }}</h1>
            <div class="template-meta">
              <span class="template-category">{{ template?.category }}</span>
              <span class="template-separator">•</span>
              <span class="template-audience">{{ template?.audienceType }}</span>
            </div>
          </div>

          <div class="header-actions">
            <!-- Undo/Redo -->
            <div class="history-actions">
              <button
                @click="undo"
                :disabled="!canUndo"
                class="action-btn action-btn--secondary"
                :aria-label="`Undo ${lastActionSnap?.action || 'last change'}`"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Undo
              </button>
              <button
                @click="redo"
                :disabled="!canRedo"
                class="action-btn action-btn--secondary"
                :aria-label="`Redo ${nextActionSnap?.action || 'last undone change'}`"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 10h10a8 8 0 008 8v2M17 10l6 6m-6-6l6-6" />
                </svg>
                Redo
              </button>
            </div>

            <!-- Save/Actions -->
            <div class="save-actions">
              <button
                @click="discardChanges"
                :disabled="!hasUnsavedChanges"
                class="action-btn action-btn--ghost"
                aria-label="Discard all unsaved changes"
              >
                Discard Changes
              </button>
              <button
                @click="saveChanges"
                :disabled="!hasUnsavedChanges || isLoading"
                class="action-btn action-btn--primary"
                aria-label="Save all template customizations"
              >
                <span v-if="isLoading" class="loading-spinner"></span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="customizer-main">
        <!-- Sidebar -->
        <aside class="customizer-sidebar" :class="{ 'sidebar--collapsed': sidebarCollapsed }">
          <!-- Panel Navigation -->
          <nav class="panel-nav">
            <button
              v-for="panel in panels"
              :key="panel.id"
              @click="activePanel = panel.id"
              :class="['panel-nav-item', { 'active': activePanel === panel.id }]"
              :aria-pressed="activePanel === panel.id"
              :aria-label="`Switch to ${panel.label} panel`"
            >
              <component :is="panel.icon" class="panel-icon" />
              <span class="panel-label">{{ panel.label }}</span>
              <span v-if="panel.notifications" class="notification-badge">
                {{ panel.notifications }}
              </span>
            </button>
          </nav>

          <!-- Panel Content -->
          <div class="panel-content">
            <!-- Brand Panel -->
            <div v-if="activePanel === 'brand'" class="panel-section">
              <div class="panel-header">
                <h3 class="panel-title">Brand Customization</h3>
                <p class="panel-description">Customize colors, fonts, and logos for your brand</p>
              </div>

              <div class="panel-controls">
                <div class="control-group">
                  <label class="control-label">Brand Colors</label>
                  <div class="color-controls">
                    <color-picker
                      v-for="(color, colorKey) in customizationData.brand.colors"
                      :key="colorKey"
                      :model-value="color.color"
                      :label="colorKey"
                      :type="color.type"
                      @update:model-value="updateBrandColor(colorKey, $event)"
                    />
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label">Typography</label>
                  <div class="font-controls">
                    <font-selector
                      v-for="(font, fontKey) in customizationData.brand.fonts"
                      :key="fontKey"
                      :model-value="font"
                      :label="fontKey"
                      @update:model-value="updateBrandFont(fontKey, $event)"
                    />
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label">Brand Assets</label>
                  <div class="logo-controls">
                    <div
                      v-for="logo in customizationData.brand.logos"
                      :key="logo.id"
                      class="logo-item"
                    >
                      <img :src="logo.url" :alt="logo.alt" class="logo-preview" />
                      <div class="logo-info">
                        <span class="logo-name">{{ logo.name }}</span>
                        <span class="logo-type">{{ logo.type }}</span>
                      </div>
                      <button @click="removeLogo(logo.id)" class="remove-logo-btn">
                        Remove
                      </button>
                    </div>
                    <button @click="addLogo" class="add-logo-btn">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Add Logo
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Content Panel -->
            <div v-if="activePanel === 'content'" class="panel-section">
              <div class="panel-header">
                <h3 class="panel-title">Content Customization</h3>
                <p class="panel-description">Manage text, images, and content blocks</p>
              </div>

              <content-editor
                v-model="customizationData.content"
                :template="template"
                @update:model-value="updateContent"
              />
            </div>

            <!-- Settings Panel -->
            <div v-if="activePanel === 'settings'" class="panel-section">
              <div class="panel-header">
                <h3 class="panel-title">Settings</h3>
                <p class="panel-description">Configure customization preferences</p>
              </div>

              <div class="panel-controls">
                <div class="settings-group">
                  <h4 class="settings-group-title">General</h4>
                  <div class="setting-item">
                    <label class="setting-label">
                      <input
                        v-model="customizationSettings.autoSave"
                        type="checkbox"
                        class="setting-checkbox"
                      />
                      Auto-save changes
                    </label>
                  </div>
                  <div class="setting-item">
                    <label class="setting-label">
                      <input
                        v-model="customizationSettings.realTimePreview"
                        type="checkbox"
                        class="setting-checkbox"
                      />
                      Real-time preview
                    </label>
                  </div>
                </div>

                <div class="settings-group">
                  <h4 class="settings-group-title">Accessibility</h4>
                  <div class="setting-item">
                    <label class="setting-label">
                      <input
                        v-model="customizationSettings.showAccessibilityWarnings"
                        type="checkbox"
                        class="setting-checkbox"
                      />
                      Show accessibility warnings
                    </label>
                  </div>
                </div>

                <div class="settings-group">
                  <h4 class="settings-group-title">Export</h4>
                  <div class="setting-item">
                    <label class="setting-label">Export formats:</label>
                    <div class="export-formats">
                      <label v-for="format in exportFormats" :key="format" class="format-label">
                        <input
                          v-model="customizationSettings.exportFormats"
                          :value="format"
                          type="checkbox"
                          class="format-checkbox"
                        />
                        {{ format.toUpperCase() }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <!-- Main Preview Area -->
        <section class="customizer-preview">
          <div class="preview-header">
            <h2 class="preview-title">Preview</h2>
            <div class="preview-controls">
              <div class="viewport-controls">
                <button
                  v-for="viewport in viewports"
                  :key="viewport.type"
                  @click="activeViewport = viewport.type"
                  :class="['viewport-btn', { 'active': activeViewport === viewport.type }]"
                  :aria-pressed="activeViewport === viewport.type"
                  :aria-label="`Switch to ${viewport.label} preview`"
                >
                  <component :is="viewport.icon" class="viewport-icon" />
                  {{ viewport.label }}
                </button>
              </div>
              <button
                @click="toggleFullScreen"
                class="fullscreen-btn"
                :aria-label="isFullScreen ? 'Exit fullscreen preview' : 'Enter fullscreen preview'"
              >
                <component :is="isFullScreen ? ArrowsPointingInIcon : ArrowsPointingOutIcon" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="preview-container" :class="{ 'fullscreen': isFullScreen }">
            <div v-if="previewLoading" class="preview-loading">
              <div class="loading-spinner"></div>
              <p>Generating preview...</p>
            </div>

            <div v-else-if="previewError" class="preview-error">
              <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <h4 class="error-title">Preview Error</h4>
              <p class="error-message">{{ previewError }}</p>
              <button @click="generatePreview" class="retry-btn">Retry</button>
            </div>

            <iframe
              v-else-if="previewSrc"
              :srcdoc="previewSrc"
              class="preview-iframe"
              :class="`viewport-${activeViewport}`"
              :title="`${template?.name} customization preview`"
              sandbox="allow-scripts"
            ></iframe>

            <div v-else class="preview-empty">
              <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h4 class="empty-title">No Preview Available</h4>
              <p class="empty-message">Preview will appear once you make customizations</p>
            </div>
          </div>
        </section>
      </main>

      <!-- Sidebar Toggle Button -->
      <button
        @click="toggleSidebar"
        class="sidebar-toggle"
        :aria-label="sidebarCollapsed ? 'Show sidebar' : 'Hide sidebar'"
      >
        <svg
          :class="{ 'rotate-180': sidebarCollapsed }"
          class="w-5 h-5 transition-transform"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Keyboard Shortcuts Info -->
    <div v-if="showKeyboardShortcuts" class="keyboard-shortcuts-overlay">
      <div class="shortcuts-modal">
        <h3 class="shortcuts-title">Keyboard Shortcuts</h3>
        <dl class="shortcuts-list">
          <dt>Ctrl+S</dt><dd>Save changes</dd>
          <dt>Ctrl+Z</dt><dd>Undo</dd>
          <dt>Ctrl+Y</dt><dd>Redo</dd>
          <dt>Ctrl+P</dt><dd>Toggle preview panel</dd>
          <dt>Ctrl+K</dt><dd>Show shortcuts</dd>
          <dt>Escape</dt><dd>Close active panel</dd>
        </dl>
        <button @click="showKeyboardShortcuts = false" class="close-shortcuts-btn">
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { templateService } from '@/services/TemplateService'
import ColorPicker from './ColorPicker.vue'
import FontSelector from './FontSelector.vue'
import ContentEditor from './ContentEditor.vue'

// Icons
import {
  SwatchIcon,
  DocumentTextIcon,
  CogIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  DeviceTabletIcon,
  ArrowsPointingOutIcon,
  ArrowsPointingInIcon
} from '@heroicons/vue/24/outline'

// Types
import type {
  Template,
  CustomizationState,
  ContentCustomization,
  TemplateCustomizationConfig,
  UndoRedoSnapshot,
  ViewportType
} from '@/types/components'

// Props
interface Props {
  modelValue: TemplateCustomizationConfig
  template: Template | null
  initialPanel?: CustomizationState['activePanel']
}

const props = withDefaults(defineProps<Props>(), {
  template: null,
  initialPanel: 'brand'
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: TemplateCustomizationConfig]
  saved: [config: TemplateCustomizationConfig]
  discarded: []
}>()

// Reactive data
const customizationData = ref<TemplateCustomizationConfig>(props.modelValue)
const template = ref<Template | null>(props.template)
const activePanel = ref<CustomizationState['activePanel']>(props.initialPanel)
const activeViewport = ref<ViewportType>('desktop')
const isLoading = ref(false)
const previewLoading = ref(false)
const previewError = ref('')
const previewSrc = ref('')
const sidebarCollapsed = ref(false)
const isFullScreen = ref(false)
const showKeyboardShortcuts = ref(false)
const undoStack = ref<UndoRedoSnapshot[]>([])
const redoStack = ref<UndoRedoSnapshot[]>([])

// Viewport configurations
const viewports = [
  { type: 'desktop' as ViewportType, label: 'Desktop', icon: ComputerDesktopIcon },
  { type: 'tablet' as ViewportType, label: 'Tablet', icon: DeviceTabletIcon },
  { type: 'mobile' as ViewportType, label: 'Mobile', icon: DevicePhoneMobileIcon }
]

// Panel navigation
const panels = [
  {
    id: 'brand' as const,
    label: 'Brand',
    icon: SwatchIcon,
    notifications: undefined
  },
  {
    id: 'content' as const,
    label: 'Content',
    icon: DocumentTextIcon,
    notifications: undefined
  },
  {
    id: 'settings' as const,
    label: 'Settings',
    icon: CogIcon,
    notifications: undefined
  }
]

// Export formats
const exportFormats = ['pdf', 'png', 'jpg', 'html']

// Settings (computed from customization data)
const customizationSettings = computed({
  get: () => customizationData.value.settings,
  set: (value) => {
    customizationData.value.settings = value
  }
})

// Computed properties
const hasUnsavedChanges = computed(() => {
  // Compare current state with original
  return JSON.stringify(customizationData.value) !== JSON.stringify(props.modelValue)
})

const canUndo = computed(() => undoStack.value.length > 0)
const canRedo = computed(() => redoStack.value.length > 0)

const lastActionSnap = computed(() => {
  return undoStack.value[undoStack.value.length - 1]
})

const nextActionSnap = computed(() => {
  return redoStack.value[redoStack.value.length - 1]
})

// Methods
const updateBrandColor = (colorKey: string, value: string) => {
  updateCustomization(() => {
    customizationData.value.brand.colors[colorKey].color = value
  }, `Update ${colorKey} color`)
}

const updateBrandFont = (fontKey: string, fontData: any) => {
  updateCustomization(() => {
    customizationData.value.brand.fonts[fontKey] = fontData
  }, `Update ${fontKey} font`)
}

const updateContent = (contentData: ContentCustomization) => {
  updateCustomization(() => {
    customizationData.value.content = contentData
  }, 'Update content')
}

const addLogo = () => {
  // Implementation for adding logo
  console.log('Add logo functionality to be implemented')
}

const removeLogo = (logoId: string) => {
  updateCustomization(() => {
    customizationData.value.brand.logos = customizationData.value.brand.logos.filter(
      logo => logo.id !== logoId
    )
  }, 'Remove logo')
}

const updateCustomization = (updateFn: () => void, action: string) => {
  // Create snapshot for undo/redo
  const snapshot: UndoRedoSnapshot = {
    id: Date.now().toString(),
    timestamp: new Date(),
    action,
    brand: JSON.parse(JSON.stringify(customizationData.value.brand)),
    content: JSON.parse(JSON.stringify(customizationData.value.content))
  }

  undoStack.value.push(snapshot)
  redoStack.value.length = 0 // Clear redo stack

  // Apply the update
  updateFn()

  // Emit update
  emit('update:modelValue', customizationData.value)

  // Auto-generate preview if enabled
  if (customizationData.value.settings.realTimePreview) {
    nextTick(() => generatePreview())
  }
}

const undo = () => {
  if (!canUndo.value) return

  const snapshot = undoStack.value.pop()!
  const currentSnapshot: UndoRedoSnapshot = {
    id: Date.now().toString(),
    timestamp: new Date(),
    action: `Undo ${snapshot.action}`,
    brand: customizationData.value.brand,
    content: customizationData.value.content
  }

  redoStack.value.push(currentSnapshot)

  if (snapshot.brand) customizationData.value.brand = snapshot.brand
  if (snapshot.content) customizationData.value.content = snapshot.content

  emit('update:modelValue', customizationData.value)
}

const redo = () => {
  if (!canRedo.value) return

  const snapshot = redoStack.value.pop()!
  const currentSnapshot: UndoRedoSnapshot = {
    id: Date.now().toString(),
    timestamp: new Date(),
    action: `Redo ${snapshot.action}`,
    brand: customizationData.value.brand,
    content: customizationData.value.content
  }

  undoStack.value.push(currentSnapshot)

  if (snapshot.brand) customizationData.value.brand = snapshot.brand
  if (snapshot.content) customizationData.value.content = snapshot.content

  emit('update:modelValue', customizationData.value)
}

const saveChanges = async () => {
  if (!hasUnsavedChanges.value) return

  try {
    isLoading.value = true

    // Save customization data (placeholder for API call)
    const saved = customizationData.value

    // Update the model value
    customizationData.value = saved

    // Emit saved event
    emit('saved', saved)

    // Clear undo/redo stacks
    undoStack.value.length = 0
    redoStack.value.length = 0

  } catch (error) {
    console.error('Failed to save customization:', error)
    previewError.value = error instanceof Error ? error.message : 'Save failed'
  } finally {
    isLoading.value = false
  }
}

const discardChanges = () => {
  if (confirm('Are you sure you want to discard all unsaved changes?')) {
    customizationData.value = JSON.parse(JSON.stringify(props.modelValue))
    undoStack.value.length = 0
    redoStack.value.length = 0

    emit('update:modelValue', customizationData.value)
    emit('discarded')
  }
}

const generatePreview = async () => {
  if (!template.value) return

  try {
    previewLoading.value = true
    previewError.value = ''

    const result = await templateService.generatePreview(
      template.value.id,
      {
        templateId: template.value.id,
        viewport: activeViewport.value,
        showControls: false,
        interactive: false
      }
    )

    previewSrc.value = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>${result.css}</style>
      </head>
      <body>${result.html}</body>
      </html>
    `

  } catch (error) {
    console.error('Failed to generate preview:', error)
    previewError.value = error instanceof Error ? error.message : 'Preview generation failed'
  } finally {
    previewLoading.value = false
  }
}

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const toggleFullScreen = () => {
  isFullScreen.value = !isFullScreen.value
}

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 's':
        event.preventDefault()
        saveChanges()
        break
      case 'z':
        event.preventDefault()
        undo()
        break
      case 'y':
        event.preventDefault()
        redo()
        break
      case 'p':
        event.preventDefault()
        toggleFullScreen()
        break
      case 'k':
        event.preventDefault()
        showKeyboardShortcuts.value = !showKeyboardShortcuts.value
        break
    }
  } else if (event.key === 'Escape') {
    if (showKeyboardShortcuts.value) {
      showKeyboardShortcuts.value = false
    } else if (isFullScreen.value) {
      isFullScreen.value = false
    }
  }
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(customizationData.value)) {
    customizationData.value = newValue
  }
})

watch(customizationSettings, () => {
  if (customizationData.value.settings.realTimePreview) {
    generatePreview()
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)

  // Generate initial preview
  if (customizationData.value.settings.realTimePreview) {
    generatePreview()
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.template-customizer {
  @apply h-screen flex flex-col bg-gray-50 dark:bg-gray-900;
}

.customizer-container {
  @apply flex flex-col h-full;
}

/* Header */
.customizer-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4;
}

.header-content {
  @apply flex items-center justify-between;
}

.template-info {
  @apply flex flex-col;
}

.template-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.template-meta {
  @apply flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.template-separator {
  @apply text-gray-400;
}

.header-actions {
  @apply flex items-center gap-4;
}

.history-actions,
.save-actions {
  @apply flex items-center gap-2;
}

.action-btn {
  @apply px-3 py-2 text-sm font-medium rounded-lg transition-colors focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.action-btn--primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.action-btn--secondary {
  @apply bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600;
}

.action-btn--ghost {
  @apply bg-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100;
}

/* Main Content */
.customizer-main {
  @apply flex flex-1 overflow-hidden;
}

/* Sidebar */
.customizer-sidebar {
  @apply w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300;
}

.customizer-sidebar.sidebar--collapsed {
  @apply w-16;
}

/* Panel Navigation */
.panel-nav {
  @apply p-4 border-b border-gray-200 dark:border-gray-700;
}

.panel-nav-item {
  @apply w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors mb-1;
  @apply text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700;
  @apply focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

.panel-nav-item.active {
  @apply bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300;
}

.panel-nav-item .panel-icon {
  @apply w-5 h-5 flex-shrink-0;
}

.panel-nav-item .panel-label {
  @apply transition-opacity;
}

.customizer-sidebar.sidebar--collapsed .panel-label {
  @apply opacity-0;
}

.notification-badge {
  @apply ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full;
}

/* Panel Content */
.panel-content {
  @apply flex-1 overflow-y-auto p-4;
}

.panel-section {
  @apply space-y-6;
}

.panel-header {
  @apply mb-4;
}

.panel-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.panel-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

/* Controls */
.panel-controls,
.color-controls,
.font-controls {
  @apply space-y-4;
}

.control-group,
.settings-group {
  @apply space-y-3;
}

.control-label,
.settings-group-title {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.logo-controls {
  @apply space-y-2;
}

.logo-item {
  @apply flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.logo-preview {
  @apply w-10 h-10 object-contain rounded;
}

.logo-info {
  @apply flex-1 min-w-0;
}

.logo-name {
  @apply text-sm font-medium text-gray-900 dark:text-white block;
}

.logo-type {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.remove-logo-btn,
.add-logo-btn {
  @apply px-3 py-1 text-sm font-medium rounded transition-colors;
}

.remove-logo-btn {
  @apply text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20;
}

.add-logo-btn {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

/* Preview Area */
.customizer-preview {
  @apply flex-1 flex flex-col;
}

.preview-header {
  @apply flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700;
}

.preview-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.preview-controls {
  @apply flex items-center gap-4;
}

.viewport-controls {
  @apply flex items-center;
}

.viewport-btn {
  @apply px-3 py-2 text-sm font-medium rounded-lg transition-colors;
  @apply text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700;
  @apply focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

.viewport-btn.active {
  @apply bg-blue-600 text-white;
}

.viewport-btn .viewport-icon {
  @apply w-4 h-4 mr-2;
}

.fullscreen-btn {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors;
  @apply focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

.preview-container {
  @apply flex-1 bg-white m-6 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-all;
}

.preview-container.fullscreen {
  @apply m-0 rounded-none;
}

.preview-loading,
.preview-error,
.preview-empty {
  @apply flex flex-col items-center justify-center h-full text-center px-8;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4;
}

.error-icon,
.empty-icon {
  @apply w-12 h-12 text-red-500 mb-4;
}

.error-title,
.empty-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-2;
}

.error-message,
.empty-message {
  @apply text-gray-600 dark:text-gray-400 mb-4;
}

.retry-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.preview-iframe {
  @apply w-full h-full border-0;
}

.preview-iframe.viewport-desktop {
  /* Desktop styles */
}

.preview-iframe.viewport-tablet {
  @apply transform scale-75 origin-top;
  transform-origin: top;
}

.preview-iframe.viewport-mobile {
  @apply transform scale-50 origin-top;
  transform-origin: top;
  max-width: 375px;
  margin: 0 auto;
}

/* Sidebar Toggle */
.sidebar-toggle {
  @apply absolute top-20 left-0 z-10 bg-white dark:bg-gray-800 border-r border-t border-b border-gray-200 dark:border-gray-700;
  @apply px-2 py-6 rounded-r-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors;
  @apply focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800;
}

.customizer-sidebar.sidebar--collapsed .sidebar-toggle {
  @apply transform -translate-x-0;
}

/* Keyboard Shortcuts */
.keyboard-shortcuts-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.shortcuts-modal {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full;
}

.shortcuts-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-4;
}

.shortcuts-list {
  @apply space-y-2 mb-4;
}

.shortcuts-list dt {
  @apply font-medium text-gray-900 dark:text-white inline-block min-w-[100px];
}

.shortcuts-list dd {
  @apply text-gray-600 dark:text-gray-400;
}

.close-shortcuts-btn {
  @apply w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

/* Settings */
.setting-item {
  @apply flex items-center justify-between py-2;
}

.setting-label {
  @apply flex items-center gap-3 cursor-pointer;
}

.setting-checkbox,
.format-checkbox {
  @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.export-formats {
  @apply flex flex-wrap gap-2 mt-2;
}

.format-label {
  @apply flex items-center gap-2;
}

/* Responsive Design */
@media (max-width: 768px) {
  .customizer-sidebar {
    @apply w-64;
  }

  .customizer-sidebar.sidebar--collapsed {
    @apply w-12;
  }

  .header-actions {
    @apply flex-col gap-2;
  }

  .history-actions,
  .save-actions {
    @apply flex-col w-full;
  }

  .action-btn {
    @apply w-full;
  }

  .preview-header {
    @apply flex-col gap-4;
  }

  .viewport-controls {
    @apply justify-center;
  }

  .preview-container {
    @apply m-4;
  }

  .sidebar-toggle {
    @apply top-16;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .template-customizer {
    @apply bg-gray-900;
  }

  .viewport-btn.active {
    @apply bg-blue-600 text-white;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    @apply animate-none;
  }

  .customizer-sidebar {
    @apply transition-none;
  }

  .action-btn,
  .viewport-btn,
  .sidebar-toggle {
    @apply transition-none;
  }
}
</style>