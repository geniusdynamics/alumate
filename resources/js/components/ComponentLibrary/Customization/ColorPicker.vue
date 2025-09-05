<template>
  <div class="color-picker" role="group" aria-labelledby="color-picker-label">
    <!-- Header -->
    <div id="color-picker-label" class="sr-only">Color Picker</div>

    <!-- Current Color Display -->
    <div class="color-current" v-if="showCurrentColor">
      <div
        class="color-swatch"
        :style="{ backgroundColor: modelValue }"
        @click="toggleDialog"
        @keydown.space.prevent="toggleDialog"
        @keydown.enter.prevent="toggleDialog"
        tabindex="0"
        role="button"
        :aria-label="`Current color: ${modelValue}`"
        :aria-expanded="isDialogOpen"
        :aria-controls="isDialogOpen ? 'color-picker-dialog' : undefined"
      ></div>
      <div class="color-value">{{ modelValue }}</div>
    </div>

    <!-- Color Picker Dialog -->
    <Transition name="color-picker-fade">
      <div
        v-if="isDialogOpen"
        id="color-picker-dialog"
        class="color-picker-dialog"
        role="dialog"
        aria-modal="true"
        :aria-label="`Color picker for ${modelValue}`"
      >
        <div class="color-picker-overlay" @click="closeDialog"></div>
        <div class="color-picker-content" ref="dialogContent" @keydown.esc="closeDialog">
          <!-- Header -->
          <div class="color-picker-header">
            <h3 class="color-picker-title">Select Color</h3>
            <button
              type="button"
              @click="closeDialog"
              class="color-picker-close"
              aria-label="Close color picker"
            >
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <!-- Color Picker Interface -->
          <div class="color-picker-interface">
            <!-- Saturation-Lightness Picker -->
            <div class="color-palette" ref="saturationLightness" @mousedown="startDrag" @touchstart="startDrag">
              <div
                class="color-palette-overlay"
                :style="{ backgroundColor: hslToHex(hue, 1, 0.5) }"
              >
                <div class="color-cursor" :style="{ left: `${satCoords.x * 100}%`, top: `${satCoords.y * 100}%` }"></div>
              </div>
            </div>

            <!-- Hue Slider -->
            <div class="hue-slider" ref="hueSlider" @mousedown="startHueDrag" @touchstart="startHueDrag">
              <div class="hue-cursor" :style="{ left: `${hueCoords.x * 100}%` }"></div>
            </div>

            <!-- Alpha Slider (if enabled) -->
            <div v-if="showAlpha" class="alpha-slider" ref="alphaSlider" @mousedown="startAlphaDrag" @touchstart="startAlphaDrag">
              <div
                class="alpha-slider-bg"
                :style="{ background: `linear-gradient(90deg, transparent, ${hslToHex(hue, saturation, lightness)})` }"
              >
                <div class="alpha-cursor" :style="{ left: `${alphaCoords.x * 100}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Color Preview and Input -->
          <div class="color-preview-section">
            <!-- New Color Preview -->
            <div class="color-preview">
              <div
                class="color-swatch-large"
                :style="{ backgroundColor: currentDisplayColor }"
              ></div>
              <div class="color-inputs">
                <div class="color-input-group">
                  <label for="hex-input" class="sr-only">Hex color value</label>
                  <input
                    id="hex-input"
                    v-model="hexValue"
                    type="text"
                    class="color-input hex-input"
                    @input="updateFromHex"
                    @blur="validateHex"
                    maxlength="9"
                    :placeholder="showAlpha ? '#RRGGBBAA' : '#RRGGBB'"
                    aria-describedby="hex-description"
                  />
                  <div id="hex-description" class="sr-only">Hex color value input</div>
                </div>

                <!-- RGB Inputs -->
                <div class="color-input-group rgb-group" v-if="inputMode === 'rgb'">
                  <label for="red-input" class="sr-only">Red value</label>
                  <input
                    id="red-input"
                    v-model.number="rgb.r"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="255"
                    @input="updateFromRgb"
                    aria-describedby="red-description"
                  />
                  <div id="red-description" class="sr-only">Red color component (0-255)</div>

                  <label for="green-input" class="sr-only">Green value</label>
                  <input
                    id="green-input"
                    v-model.number="rgb.g"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="255"
                    @input="updateFromRgb"
                    aria-describedby="green-description"
                  />
                  <div id="green-description" class="sr-only">Green color component (0-255)</div>

                  <label for="blue-input" class="sr-only">Blue value</label>
                  <input
                    id="blue-input"
                    v-model.number="rgb.b"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="255"
                    @input="updateFromRgb"
                    aria-describedby="blue-description"
                  />
                  <div id="blue-description" class="sr-only">Blue color component (0-255)</div>

                  <label v-if="showAlpha" for="alpha-input" class="sr-only">Alpha value</label>
                  <input
                    v-if="showAlpha"
                    id="alpha-input"
                    v-model.number="rgb.a"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="1"
                    step="0.01"
                    @input="updateFromRgb"
                    aria-describedby="alpha-description"
                  />
                  <div v-if="showAlpha" id="alpha-description" class="sr-only">Alpha transparency (0-1)</div>
                </div>

                <!-- HSL Inputs -->
                <div class="color-input-group hsl-group" v-if="inputMode === 'hsl'">
                  <label for="hue-input" class="sr-only">Hue value</label>
                  <input
                    id="hue-input"
                    v-model.number="hsl.h"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="360"
                    @input="updateFromHsl"
                    aria-describedby="hue-description"
                  />
                  <div id="hue-description" class="sr-only">Hue angle (0-360 degrees)</div>

                  <label for="sat-input" class="sr-only">Saturation value</label>
                  <input
                    id="sat-input"
                    v-model.number="hsl.s"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="100"
                    @input="updateFromHsl"
                    aria-describedby="sat-description"
                  />
                  <div id="sat-description" class="sr-only">Saturation percentage (0-100%)</div>

                  <label for="lightnes-input" class="sr-only">Lightness value</label>
                  <input
                    id="lightness-input"
                    v-model.number="hsl.l"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="100"
                    @input="updateFromHsl"
                    aria-describedby="lightness-description"
                  />
                  <div id="lightness-description" class="sr-only">Lightness percentage (0-100%)</div>

                  <label v-if="showAlpha" for="hsl-alpha" class="sr-only">Alpha value</label>
                  <input
                    v-if="showAlpha"
                    id="hsl-alpha"
                    v-model.number="hsl.a"
                    type="number"
                    class="color-input small-input"
                    min="0"
                    max="1"
                    step="0.01"
                    @input="updateFromHsl"
                    aria-describedby="hsl-alpha-description"
                  />
                  <div v-if="showAlpha" id="hsl-alpha-description" class="sr-only">HSL Alpha transparency (0-1)</div>
                </div>
              </div>
            </div>

            <!-- Input Mode Toggle -->
            <div class="input-mode-toggle" v-if="showAlpha">
              <button
                type="button"
                @click="inputMode = 'rgb'"
                :class="{ active: inputMode === 'rgb' }"
                class="mode-button"
              >
                RGB
              </button>
              <button
                type="button"
                @click="inputMode = 'hsl'"
                :class="{ active: inputMode === 'hsl' }"
                class="mode-button"
              >
                HSL
              </button>
            </div>
          </div>

          <!-- Preset Colors -->
          <div class="preset-colors" v-if="presetColors && presetColors.length > 0">
            <h4 class="preset-title">Preset Colors</h4>
            <div class="color-grid">
              <button
                v-for="color in presetColors"
                :key="color"
                type="button"
                @click="selectPresetColor(color)"
                :aria-label="`Select preset color ${color}`"
                class="preset-color"
                :style="{ backgroundColor: color }"
              ></button>
            </div>
          </div>

          <!-- Actions -->
          <div class="color-picker-actions">
            <button
              type="button"
              @click="resetToInitialColor"
              class="btn-secondary"
            >
              Reset
            </button>
            <div class="action-group">
              <button
                type="button"
                @click="closeDialog"
                class="btn-secondary"
              >
                Cancel
              </button>
              <button
                type="button"
                @click="confirmColor"
                class="btn-primary"
                :disabled="!isValidColor"
              >
                Apply Color
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'

interface Props {
  modelValue: string
  showCurrentColor?: boolean
  showAlpha?: boolean
  presetColors?: string[]
  disableTransparency?: boolean
}

interface Emits {
  'update:modelValue': [value: string]
  'color-selected': [value: string]
  'cancel': []
}

const props = withDefaults(defineProps<Props>(), {
  showCurrentColor: true,
  showAlpha: false,
  disableTransparency: false,
  presetColors: () => []
})

const emit = defineEmits<Emits>()

// Reactive state
const isDialogOpen = ref(false)
const dialogContent = ref<HTMLElement>()

// Color state
const hue = ref(0)
const saturation = ref(1)
const lightness = ref(0.5)
const alpha = ref(1)

// Coordinates for draggable elements
const satCoords = ref({ x: 1, y: 0.5 }) // Saturation/Lightness (0-1 range)
const hueCoords = ref({ x: 0, y: 0 })  // Hue (0-1 range)
const alphaCoords = ref({ x: 1, y: 0 }) // Alpha (0-1 range)

// Input mode
const inputMode = ref<'rgb' | 'hsl'>('hsl')

// Color representations
const rgb = ref({ r: 255, g: 0, b: 0, a: 1 })
const hsl = ref({ h: 0, s: 100, l: 50, a: 1 })
const hexValue = ref('#ff0000')

// Drag state
let isDragging = false
let isHueDragging = false
let isAlphaDragging = false
let dragStart: { x: number; y: number; element: HTMLElement } | null = null

// Computed properties
const currentDisplayColor = computed(() => {
  const hex = hslToHex(hue.value, saturation.value, lightness.value)
  return props.showAlpha ? tryAddAlpha(hex, alpha.value) : hex
})

const isValidColor = computed(() => {
  return /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/.test(hexValue.value)
})

// Methods
const toggleDialog = () => {
  if (isDialogOpen.value) {
    closeDialog()
  } else {
    openDialog()
  }
}

const openDialog = () => {
  parseInitialColor()
  isDialogOpen.value = true
  nextTick(() => focusDialogContent())
}

const closeDialog = () => {
  isDialogOpen.value = false
  emit('cancel')
}

const focusDialogContent = () => {
  if (dialogContent.value) {
    dialogContent.value.focus()
  }
}

const parseInitialColor = () => {
  const color = props.modelValue
  try {
    if (color.startsWith('#')) {
      hexValue.value = color
      const parsed = hexToHsl(color)
      hue.value = parsed.h
      saturation.value = parsed.s
      lightness.value = parsed.l
      alpha.value = parsed.a

      // Update coordinates
      satCoords.value.x = saturation.value
      satCoords.value.y = 1 - lightness.value
      hueCoords.value.x = hue.value / 360
      alphaCoords.value.x = alpha.value

      updateRgbFromHsl()
      updateHslFromInternal()
    }
  } catch {
    // Fallback to red if invalid
    hexValue.value = '#ff0000'
    hue.value = 0
    saturation.value = 1
    lightness.value = 0.5
    alpha.value = 1
  }
}

const hexToHsl = (hex: string) => {
  // Remove # if present
  hex = hex.replace('#', '')

  // Parse RGB components
  let r: number, g: number, b: number, a: number = 1

  if (hex.length === 3) {
    r = parseInt(hex[0] + hex[0], 16)
    g = parseInt(hex[1] + hex[1], 16)
    b = parseInt(hex[2] + hex[2], 16)
  } else if (hex.length === 6) {
    r = parseInt(hex.substr(0, 2), 16)
    g = parseInt(hex.substr(2, 2), 16)
    b = parseInt(hex.substr(4, 2), 16)
  } else if (hex.length === 8) {
    r = parseInt(hex.substr(0, 2), 16)
    g = parseInt(hex.substr(2, 2), 16)
    b = parseInt(hex.substr(4, 2), 16)
    a = parseInt(hex.substr(6, 2), 16) / 255
  } else {
    throw new Error('Invalid hex color')
  }

  // Normalize RGB to 0-1
  r /= 255
  g /= 255
  b /= 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  let h: number, s: number, l: number = (max + min) / 2

  if (max === min) {
    h = s = 0 // achromatic
  } else {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)

    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break
      case g: h = (b - r) / d + 2; break
      case b: h = (r - g) / d + 4; break
      default: h = 0
    }
    h /= 6
  }

  return {
    h: Math.round(h * 360),
    s: Math.round(s * 100),
    l: Math.round(l * 100),
    a
  }
}

const hslToHex = (h: number, s: number, l: number): string => {
  // Normalize inputs
  h = h % 360 / 360
  s = Math.max(0, Math.min(1, s))
  l = Math.max(0, Math.min(1, l))

  const hue2rgb = (p: number, q: number, t: number) => {
    if (t < 0) t += 1
    if (t > 1) t -= 1
    if (t < 1/6) return p + (q - p) * 6 * t
    if (t < 1/2) return q
    if (t < 2/3) return p + (q - p) * (2/3 - t) * 6
    return p
  }

  let r: number, g: number, b: number

  if (s === 0) {
    r = g = b = l // achromatic
  } else {
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s
    const p = 2 * l - q
    r = hue2rgb(p, q, h + 1/3)
    g = hue2rgb(p, q, h)
    b = hue2rgb(p, q, h - 1/3)
  }

  return rgbToHex(Math.round(r * 255), Math.round(g * 255), Math.round(b * 255))
}

const rgbToHex = (r: number, g: number, b: number): string => {
  return `#${[r, g, b].map(x => {
    const hex = x.toString(16)
    return hex.length === 1 ? '0' + hex : hex
  }).join('')}`
}

const tryAddAlpha = (hex: string, alpha: number): string => {
  if (!props.showAlpha) return hex
  return `${hex}${Math.round(alpha * 255).toString(16).padStart(2, '0')}`
}

// Input mode functions
const updateFromHex = () => {
  if (isValidColor.value) {
    try {
      const parsed = hexToHsl(hexValue.value)
      hue.value = parsed.h
      saturation.value = parsed.s / 100
      lightness.value = parsed.l / 100
      alpha.value = parsed.a

      // Update coordinates
      satCoords.value.x = saturation.value
      satCoords.value.y = 1 - lightness.value
      hueCoords.value.x = hue.value / 360
      alphaCoords.value.x = alpha.value

      updateRgbFromHsl()
      updateHslFromInternal()
    } catch {
      // Invalid input, ignore
    }
  }
}

const validateHex = () => {
  if (!isValidColor.value) {
    // Reset to current valid color
    hexValue.value = hslToHex(hue.value, saturation.value, lightness.value)
    if (props.showAlpha) {
      hexValue.value += Math.round(alpha.value * 255).toString(16).padStart(2, '0')
    }
  }
}

const updateFromRgb = () => {
  // Validate and clamp values
  rgb.value.r = Math.max(0, Math.min(255, rgb.value.r || 0))
  rgb.value.g = Math.max(0, Math.min(255, rgb.value.g || 0))
  rgb.value.b = Math.max(0, Math.min(255, rgb.value.b || 0))
  if (props.showAlpha) {
    rgb.value.a = Math.max(0, Math.min(1, rgb.value.a || 0))
  }

  // Convert to HSL
  const r = rgb.value.r / 255
  const g = rgb.value.g / 255
  const b = rgb.value.b / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const l = (max + min) / 2

  let h: number, s: number

  if (max === min) {
    h = s = 0 // achromatic
  } else {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break
      case g: h = (b - r) / d + 2; break
      case b: h = (r - g) / d + 4; break
      default: h = 0
    }
    h /= 6
  }

  hue.value = Math.round(h * 360)
  saturation.value = s
  lightness.value = l

  if (props.showAlpha) {
    alpha.value = rgb.value.a
    alphaCoords.value.x = alpha.value
  }

  // Update coordinates and displays
  satCoords.value.x = saturation.value
  satCoords.value.y = 1 - lightness.value
  hueCoords.value.x = hue.value / 360

  updateHexFromInternal()
}

const updateFromHsl = () => {
  // Validate and clamp values
  hsl.value.h = Math.max(0, Math.min(360, hsl.value.h || 0))
  hsl.value.s = Math.max(0, Math.min(100, hsl.value.s || 0))
  hsl.value.l = Math.max(0, Math.min(100, hsl.value.l || 0))
  if (props.showAlpha) {
    hsl.value.a = Math.max(0, Math.min(1, hsl.value.a || 0))
  }

  hue.value = hsl.value.h
  saturation.value = hsl.value.s / 100
  lightness.value = hsl.value.l / 100

  if (props.showAlpha) {
    alpha.value = hsl.value.a
    alphaCoords.value.x = alpha.value
  }

  // Update coordinates and displays
  satCoords.value.x = saturation.value
  satCoords.value.y = 1 - lightness.value
  hueCoords.value.x = hue.value / 360

  updateRgbFromHsl()
  updateHexFromInternal()
}

const updateRgbFromHsl = () => {
  const h = hue.value / 360
  const s = saturation.value
  const l = lightness.value

  const hue2rgb = (p: number, q: number, t: number) => {
    if (t < 0) t += 1
    if (t > 1) t -= 1
    if (t < 1/6) return p + (q - p) * 6 * t
    if (t < 1/2) return q
    if (t < 2/3) return p + (q - p) * (2/3 - t) * 6
    return p
  }

  let r: number, g: number, b: number

  if (s === 0) {
    r = g = b = l // achromatic
  } else {
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s
    const p = 2 * l - q
    r = hue2rgb(p, q, h + 1/3)
    g = hue2rgb(p, q, h)
    b = hue2rgb(p, q, h - 1/3)
  }

  rgb.value.r = Math.round(r * 255)
  rgb.value.g = Math.round(g * 255)
  rgb.value.b = Math.round(b * 255)
  rgb.value.a = alpha.value
}

const updateHslFromInternal = () => {
  hsl.value.h = Math.round(hue.value)
  hsl.value.s = Math.round(saturation.value * 100)
  hsl.value.l = Math.round(lightness.value * 100)
  hsl.value.a = alpha.value
}

const updateHexFromInternal = () => {
  hexValue.value = hslToHex(hue.value, saturation.value, lightness.value)
  if (props.showAlpha) {
    hexValue.value += Math.round(alpha.value * 255).toString(16).padStart(2, '0')
  }
}

// Drag handling
const startDrag = (event: MouseEvent | TouchEvent) => {
  isDragging = true
  updateSaturationLightness(event)
  document.addEventListener('mousemove', handleDrag)
  document.addEventListener('mouseup', stopDrag)
  document.addEventListener('touchmove', handleDrag)
  document.addEventListener('touchend', stopDrag)
}

const startHueDrag = (event: MouseEvent | TouchEvent) => {
  isHueDragging = true
  updateHue(event)
  document.addEventListener('mousemove', handleHueDrag)
  document.addEventListener('mouseup', stopHueDrag)
  document.addEventListener('touchmove', handleHueDrag)
  document.addEventListener('touchend', stopHueDrag)
}

const startAlphaDrag = (event: MouseEvent | TouchEvent) => {
  isAlphaDragging = true
  updateAlpha(event)
  document.addEventListener('mousemove', handleAlphaDrag)
  document.addEventListener('mouseup', stopAlphaDrag)
  document.addEventListener('touchmove', handleAlphaDrag)
  document.addEventListener('touchend', stopAlphaDrag)
}

const handleDrag = (event: MouseEvent | TouchEvent) => {
  if (isDragging) {
    updateSaturationLightness(event)
  }
}

const handleHueDrag = (event: MouseEvent | TouchEvent) => {
  if (isHueDragging) {
    updateHue(event)
  }
}

const handleAlphaDrag = (event: MouseEvent | TouchEvent) => {
  if (isAlphaDragging) {
    updateAlpha(event)
  }
}

const stopDrag = () => {
  isDragging = false
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', stopDrag)
  document.removeEventListener('touchmove', handleDrag)
  document.removeEventListener('touchend', stopDrag)
}

const stopHueDrag = () => {
  isHueDragging = false
  document.removeEventListener('mousemove', handleHueDrag)
  document.removeEventListener('mouseup', stopHueDrag)
  document.removeEventListener('touchmove', handleHueDrag)
  document.removeEventListener('touchend', stopHueDrag)
}

const stopAlphaDrag = () => {
  isAlphaDragging = false
  document.removeEventListener('mousemove', handleAlphaDrag)
  document.removeEventListener('mouseup', stopAlphaDrag)
  document.removeEventListener('touchmove', handleAlphaDrag)
  document.removeEventListener('touchend', stopAlphaDrag)
}

const updateSaturationLightness = (event: MouseEvent | TouchEvent) => {
  const rect = (event.target as HTMLElement).getBoundingClientRect()
  const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX
  const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY

  const x = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
  const y = Math.max(0, Math.min(1, (clientY - rect.top) / rect.height))

  satCoords.value.x = x
  satCoords.value.y = y
  saturation.value = x
  lightness.value = 1 - y // Invert Y axis

  updateRgbFromHsl()
  updateHslFromInternal()
  updateHexFromInternal()
}

const updateHue = (event: MouseEvent | TouchEvent) => {
  const rect = (event.target as HTMLElement).getBoundingClientRect()
  const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX

  const x = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))

  hueCoords.value.x = x
  hue.value = x * 360

  updateRgbFromHsl()
  updateHslFromInternal()
  updateHexFromInternal()
}

const updateAlpha = (event: MouseEvent | TouchEvent) => {
  if (!props.showAlpha) return

  const rect = (event.target as HTMLElement).getBoundingClientRect()
  const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX

  const x = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))

  alphaCoords.value.x = x
  alpha.value = x

  updateRgbFromHsl()
  updateHslFromInternal()
  updateHexFromInternal()
}

// Preset colors
const selectPresetColor = (color: string) => {
  hexValue.value = color
  updateFromHex()
}

// Actions
const confirmColor = () => {
  const finalColor = currentDisplayColor.value
  emit('update:modelValue', finalColor)
  emit('color-selected', finalColor)
  closeDialog()
}

const resetToInitialColor = () => {
  emit('update:modelValue', props.modelValue)
  parseInitialColor()
}

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    parseInitialColor()
  }
})

onBeforeUnmount(() => {
  stopDrag()
  stopHueDrag()
  stopAlphaDrag()
})
</script>

<style scoped>
.color-picker {
  @apply relative inline-block;
}

.color-current {
  @apply flex items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm;
}

.color-swatch {
  @apply w-8 h-8 rounded-md border-2 border-gray-300 cursor-pointer transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.color-value {
  @apply text-sm font-mono text-gray-700 dark:text-gray-300 min-w-20;
}

/* Dialog */
.color-picker-fade-enter-active,
.color-picker-fade-leave-active {
  @apply transition-all duration-200;
}

.color-picker-fade-enter-from,
.color-picker-fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

.color-picker-dialog {
  @apply fixed inset-0 z-50 flex items-center justify-center p-4;
}

.color-picker-overlay {
  @apply absolute inset-0 bg-black/50;
}

.color-picker-content {
  @apply relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm max-h-[90vh] overflow-hidden;
}

.color-picker-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.color-picker-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.color-picker-close {
  @apply p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

/* Color Picker Interface */
.color-picker-interface {
  @apply p-4 space-y-4;
}

.color-palette {
  @apply relative w-full h-40 rounded-lg overflow-hidden cursor-crosshair bg-gray-200;
  background-image: linear-gradient(0deg, black 0%, transparent 50%, white 100%);
}

.color-palette-overlay {
  @apply absolute inset-0 cursor-crosshair;
}

.color-cursor {
  @apply absolute w-5 h-5 -ml-2.5 -mt-2.5 border-2 border-white rounded-full shadow-md pointer-events-none;
}

.hue-slider {
  @apply relative w-full h-4 rounded-lg overflow-hidden cursor-col-resize;
  background: linear-gradient(90deg, #ff0000 0%, #ffff00 16.67%, #00ff00 33.33%, #00ffff 50%, #0000ff 66.67%, #ff00ff 83.33%, #ff0000 100%);
}

.hue-cursor {
  @apply absolute top-0 w-3 h-full -ml-1.5 bg-white border border-gray-300 shadow-sm pointer-events-none;
}

.alpha-slider {
  @apply relative w-full h-4 rounded-lg overflow-hidden cursor-col-resize;
}

.alpha-slider-bg {
  @apply relative w-full h-full;
  background-image: linear-gradient(90deg, #808080 0%, transparent 100%), linear-gradient(45deg, #808080 25%, transparent 25%, transparent 75%, #808080 75%, #808080);
  background-size: 100% 100%, 20px 20px;
}

.alpha-cursor {
  @apply absolute top-0 w-3 h-full -ml-1.5 bg-white border border-gray-300 shadow-sm pointer-events-none;
}

/* Preview and Input */
.color-preview-section {
  @apply px-4 pb-4 space-y-4;
}

.color-preview {
  @apply flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.color-swatch-large {
  @apply w-12 h-12 rounded-lg border-2 border-gray-300 shadow-sm;
}

.color-input-group {
  @apply flex flex-col gap-2 flex-1;
}

.color-inputs {
  @apply flex gap-2 flex-1;
}

.rgb-group, .hsl-group {
  @apply flex gap-2;
}

.color-input {
  @apply px-3 py-2 text-sm border border-gray-300 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500;
}

.small-input {
  @apply w-16 text-xs;
}

.hex-input {
  @apply font-mono text-xs;
}

.input-mode-toggle {
  @apply flex gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-md;
}

.mode-button {
  @apply px-3 py-1 text-xs font-medium rounded text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-200 transition-colors;
}

.mode-button.active {
  @apply bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm;
}

/* Preset Colors */
.preset-colors {
  @apply px-4 pb-4;
}

.preset-title {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.color-grid {
  @apply grid grid-cols-8 gap-2;
}

.preset-color {
  @apply w-6 h-6 rounded border border-gray-300 hover:scale-110 transition-transform focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

/* Actions */
.color-picker-actions {
  @apply flex items-center justify-between px-4 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600;
}

.action-group {
  @apply flex gap-2;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .color-palette {
    @apply bg-gray-700;
  }
}

/* Mobile responsive */
@media (max-width: 640px) {
  .color-picker-content {
    @apply max-w-none mx-4;
  }

  .color-inputs {
    @apply flex-col gap-3;
  }

  .rgb-group, .hsl-group {
    @apply justify-between;
  }

  .small-input {
    @apply w-14;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .color-picker-content {
    @apply border-2 border-gray-900;
  }

  .color-swatch {
    @apply border-black border-opacity-100;
  }
}
</style>