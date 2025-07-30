<template>
  <div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border p-8">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Become a Mentor</h2>
        <p class="text-gray-600">
          Share your experience and help fellow alumni advance their careers
        </p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Bio Section -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            About You
            <span class="text-red-500">*</span>
          </label>
          <textarea v-model="form.bio" rows="4"
            placeholder="Tell potential mentees about your background, experience, and what you can offer as a mentor..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-500': errors.bio }"></textarea>
          <div class="flex justify-between mt-1">
            <span v-if="errors.bio" class="text-sm text-red-600">{{ errors.bio }}</span>
            <span class="text-sm text-gray-500">{{ form.bio.length }}/1000 characters</span>
          </div>
        </div>

        <!-- Expertise Areas -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Areas of Expertise
            <span class="text-red-500">*</span>
          </label>
          <div class="space-y-2">
            <div class="flex flex-wrap gap-2 mb-3">
              <span v-for="area in form.expertise_areas" :key="area"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                {{ area }}
                <button type="button" @click="removeExpertiseArea(area)" class="ml-2 text-blue-600 hover:text-blue-800">
                  ×
                </button>
              </span>
            </div>

            <div class="flex space-x-2">
              <select v-model="selectedExpertiseArea"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select an area of expertise</option>
                <option v-for="area in availableExpertiseAreas" :key="area" :value="area">
                  {{ area }}
                </option>
              </select>
              <button type="button" @click="addExpertiseArea" :disabled="!selectedExpertiseArea"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                Add
              </button>
            </div>

            <div class="flex space-x-2">
              <input v-model="customExpertiseArea" type="text" placeholder="Or add a custom expertise area"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @keyup.enter="addCustomExpertiseArea" />
              <button type="button" @click="addCustomExpertiseArea" :disabled="!customExpertiseArea.trim()"
                class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                Add Custom
              </button>
            </div>
          </div>
          <span v-if="errors.expertise_areas" class="text-sm text-red-600">{{ errors.expertise_areas }}</span>
        </div>

        <!-- Availability -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Availability Level
            <span class="text-red-500">*</span>
          </label>
          <div class="space-y-3">
            <label class="flex items-start space-x-3 cursor-pointer">
              <input v-model="form.availability" type="radio" value="high"
                class="mt-1 text-blue-600 focus:ring-blue-500" />
              <div>
                <div class="font-medium text-gray-900">High Availability</div>
                <div class="text-sm text-gray-600">
                  Available for regular sessions, quick to respond to messages
                </div>
              </div>
            </label>

            <label class="flex items-start space-x-3 cursor-pointer">
              <input v-model="form.availability" type="radio" value="medium"
                class="mt-1 text-blue-600 focus:ring-blue-500" />
              <div>
                <div class="font-medium text-gray-900">Medium Availability</div>
                <div class="text-sm text-gray-600">
                  Available for monthly sessions, responds within a few days
                </div>
              </div>
            </label>

            <label class="flex items-start space-x-3 cursor-pointer">
              <input v-model="form.availability" type="radio" value="low"
                class="mt-1 text-blue-600 focus:ring-blue-500" />
              <div>
                <div class="font-medium text-gray-900">Low Availability</div>
                <div class="text-sm text-gray-600">
                  Available for occasional sessions, may take time to respond
                </div>
              </div>
            </label>
          </div>
          <span v-if="errors.availability" class="text-sm text-red-600">{{ errors.availability }}</span>
        </div>

        <!-- Maximum Mentees -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Maximum Number of Mentees
            <span class="text-red-500">*</span>
          </label>
          <select v-model="form.max_mentees"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-500': errors.max_mentees }">
            <option value="">Select maximum mentees</option>
            <option v-for="n in 10" :key="n" :value="n">{{ n }} mentee{{ n > 1 ? 's' : '' }}</option>
          </select>
          <p class="text-sm text-gray-600 mt-1">
            You can always adjust this number later based on your capacity
          </p>
          <span v-if="errors.max_mentees" class="text-sm text-red-600">{{ errors.max_mentees }}</span>
        </div>

        <!-- Mentorship Guidelines -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h3 class="font-medium text-blue-900 mb-2">Mentorship Guidelines</h3>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Commit to regular communication with your mentees</li>
            <li>• Provide constructive feedback and career guidance</li>
            <li>• Respect confidentiality and maintain professionalism</li>
            <li>• Be responsive and reliable in your commitments</li>
            <li>• Share your experiences and insights generously</li>
          </ul>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
          <button type="button" @click="$emit('cancel')"
            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
            Cancel
          </button>
          <button type="submit" :disabled="loading"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed">
            {{ loading ? 'Creating Profile...' : (isEditing ? 'Update Profile' : 'Become a Mentor') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  existingProfile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['submit', 'cancel'])

const loading = ref(false)
const selectedExpertiseArea = ref('')
const customExpertiseArea = ref('')

const form = ref({
  bio: '',
  expertise_areas: [],
  availability: 'medium',
  max_mentees: 3
})

const errors = ref({})

const expertiseAreas = ref([
  'Software Engineering',
  'Product Management',
  'Marketing',
  'Sales',
  'Finance',
  'Data Science',
  'Design',
  'Operations',
  'Consulting',
  'Entrepreneurship',
  'Healthcare',
  'Education',
  'Legal',
  'Human Resources',
  'Project Management',
  'Business Development',
  'Customer Success',
  'Quality Assurance',
  'DevOps',
  'Cybersecurity'
])

const isEditing = computed(() => !!props.existingProfile)

const availableExpertiseAreas = computed(() => {
  return expertiseAreas.value.filter(area => !form.value.expertise_areas.includes(area))
})

const addExpertiseArea = () => {
  if (selectedExpertiseArea.value && !form.value.expertise_areas.includes(selectedExpertiseArea.value)) {
    form.value.expertise_areas.push(selectedExpertiseArea.value)
    selectedExpertiseArea.value = ''
  }
}

const addCustomExpertiseArea = () => {
  const area = customExpertiseArea.value.trim()
  if (area && !form.value.expertise_areas.includes(area)) {
    form.value.expertise_areas.push(area)
    customExpertiseArea.value = ''
  }
}

const removeExpertiseArea = (area) => {
  const index = form.value.expertise_areas.indexOf(area)
  if (index > -1) {
    form.value.expertise_areas.splice(index, 1)
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.bio.trim()) {
    errors.value.bio = 'Bio is required'
  } else if (form.value.bio.length < 50) {
    errors.value.bio = 'Bio must be at least 50 characters'
  } else if (form.value.bio.length > 1000) {
    errors.value.bio = 'Bio must not exceed 1000 characters'
  }

  if (form.value.expertise_areas.length === 0) {
    errors.value.expertise_areas = 'At least one area of expertise is required'
  } else if (form.value.expertise_areas.length > 10) {
    errors.value.expertise_areas = 'Maximum 10 areas of expertise allowed'
  }

  if (!form.value.availability) {
    errors.value.availability = 'Availability level is required'
  }

  if (!form.value.max_mentees) {
    errors.value.max_mentees = 'Maximum mentees is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    const endpoint = isEditing.value ? '/api/mentorships/profile' : '/api/mentorships/become-mentor'
    const method = isEditing.value ? 'put' : 'post'
    
    const response = await axios[method](endpoint, form.value)

    emit('submit', response.data.profile)
  } catch (error) {
    console.error('Error saving mentor profile:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      alert(error.response?.data?.message || 'Failed to save mentor profile')
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.existingProfile) {
    form.value = {
      bio: props.existingProfile.bio,
      expertise_areas: [...props.existingProfile.expertise_areas],
      availability: props.existingProfile.availability,
      max_mentees: props.existingProfile.max_mentees
    }
  }
})
</script>