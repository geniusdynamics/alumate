<template>
  <div class="content-editor">
    <!-- Toolbar -->
    <div class="editor-toolbar">
      <div class="toolbar-section">
        <!-- View Toggle -->
        <div class="view-toggle">
          <button
            v-for="view in viewModes"
            :key="view.id"
            @click="activeView = view.id"
            :class="['view-btn', { 'active': activeView === view.id }]"
            :aria-pressed="activeView === view.id"
            :aria-label="`Switch to ${view.label} view`"
          >
            <component :is="view.icon" class="view-icon" />
            <span class="view-label">{{ view.label }}</span>
          </button>
        </div>

        <!-- Add Block Button -->
        <button
          @click="showBlockMenu = !showBlockMenu"
          class="add-block-btn"
          :aria-expanded="showBlockMenu"
          aria-label="Add new content block"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Block
        </button>

        <!-- Block Menu -->
        <div v-if="showBlockMenu" class="block-menu" @click.stop>
          <div class="block-menu-header">
            <h4>Add Content Block</h4>
            <button @click="showBlockMenu = false" class="close-menu-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="block-types">
            <button
              v-for="blockType in availableBlockTypes"
              :key="blockType.id"
              @click="addBlock(blockType.id)"
              class="block-type-btn"
              :title="blockType.description"
            >
              <component :is="blockType.icon" class="block-type-icon" />
              <span class="block-type-label">{{ blockType.label }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Editor Tools -->
      <div class="toolbar-tools">
        <!-- Undo/Redo -->
        <div class="history-tools">
          <button
            @click="undo"
            :disabled="!canUndo"
            class="tool-btn"
            :aria-label="`Undo ${lastUndoAction}`"
            title="Undo (Ctrl+Z)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
          </button>
          <button
            @click="redo"
            :disabled="!canRedo"
            class="tool-btn"
            :aria-label="`Redo ${lastRedoAction}`"
            title="Redo (Ctrl+Y)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 10h10a8 8 0 008 8v2M17 10l6 6m-6-6l6-6" />
            </svg>
          </button>
        </div>

        <!-- Save Indicator -->
        <div class="save-indicator" :class="{ 'saving': isSaving, 'saved': justSaved }">
          <svg v-if="isSaving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else-if="justSaved" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <span>{{ saveStatus }}</span>
        </div>

        <!-- Settings Toggle -->
        <button
          @click="showSettings = !showSettings"
          class="tool-btn"
          :aria-pressed="showSettings"
          :aria-label="`${showSettings ? 'Hide' : 'Show'} editor settings`"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 100 4m0-4v2m0 16v2m0-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v-2m-6-8h2m10 0h2M4.93 4.93l1.41 1.41m10.73 0l1.41 1.41M4.93 19.07l1.41-1.41m10.73 0l1.41-1.41" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Editor Content Area -->
    <div class="editor-content">
      <div class="content-canvas" :class="{ 'view-visual': activeView === 'visual', 'view-code': activeView === 'code', 'view-split': activeView === 'split' }">
        <!-- Visual Editor -->
        <div class="visual-editor" v-if="activeView === 'visual' || activeView === 'split'">
          <div class="blocks-container" :class="{ 'dragging-over': isDragOver }">
            <div
              v-for="(block, index) in content.blocks"
              :key="block.id"
              :class="['content-block', `block-type-${block.type}`, { 'selected': selectedBlockId === block.id }]"
              :data-block-id="block.id"
              @click="selectBlock(block.id)"
              @dragstart="onBlockDragStart($event, index)"
              @dragend="onBlockDragEnd"
              @dragover="onBlockDragOver($event, index)"
              @drop="onBlockDrop($event, index)"
              draggable="true"
              tabindex="0"
              role="button"
              :aria-label="`Content block: ${blockTypeLabel(block.type)}`"
              :aria-selected="selectedBlockId === block.id"
            >
              <!-- Block Controls -->
              <div class="block-controls">
                <div class="block-grip">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </div>
                <button
                  @click.stop="duplicateBlock(block.id)"
                  class="block-control-btn"
                  :aria-label="`Duplicate ${blockTypeLabel(block.type)} block`"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
                <button
                  @click.stop="deleteBlock(block.id)"
                  class="block-control-btn delete-btn"
                  :aria-label="`Delete ${blockTypeLabel(block.type)} block`"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <!-- Block Content -->
              <div class="block-content" @click.stop>
                <component
                  :is="getBlockComponent(block.type)"
                  :block="block"
                  :is-editing="selectedBlockId === block.id"
                  @update="updateBlock(block.id, $event)"
                />
                <div v-if="!block.isVisible" class="block-hidden-indicator">
                  <span>Hidden</span>
                </div>
              </div>

              <!-- Block Status -->
              <div class="block-status" v-if="!block.isVisible">
                <span class="status-indicator hidden">Hidden</span>
              </div>
            </div>

            <!-- Add Block Drop Zone -->
            <div class="add-block-drop-zone" :class="{ 'active': showBlockDropZone }" @drop="onBlockDrop($event, content.blocks.length)" @dragover.prevent>
              <div class="drop-zone-content">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Drop new block here</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Code Editor -->
        <div class="code-editor" v-if="activeView === 'code' || activeView === 'split'">
          <div class="code-header">
            <span class="code-label">Content Structure (JSON)</span>
            <button @click="formatJson" class="format-btn">
              Format JSON
            </button>
          </div>
          <textarea
            v-model="contentJson"
            class="json-editor"
            spellcheck="false"
            :aria-label="`Content structure JSON editor`"
          ></textarea>
          <div v-if="jsonError" class="json-error">
            {{ jsonError }}
          </div>
        </div>
      </div>
    </div>

    <!-- Block Editor Sidebar -->
    <div v-if="selectedBlockId" class="block-editor-sidebar">
      <div class="sidebar-header">
        <h3 class="sidebar-title">Block Settings</h3>
        <button @click="selectedBlockId = null" class="close-sidebar-btn">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="sidebar-content">
        <block-settings
          v-if="selectedBlock"
          :block="selectedBlock"
          @update="updateBlock(selectedBlock.id, $event)"
          @duplicate="duplicateBlock(selectedBlock.id)"
          @delete="deleteBlock(selectedBlock.id)"
        />
      </div>
    </div>

    <!-- Settings Panel -->
    <div v-if="showSettings" class="settings-panel">
      <div class="settings-header">
        <h3 class="settings-title">Editor Settings</h3>
        <button @click="showSettings = false" class="close-settings-btn">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="settings-content">
        <div class="setting-group">
          <h4>Auto-save</h4>
          <label class="setting-label">
            <input
              v-model="settings.autoSave"
              type="checkbox"
              class="setting-checkbox"
            />
            Auto-save changes
          </label>
        </div>

        <div class="setting-group">
          <h4>Real-time Preview</h4>
          <label class="setting-label">
            <input
              v-model="settings.realTimePreview"
              type="checkbox"
              class="setting-checkbox"
            />
            Update preview in real-time
          </label>
        </div>

        <div class="setting-group">
          <h4>Accessibility</h4>
          <label class="setting-label">
            <input
              v-model="settings.showAccessibilityWarnings"
              type="checkbox"
              class="setting-checkbox"
            />
            Show accessibility warnings
          </label>
        </div>
      </div>
    </div>

    <!-- Drag Overlay -->
    <div v-if="isDragging" class="drag-overlay">
      <div class="drag-preview">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
        </svg>
        <span>Drag to reorder blocks</span>
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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

// Types
import type {
  ContentCustomization,
  ContentBlock,
  ContentEditorState
} from '@/types/components'

// Icons
import {
  EyeIcon,
  CodeBracketIcon,
  DocumentTextIcon,
  PhotoIcon,
  Square3Stack3DIcon,
  MinusIcon,
  PlayIcon,
  LinkIcon,
  ChatBubbleLeftRightIcon,
  ViewfinderCircleIcon
} from '@heroicons/vue/24/outline'

// Props
interface Props {
  modelValue: ContentCustomization
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: ContentCustomization]
  'save': [content: ContentCustomization]
}>()

// Reactive data
const content = ref<ContentCustomization>(props.modelValue)
const selectedBlockId = ref<string | null>(null)
const activeView = ref<'visual' | 'code' | 'split'>('visual')
const isLoading = ref(false)
const loadingMessage = ref('')
const showBlockMenu = ref(false)
const showSettings = ref(false)
const isDragging = ref(false)
const isDragOver = ref(false)
const showBlockDropZone = ref(false)
const draggedBlockIndex = ref<number | null>(null)
const isSaving = ref(false)
const justSaved = ref(false)
const undoStack = ref<ContentEditorState[]>([])
const redoStack = ref<ContentEditorState[]>([])

// Settings
const settings = ref({
  autoSave: true,
  realTimePreview: true,
  showAccessibilityWarnings: true
})

// View modes
const viewModes = [
  { id: 'visual', label: 'Visual', icon: EyeIcon },
  { id: 'code', label: 'Code', icon: CodeBracketIcon },
  { id: 'split', label: 'Split', icon: DocumentTextIcon }
]

// Available block types
const availableBlockTypes = [
  { id: 'text', label: 'Text', icon: DocumentTextIcon, description: 'Rich text content with formatting' },
  { id: 'image', label: 'Image', icon: PhotoIcon, description: 'Display images and media assets' },
  { id: 'button', label: 'Button', icon: Square3Stack3DIcon, description: 'Call-to-action buttons' },
  { id: 'divider', label: 'Divider', icon: MinusIcon, description: 'Visual separation between sections' },
  { id: 'video', label: 'Video', icon: PlayIcon, description: 'Video embedding and player' },
  { id: 'link', label: 'Link', icon: LinkIcon, description: 'Hyperlinks to external content' },
  { id: 'quote', label: 'Quote', icon: ChatBubbleLeftRightIcon, description: 'Highlighted quotes or testimonials' },
  { id: 'spacer', label: 'Spacer', icon: ViewfinderCircleIcon, description: 'Empty space between blocks' }
]

// Computed properties
const selectedBlock = computed(() => {
  return selectedBlockId.value ? content.value.blocks.find(b => b.id === selectedBlockId.value) : null
})

const canUndo = computed(() => undoStack.value.length > 0)
const canRedo = computed(() => redoStack.value.length > 0)

const lastUndoAction = computed(() => {
  const lastAction = undoStack.value[undoStack.value.length - 1]
  return lastAction?.action || 'last change'
})

const lastRedoAction = computed(() => {
  const lastAction = redoStack.value[redoStack.value.length - 1]
  return lastAction?.action || 'last undone change'
})

const saveStatus = computed(() => {
  if (isSaving.value) return 'Saving...'
  if (justSaved.value) return 'Saved'
  return 'Not saved'
})

const contentJson = computed({
  get: () => {
    try {
      return JSON.stringify(content.value, null, 2)
    } catch {
      return '{}'
    }
  },
  set: (value) => {
    try {
      const parsed = JSON.parse(value)
      if (parsed && typeof parsed === 'object') {
        content.value = parsed
        jsonError = ref('')
      }
    } catch (e: any) {
      jsonError = ref(`Invalid JSON: ${e.message}`)
    }
  }
})

const jsonError = ref('')

// Methods
const selectBlock = (blockId: string) => {
  selectedBlockId.value = selectedBlockId.value === blockId ? null : blockId
}

const addBlock = (blockType: string) => {
  const newBlock: ContentBlock = {
    id: `block-${Date.now()}`,
    type: blockType as ContentBlock['type'],
    data: getDefaultBlockData(blockType),
    position: content.value.blocks.length,
    isVisible: true,
    responsiveSettings: {
      desktop: {},
      tablet: {},
      mobile: {}
    },
    animationSettings: {
      enabled: false,
      type: 'fade',
      delay: 0,
      duration: 500
    },
    accessibilitySettings: {
      ariaLabel: '',
      role: '',
      screenReaderText: ''
    }
  }

  content.value.blocks.push(newBlock)
  selectedBlockId.value = newBlock.id
  showBlockMenu.value = false

  saveSnapshot(`Add ${blockType} block`)
  emitContentUpdate()
}

const duplicateBlock = (blockId: string) => {
  const originalBlock = content.value.blocks.find(b => b.id === blockId)
  if (!originalBlock) return

  const duplicatedBlock: ContentBlock = {
    ...JSON.parse(JSON.stringify(originalBlock)),
    id: `block-${Date.now()}`,
    position: originalBlock.position + 1
  }

  const index = content.value.blocks.findIndex(b => b.id === blockId)
  content.value.blocks.splice(index + 1, 0, duplicatedBlock)
  selectedBlockId.value = duplicatedBlock.id

  saveSnapshot(`Duplicate ${originalBlock.type} block`)
  emitContentUpdate()
}

const deleteBlock = (blockId: string) => {
  const index = content.value.blocks.findIndex(b => b.id === blockId)
  if (index === -1) return

  const deletedBlock = content.value.blocks[index]
  content.value.blocks.splice(index, 1)

  // Update positions
  content.value.blocks.forEach((block, i) => {
    block.position = i
  })

  if (selectedBlockId.value === blockId) {
    selectedBlockId.value = null
  }

  saveSnapshot(`Delete ${deletedBlock.type} block`)
  emitContentUpdate()
}

const updateBlock = (blockId: string, updates: Partial<ContentBlock>) => {
  const block = content.value.blocks.find(b => b.id === blockId)
  if (!block) return

  Object.assign(block, updates)
  saveSnapshot(`Update ${block.type} block`)
  emitContentUpdate()
}

const getDefaultBlockData = (blockType: string) => {
  const defaults = {
    text: {
      html: '<p>Enter your text content here...</p>',
      text: 'Enter your text content here...',
      format: 'paragraph' as const,
      alignment: 'left' as const
    },
    image: {
      url: '',
      alt: '',
      caption: '',
      width: 800,
      height: 600,
      aspectRatio: 'rectangle' as const,
      fit: 'cover' as const
    },
    button: {
      text: 'Click Here',
      url: '#',
      style: 'primary' as const,
      size: 'md' as const
    },
    divider: {},
    video: {
      url: '',
      poster: '',
      autoplay: false,
      muted: true
    },
    link: {
      text: 'Link Text',
      url: '#'
    },
    quote: {
      text: 'Enter your quote here...',
      author: '',
      source: ''
    },
    spacer: {
      height: 50
    }
  }

  return defaults[blockType as keyof typeof defaults] || {}
}

const blockTypeLabel = (type: string) => {
  return availableBlockTypes.find(bt => bt.id === type)?.label || type
}

const getBlockComponent = (type: string) => {
  // In a real implementation, this would map to actual Vue components
  // For now, return placeholder components
  return `${type.charAt(0).toUpperCase() + type.slice(1)}Block`
}

const onBlockDragStart = (event: DragEvent, index: number) => {
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', index.toString())
    isDragging.value = true
    draggedBlockIndex.value = index
  }
}

const onBlockDragEnd = () => {
  isDragging.value = false
  isDragOver.value = false
  showBlockDropZone.value = false
  draggedBlockIndex.value = null
}

const onBlockDragOver = (event: DragEvent, index: number) => {
  event.preventDefault()
  if (draggedBlockIndex.value !== null && draggedBlockIndex.value !== index) {
    isDragOver.value = true
  }
}

const onBlockDrop = (event: DragEvent, targetIndex: number) => {
  event.preventDefault()

  const draggedIndex = draggedBlockIndex.value
  if (draggedIndex === null || draggedIndex === targetIndex) {
    onBlockDragEnd()
    return
  }

  // Reorder blocks
  const blocks = [...content.value.blocks]
  const [draggedBlock] = blocks.splice(draggedIndex, 1)
  blocks.splice(targetIndex, 0, draggedBlock)

  // Update positions
  blocks.forEach((block, i) => {
    block.position = i
  })

  content.value.blocks = blocks
  selectedBlockId.value = draggedBlock.id

  saveSnapshot('Reorder blocks')
  emitContentUpdate()
  onBlockDragEnd()
}

const saveSnapshot = (action: string) => {
  const snapshot: ContentEditorState = {
    isActive: true,
    selectedBlockId: selectedBlockId.value,
    editMode: activeView.value,
    hasUnsavedChanges: true,
    undoStack: [],
    redoStack: [],
    searchQuery: '',
    filters: {},
    dragState: {
      isDragging: isDragging.value,
      draggedId: null,
      dropTargetId: null,
      dropPosition: null
    },
    action
  }

  undoStack.value.push(snapshot)
  redoStack.value = []
}

const undo = () => {
  if (!canUndo.value) return

  const lastState = undoStack.value.pop()!
  const currentState: ContentEditorState = {
    isActive: true,
    selectedBlockId: selectedBlockId.value,
    editMode: activeView.value,
    hasUnsavedChanges: true,
    undoStack: [],
    redoStack: [],
    searchQuery: '',
    filters: {},
    dragState: {
      isDragging: false,
      draggedId: null,
      dropTargetId: null,
      dropPosition: null
    },
    action: `Redo ${lastState.action}`
  }

  redoStack.value.push(currentState)

  // Restore previous content state
  restoreSnapshot(lastState)
}

const redo = () => {
  if (!canRedo.value) return

  const nextState = redoStack.value.pop()!
  const currentState: ContentEditorState = {
    isActive: true,
    selectedBlockId: selectedBlockId.value,
    editMode: activeView.value,
    hasUnsavedChanges: true,
    undoStack: [],
    redoStack: [],
    searchQuery: '',
    filters: {},
    dragState: {
      isDragging: false,
      draggedId: null,
      dropTargetId: null,
      dropPosition: null
    },
    action: `Undo ${nextState.action}`
  }

  undoStack.value.push(currentState)
  restoreSnapshot(nextState)
}

const restoreSnapshot = (snapshot: ContentEditorState) => {
  selectedBlockId.value = snapshot.selectedBlockId
  activeView.value = snapshot.editMode
}

const formatJson = () => {
  try {
    contentJson.value = JSON.stringify(JSON.parse(contentJson.value), null, 2)
    jsonError.value = ''
  } catch (e: any) {
    jsonError.value = `Invalid JSON: ${e.message}`
  }
}

const emitContentUpdate = () => {
  emit('update:modelValue', content.value)

  if (settings.value.autoSave) {
    debouncedSave()
  }
}

const debouncedSave = useDebounceFn(() => {
  saveContent()
}, 2000)

const saveContent = async () => {
  try {
    isSaving.value = true
    justSaved.value = false

    // Simulate save operation
    await new Promise(resolve => setTimeout(resolve, 1000))

    isSaving.value = false
    justSaved.value = true

    setTimeout(() => {
      justSaved.value = false
    }, 2000)

    emit('save', content.value)

  } catch (error) {
    console.error('Failed to save content:', error)
    isSaving.value = false
  }
}

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 's':
        event.preventDefault()
        if (!settings.value.autoSave) {
          saveContent()
        }
        break
      case 'z':
        event.preventDefault()
        undo()
        break
      case 'y':
        event.preventDefault()
        redo()
        break
    }
  } else if (event.key === 'Delete' && selectedBlockId.value) {
    event.preventDefault()
    deleteBlock(selectedBlockId.value)
  }
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(content.value)) {
    content.value = newValue
  }
})

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)

  // Initial snapshot
  saveSnapshot('Initialize editor')
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.content-editor {
  @apply h-screen flex flex-col bg-gray-50 dark:bg-gray-900;
}

/* Toolbar */
.editor-toolbar {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between;
}

.toolbar-section {
  @apply flex items-center gap-4;
}

.view-toggle {
  @apply flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1;
}

.view-btn {
  @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors;
  @apply text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none;
}

.view-btn.active {
  @apply bg-blue-500 text-white;
}

.view-icon {
  @apply w-4 h-4;
}

.add-block-btn {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none transition-colors;
}

.block-menu {
  @apply absolute top-full left-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl;
  @apply shadow-xl z-50 p-4;
}

.block-menu-header {
  @apply flex items-center justify-between mb-4;
}

.block-menu-header h4 {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-menu-btn {
  @apply p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded;
}

.block-types {
  @apply grid grid-cols-2 gap-2;
}

.block-type-btn {
  @apply flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg;
  @apply bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none text-left;
}

.block-type-icon {
  @apply w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0;
}

.block-type-label {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.toolbar-tools {
  @apply flex items-center gap-4;
}

.history-tools {
  @apply flex items-center gap-1;
}

.tool-btn {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed;
}

.save-indicator {
  @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg;
  @apply bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200;
  transition: all 0.3s ease;
}

.save-indicator.saving {
  @apply bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200;
}

.save-indicator.saved {
  @apply bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200;
}

/* Content Area */
.editor-content {
  @apply flex-1 overflow-hidden;
}

.content-canvas {
  @apply h-full;
}

.visual-editor {
  @apply h-full overflow-y-auto p-6;
}

.blocks-container {
  @apply space-y-4;
}

.dragging-over {
  @apply bg-blue-50 dark:bg-blue-900/20;
}

.content-block {
  @apply relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
  @apply transition-all duration-200 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none cursor-pointer;
}

.content-block.selected {
  @apply ring-2 ring-blue-500 shadow-lg;
}

.content-block.selected .block-controls {
  @apply opacity-100;
}

.block-controls {
  @apply absolute top-3 left-3 flex items-center gap-2 px-2 py-1 bg-white dark:bg-gray-800 rounded shadow opacity-0 transition-opacity z-10;
}

.block-grip {
  @apply p-1 text-gray-400 cursor-move;
}

.block-control-btn {
  @apply p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded transition-colors;
}

.block-control-btn.delete-btn:hover {
  @apply text-red-600;
}

.block-content {
  @apply p-6 cursor-text;
}

.block-hidden-indicator {
  @apply absolute top-2 right-2 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs;
}

.block-status {
  @apply absolute bottom-3 right-3;
}

.status-indicator {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.status-indicator.hidden {
  @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.add-block-drop-zone {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg py-12 transition-colors;
  @apply opacity-0 pointer-events-none;
}

.add-block-drop-zone.active {
  @apply opacity-100 pointer-events-auto border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.drop-zone-content {
  @apply flex flex-col items-center gap-2 text-gray-500 dark:text-gray-400;
}

.drop-zone-content svg {
  @apply opacity-50;
}

/* Code Editor */
.code-editor {
  @apply h-full flex flex-col;
}

.code-header {
  @apply flex items-center justify-between px-6 py-4 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700;
}

.code-label {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.format-btn {
  @apply px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors;
}

.json-editor {
  @apply flex-1 p-4 font-mono text-sm bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-0 resize-none;
  @apply focus:outline-none;
}

.json-error {
  @apply px-6 py-2 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-sm border-t border-red-200 dark:border-red-800;
}

/* Split View */
.view-split .visual-editor {
  @apply w-1/2 border-r border-gray-200 dark:border-gray-700;
}

.view-split .code-editor {
  @apply w-1/2;
}

/* Block Editor Sidebar */
.block-editor-sidebar {
  @apply w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col;
}

.sidebar-header {
  @apply px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.sidebar-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-sidebar-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded;
}

.sidebar-content {
  @apply flex-1 overflow-y-auto p-6;
}

/* Settings Panel */
.settings-panel {
  @apply absolute top-20 right-6 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl;
  @apply shadow-xl z-50;
}

.settings-header {
  @apply px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.settings-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-settings-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded;
}

.settings-content {
  @apply p-6 space-y-6;
}

.setting-group h4 {
  @apply text-lg font-medium text-gray-900 dark:text-white mb-3;
}

.setting-label {
  @apply flex items-center gap-3 cursor-pointer;
}

.setting-checkbox {
  @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

/* Overlay Styles */
.drag-overlay,
.loading-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.drag-preview {
  @apply bg-white dark:bg-gray-800 rounded-lg px-6 py-4 flex items-center gap-3 shadow-xl;
}

.loading-content {
  @apply bg-white dark:bg-gray-800 rounded-lg px-6 py-4 flex flex-col items-center gap-4 shadow-xl;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin;
}

/* Accessibility Highlights */
.content-editor:focus-within [aria-selected="true"] {
  @apply ring-2 ring-blue-500;
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    @apply animate-none;
  }

  .drag-preview {
    @apply transition-none;
  }

  .content-block:hover {
    @apply transform-none;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .content-block {
    @apply border-2;
  }

  .content-block.selected {
    @apply border-blue-500 border-4;
  }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .editor-toolbar {
    @apply flex-col gap-4;
  }

  .toolbar-section,
  .toolbar-tools {
    @apply justify-center;
  }

  .block-editor-sidebar,
  .settings-panel {
    @apply w-full inset-x-0 inset-y-auto;
  }

  .block-controls {
    @apply opacity-100;
  }

  .view-split {
    @apply flex-col;
  }

  .view-split .visual-editor,
  .view-split .code-editor {
    @apply w-full h-1/2;
  }

  .view-split .visual-editor {
    @apply border-r-0 border-b border-gray-200 dark:border-gray-700;
  }
}
</style>