<template>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-xl font-semibold text-gray-900">{{ scholarship.name }}</h3>
        <p class="text-sm text-gray-600 mt-1">
          Created by {{ scholarship.creator?.name }}
        </p>
      </div>
      <div class="flex items-center space-x-2">
        <span 
          :class="statusClasses"
          class="px-2 py-1 rounded-full text-xs font-medium"
        >
          {{ scholarship.status }}
        </span>
        <span 
          :class="typeClasses"
          class="px-2 py-1 rounded-full text-xs font-medium"
        >
          {{ scholarship.type }}
        </span>
      </div>
    </div>

    <p class="text-gray-700 mb-4 line-clamp-3">{{ scholarship.description }}</p>

    <div class="grid grid-cols-2 gap-4 mb-4">
      <div>
        <p class="text-sm text-gray-600">Award Amount</p>
        <p class="text-lg font-semibold text-green-600">
          ${{ formatCurrency(scholarship.amount) }}
        </p>
      </div>
      <div>
        <p class="text-sm text-gray-600">Application Deadline</p>
        <p class="text-sm font-medium">
          {{ formatDate(scholarship.application_deadline) }}
        </p>
      </div>
    </div>

    <div class="flex justify-between items-center mb-4">
      <div class="flex space-x-4 text-sm text-gray-600">
        <span>{{ scholarship.applications_count || 0 }} applications</span>
        <span>{{ scholarship.recipients_count || 0 }} recipients</span>
      </div>
      <div class="text-sm text-gray-600">
        {{ scholarship.max_recipients }} max recipients
      </div>
    </div>

    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-600">
        <span v-if="scholarship.is_open_for_applications" class="text-green-600 font-medium">
          Open for Applications
        </span>
        <span v-else class="text-red-600 font-medium">
          Applications Closed
        </span>
      </div>
      <div class="flex space-x-2">
        <button
          @click="$emit('view', scholarship)"
          class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
        >
          View Details
        </button>
        <button
          v-if="canEdit"
          @click="$emit('edit', scholarship)"
          class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors"
        >
          Edit
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Scholarship {
  id: number
  name: string
  description: string
  amount: number
  type: 'one_time' | 'recurring' | 'endowment'
  status: 'draft' | 'active' | 'paused' | 'closed'
  application_deadline: string
  max_recipients: number
  applications_count?: number
  recipients_count?: number
  is_open_for_applications?: boolean
  creator?: {
    id: number
    name: string
  }
}

interface Props {
  scholarship: Scholarship
  canEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false
})

defineEmits<{
  view: [scholarship: Scholarship]
  edit: [scholarship: Scholarship]
}>()

const statusClasses = computed(() => {
  const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium'
  switch (props.scholarship.status) {
    case 'active':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'draft':
      return `${baseClasses} bg-gray-100 text-gray-800`
    case 'paused':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'closed':
      return `${baseClasses} bg-red-100 text-red-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
})

const typeClasses = computed(() => {
  const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium'
  switch (props.scholarship.type) {
    case 'one_time':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'recurring':
      return `${baseClasses} bg-purple-100 text-purple-800`
    case 'endowment':
      return `${baseClasses} bg-indigo-100 text-indigo-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US').format(amount)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>