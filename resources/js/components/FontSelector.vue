<template>
  <div class="font-selector" :class="{ 'font-selector--disabled': disabled }">
    <!-- Font Input Group -->
    <div class="font-input-group">
      <!-- Font Preview -->
      <div class="font-preview" @click="toggleSelector" :tabindex="disabled ? -1 : 0" role="button" :aria-label="`Select ${label} font`" :aria-disabled="disabled" :aria-expanded="isOpen">
        <div class="font-sample" :style="{ fontFamily: currentFont.family }">
          Aa
        </div>
        <div class="font-info">
          <span class="font-name">{{ currentFont.family }}</span>
          <span class="font-weight">{{ getWeightLabel(currentFont.weight) }}</span>
        </div>
        <div class="font-toggle" :class="{ 'is-open': isOpen }">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Font Selector Panel -->
    <div v-if="isOpen" class="font-selector-panel" @click.stop>
      <!-- Font Source Tabs -->
      <div class="font-source-tabs">
        <button
          v-for="source in fontSources"
          :key="source.id"
          @click="activeFontSource = source.id"
          :class="['source-tab', { 'active': activeFontSource === source.id }]"
          type="button"
          :aria-pressed="activeFontSource === source.id"
        >
          {{ source.label }}
        </button>
      </div>

      <!-- Font Search -->
      <div class="font-search">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search fonts..."
          class="font-search-input"
          :aria-label="`Search ${activeFontSource} fonts`"
        />
        <button @click="clearSearch" v-if="searchQuery" class="clear-search-btn" :aria-label="`Clear search`">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Font Filters -->
      <div class="font-filters" v-if="activeFontSource === 'google'">
        <div class="filter-group">
          <label class="filter-label">Category:</label>
          <select v-model="categoryFilter" class="filter-select" :aria-label="`Filter by font category`">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ category.charAt(0).toUpperCase() + category.slice(1) }}
            </option>
          </select>
        </div>
      </div>

      <!-- Font List -->
      <div class="font-list" :class="{ 'loading': fontsLoading }">
        <div v-if="fontsLoading" class="font-loading">
          <div class="loading-spinner"></div>
          <p>Loading fonts...</p>
        </div>

        <div v-else-if="filteredFonts.length === 0" class="no-fonts">
          <svg class="no-fonts-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          <h4 class="no-fonts-title">No fonts found</h4>
          <p class="no-fonts-message">Try adjusting your search or filters</p>
        </div>

        <div v-else class="font-items">
          <button
            v-for="font in paginatedFonts"
            :key="font.family"
            @click="selectFont(font)"
            :class="['font-item', { 'selected': isSelectedFont(font) }]"
            type="button"
            :aria-label="`Select ${font.family} font`"
          >
            <div class="font-sample" :style="{ fontFamily: font.family }">
              {{ previewText }}
            </div>
            <div class="font-details">
              <span class="font-name">{{ font.family }}</span>
              <div class="font-meta">
                <span class="font-category" v-if="font.category">{{ font.category }}</span>
                <span class="font-source" v-if="font.source">{{ font.source }}</span>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Font Size and Weight Controls -->
      <div v-if="selectedFont" class="font-controls">
        <div class="control-group">
          <label class="control-label" for="font-weight">Weight:</label>
          <select
            id="font-weight"
            v-model="selectedFontWeight"
            @change="updateFontWeight"
            class="font-weight-select"
            :aria-label="`Select font weight for ${selectedFont.family}`"
          >
            <option v-for="weight in availableWeights" :key="weight" :value="weight">
              {{ getWeightLabel(weight) }}
            </option>
          </select>
        </div>

        <div class="control-group">
          <label class="control-label" for="font-size">Size:</label>
          <input
            id="font-size"
            v-model.number="fontSize"
            type="range"
            min="12"
            max="72"
            step="1"
            @input="updateFontSize"
            class="font-size-slider"
            :aria-label="`Font size, current ${fontSize}px`"
          />
          <input
            v-model.number="fontSize"
            type="number"
            min="12"
            max="72"
            step="1"
            @input="updateFontSize"
            class="font-size-input"
            :aria-label="`Font size input, current ${fontSize}px`"
          />
        </div>

        <div class="control-group">
          <label class="control-label">Preview:</label>
          <div class="preview-options">
            <button
              v-for="text in previewOptions"
              :key="text"
              @click="previewText = text"
              :class="['preview-option', { 'active': previewText === text }]"
              type="button"
              :aria-label="`Preview with text: ${text}`"
            >
              {{ text }}
            </button>
          </div>
        </div>
      </div>

      <!-- Font Loading Status -->
      <div v-if="hasFontLoading" class="font-loading-status">
        <div class="loading-item" v-for="font in loadingFonts" :key="font">
          <span>{{ font }}</span>
          <div class="loading-bar">
            <div class="loading-progress" :style="{ width: fontLoadProgress[font] + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="resetToOriginal" class="action-btn action-btn--secondary" :disabled="!hasChanged" :aria-label="`Reset ${label} to original font`">
          Reset
        </button>
        <button @click="applyFont" class="action-btn action-btn--primary" :aria-label="`Apply ${currentFont.family} font`">
          Apply
        </button>
        <button @click="cancel" class="action-btn action-btn--ghost" aria-label="Cancel font selection">
          Cancel
        </button>
      </div>
    </div>

    <!-- Font Loading Indicator -->
    <div v-if="hasFontLoading" class="font-loading-indicator" :aria-label="`Loading ${loadingFonts.length} fonts`">
      <div class="loading-dots">
        <div class="loading-dot" v-for="n in 3" :key="n"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

// Types
import type { FontCustomization } from '@/types/components'

// Props
interface Props {
  modelValue: FontCustomization
  label?: string
  showCategories?: boolean
  disabled?: boolean
  type?: 'heading' | 'body' | 'display' | 'accent'
  previewText?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Font',
  showCategories: true,
  disabled: false,
  type: 'body',
  previewText: 'The quick brown fox jumps over the lazy dog'
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: FontCustomization]
  'fontChanged': [font: FontCustomization, validation?: any]
}>()

// Reactive data
const isOpen = ref(false)
const activeFontSource = ref<'google' | 'system' | 'custom'>('google')
const searchQuery = ref('')
const categoryFilter = ref('')
const selectedFont = ref<any>(null)
const selectedFontWeight = ref(400)
const fontSize = ref(16)
const previewText = ref(props.previewText)
const currentFont = ref<FontCustomization>(props.modelValue)
const originalFont = ref<FontCustomization>(props.modelValue)
const fonts = ref<any[]>([])
const fontsLoading = ref(false)
const loadingFonts = ref<string[]>([])
const fontLoadProgress = ref<Record<string, number>>({})

// Font sources configuration
const fontSources = [
  { id: 'google' as const, label: 'Google Fonts' },
  { id: 'system' as const, label: 'System Fonts' },
  { id: 'custom' as const, label: 'Custom Fonts' }
]

// Font categories
const categories = ['serif', 'sans-serif', 'display', 'monospace', 'handwriting']

// Preview text options
const previewOptions = [
  'Aa',
  'Hello World',
  'The quick brown fox',
  'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
  'abcdefghijklmnopqrstuvwxyz',
  'The quick brown fox jumps over the lazy dog'
]

// Available font weights
const availableWeights = [100, 200, 300, 400, 500, 600, 700, 800, 900]

// Computed properties
const hasChanged = computed(() => {
  return JSON.stringify(currentFont.value) !== JSON.stringify(originalFont.value)
})

const filteredFonts = computed(() => {
  let filtered = fonts.value

  // Filter by search query
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(font =>
      font.family.toLowerCase().includes(query)
    )
  }

  // Filter by category
  if (categoryFilter.value && activeFontSource.value === 'google') {
    filtered = filtered.filter(font => font.category === categoryFilter.value)
  }

  // Sort by relevance
  if (searchQuery.value.trim()) {
    filtered.sort((a, b) => {
      const aExact = a.family.toLowerCase().startsWith(searchQuery.value.toLowerCase()) ? 1 : 0
      const bExact = b.family.toLowerCase().startsWith(searchQuery.value.toLowerCase()) ? 1 : 0

      if (aExact !== bExact) {
        return bExact - aExact
      }

      const aRank = a.family.toLowerCase().includes(searchQuery.value.toLowerCase()) ? 1 : 0
      const bRank = b.family.toLowerCase().includes(searchQuery.value.toLowerCase()) ? 1 : 0

      if (aRank !== bRank) {
        return bRank - aRank
      }

      return a.family.localeCompare(b.family)
    })
  } else {
    filtered.sort((a, b) => a.family.localeCompare(b.family))
  }

  return filtered
})

const paginatedFonts = computed(() => {
  // For simplicity, showing all filtered fonts
  // In a real implementation, you might want to add pagination
  return filteredFonts.value
})

const hasFontLoading = computed(() => loadingFonts.value.length > 0)

const isSelectedFont = (font: any) => {
  return selectedFont.value && selectedFont.value.family === font.family
}

// Methods
const toggleSelector = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value

  if (isOpen.value && fonts.value.length === 0) {
    loadFonts()
  }
}

const selectFont = (font: any) => {
  selectedFont.value = font

  // Set default weight if available
  if (font.weights && font.weights.length > 0) {
    selectedFontWeight.value = font.weights[0]
  } else {
    selectedFontWeight.value = 400
  }

  // Create font object
  const fontData: FontCustomization = {
    type: props.type,
    family: font.family,
    weight: selectedFontWeight.value,
    size: currentFont.value.size,
    lineHeight: currentFont.value.lineHeight,
    letterSpacing: currentFont.value.letterSpacing,
    googleFontsUrl: font.source === 'google' ? font.url : undefined,
    fallbacks: font.fallbacks || getFallbackFonts(font.category),
    isVariableFont: font.variable || false,
    source: font.source || activeFontSource.value
  }

  currentFont.value = fontData

  // Load font if needed
  if (font.source === 'google' && font.url) {
    loadGoogleFont(font)
  }

  emitFontChange()
}

const loadFonts = async () => {
  fontsLoading.value = true

  try {
    switch (activeFontSource.value) {
      case 'google':
        await loadGoogleFonts()
        break
      case 'system':
        fonts.value = loadSystemFonts()
        break
      case 'custom':
        fonts.value = loadCustomFonts()
        break
    }
  } catch (error) {
    console.error('Failed to load fonts:', error)
  } finally {
    fontsLoading.value = false
  }
}

const loadGoogleFonts = async () => {
  try {
    // In a real implementation, fetch from Google Fonts API
    // For now, using mock data
    fonts.value = mockGoogleFonts.map(font => ({
      ...font,
      source: 'google'
    }))
  } catch (error) {
    console.error('Failed to load Google Fonts:', error)
  }
}

const loadSystemFonts = () => {
  // System fonts available on most platforms
  return [
    {
      family: 'Arial',
      weights: [400, 700],
      category: 'sans-serif',
      source: 'system',
      fallbacks: ['Helvetica', 'sans-serif']
    },
    {
      family: 'Times New Roman',
      weights: [400, 700],
      category: 'serif',
      source: 'system',
      fallbacks: ['Times', 'serif']
    },
    {
      family: 'Courier New',
      weights: [400, 700],
      category: 'monospace',
      source: 'system',
      fallbacks: ['Courier', 'monospace']
    }
  ]
}

const loadCustomFonts = () => {
  // Load custom fonts from local storage or API
  // For now, return empty array
  return []
}

const loadGoogleFont = async (font: any) => {
  loadingFonts.value.push(font.family)
  fontLoadProgress.value[font.family] = 0

  try {
    // Simulate font loading
    for (let progress = 0; progress <= 100; progress += 10) {
      await new Promise(resolve => setTimeout(resolve, 50))
      fontLoadProgress.value[font.family] = progress
    }

    // In a real implementation, load the font using WebFontLoader or similar
    const link = document.createElement('link')
    link.href = font.url
    link.rel = 'stylesheet'
    document.head.appendChild(link)

  } catch (error) {
    console.error('Failed to load font:', error)
  } finally {
    loadingFonts.value = loadingFonts.value.filter(f => f !== font.family)
    delete fontLoadProgress.value[font.family]
  }
}

const getFallbackFonts = (category?: string) => {
  const fallbacks = {
    'serif': ['Times', 'serif'],
    'sans-serif': ['Arial', 'Helvetica', 'sans-serif'],
    'monospace': ['Courier', 'monospace'],
    'display': ['Arial', 'sans-serif'],
    'handwriting': ['serif']
  }

  return fallbacks[category as keyof typeof fallbacks] || ['sans-serif']
}

const updateFontWeight = () => {
  if (selectedFont.value) {
    currentFont.value.weight = selectedFontWeight.value
    emitFontChange()
  }
}

const updateFontSize = () => {
  currentFont.value.size = fontSize.value
  emitFontChange()
}

const resetToOriginal = () => {
  currentFont.value = JSON.parse(JSON.stringify(originalFont.value))
  updateFromCurrent()
  emitFontChange()
}

const applyFont = () => {
  originalFont.value = JSON.parse(JSON.stringify(currentFont.value))
  emit('update:modelValue', currentFont.value)
  emit('fontChanged', currentFont.value, getFontValidation())
  isOpen.value = false
}

const cancel = () => {
  currentFont.value = JSON.parse(JSON.stringify(originalFont.value))
  updateFromCurrent()
  isOpen.value = false
}

const clearSearch = () => {
  searchQuery.value = ''
}

const updateFromCurrent = () => {
  fontSize.value = currentFont.value.size
  selectedFontWeight.value = currentFont.value.weight

  // Find selected font in current list
  const font = fonts.value.find(f => f.family === currentFont.value.family)
  if (font) {
    selectedFont.value = font
  }
}

const emitFontChange = () => {
  emit('fontChanged', currentFont.value, getFontValidation())
}

const getFontValidation = () => {
  return {
    readable: fontSize.value >= 14,
    contrast: true, // Placeholder for contrast validation
    loaded: !hasFontLoading.value,
    available: true // Placeholder for font availability check
  }
}

const getWeightLabel = (weight: number) => {
  const labels = {
    100: 'Thin (100)',
    200: 'Extra Light (200)',
    300: 'Light (300)',
    400: 'Regular (400)',
    500: 'Medium (500)',
    600: 'Semi Bold (600)',
    700: 'Bold (700)',
    800: 'Extra Bold (800)',
    900: 'Black (900)'
  }
  return labels[weight as keyof typeof labels] || `${weight}`
}

// Mock Google Fonts data (in a real app, this would come from the Google Fonts API)
const mockGoogleFonts = [
  {
    family: ' Roboto',
    variants: ['100', '100i', '300', '300i', '400', '400i', '500', '500i', '700', '700i', '900', '900i'],
    url: 'https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap',
    category: 'sans-serif',
    weights: [100, 300, 400, 500, 700, 900],
    variable: true
  },
  {
    family: 'Open Sans',
    variants: ['300', '400', '500', '600', '700', '800'],
    url: 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap',
    category: 'sans-serif',
    weights: [300, 400, 500, 600, 700, 800],
    variable: false
  },
  // Add more mock fonts as needed
]

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (JSON.stringify(newValue) !== JSON.stringify(currentFont.value)) {
    currentFont.value = newValue
    originalFont.value = newValue
    updateFromCurrent()
  }
})

watch(activeFontSource, () => {
  if (isOpen.value) {
    loadFonts()
  }
})

watch([searchQuery, categoryFilter], () => {
  // Search/filter will automatically update via computed properties
})

// Lifecycle
onMounted(() => {
  updateFromCurrent()
})
</script>

<style scoped>
.font-selector {
  @apply relative inline-block;
}

.font-selector--disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Font Input Group */
.font-input-group {
  @apply flex items-center gap-2;
}

.font-preview {
  @apply flex items-center gap-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg;
  @apply bg-white dark:bg-gray-700 cursor-pointer transition-colors;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-1;
}

.font-preview:hover {
  @apply border-gray-400 dark:border-gray-500;
}

.font-sample {
  @apply w-8 h-8 rounded bg-gray-100 dark:bg-gray-600 flex items-center justify-center text-lg;
  @apply font-bold text-gray-900 dark:text-gray-100 leading-none;
}

.font-info {
  @apply flex flex-col min-w-0 flex-1;
}

.font-name {
  @apply text-sm font-medium text-gray-900 dark:text-gray-100 truncate;
}

.font-weight {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.font-toggle {
  @apply transition-transform duration-200;
}

.font-toggle.is-open {
  @apply transform rotate-180;
}

/* Font Selector Panel */
.font-selector-panel {
  @apply absolute top-full left-0 mt-2 w-96 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl;
  @apply shadow-xl z-50 p-4;
}

/* Font Source Tabs */
.font-source-tabs {
  @apply flex border-b border-gray-200 dark:border-gray-700 mb-4;
}

.source-tab {
  @apply flex-1 px-3 py-2 text-sm font-medium rounded-t-lg transition-colors;
  @apply text-gray-600 dark:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
}

.source-tab.active {
  @apply bg-blue-500 text-white;
}

/* Font Search */
.font-search {
  @apply relative mb-4;
}

.font-search-input {
  @apply w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600;
  @apply bg-white dark:bg-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none pr-8;
}

.clear-search-btn {
  @apply absolute right-2 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
  @apply rounded focus:ring-2 focus:ring-blue-500 focus:outline-none;
}

/* Font Filters */
.font-filters {
  @apply mb-4;
}

.filter-group {
  @apply flex items-center gap-2;
}

.filter-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.filter-select {
  @apply px-3 py-1 text-sm border border-gray-300 dark:border-gray-600;
  @apply bg-white dark:bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
}

/* Font List */
.font-list {
  @apply max-h-64 overflow-y-auto mb-4;
}

.font-loading,
.no-fonts {
  @apply flex flex-col items-center justify-center py-8 text-center;
}

.loading-spinner {
  @apply w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mb-3;
}

.no-fonts-icon {
  @apply w-8 h-8 text-gray-400 mb-3;
}

.no-fonts-title {
  @apply text-sm font-medium text-gray-900 dark:text-gray-100 mb-1;
}

.no-fonts-message {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.font-items {
  @apply space-y-1;
}

.font-item {
  @apply w-full px-3 py-3 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors;
  @apply bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
  @apply focus:outline-none text-left;
}

.font-item.selected {
  @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.font-sample {
  @apply text-lg mb-1 leading-tight text-gray-900 dark:text-gray-100;
}

.font-details {
  @apply flex items-center justify-between;
}

.font-name {
  @apply text-sm font-medium text-gray-900 dark:text-gray-100;
}

.font-meta {
  @apply flex items-center gap-2;
}

.font-category,
.font-source {
  @apply text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400;
}

/* Font Controls */
.font-controls {
  @apply space-y-3 mb-4 border-t border-gray-200 dark:border-gray-700 pt-4;
}

.control-group {
  @apply flex items-center gap-2;
}

.control-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 w-16;
}

.font-weight-select,
.font-size-input {
  @apply px-2 py-1 text-sm border border-gray-300 dark:border-gray-600;
  @apply bg-white dark:bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
}

.font-size-slider {
  @apply flex-1;
}

/* Preview Options */
.preview-options {
  @apply flex flex-wrap gap-1;
}

.preview-option {
  @apply px-2 py-1 text-xs rounded transition-colors;
  @apply bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-inset focus:outline-none;
}

.preview-option.active {
  @apply bg-blue-500 text-white;
}

/* Font Loading Status */
.font-loading-status {
  @apply mb-4;
}

.loading-item {
  @apply flex items-center gap-2 mb-2;
}

.loading-item span {
  @apply text-xs text-gray-600 dark:text-gray-400 flex-1;
}

.loading-bar {
  @apply flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1 overflow-hidden;
}

.loading-progress {
  @apply bg-blue-600 h-full rounded-full transition-all duration-300;
}

/* Action Buttons */
.action-buttons {
  @apply flex items-center gap-2 pt-4 border-t border-gray-200 dark:border-gray-700;
}

.action-btn {
  @apply flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-colors;
  @apply focus:ring-2 focus:ring-offset-1 focus:outline-none;
}

.action-btn--primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.action-btn--secondary {
  @apply bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600;
}

.action-btn--ghost {
  @apply bg-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700;
}

/* Font Loading Indicator */
.font-loading-indicator {
  @apply absolute top-2 right-2;
}

.loading-dots {
  @apply flex items-center gap-1;
}

.loading-dot {
  @apply w-2 h-2 bg-blue-600 rounded-full animate-pulse;
}

.loading-dot:nth-child(2) {
  @apply animation-delay-100;
}

.loading-dot:nth-child(3) {
  @apply animation-delay-200;
}

@keyframes pulse {
  0%, 80%, 100% {
    opacity: 0.3;
  }
  40% {
    opacity: 1;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .font-selector-panel {
    @apply bg-gray-800 border-gray-700;
  }

  .font-preview {
    @apply bg-gray-800 border-gray-600;
  }

  .font-sample {
    @apply bg-gray-600 text-gray-100;
  }

  .font-item {
    @apply bg-gray-800 border-gray-600 hover:bg-gray-700;
  }

  .font-item.selected {
    @apply border-blue-500 bg-blue-900/20;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .font-toggle,
  .loading-spinner,
  .loading-progress,
  .action-btn {
    @apply transition-none;
  }

  .loading-dot {
    @apply animate-none;
  }

  .font-preview:hover,
  .font-item:hover {
    @apply transform-none;
  }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .font-selector-panel {
    @apply w-80;
  }

  .font-list {
    @apply max-h-48;
  }

  .control-label {
    @apply w-20;
  }

  .action-buttons {
    @apply flex-col;
  }

  .action-btn {
    @apply w-full;
  }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .font-item {
    @apply border-2;
  }

  .font-item.selected {
    @apply border-blue-500 bg-blue-100 dark:bg-blue-900;
  }
}
</style>