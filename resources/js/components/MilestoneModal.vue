<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">
            {{ isEditing ? 'Edit Milestone' : 'Add New Milestone' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Type and Title -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                Milestone Type *
              </label>
              <select
                id="type"
                v-model="form.type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Select type</option>
                <option v-for="(label, value) in milestoneTypes" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>
            
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                Title *
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Milestone title"
              />
            </div>
          </div>

          <!-- Date and Visibility -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                Date *
              </label>
              <input
                id="date"
                v-model="form.date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            
            <div>
              <label for="visibility" class="block text-sm font-medium text-gray-700 mb-2">
                Visibility *
              </label>
              <select
                id="visibility"
                v-model="form.visibility"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="(label, value) in visibilityOptions" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Company and Organization -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="company" class="block text-sm font-medium text-gray-700 mb-2">
                Company
              </label>
              <input
                id="company"
                v-model="form.company"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Company name"
              />
            </div>
            
            <div>
              <label for="organization" class="block text-sm font-medium text-gray-700 mb-2">
                Organization
              </label>
              <input
                id="organization"
                v-model="form.organization"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Organization name"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Describe your milestone..."
            ></textarea>
          </div>

          <!-- Featured checkbox -->
          <div class="flex items-center">
            <input
              id="is_featured"
              v-model="form.is_featured"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="is_featured" class="ml-2 block text-sm text-gray-700">
              Feature this milestone (will be highlighted on your profile)
            </label>
          </div>

          <!-- Metadata -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Additional Details
            </label>
            <div class="space-y-3">
              <div
                v-for="(value, key, index) in form.metadata"
                :key="index"
                class="flex items-center space-x-2"
              >
                <input
                  v-model="metadataKeys[index]"
                  type="text"
                  placeholder="Field name"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <input
                  v-model="form.metadata[key]"
                  type="text"
                  placeholder="Value"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <button
                  type="button"
                  @click="removeMetadata(key)"
                  class="p-2 text-red-500 hover:text-red-700 transition-colors"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
              <button
                type="button"
                @click="addMetadata"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                Add Detail
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ loading ? 'Saving...' : (isEditing ? 'Update Milestone' : 'Add Milestone') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { XMarkIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  milestone: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// Reactive data
const loading = ref(false)
const milestoneTypes = ref({})
const visibilityOptions = ref({})
const metadataKeys = ref([])

const form = ref({
  type: '',
  title: '',
  description: '',
  date: '',
  visibility: 'public',
  company: '',
  organization: '',
  metadata: {},
  is_featured: false
})

// Computed
const isEditing = computed(() => !!props.milestone)

// Methods
const loadOptions = async () => {
  try {
    const response = await fetch('/api/career/options', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      milestoneTypes.value = data.data.milestone_types
      visibilityOptions.value = data.data.visibility_options
    }
  } catch (err) {
    console.error('Error loading options:', err)
  }
}

const addMetadata = () => {
  const key = `field_${Date.now()}`
  form.value.metadata[key] = ''
  metadataKeys.value.push(key)
}

const removeMetadata = (key) => {
  delete form.value.metadata[key]
  const index = metadataKeys.value.indexOf(key)
  if (index > -1) {
    metadataKeys.value.splice(index, 1)
  }
}

const handleSubmit = async () => {
  try {
    loading.value = true

    // Rebuild metadata with current keys
    const newMetadata = {}
    metadataKeys.value.forEach((key, index) => {
      const newKey = key.startsWith('field_') ? `custom_${index + 1}` : key
      if (form.value.metadata[key]) {
        newMetadata[newKey] = form.value.metadata[key]
      }
    })

    const url = isEditing.value 
      ? `/api/milestones/${props.milestone.id}`
      : '/api/milestones'
    
    const method = isEditing.value ? 'PUT' : 'POST'

    const formData = {
      ...form.value,
      metadata: newMetadata
    }

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(formData)
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Failed to save milestone')
    }

    emit('saved')
  } catch (err) {
    console.error('Error saving milestone:', err)
    alert(err.message || 'Failed to save milestone')
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.milestone, (newMilestone) => {
  if (newMilestone) {
    form.value = {
      type: newMilestone.type || '',
      title: newMilestone.title || '',
      description: newMilestone.description || '',
      date: newMilestone.date || '',
      visibility: newMilestone.visibility || 'public',
      company: newMilestone.company || '',
      organization: newMilestone.organization || '',
      metadata: newMilestone.metadata || {},
      is_featured: newMilestone.is_featured || false
    }
    
    // Set up metadata keys
    metadataKeys.value = Object.keys(newMilestone.metadata || {})
  } else {
    // Reset form for new milestone
    form.value = {
      type: '',
      title: '',
      description: '',
      date: '',
      visibility: 'public',
      company: '',
      organization: '',
      metadata: {},
      is_featured: false
    }
    metadataKeys.value = []
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadOptions()
})
</script>