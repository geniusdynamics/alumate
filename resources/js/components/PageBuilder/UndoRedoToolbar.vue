<template>
  <div class="undo-redo-toolbar">
    <!-- Undo/Redo Actions -->
    <div class="action-group">
      <button
        @click="undo"
        :disabled="!canUndo"
        :class="['action-btn undo-btn', { 'disabled': !canUndo }]"
        :title="`Undo (Ctrl+Z) - ${undoDescription}`"
      >
        <Icon name="undo" class="w-4 h-4" />
        <span class="btn-text">Undo</span>
      </button>

      <button
        @click="redo"
        :disabled="!canRedo"
        :class="['action-btn redo-btn', { 'disabled': !canRedo }]"
        :title="`Redo (Ctrl+Y) - ${redoDescription}`"
      >
        <Icon name="redo" class="w-4 h-4" />
        <span class="btn-text">Redo</span>
      </button>
    </div>

    <!-- History Indicator -->
    <div class="history-indicator">
      <div class="history-dots">
        <div
          v-for="(item, index) in historyPreview"
          :key="index"
          :class="['history-dot', { 
            'current': index === currentHistoryIndex,
            'past': index < currentHistoryIndex,
            'future': index > currentHistoryIndex
          }]"
          @click="jumpToHistory(index)"
          :title="item.description"
        ></div>
      </div>
      <span class="history-count">
        {{ currentHistoryIndex + 1 }} / {{ totalHistoryItems }}
      </span>
    </div>

    <!-- Auto-save Status -->
    <div class="autosave-status">
      <div :class="['autosave-indicator', { 
        'saving': isSaving,
        'saved': !hasUnsavedChanges && !isSaving,
        'unsaved': hasUnsavedChanges && !isSaving
      }]">
        <Icon 
          :name="getSaveIcon()" 
          :class="['w-3 h-3', { 'animate-spin': isSaving }]" 
        />
      </div>
      <span class="autosave-text">{{ getSaveText() }}</span>
      <span v-if="lastSaved" class="last-saved">
        {{ formatLastSaved(lastSaved) }}
      </span>
    </div>

    <!-- History Panel Toggle -->
    <button
      @click="toggleHistoryPanel"
      :class="['panel-toggle', { 'active': showHistoryPanel }]"
      title="Show history panel"
    >
      <Icon name="history" class="w-4 h-4" />
    </button>

    <!-- History Panel -->
    <div v-if="showHistoryPanel" class="history-panel">
      <div class="panel-header">
        <h4 class="panel-title">History</h4>
        <button @click="showHistoryPanel = false" class="close-panel-btn">
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>

      <div class="history-list">
        <div
          v-for="(item, index) in historyItems"
          :key="index"
          :class="['history-item', { 
            'current': index === currentHistoryIndex,
            'clickable': index !== currentHistoryIndex
          }]"
          @click="jumpToHistory(index)"
        >
          <div class="item-icon">
            <Icon :name="getHistoryIcon(item.type)" class="w-4 h-4" />
          </div>
          <div class="item-content">
            <div class="item-description">{{ item.description }}</div>
            <div class="item-time">{{ formatHistoryTime(item.timestamp) }}</div>
          </div>
          <div v-if="index === currentHistoryIndex" class="current-indicator">
            <Icon name="arrow-right" class="w-3 h-3" />
          </div>
        </div>
      </div>

      <div class="history-actions">
        <button @click="clearHistory" class="clear-history-btn">
          <Icon name="trash" class="w-4 h-4" />
          Clear History
        </button>
        <button @click="exportHistory" class="export-history-btn">
          <Icon name="download" class="w-4 h-4" />
          Export
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { realTimeEditingService } from '../../services/RealTimeEditingService'
import Icon from '../ui/Icon.vue'

// Props
interface Props {
  grapeJSEditor?: any
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  historyChange: [index: number]
  save: []
}>()

// Reactive state
const showHistoryPanel = ref(false)
const isSaving = ref(false)

// Computed properties from editing service
const canUndo = computed(() => realTimeEditingService.canUndo.value)
const canRedo = computed(() => realTimeEditingService.canRedo.value)
const hasUnsavedChanges = computed(() => realTimeEditingService.editingState.hasUnsavedChanges)
const lastSaved = computed(() => realTimeEditingService.editingState.lastSaved)

const currentHistoryIndex = computed(() => realTimeEditingService.undoRedoState.currentIndex)
const totalHistoryItems = computed(() => realTimeEditingService.undoRedoState.history.length)

const historyItems = computed(() => {
  return realTimeEditingService.undoRedoState.history.map((item, index) => ({
    ...item,
    description: generateHistoryDescription(item, index),
    type: determineHistoryType(item)
  }))
})

const historyPreview = computed(() => {
  // Show last 10 history items for the dots indicator
  const items = historyItems.value
  const start = Math.max(0, items.length - 10)
  return items.slice(start)
})

const undoDescription = computed(() => {
  if (!canUndo.value) return 'Nothing to undo'
  const prevIndex = currentHistoryIndex.value - 1
  const prevItem = historyItems.value[prevIndex]
  return prevItem ? prevItem.description : 'Previous action'
})

const redoDescription = computed(() => {
  if (!canRedo.value) return 'Nothing to redo'
  const nextIndex = currentHistoryIndex.value + 1
  const nextItem = historyItems.value[nextIndex]
  return nextItem ? nextItem.description : 'Next action'
})

// Methods
const undo = () => {
  realTimeEditingService.undo()
  emit('historyChange', currentHistoryIndex.value)
}

const redo = () => {
  realTimeEditingService.redo()
  emit('historyChange', currentHistoryIndex.value)
}

const jumpToHistory = (index: number) => {
  if (index === currentHistoryIndex.value) return
  
  const currentIndex = currentHistoryIndex.value
  const diff = index - currentIndex
  
  if (diff > 0) {
    // Redo multiple times
    for (let i = 0; i < diff; i++) {
      if (realTimeEditingService.canRedo.value) {
        realTimeEditingService.redo()
      }
    }
  } else {
    // Undo multiple times
    for (let i = 0; i < Math.abs(diff); i++) {
      if (realTimeEditingService.canUndo.value) {
        realTimeEditingService.undo()
      }
    }
  }
  
  emit('historyChange', currentHistoryIndex.value)
}

const toggleHistoryPanel = () => {
  showHistoryPanel.value = !showHistoryPanel.value
}

const clearHistory = () => {
  if (confirm('Are you sure you want to clear the history? This action cannot be undone.')) {
    // Reset history to current state only
    realTimeEditingService.undoRedoState.history = [
      realTimeEditingService.undoRedoState.history[currentHistoryIndex.value]
    ]
    realTimeEditingService.undoRedoState.currentIndex = 0
  }
}

const exportHistory = () => {
  const historyData = {
    items: historyItems.value,
    currentIndex: currentHistoryIndex.value,
    exportedAt: new Date().toISOString()
  }
  
  const blob = new Blob([JSON.stringify(historyData, null, 2)], {
    type: 'application/json'
  })
  
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `page-history-${Date.now()}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const getSaveIcon = () => {
  if (isSaving.value) return 'spinner'
  if (!hasUnsavedChanges.value) return 'check'
  return 'clock'
}

const getSaveText = () => {
  if (isSaving.value) return 'Saving...'
  if (!hasUnsavedChanges.value) return 'Saved'
  return 'Unsaved changes'
}

const formatLastSaved = (date: Date) => {
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  if (diff < 60000) return 'Just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  
  return date.toLocaleDateString()
}

const formatHistoryTime = (timestamp: number) => {
  return new Date(timestamp).toLocaleTimeString()
}

const generateHistoryDescription = (item: any, index: number) => {
  if (index === 0) return 'Initial state'
  
  // Analyze the changes to generate a meaningful description
  const prevItem = realTimeEditingService.undoRedoState.history[index - 1]
  if (!prevItem) return 'Change'
  
  // Compare HTML to detect changes
  if (item.html !== prevItem.html) {
    if (item.html.length > prevItem.html.length) {
      return 'Added content'
    } else if (item.html.length < prevItem.html.length) {
      return 'Removed content'
    } else {
      return 'Modified content'
    }
  }
  
  // Compare CSS to detect style changes
  if (item.css !== prevItem.css) {
    return 'Style changes'
  }
  
  return 'Change'
}

const determineHistoryType = (item: any) => {
  // Determine the type of change for icon selection
  return 'edit' // Default type
}

const getHistoryIcon = (type: string) => {
  const iconMap = {
    'add': 'plus',
    'remove': 'minus',
    'edit': 'edit',
    'style': 'palette',
    'move': 'move'
  }
  
  return iconMap[type] || 'edit'
}

// Auto-save handling
const handleAutoSave = () => {
  isSaving.value = true
  emit('save')
  
  // Simulate save completion
  setTimeout(() => {
    isSaving.value = false
  }, 1000)
}

// Listen for auto-save events
onMounted(() => {
  window.addEventListener('grapejs:auto-save', handleAutoSave)
  window.addEventListener('grapejs:save', handleAutoSave)
})

onUnmounted(() => {
  window.removeEventListener('grapejs:auto-save', handleAutoSave)
  window.removeEventListener('grapejs:save', handleAutoSave)
})

// Keyboard shortcuts are handled by the RealTimeEditingService
</script>

<style scoped>
.undo-redo-toolbar {
  @apply relative flex items-center space-x-4 px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700;
}

.action-group {
  @apply flex items-center space-x-1;
}

.action-btn {
  @apply flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors;
}

.action-btn.disabled {
  @apply opacity-50 cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700;
}

.btn-text {
  @apply hidden sm:inline;
}

.history-indicator {
  @apply flex items-center space-x-2;
}

.history-dots {
  @apply flex items-center space-x-1;
}

.history-dot {
  @apply w-2 h-2 rounded-full cursor-pointer transition-colors;
}

.history-dot.past {
  @apply bg-blue-500;
}

.history-dot.current {
  @apply bg-green-500 ring-2 ring-green-200;
}

.history-dot.future {
  @apply bg-gray-300 dark:bg-gray-600;
}

.history-count {
  @apply text-xs text-gray-500 dark:text-gray-400 font-mono;
}

.autosave-status {
  @apply flex items-center space-x-2;
}

.autosave-indicator {
  @apply flex items-center justify-center w-6 h-6 rounded-full;
}

.autosave-indicator.saving {
  @apply bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400;
}

.autosave-indicator.saved {
  @apply bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400;
}

.autosave-indicator.unsaved {
  @apply bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400;
}

.autosave-text {
  @apply text-xs font-medium;
}

.autosave-text {
  @apply text-gray-600 dark:text-gray-400;
}

.last-saved {
  @apply text-xs text-gray-500 dark:text-gray-500;
}

.panel-toggle {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors;
}

.panel-toggle.active {
  @apply text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900;
}

.history-panel {
  @apply absolute top-full right-0 mt-1 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50;
}

.panel-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.panel-title {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.close-panel-btn {
  @apply p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded;
}

.history-list {
  @apply max-h-64 overflow-y-auto;
}

.history-item {
  @apply flex items-center space-x-3 p-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0;
}

.history-item.current {
  @apply bg-blue-50 dark:bg-blue-900;
}

.history-item.clickable {
  @apply cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700;
}

.item-icon {
  @apply flex-shrink-0 text-gray-500 dark:text-gray-400;
}

.item-content {
  @apply flex-1 min-w-0;
}

.item-description {
  @apply text-sm font-medium text-gray-900 dark:text-white truncate;
}

.item-time {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.current-indicator {
  @apply flex-shrink-0 text-blue-600 dark:text-blue-400;
}

.history-actions {
  @apply flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-700;
}

.clear-history-btn,
.export-history-btn {
  @apply flex items-center space-x-2 px-3 py-2 text-xs font-medium rounded transition-colors;
}

.clear-history-btn {
  @apply text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800;
}

.export-history-btn {
  @apply text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600;
}
</style>