<template>
  <AppLayout :title="campaign.title">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ campaign.title }}
        </h2>
        <div class="flex space-x-2">
          <button
            v-if="canEdit"
            @click="editCampaign"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md font-medium"
          >
            Edit Campaign
          </button>
          <button
            v-if="campaign.allow_peer_fundraising"
            @click="createPeerFundraiser"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium"
          >
            Start Fundraising
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Campaign Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
              <div v-if="campaign.media_urls && campaign.media_urls[0]" class="h-64 bg-gray-200">
                <img
                  :src="campaign.media_urls[0]"
                  :alt="campaign.title"
                  class="w-full h-full object-cover"
                />
              </div>
              
              <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center space-x-4">
                    <span :class="statusClasses" class="px-3 py-1 rounded-full text-sm font-medium">
                      {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                    </span>
                    <span class="text-sm text-gray-500">
                      {{ campaign.type.charAt(0).toUpperCase() + campaign.type.slice(1) }} Campaign
                    </span>
                  </div>
                  <div class="text-sm text-gray-500">
                    Created by {{ campaign.creator.name }}
                    <span v-if="campaign.institution">
                      • {{ campaign.institution.name }}
                    </span>
                  </div>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ campaign.title }}</h1>
                <p class="text-gray-700 text-lg mb-6">{{ campaign.description }}</p>

                <div v-if="campaign.story" class="prose max-w-none">
                  <div class="whitespace-pre-wrap">{{ campaign.story }}</div>
                </div>
              </div>
            </div>

            <!-- Campaign Updates -->
            <div v-if="campaign.updates && campaign.updates.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Updates</h3>
              <div class="space-y-6">
                <div
                  v-for="update in campaign.updates"
                  :key="update.id"
                  class="border-l-4 border-blue-500 pl-4"
                >
                  <div class="flex justify-between items-start mb-2">
                    <h4 class="font-medium text-gray-900">{{ update.title }}</h4>
                    <span class="text-sm text-gray-500">
                      {{ formatDate(update.published_at) }}
                    </span>
                  </div>
                  <p class="text-gray-700">{{ update.content }}</p>
                </div>
              </div>
            </div>

            <!-- Peer Fundraisers -->
            <div v-if="campaign.allow_peer_fundraising && campaign.peer_fundraisers && campaign.peer_fundraisers.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Peer Fundraisers</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <PeerFundraiserCard
                  v-for="fundraiser in campaign.peer_fundraisers.slice(0, 4)"
                  :key="fundraiser.id"
                  :fundraiser="fundraiser"
                />
              </div>
              <div v-if="campaign.peer_fundraisers.length > 4" class="mt-4 text-center">
                <button class="text-blue-600 hover:text-blue-700 font-medium">
                  View All Fundraisers ({{ campaign.peer_fundraisers.length }})
                </button>
              </div>
            </div>

            <!-- Analytics (for campaign creators) -->
            <div v-if="canViewAnalytics">
              <CampaignAnalytics :campaign="campaign" />
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Progress Widget -->
            <CampaignProgress :campaign="campaign" />

            <!-- Donation Form -->
            <DonationForm
              :campaign="campaign"
              :is-authenticated="!!$page.props.auth.user"
              @donated="onDonated"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CampaignProgress from '@/Components/Fundraising/CampaignProgress.vue'
import CampaignAnalytics from '@/Components/Fundraising/CampaignAnalytics.vue'
import DonationForm from '@/Components/Fundraising/DonationForm.vue'
import PeerFundraiserCard from '@/Components/Fundraising/PeerFundraiserCard.vue'
import { usePage } from '@inertiajs/vue3'

interface Campaign {
  id: number
  title: string
  description: string
  story?: string
  goal_amount: number
  raised_amount: number
  progress_percentage: number
  status: string
  type: string
  allow_peer_fundraising: boolean
  created_by: number
  creator: {
    id: number
    name: string
  }
  institution?: {
    id: number
    name: string
  }
  media_urls?: string[]
  updates?: any[]
  peer_fundraisers?: any[]
}

const props = defineProps<{
  campaign: Campaign
}>()

const page = usePage()

const canEdit = computed(() => {
  const user = page.props.auth?.user
  return user && (
    user.id === props.campaign.created_by ||
    user.roles?.includes('admin') ||
    user.roles?.includes('institution_admin')
  )
})

const canViewAnalytics = computed(() => {
  return canEdit.value
})

const statusClasses = computed(() => {
  const baseClasses = 'px-3 py-1 rounded-full text-sm font-medium'
  
  switch (props.campaign.status) {
    case 'active':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'draft':
      return `${baseClasses} bg-gray-100 text-gray-800`
    case 'completed':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'paused':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'cancelled':
      return `${baseClasses} bg-red-100 text-red-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
})

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function editCampaign() {
  // Navigate to edit page or open modal
  window.location.href = `/campaigns/${props.campaign.id}/edit`
}

function createPeerFundraiser() {
  // Navigate to peer fundraiser creation or open modal
  window.location.href = `/campaigns/${props.campaign.id}/peer-fundraisers/create`
}

function onDonated(donation: any) {
  // Refresh the page or update the campaign data
  window.location.reload()
}
</script>