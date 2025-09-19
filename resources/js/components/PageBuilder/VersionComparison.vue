<template>
  <div class="version-comparison">
    <div class="mb-6">
      <h4 class="text-lg font-semibold mb-4">Compare Versions</h4>
      
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Version A
          </label>
          <select
            v-model="selectedVersion1"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option
              v-for="version in versions"
              :key="version.id"
              :value="version"
            >
              Version {{ version.version_number }} - {{ version.change_summary || 'No description' }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Version B
          </label>
          <select
            v-model="selectedVersion2"
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option
              v-for="version in versions"
              :key="version.id"
              :value="version"
            >
              Version {{ version.version_number }} - {{ version.change_summary || 'No description' }}
            </option>
          </select>
        </div>
      </div>

      <button
        @click="compareVersions"
        :disabled="!selectedVersion1 || !selectedVersion2 || isComparing"
        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <Icon v-if="isComparing" name="spinner" class="animate-spin w-4 h-4" />
        <Icon v-else name="compare" class="w-4 h-4" />
        {{ isComparing ? 'Comparing...' : 'Compare' }}
      </button>
    </div>

    <div v-if="comparison" class="comparison-results">
      <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Version A Details -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h5 class="font-semibold text-gray-900 mb-3">
            Version {{ comparison.version_1.number }}
          </h5>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Created:</span>
              <span>{{ formatDate(comparison.version_1.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Creator:</span>
              <span>{{ comparison.version_1.creator }}</span>
            </div>
          </div>
        </div>

        <!-- Version B Details -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h5 class="font-semibold text-gray-900 mb-3">
            Version {{ comparison.version_2.number }}
          </h5>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Created:</span>
              <span>{{ formatDate(comparison.version_2.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Creator:</span>
              <span>{{ comparison.version_2.creator }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Differences -->
      <div class="differences">
        <h5 class="font-semibold text-gray-900 mb-3">Differences</h5>
        
        <div v-if="Object.keys(comparison.differences).length === 0" class="text-center py-8">
          <Icon name="check-circle" class="w-12 h-12 text-green-500 mx-auto mb-3" />
          <p class="text-gray-600">No differences found</p>
          <p class="text-sm text-gray-500">These versions are identical</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(difference, key) in comparison.differences"
            :key="key"
            class="border border-yellow-200 bg-yellow-50 rounded-lg p-4"
          >
            <div class="flex items-center gap-2 mb-2">
              <Icon name="alert-triangle" class="w-5 h-5 text-yellow-600" />
              <span class="font-medium text-yellow-800 capitalize">{{ key }} Changed</span>
            </div>
            <p class="text-sm text-yellow-700">{{ difference }}</p>
          </div>
        </div>
      </div>

      <!-- Side-by-side Preview -->
      <div class="mt-8">
        <h5 class="font-semibold text-gray-900 mb-3">Visual Comparison</h5>
        
        <div class="grid grid-cols-2 gap-4">
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">
              Version {{ comparison.version_1.number }}
            </div>
            <div class="p-4 bg-white min-h-64">
              <div
                v-html="renderPreview(comparison.version_1.data)"
                class="version-preview"
              ></div>
            </div>
          </div>

          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700">
              Version {{ comparison.version_2.number }}
            </div>
            <div class="p-4 bg-white min-h-64">
              <div
                v-html="renderPreview(comparison.version_2.data)"
                class="version-preview"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
        <button
          @click="rollbackToVersion(selectedVersion1)"
          class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center gap-2"
        >
          <Icon name="arrow-left" class="w-4 h-4" />
          Rollback to Version {{ selectedVersion1?.version_number }}
        </button>
        
        <button
          @click="rollbackToVersion(selectedVersion2)"
          class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center gap-2"
        >
          <Icon name="arrow-left" class="w-4 h-4" />
          Rollback to Version {{ selectedVersion2?.version_number }}
        </button>
        
        <button
          @click="$emit('close')"
          class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 ml-auto"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useVersionControl } from '@/composables/useVersionControl'
import Icon from '@/components/ui/Icon.vue'

interface PageVersion {
  id: number
  version_number: number
  change_summary?: string
  is_published: boolean
  created_at: string
  published_at?: string
  creator: {
    id: number
    name: string
    email: string
  }
  grapejs_data: any
}

interface VersionComparison {
  version_1: {
    number: number
    created_at: string
    creator: string
    data: any
  }
  version_2: {
    number: number
    created_at: string
    creator: string
    data: any
  }
  differences: Record<string, string>
}

const props = defineProps<{
  versions: PageVersion[]
  selectedVersion?: PageVersion | null
}>()

const emit = defineEmits<{
  close: []
}>()

const selectedVersion1 = ref<PageVersion | null>(props.selectedVersion || null)
const selectedVersion2 = ref<PageVersion | null>(null)
const comparison = ref<VersionComparison | null>(null)
const isComparing = ref(false)

const { compareVersions: compareVersionsAction, rollbackToVersion } = useVersionControl(
  props.versions[0]?.page_id || 0
)

const compareVersions = async () => {
  if (!selectedVersion1.value || !selectedVersion2.value) return
  
  isComparing.value = true
  
  try {
    const result = await compareVersionsAction(selectedVersion1.value, selectedVersion2.value)
    comparison.value = result
  } catch (error) {
    console.error('Error comparing versions:', error)
  } finally {
    isComparing.value = false
  }
}

const renderPreview = (grapeJSData: any): string => {
  if (!grapeJSData) return '<p class="text-gray-500">No preview available</p>'
  
  // Simple preview rendering - in production you might want more sophisticated rendering
  const html = grapeJSData.html || ''
  const css = grapeJSData.css || ''
  
  return `
    <style scoped>
      ${css}
    </style>
    <div class="grapejs-preview">
      ${html}
    </div>
  `
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}

// Auto-select the second most recent version if only one is selected
if (selectedVersion1.value && !selectedVersion2.value && props.versions.length > 1) {
  const currentIndex = props.versions.findIndex(v => v.id === selectedVersion1.value?.id)
  if (currentIndex >= 0 && currentIndex < props.versions.length - 1) {
    selectedVersion2.value = props.versions[currentIndex + 1]
  } else if (currentIndex > 0) {
    selectedVersion2.value = props.versions[currentIndex - 1]
  }
}
</script>

<style scoped>
.version-preview {
  max-height: 300px;
  overflow-y: auto;
  font-size: 0.875rem;
}

.version-preview :deep(*) {
  max-width: 100%;
}

.version-preview :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>