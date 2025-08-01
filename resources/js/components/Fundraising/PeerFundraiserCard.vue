<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center">
        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
          <span class="text-lg font-medium text-gray-600">
            {{ fundraiser.user.name.charAt(0).toUpperCase() }}
          </span>
        </div>
        <div class="ml-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ fundraiser.title }}</h3>
          <p class="text-sm text-gray-600">by {{ fundraiser.user.name }}</p>
        </div>
      </div>
      <div class="text-right">
        <div class="text-lg font-bold text-gray-900">
          ${{ formatAmount(fundraiser.raised_amount) }}
        </div>
        <div v-if="fundraiser.goal_amount" class="text-sm text-gray-500">
          of ${{ formatAmount(fundraiser.goal_amount) }}
        </div>
      </div>
    </div>

    <p v-if="fundraiser.personal_message" class="text-gray-700 text-sm mb-4 line-clamp-3">
      {{ fundraiser.personal_message }}
    </p>

    <!-- Progress Bar -->
    <div v-if="fundraiser.goal_amount" class="mb-4">
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm text-gray-600">Progress</span>
        <span class="text-sm font-medium text-gray-900">
          {{ Math.round(fundraiser.progress_percentage) }}%
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-500 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${Math.min(100, fundraiser.progress_percentage)}%` }"
        ></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="flex justify-between items-center mb-4">
      <div class="text-center">
        <div class="text-lg font-semibold text-gray-900">{{ fundraiser.donor_count }}</div>
        <div class="text-xs text-gray-500">Donors</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-semibold text-gray-900">{{ daysActive }}</div>
        <div class="text-xs text-gray-500">Days Active</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-semibold text-gray-900">{{ shareCount }}</div>
        <div class="text-xs text-gray-500">Shares</div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex space-x-2">
      <button
        @click="viewFundraiser"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors"
      >
        View & Donate
      </button>
      <button
        @click="shareFundraiser"
        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-md transition-colors"
      >
        Share
      </button>
    </div>

    <!-- Social Share Modal -->
    <div v-if="showShareModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Share Fundraiser</h3>
          <button
            @click="showShareModal = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="space-y-3">
          <button
            @click="shareToFacebook"
            class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Share on Facebook
          </button>

          <button
            @click="shareToTwitter"
            class="w-full flex items-center justify-center px-4 py-2 bg-blue-400 hover:bg-blue-500 text-white rounded-md"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            Share on Twitter
          </button>

          <button
            @click="shareToLinkedIn"
            class="w-full flex items-center justify-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-md"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
            Share on LinkedIn
          </button>

          <div class="border-t pt-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Share Link</label>
            <div class="flex">
              <input
                ref="shareUrlInput"
                :value="shareUrl"
                readonly
                class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 text-sm"
              />
              <button
                @click="copyShareUrl"
                class="bg-gray-100 hover:bg-gray-200 border border-l-0 border-gray-300 rounded-r-md px-3 py-2 text-sm font-medium"
              >
                Copy
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface PeerFundraiser {
  id: number
  title: string
  personal_message?: string
  goal_amount?: number
  raised_amount: number
  progress_percentage: number
  donor_count: number
  status: string
  created_at: string
  user: {
    id: number
    name: string
  }
  share_url: string
}

const props = defineProps<{
  fundraiser: PeerFundraiser
}>()

const showShareModal = ref(false)
const shareUrlInput = ref<HTMLInputElement>()

const daysActive = computed(() => {
  const createdDate = new Date(props.fundraiser.created_at)
  const today = new Date()
  const diffTime = today.getTime() - createdDate.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return Math.max(1, diffDays)
})

const shareCount = computed(() => {
  // This would come from analytics in a real implementation
  return Math.floor(Math.random() * 20) + 1
})

const shareUrl = computed(() => {
  return props.fundraiser.share_url || `${window.location.origin}/peer-fundraisers/${props.fundraiser.id}`
})

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

function viewFundraiser() {
  window.location.href = `/peer-fundraisers/${props.fundraiser.id}`
}

function shareFundraiser() {
  showShareModal.value = true
}

function shareToFacebook() {
  const url = encodeURIComponent(shareUrl.value)
  const text = encodeURIComponent(`Help me support ${props.fundraiser.title}!`)
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank')
}

function shareToTwitter() {
  const url = encodeURIComponent(shareUrl.value)
  const text = encodeURIComponent(`Help me support ${props.fundraiser.title}! ${shareUrl.value}`)
  window.open(`https://twitter.com/intent/tweet?text=${text}`, '_blank')
}

function shareToLinkedIn() {
  const url = encodeURIComponent(shareUrl.value)
  const title = encodeURIComponent(props.fundraiser.title)
  const summary = encodeURIComponent(props.fundraiser.personal_message || '')
  window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}&title=${title}&summary=${summary}`, '_blank')
}

async function copyShareUrl() {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    // You could show a toast notification here
    alert('Link copied to clipboard!')
  } catch (error) {
    // Fallback for older browsers
    shareUrlInput.value?.select()
    document.execCommand('copy')
    alert('Link copied to clipboard!')
  }
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>