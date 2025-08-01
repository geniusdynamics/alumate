<template>
  <div class="recurring-donation-manager">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900">Recurring Donations</h3>
        <span class="text-sm text-gray-500">{{ recurringDonations.length }} active</span>
      </div>

      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-gray-600">Loading recurring donations...</p>
      </div>

      <div v-else-if="recurringDonations.length === 0" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No recurring donations</h3>
        <p class="mt-1 text-sm text-gray-500">You don't have any active recurring donations.</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="donation in recurringDonations"
          :key="donation.id"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h4 class="text-lg font-medium text-gray-900">
                  {{ donation.campaign.title }}
                </h4>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getStatusColor(donation.status)
                  ]"
                >
                  {{ getStatusLabel(donation.status) }}
                </span>
              </div>
              
              <div class="mt-2 grid grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                  <span class="font-medium">Amount:</span>
                  ${{ donation.amount }} {{ donation.frequency_label.toLowerCase() }}
                </div>
                <div>
                  <span class="font-medium">Next Payment:</span>
                  {{ formatDate(donation.next_payment_date) }}
                </div>
                <div>
                  <span class="font-medium">Total Donated:</span>
                  ${{ donation.total_amount_collected }}
                </div>
                <div>
                  <span class="font-medium">Payments Made:</span>
                  {{ donation.total_payments }}
                </div>
              </div>

              <div v-if="donation.status === 'cancelled' && donation.cancellation_reason" class="mt-2">
                <span class="text-sm text-gray-500">
                  <strong>Cancelled:</strong> {{ donation.cancellation_reason }}
                </span>
              </div>
            </div>

            <div class="flex space-x-2 ml-4">
              <button
                v-if="donation.status === 'active'"
                @click="pauseDonation(donation)"
                :disabled="processingId === donation.id"
                class="px-3 py-1 text-sm font-medium text-yellow-700 bg-yellow-100 hover:bg-yellow-200 rounded-md transition-colors disabled:opacity-50"
              >
                {{ processingId === donation.id ? 'Processing...' : 'Pause' }}
              </button>

              <button
                v-if="donation.status === 'paused'"
                @click="resumeDonation(donation)"
                :disabled="processingId === donation.id"
                class="px-3 py-1 text-sm font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md transition-colors disabled:opacity-50"
              >
                {{ processingId === donation.id ? 'Processing...' : 'Resume' }}
              </button>

              <button
                v-if="donation.status !== 'cancelled'"
                @click="showCancelModal(donation)"
                :disabled="processingId === donation.id"
                class="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded-md transition-colors disabled:opacity-50"
              >
                Cancel
              </button>

              <button
                @click="viewDetails(donation)"
                class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-colors"
              >
                Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div
      v-if="showingCancelModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeCancelModal"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Cancel Recurring Donation
          </h3>
          
          <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to cancel your recurring donation of 
            <strong>${{ donationToCancel?.amount }} {{ donationToCancel?.frequency_label.toLowerCase() }}</strong>
            to <strong>{{ donationToCancel?.campaign.title }}</strong>?
          </p>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Reason for cancellation (optional)
            </label>
            <textarea
              v-model="cancellationReason"
              rows="3"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Let us know why you're cancelling..."
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              @click="closeCancelModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
            >
              Keep Donation
            </button>
            <button
              @click="confirmCancel"
              :disabled="cancelling"
              class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors disabled:opacity-50"
            >
              {{ cancelling ? 'Cancelling...' : 'Cancel Donation' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface RecurringDonation {
  id: number
  amount: number
  frequency_label: string
  status: string
  next_payment_date: string
  total_amount_collected: number
  total_payments: number
  cancellation_reason?: string
  campaign: {
    id: number
    title: string
  }
}

const emit = defineEmits<{
  donationUpdated: [donation: RecurringDonation]
}>()

const loading = ref(true)
const processingId = ref<number | null>(null)
const recurringDonations = ref<RecurringDonation[]>([])
const showingCancelModal = ref(false)
const donationToCancel = ref<RecurringDonation | null>(null)
const cancellationReason = ref('')
const cancelling = ref(false)

onMounted(() => {
  loadRecurringDonations()
})

async function loadRecurringDonations() {
  try {
    const response = await fetch('/api/user/recurring-donations')
    const data = await response.json()
    recurringDonations.value = data.data || []
  } catch (error) {
    console.error('Failed to load recurring donations:', error)
  } finally {
    loading.value = false
  }
}

async function pauseDonation(donation: RecurringDonation) {
  processingId.value = donation.id
  
  try {
    const response = await fetch(`/api/recurring-donations/${donation.id}/pause`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (response.ok) {
      const result = await response.json()
      const index = recurringDonations.value.findIndex(d => d.id === donation.id)
      if (index !== -1) {
        recurringDonations.value[index] = result.recurring_donation
        emit('donationUpdated', result.recurring_donation)
      }
    } else {
      alert('Failed to pause recurring donation')
    }
  } catch (error) {
    console.error('Failed to pause donation:', error)
    alert('Failed to pause recurring donation')
  } finally {
    processingId.value = null
  }
}

async function resumeDonation(donation: RecurringDonation) {
  processingId.value = donation.id
  
  try {
    const response = await fetch(`/api/recurring-donations/${donation.id}/resume`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (response.ok) {
      const result = await response.json()
      const index = recurringDonations.value.findIndex(d => d.id === donation.id)
      if (index !== -1) {
        recurringDonations.value[index] = result.recurring_donation
        emit('donationUpdated', result.recurring_donation)
      }
    } else {
      alert('Failed to resume recurring donation')
    }
  } catch (error) {
    console.error('Failed to resume donation:', error)
    alert('Failed to resume recurring donation')
  } finally {
    processingId.value = null
  }
}

function showCancelModal(donation: RecurringDonation) {
  donationToCancel.value = donation
  cancellationReason.value = ''
  showingCancelModal.value = true
}

function closeCancelModal() {
  showingCancelModal.value = false
  donationToCancel.value = null
  cancellationReason.value = ''
}

async function confirmCancel() {
  if (!donationToCancel.value) return
  
  cancelling.value = true
  
  try {
    const response = await fetch(`/api/recurring-donations/${donationToCancel.value.id}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        reason: cancellationReason.value || 'Cancelled by donor',
      }),
    })

    if (response.ok) {
      const result = await response.json()
      const index = recurringDonations.value.findIndex(d => d.id === donationToCancel.value!.id)
      if (index !== -1) {
        recurringDonations.value[index] = result.recurring_donation
        emit('donationUpdated', result.recurring_donation)
      }
      closeCancelModal()
    } else {
      alert('Failed to cancel recurring donation')
    }
  } catch (error) {
    console.error('Failed to cancel donation:', error)
    alert('Failed to cancel recurring donation')
  } finally {
    cancelling.value = false
  }
}

function viewDetails(donation: RecurringDonation) {
  // Navigate to donation details page or show modal
  window.location.href = `/recurring-donations/${donation.id}`
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800'
    case 'paused':
      return 'bg-yellow-100 text-yellow-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'active':
      return 'Active'
    case 'paused':
      return 'Paused'
    case 'cancelled':
      return 'Cancelled'
    case 'failed':
      return 'Failed'
    default:
      return status.charAt(0).toUpperCase() + status.slice(1)
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>