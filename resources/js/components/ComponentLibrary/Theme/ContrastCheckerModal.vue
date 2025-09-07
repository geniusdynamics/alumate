<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-3xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Color Contrast Checker
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Check WCAG accessibility compliance for "{{ color?.name }}"
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="btn-icon"
          >
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <div v-if="color" class="space-y-6">
          <!-- Color Information -->
          <div class="color-info-section">
            <div class="flex items-center gap-4">
              <div
                class="color-display"
                :style="{ backgroundColor: color.value }"
              ></div>
              <div class="color-details">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ color.name }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                  {{ color.value }}
                </p>
                <p class="text-sm text-gray-500">
                  {{ color.type }} color
                </p>
              </div>
            </div>
          </div>

          <!-- Contrast Tests -->
          <div class="contrast-tests-section">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Contrast Tests Against Background Colors
            </h4>
            
            <div class="contrast-grid">
              <div
                v-for="bgColor in backgroundColors"
                :key="bgColor.id"
                class="contrast-test-card"
              >
                <div class="contrast-preview">
                  <div
                    class="contrast-sample large"
                    :style="{
                      backgroundColor: bgColor.value,
                      color: color.value
                    }"
                  >
                    <div class="sample-text">
                      <h5 class="text-lg font-semibold">Heading Text</h5>
                      <p class="text-sm">Normal body text for reading</p>
                      <p class="text-xs">Small text example</p>
                    </div>
                  </div>
                </div>
                
                <div class="contrast-info">
                  <div class="bg-color-info">
                    <div
                      class="bg-color-swatch"
                      :style="{ backgroundColor: bgColor.value }"
                    ></div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">
                        {{ bgColor.name }}
                      </p>
                      <p class="text-xs text-gray-500 font-mono">
                        {{ bgColor.value }}
                      </p>
                    </div>
                  </div>
                  
                  <div class="contrast-results">
                    <div class="contrast-ratio">
                      <span class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ getContrastRatio(color.value, bgColor.value).toFixed(2) }}:1
                      </span>
                    </div>
                    
                    <div class="compliance-badges">
                      <span
                        v-for="test in getComplianceTests(color.value, bgColor.value)"
                        :key="test.name"
                        class="compliance-badge"
                        :class="getComplianceBadgeClass(test.passes)"
                      >
                        {{ test.name }}: {{ test.passes ? 'Pass' : 'Fail' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Custom Background Test -->
          <div class="custom-test-section">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Test Against Custom Background
            </h4>
            
            <div class="custom-test-controls">
              <div class="custom-color-input">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Background Color
                </label>
                <div class="color-input-group">
                  <input
                    v-model="customBackgroundColor"
                    type="color"
                    class="color-picker"
                  />
                  <input
                    v-model="customBackgroundColor"
                    type="text"
                    class="form-input flex-1"
                    placeholder="#FFFFFF"
                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                  />
                </div>
              </div>
              
              <div class="custom-test-preview">
                <div
                  class="contrast-sample large"
                  :style="{
                    backgroundColor: customBackgroundColor,
                    color: color.value
                  }"
                >
                  <div class="sample-text">
                    <h5 class="text-lg font-semibold">Custom Background Test</h5>
                    <p class="text-sm">This is how your text will look on the custom background</p>
                    <button
                      class="sample-button"
                      :style="{
                        backgroundColor: color.value,
                        color: customBackgroundColor
                      }"
                    >
                      Sample Button
                    </button>
                  </div>
                </div>
                
                <div class="custom-test-results">
                  <div class="contrast-ratio">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                      {{ getContrastRatio(color.value, customBackgroundColor).toFixed(2) }}:1
                    </span>
                  </div>
                  
                  <div class="compliance-badges">
                    <span
                      v-for="test in getComplianceTests(color.value, customBackgroundColor)"
                      :key="test.name"
                      class="compliance-badge"
                      :class="getComplianceBadgeClass(test.passes)"
                    >
                      {{ test.name }}: {{ test.passes ? 'Pass' : 'Fail' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- WCAG Guidelines -->
          <div class="guidelines-section">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              WCAG Contrast Guidelines
            </h4>
            
            <div class="guidelines-grid">
              <div class="guideline-card">
                <div class="guideline-header">
                  <Icon name="information-circle" class="w-5 h-5 text-blue-600" />
                  <h5 class="font-medium text-gray-900 dark:text-white">AA Normal Text</h5>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Minimum contrast ratio of <strong>4.5:1</strong> for normal text (14pt and above)
                </p>
              </div>
              
              <div class="guideline-card">
                <div class="guideline-header">
                  <Icon name="information-circle" class="w-5 h-5 text-blue-600" />
                  <h5 class="font-medium text-gray-900 dark:text-white">AA Large Text</h5>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Minimum contrast ratio of <strong>3:1</strong> for large text (18pt+ or 14pt+ bold)
                </p>
              </div>
              
              <div class="guideline-card">
                <div class="guideline-header">
                  <Icon name="information-circle" class="w-5 h-5 text-green-600" />
                  <h5 class="font-medium text-gray-900 dark:text-white">AAA Normal Text</h5>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Enhanced contrast ratio of <strong>7:1</strong> for normal text (highest accessibility)
                </p>
              </div>
              
              <div class="guideline-card">
                <div class="guideline-header">
                  <Icon name="information-circle" class="w-5 h-5 text-green-600" />
                  <h5 class="font-medium text-gray-900 dark:text-white">AAA Large Text</h5>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Enhanced contrast ratio of <strong>4.5:1</strong> for large text (highest accessibility)
                </p>
              </div>
            </div>
          </div>

          <!-- Recommendations -->
          <div v-if="recommendations.length > 0" class="recommendations-section">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Recommendations
            </h4>
            
            <div class="recommendations-list">
              <div
                v-for="recommendation in recommendations"
                :key="recommendation.type"
                class="recommendation-item"
                :class="`recommendation-item--${recommendation.severity}`"
              >
                <div class="recommendation-icon">
                  <Icon
                    :name="getRecommendationIcon(recommendation.severity)"
                    class="w-5 h-5"
                  />
                </div>
                <div class="recommendation-content">
                  <h5 class="font-medium text-gray-900 dark:text-white">
                    {{ recommendation.title }}
                  </h5>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ recommendation.description }}
                  </p>
                  <div v-if="recommendation.suggestedColors" class="suggested-colors">
                    <p class="text-xs text-gray-500 mb-2">Suggested alternatives:</p>
                    <div class="color-suggestions">
                      <button
                        v-for="suggestedColor in recommendation.suggestedColors"
                        :key="suggestedColor"
                        class="color-suggestion"
                        :style="{ backgroundColor: suggestedColor }"
                        @click="applySuggestedColor(suggestedColor)"
                        :title="`Use ${suggestedColor}`"
                      ></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Learn more about <a href="https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html" target="_blank" class="text-blue-600 hover:text-blue-700">WCAG contrast guidelines</a>
          </div>
          <div class="flex gap-3">
            <button
              @click="exportReport"
              class="btn-secondary"
            >
              <Icon name="download" class="w-4 h-4 mr-2" />
              Export Report
            </button>
            <button
              @click="$emit('close')"
              class="btn-primary"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { BrandColor } from '@/types/components'

interface Props {
  color: BrandColor | null
  backgroundColors: BrandColor[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  applySuggestion: [color: string]
}>()

const customBackgroundColor = ref('#FFFFFF')

// Computed properties
const recommendations = computed(() => {
  if (!props.color) return []

  const recs: Array<{
    type: string
    severity: 'error' | 'warning' | 'info'
    title: string
    description: string
    suggestedColors?: string[]
  }> = []

  // Check if color fails against common backgrounds
  const commonBackgrounds = ['#FFFFFF', '#000000', '#F3F4F6', '#374151']
  const failingBackgrounds = commonBackgrounds.filter(bg => {
    const ratio = getContrastRatio(props.color!.value, bg)
    return ratio < 4.5
  })

  if (failingBackgrounds.length > 0) {
    recs.push({
      type: 'contrast',
      severity: 'warning',
      title: 'Low Contrast on Common Backgrounds',
      description: `This color may not meet accessibility standards on ${failingBackgrounds.length} common background colors.`,
      suggestedColors: generateContrastSuggestions(props.color.value)
    })
  }

  // Check if it's a primary color with poor contrast
  if (props.color.type === 'primary') {
    const whiteContrast = getContrastRatio(props.color.value, '#FFFFFF')
    const blackContrast = getContrastRatio(props.color.value, '#000000')
    
    if (whiteContrast < 4.5 && blackContrast < 4.5) {
      recs.push({
        type: 'primary-contrast',
        severity: 'error',
        title: 'Primary Color Accessibility Issue',
        description: 'Primary colors should have good contrast against both light and dark backgrounds for maximum versatility.',
        suggestedColors: generateContrastSuggestions(props.color.value)
      })
    }
  }

  return recs
})

// Methods
const getContrastRatio = (color1: string, color2: string): number => {
  const getLuminance = (hex: string): number => {
    const rgb = hex.replace('#', '').match(/.{2}/g)
    if (!rgb) return 0
    
    const [r, g, b] = rgb.map(x => {
      const val = parseInt(x, 16) / 255
      return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4)
    })
    
    return 0.2126 * r + 0.7152 * g + 0.0722 * b
  }

  const lum1 = getLuminance(color1)
  const lum2 = getLuminance(color2)
  const brightest = Math.max(lum1, lum2)
  const darkest = Math.min(lum1, lum2)
  
  return (brightest + 0.05) / (darkest + 0.05)
}

const getComplianceTests = (foreground: string, background: string) => {
  const ratio = getContrastRatio(foreground, background)
  
  return [
    { name: 'AA Normal', passes: ratio >= 4.5 },
    { name: 'AA Large', passes: ratio >= 3 },
    { name: 'AAA Normal', passes: ratio >= 7 },
    { name: 'AAA Large', passes: ratio >= 4.5 }
  ]
}

const getComplianceBadgeClass = (passes: boolean): string => {
  return passes 
    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
}

const getRecommendationIcon = (severity: string): string => {
  switch (severity) {
    case 'error':
      return 'x-circle'
    case 'warning':
      return 'exclamation-triangle'
    case 'info':
      return 'information-circle'
    default:
      return 'information-circle'
  }
}

const generateContrastSuggestions = (originalColor: string): string[] => {
  // Generate darker and lighter variants of the original color
  const suggestions: string[] = []
  
  // Convert hex to HSL for easier manipulation
  const hex = originalColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16) / 255
  const g = parseInt(hex.substr(2, 2), 16) / 255
  const b = parseInt(hex.substr(4, 2), 16) / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  let h = 0
  let s = 0
  const l = (max + min) / 2

  if (max !== min) {
    const d = max - min
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
    
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break
      case g: h = (b - r) / d + 2; break
      case b: h = (r - g) / d + 4; break
    }
    h /= 6
  }

  // Generate darker variants
  for (let i = 1; i <= 3; i++) {
    const newL = Math.max(0, l - (i * 0.15))
    const newColor = hslToHex(h, s, newL)
    suggestions.push(newColor)
  }

  // Generate lighter variants
  for (let i = 1; i <= 3; i++) {
    const newL = Math.min(1, l + (i * 0.15))
    const newColor = hslToHex(h, s, newL)
    suggestions.push(newColor)
  }

  return suggestions.filter(color => color !== originalColor)
}

const hslToHex = (h: number, s: number, l: number): string => {
  const hue2rgb = (p: number, q: number, t: number): number => {
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

  const toHex = (c: number): string => {
    const hex = Math.round(c * 255).toString(16)
    return hex.length === 1 ? '0' + hex : hex
  }

  return `#${toHex(r)}${toHex(g)}${toHex(b)}`
}

const applySuggestedColor = (color: string) => {
  emit('applySuggestion', color)
}

const exportReport = () => {
  if (!props.color) return

  const report = {
    color: props.color,
    contrastTests: props.backgroundColors.map(bg => ({
      background: bg,
      ratio: getContrastRatio(props.color!.value, bg.value),
      compliance: getComplianceTests(props.color!.value, bg.value)
    })),
    recommendations: recommendations.value,
    timestamp: new Date().toISOString()
  }

  const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `contrast-report-${props.color.name.toLowerCase().replace(/\s+/g, '-')}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.form-input {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.color-picker {
  @apply w-12 h-10 rounded-md border-2 border-gray-300 dark:border-gray-600 cursor-pointer;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background: none;
}

.color-picker::-webkit-color-swatch-wrapper {
  padding: 0;
}

.color-picker::-webkit-color-swatch {
  border: none;
  border-radius: 6px;
}

.color-info-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.color-display {
  @apply w-16 h-16 rounded-lg border-4 border-white shadow-lg;
}

.color-details {
  @apply flex-1;
}

.contrast-tests-section {
  @apply space-y-4;
}

.contrast-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.contrast-test-card {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.contrast-preview {
  @apply mb-4;
}

.contrast-sample {
  @apply rounded-lg p-4 border-2 border-gray-200;
}

.contrast-sample.large {
  @apply p-6;
}

.sample-text {
  @apply space-y-2;
}

.sample-button {
  @apply px-4 py-2 rounded-md font-medium mt-3;
}

.contrast-info {
  @apply space-y-4;
}

.bg-color-info {
  @apply flex items-center gap-3;
}

.bg-color-swatch {
  @apply w-6 h-6 rounded border border-gray-200;
}

.contrast-results {
  @apply space-y-2;
}

.contrast-ratio {
  @apply text-center;
}

.compliance-badges {
  @apply flex flex-wrap gap-2;
}

.compliance-badge {
  @apply px-2 py-1 text-xs font-medium rounded-full;
}

.custom-test-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.custom-test-controls {
  @apply space-y-4;
}

.color-input-group {
  @apply flex items-center gap-3;
}

.custom-test-preview {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.custom-test-results {
  @apply space-y-4;
}

.guidelines-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.guidelines-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.guideline-card {
  @apply bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600;
}

.guideline-header {
  @apply flex items-center gap-2 mb-2;
}

.recommendations-section {
  @apply space-y-4;
}

.recommendations-list {
  @apply space-y-4;
}

.recommendation-item {
  @apply flex gap-4 p-4 rounded-lg border;
}

.recommendation-item--error {
  @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800;
}

.recommendation-item--warning {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800;
}

.recommendation-item--info {
  @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800;
}

.recommendation-icon {
  @apply flex-shrink-0 mt-1;
}

.recommendation-content {
  @apply flex-1 space-y-2;
}

.suggested-colors {
  @apply space-y-2;
}

.color-suggestions {
  @apply flex gap-2;
}

.color-suggestion {
  @apply w-6 h-6 rounded border-2 border-white shadow-sm cursor-pointer hover:scale-110 transition-transform duration-200;
}
</style>