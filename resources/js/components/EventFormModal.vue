<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <!-- Header -->
              <div class="bg-white px-6 py-4 border-b">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium text-gray-900">
                    {{ isEditing ? 'Edit Event' : 'Create New Event' }}
                  </h3>
                  <button
                    @click="$emit('close')"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Form -->
              <form @submit.prevent="handleSubmit" class="bg-white">
                <div class="px-6 py-6 max-h-96 overflow-y-auto">
                  <div class="grid grid-cols-1 gap-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- Title -->
                      <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Event Title *
                        </label>
                        <input
                          v-model="form.title"
                          type="text"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Enter event title"
                        />
                      </div>

                      <!-- Type -->
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Event Type *
                        </label>
                        <select
                          v-model="form.type"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                          <option value="">Select type</option>
                          <option value="networking">Networking</option>
                          <option value="reunion">Reunion</option>
                          <option value="webinar">Webinar</option>
                          <option value="workshop">Workshop</option>
                          <option value="social">Social</option>
                          <option value="professional">Professional</option>
                          <option value="fundraising">Fundraising</option>
                          <option value="other">Other</option>
                        </select>
                      </div>

                      <!-- Format -->
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Format *
                        </label>
                        <select
                          v-model="form.format"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                          <option value="">Select format</option>
                          <option value="in_person">In Person</option>
                          <option value="virtual">Virtual</option>
                          <option value="hybrid">Hybrid</option>
                        </select>
                      </div>
                    </div>

                    <!-- Short Description -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Short Description
                      </label>
                      <input
                        v-model="form.short_description"
                        type="text"
                        maxlength="500"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Brief description for event cards"
                      />
                      <p class="mt-1 text-sm text-gray-500">
                        {{ form.short_description?.length || 0 }}/500 characters
                      </p>
                    </div>

                    <!-- Full Description -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Full Description *
                      </label>
                      <textarea
                        v-model="form.description"
                        rows="4"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Detailed event description"
                      ></textarea>
                    </div>

                    <!-- Date and Time -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Start Date & Time *
                        </label>
                        <input
                          v-model="form.start_date"
                          type="datetime-local"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          End Date & Time *
                        </label>
                        <input
                          v-model="form.end_date"
                          type="datetime-local"
                          required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                      </div>
                    </div>

                    <!-- Timezone -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Timezone *
                      </label>
                      <select
                        v-model="form.timezone"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">Eastern Time</option>
                        <option value="America/Chicago">Central Time</option>
                        <option value="America/Denver">Mountain Time</option>
                        <option value="America/Los_Angeles">Pacific Time</option>
                      </select>
                    </div>

                    <!-- Location (for in-person and hybrid events) -->
                    <div v-if="form.format !== 'virtual'" class="space-y-4">
                      <h4 class="text-lg font-medium text-gray-900">Location Details</h4>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Venue Name
                        </label>
                        <input
                          v-model="form.venue_name"
                          type="text"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Enter venue name"
                        />
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Address
                        </label>
                        <textarea
                          v-model="form.venue_address"
                          rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Enter full address"
                        ></textarea>
                      </div>
                    </div>

                    <!-- Virtual Meeting Configuration (for virtual and hybrid events) -->
                    <div v-if="form.format !== 'in_person'" class="space-y-4">
                      <h4 class="text-lg font-medium text-gray-900">Virtual Meeting Configuration</h4>
                      
                      <MeetingPlatformSelector
                        v-model="form.meeting_config"
                        @update:modelValue="handleMeetingConfigUpdate"
                      />
                    </div>

                    <!-- Registration Settings -->
                    <div class="space-y-4">
                      <h4 class="text-lg font-medium text-gray-900">Registration Settings</h4>
                      
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Capacity
                          </label>
                          <input
                            v-model.number="form.max_capacity"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Leave empty for unlimited"
                          />
                        </div>

                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-2">
                            Registration Deadline
                          </label>
                          <input
                            v-model="form.registration_deadline"
                            type="datetime-local"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                        </div>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ticket Price ($)
                          </label>
                          <input
                            v-model.number="form.ticket_price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="0.00 for free events"
                          />
                        </div>

                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-2">
                            Visibility
                          </label>
                          <select
                            v-model="form.visibility"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          >
                            <option value="public">Public</option>
                            <option value="alumni_only">Alumni Only</option>
                            <option value="institution_only">Institution Only</option>
                            <option value="private">Private</option>
                          </select>
                        </div>
                      </div>

                      <!-- Checkboxes -->
                      <div class="space-y-3">
                        <label class="flex items-center">
                          <input
                            v-model="form.requires_approval"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Require approval for registration</span>
                        </label>

                        <label class="flex items-center">
                          <input
                            v-model="form.allow_guests"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Allow attendees to bring guests</span>
                        </label>

                        <div v-if="form.allow_guests" class="ml-6">
                          <label class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum guests per attendee
                          </label>
                          <input
                            v-model.number="form.max_guests_per_attendee"
                            type="number"
                            min="1"
                            max="10"
                            class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                        </div>

                        <label class="flex items-center">
                          <input
                            v-model="form.enable_networking"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Enable networking features</span>
                        </label>

                        <label class="flex items-center">
                          <input
                            v-model="form.enable_checkin"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <span class="ml-2 text-sm text-gray-700">Enable check-in functionality</span>
                        </label>
                      </div>
                    </div>

                    <!-- Tags -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tags
                      </label>
                      <div class="space-y-2">
                        <input
                          v-model="tagInput"
                          type="text"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Add tags (press Enter to add)"
                          @keydown.enter.prevent="addTag"
                        />
                        
                        <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                          <span
                            v-for="tag in form.tags"
                            :key="tag"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                          >
                            {{ tag }}
                            <button
                              @click="removeTag(tag)"
                              type="button"
                              class="ml-1 text-blue-600 hover:text-blue-800"
                            >
                              <XMarkIcon class="h-3 w-3" />
                            </button>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <label class="flex items-center">
                      <input
                        v-model="form.status"
                        type="radio"
                        value="draft"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">Save as Draft</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        v-model="form.status"
                        type="radio"
                        value="published"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">Publish Event</span>
                    </label>
                  </div>

                  <div class="flex items-center space-x-3">
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      :disabled="loading"
                      class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                      <template v-if="loading">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                      </template>
                      <template v-else>
                        {{ isEditing ? 'Update Event' : 'Create Event' }}
                      </template>
                    </button>
                  </div>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useEventsStore } from '@/Stores/eventsStore'
import MeetingPlatformSelector from '@/Components/MeetingPlatformSelector.vue'

interface Event {
  id?: number
  title: string
  description: string
  short_description?: string
  type: string
  format: string
  start_date: string
  end_date: string
  timezone: string
  venue_name?: string
  venue_address?: string
  virtual_link?: string
  virtual_instructions?: string
  meeting_config?: {
    platform: string
    settings: any
  }
  max_capacity?: number
  requires_approval: boolean
  ticket_price?: number
  registration_deadline?: string
  visibility: string
  allow_guests: boolean
  max_guests_per_attendee: number
  enable_networking: boolean
  enable_checkin: boolean
  tags: string[]
  status: string
}

interface Props {
  show: boolean
  event?: Event | null
}

interface Emits {
  (e: 'close'): void
  (e: 'saved', event: Event): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const eventsStore = useEventsStore()

const loading = ref(false)
const tagInput = ref('')

const form = reactive<Event>({
  title: '',
  description: '',
  short_description: '',
  type: '',
  format: '',
  start_date: '',
  end_date: '',
  timezone: 'UTC',
  venue_name: '',
  venue_address: '',
  virtual_link: '',
  virtual_instructions: '',
  meeting_config: {
    platform: 'jitsi',
    settings: {}
  },
  max_capacity: undefined,
  requires_approval: false,
  ticket_price: undefined,
  registration_deadline: '',
  visibility: 'alumni_only',
  allow_guests: false,
  max_guests_per_attendee: 1,
  enable_networking: true,
  enable_checkin: true,
  tags: [],
  status: 'published'
})

const isEditing = computed(() => !!props.event?.id)

// Watch for event prop changes
watch(() => props.event, (newEvent) => {
  if (newEvent) {
    Object.assign(form, {
      ...newEvent,
      tags: [...(newEvent.tags || [])]
    })
  } else {
    resetForm()
  }
}, { immediate: true })

// Methods
const resetForm = () => {
  Object.assign(form, {
    title: '',
    description: '',
    short_description: '',
    type: '',
    format: '',
    start_date: '',
    end_date: '',
    timezone: 'UTC',
    venue_name: '',
    venue_address: '',
    virtual_link: '',
    virtual_instructions: '',
    meeting_config: {
      platform: 'jitsi',
      settings: {}
    },
    max_capacity: undefined,
    requires_approval: false,
    ticket_price: undefined,
    registration_deadline: '',
    visibility: 'alumni_only',
    allow_guests: false,
    max_guests_per_attendee: 1,
    enable_networking: true,
    enable_checkin: true,
    tags: [],
    status: 'published'
  })
  tagInput.value = ''
}

const addTag = () => {
  const tag = tagInput.value.trim()
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag)
    tagInput.value = ''
  }
}

const removeTag = (tag: string) => {
  const index = form.tags.indexOf(tag)
  if (index > -1) {
    form.tags.splice(index, 1)
  }
}

const handleMeetingConfigUpdate = (config: any) => {
  form.meeting_config = config
}

const handleSubmit = async () => {
  loading.value = true
  
  try {
    let savedEvent: Event
    
    if (isEditing.value) {
      savedEvent = await eventsStore.updateEvent(props.event!.id!, form)
    } else {
      savedEvent = await eventsStore.createEvent(form)
    }
    
    emit('saved', savedEvent)
  } catch (error) {
    console.error('Failed to save event:', error)
    alert('Failed to save event. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>