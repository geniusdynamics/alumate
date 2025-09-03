<template>
  <div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
    <div class="space-y-3">
      <div class="flex justify-between items-center">
        <span class="text-gray-600">Total Tenants</span>
        <span class="font-semibold text-2xl text-blue-600">{{ formatNumber(data?.total_tenants || 0) }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-gray-600">Total Users</span>
        <span class="font-semibold text-2xl text-green-600">{{ formatNumber(data?.total_users || 0) }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-gray-600">Active Components</span>
        <span class="font-semibold text-2xl text-purple-600">{{ formatNumber(data?.active_components || 0) }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-gray-600">System Uptime</span>
        <span class="font-semibold text-sm text-gray-700">{{ data?.system_uptime || 'Loading...' }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-gray-600">Last Deployment</span>
        <span class="font-semibold text-sm text-gray-700">{{ formatDate(data?.last_deployment) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  data?: {
    total_tenants?: number
    total_users?: number
    active_components?: number
    system_uptime?: string
    last_deployment?: string
  }
}

defineProps<Props>()

const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const formatDate = (date?: string): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString() + ' ' + new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>