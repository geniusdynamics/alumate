<template>
  <div class="campaign-analytics">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Campaign Analytics</h2>
      <p class="text-gray-600">{{ campaign.title }}</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Raised</p>
            <p class="text-2xl font-bold text-gray-900">
              ${{ formatAmount(analytics.total_raised) }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Donors</p>
            <p class="text-2xl font-bold text-gray-900">{{ analytics.donor_count }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Average Donation</p>
            <p class="text-2xl font-bold text-gray-900">
              ${{ formatAmount(analytics.average_donation) }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-orange-100 rounded-lg">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Peer Fundraisers</p>
            <p class="text-2xl font-bold text-gray-900">{{ analytics.peer_fundraisers_count }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Overview -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Progress</h3>
      <div class="mb-4">
        <div class="flex justify-between items-center mb-2">
          <span class="text-sm font-medium text-gray-700">
            ${{ formatAmount(analytics.total_raised) }} of ${{ formatAmount(analytics.goal_amount) }}
          </span>
          <span class="text-sm text-gray-500">
            {{ Math.round(analytics.progress_percentage) }}%
          </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
          <div
            class="bg-green-500 h-3 rounded-full transition-all duration-300"
            :style="{ width: `${Math.min(100, analytics.progress_percentage)}%` }"
          ></div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-2xl font-bold text-green-600">
            ${{ formatAmount(analytics.largest_donation) }}
          </div>
          <div class="text-sm text-gray-500">Largest Donation</div>
        </div>
        <div>
          <div class="text-2xl font-bold text-blue-600">
            {{ analytics.active_peer_fundraisers }}
          </div>
          <div class="text-sm text-gray-500">Active Fundraisers</div>
        </div>
        <div>
          <div class="text-2xl font-bold text-purple-600">
            ${{ formatAmount(analytics.peer_fundraising_total) }}
          </div>
          <div class="text-sm text-gray-500">Peer Fundraising Total</div>
        </div>
      </div>
    </div>

    <!-- Top Donors -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Donors</h3>
        <div class="space-y-3">
          <div
            v-for="donor in topDonors.slice(0, 5)"
            :key="donor.donor_id"
            class="flex items-center justify-between"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                <span class="text-xs font-medium text-gray-600">
                  {{ donor.donor?.name?.charAt(0).toUpperCase() || '?' }}
                </span>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">
                  {{ donor.donor?.name || 'Anonymous' }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ donor.donation_count }} donation{{ donor.donation_count !== 1 ? 's' : '' }}
                </p>
              </div>
            </div>
            <div class="text-sm font-semibold text-gray-900">
              ${{ formatAmount(donor.total_donated) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Top Peer Fundraisers -->
      <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Peer Fundraisers</h3>
        <div class="space-y-3">
          <div
            v-for="fundraiser in analytics.top_peer_fundraisers.slice(0, 5)"
            :key="fundraiser.id"
            class="flex items-center justify-between"
          >
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                <span class="text-xs font-medium text-gray-600">
                  {{ fundraiser.user?.name?.charAt(0).toUpperCase() || '?' }}
                </span>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">
                  {{ fundraiser.user?.name || 'Unknown' }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ fundraiser.donor_count }} donor{{ fundraiser.donor_count !== 1 ? 's' : '' }}
                </p>
              </div>
            </div>
            <div class="text-sm font-semibold text-gray-900">
              ${{ formatAmount(fundraiser.raised_amount) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Donations -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Donations</h3>
      <div class="space-y-4">
        <div
          v-for="donation in recentDonations.slice(0, 10)"
          :key="donation.id"
          class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0"
        >
          <div class="flex items-center">
            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
              <span class="text-sm font-medium text-gray-600">
                {{ donation.donor_display_name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-900">
                {{ donation.donor_display_name }}
              </p>
              <p class="text-xs text-gray-500">
                {{ formatDate(donation.processed_at) }}
                <span v-if="donation.peer_fundraiser">
                  • via {{ donation.peer_fundraiser.user?.name }}
                </span>
              </p>
              <p v-if="donation.message" class="text-xs text-gray-600 mt-1">
                "{{ donation.message }}"
              </p>
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm font-semibold text-gray-900">
              ${{ formatAmount(donation.amount) }}
            </div>
            <div v-if="donation.is_recurring" class="text-xs text-blue-600">
              Recurring
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Campaign {
  id: number
  title: string
}

interface Analytics {
  total_raised: number
  goal_amount: number
  progress_percentage: number
  donor_count: number
  average_donation: number
  largest_donation: number
  peer_fundraisers_count: number
  active_peer_fundraisers: number
  peer_fundraising_total: number
  top_peer_fundraisers: any[]
}

const props = defineProps<{
  campaign: Campaign
}>()

const analytics = ref<Analytics>({
  total_raised: 0,
  goal_amount: 0,
  progress_percentage: 0,
  donor_count: 0,
  average_donation: 0,
  largest_donation: 0,
  peer_fundraisers_count: 0,
  active_peer_fundraisers: 0,
  peer_fundraising_total: 0,
  top_peer_fundraisers: [],
})

const topDonors = ref([])
const recentDonations = ref([])

onMounted(() => {
  loadAnalytics()
})

async function loadAnalytics() {
  try {
    const response = await fetch(`/api/fundraising-campaigns/${props.campaign.id}/analytics`)
    const data = await response.json()
    
    analytics.value = data.analytics
    topDonors.value = data.top_donors
    recentDonations.value = data.recent_donations
  } catch (error) {
    console.error('Failed to load analytics:', error)
  }
}

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
</script>