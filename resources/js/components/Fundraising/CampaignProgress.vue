<template>
  <div class="campaign-progress bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Campaign Progress</h3>
      <div class="flex justify-between items-center text-sm text-gray-600">
        <span>{{ remainingDays }} days left</span>
        <span>{{ campaign.donor_count }} donors</span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <span class="text-2xl font-bold text-gray-900">
          ${{ formatAmount(campaign.raised_amount) }}
        </span>
        <span class="text-sm text-gray-500">
          {{ Math.round(campaign.progress_percentage) }}%
        </span>
      </div>
      
      <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
        <div
          class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-500 ease-out"
          :style="{ width: `${Math.min(100, campaign.progress_percentage)}%` }"
        ></div>
      </div>
      
      <div class="flex justify-between items-center text-sm text-gray-600">
        <span>Goal: ${{ formatAmount(campaign.goal_amount) }}</span>
        <span v-if="campaign.progress_percentage >= 100" class="text-green-600 font-medium">
          🎉 Goal Reached!
        </span>
        <span v-else>
          ${{ formatAmount(campaign.goal_amount - campaign.raised_amount) }} to go
        </span>
      </div>
    </div>

    <!-- Campaign Stats -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="text-center p-3 bg-gray-50 rounded-lg">
        <div class="text-xl font-bold text-gray-900">{{ campaign.donor_count }}</div>
        <div class="text-sm text-gray-600">Donors</div>
      </div>
      <div class="text-center p-3 bg-gray-50 rounded-lg">
        <div class="text-xl font-bold text-gray-900">{{ averageDonation }}</div>
        <div class="text-sm text-gray-600">Avg. Donation</div>
      </div>
    </div>

    <!-- Peer Fundraising Stats -->
    <div v-if="campaign.allow_peer_fundraising && peerFundraisingStats" class="mb-6 p-4 bg-blue-50 rounded-lg">
      <h4 class="text-sm font-medium text-blue-900 mb-2">Peer Fundraising</h4>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="font-semibold text-blue-800">{{ peerFundraisingStats.active_count }}</div>
          <div class="text-blue-600">Active Fundraisers</div>
        </div>
        <div>
          <div class="font-semibold text-blue-800">${{ formatAmount(peerFundraisingStats.total_raised) }}</div>
          <div class="text-blue-600">Peer Total</div>
        </div>
      </div>
    </div>

    <!-- Recent Donations -->
    <div v-if="recentDonations.length > 0" class="mb-6">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Donations</h4>
      <div class="space-y-2 max-h-32 overflow-y-auto">
        <div
          v-for="donation in recentDonations.slice(0, 5)"
          :key="donation.id"
          class="flex items-center justify-between text-sm"
        >
          <div class="flex items-center">
            <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center mr-2">
              <span class="text-xs font-medium text-gray-600">
                {{ donation.donor_display_name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <span class="text-gray-900">{{ donation.donor_display_name }}</span>
          </div>
          <div class="flex items-center space-x-2">
            <span class="font-medium text-gray-900">${{ formatAmount(donation.amount) }}</span>
            <span class="text-xs text-gray-500">{{ formatTimeAgo(donation.processed_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Social Sharing -->
    <div class="border-t pt-4">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Share Campaign</h4>
      <div class="flex space-x-2">
        <button
          @click="shareToFacebook"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors"
        >
          Facebook
        </button>
        <button
          @click="shareToTwitter"
          class="flex-1 bg-blue-400 hover:bg-blue-500 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors"
        >
          Twitter
        </button>
        <button
          @click="shareToLinkedIn"
          class="flex-1 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors"
        >
          LinkedIn
        </button>
        <button
          @click="copyShareUrl"
          class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-3 rounded-md transition-colors"
          title="Copy Link"
        >
          📋
        </button>
      </div>
    </div>

    <!-- Milestones -->
    <div v-if="milestones.length > 0" class="mt-6 border-t pt-4">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Milestones</h4>
      <div class="space-y-2">
        <div
          v-for="milestone in milestones"
          :key="milestone.percentage"
          class="flex items-center justify-between text-sm"
        >
          <div class="flex items-center">
            <div
              :class="[
                'w-4 h-4 rounded-full mr-2',
                campaign.progress_percentage >= milestone.percentage
                  ? 'bg-green-500'
                  : 'bg-gray-300'
              ]"
            ></div>
            <span class="text-gray-700">{{ milestone.label }}</span>
          </div>
          <span class="text-gray-500">${{ formatAmount(milestone.amount) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Campaign {
  id: number
  title: string
  goal_amount: number
  raised_amount: number
  progress_percentage: number
  donor_count: number
  end_date: string
  allow_peer_fundraising: boolean
}

interface Donation {
  id: number
  amount: number
  donor_display_name: string
  processed_at: string
}

const props = defineProps<{
  campaign: Campaign
}>()

const recentDonations = ref<Donation[]>([])
const peerFundraisingStats = ref<any>(null)

const remainingDays = computed(() => {
  const endDate = new Date(props.campaign.end_date)
  const today = new Date()
  const diffTime = endDate.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return Math.max(0, diffDays)
})

const averageDonation = computed(() => {
  if (props.campaign.donor_count === 0) return '$0'
  const avg = props.campaign.raised_amount / props.campaign.donor_count
  return `$${formatAmount(avg)}`
})

const milestones = computed(() => {
  const goal = props.campaign.goal_amount
  return [
    { percentage: 25, amount: goal * 0.25, label: '25% Complete' },
    { percentage: 50, amount: goal * 0.5, label: 'Halfway There!' },
    { percentage: 75, amount: goal * 0.75, label: '75% Complete' },
    { percentage: 100, amount: goal, label: 'Goal Reached!' },
  ]
})

const shareUrl = computed(() => {
  return `${window.location.origin}/campaigns/${props.campaign.id}`
})

onMounted(() => {
  loadRecentDonations()
  if (props.campaign.allow_peer_fundraising) {
    loadPeerFundraisingStats()
  }
})

async function loadRecentDonations() {
  try {
    const response = await fetch(`/api/campaigns/${props.campaign.id}/donations?per_page=5`)
    const data = await response.json()
    recentDonations.value = data.data || []
  } catch (error) {
    console.error('Failed to load recent donations:', error)
  }
}

async function loadPeerFundraisingStats() {
  try {
    const response = await fetch(`/api/campaigns/${props.campaign.id}/peer-fundraisers`)
    const data = await response.json()
    
    peerFundraisingStats.value = {
      active_count: data.data?.filter((f: any) => f.status === 'active').length || 0,
      total_raised: data.data?.reduce((sum: number, f: any) => sum + f.raised_amount, 0) || 0,
    }
  } catch (error) {
    console.error('Failed to load peer fundraising stats:', error)
  }
}

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatTimeAgo(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays}d ago`
  
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

function shareToFacebook() {
  const url = encodeURIComponent(shareUrl.value)
  const text = encodeURIComponent(`Help support ${props.campaign.title}!`)
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank')
}

function shareToTwitter() {
  const url = encodeURIComponent(shareUrl.value)
  const text = encodeURIComponent(`Help support ${props.campaign.title}! ${shareUrl.value}`)
  window.open(`https://twitter.com/intent/tweet?text=${text}`, '_blank')
}

function shareToLinkedIn() {
  const url = encodeURIComponent(shareUrl.value)
  const title = encodeURIComponent(props.campaign.title)
  window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}&title=${title}`, '_blank')
}

async function copyShareUrl() {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    alert('Link copied to clipboard!')
  } catch (error) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = shareUrl.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    alert('Link copied to clipboard!')
  }
}
</script>