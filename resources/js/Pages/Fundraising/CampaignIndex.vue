<template>
  <AppLayout title="Fundraising Campaigns">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Fundraising Campaigns
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <CampaignList :can-create-campaign="canCreateCampaign" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CampaignList from '@/Components/Fundraising/CampaignList.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const canCreateCampaign = computed(() => {
  const user = page.props.auth?.user
  return user && (
    user.roles?.includes('admin') || 
    user.roles?.includes('institution_admin') || 
    user.roles?.includes('alumni')
  )
})
</script>