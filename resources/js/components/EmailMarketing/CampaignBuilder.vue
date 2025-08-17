<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
              {{ campaign ? 'Edit Campaign' : 'Create New Campaign' }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveCampaign" class="space-y-6">
            <!-- Step Navigation -->
            <div class="flex items-center justify-center mb-8">
              <nav class="flex space-x-4">
                <button
                  v-for="(step, index) in steps"
                  :key="step.id"
                  type="button"
                  @click="currentStep = index"
                  :class="[
                    'px-3 py-2 text-sm font-medium rounded-md',
                    currentStep === index
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                  ]"
                >
                  {{ step.title }}
                </button>
              </nav>
            </div>

            <!-- Step 1: Basic Information -->
            <div v-if="currentStep === 0" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Campaign Name
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="Enter campaign name"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Description
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="Brief description of the campaign"
                ></textarea>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Campaign Type
                  </label>
                  <select
                    v-model="form.type"
                    required
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  >
                    <option value="">Select type</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="announcement">Announcement</option>
                    <option value="event">Event</option>
                    <option value="fundraising">Fundraising</option>
                    <option value="engagement">Engagement</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email Provider
                  </label>
                  <select
                    v-model="form.provider"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  >
                    <option value="internal">Internal</option>
                    <option value="mailchimp">Mailchimp</option>
                    <option value="constant_contact">Constant Contact</option>
                    <option value="mautic">Mautic</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Subject Line
                </label>
                <input
                  v-model="form.subject"
                  type="text"
                  required
                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="Enter email subject line"
                />
              </div>
            </div>

            <!-- Step 2: Content -->
            <div v-if="currentStep === 1" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Email Content
                </label>
                <div class="border border-gray-300 dark:border-gray-600 rounded-md">
                  <div class="bg-gray-50 dark:bg-gray-700 px-3 py-2 border-b border-gray-300 dark:border-gray-600">
                    <div class="flex items-center space-x-2">
                      <button
                        type="button"
                        @click="insertVariable('{{first_name}}')"
                        class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded"
                      >
                        First Name
                      </button>
                      <button
                        type="button"
                        @click="insertVariable('{{full_name}}')"
                        class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded"
                      >
                        Full Name
                      </button>
                      <button
                        type="button"
                        @click="insertVariable('{{current_role}}')"
                        class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded"
                      >
                        Current Role
                      </button>
                      <button
                        type="button"
                        @click="insertVariable('{{recent_posts}}')"
                        class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded"
                      >
                        Recent Posts
                      </button>
                    </div>
                  </div>
                  <textarea
                    ref="contentTextarea"
                    v-model="form.content"
                    rows="12"
                    required
                    class="block w-full border-0 resize-none focus:ring-0 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="Enter your email content here. Use the buttons above to insert personalization variables."
                  ></textarea>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Personalization Rules
                </label>
                <div class="mt-2 space-y-2">
                  <div
                    v-for="(rule, index) in form.personalization_rules"
                    :key="index"
                    class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700 rounded"
                  >
                    <select
                      v-model="rule.type"
                      class="flex-1 text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    >
                      <option value="recent_posts">Recent Posts</option>
                      <option value="career_milestone">Career Milestone</option>
                      <option value="upcoming_events">Upcoming Events</option>
                    </select>
                    <button
                      type="button"
                      @click="removePersonalizationRule(index)"
                      class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                  <button
                    type="button"
                    @click="addPersonalizationRule"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    + Add Personalization Rule
                  </button>
                </div>
              </div>
            </div>

            <!-- Step 3: Audience -->
            <div v-if="currentStep === 2" class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Target Audience
                </h4>
                
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">
                      Graduation Years
                    </label>
                    <input
                      v-model="graduationYearsInput"
                      type="text"
                      class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                      placeholder="e.g., 2020,2021,2022 or leave empty for all"
                    />
                  </div>

                  <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">
                      Industries
                    </label>
                    <input
                      v-model="industriesInput"
                      type="text"
                      class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                      placeholder="e.g., Technology,Healthcare,Finance or leave empty for all"
                    />
                  </div>

                  <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">
                      Locations
                    </label>
                    <input
                      v-model="locationsInput"
                      type="text"
                      class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                      placeholder="e.g., New York,California,Texas or leave empty for all"
                    />
                  </div>

                  <div>
                    <label class="flex items-center">
                      <input
                        v-model="form.audience_criteria.engagement_level"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        Only highly engaged alumni (active in last 30 days)
                      </span>
                    </label>
                  </div>
                </div>

                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900 rounded-md">
                  <p class="text-sm text-blue-700 dark:text-blue-300">
                    Estimated recipients: {{ estimatedRecipients }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Step 4: Schedule -->
            <div v-if="currentStep === 3" class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Sending Options
                </h4>
                
                <div class="space-y-3">
                  <label class="flex items-center">
                    <input
                      v-model="sendOption"
                      type="radio"
                      value="now"
                      class="text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      Send immediately
                    </span>
                  </label>
                  
                  <label class="flex items-center">
                    <input
                      v-model="sendOption"
                      type="radio"
                      value="schedule"
                      class="text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      Schedule for later
                    </span>
                  </label>
                  
                  <label class="flex items-center">
                    <input
                      v-model="sendOption"
                      type="radio"
                      value="draft"
                      class="text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      Save as draft
                    </span>
                  </label>
                </div>

                <div v-if="sendOption === 'schedule'" class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Schedule Date & Time
                  </label>
                  <input
                    v-model="form.scheduled_at"
                    type="datetime-local"
                    :min="minDateTime"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label class="flex items-center">
                  <input
                    v-model="form.is_ab_test"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  />
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Create A/B test variant
                  </span>
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  You can create a variant with different subject line or content after saving
                </p>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
              <button
                v-if="currentStep > 0"
                type="button"
                @click="currentStep--"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Previous
              </button>
              <div v-else></div>

              <div class="flex space-x-3">
                <button
                  type="button"
                  @click="$emit('close')"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  Cancel
                </button>
                
                <button
                  v-if="currentStep < steps.length - 1"
                  type="button"
                  @click="currentStep++"
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  Next
                </button>
                
                <button
                  v-else
                  type="submit"
                  :disabled="saving"
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ saving ? 'Saving...' : (sendOption === 'now' ? 'Send Campaign' : 'Save Campaign') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  campaign: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// Reactive data
const currentStep = ref(0)
const saving = ref(false)
const sendOption = ref('draft')
const estimatedRecipients = ref(0)
const graduationYearsInput = ref('')
const industriesInput = ref('')
const locationsInput = ref('')

const steps = [
  { id: 'basic', title: 'Basic Info' },
  { id: 'content', title: 'Content' },
  { id: 'audience', title: 'Audience' },
  { id: 'schedule', title: 'Schedule' }
]

const form = ref({
  name: '',
  description: '',
  subject: '',
  content: '',
  type: '',
  provider: 'internal',
  audience_criteria: {},
  personalization_rules: [],
  scheduled_at: '',
  is_ab_test: false
})

// Computed properties
const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + 30) // Minimum 30 minutes from now
  return now.toISOString().slice(0, 16)
})

// Methods
const insertVariable = (variable) => {
  const textarea = document.querySelector('textarea[ref="contentTextarea"]')
  if (textarea) {
    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const text = form.value.content
    form.value.content = text.substring(0, start) + variable + text.substring(end)
    
    // Set cursor position after inserted variable
    setTimeout(() => {
      textarea.focus()
      textarea.setSelectionRange(start + variable.length, start + variable.length)
    }, 0)
  }
}

const addPersonalizationRule = () => {
  form.value.personalization_rules.push({ type: 'recent_posts' })
}

const removePersonalizationRule = (index) => {
  form.value.personalization_rules.splice(index, 1)
}

const updateAudienceCriteria = () => {
  const criteria = {}
  
  if (graduationYearsInput.value) {
    criteria.graduation_years = graduationYearsInput.value.split(',').map(year => parseInt(year.trim())).filter(year => !isNaN(year))
  }
  
  if (industriesInput.value) {
    criteria.industries = industriesInput.value.split(',').map(industry => industry.trim())
  }
  
  if (locationsInput.value) {
    criteria.locations = locationsInput.value.split(',').map(location => location.trim())
  }
  
  form.value.audience_criteria = criteria
  
  // Estimate recipients (mock calculation)
  estimatedRecipients.value = Math.floor(Math.random() * 500) + 100
}

const saveCampaign = async () => {
  try {
    saving.value = true
    
    const url = props.campaign 
      ? `/api/email-campaigns/${props.campaign.id}`
      : '/api/email-campaigns'
    
    const method = props.campaign ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(form.value)
    })

    if (response.ok) {
      const data = await response.json()
      
      // Handle sending options
      if (sendOption.value === 'now') {
        await sendCampaignNow(data.campaign.id)
      } else if (sendOption.value === 'schedule') {
        await scheduleCampaign(data.campaign.id)
      }
      
      emit('saved', data.campaign)
    } else {
      const error = await response.json()
      alert('Failed to save campaign: ' + error.message)
    }
  } catch (error) {
    console.error('Failed to save campaign:', error)
    alert('Failed to save campaign')
  } finally {
    saving.value = false
  }
}

const sendCampaignNow = async (campaignId) => {
  const response = await fetch(`/api/email-campaigns/${campaignId}/send`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  
  if (!response.ok) {
    const error = await response.json()
    throw new Error(error.message)
  }
}

const scheduleCampaign = async (campaignId) => {
  const response = await fetch(`/api/email-campaigns/${campaignId}/schedule`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ scheduled_at: form.value.scheduled_at })
  })
  
  if (!response.ok) {
    const error = await response.json()
    throw new Error(error.message)
  }
}

// Watchers
watch([graduationYearsInput, industriesInput, locationsInput], updateAudienceCriteria)

// Lifecycle
onMounted(() => {
  if (props.campaign) {
    form.value = { ...props.campaign }
    
    // Populate input fields from audience criteria
    if (props.campaign.audience_criteria) {
      const criteria = props.campaign.audience_criteria
      if (criteria.graduation_years) {
        graduationYearsInput.value = criteria.graduation_years.join(',')
      }
      if (criteria.industries) {
        industriesInput.value = criteria.industries.join(',')
      }
      if (criteria.locations) {
        locationsInput.value = criteria.locations.join(',')
      }
    }
  }
  
  updateAudienceCriteria()
})
</script>