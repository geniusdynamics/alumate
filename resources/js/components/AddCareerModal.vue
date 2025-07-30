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
            {{ isEditing ? 'Edit Position' : 'Add New Position' }}
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
          <!-- Company and Title -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="company" class="block text-sm font-medium text-gray-700 mb-2">
                Company *
              </label>
              <input
                id="company"
                v-model="form.company"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Company name"
              />
            </div>
            
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                Job Title *
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Job title"
              />
            </div>
          </div>

          <!-- Employment Type and Industry -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-2">
                Employment Type
              </label>
              <select
                id="employment_type"
                v-model="form.employment_type"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Select type</option>
                <option v-for="(label, value) in employmentTypes" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>
            
            <div>
              <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                Industry
              </label>
              <input
                id="industry"
                v-model="form.industry"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Industry"
              />
            </div>
          </div>

          <!-- Location -->
          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
              Location
            </label>
            <input
              id="location"
              v-model="form.location"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="City, State, Country"
            />
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                Start Date *
              </label>
              <input
                id="start_date"
                v-model="form.start_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            
            <div>
              <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                End Date
              </label>
              <input
                id="end_date"
                v-model="form.end_date"
                type="date"
                :disabled="form.is_current"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
              />
            </div>
          </div>

          <!-- Current position checkbox -->
          <div class="flex items-center">
            <input
              id="is_current"
              v-model="form.is_current"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              @change="handleCurrentChange"
            />
            <label for="is_current" class="ml-2 block text-sm text-gray-700">
              This is my current position
            </label>
          </div>

          <!-- Company Logo URL -->
          <div>
            <label for="company_logo_url" class="block text-sm font-medium text-gray-700 mb-2">
              Company Logo URL
            </label>
            <input
              id="company_logo_url"
              v-model="form.company_logo_url"
              type="url"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="https://example.com/logo.png"
            />
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
              placeholder="Describe your role and responsibilities..."
            ></textarea>
          </div>

          <!-- Achievements -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Key Achievements
            </label>
            <div class="space-y-2">
              <div
                v-for="(achievement, index) in form.achievements"
                :key="index"
                class="flex items-center space-x-2"
              >
                <input
                  v-model="form.achievements[index]"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Achievement description"
                />
                <button
                  type="button"
                  @click="removeAchievement(index)"
                  class="p-2 text-red-500 hover:text-red-700 transition-colors"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
              <button
                type="button"
                @click="addAchievement"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                Add Achievement
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
              {{ loading ? 'Saving...' : (isEditing ? 'Update Position' : 'Add Position') }}
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
  careerEntry: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// Reactive data
const loading = ref(false)
const employmentTypes = ref({})

const form = ref({
  company: '',
  title: '',
  start_date: '',
  end_date: '',
  description: '',
  is_current: false,
  achievements: [],
  location: '',
  company_logo_url: '',
  industry: '',
  employment_type: 'full-time'
})

// Computed
const isEditing = computed(() => !!props.careerEntry)

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
      employmentTypes.value = data.data.employment_types
    }
  } catch (err) {
    console.error('Error loading options:', err)
  }
}

const handleCurrentChange = () => {
  if (form.value.is_current) {
    form.value.end_date = ''
  }
}

const addAchievement = () => {
  form.value.achievements.push('')
}

const removeAchievement = (index) => {
  form.value.achievements.splice(index, 1)
}

const handleSubmit = async () => {
  try {
    loading.value = true

    const url = isEditing.value 
      ? `/api/career/${props.careerEntry.id}`
      : '/api/career'
    
    const method = isEditing.value ? 'PUT' : 'POST'

    // Filter out empty achievements
    const formData = {
      ...form.value,
      achievements: form.value.achievements.filter(a => a.trim() !== '')
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
      throw new Error(errorData.message || 'Failed to save career entry')
    }

    emit('saved')
  } catch (err) {
    console.error('Error saving career entry:', err)
    alert(err.message || 'Failed to save career entry')
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.careerEntry, (newEntry) => {
  if (newEntry) {
    form.value = {
      company: newEntry.company || '',
      title: newEntry.title || '',
      start_date: newEntry.start_date || '',
      end_date: newEntry.end_date || '',
      description: newEntry.description || '',
      is_current: newEntry.is_current || false,
      achievements: newEntry.achievements || [],
      location: newEntry.location || '',
      company_logo_url: newEntry.company_logo_url || '',
      industry: newEntry.industry || '',
      employment_type: newEntry.employment_type || 'full-time'
    }
  } else {
    // Reset form for new entry
    form.value = {
      company: '',
      title: '',
      start_date: '',
      end_date: '',
      description: '',
      is_current: false,
      achievements: [],
      location: '',
      company_logo_url: '',
      industry: '',
      employment_type: 'full-time'
    }
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadOptions()
})
</script>