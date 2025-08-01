<template>
  <div class="donation-form bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="mb-6">
      <h3 class="text-xl font-semibold text-gray-900 mb-2">Make a Donation</h3>
      <p class="text-gray-600">Support {{ campaign.title }}</p>
    </div>

    <form @submit.prevent="submitDonation" class="space-y-6">
      <!-- Donation Amount -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Donation Amount *
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-gray-500 sm:text-sm">$</span>
          </div>
          <input
            v-model.number="form.amount"
            type="number"
            min="1"
            step="0.01"
            required
            class="block w-full pl-7 pr-12 border border-gray-300 rounded-md py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="0.00"
          />
        </div>
        
        <!-- Quick Amount Buttons -->
        <div class="mt-3 grid grid-cols-4 gap-2">
          <button
            v-for="amount in quickAmounts"
            :key="amount"
            type="button"
            @click="form.amount = amount"
            :class="[
              'px-3 py-2 text-sm font-medium rounded-md border transition-colors',
              form.amount === amount
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
            ]"
          >
            ${{ amount }}
          </button>
        </div>
      </div>

      <!-- Recurring Donation -->
      <div>
        <label class="flex items-center">
          <input
            v-model="form.is_recurring"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-700">Make this a recurring donation</span>
        </label>
        
        <div v-if="form.is_recurring" class="mt-3">
          <select
            v-model="form.recurring_frequency"
            class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
      </div>

      <!-- Anonymous Donation -->
      <div v-if="campaign.allow_anonymous_donations">
        <label class="flex items-center">
          <input
            v-model="form.is_anonymous"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-700">Donate anonymously</span>
        </label>
      </div>

      <!-- Donor Information (for non-authenticated users) -->
      <div v-if="!isAuthenticated && !form.is_anonymous" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Your Name *
          </label>
          <input
            v-model="form.donor_name"
            type="text"
            required
            class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your name"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email Address *
          </label>
          <input
            v-model="form.donor_email"
            type="email"
            required
            class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your email"
          />
        </div>
      </div>

      <!-- Donation Message -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Message (Optional)
        </label>
        <textarea
          v-model="form.message"
          rows="3"
          class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Leave a message of support..."
        ></textarea>
      </div>

      <!-- Peer Fundraiser Selection -->
      <div v-if="peerFundraisers.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Support a Peer Fundraiser (Optional)
        </label>
        <select
          v-model="form.peer_fundraiser_id"
          class="block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Donate directly to campaign</option>
          <option
            v-for="fundraiser in peerFundraisers"
            :key="fundraiser.id"
            :value="fundraiser.id"
          >
            {{ fundraiser.user.name }} - {{ fundraiser.title }}
          </option>
        </select>
      </div>

      <!-- Payment Method -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Payment Method *
        </label>
        <div class="grid grid-cols-1 gap-3">
          <label
            v-for="method in paymentMethods"
            :key="method.value"
            class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50"
            :class="{ 'border-blue-500 bg-blue-50': form.payment_method === method.value }"
          >
            <input
              v-model="form.payment_method"
              :value="method.value"
              type="radio"
              class="text-blue-600 focus:ring-blue-500"
              required
            />
            <div class="ml-3 flex items-center">
              <component :is="method.icon" class="w-6 h-6 mr-2" />
              <div>
                <div class="text-sm font-medium text-gray-900">{{ method.label }}</div>
                <div class="text-xs text-gray-500">{{ method.description }}</div>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Terms and Conditions -->
      <div>
        <label class="flex items-start">
          <input
            v-model="form.agree_terms"
            type="checkbox"
            required
            class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-700">
            I agree to the 
            <a href="/terms" target="_blank" class="text-blue-600 hover:text-blue-700">
              Terms and Conditions
            </a>
            and understand that donations are non-refundable.
          </span>
        </label>
      </div>

      <!-- Submit Button -->
      <div class="pt-4 border-t">
        <button
          type="submit"
          :disabled="loading || !form.amount || !form.payment_method || !form.agree_terms"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-3 px-4 rounded-md transition-colors"
        >
          {{ loading ? 'Processing...' : `Donate $${form.amount || 0}` }}
        </button>
        
        <p class="mt-2 text-xs text-gray-500 text-center">
          Your donation will be processed securely. You will receive a confirmation email.
        </p>
      </div>
    </form>

    <!-- Success Message -->
    <div v-if="showSuccess" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-md">
      <div class="flex">
        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Donation Successful!</h3>
          <p class="mt-1 text-sm text-green-700">
            Thank you for your generous donation of ${{ form.amount }}. 
            {{ campaign.thank_you_message || 'Your support makes a difference!' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

interface Campaign {
  id: number
  title: string
  allow_anonymous_donations: boolean
  thank_you_message?: string
}

interface PeerFundraiser {
  id: number
  title: string
  user: {
    id: number
    name: string
  }
}

const props = defineProps<{
  campaign: Campaign
  isAuthenticated?: boolean
}>()

const emit = defineEmits<{
  donated: [donation: any]
}>()

const loading = ref(false)
const showSuccess = ref(false)
const peerFundraisers = ref<PeerFundraiser[]>([])

const form = reactive({
  amount: null as number | null,
  is_recurring: false,
  recurring_frequency: 'monthly',
  is_anonymous: false,
  donor_name: '',
  donor_email: '',
  message: '',
  peer_fundraiser_id: '',
  payment_method: '',
  agree_terms: false,
})

const quickAmounts = [25, 50, 100, 250]

const paymentMethods = [
  {
    value: 'stripe',
    label: 'Credit/Debit Card',
    description: 'Visa, Mastercard, American Express',
    icon: 'CreditCardIcon',
  },
  {
    value: 'paypal',
    label: 'PayPal',
    description: 'Pay with your PayPal account',
    icon: 'PayPalIcon',
  },
  {
    value: 'bank_transfer',
    label: 'Bank Transfer',
    description: 'Direct bank transfer',
    icon: 'BankIcon',
  },
]

onMounted(() => {
  loadPeerFundraisers()
})

async function loadPeerFundraisers() {
  try {
    const response = await fetch(`/api/campaigns/${props.campaign.id}/peer-fundraisers`)
    const data = await response.json()
    peerFundraisers.value = data.data || []
  } catch (error) {
    console.error('Failed to load peer fundraisers:', error)
  }
}

async function submitDonation() {
  loading.value = true
  
  try {
    const payload = {
      campaign_id: props.campaign.id,
      ...form,
      peer_fundraiser_id: form.peer_fundraiser_id || null,
    }
    
    const response = await fetch('/api/campaign-donations', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(payload),
    })
    
    if (response.ok) {
      const donation = await response.json()
      showSuccess.value = true
      emit('donated', donation)
      
      // Reset form
      Object.keys(form).forEach(key => {
        if (typeof form[key] === 'boolean') {
          form[key] = false
        } else if (typeof form[key] === 'number') {
          form[key] = null
        } else {
          form[key] = ''
        }
      })
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        showSuccess.value = false
      }, 5000)
    } else {
      const error = await response.json()
      console.error('Failed to process donation:', error)
      alert('Failed to process donation. Please try again.')
    }
  } catch (error) {
    console.error('Failed to process donation:', error)
    alert('Failed to process donation. Please try again.')
  } finally {
    loading.value = false
  }
}

// Simple icon components (you could replace with actual icon library)
const CreditCardIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
    </svg>
  `
}

const PayPalIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 24 24">
      <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.26-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81.394.45.67.99.824 1.607z"/>
    </svg>
  `
}

const BankIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
    </svg>
  `
}
</script>