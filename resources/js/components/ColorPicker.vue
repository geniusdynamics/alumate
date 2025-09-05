<template>
  <div class="color-picker" :class="{ 'color-picker--disabled': disabled }">
    <!-- Color Input Group -->
    <div class="color-input-group">
      <!-- Color Preview -->
      <div class="color-preview" @click="togglePicker" :tabindex="disabled ? -1 : 0" role="button" :aria-label="`Select ${label} color`" :aria-disabled="disabled" :aria-expanded="isOpen">
        <div class="color-swatch" :style="{ backgroundColor: currentColor }" :title="currentColor"></div>
        <div class="color-value">{{ currentColor }}</div>
        <div class="color-toggle" :class="{ 'is-open': isOpen }">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      <!-- Quick Color Presets -->
      <div class="color-presets" v-if="showPresets">
        <button
          v-for="preset in defaultColors"
          :key="preset"
          @click="selectColor(preset)"
          class="color-preset"
          :class="{ 'selected': preset === currentColor }"
          :title="preset"
          type="button"
          :aria-label="`Select ${preset} color`"
        >
          <div class="preset-swatch" :style="{ backgroundColor: preset }"></div>
        </button>
      </div>
    </div>

    <!-- Color Picker Panel -->
    <div v-if="isOpen" class="color-picker-panel" @click.stop>
      <!-- Color Mode Tabs -->
      <div class="color-mode-tabs">
        <button
          v-for="mode in colorModes"
          :key="mode.value"
          @click="activeColorMode = mode.value"
          :class="['mode-tab', { 'active': activeColorMode === mode.value }]"
          type="button"
          :aria-pressed="activeColorMode === mode.value"
        >
          {{ mode.label }}
        </button>
      </div>

      <!-- Color Picker Canvas -->
      <div class="color-picker-canvas">
        <!-- HSV/SL Color Map -->
        <div v-if="activeColorMode === 'hsv' || activeColorMode === 'hsl'" class="color-map-container">
          <div class="color-map" @mousedown="startColorSelection" @mousemove="updateColorFromPosition" @mouseup="endColorSelection">
            <canvas
              ref="colorMapCanvas"
              class="color-map-canvas"
              width="256"
              height="256"
              :aria-label="`${activeColorMode.toUpperCase()} color selector`"
            ></canvas>
            <div class="color-map-indicator" :style="{ left: hueSatPosition.x + 'px', top: hueSatPosition.y + 'px' }"></div>
          </div>

          <!-- Hue Slider -->
          <div class="hue-slider">
            <canvas
              ref="hueSliderCanvas"
              class="hue-slider-canvas"
              width="20"
              height="256"
              :aria-label="`${activeColorMode} hue slider`"
            ></canvas>
            <div class="hue-slider-indicator" :style="{ top: huePosition.y + 'px' }"></div>
          </div>
        </div>

        <!-- RGB/SL Inputs -->
        <div v-else class="color-inputs">
          <div class="input-group">
            <label class="input-label" for="red-channel">Red</label>
            <input
              id="red-channel"
              v-model.number="rgb.r"
              type="range"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-slider"
              :aria-labelledby="`red-channel`"
            />
            <input
              v-model.number="rgb.r"
              type="number"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-number-input"
              :aria-label="`Red channel value, current ${rgb.r}`"
            />
          </div>

          <div class="input-group">
            <label class="input-label" for="green-channel">Green</label>
            <input
              id="green-channel"
              v-model.number="rgb.g"
              type="range"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-slider"
              :aria-labelledby="`green-channel`"
            />
            <input
              v-model.number="rgb.g"
              type="number"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-number-input"
              :aria-label="`Green channel value, current ${rgb.g}`"
            />
          </div>

          <div class="input-group">
            <label class="input-label" for="blue-channel">Blue</label>
            <input
              id="blue-channel"
              v-model.number="rgb.b"
              type="range"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-slider"
              :aria-labelledby="`blue-channel`"
            />
            <input
              v-model.number="rgb.b"
              type="number"
              min="0"
              max="255"
              step="1"
              @input="updateFromRgbInputs"
              class="color-number-input"
              :aria-label="`Blue channel value, current ${rgb.b}`"
            />
          </div>
        </div>
      </div>

      <!-- Hex Input -->
      <div class="hex-input-container">
        <label class="input-label" for="hex-input">Hex</label>
        <input
          id="hex-input"
          v-model="hexValue"
          type="text"
          @blur="updateFromHex"
          @keyup.enter="updateFromHex"
          class="hex-input"
          :aria-label="`Hex color value, current ${hexValue}`"
          placeholder="#000000"
        />
      </div>

      <!-- Color Format Selector -->
      <div class="color-format-selector">
        <button
          v-for="format in colorFormats"
          :key="format.value"
          @click="inputMode = format.value"
          :class="['format-btn', { 'active': inputMode === format.value }]"
          type="button"
          :aria-pressed="inputMode === format.value"
        >
          {{ format.label }}
        </button>
      </div>

      <!-- Recent Colors -->
      <div class="recent-colors" v-if="recentColors.length > 0">
        <label class="recent-label">Recent</label>
        <div class="recent-colors-list">
          <button
            v-for="(color, index) in recentColors"
            :key="index"
            @click="selectColor(color)"
            class="recent-color"
            :aria-label="`Select recent color ${color}`"
          >
            <div class="recent-swatch" :style="{ backgroundColor: color }"></div>
          </button>
        </div>
      </div>

      <!-- Accessibility Panel -->
      <div class="accessibility-panel" v-if="showAccessibility">
        <div class="accessibility-header">
          <h4 class="accessibility-title">Accessibility</h4>
          <button @click="showAccessibilityPanel = !showAccessibilityPanel" class="accessibility-toggle">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l-8 8m0-8l8 8" />
            </svg>
          </button>
        </div>

        <div class="contrast-checker">
          <div v-for="(result, index) in contrastResults" :key="index" class="contrast-result">
            <div class="contrast-against">
              <div class="contrast-swatch" :style="{ backgroundColor: result.backgroundColor }"></div>
              <span class="contrast-text">{{ result.backgroundColor }}</span>
            </div>
            <div class="contrast-ratio">
              <span :class="getContrastClass(result.level)">
                {{ result.ratio?.toFixed(2) }}:1
              </span>
              <span class="contrast-level">{{ result.level }}</span>
            </div>
          </div>
        </div>

        <div v-if="accessibilitySuggestions.length > 0" class="accessibility-suggestions">
          <h5>Suggestions</h5>
          <ul class="suggestions-list">
            <li v-for="(suggestion, index) in accessibilitySuggestions" :key="index" class="suggestion-item">
              {{ suggestion }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="resetToOriginal" class="action-btn action-btn--secondary" :disabled="!hasChanged" :aria-label="`Reset ${label} color to ${originalColor}`">
          Reset
        </button>
        <button @click="applyColor" class="action-btn action-btn--primary" :aria-label="`Apply ${currentColor} color`">
          Apply
        </button>
        <button @click="cancel" class="action-btn action-btn--ghost" aria-label="Cancel color selection">
          Cancel
        </button>
      </div>
    </div>

    <!-- Accessibility Toggle Icon -->
    <button
      v-if="isOpen && accessibilityEnabled"
      @click="toggleAccessibility"
      class="accessibility-icon"
      :aria-pressed="showAccessibility"
      :aria-label="`${showAccessibility ? 'Hide' : 'Show'} accessibility information`"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l2 2 2-2" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

// Types
export type ColorFormatType = 'hex' | 'rgb' | 'hsl'
export type ColorModeType = 'hsv' | 'hsl' | 'rgb'
export type ContrastLevel = 'AA' | 'AAA' | 'fail'

// Props
interface Props {
  modelValue: string
  label?: string
  showPresets?: boolean
  showAccessibility?: boolean
  disabled?: boolean
  type?: 'primary' | 'secondary' | 'accent' | 'text' | 'background' | 'surface'
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Color',
  showPresets: true,
  showAccessibility: false,
  disabled: false,
  type: 'primary'
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string]
  'colorChanged': [color: string, validation?: any]
}>()

// Reactive data
const isOpen = ref(false)
const activeColorMode = ref<'hsv' | 'hsl' | 'rgb'>('hsv')
const inputMode = ref<'hex' | 'rgb' | 'hsl'>('hex')
const showAccessibility = ref(false)
// Reactive data for component state
const showAccessibilityPanel = ref(false)
const recentColors = ref<string[]>([])
const accessibilitySuggestions = ref<string[]>([])
const currentColor = ref(props.modelValue)
const originalColor = ref(props.modelValue)

// Canvas refs
const colorMapCanvas = ref<HTMLCanvasElement>()
const hueSliderCanvas = ref<HTMLCanvasElement>()

// Color state
const hsv = ref({ h: 0, s: 1, v: 1 })
const rgb = ref({ r: 0, g: 0, b: 0 })
const hsl = ref({ h: 0, s: 1, l: 0.5 })

// Position tracking for canvas interaction
const hueSatPosition = ref({ x: 128, y: 128 })
const huePosition = ref({ y: 0 })
const isDragging = ref(false)

// Configuration
const colorModes = [
  { label: 'HSV', value: 'hsv' as const },
  { label: 'HSL', value: 'hsl' as const },
  { label: 'RGB', value: 'rgb' as const }
]

const colorFormats = [
  { label: 'HEX', value: 'hex' as const },
  { label: 'RGB', value: 'rgb' as const },
  { label: 'HSL', value: 'hsl' as const }
]

const defaultColors = [
  '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff',
  '#ffff00', '#ff00ff', '#00ffff', '#ffa500', '#800080',
  '#ffc0cb', '#a52a2a', '#808080', '#000080', '#008000'
]

const accessibilityEnabled = computed(() => props.showAccessibility)

// Computed properties
const hexValue = computed({
  get: () => currentColor.value.startsWith('#') ? currentColor.value : '#' + currentColor.value,
  set: (value) => {
    currentColor.value = value.startsWith('#') ? value : '#' + value
  }
})

const hasChanged = computed(() => currentColor.value !== originalColor.value)

const contrastResults = computed(() => {
  // Placeholder for contrast calculation logic
  return [
    { backgroundColor: '#ffffff', ratio: 4.63, level: 'AA' as const },
    { backgroundColor: '#333333', ratio: 8.92, level: 'AAA' as const }
  ]
})

// Methods
const togglePicker = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

const selectColor = (color: string) => {
  currentColor.value = color
  addToRecentColors(color)
  updateHSVFromCurrentColor()
  updateRGBFromHSV()
  updateHSLFromHSV()
  emitColorChange()
}

const addToRecentColors = (color: string) => {
  if (!recentColors.value.includes(color)) {
    recentColors.value.unshift(color)
    if (recentColors.value.length > 8) {
      recentColors.value.pop()
    }
  }
}

const resetToOriginal = () => {
  currentColor.value = originalColor.value
  updateHSVFromCurrentColor()
  updateRGBFromHSV()
  updateHSLFromHSV()
  emitColorChange()
}

const applyColor = () => {
  originalColor.value = currentColor.value
  addToRecentColors(currentColor.value)
  emit('update:modelValue', currentColor.value)
  isOpen.value = false
  emit('colorChanged', currentColor.value, getAccessibilityValidation())
}

const cancel = () => {
  currentColor.value = originalColor.value
  isOpen.value = false
}

const toggleAccessibility = () => {
  showAccessibility.value = !showAccessibility.value
}

// Color conversion methods
const updateHSVFromCurrentColor = () => {
  // Convert current color to HSV
  const tempRgb = hexToRgb(currentColor.value)
  if (tempRgb) {
    rgb.value = tempRgb
    rgbToHsv(rgb.value.r, rgb.value.g, rgb.value.b)
  }
}

const updateRGBFromHSV = () => {
  const { r, g, b } = hsvToRgb(hsv.value.h, hsv.value.s, hsv.value.v)
  rgb.value = { r: Math.round(r), g: Math.round(g), b: Math.round(b) }
  currentColor.value = rgbToHex(r, g, b)
}

const updateHSLFromHSV = () => {
  hsl.value = hsvToHsl(hsv.value.h, hsv.value.s, hsv.value.v)
}

const updateFromRgbInputs = () => {
  hsv.value = rgbToHsv(rgb.value.r, rgb.value.g, rgb.value.b)
  currentColor.value = rgbToHex(rgb.value.r, rgb.value.g, rgb.value.b)
  hsl.value = hsvToHsl(hsv.value.h, hsv.value.s, hsv.value.v)
  emitColorChange()
}

const updateFromHex = () => {
  if (/^#[0-9A-Fa-f]{6}$/.test(hexValue.value)) {
    currentColor.value = hexValue.value
    updateHSVFromCurrentColor()
    updateHSLFromHSV()
    emitColorChange()
  } else {
    // Reset to current value if invalid
    hexValue.value = currentColor.value
  }
}

const emitColorChange = () => {
  emit('colorChanged', currentColor.value, getAccessibilityValidation())
}

const getAccessibilityValidation = () => {
  return {
    contrast: contrastResults.value,
    suggestions: accessibilitySuggestions.value,
    accessible: contrastResults.value.some(r => r.level === 'AAA')
  }
}

// Color conversion utilities
const hexToRgb = (hex: string) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null
}

const rgbToHex = (r: number, g: number, b: number) => {
  return '#' + [r, g, b].map(x => {
    const hex = Math.max(0, Math.min(255, Math.round(x))).toString(16)
    return hex.length === 1 ? '0' + hex : hex
  }).join('')
}

const rgbToHsv = (r: number, g: number, b: number): { h: number; s: number; v: number } => {
  r /= 255
  g /= 255
  b /= 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const diff = max - min

  const s = max === 0 ? 0 : diff / max
  const v = max
  let h = max === min ? 0 :
    max === r ? ((g - b) / diff) % 6 :
    max === g ? (b - r) / diff + 2 :
    (r - g) / diff + 4
  h = (h * 60 + 360) % 360

  return { h, s, v }
}

const hsvToRgb = (h: number, s: number, v: number) => {
  const f = (t: number) => {
    t = ((t + h / 60) % 6)
    return v - v * s * Math.max(Math.min(t, 4 - t, 1), 0)
  }
  return { r: f(5) * 255, g: f(3) * 255, b: f(1) * 255 }
}

const hsvToHsl = (h: number, s: number, v: number) => {
  const l = v - v * s / 2
  const _s = l === 0 || l === 1 ? 0 : (v - l) / Math.min(l, 1 - l)
  return { h, s: _s, l }
}

const getContrastClass = (level: 'AA' | 'AAA' | 'fail') => {
  return {
    'AA': 'contrast-aa',
    'AAA': 'contrast-aaa',
    'fail': 'contrast-fail'
  }[level]
}

// Canvas interaction methods
const startColorSelection = (event: MouseEvent) => {
  if (!colorMapCanvas.value) return

  isDragging.value = true
  updateColorFromPosition(event)
  document.addEventListener('mousemove', updateColorFromPosition)
  document.addEventListener('mouseup', endColorSelection)
}

const updateColorFromPosition = (event: MouseEvent) => {
  if (!isDragging.value || !colorMapCanvas.value) return

  const rect = colorMapCanvas.value.getBoundingClientRect()
  const x = Math.max(0, Math.min(255, event.clientX - rect.left))
  const y = Math.max(0, Math.min(255, event.clientY - rect.top))

  hueSatPosition.value = { x, y }
  hsv.value.s = x / 255
  hsv.value.v = (255 - y) / 255

  updateRGBFromHSV()
  updateHSLFromHSV()
  emitColorChange()
}

const endColorSelection = () => {
  isDragging.value = false
  document.removeEventListener('mousemove', updateColorFromPosition)
  document.removeEventListener('mouseup', endColorSelection)
}

const renderColorMap = () => {
  if (!colorMapCanvas.value) return

  const ctx = colorMapCanvas.value.getContext('2d')
  if (!ctx) return

  const imageData = ctx.createImageData(256, 256)
  const data = imageData.data

  for (let y = 0; y < 256; y++) {
    for (let x = 0; x < 256; x++) {
      const i = (y * 256 + x) * 4
      const { r, g, b } = hsvToRgb(hsv.value.h, x / 255, (255 - y) / 255)

      data[i]     = r * 255 // R value
      data[i + 1] = g * 255 // G value
      data[i + 2] = b * 255 // B value
      data[i + 3] = 255     // A value
    }
  }

  ctx.putImageData(imageData, 0, 0)
}

const renderHueSlider = () => {
  if (!hueSliderCanvas.value) return

  const ctx = hueSliderCanvas.value.getContext('2d')
  if (!ctx) return

  const imageData = ctx.createImageData(20, 256)
  const data = imageData.data

  for (let y = 0; y < 256; y++) {
    for (let x = 0; x < 20; x++) {
      const i = (y * 20 + x) * 4
      const hue = (255 - y) / 255 * 360
      const { r, g, b } = hsvToRgb(hue, 1, 1)

      data[i]     = r * 255 // R value
      data[i + 1] = g * 255 // G value
      data[i + 2] = b * 255 // B value
      data[i + 3] = 255     // A value
    }
  }

  ctx.putImageData(imageData, 0, 0)
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue !== currentColor.value) {
    currentColor.value = newValue
    originalColor.value = newValue
    updateHSVFromCurrentColor()
    updateHSLFromHSV()
  }
})

// Lifecycle
onMounted(() => {
  // Initialize color from props
  updateHSVFromCurrentColor()
  updateHSLFromHSV()

  // Load recent colors from localStorage
  const saved = localStorage.getItem(`color-recent-${props.type}`)
  if (saved) {
    try {
      recentColors.value = JSON.parse(saved)
    } catch (e) {
      console.warn('Failed to load recent colors:', e)
    }
  }

  // Render initial canvas
  nextTick(() => {
    renderColorMap()
    renderHueSlider()
  })

  // Watch for canvas updates
  watch(hsv, () => {
    renderColorMap()
    renderHueSlider()
  }, { deep: true })
})

onBeforeUnmount(() => {
  // Save recent colors
  localStorage.setItem(`color-recent-${props.type}`, JSON.stringify(recentColors.value))
})
</script>

<style scoped>
.color-picker {
  @apply relative inline-block;
}

.color-picker--disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Color Input Group */
.color-input-group {
  @apply flex items-center gap-2;
}

.color-preview {
  @apply flex items-center gap-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg;
  @apply bg-white dark:bg-gray-700 cursor-pointer transition-colors;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-1;
}

.color-preview:hover {
  @apply border-gray-400 dark:border-gray-500;
}

.color-swatch {
  @apply w-6 h-6 rounded border border-gray-200 dark:border-gray-600;
  @apply shadow-sm;
}

.color-value {
  @apply text-sm font-mono text-gray-900 dark:text-gray-100 min-w-[80px];
}

.color-toggle {
  @apply transition-transform duration-200;
}

.color-toggle.is-open {
  @apply transform rotate-180;
}

/* Color Presets */
.color-presets {
  @apply flex items-center gap-1;
}

.color-preset {
  @apply w-6 h-6 rounded border border-gray-200 dark:border-gray-600;
  @apply transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
  @apply focus:outline-none;
}

.color-preset:hover {
  @apply transform scale-110;
}

.color-preset.selected {
  @apply ring-2 ring-blue-500 ring-offset-1;
}

.preset-swatch {
  @apply w-full h-full rounded-sm;
}

/* Color Picker Panel */
.color-picker-panel {
  @apply absolute top-full left-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl;
  @apply shadow-xl z-50 p-4;
}

/* Color Mode Tabs */
.color-mode-tabs {
  @apply flex border-b border-gray-200 dark:border-gray-700 mb-4;
}

.mode-tab {
  @apply flex-1 px-3 py-2 text-sm font-medium rounded-t-lg transition-colors;
  @apply text-gray-600 dark:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
}

.mode-tab.active {
  @apply bg-blue-500 text-white;
}

/* Color Picker Canvas */
.color-picker-canvas {
  @apply space-y-4;
}

.color-map-container {
  @apply flex gap-4;
}

.color-map {
  @apply relative w-full h-32 border border-gray-200 dark:border-gray-600 rounded-lg;
  @apply cursor-crosshair overflow-hidden;
}

.color-map-canvas {
  @apply w-full h-full cursor-crosshair;
  @apply focus:outline-none;
}

.color-map-indicator {
  @apply absolute w-3 h-3 border-2 border-white rounded-full shadow-lg;
  @apply transform -translate-x-1/2 -translate-y-1/2 pointer-events-none;
}

.hue-slider {
  @apply relative w-5 h-32 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden;
  @apply cursor-ns-resize;
}

.hue-slider-canvas {
  @apply w-full h-full cursor-ns-resize;
  @apply focus:outline-none;
}

.hue-slider-indicator {
  @apply absolute left-0 w-full h-1 bg-white shadow-lg pointer-events-none;
  @apply transform -translate-y-1/2;
}

/* Color Inputs */
.color-inputs {
  @apply space-y-3;
}

.input-group {
  @apply flex items-center gap-2;
}

.input-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 w-12;
}

.color-slider {
  @apply flex-1;
}

.color-number-input {
  @apply w-16 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600;
  @apply bg-white dark:bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
}

/* Hex Input */
.hex-input-container {
  @apply flex items-center gap-2;
}

.hex-input {
  @apply flex-1 px-3 py-2 text-sm font-mono border border-gray-300 dark:border-gray-600;
  @apply bg-white dark:bg-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-inset;
  @apply focus:outline-none;
  @apply uppercase;
}

/* Color Format Selector */
.color-format-selector {
  @apply flex gap-1;
}

.format-btn {
  @apply flex-1 px-2 py-1 text-xs font-medium rounded-md transition-colors;
  @apply bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-inset focus:outline-none;
}

.format-btn.active {
  @apply bg-blue-500 text-white;
}

/* Recent Colors */
.recent-colors {
  @apply space-y-2;
}

.recent-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.recent-colors-list {
  @apply flex gap-1 flex-wrap;
}

.recent-color {
  @apply w-6 h-6 rounded border border-gray-200 dark:border-gray-600;
  @apply transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
  @apply focus:outline-none hover:transform hover:scale-110;
}

.recent-swatch {
  @apply w-full h-full rounded-sm;
}

/* Accessibility Panel */
.accessibility-panel {
  @apply bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg;
}

.accessibility-header {
  @apply flex items-center justify-between mb-3;
}

.accessibility-title {
  @apply text-sm font-semibold text-blue-900 dark:text-blue-100;
}

.accessibility-toggle {
  @apply p-1 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800/50 rounded;
}

.contrast-checker {
  @apply space-y-2 mb-3;
}

.contrast-result {
  @apply flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded;
  @apply border border-gray-200 dark:border-gray-700;
}

.contrast-against {
  @apply flex items-center gap-2;
}

.contrast-swatch {
  @apply w-4 h-4 rounded border border-gray-300 dark:border-gray-600;
}

.contrast-text {
  @apply text-xs font-mono text-gray-600 dark:text-gray-400;
}

.contrast-ratio {
  @apply flex items-center gap-2;
}

.contrast-aa {
  @apply text-green-600 dark:text-green-400;
}

.contrast-aaa {
  @apply text-blue-600 dark:text-blue-400 font-semibold;
}

.contrast-fail {
  @apply text-red-600 dark:text-red-400;
}

.contrast-level {
  @apply text-xs font-medium;
}

.accessibility-suggestions {
  @apply border-t border-blue-200 dark:border-blue-800 pt-3;
}

.accessibility-suggestions h5 {
  @apply text-sm font-medium text-blue-900 dark:text-blue-100 mb-2;
}

.suggestions-list {
  @apply space-y-1;
}

.suggestion-item {
  @apply text-xs text-blue-800 dark:text-blue-200;
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

/* Accessibility Icon */
.accessibility-icon {
  @apply absolute top-2 right-2 p-1 text-gray-600 dark:text-gray-400;
  @apply hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors;
  @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 focus:outline-none;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .color-picker-panel {
    @apply bg-gray-800 border-gray-700;
  }

  .color-preview {
    @apply bg-gray-800 border-gray-600;
  }

  .color preset {
    @apply border-gray-600;
  }

  .color-map,
  .hue-slider {
    @apply border-gray-600;
  }

  .hex-input,
  .color-number-input {
    @apply bg-gray-700 border-gray-600;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .color-map-indicator,
  .hue-slider-indicator {
    @apply transition-none;
  }

  .color-preset:hover,
  .recent-color:hover,
  .color-preview:hover {
    @apply transform-none scale-100;
  }
}
</style>