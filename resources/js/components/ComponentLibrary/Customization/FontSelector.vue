<template>
  <div class="font-selector" role="region" aria-labelledby="font-selector-label">
    <!-- Header -->
    <div id="font-selector-label" class="sr-only">Font Selector</div>

    <!-- Current Font Display -->
    <div class="font-current" v-if="selectedFont">
      <div class="font-preview" :style="{ fontFamily }">
        <span class="font-sample">AaBbCcDd</span>
      </div>
      <div class="font-details">
        <div class="font-name">{{ selectedFont.name }}</div>
        <div class="font-family">{{ fontFamily }}</div>
      </div>
      <button
        @click="toggleFontSelector"
        @keydown.space.prevent="toggleFontSelector"
        @keydown.enter.prevent="toggleFontSelector"
        class="font-selector-toggle"
        :aria-label="`Change current font ${selectedFont.name}`"
        :aria-expanded="isFontSelectorOpen"
        :aria-controls="isFontSelectorOpen ? 'font-selector-modal' : undefined"
      >
        Change Font
      </button>
    </div>

    <!-- Font Selector Modal -->
    <Transition name="font-selector-fade">
      <div
        v-if="isFontSelectorOpen"
        id="font-selector-modal"
        class="font-selector-modal"
        role="dialog"
        aria-modal="true"
        :aria-label="`Font selection dialog for ${selectedFont?.name || 'current selection'}`"
      >
        <div class="font-selector-overlay" @click="closeFontSelector"></div>
        <div class="font-selector-content" ref="modalContent">
          <!-- Header -->
          <div class="font-selector-header">
            <h2 class="font-selector-title">Select Font</h2>
            <button
              @click="closeFontSelector"
              class="font-selector-close"
              aria-label="Close font selector"
            >
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <!-- Search -->
          <div class="font-search-section">
            <div class="font-search-wrapper">
              <input
                v-model="fontSearchQuery"
                type="search"
                class="font-search-input"
                placeholder="Search fonts..."
                @input="debounceSearch"
                aria-label="Search font families"
                id="font-search-input"
              />
              <label for="font-search-input" class="sr-only">Search font families</label>
            </div>

            <!-- Font Filters -->
            <div class="font-filters">
              <div class="filter-tabs">
                <button
                  v-for="source in fontSources"
                  :key="source.id"
                  @click="activeSource = source.id"
                  :class="{ active: activeSource === source.id }"
                  class="filter-tab"
                  :aria-label="`Filter fonts by ${source.label} source`"
                >
                  {{ source.label }}
                </button>
              </div>

              <div class="filter-category" v-if="showCategoryFilter">
                <select
                  v-model="selectedCategory"
                  class="font-category-select"
                  aria-label="Filter fonts by category"
                >
                  <option value="">All Categories</option>
                  <option v-for="category in availableCategories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Font List -->
          <div class="font-list-container">
            <div class="font-list" ref="fontList" role="list">
              <div
                v-for="font in filteredFonts"
                :key="font.id"
                class="font-item"
                :class="{ selected: isFontSelected(font), loading: isLoadingFont(font) }"
                @click="selectFont(font)"
                role="listitem"
                tabindex="0"
                :aria-label="`Select ${font.name} font`"
                :aria-selected="isFontSelected(font)"
                @keydown.enter="selectFont(font)"
                @keydown.space.prevent="selectFont(font)"
              >
                <div class="font-preview-area" :style="{ fontFamily: getFontFamilyString(font) }">
                  <div class="font-preview-text">
                    <div class="font-title">{{ font.name }}</div>
                    <div class="font-sample">
                      The quick brown fox jumps over the lazy dog
                    </div>
                  </div>
                  <div class="font-meta">
                    <span class="font-category-badge" v-if="font.category">{{ font.category }}</span>
                    <span class="font-source-badge">{{ getSourceLabel(font.source) }}</span>
                  </div>
                </div>

                <!-- Font Variants -->
                <div class="font-variants" v-if="font.weights && font.weights.length > 1">
                  <div class="variant-selector">
                    <label class="variant-label">Weight:</label>
                    <select
                      :value="selectedWeight[font.id] || font.weights[0]"
                      @change="updateFontWeight(font, $event)"
                      @click.stop
                      class="weight-select"
                      :aria-label="`Select weight for ${font.name}`"
                    >
                      <option v-for="weight in font.weights" :key="weight" :value="weight">
                        {{ weight }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="font-actions">
                  <button
                    @click.stop="previewFont(font)"
                    class="preview-btn"
                    :aria-label="`Preview ${font.name} font in larger context`"
                  >
                    Preview
                  </button>
                  <button
                    @click.stop="favoriteFont(font)"
                    class="favorite-btn"
                    :class="{ favorited: isFavoritedFont(font) }"
                    :aria-label="isFavoritedFont(font) ? `Remove ${font.name} from favorites` : `Add ${font.name} to favorites`"
                  >
                    ★
                  </button>
                </div>
              </div>
            </div>

            <!-- Loading state -->
            <div class="font-loading" v-if="isLoadingFonts">
              <div class="loading-spinner"></div>
              <span>Loading fonts...</span>
            </div>

            <!-- Empty state -->
            <div class="font-empty" v-if="!isLoadingFonts && filteredFonts.length === 0">
              <div class="empty-icon">🔍</div>
              <h3>No fonts found</h3>
              <p>Try adjusting your search or filter criteria.</p>
            </div>
          </div>

          <!-- Font Preview Panel -->
          <div class="font-preview-panel" v-if="previewFontData">
            <div class="preview-header">
              <h3>Font Preview: {{ previewFontData.name }}</h3>
              <button @click="closeFontPreview" class="preview-close" aria-label="Close font preview">&times;</button>
            </div>
            <div class="preview-content" :style="{ fontFamily: getFontFamilyString(previewFontData) }">
              <div class="preview-size h1">AaBbCc</div>
              <div class="preview-size h2">The quick brown fox</div>
              <div class="preview-size h3">Jumps over the lazy dog</div>
              <div class="preview-size body">This is how your regular text will look with this font family.</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="font-selector-actions">
            <div class="font-info">
              <span v-if="selectedFont">
                Selected: <strong>{{ selectedFont.name }}</strong>
                ({{ getSourceLabel(selectedFont.source) }})
              </span>
            </div>
            <div class="action-buttons">
              <button @click="closeFontSelector" class="btn-secondary">Cancel</button>
              <button
                @click="confirmFontSelection"
                class="btn-primary"
                :disabled="!selectedFont"
              >
                Apply Font
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Font Preview Modal -->
    <div v-if="previewModalOpen" class="font-preview-modal" role="dialog" aria-modal="true" aria-labelledby="preview-title">
      <div class="font-preview-overlay" @click="closeFontPreviewModal"></div>
      <div class="font-preview-container">
        <div class="font-preview-header">
          <h3 id="preview-title">Font Preview</h3>
          <button @click="closeFontPreviewModal" class="preview-modal-close">×</button>
        </div>
        <div class="font-preview-body">
          <!-- Implementation for full preview modal -->
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { debounce } from 'lodash-es'

// Font types
interface FontFamily {
  id: string
  name: string
  family: string
  source: 'google' | 'system' | 'custom' | 'adobe'
  category: string
  weights: number[]
  styles?: string[]
  unicodeRange?: string[]
  isVariableFont?: boolean
  url?: string
  fallbacks?: string[]
  loading?: boolean
}

interface Props {
  modelValue?: string
  defaultFont?: FontFamily
  allowedSources?: string[]
  includeGoogleFonts?: boolean
  includeSystemFonts?: boolean
  showCategoryFilter?: boolean
  maxFontSize?: number
}

interface Emits {
  'update:modelValue': [value: string]
  'font-selected': [font: FontFamily]
  'cancel': []
}

const props = withDefaults(defineProps<Props>(), {
  allowedSources: () => ['google', 'system', 'custom'],
  includeGoogleFonts: true,
  includeSystemFonts: true,
  showCategoryFilter: false,
  maxFontSize: 48
})

const emit = defineEmits<Emits>()

// Reactive state
const isFontSelectorOpen = ref(false)
const modalContent = ref<HTMLElement>()
const fontList = ref<HTMLElement>()

const selectedFont = ref<FontFamily | null>(props.defaultFont || null)
const previewFontData = ref<FontFamily | null>(null)
const previewModalOpen = ref(false)

const activeSource = ref<string>('all')
const selectedCategory = ref<string>('')
const fontSearchQuery = ref('')
const isLoadingFonts = ref(false)

// Font collections
const googleFonts = ref<FontFamily[]>([])
const systemFonts = ref<FontFamily[]>([])
const customFonts = ref<FontFamily[]>([])
const favoriteFonts = ref<string[]>([])
const selectedWeight = ref<Record<string, number>>({})

// Font sources configuration
const fontSources = [
  { id: 'all', label: 'All Fonts' },
  { id: 'google', label: 'Google Fonts' },
  { id: 'system', label: 'System Fonts' },
  { id: 'custom', label: 'Custom Fonts' }
]

// Computed properties
const fontFamily = computed(() => {
  if (!selectedFont.value) return 'inherit'
  const font = selectedFont.value
  const weight = selectedWeight.value[font.id] || font.weights[0]
  return `${font.family}${weight && weight !== 400 ? ':' + weight : ''}`
})

const availableCategories = computed(() => {
  const allFonts = [...googleFonts.value, ...systemFonts.value, ...customFonts.value]
  return [...new Set(allFonts.map(f => f.category).filter(Boolean))]
})

const allFonts = computed(() => {
  return {
    google: googleFonts.value,
    system: systemFonts.value,
    custom: customFonts.value
  }
})

const filteredFonts = computed(() => {
  let fonts: FontFamily[] = []

  // Filter by source
  switch (activeSource.value) {
    case 'google':
      fonts = googleFonts.value
      break
    case 'system':
      fonts = systemFonts.value
      break
    case 'custom':
      fonts = customFonts.value
      break
    default:
      fonts = [...googleFonts.value, ...systemFonts.value, ...customFonts.value]
  }

  // Filter by search query
  if (fontSearchQuery.value) {
    const query = fontSearchQuery.value.toLowerCase()
    fonts = fonts.filter(font =>
      font.name.toLowerCase().includes(query) ||
      font.category?.toLowerCase().includes(query) ||
      font.family.toLowerCase().includes(query)
    )
  }

  // Filter by category
  if (selectedCategory.value) {
    fonts = fonts.filter(font => font.category === selectedCategory.value)
  }

  return fonts
})

// Methods
const toggleFontSelector = () => {
  if (isFontSelectorOpen.value) {
    closeFontSelector()
  } else {
    openFontSelector()
  }
}

const openFontSelector = () => {
  isFontSelectorOpen.value = true
  nextTick(() => {
    focusModalContent()
    loadFontsIfNeeded()
  })
}

const closeFontSelector = () => {
  isFontSelectorOpen.value = false
  emit('cancel')
}

const focusModalContent = () => {
  if (modalContent.value) {
    modalContent.value.focus()
  }
}

const loadFontsIfNeeded = async () => {
  if (props.includeGoogleFonts && googleFonts.value.length === 0) {
    await loadGoogleFonts()
  }
  if (props.includeSystemFonts && systemFonts.value.length === 0) {
    loadSystemFonts()
  }
}

const loadGoogleFonts = async () => {
  try {
    isLoadingFonts.value = true

    // In a real implementation, this would fetch from Google Fonts API
    // For now, we'll use a mock implementation
    const mockGoogleFonts: FontFamily[] = [
      {
        id: 'roboto',
        name: 'Roboto',
        family: 'Roboto',
        source: 'google',
        category: 'sans-serif',
        weights: [100, 300, 400, 500, 700, 900],
        styles: ['normal', 'italic'],
        loading: false
      },
      {
        id: 'open-sans',
        name: 'Open Sans',
        family: 'Open Sans',
        source: 'google',
        category: 'sans-serif',
        weights: [300, 400, 600, 700, 800],
        styles: ['normal', 'italic'],
        loading: false
      },
      {
        id: 'lato',
        name: 'Lato',
        family: 'Lato',
        source: 'google',
        category: 'sans-serif',
        weights: [100, 300, 400, 700, 900],
        styles: ['normal', 'italic'],
        loading: false
      },
      {
        id: 'playfair-display',
        name: 'Playfair Display',
        family: 'Playfair Display',
        source: 'google',
        category: 'serif',
        weights: [400, 500, 600, 700, 800, 900],
        styles: ['normal', 'italic'],
        loading: false
      },
      {
        id: 'source-sans-pro',
        name: 'Source Sans Pro',
        family: 'Source Sans Pro',
        source: 'google',
        category: 'sans-serif',
        weights: [200, 300, 400, 600, 700, 900],
        styles: ['normal', 'italic'],
        loading: false
      }
    ]

    googleFonts.value = mockGoogleFonts
  } catch (error) {
    console.error('Failed to load Google fonts:', error)
  } finally {
    isLoadingFonts.value = false
  }
}

const loadSystemFonts = () => {
  // System fonts available on most operating systems
  const systemFontsList: FontFamily[] = [
    {
      id: 'system-ui',
      name: 'System UI',
      family: 'system-ui',
      source: 'system',
      category: 'sans-serif',
      weights: [400, 600, 700],
      loading: false
    },
    {
      id: 'arial',
      name: 'Arial',
      family: 'Arial',
      source: 'system',
      category: 'sans-serif',
      weights: [400, 700],
      loading: false
    },
    {
      id: 'helvetica',
      name: 'Helvetica',
      family: 'Helvetica',
      source: 'system',
      category: 'sans-serif',
      weights: [400, 700],
      loading: false
    },
    {
      id: 'times-new-roman',
      name: 'Times New Roman',
      family: 'Times New Roman',
      source: 'system',
      category: 'serif',
      weights: [400, 700],
      loading: false
    },
    {
      id: 'georgia',
      name: 'Georgia',
      family: 'Georgia',
      source: 'system',
      category: 'serif',
      weights: [400, 700],
      loading: false
    },
    {
      id: 'verdana',
      name: 'Verdana',
      family: 'Verdana',
      source: 'system',
      category: 'sans-serif',
      weights: [400, 700],
      loading: false
    }
  ]

  systemFonts.value = systemFontsList
}

const getSourceLabel = (source: FontFamily['source']): string => {
  return fontSources.find(s => s.id === source)?.label || source
}

const isFontSelected = (font: FontFamily): boolean => {
  return selectedFont.value?.id === font.id
}

const isLoadingFont = (font: FontFamily): boolean => {
  return font.loading || false
}

const isFavoritedFont = (font: FontFamily): boolean => {
  return favoriteFonts.value.includes(font.id)
}

const selectFont = (font: FontFamily) => {
  selectedFont.value = font

  // Set default weight if not already set
  if (!selectedWeight.value[font.id] && font.weights.length > 0) {
    selectedWeight.value[font.id] = font.weights[0]
  }
}

const updateFontWeight = (font: FontFamily, event: Event) => {
  const target = event.target as HTMLSelectElement
  const weight = parseInt(target.value)
  selectedWeight.value[font.id] = weight
}

const favoriteFont = (font: FontFamily) => {
  const index = favoriteFonts.value.indexOf(font.id)
  if (index > -1) {
    favoriteFonts.value.splice(index, 1)
  } else {
    favoriteFonts.value.push(font.id)
  }
}

const previewFont = (font: FontFamily) => {
  previewFontData.value = font
}

const closeFontPreview = () => {
  previewFontData.value = null
}

const closeFontPreviewModal = () => {
  previewModalOpen.value = false
}

const confirmFontSelection = () => {
  if (!selectedFont.value) return

  const fontValue = getFontFamilyString(selectedFont.value)
  emit('update:modelValue', fontValue)
  emit('font-selected', selectedFont.value)
  closeFontSelector()
}

const getFontFamilyString = (font: FontFamily): string => {
  let familyStr = font.family

  if (selectedWeight.value[font.id] && selectedWeight.value[font.id] !== 400) {
    // For variable fonts or font-weight specification
    familyStr += `;font-weight:${selectedWeight.value[font.id]}`
  }

  if (font.fallbacks && font.fallbacks.length > 0) {
    familyStr += ',' + font.fallbacks.join(',')
  }

  return familyStr
}

// Debounced search
let searchTimeout: NodeJS.Timeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // Search logic handled by computed property
  }, 300)
}

// Lifecycle
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    parseFontValue(newValue)
  }
})

const parseFontValue = (value: string) => {
  // Basic parsing of font-family value
  // In a real implementation, this would be more sophisticated
  const families = value.split(',')
  if (families.length > 0) {
    const primaryFamily = families[0].trim()
    // Find matching font from loaded fonts
    const allLoadedFonts = [...googleFonts.value, ...systemFonts.value, ...customFonts.value]
    const matchingFont = allLoadedFonts.find(f => f.family === primaryFamily || f.name === primaryFamily)
    if (matchingFont) {
      selectedFont.value = matchingFont
    }
  }
}

// Focus management
onMounted(() => {
  if (props.includeSystemFonts) {
    loadSystemFonts()
  }

  if (props.modelValue) {
    parseFontValue(props.modelValue)
  }
})

onBeforeUnmount(() => {
  clearTimeout(searchTimeout)
})

// Keyboard navigation support
const handleKeyDown = (event: KeyboardEvent) => {
  if (!isFontSelectorOpen.value) return

  switch (event.key) {
    case 'Escape':
      closeFontSelector()
      break
    case 'ArrowDown':
      if (!event.target || !(event.target as HTMLElement).classList.contains('font-item')) {
        const firstItem = fontList.value?.querySelector('.font-item') as HTMLElement
        firstItem?.focus()
      }
      event.preventDefault()
      break
  }
}

// Add keyboard event listener
onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.font-selector {
  @apply relative;
}

/* Current Font Display */
.font-current {
  @apply flex items-center gap-4 p-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm;
}

.font-preview {
  @apply flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-md text-lg font-bold;
}

.font-sample {
  @apply font-bold;
}

.font-details {
  @apply flex-1;
}

.font-name {
  @apply font-semibold text-gray-900 dark:text-white;
}

.font-family {
  @apply text-sm text-gray-500 dark:text-gray-400 font-mono;
}

.font-selector-toggle {
  @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

/* Modal */
.font-selector-fade-enter-active,
.font-selector-fade-leave-active {
  @apply transition-all duration-200 ease-out;
}

.font-selector-fade-enter-from,
.font-selector-fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

.font-selector-modal {
  @apply fixed inset-0 z-50 flex items-center justify-center p-4;
}

.font-selector-overlay {
  @apply absolute inset-0 bg-black/50;
}

.font-selector-content {
  @apply relative max-w-4xl max-h-[90vh] bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden w-full;
}

.font-selector-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.font-selector-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.font-selector-close {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-400 hover:text-gray-600;
}

/* Search Section */
.font-search-section {
  @apply p-6 border-b border-gray-200 dark:border-gray-700;
}

.font-search-wrapper {
  @apply mb-4;
}

.font-search-input {
  @apply w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500;
}

.font-filters {
  @apply space-y-4;
}

.filter-tabs {
  @apply flex gap-2 flex-wrap;
}

.filter-tab {
  @apply px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.filter-tab.active {
  @apply bg-blue-600 border-blue-600 text-white;
}

.font-category-select {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500;
}

/* Font List */
.font-list-container {
  @apply max-h-96 overflow-y-auto;
}

.font-list {
  @apply divide-y divide-gray-200 dark:divide-gray-700;
}

.font-item {
  @apply p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500;
}

.font-item.selected {
  @apply bg-blue-50 dark:bg-blue-900/20 border-r-4 border-blue-500;
}

.font-item.loading {
  @apply opacity-50 pointer-events-none;
}

.font-preview-area {
  @apply mb-3;
}

.font-preview-text {
  @apply mb-2;
}

.font-title {
  @apply text-sm font-medium text-gray-900 dark:text-white mb-1;
}

.font-sample {
  @apply text-xs text-gray-500 dark:text-gray-400 leading-relaxed;
}

.font-meta {
  @apply flex gap-2;
}

.font-category-badge, .font-source-badge {
  @apply inline-block px-2 py-1 text-xs rounded-full;
}

.font-category-badge {
  @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.font-source-badge {
  @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200;
}

.font-variants {
  @apply mb-3;
}

.variant-selector {
  @apply flex items-center gap-2;
}

.variant-label {
  @apply text-xs text-gray-600 dark:text-gray-400;
}

.weight-select {
  @apply text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700;
}

.font-actions {
  @apply flex gap-2;
}

.preview-btn, .favorite-btn {
  @apply px-3 py-1 text-xs font-medium rounded border transition-colors;
}

.preview-btn {
  @apply border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600;
}

.favorite-btn {
  @apply border-yellow-300 dark:border-yellow-600 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900;
}

.favorite-btn.favorited {
  @apply bg-yellow-100 text-yellow-900 dark:bg-yellow-900 dark:text-yellow-200;
}

/* Preview Panel */
.font-preview-panel {
  @apply border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700;
}

.preview-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.preview-content {
  @apply p-4 space-y-3;
}

.preview-size {
  @apply leading-tight;
}

.preview-size.h1 {
  @apply text-3xl font-bold;
}

.preview-size.h2 {
  @apply text-2xl font-semibold;
}

.preview-size.h3 {
  @apply text-xl font-medium;
}

.preview-size.body {
  @apply text-base;
}

.preview-close {
  @apply text-2xl text-gray-400 hover:text-gray-600;
}

/* Actions */
.font-selector-actions {
  @apply flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600;
}

.font-info {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.action-buttons {
  @apply flex gap-3;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
}

/* Empty States */
.font-loading, .font-empty {
  @apply flex flex-col items-center justify-center py-12 text-center;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4;
}

.empty-icon {
  @apply text-4xl mb-4;
}

/* Responsive Design */
@media (max-width: 640px) {
  .font-selector-content {
    @apply mx-4;
  }

  .font-current {
    @apply flex-col text-center gap-3;
  }

  .filter-tabs {
    @apply justify-center;
  }

  .font-actions {
    @apply justify-center;
  }

  .action-buttons {
    @apply flex-col w-full;
  }

  .btn-primary, .btn-secondary {
    @apply flex-1;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .font-selector-fade-enter-active,
  .font-selector-fade-leave-active {
    @apply transition-none;
  }
}

/* Focus styles */
.font-item:focus-visible {
  @apply outline-none;
}

.font-select:focus-visible {
  @apply outline-none ring-2 ring-blue-500;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .font-preview {
    @apply bg-gray-700;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .font-item.selected {
    @apply border-blue-400;
  }

  .filter-tab.active {
    @apply border-blue-400 bg-blue-500;
  }
}

/* Print styles */
@media print {
  .font-selector-modal {
    @apply hidden;
  }
}
</style>