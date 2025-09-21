# GrapeJS Vue Wrapper Component Design

## Overview

This document outlines the design for the core Vue wrapper component that will integrate GrapeJS Page Builder into the Vue.js application. This component will provide a seamless integration between Vue and GrapeJS while maintaining the reactive nature of Vue applications.

## Component Structure

### Main Component Interface

```vue
<template>
  <div class="grapejs-page-builder">
    <!-- Loading state -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner">Loading editor...</div>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="error-overlay">
      <div class="error-message">
        <h3>Error loading page builder</h3>
        <p>{{ error.message }}</p>
        <button @click="retryInitialization">Retry</button>
      </div>
    
    <!-- Main editor interface -->
    <div v-else class="editor-container">
      <!-- Toolbar -->
      <div class="editor-toolbar">
        <div class="toolbar-section left">
          <button 
            v-for="device in devices" 
            :key="device.id"
            :class="{ active: currentDevice === device.id }"
            @click="switchDevice(device.id)"
            class="device-button"
          >
            {{ device.name }}
          </button>
        </div>
        
        <div class="toolbar-section center">
          <h2>{{ currentPage?.title || 'Untitled Page' }}</h2>
        </div>
        
        <div class="toolbar-section right">
          <button @click="savePage" :disabled="isSaving" class="save-button">
            {{ isSaving ? 'Saving...' : 'Save' }}
          </button>
          <button @click="previewPage" class="preview-button">Preview</button>
          <button @click="publishPage" class="publish-button">Publish</button>
        </div>
      </div>
      
      <!-- Main editor area -->
      <div class="editor-main">
        <!-- Component palette -->
        <div class="component-palette">
          <div class="palette-header">
            <h3>Components</h3>
            <input 
              v-model="searchQuery" 
              placeholder="Search components..." 
              class="search-input"
            />
          </div>
          
          <div class="palette-content">
            <div 
              v-for="category in componentCategories" 
              :key="category.id"
              class="category-section"
            >
              <h4 @click="toggleCategory(category.id)" class="category-header">
                {{ category.name }}
                <span class="toggle-icon">
                  {{ expandedCategories.includes(category.id) ? '−' : '+' }}
                </span>
              </h4>
              
              <div 
                v-show="expandedCategories.includes(category.id)" 
                class="category-components"
              >
                <div
                  v-for="component in category.components"
                  :key="component.id"
                  draggable="true"
                  @dragstart="handleDragStart($event, component)"
                  class="component-item"
                  :title="component.description"
                >
                  <img :src="component.previewImage" :alt="component.name" />
                  <span>{{ component.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Canvas area -->
        <div class="canvas-container">
          <div 
            ref="grapejsContainer" 
            class="grapejs-editor"
            :class="`device-${currentDevice}`"
          ></div>
        </div>
        
        <!-- Properties panel -->
        <div class="properties-panel">
          <div v-if="selectedComponent" class="component-properties">
            <h3>{{ selectedComponent.name }} Properties</h3>
            
            <div class="property-group" v-for="trait in selectedComponent.traits" :key="trait.name">
              <label :for="trait.name">{{ trait.label || trait.name }}</label>
              
              <input
                v-if="trait.type === 'text'"
                :id="trait.name"
                :value="trait.value"
                @input="updateTrait(trait.name, $event.target.value)"
                class="property-input"
              />
              
              <select
                v-else-if="trait.type === 'select'"
                :id="trait.name"
                :value="trait.value"
                @change="updateTrait(trait.name, $event.target.value)"
                class="property-input"
              >
                <option 
                  v-for="option in trait.options" 
                  :key="option.id" 
                  :value="option.id"
                >
                  {{ option.name }}
                </option>
              </select>
              
              <input
                v-else-if="trait.type === 'checkbox'"
                type="checkbox"
                :id="trait.name"
                :checked="trait.value"
                @change="updateTrait(trait.name, $event.target.checked)"
                class="property-input"
              />
            </div>
            
            <button @click="applyChanges" class="apply-button">Apply Changes</button>
          </div>
          
          <div v-else class="no-selection">
            <p>Select a component to edit its properties</p>
          </div>
        </div>
      
      <!-- Status bar -->
      <div class="status-bar">
        <div class="status-item">
          <span>Device: {{ currentDevice }}</span>
        </div>
        <div class="status-item">
          <span>Last saved: {{ lastSaved }}</span>
        </div>
        <div class="status-item">
          <span>Version: {{ currentPage?.version }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import type { Ref } from 'vue'
import grapesjs from 'grapesjs'
import type { Editor } from 'grapesjs'
import { componentLibraryBridge } from '@/services/ComponentLibraryBridge'
import { templateSystemBridge } from '@/services/TemplateSystemBridge'
import { usePageStore } from '@/stores/pageStore'
import type { Component, Page } from '@/types/components'

// Reactive state
const isLoading = ref(true)
const isSaving = ref(false)
const error: Ref<Error | null> = ref(null)
const grapejsContainer: Ref<HTMLElement | null> = ref(null)
const editor: Ref<Editor | null> = ref(null)
const currentPage: Ref<Page | null> = ref(null)
const searchQuery = ref('')
const currentDevice = ref('desktop')
const expandedCategories: Ref<string[]> = ref([])
const selectedComponent: Ref<Component | null> = ref(null)
const lastSaved = ref('Never')

// Devices configuration
const devices = [
  { id: 'desktop', name: 'Desktop', width: '' },
  { id: 'tablet', name: 'Tablet', width: '768px' },
  { id: 'mobile', name: 'Mobile', width: '375px' }
]

// Component categories from bridge
const componentCategories = computed(() => {
  return componentLibraryBridge.getGrapeJSCategories()
})

// Page store
const pageStore = usePageStore()

// Lifecycle
onMounted(async () => {
  try {
    await initializeEditor()
    await loadPage()
  } catch (err) {
    error.value = err instanceof Error ? err : new Error('Unknown error')
    console.error('Failed to initialize editor:', err)
  } finally {
    isLoading.value = false
  }
})

onUnmounted(() => {
  // Clean up GrapeJS editor
  if (editor.value) {
    editor.value.destroy()
  }
})

// Methods
const initializeEditor = async (): Promise<void> => {
  if (!grapejsContainer.value) {
    throw new Error('GrapeJS container not found')
  }
  
  // Initialize GrapeJS editor
  editor.value = grapesjs.init({
    container: grapejsContainer.value,
    height: '100%',
    width: 'auto',
    storageManager: {
      type: 'remote',
      stepsBeforeSave: 3,
      options: {
        remote: {
          urlLoad: '/api/pages/{id}/grapejs-data',
          urlStore: '/api/pages/{id}/grapejs-data',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        }
      }
    },
    blockManager: {
      appendTo: '.component-palette'
    },
    styleManager: {
      appendTo: '.properties-panel'
    },
    deviceManager: {
      devices: devices.map(device => ({
        name: device.name,
        width: device.width
      }))
    },
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
      'grapesjs-style-bg'
    ],
    pluginsOpts: {
      'gjs-blocks-basic': { flexGrid: true }
    }
  })
  
  // Set up event listeners
  setupEditorEvents()
  
  // Register components from Component Library
  await registerComponents()
  
  // Set up real-time collaboration if enabled
  setupCollaboration()
}

const setupEditorEvents = (): void => {
  if (!editor.value) return
  
  // Listen for component selection
  editor.value.on('component:selected', (component: any) => {
    const componentId = component.get('attributes')?.['data-component-id']
    if (componentId) {
      selectedComponent.value = componentLibraryBridge.getComponentById(componentId)
    } else {
      selectedComponent.value = null
    }
  })
  
  // Listen for component updates
  editor.value.on('component:update', () => {
    // Mark page as dirty
    pageStore.markDirty()
  })
  
  // Listen for storage events
  editor.value.on('storage:start:store', () => {
    isSaving.value = true
  })
  
  editor.value.on('storage:end:store', () => {
    isSaving.value = false
    lastSaved.value = new Date().toLocaleTimeString()
    pageStore.markClean()
  })
  
 editor.value.on('storage:error:store', (err: Error) => {
    isSaving.value = false
    error.value = err
  })
}

const registerComponents = async (): Promise<void> => {
  if (!editor.value) return
  
  try {
    // Load all components from Component Library
    const components = await componentLibraryBridge.loadAllComponents()
    
    // Register each component as a GrapeJS block
    components.forEach(component => {
      const block = componentLibraryBridge.convertToGrapeJSBlock(component)
      editor.value?.BlockManager.add(block.id, block)
    })
  } catch (err) {
    console.error('Failed to register components:', err)
    throw err
  }
}

const setupCollaboration = (): void => {
  // TODO: Implement real-time collaboration
  // This would integrate with Laravel Echo for WebSocket support
}

const loadPage = async (): Promise<void> => {
  try {
    const pageId = getPageIdFromRoute()
    if (pageId) {
      currentPage.value = await pageStore.fetchPage(pageId)
      
      // If page has template, load it
      if (currentPage.value.templateId) {
        await templateSystemBridge.loadTemplate(currentPage.value.templateId)
      }
      
      // Load page content into editor
      if (editor.value && currentPage.value.grapejsData) {
        editor.value.setComponents(currentPage.value.grapejsData.components)
        editor.value.setStyle(currentPage.value.grapejsData.styles)
      }
    }
  } catch (err) {
    console.error('Failed to load page:', err)
    throw err
  }
}

const savePage = async (): Promise<void> => {
  if (!editor.value || !currentPage.value) return
  
  try {
    isSaving.value = true
    
    // Get current editor data
    const grapejsData = {
      html: editor.value.getHtml(),
      css: editor.value.getCss(),
      components: editor.value.getComponents().toJSON(),
      styles: editor.value.getStyle().toJSON()
    }
    
    // Update page with current data
    currentPage.value.grapejsData = grapejsData
    currentPage.value.updatedAt = new Date().toISOString()
    
    // Save to store
    await pageStore.updatePage(currentPage.value)
    
    lastSaved.value = new Date().toLocaleTimeString()
  } catch (err) {
    error.value = err instanceof Error ? err : new Error('Failed to save page')
    console.error('Failed to save page:', err)
  } finally {
    isSaving.value = false
  }
}

const previewPage = (): void => {
 // Open preview in new tab
  if (currentPage.value?.id) {
    window.open(`/preview/page/${currentPage.value.id}`, '_blank')
  }
}

const publishPage = async (): Promise<void> => {
  if (!currentPage.value) return
  
  try {
    currentPage.value.status = 'published'
    currentPage.value.publishedAt = new Date().toISOString()
    
    await pageStore.updatePage(currentPage.value)
    
    // Show success message
    alert('Page published successfully!')
  } catch (err) {
    error.value = err instanceof Error ? err : new Error('Failed to publish page')
    console.error('Failed to publish page:', err)
  }
}

const switchDevice = (deviceId: string): void => {
  currentDevice.value = deviceId
  if (editor.value) {
    editor.value.setDevice(deviceId)
  }
}

const toggleCategory = (categoryId: string): void => {
  const index = expandedCategories.value.indexOf(categoryId)
  if (index >= 0) {
    expandedCategories.value.splice(index, 1)
  } else {
    expandedCategories.value.push(categoryId)
  }
}

const handleDragStart = (event: DragEvent, component: Component): void => {
  // Set drag data
  event.dataTransfer?.setData('text/plain', JSON.stringify(component))
}

const updateTrait = (traitName: string, value: any): void => {
  if (selectedComponent.value) {
    // Update trait value in component
    const trait = selectedComponent.value.traits?.find(t => t.name === traitName)
    if (trait) {
      trait.value = value
    }
  }
}

const applyChanges = (): void => {
  if (!editor.value || !selectedComponent.value) return
  
  // Apply changes to selected component in editor
  const selected = editor.value.getSelected()
  if (selected) {
    // Update component properties
    selectedComponent.value.traits?.forEach(trait => {
      selected.addTrait({
        name: trait.name,
        value: trait.value
      })
    })
    
    // Trigger update
    selected.trigger('change:traits')
  }
}

const retryInitialization = async (): Promise<void> => {
  error.value = null
  isLoading.value = true
  
  try {
    await initializeEditor()
    await loadPage()
  } catch (err) {
    error.value = err instanceof Error ? err : new Error('Unknown error')
  } finally {
    isLoading.value = false
  }
}

// Helper functions
const getPageIdFromRoute = (): string | null => {
  // Extract page ID from current route
  // This would depend on your routing implementation
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get('pageId') || null
}
</script>

<style scoped>
.grapejs-page-builder {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f5f5;
}

.loading-overlay,
.error-overlay {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.9);
}

.spinner {
  font-size: 1.5rem;
  color: #333;
}

.error-message {
  text-align: center;
  padding: 2rem;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.error-message h3 {
  color: #e74c3c;
  margin-bottom: 1rem;
}

.editor-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  background-color: #fff;
  border-bottom: 1px solid #ddd;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toolbar-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.toolbar-section.center {
  flex: 1;
  justify-content: center;
}

.toolbar-section.right {
  justify-content: flex-end;
}

.device-button {
  padding: 0.5rem 1rem;
  background-color: #f0f0;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.device-button.active {
  background-color: #007bff;
  color: white;
}

.save-button,
.preview-button,
.publish-button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.save-button {
  background-color: #28a745;
  color: white;
}

.save-button:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.preview-button {
  background-color: #17a2b8;
  color: white;
  margin-left: 0.5rem;
}

.publish-button {
  background-color: #ffc107;
  color: #212529;
  margin-left: 0.5rem;
}

.editor-main {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.component-palette {
  width: 250px;
  background-color: #fff;
  border-right: 1px solid #ddd;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.palette-header {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.search-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top: 0.5rem;
}

.palette-content {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.category-section {
  margin-bottom: 1rem;
}

.category-header {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  padding: 0.5rem;
  background-color: #f8f9fa;
 border-radius: 4px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.category-components {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.component-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem;
  border: 1px solid #eee;
  border-radius: 4px;
  cursor: grab;
  transition: all 0.2s;
}

.component-item:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.component-item img {
  width: 100%;
  height: 60px;
  object-fit: cover;
 border-radius: 2px;
  margin-bottom: 0.5rem;
}

.component-item span {
  font-size: 0.8rem;
  text-align: center;
}

.canvas-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.grapejs-editor {
  flex: 1;
  overflow: hidden;
}

.properties-panel {
  width: 300px;
  background-color: #fff;
  border-left: 1px solid #ddd;
  padding: 1rem;
  overflow-y: auto;
}

.component-properties h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
}

.property-group {
  margin-bottom: 1rem;
}

.property-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.property-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.apply-button {
  width: 100%;
  padding: 0.75rem;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.apply-button:hover {
  background-color: #0056b3;
}

.no-selection {
  text-align: center;
  color: #6c757d;
  padding: 2rem 0;
}

.status-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 1rem;
  background-color: #fff;
  border-top: 1px solid #ddd;
  font-size: 0.8rem;
  color: #6c757d;
}

.status-item {
  padding: 0 0.5rem;
}

@media (max-width: 768px) {
  .editor-main {
    flex-direction: column;
  }
  
  .component-palette,
  .properties-panel {
    width: 100%;
    height: 200px;
  }
  
  .canvas-container {
    height: calc(100% - 400px);
  }
}
</style>
```

## Component Architecture

### Props

```typescript
interface GrapeJSPageBuilderProps {
  pageId?: string
  templateId?: string
 initialContent?: string
  readOnly?: boolean
  showToolbar?: boolean
  showPalette?: boolean
  showProperties?: boolean
}
```

### Events

```typescript
interface GrapeJSPageBuilderEvents {
  'update:content': (content: string) => void
  'save': (page: Page) => void
  'publish': (page: Page) => void
  'error': (error: Error) => void
  'loading': (isLoading: boolean) => void
}
```

### Methods

```typescript
interface GrapeJSPageBuilderMethods {
  loadPage(pageId: string): Promise<void>
  savePage(): Promise<void>
  exportContent(): string
  importContent(content: string): void
  switchDevice(device: 'desktop' | 'tablet' | 'mobile'): void
  undo(): void
  redo(): void
}
```

## Integration Points

### 1. Component Library Bridge

The Vue wrapper will integrate with the ComponentLibraryBridge to:

- Load available components into the palette
- Convert components to GrapeJS blocks
- Handle component selection and property editing
- Sync component updates between systems

### 2. Template System Bridge

The Vue wrapper will integrate with the TemplateSystemBridge to:

- Load templates into the editor
- Save pages as templates
- Apply template configurations
- Generate template previews

### 3. State Management

The component will use Pinia stores for:

- Page state management
- User preferences
- Editor configuration
- Collaboration state

### 4. Real-time Collaboration

The component will integrate with Laravel Echo for:

- Real-time user presence
- Collaborative editing
- Conflict resolution
- Change notifications

## Responsive Design

The component will support responsive design through:

1. Device switching toolbar
2. Responsive component palette
3. Flexible canvas area
4. Adaptive properties panel
5. Mobile-friendly touch interactions

## Accessibility

The component will follow accessibility best practices:

1. Proper ARIA labels and roles
2. Keyboard navigation support
3. Screen reader compatibility
4. Color contrast compliance
5. Focus management

## Performance Considerations

### 1. Lazy Loading

- Components loaded on-demand
- Templates loaded asynchronously
- Heavy assets loaded progressively

### 2. Caching

- Component metadata caching
- Template preview caching
- User preference caching

### 3. Memory Management

- Proper cleanup of GrapeJS instances
- Event listener removal
- Resource deallocation

## Error Handling

The component will implement comprehensive error handling:

1. Initialization errors
2. Network errors
3. Data validation errors
4. User action errors
5. Recovery mechanisms

## Testing Strategy

### Unit Tests

1. Component initialization
2. Event handling
3. Method execution
4. State management
5. Integration points

### Integration Tests

1. Component Library integration
2. Template System integration
3. Real-time collaboration
4. Responsive behavior
5. Accessibility features

### End-to-End Tests

1. Complete page building workflow
2. Template loading and saving
3. Publishing workflow
4. Collaboration scenarios
5. Error recovery

## Implementation Plan

### Phase 1: Basic Component Structure
- Create Vue wrapper component
- Implement basic GrapeJS initialization
- Add core UI elements (toolbar, canvas, panels)
- Implement basic event handling

### Phase 2: Component Integration
- Integrate with ComponentLibraryBridge
- Implement component palette
- Add property editing capabilities
- Implement component selection

### Phase 3: Template Integration
- Integrate with TemplateSystemBridge
- Implement template loading
- Add template saving functionality
- Implement preview generation

### Phase 4: Advanced Features
- Add real-time collaboration
- Implement responsive design
- Add accessibility features
- Implement performance optimizations

### Phase 5: Testing and Refinement
- Implement unit tests
- Add integration tests
- Perform accessibility testing
- Optimize performance

## Dependencies

- `grapesjs` - Core page builder engine
- `vue` - Vue.js framework
- `pinia` - State management
- `ComponentLibraryBridge` - Component integration service
- `TemplateSystemBridge` - Template integration service
- `Laravel Echo` - Real-time collaboration

## Security Considerations

- Content sanitization
- User permission validation
- Tenant isolation
- CSRF protection
- Input validation