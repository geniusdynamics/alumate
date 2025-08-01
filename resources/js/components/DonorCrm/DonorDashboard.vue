<template>
    <div class="donor-dashboard">
        <div class="dashboard-header mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Donor CRM Dashboard</h1>
            <div class="flex space-x-4">
                <button @click="refreshDashboard" class="btn btn-secondary" :disabled="loading">
                    <RefreshIcon class="w-4 h-4 mr-2" />
                    Refresh
                </button>
                <button @click="showCreateDonorModal = true" class="btn btn-primary">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Add Donor
                </button>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <UsersIcon class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Donors
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ dashboardStats.totalDonors }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <CurrencyDollarIcon class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Total Donations
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                ${{ formatCurrency(dashboardStats.totalDonations) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <StarIcon class="h-8 w-8 text-yellow-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Major Gift Prospects
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ dashboardStats.majorGiftProspects }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ClockIcon class="h-8 w-8 text-purple-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Pending Follow-ups
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ dashboardStats.pendingFollowups }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button @click="showCreateInteractionModal = true"
                        class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <ChatBubbleLeftIcon class="h-6 w-6 text-blue-600 mr-3" />
                        <span class="text-sm font-medium text-gray-900">Log Interaction</span>
                    </button>

                    <button @click="showCreateStewardshipPlanModal = true"
                        class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <DocumentTextIcon class="h-6 w-6 text-green-600 mr-3" />
                        <span class="text-sm font-medium text-gray-900">Create Stewardship Plan</span>
                    </button>

                    <button @click="showMajorGiftProspectModal = true"
                        class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <TrophyIcon class="h-6 w-6 text-yellow-600 mr-3" />
                        <span class="text-sm font-medium text-gray-900">Add Major Gift Prospect</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Interactions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Recent Interactions</h2>
                </div>
                <div class="p-6">
                    <div v-if="recentInteractions.length === 0" class="text-center text-gray-500 py-8">
                        No recent interactions
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="interaction in recentInteractions" :key="interaction.id"
                            class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <ChatBubbleLeftIcon class="w-4 h-4 text-blue-600" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ interaction.donor_profile?.user?.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ interaction.type }} - {{ interaction.subject }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ formatDate(interaction.interaction_date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Follow-ups -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Upcoming Follow-ups</h2>
                </div>
                <div class="p-6">
                    <div v-if="upcomingFollowups.length === 0" class="text-center text-gray-500 py-8">
                        No upcoming follow-ups
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="followup in upcomingFollowups" :key="followup.id"
                            class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <ClockIcon class="w-4 h-4 text-yellow-600" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ followup.donor_profile?.user?.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ followup.subject }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Due: {{ formatDate(followup.follow_up_date) }}
                                </p>
                            </div>
                            <button @click="markFollowupComplete(followup.id)"
                                class="text-green-600 hover:text-green-800">
                                <CheckIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Donors -->
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Top Donors</h2>
            </div>
            <div class="p-6">
                <div v-if="topDonors.length === 0" class="text-center text-gray-500 py-8">
                    No donor data available
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Donor
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Donated
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Donation
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="donor in topDonors" :key="donor.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <UserIcon class="h-6 w-6 text-gray-600" />
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ donor.user?.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ donor.user?.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ formatCurrency(donor.total_donated) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(donor.last_donation_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusBadgeClass(donor.donor_status)"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ donor.donor_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="viewDonorProfile(donor.id)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        View
                                    </button>
                                    <button @click="editDonor(donor)" class="text-green-600 hover:text-green-900">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CreateDonorModal v-if="showCreateDonorModal" @close="showCreateDonorModal = false"
            @created="handleDonorCreated" />

        <CreateInteractionModal v-if="showCreateInteractionModal" @close="showCreateInteractionModal = false"
            @created="handleInteractionCreated" />

        <CreateStewardshipPlanModal v-if="showCreateStewardshipPlanModal"
            @close="showCreateStewardshipPlanModal = false" @created="handleStewardshipPlanCreated" />

        <CreateMajorGiftProspectModal v-if="showMajorGiftProspectModal" @close="showMajorGiftProspectModal = false"
            @created="handleMajorGiftProspectCreated" />
    </div>
</template>
<
script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import {
  RefreshIcon,
  PlusIcon,
  UsersIcon,
  CurrencyDollarIcon,
  StarIcon,
  ClockIcon,
  ChatBubbleLeftIcon,
  DocumentTextIcon,
  TrophyIcon,
  CheckIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

// Import modal components (these would need to be created)
import CreateDonorModal from './CreateDonorModal.vue'
import CreateInteractionModal from './CreateInteractionModal.vue'
import CreateStewardshipPlanModal from './CreateStewardshipPlanModal.vue'
import CreateMajorGiftProspectModal from './CreateMajorGiftProspectModal.vue'

// Types
interface DashboardStats {
  totalDonors: number
  totalDonations: number
  majorGiftProspects: number
  pendingFollowups: number
}

interface DonorProfile {
  id: number
  user?: {
    name: string
    email: string
  }
  total_donated: number
  last_donation_date: string
  donor_status: string
}

interface Interaction {
  id: number
  type: string
  subject: string
  interaction_date: string
  follow_up_date?: string
  donor_profile?: {
    user?: {
      name: string
    }
  }
}

// Reactive state
const loading = ref(false)
const showCreateDonorModal = ref(false)
const showCreateInteractionModal = ref(false)
const showCreateStewardshipPlanModal = ref(false)
const showMajorGiftProspectModal = ref(false)

const dashboardStats = ref<DashboardStats>({
  totalDonors: 0,
  totalDonations: 0,
  majorGiftProspects: 0,
  pendingFollowups: 0
})

const recentInteractions = ref<Interaction[]>([])
const upcomingFollowups = ref<Interaction[]>([])
const topDonors = ref<DonorProfile[]>([])

// Methods
const refreshDashboard = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadDashboardStats(),
      loadRecentInteractions(),
      loadUpcomingFollowups(),
      loadTopDonors()
    ])
  } catch (error) {
    console.error('Error refreshing dashboard:', error)
  } finally {
    loading.value = false
  }
}

const loadDashboardStats = async () => {
  try {
    const response = await axios.get('/api/donor-crm/dashboard/stats')
    dashboardStats.value = response.data
  } catch (error) {
    console.error('Error loading dashboard stats:', error)
  }
}

const loadRecentInteractions = async () => {
  try {
    const response = await axios.get('/api/donor-interactions', {
      params: { limit: 5, recent: true }
    })
    recentInteractions.value = response.data.data
  } catch (error) {
    console.error('Error loading recent interactions:', error)
  }
}

const loadUpcomingFollowups = async () => {
  try {
    const response = await axios.get('/api/donor-interactions', {
      params: { limit: 5, upcoming_followups: true }
    })
    upcomingFollowups.value = response.data.data
  } catch (error) {
    console.error('Error loading upcoming follow-ups:', error)
  }
}

const loadTopDonors = async () => {
  try {
    const response = await axios.get('/api/donor-profiles', {
      params: { limit: 10, sort: 'total_donated', order: 'desc' }
    })
    topDonors.value = response.data.data
  } catch (error) {
    console.error('Error loading top donors:', error)
  }
}

const markFollowupComplete = async (interactionId: number) => {
  try {
    await axios.patch(`/api/donor-interactions/${interactionId}`, {
      follow_up_completed: true
    })
    await loadUpcomingFollowups()
    await loadDashboardStats()
  } catch (error) {
    console.error('Error marking follow-up complete:', error)
  }
}

const viewDonorProfile = (donorId: number) => {
  router.visit(`/donor-crm/donors/${donorId}`)
}

const editDonor = (donor: DonorProfile) => {
  router.visit(`/donor-crm/donors/${donor.id}/edit`)
}

// Event handlers
const handleDonorCreated = () => {
  showCreateDonorModal.value = false
  refreshDashboard()
}

const handleInteractionCreated = () => {
  showCreateInteractionModal.value = false
  refreshDashboard()
}

const handleStewardshipPlanCreated = () => {
  showCreateStewardshipPlanModal.value = false
  refreshDashboard()
}

const handleMajorGiftProspectCreated = () => {
  showMajorGiftProspectModal.value = false
  refreshDashboard()
}

// Utility functions
const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusBadgeClass = (status: string): string => {
  const statusClasses: Record<string, string> = {
    'active': 'bg-green-100 text-green-800',
    'lapsed': 'bg-yellow-100 text-yellow-800',
    'prospect': 'bg-blue-100 text-blue-800',
    'major_gift': 'bg-purple-100 text-purple-800',
    'inactive': 'bg-gray-100 text-gray-800'
  }
  return statusClasses[status] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(() => {
  refreshDashboard()
})
</script>

<style scoped>
.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
  @apply text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-indigo-500;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>