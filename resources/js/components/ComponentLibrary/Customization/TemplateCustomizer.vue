
<template>
  <div class="template-customizer" role="application" aria-labelledby="customizer-title">
    <!-- Header -->
    <div class="customizer-header">
      <div class="header-content">
        <h1 id="customizer-title" class="customizer-title">Template Customizer</h1>
        <p class="customizer-subtitle">Customize your template's brand and content</p>
      </div>

      <div class="header-actions">
        <button
          @click="autoSave = !autoSave"
          :class="{ active: autoSave }"
          class="header-btn"
          aria-label="Toggle auto-save feature"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2"/>
          </svg>
          Auto-save
        </button>

        <button
          @click="showPreview = !showPreview"
          :class="{ active: showPreview }"
          class="header-btn"
          aria-label="Toggle live preview"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2"/>
            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2"/>
          </svg>
          Live Preview
        </button>

        <div class="undo-redo-group">
          <button
            @click="undo"
            :disabled="!canUndo"
            class="header-btn"
            aria-label="Undo last change"
            title="Undo (Ctrl+Z)"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M3 7v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <button
            @click="redo"
            :disabled="!canRedo"
            class="header-btn"
            aria-label="Redo last change"
            title="Redo (Ctrl+Y)"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M21 7v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M3 17a9 9 0 009-9 9 9 0 006 2.3L21 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <button @click="saveTemplate" class="btn-primary" :disabled="isSaving">
          <svg v-if="isSaving" width="16" height="16" viewBox="0 0 24 24" fill="none" class="animate-spin">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
          {{ isSaving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="customizer-content">
      <!-- Sidebar Navigation -->
      <div class="customizer-sidebar">
        <nav class="sidebar-nav" role="tablist" aria-label="Customization sections">
          <button
            v-for="panel in panels"
            :key="panel.id"
            @click="activePanel = panel.id"
            :class="{ active: activePanel === panel.id }"
            class="nav-button"
            :aria-selected="activePanel === panel.id"
            :aria-controls="`panel-${panel.id}`"
            :id="`tab-${panel.id}`"
            role="tab"
          >
            <component :is="panel.icon" class="w-5 h-5" />
            <span>{{ panel.name }}</span>
            <span v-if="getPanelBadge(panel.id)" class="nav-badge">{{ getPanelBadge(panel.id) }}</span>
          </button>
        </nav>
      </div>

      <!-- Main Panel Area -->
      <div class="customizer-main">
        <TransitionGroup name="panel-transition" tag="div" class="panel-container">
          <!-- Brand Panel -->
          <div
            v-if="activePanel === 'brand'"
            key="brand"
            id="panel-brand"
            class="customizer-panel"
            role="tabpanel"
            aria-labelledby="tab-brand"
          >
            <div class="panel-header">
              <h2 class="panel-title">Brand Customization</h2>
              <p class="panel-description">Customize colors, fonts, and logos for your template</p>
            </div>

            <div class="brand-sections">
              <!-- Colors Section -->
              <div class="brand-section">
                <div class="section-header">
                  <h3 class="section-title">Colors</h3>
                  <ColorPicker
                    v-model="brandCustomization.colors.primary"
                    :preset-colors="colorPresets"
                    class="integrated-color-picker"
                  />
                </div>

                <div class="color-palettes">
                  <div
                    v-for="(color, key) in brandCustomization.colors"
                    :key="key"
                    class="color-item"
                  >
                    <div class="color-label">
                      <span class="color-name">{{ formatColorName(key) }}</span>
                      <span class="color-value">{{ color }}</span>
                    </div>
                    <ColorPicker
                      v-model="brandCustomization.colors[key]"
                      :preset-colors="colorPresets"
                      show-current-color
                    />
                  </div>
                </div>
              </div>

              <!-- Fonts Section -->
              <div class="brand-section">
                <div class="section-header">
                  <h3 class="section-title">Typography</h3>
                  <FontSelector
                    v-model="brandCustomization.fonts.primary"
                    :default-font="brandCustomization.fonts.primary"
                    class="integrated-font-selector"
                  />
                </div>

                <div class="font-stack">
                  <div
                    v-for="(font, key) in brandCustomization.fonts"
                    :key="key"
                    class="font-item"
                  >
                    <div class="font-label">
                      <span class="font-name">{{ formatFontName(key) }}</span>
                      <span class="font-family">{{ font.family }}</span>
                    </div>
                    <FontSelector
                      v-model="brandCustomization.fonts[key].family"
                      :default-font="font"
                    />
                  </div>
                </div>
              </div>

              <!-- Styles Section -->
              <div class="brand-section">
                <h3 class="section-title">Visual Style</h3>
                <div class="style-options">
                  <div class="style-group">
                    <label class="style-label">Border Radius</label>
                    <select v-model="brandCustomization.styles.borderRadius" class="style-select">
                      <option value="none">None</option>
                      <option value="sm">Small</option>
                      <option value="md">Medium</option>
                      <option value="lg">Large</option>
                      <option value="xl">Extra Large</option>
                      <option value="full">Full</option>
                    </select>
                  </div>

                  <div class="style-group">
                    <label class="style-label">Shadow</label>
                    <select v-model="brandCustomization.styles.shadow" class="style-select">
                      <option value="none">None</option>
                      <option value="sm">Small</option>
                      <option value="md">Medium</option>
                      <option value="lg">Large</option>
                      <option value="xl">Extra Large</option>
                    </select>
                  </div>

                  <div class="style-group">
                    <label class="style-label">Spacing</label>
                    <select v-model="brandCustomization.styles.spacing" class="style-select">
                      <option value="compact">Compact</option>
                      <option value="default">Default</option>
                      <option value="spacious">Spacious</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Content Panel -->
          <div
            v-if="activePanel === 'content'"
            key="content"
            id="panel-content"
            class="customizer-panel"
            role="tabpanel"
            aria-labelledby="tab-content"
          >
            <div class="panel-header">
              <h2 class="panel-title">Content Customization</h2>
              <p class="panel-description">Edit text, images, buttons, and other content blocks</p>
            </div>

            <ContentEditor
              :initial-blocks="contentCustomization.blocks"
              @content-changed="handleContentChange"
              @file-uploaded="handleFileUpload"
              @block-deleted="handleBlockDelete"
              class="integrated-content-editor"
            />
          </div>

          <!-- Settings Panel -->
          <div
            v-if="activePanel === 'settings'"
            key="settings"
            id="panel-settings"
            class="customizer-panel"
            role="tabpanel"
            aria-labelledby="tab-settings"
          >
            <div class="panel-header">
              <h2 class="panel-title">Settings</h2>
              <p class="panel-description">Configure template settings and behavior</p>
            </div>

            <div class="settings-sections">
              <!-- General Settings -->
              <div class="settings-section">
                <h3 class="settings-section-title">General</h3>
                <div class="settings-group">
                  <div class="setting-item">
                    <label class="setting-label">Template Name</label>
                    <input
                      v-model="templateConfig.name"
                      type="text"
                      class="setting-input"
                      placeholder="Enter template name"
                    />
                  </div>

                  <div class="setting-item">
                    <label class="setting-label">Custom CSS</label>
                    <textarea
                      v-model="templateConfig.customCss"
                      class="setting-textarea"
                      placeholder="Enter custom CSS"
                      rows="6"
                    ></textarea>
                  </div>

                  <div class="setting-item">
                    <label class="setting-label">Custom JavaScript</label>
                    <textarea
                      v-model="templateConfig.customJs"
                      class="setting-textarea"
                      placeholder="Enter custom JavaScript"
                      rows="6"
                    ></textarea>
                  </div>
                </div>
              </div>

              <!-- Performance Settings -->
              <div class="settings-section">
                <h3 class="settings-section-title">Performance</h3>
                <div class="settings-group">
                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.lazyLoad" type="checkbox" />
                      Enable lazy loading for images
                    </label>
                  </div>

                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.optimizeImages" type="checkbox" />
                      Optimize images automatically
                    </label>
                  </div>

                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.minifyCss" type="checkbox" />
                      Minify CSS output
                    </label>
                  </div>
                </div>
              </div>

              <!-- Accessibility Settings -->
              <div class="settings-section">
                <h3 class="settings-section-title">Accessibility</h3>
                <div class="settings-group">
                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.highContrast" type="checkbox" />
                      Enable high contrast mode
                    </label>
                  </div>

                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.skipLinks" type="checkbox" />
                      Add skip links for keyboard navigation
                    </label>
                  </div>

                  <div class="setting-item">
                    <label class="checkbox-label">
                      <input v-model="templateConfig.ariaLabels" type="checkbox" />
                      Auto-generate ARIA labels
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Export Panel -->
          <div
            v-if="activePanel === 'export'"
            key="export"
            id="panel-export"
            class="customizer-panel"
            role="tabpanel"
            aria-labelledby="tab-export"
          >
            <div class="panel-header">
              <h2 class="panel-title">Export & Share</h2>
              <p class="panel-description">Export your customized template or share it with others</p>
            </div>

            <div class="export-options">
              <!-- Preview Options -->
              <div class="export-section">
                <h3 class="export-section-title">Preview</h3>
                <div class="preview-devices">
                  <button
                    v-for="device in previewDevices"
                    :key="device.id"
                    @click="setPreviewDevice(device.id)"
                    :class="{ active: currentPreviewDevice === device.id }"
                    class="device-btn"
                    :aria-label="`Preview on ${device.name}`"
                  >
                    <component :is="device.icon" class="w-5 h-5" />
                    {{ device.name }}
                  </button>
                </div>
              </div>

              <!-- Export Formats -->
              <div class="export-section">
                <h3 class="export-section-title">Export Format</h3>
                <div class="export-format">
                  <button
                    v-for="format in exportFormats"
                    :key="format.id"
                    @click="exportTemplate(format.id)"
                    class="format-btn"
                    :aria-label="`Export as ${format.name}`"
                  >
                    <component :is="format.icon" class="w-5 h-5" />
                    {{ format.name }}
                  </button>
                </div>
              </div>

              <!-- Share Options -->
              <div class="export-section">
                <h3 class="export-section-title">Share</h3>
                <div class="share-options">
                  <button @click="generateShareableLink" class="share-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Generate Shareable Link
                  </button>

                  <button @click="exportAsJson" class="share-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Export Configuration
                  </button>
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Live Preview Panel -->
      <div v-if="showPreview" class="customizer-preview">
        <div class="preview-header">
          <h3 class="preview-title">Live Preview</h3>
          <button @click="toggleFullscreenPreview" class="fullscreen-btn" aria-label="Toggle fullscreen preview">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" stroke="currentColor" stroke-width="2"/>
            </svg>
          </button>
        </div>

        <div class="preview-frame">
          <iframe
            ref="previewFrame"
            :src="previewUrl"
            class="preview-iframe"
            title="Template Live Preview"
            @load="onPreviewLoad"
          ></iframe>
        </div>

        <div class="preview-controls">
          <button
            v-for="device in previewDevices"
            :key="device.id"
            @click="setPreviewViewport(device.id)"
            :class="{ active: currentPreviewDevice === device.id }"
            class="viewport-btn"
            :aria-label="`Preview viewport for ${device.name}`"
          >
            <component :is="device.icon" class="w-4 h-4" />
            {{ device.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Save Status Indicator -->
    <div v-if="saveStatus" class="save-status" :class="saveStatus.type">
      <svg v-if="saveStatus.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none">
        <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <svg v-else-if="saveStatus.type === 'error'" width="16" height="16" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
        <path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2"/>
      </svg>
      {{ saveStatus.message }}
    </div>

    <!-- Shortcut hints -->
    <div class="shortcut-hints" v-if="showShortcuts">
      <div class="hints-overlay" @click="toggleShortcuts"></div>
      <div class="hints-content">
        <h3>Keyboard Shortcuts</h3>
        <ul class="hints-list">
          <li><kbd>Ctrl+Z</kbd> Undo</li>
          <li><kbd>Ctrl+Y</kbd> Redo</li>
          <li><kbd>Ctrl+S</kbd> Save</li>
          <li><kbd>Tab</kbd> Navigate panels</li>
          <li><kbd>Escape</kbd> Close modals</li>
        </ul>
      </div>
    </div>

    <!-- Accessibility announcements -->
    <div class="sr-only" aria-live="polite" aria-atomic="true">
      {{ accessibilityMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { router } from '@inertiajs/vue3'

// Component imports
import ColorPicker from './ColorPicker.vue'
import FontSelector from './FontSelector.vue'

// Types
import type {
  BrandCustomization,
  ContentCustomization,
  TemplateCustomizationConfig
} from '@/types/components'

// Navigation icons (placeholder imports - replace with actual icons)
const PaletteIcon = () => import('@heroicons/vue/outline/PaletteIcon.vue').then(m => m.default)
const DocumentTextIcon = () => import('@heroicons/vue/outline/DocumentTextIcon.vue').then(m => m.default)
const CogIcon = () => import('@heroicons/vue/outline/CogIcon.vue').then(m => m.default)
const DownloadIcon = () => import('@heroicons/vue/outline/DownloadIcon.vue').then(m => m.default)
const ComputerDesktopIcon = () => import('@heroicons/vue/outline/ComputerDesktopIcon.vue').then(m => m.default)
const DevicePhoneMobileIcon = () => import('@heroicons/vue/outline/DevicePhoneMobileIcon.vue').then(m => m.default)
const DeviceTabletIcon = () => import('@heroicons/vue/outline/DeviceTabletIcon.vue').then(m => m.default)

// Props
interface Props {
  templateId?: number
  initialConfig?: Partial<TemplateCustomizationConfig>
  previewUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
  previewUrl: '#'
})

// Emits
interface Emits {
  customizationChanged: [config: TemplateCustomizationConfig]
  saved: [config: TemplateCustomizationConfig]
  previewUpdated: [viewport: string]
}

const emit = defineEmits<Emits>()

// Reactive state
const activePanel = ref<string>('brand')
const showPreview = ref(false)
const autoSave = ref(true)
const isSaving = ref(false)
const currentPreviewDevice = ref<string>('desktop')
const showShortcuts = ref(false)
const accessibilityMessage = ref('')

// Components state
const brandCustomization = ref<BrandCustomization>({
  name: '',
  tagline: '',
  colors: {
    primary: '#3B82F6',
    secondary: '#6B7280',
    accent: '#F59E0B',
    text: '#111827',
    background: '#FFFFFF'
  },
  fonts: {
    primary: {
      type: 'heading',
      family: 'Georgia',
      weight: 400,
      size: 32,
      lineHeight: 1.2,
      letterSpacing: 0,
      color: '#111827'
    },
    secondary: {
      type: 'body',
      family: 'Helvetica',
      weight: 400,
      size: 16,
      lineHeight: 1.6,
      letterSpacing: 0,
      color: '#374151'
    }
  },
  styles: {
    borderRadius: 'md',
    spacing: 'default',
    shadow: 'md'
  }
})

const contentCustomization = ref<ContentCustomization>({
  blocks: [],
  layout: {
    maxWidth: '2xl',
    alignment: 'center',
    verticalSpacing: 'lg'
  }
})

const templateConfig = ref({
  name: 'Custom Template',
  customCss: '',
  customJs: '',
  lazyLoad: true,
  optimizeImages: true,
  minifyCss: false,
  highContrast: false,
  skipLinks: true,
  ariaLabels: true
})

const saveStatus = ref<{ type: 'success' | 'error'; message: string } | null>(null)

// Undo/Redo state
const undoStack = ref<Array<{ action: string; data: any; timestamp: Date }>>([])
const redoStack = ref<Array<{ action: string; data: any; timestamp: Date }>>([])
let currentSnapshot: TemplateCustomizationConfig | null = null

// Panel definitions
const panels = [
  { id: 'brand', name: 'Brand', icon: PaletteIcon },
  { id: 'content', name: 'Content', icon: DocumentTextIcon },
  { id: 'settings', name: 'Settings', icon: CogIcon },
  { id: 'export', name: 'Export', icon: DownloadIcon }
]

// Preview devices
const previewDevices = [
  { id: 'desktop', name: 'Desktop', label: '1200px', icon: ComputerDesktopIcon, width: 1200 },
  { id: 'tablet', name: 'Tablet', label: '768px', icon: DeviceTabletIcon, width: 768 },
  { id: 'mobile', name: 'Mobile', label: '375px', icon: DevicePhoneMobileIcon, width: 375 }
]

// Export formats
const exportFormats = [
  { id: 'html', name: 'HTML', icon: DocumentTextIcon },
  { id: 'pdf', name: 'PDF', icon: DownloadIcon },
  { id: 'json', name: 'JSON', icon: CogIcon }
]

// Color presets
const colorPresets = [
  '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
  '#F97316', '#06B6D4', '#84CC16', '#EC4899', '#6366F1'
]

// Template refs
const previewFrame = ref<HTMLIFrameElement>()

// Computed properties
const canUndo = computed(() => undoStack.value.length > 0)
const canRedo = computed(() => redoStack.value.length > 0)

// Methods
const createSnapshot = (action: string) => {
  const snapshot = {
    action,
    data: {
      brand: { ...brandCustomization.value },
      content: { ...contentCustomization.value },
      settings: { ...templateConfig.value }
    },
    timestamp: new Date()
  }

  // Limit undo stack to 50 items
  if (undoStack.value.length >= 50) {
    undoStack.value.shift()
  }

  undoStack.value.push(snapshot)
  redoStack.value.length = 0 // Clear redo stack when new action is performed
  currentSnapshot = snapshot.data

  emit('customizationChanged', getFullConfig())
}

const undo = () => {
  if (!canUndo.value) return

  const currentState = {
    brand: { ...brandCustomization.value },
    content: { ...contentCustomization.value },
    settings: { ...templateConfig.value }
  }

  const snapshot = undoStack.value.pop()!
  redoStack.value.push({ ...snapshot, data: currentState })

  // Restore from snapshot
  brandCustomization.value = snapshot.data.brand
  contentCustomization.value = snapshot.data.content
  templateConfig.value = snapshot.data.settings

  accessibilityMessage.value = `Undid ${snapshot.action}`
  emit('customizationChanged', getFullConfig())
}

const redo = () => {
  if (!canRedo.value) return

  const snapshot = redoStack.value.pop()!
  undoStack.value.push(snapshot)

  // Apply from redo snapshot
  brandCustomization.value = snapshot.data.brand
  contentCustomization.value = snapshot.data.content
  templateConfig.value = snapshot.data.settings

  accessibilityMessage.value = `Redid ${snapshot.action}`
  emit('customizationChanged', getFullConfig())
}

const getFullConfig = (): TemplateCustomizationConfig => {
  return {
    templateId: props.templateId!,
    brand: brandCustomization.value,
    content: contentCustomization.value,
    settings: templateConfig.value as any,
    metadata: {
      createdBy: 1, // Should be from auth context
      createdAt: new Date().toISOString(),
      version: 1,
      changeLog: undoStack.value.map(item => ({
        action: item.action,
        timestamp: item.timestamp.toISOString(),
        userId: 1, // Should be from auth context
        details: item.data
      }))
    }
  }
}

const saveTemplate = async () => {
  isSaving.value = true
  saveStatus.value = null

  try {
    const config = getFullConfig()

    // In a real implementation, this would send to server
    await new Promise(resolve => setTimeout(resolve, 1000)) // Mock delay

    createSnapshot('Save template')
    emit('saved', config)

    saveStatus.value = {
      type: 'success',
      message: 'Template saved successfully!'
    }

    setTimeout(() => {
      saveStatus.value = null
    }, 3000)

  } catch (error) {
    saveStatus.value = {
      type: 'error',
      message: 'Failed to save template'
    }

    setTimeout(() => {
      saveStatus.value = null
    }, 5000)
  } finally {
    isSaving.value = false
  }
}

const getPanelBadge = (panelId: string): string | null => {
  switch (panelId) {
    case 'brand':
      return Object.keys(brandCustomization.value.colors).length > 0 ? Object.keys(brandCustomization.value.colors).length.toString() : null
    case 'content':
      return contentCustomization.value.blocks.length > 0 ? contentCustomization.value.blocks.length.toString() : null
    default:
      return null
  }
}

const formatColorName = (key: string): string => {
  return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')
}

const formatFontName = (key: string): string => {
  return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')
}

const handleContentChange = (data: any) => {
  // Find and update the block
  const blockIndex = contentCustomization.value.blocks.findIndex(b => b.id === data.blockId)
  if (blockIndex !== -1) {
    contentCustomization.value.blocks[blockIndex] = {
      ...contentCustomization.value.blocks[blockIndex],
      data: { ...contentCustomization.value.blocks[blockIndex].data, ...data }
    }
    createSnapshot(`Updated ${data.type || 'content'}`)
  }
}

const handleFileUpload = (data: any) => {
  // Handle file upload logic
  createSnapshot('Uploaded file')
}

const handleBlockDelete = (blockId: string) => {
  contentCustomization.value.blocks = contentCustomization.value.blocks.filter(b => b.id !== blockId)
  createSnapshot('Deleted block')
}

const setPreviewDevice = (deviceId: string) => {
  currentPreviewDevice.value = deviceId
  updatePreview()
}

const setPreviewViewport = (deviceId: string) => {
  currentPreviewDevice.value = deviceId
  updatePreview()
}

const updatePreview = () => {
  const device = previewDevices.find(d => d.id === currentPreviewDevice.value)
  if (device && previewFrame.value) {
    previewFrame.value.style.width = `${device.width}px`
  }
  emit('previewUpdated', currentPreviewDevice.value)
}

const onPreviewLoad = () => {
  // Update iframe content with current customization
  updatePreview()
}

const toggleFullscreenPreview = () => {
  if (previewFrame.value) {
    if (previewFrame.value.requestFullscreen) {
      previewFrame.value.requestFullscreen()
    }
  }
}

const exportTemplate = (format: string) => {
  const config = getFullConfig()

  switch (format) {
    case 'html':
      exportAsHTML(config)
      break
    case 'pdf':
      exportAsPDF(config)
      break
    case 'json':
      exportAsJson()
      break
  }
}

const exportAsHTML = (config: TemplateCustomizationConfig) => {
  // Implement HTML export
  const html = generateHTML(config)
  downloadFile(html, 'template.html', 'text/html')
}

const exportAsPDF = (config: TemplateCustomizationConfig) => {
  // Implement PDF export
  accessibilityMessage.value = 'PDF export not yet implemented'
}

const exportAsJson = () => {
  const config = getFullConfig()
  const json = JSON.stringify(config, null, 2)
  downloadFile(json, 'template-config.json', 'application/json')
}

const generateShareableLink = () => {
  // Implement shareable link generation
  const config = getFullConfig()
  const encodedConfig = btoa(JSON.stringify(config))
  const url = `${window.location.origin}/template-previewer?config=${encodeURIComponent(encodedConfig)}`

  navigator.clipboard.writeText(url).then(() => {
    saveStatus.value = {
      type: 'success',
      message: 'Shareable link copied to clipboard!'
    }
  })
}

const generateHTML = (config: TemplateCustomizationConfig): string => {
  // Basic HTML generation - in real implementation this would be much more complex
  return `
    <!DOCTYPE html>
    <html>
    <head>
      <title>${config.brand.name}</title>
      <style>
        body { font-family: ${config.brand.fonts.secondary.family}; color: ${config.brand.colors.text}; background: ${config.brand.colors.background}; }
        h1, h2, h3 { font-family: ${config.brand.fonts.primary.family}; color: ${config.brand.colors.primary}; }
        .primary-btn { background-color: ${config.brand.colors.primary}; }
      </style>
      ${config.settings.customCss ? `<style>${config.settings.customCss}</style>` : ''}
    </head>
    <body>
      <h1>${config.brand.name}</h1>
      <p>Welcome to your customized template!</p>
      ${config.settings.customJs ? `<script>${config.settings.customJs}</script>` : ''}
    </body>
    </html>
  `.trim()
}

const downloadFile = (content: string, filename: string, mimeType: string) => {
  const blob = new Blob([content], { type: mimeType })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const toggleShortcuts = () => {
  showShortcuts.value = !showShortcuts.value
}

// Keyboard shortcuts
const handleKeyboardShortcut = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key.toLowerCase()) {
      case 'z':
        if (!event.shiftKey) {
          event.preventDefault()
          undo()
        }
        break
      case 'y':
        event.preventDefault()
        redo()
        break
      case 's':
        event.preventDefault()
        saveTemplate()
        break
    }
  }

  if (event.key === '?') {
    event.preventDefault()
    toggleShortcuts()
  }
}

// Initialize with props data
const initializeCustomization = () => {
  if (props.initialConfig) {
    if (props.initialConfig.brand) {
      brandCustomization.value = { ...brandCustomization.value, ...props.initialConfig.brand }
    }
    if (props.initialConfig.content) {
      contentCustomization.value = { ...contentCustomization.value, ...props.initialConfig.content }
    }
    if (props.initialConfig.settings) {
      templateConfig.value = { ...templateConfig.value, ...props.initialConfig.settings }
    }
  }
}

// Watch for changes and auto-save
watch(
  [brandCustomization, contentCustomization, templateConfig],
  () => {
    if (autoSave.value && currentSnapshot) {
      const hasChanges = JSON.stringify({
        brand: brandCustomization.value,
        content: contentCustomization.value,
        settings: templateConfig.value
      }) !== JSON.stringify(currentSnapshot)

      if (hasChanges) {
        createSnapshot('Auto-save')
        if (autoSave.value) {
          saveTemplate()
        }
      }
    }
  },
  { deep: true, debounce: 1000 }
)

// Lifecycle
onMounted(() => {
  initializeCustomization()
  document.addEventListener('keydown', handleKeyboardShortcut)

  // Create initial snapshot
  nextTick(() => {
    createSnapshot('Initial state')
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyboardShortcut)
})
</script>

<style scoped>
.template-customizer {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}

/* Header */
.customizer-header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4;
}

.header-content {
  @apply mb-4;
}

.customizer-title {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.customizer-subtitle {
  @apply text-gray-600 dark:text-gray-400 mt-1;
}

.header-actions {
  @apply flex items-center justify-between gap-3;
}

.header-btn {
  @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors;
}

.header-btn.active {
  @apply bg-blue-600 border-blue-600 text-white;
}

.header-btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.undo-redo-group {
  @apply flex gap-1;
}

/* Content Layout */
.customizer-content {
  @apply flex h-screen bg-white dark:bg-gray-800;
}

.customizer-sidebar {
  @apply w-64 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800;
}

.sidebar-nav {
  @apply p-4 space-y-2;
}

.nav-button {
  @apply w-full flex items-center justify-between p-3 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.nav-button.active {
  @apply bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200;
}

.nav-badge {
  @apply bg-red-500 text-white text-xs px-2 py-1 rounded-full;
}

.customizer-main {
  @apply flex-1 overflow-hidden;
}

.panel-container {
  @apply h-full overflow-y-auto;
}

.customizer-panel {
  @apply p-6 h-full;
}

.panel-header {
  @apply mb-6;
}

.panel-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.panel-description {
  @apply text-gray-600 dark:text-gray-400 mt-2;
}

/* Panel Transitions */
.panel-transition-enter-active,
.panel-transition-leave-active {
  @apply transition-all duration-300 ease-out;
}

.panel-transition-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.panel-transition-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

/* Brand Sections */
.brand-sections {
  @apply space-y-8;
}

.brand-section {
  @apply bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.section-header {
  @apply flex items-center justify-between mb-6;
}

.section-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.integrated-color-picker,
.integrated-font-selector {
  @apply mb-4;
}

.color-palettes {
  @apply grid grid-cols-1 gap-4;
}

.color-item {
  @apply flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-600 rounded-lg;
}

.color-label {
  @apply flex flex-col;
}

.color-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.color-value {
  @apply text-sm text-gray-500 dark:text-gray-400 font-mono;
}

.font-stack {
  @apply space-y-4;
}

.font-item {
  @apply flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-600 rounded-lg;
}

.font-label {
  @apply flex flex-col;
}

.font-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.font-family {
  @apply text-sm text-gray-500 dark:text-gray-400 font-mono;
}

.style-options {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.style-group {
  @apply space-y-2;
}

.style-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.style-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500;
}

/* Content Editor */
.integrated-content-editor {
  @apply mt-6;
}

/* Settings Sections */
.settings-sections {
  @apply space-y-8;
}

.settings-section {
  @apply bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.settings-section-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white mb-4;
}

.settings-group {
  @apply space-y-4;
}

.setting-item {
  @apply space-y-2;
}

.setting-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.checkbox-label {
  @apply flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300;
}

.setting-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500;
}

.setting-textarea {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono text-sm;
}

/* Export Sections */
.export-options {
  @apply space-y-8;
}

.export-section {
  @apply bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.export-section-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white mb-4;
}

.preview-devices {
  @apply flex gap-2;
}

.device-btn {
  @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.device-btn.active {
  @apply bg-blue-600 border-blue-600 text-white;
}

.export-format {
  @apply grid grid-cols-1 sm:grid-cols-3 gap-3;
}

.format-btn {
  @apply flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.share-options {
  @apply space-y-3;
}

.share-btn {
  @apply flex items-center gap-2 w-full px-4 py-3 text-sm font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

/* Preview Panel */
.customizer-preview {
  @apply w-96 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col;
}

.preview-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.preview-title {
  @apply font-semibold text-gray-900 dark:text-white;
}

.fullscreen-btn {
  @apply p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

.preview-frame {
  @apply flex-1 p-4;
}

.preview-iframe {
  @apply w-full h-full border rounded bg-white;
}

.preview-controls {
  @apply flex gap-2 p-4 border-t border-gray-200 dark:border-gray-700;
}

.viewport-btn {
  @apply flex items-center gap-1 px-3 py-2 text-xs font-medium rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.viewport-btn.active {
  @apply bg-blue-600 border-blue-600 text-white;
}

/* Save Status */
.save-status {
  @apply fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 z-50;
}

.save-status.success {
  @apply bg-green-600 text-white;
}

.save-status.error {
  @apply bg-red-600 text-white;
}

/* Shortcuts */
.shortcut-hints {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.hints-overlay {
  @apply absolute inset-0;
}

.hints-content {
  @apply relative bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm w-full;
}

.hints-list {
  @apply space-y-2 mt-4;
}

.hints-list li {
  @apply flex items-center justify-between text-sm;
}

.hints-list kbd {
  @apply bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs font-mono;
}

/* Accessibility */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: