<template>
  <div class="version-control-panel bg-white border-l border-gray-200 w-80 h-full overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900">Version Control</h3>
      <p class="text-sm text-gray-600 mt-1">Manage page versions and history</p>
    </div>

    <!-- Actions -->
    <div class="p-4 border-b border-gray-200 space-y-3">
      <button
        @click="createVersion"
        :disabled="isCreatingVersion"
        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <Icon v-if="isCreatingVersion" name="spinner" class="animate-spin w-4 h-4" />
        <Icon v-else name="save" class="w-4 h-4" />
        {{ isCreatingVersion ? 'Creating...' : 'Create Version' }}
      </button>

      <div class="flex gap-2">
        <button
          @click="toggleAutoSave"
          :class="[
            'flex-1 px-3 py-2 rounded-md text-sm font-medium transition-colors',
            autoSaveEnabled
              ? 'bg-green-100 text-green-800 hover:bg-green-200'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          <Icon :name="autoSaveEnabled ? 'check-circle' : 'pause-circle'" class="w-4 h-4 mr-1" />
          Auto-save {{ autoSaveEnabled ? 'On' : 'Off' }}
        </button>
      </div>
    </div>

    <!-- Version History -->
    <div class="flex-1 overflow-y-auto">
      <div class="p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Version History</h4>
        
        <div v-if="isLoadingVersions" class="space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="bg-gray-200 h-16 rounded-md"></div>
          </div>
        </div>

        <div v-else-if="versions.length === 0" class="text-center py-8">
          <Icon name="clock" class="w-12 h-12 text-gray-400 mx-auto mb-3" />
          <p class="text-gray-500">No versions yet</p>
          <p class="text-sm text-gray-400">Create your first version to get started</p>
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="version in versions"
            :key="version.id"
            :class="[
              'border rounded-md p-3 cursor-pointer transition-colors',
              selectedVersion?.id === version.id
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
            ]"
            @click="selectVersion(version)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium text-gray-900">
                    Version {{ version.version_number }}
                  </span>
                  <span
                    v-if="version.is_published"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                  >
                    <Icon name="check-circle" class="w-3 h-3 mr-1" />
                    Published
                  </span>
                </div>
                
                <p v-if="version.change_summary" class="text-sm text-gray-600 mt-1 truncate">
                  {{ version.change_summary }}
                </p>
                
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                  <span>{{ version.creator.name }}</span>
                  <span>{{ formatDate(version.created_at) }}</span>
                </div>
              </div>

              <div class="flex items-center gap-1 ml-2">
                <button
                  v-if="!version.is_published"
                  @click.stop="publishVersion(version)"
                  :disabled="isPublishing"
                  class="p-1 text-gray-400 hover:text-green-600 disabled:opacity-50"
                  title="Publish this version"
                >
                  <Icon name="upload" class="w-4 h-4" />
                </button>
                
                <button
                  @click.stop="rollbackToVersion(version)"
                  :disabled="isRollingBack"
                  class="p-1 text-gray-400 hover:text-blue-600 disabled:opacity-50"
                  title="Rollback to this version"
                >
                  <Icon name="arrow-left" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Version Details -->
    <div v-if="selectedVersion" class="border-t border-gray-200 p-4">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Version Details</h4>
      
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Version:</span>
          <span class="font-medium">{{ selectedVersion.version_number }}</span>
        </div>
        
        <div class="flex justify-between">
          <span class="text-gray-600">Created by:</span>
          <span class="font-medium">{{ selectedVersion.creator.name }}</span>
        </div>
        
        <div class="flex justify-between">
          <span class="text-gray-600">Created:</span>
          <span class="font-medium">{{ formatDate(selectedVersion.created_at) }}</span>
        </div>
        
        <div v-if="selectedVersion.published_at" class="flex justify-between">
          <span class="text-gray-600">Published:</span>
          <span class="font-medium">{{ formatDate(selectedVersion.published_at) }}</span>
        </div>
      </div>

      <div class="mt-4 flex gap-2">
        <button
          @click="previewVersion(selectedVersion)"
          class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md text-sm hover:bg-gray-200"
        >
          <Icon name="eye" class="w-4 h-4 mr-1" />
          Preview
        </button>
        
        <button
          v-if="versions.length > 1"
          @click="showCompareModal = true"
          class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md text-sm hover:bg-gray-200"
        >
          <Icon name="compare" class="w-4 h-4 mr-1" />
          Compare
        </button>
      </div>
    </div>

    <!-- Create Version Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Create New Version</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Change Summary
            </label>
            <input
              v-model="newVersionSummary"
              type="text"
              placeholder="Describe what changed..."
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button
            @click="showCreateModal = false"
            class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200"
          >
            Cancel
          </button>
          <button
            @click="confirmCreateVersion"
            :disabled="isCreatingVersion"
            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            Create Version
          </button>
        </div>
      </div>
    </div>

    <!-- Compare Modal -->
    <div v-if="showCompareModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-full overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Compare Versions</h3>
          <button
            @click="showCompareModal = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <Icon name="x" class="w-6 h-6" />
          </button>
        </div>
        
        <VersionComparison
          :versions="versions"
          :selected-version="selectedVersion"
          @close="showCompareModal = false"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useVersionControl } from '@/composables/useVersionControl'
import { usePageBuilder } from '@/composables/usePageBuilder'
import Icon from '@/components/ui/Icon.vue'
import VersionComparison from './VersionComparison.vue'

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

const props = defineProps<{
  pageId: number
}>()

const emit = defineEmits<{
  versionSelected: [version: PageVersion]
  versionPublished: [version: PageVersion]
}>()

const {
  versions,
  isLoadingVersions,
  isCreatingVersion,
  isPublishing,
  isRollingBack,
  autoSaveEnabled,
  loadVersions,
  createVersion: createVersionAction,
  publishVersion: publishVersionAction,
  rollbackToVersion: rollbackAction,
  toggleAutoSave,
} = useVersionControl(props.pageId)

const { getCurrentPageData } = usePageBuilder()

const selectedVersion = ref<PageVersion | null>(null)
const showCreateModal = ref(false)
const showCompareModal = ref(false)
const newVersionSummary = ref('')

onMounted(() => {
  loadVersions()
})

const selectVersion = (version: PageVersion) => {
  selectedVersion.value = version
  emit('versionSelected', version)
}

const createVersion = () => {
  showCreateModal.value = true
  newVersionSummary.value = ''
}

const confirmCreateVersion = async () => {
  const pageData = getCurrentPageData()
  
  await createVersionAction(pageData, newVersionSummary.value || undefined)
  
  showCreateModal.value = false
  newVersionSummary.value = ''
}

const publishVersion = async (version: PageVersion) => {
  const published = await publishVersionAction(version)
  if (published) {
    emit('versionPublished', published)
  }
}

const rollbackToVersion = async (version: PageVersion) => {
  const rolledBack = await rollbackAction(version)
  if (rolledBack) {
    // Reload the page builder with the rolled back data
    window.location.reload()
  }
}

const previewVersion = (version: PageVersion) => {
  // Open preview in new tab/window
  const previewUrl = `/pages/${props.pageId}/preview?version=${version.version_number}`
  window.open(previewUrl, '_blank')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}
</script>

<style scoped>
.version-control-panel {
  min-height: 0;
}
</style>