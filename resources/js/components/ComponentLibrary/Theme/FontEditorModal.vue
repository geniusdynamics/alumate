<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        @click="$emit('cancel')"
      ></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-3xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ isNew ? 'Add New Font' : 'Edit Font' }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ isNew ? 'Add a new font to your brand typography' : 'Update font properties and settings' }}
            </p>
          </div>
          <button
            @click="$emit('cancel')"
            class="btn-icon"
          >
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="space-y-6">
            <!-- Font Preview -->
            <div class="font-preview-section">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Font Preview
              </label>
              <div class="font-preview-container" :style="{ fontFamily: previewFontFamily }">
                <div class="preview-sizes">
                  <h1 class="preview-heading">{{ form.name || 'Font Name' }}</h1>
                  <h2 class="preview-subheading">The quick brown fox jumps over the lazy dog</h2>
                  <p class="preview-body">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor 
                    incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis 
                    nostrud exercitation ullamco laboris.
                  </p>
                  <p class="preview-small">
                    Small text example with numbers: 1234567890
                  </p>
                </div>
              </div>
            </div>

            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Font Name *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="form-input"
                  placeholder="e.g., Inter"
                />
                <p class="text-xs text-gray-500 mt-1">
                  Display name for this font
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Font Type
                </label>
                <select
                  v-model="form.type"
                  class="form-select"
                >
                  <option value="heading">Heading</option>
                  <option value="body">Body</option>
                  <option value="display">Display</option>
                  <option value="monospace">Monospace</option>
                </select>
              </div>
            </div>

            <!-- Font Family and Source -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Font Family *
                </label>
                <input
                  v-model="form.family"
                  type="text"
                  required
                  class="form-input"
                  placeholder="e.g., 'Inter', sans-serif"
                  @input="updatePreview"
                />
                <p class="text-xs text-gray-500 mt-1">
                  CSS font-family value
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Font Source
                </label>
                <select
                  v-model="form.source"
                  class="form-select"
                  @change="handleSourceChange"
                >
                  <option value="google">Google Fonts</option>
                  <option value="adobe">Adobe Fonts</option>
                  <option value="custom">Custom Font</option>
                  <option value="system">System Font</option>
                </select>
              </div>
            </div>

            <!-- Google Fonts Integration -->
            <div v-if="form.source === 'google'" class="google-fonts-section">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Search Google Fonts
              </label>
              <div class="google-fonts-search">
                <input
                  v-model="googleFontSearch"
                  type="text"
                  class="form-input"
                  placeholder="Search Google Fonts..."
                  @input="searchGoogleFonts"
                />
                <div v-if="googleFontResults.length > 0" class="google-fonts-results">
                  <div
                    v-for="font in googleFontResults"
                    :key="font.family"
                    class="google-font-item"
                    @click="selectGoogleFont(font)"
                  >
                    <div class="font-info">
                      <h4 class="font-medium">{{ font.family }}</h4>
                      <p class="text-sm text-gray-600">{{ font.category }}</p>
                    </div>
                    <div class="font-sample" :style="{ fontFamily: font.family }">
                      Aa
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Custom Font Upload -->
            <div v-if="form.source === 'custom'" class="custom-font-section">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Upload Font Files
              </label>
              <div class="font-upload-area">
                <input
                  ref="fontFileInput"
                  type="file"
                  multiple
                  accept=".woff,.woff2,.ttf,.otf"
                  class="hidden"
                  @change="handleFontUpload"
                />
                <div
                  class="upload-dropzone"
                  @click="$refs.fontFileInput?.click()"
                  @dragover.prevent
                  @drop.prevent="handleFontDrop"
                >
                  <Icon name="cloud-upload" class="w-8 h-8 text-gray-400 mb-2" />
                  <p class="text-sm text-gray-600">
                    Click to upload or drag and drop font files
                  </p>
                  <p class="text-xs text-gray-500">
                    Supports WOFF, WOFF2, TTF, OTF formats
                  </p>
                </div>
                
                <div v-if="uploadedFontFiles.length > 0" class="uploaded-files">
                  <div
                    v-for="file in uploadedFontFiles"
                    :key="file.name"
                    class="uploaded-file-item"
                  >
                    <Icon name="document" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm">{{ file.name }}</span>
                    <button
                      type="button"
                      @click="removeUploadedFile(file)"
                      class="btn-icon-sm text-red-600"
                    >
                      <Icon name="x" class="w-3 h-3" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Font URL (for Adobe Fonts or custom) -->
            <div v-if="form.source === 'adobe' || (form.source === 'custom' && !uploadedFontFiles.length)">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Font URL
              </label>
              <input
                v-model="form.url"
                type="url"
                class="form-input"
                placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
              />
              <p class="text-xs text-gray-500 mt-1">
                URL to load the font from (CSS import or font file)
              </p>
            </div>

            <!-- Font Weights and Styles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Available Weights
                </label>
                <div class="font-weights-grid">
                  <label
                    v-for="weight in availableWeights"
                    :key="weight.value"
                    class="weight-checkbox"
                  >
                    <input
                      v-model="form.weights"
                      type="checkbox"
                      :value="weight.value"
                      class="form-checkbox"
                    />
                    <span class="ml-2 text-sm">{{ weight.label }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Available Styles
                </label>
                <div class="font-styles-grid">
                  <label
                    v-for="style in availableStyles"
                    :key="style"
                    class="style-checkbox"
                  >
                    <input
                      v-model="form.styles"
                      type="checkbox"
                      :value="style"
                      class="form-checkbox"
                    />
                    <span class="ml-2 text-sm">{{ style }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Fallback Fonts -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Fallback Fonts
              </label>
              <div class="fallback-fonts">
                <div
                  v-for="(fallback, index) in form.fallbacks"
                  :key="index"
                  class="fallback-item"
                >
                  <input
                    v-model="form.fallbacks[index]"
                    type="text"
                    class="form-input flex-1"
                    placeholder="e.g., Arial, sans-serif"
                  />
                  <button
                    type="button"
                    @click="removeFallback(index)"
                    class="btn-icon text-red-600"
                  >
                    <Icon name="trash" class="w-4 h-4" />
                  </button>
                </div>
                <button
                  type="button"
                  @click="addFallback"
                  class="btn-secondary mt-2"
                >
                  <Icon name="plus" class="w-4 h-4 mr-2" />
                  Add Fallback
                </button>
              </div>
            </div>

            <!-- Loading Strategy -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Loading Strategy
              </label>
              <select
                v-model="form.loadingStrategy"
                class="form-select"
              >
                <option value="preload">Preload (fastest, use for critical fonts)</option>
                <option value="swap">Swap (balanced, recommended)</option>
                <option value="lazy">Lazy (slowest, use for non-critical fonts)</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                How the font should be loaded to optimize performance
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <button
              type="button"
              @click="$emit('cancel')"
              class="btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="!isFormValid"
            >
              {{ isNew ? 'Add Font' : 'Update Font' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { BrandFont } from '@/types/components'

interface Props {
  font?: BrandFont | null
  isNew: boolean
}

const props = withDefaults(defineProps<Props>(), {
  font: null
})

const emit = defineEmits<{
  save: [fontData: any]
  cancel: []
}>()

// Form state
const form = ref({
  name: '',
  family: '',
  type: 'body' as const,
  source: 'google' as const,
  url: '',
  weights: [400] as number[],
  styles: ['normal'] as string[],
  fallbacks: ['Arial', 'sans-serif'] as string[],
  loadingStrategy: 'swap' as const
})

const googleFontSearch = ref('')
const googleFontResults = ref<any[]>([])
const uploadedFontFiles = ref<File[]>([])
const fontFileInput = ref<HTMLInputElement>()

// Available options
const availableWeights = [
  { value: 100, label: '100 - Thin' },
  { value: 200, label: '200 - Extra Light' },
  { value: 300, label: '300 - Light' },
  { value: 400, label: '400 - Regular' },
  { value: 500, label: '500 - Medium' },
  { value: 600, label: '600 - Semi Bold' },
  { value: 700, label: '700 - Bold' },
  { value: 800, label: '800 - Extra Bold' },
  { value: 900, label: '900 - Black' }
]

const availableStyles = ['normal', 'italic', 'oblique']

// Computed properties
const previewFontFamily = computed(() => {
  if (form.value.family) {
    return form.value.family
  }
  return 'Arial, sans-serif'
})

const isFormValid = computed(() => {
  return form.value.name.trim() !== '' && 
         form.value.family.trim() !== '' &&
         form.value.weights.length > 0 &&
         form.value.styles.length > 0
})

// Methods
const updatePreview = () => {
  // Load font if it's a Google Font or has a URL
  if (form.value.source === 'google' && form.value.url) {
    loadFontFromUrl(form.value.url)
  } else if (form.value.url) {
    loadFontFromUrl(form.value.url)
  }
}

const loadFontFromUrl = (url: string) => {
  // Check if font is already loaded
  const existingLink = document.querySelector(`link[href="${url}"]`)
  if (existingLink) return

  // Create and append font link
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = url
  document.head.appendChild(link)
}

const handleSourceChange = () => {
  // Reset relevant fields when source changes
  form.value.url = ''
  uploadedFontFiles.value = []
  googleFontResults.value = []
  googleFontSearch.value = ''
}

const searchGoogleFonts = async () => {
  if (!googleFontSearch.value.trim()) {
    googleFontResults.value = []
    return
  }

  try {
    // Mock Google Fonts API call - in real implementation, use actual API
    const mockResults = [
      { family: 'Inter', category: 'sans-serif' },
      { family: 'Roboto', category: 'sans-serif' },
      { family: 'Open Sans', category: 'sans-serif' },
      { family: 'Lato', category: 'sans-serif' },
      { family: 'Montserrat', category: 'sans-serif' }
    ].filter(font => 
      font.family.toLowerCase().includes(googleFontSearch.value.toLowerCase())
    )

    googleFontResults.value = mockResults
  } catch (error) {
    console.error('Failed to search Google Fonts:', error)
  }
}

const selectGoogleFont = (font: any) => {
  form.value.name = font.family
  form.value.family = `'${font.family}', ${font.category}`
  form.value.url = `https://fonts.googleapis.com/css2?family=${font.family.replace(' ', '+')}:wght@400;500;600;700&display=swap`
  googleFontResults.value = []
  googleFontSearch.value = ''
  
  // Load the font for preview
  loadFontFromUrl(form.value.url)
}

const handleFontUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    uploadedFontFiles.value = Array.from(target.files)
  }
}

const handleFontDrop = (event: DragEvent) => {
  if (event.dataTransfer?.files) {
    uploadedFontFiles.value = Array.from(event.dataTransfer.files)
  }
}

const removeUploadedFile = (file: File) => {
  uploadedFontFiles.value = uploadedFontFiles.value.filter(f => f !== file)
}

const addFallback = () => {
  form.value.fallbacks.push('')
}

const removeFallback = (index: number) => {
  form.value.fallbacks.splice(index, 1)
}

const handleSubmit = async () => {
  if (!isFormValid.value) return

  let fontUrl = form.value.url

  // Handle custom font upload
  if (form.value.source === 'custom' && uploadedFontFiles.value.length > 0) {
    try {
      const formData = new FormData()
      uploadedFontFiles.value.forEach(file => {
        formData.append('fonts[]', file)
      })

      const response = await fetch('/api/brand-customizer/fonts/upload', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: formData
      })

      if (response.ok) {
        const result = await response.json()
        fontUrl = result.fontUrl
      }
    } catch (error) {
      console.error('Failed to upload font files:', error)
      return
    }
  }

  const fontData = {
    name: form.value.name.trim(),
    family: form.value.family.trim(),
    type: form.value.type,
    source: form.value.source,
    url: fontUrl,
    weights: form.value.weights,
    styles: form.value.styles,
    fallbacks: form.value.fallbacks.filter(f => f.trim() !== ''),
    loadingStrategy: form.value.loadingStrategy
  }

  emit('save', fontData)
}

// Initialize form with existing font data
onMounted(() => {
  if (props.font) {
    form.value = {
      name: props.font.name,
      family: props.font.family,
      type: props.font.type,
      source: props.font.source,
      url: props.font.url || '',
      weights: props.font.weights,
      styles: props.font.styles,
      fallbacks: props.font.fallbacks,
      loadingStrategy: props.font.loadingStrategy
    }
  }
})

// Watch for font prop changes
watch(() => props.font, (newFont) => {
  if (newFont) {
    form.value = {
      name: newFont.name,
      family: newFont.family,
      type: newFont.type,
      source: newFont.source,
      url: newFont.url || '',
      weights: newFont.weights,
      styles: newFont.styles,
      fallbacks: newFont.fallbacks,
      loadingStrategy: newFont.loadingStrategy
    }
  }
})
</script>

<style scoped>
.btn-icon {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.btn-icon-sm {
  @apply p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-4 py-2 rounded-md font-medium transition-colors duration-200;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.form-input {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-select {
  @apply block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.form-checkbox {
  @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50;
}

.font-preview-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.font-preview-container {
  @apply bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.preview-sizes {
  @apply space-y-4;
}

.preview-heading {
  @apply text-3xl font-bold text-gray-900 dark:text-white;
}

.preview-subheading {
  @apply text-xl font-semibold text-gray-700 dark:text-gray-300;
}

.preview-body {
  @apply text-base text-gray-600 dark:text-gray-400 leading-relaxed;
}

.preview-small {
  @apply text-sm text-gray-500 dark:text-gray-500;
}

.google-fonts-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.google-fonts-search {
  @apply relative;
}

.google-fonts-results {
  @apply absolute top-full left-0 right-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto;
}

.google-font-item {
  @apply flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0;
}

.font-info {
  @apply flex-1;
}

.font-sample {
  @apply text-2xl font-medium text-gray-900 dark:text-white;
}

.custom-font-section {
  @apply bg-gray-50 dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600;
}

.upload-dropzone {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition-colors duration-200;
}

.uploaded-files {
  @apply mt-4 space-y-2;
}

.uploaded-file-item {
  @apply flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600;
}

.font-weights-grid {
  @apply grid grid-cols-2 gap-2;
}

.weight-checkbox {
  @apply flex items-center;
}

.font-styles-grid {
  @apply space-y-2;
}

.style-checkbox {
  @apply flex items-center;
}

.fallback-fonts {
  @apply space-y-2;
}

.fallback-item {
  @apply flex items-center gap-2;
}
</style>