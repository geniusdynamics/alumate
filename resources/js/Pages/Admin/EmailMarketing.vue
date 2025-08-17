<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              Email Marketing
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              Create and manage email campaigns for alumni engagement
            </p>
          </div>
          <div class="flex space-x-3">
            <button
              @click="showTemplateModal = true"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Templates
            </button>
            <button
              @click="showAutomationModal = true"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              Automation
            </button>
            <button
              @click="showCampaignModal = true"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              New Campaign
            </button>
          </div>
        </div>
      </div>

      <!-- Analytics Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Campaigns</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ analytics.total_campaigns || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sent Campaigns</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ analytics.total_sent || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Open Rate</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ (analytics.average_open_rate || 0).toFixed(1) }}%
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Click Rate</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ (analytics.average_click_rate || 0).toFixed(1) }}%
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div class="flex-1 max-w-lg">
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search campaigns..."
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
            <div class="flex space-x-3">
              <select
                v-model="selectedType"
                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">All Types</option>
                <option value="newsletter">Newsletter</option>
                <option value="announcement">Announcement</option>
                <option value="event">Event</option>
                <option value="fundraising">Fundraising</option>
                <option value="engagement">Engagement</option>
              </select>
              <select
                v-model="selectedStatus"
                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="sending">Sending</option>
                <option value="sent">Sent</option>
                <option value="paused">Paused</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaigns List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Campaigns</h3>
        </div>
        <div class="overflow-hidden">
          <div v-if="loading" class="p-8 text-center">
            <div class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading campaigns...
            </div>
          </div>
          <div v-else-if="filteredCampaigns.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
            No campaigns found
          </div>
          <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
              v-for="campaign in filteredCampaigns"
              :key="campaign.id"
              class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ campaign.name }}
                    </h4>
                    <span
                      :class="getStatusBadgeClass(campaign.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ campaign.status }}
                    </span>
                    <span
                      :class="getTypeBadgeClass(campaign.type)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ campaign.type }}
                    </span>
                  </div>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ campaign.subject }}
                  </p>
                  <div class="mt-2 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ campaign.total_recipients }} recipients</span>
                    <span v-if="campaign.open_rate">{{ campaign.open_rate }}% open rate</span>
                    <span v-if="campaign.click_rate">{{ campaign.click_rate }}% click rate</span>
                    <span>{{ formatDate(campaign.created_at) }}</span>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    @click="viewCampaign(campaign)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    View
                  </button>
                  <button
                    v-if="campaign.status === 'draft'"
                    @click="editCampaign(campaign)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    Edit
                  </button>
                  <button
                    v-if="campaign.status === 'draft'"
                    @click="sendCampaign(campaign)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                  >
                    Send
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaign Builder Modal -->
      <CampaignBuilder
        v-if="showCampaignModal"
        :campaign="selectedCampaign"
        @close="closeCampaignModal"
        @saved="handleCampaignSaved"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import CampaignBuilder from '@/Components/EmailMarketing/CampaignBuilder.vue'

// Reactive data
const campaigns = ref([])
const analytics = ref({})
const loading = ref(true)
const searchQuery = ref('')
const selectedType = ref('')
const selectedStatus = ref('')
const showCampaignModal = ref(false)
const showTemplateModal = ref(false)
const showAutomationModal = ref(false)
const selectedCampaign = ref(null)

// Computed properties
const filteredCampaigns = computed(() => {
  let filtered = campaigns.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(campaign =>
      campaign.name.toLowerCase().includes(query) ||
      campaign.subject.toLowerCase().includes(query)
    )
  }

  if (selectedType.value) {
    filtered = filtered.filter(campaign => campaign.type === selectedType.value)
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(campaign => campaign.status === selectedStatus.value)
  }

  return filtered
})

// Methods
const loadCampaigns = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/email-campaigns')
    const data = await response.json()
    campaigns.value = data.campaigns.data || []
  } catch (error) {
    console.error('Failed to load campaigns:', error)
  } finally {
    loading.value = false
  }
}

const loadAnalytics = async () => {
  try {
    const response = await fetch('/api/email-campaigns/analytics')
    const data = await response.json()
    analytics.value = data.analytics || {}
  } catch (error) {
    console.error('Failed to load analytics:', error)
  }
}

const viewCampaign = (campaign) => {
  router.visit(`/admin/email-marketing/campaigns/${campaign.id}`)
}

const editCampaign = (campaign) => {
  selectedCampaign.value = campaign
  showCampaignModal.value = true
}

const sendCampaign = async (campaign) => {
  if (!confirm('Are you sure you want to send this campaign?')) {
    return
  }

  try {
    const response = await fetch(`/api/email-campaigns/${campaign.id}/send`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await loadCampaigns()
      alert('Campaign sent successfully!')
    } else {
      const error = await response.json()
      alert('Failed to send campaign: ' + error.message)
    }
  } catch (error) {
    console.error('Failed to send campaign:', error)
    alert('Failed to send campaign')
  }
}

const closeCampaignModal = () => {
  showCampaignModal.value = false
  selectedCampaign.value = null
}

const handleCampaignSaved = () => {
  closeCampaignModal()
  loadCampaigns()
}

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    sending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    sent: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    paused: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
  }
  return classes[status] || classes.draft
}

const getTypeBadgeClass = (type) => {
  const classes = {
    newsletter: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    announcement: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    event: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    fundraising: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    engagement: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
  }
  return classes[type] || classes.newsletter
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  loadCampaigns()
  loadAnalytics()
})
</script>