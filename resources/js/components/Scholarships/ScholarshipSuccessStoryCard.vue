<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
    <div class="p-6">
      <div class="flex items-start space-x-4">
        <div class="flex-shrink-0">
          <img
            :src="recipient.recipient?.avatar || '/default-avatar.png'"
            :alt="recipient.recipient?.name"
            class="w-16 h-16 rounded-full object-cover"
          />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">
                {{ recipient.recipient?.name }}
              </h3>
              <p class="text-sm text-gray-600">
                {{ recipient.scholarship?.name }} Recipient
              </p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">
                {{ formatDate(recipient.award_date) }}
              </p>
              <p class="text-sm font-medium text-green-600">
                ${{ formatCurrency(recipient.awarded_amount) }}
              </p>
            </div>
          </div>
          
          <div class="mt-4">
            <p class="text-gray-700 leading-relaxed">
              {{ recipient.success_story }}
            </p>
          </div>

          <div v-if="recipient.thank_you_message" class="mt-4 p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800 italic">
              "{{ recipient.thank_you_message }}"
            </p>
          </div>

          <div v-if="recipient.academic_progress || recipient.impact_metrics" class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="recipient.academic_progress?.current_gpa">
                <p class="text-sm text-gray-600">Current GPA</p>
                <p class="font-semibold text-gray-900">{{ recipient.academic_progress.current_gpa }}</p>
              </div>
              <div v-if="recipient.academic_progress?.graduation_status">
                <p class="text-sm text-gray-600">Status</p>
                <p class="font-semibold text-gray-900">{{ recipient.academic_progress.graduation_status }}</p>
              </div>
              <div v-if="recipient.impact_metrics?.career_advancement">
                <p class="text-sm text-gray-600">Career Impact</p>
                <p class="font-semibold text-gray-900">{{ recipient.impact_metrics.career_advancement }}</p>
              </div>
              <div v-if="recipient.impact_metrics?.community_involvement">
                <p class="text-sm text-gray-600">Community Impact</p>
                <p class="font-semibold text-gray-900">{{ recipient.impact_metrics.community_involvement }}</p>
              </div>
            </div>
          </div>

          <div v-if="recipient.updates && recipient.updates.length > 0" class="mt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Recent Updates</h4>
            <div class="space-y-2">
              <div
                v-for="(update, index) in recipient.updates.slice(0, 2)"
                :key="index"
                class="text-sm text-gray-600 p-2 bg-gray-50 rounded"
              >
                <p class="font-medium">{{ update.title }}</p>
                <p>{{ update.description }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ formatDate(update.date) }}</p>
              </div>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center space-x-4 text-sm text-gray-500">
              <span>{{ yearsAgo }} years ago</span>
              <span v-if="recipient.status" :class="statusClasses">
                {{ recipient.status }}
              </span>
            </div>
            <div class="flex space-x-2">
              <button
                v-if="canEdit"
                @click="$emit('edit', recipient)"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                Edit Story
              </button>
              <button
                @click="$emit('share', recipient)"
                class="text-sm text-gray-600 hover:text-gray-800 font-medium"
              >
                Share
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface ScholarshipRecipient {
  id: number
  awarded_amount: number
  award_date: string
  status: 'awarded' | 'active' | 'completed' | 'revoked'
  success_story: string
  thank_you_message?: string
  academic_progress?: {
    current_gpa?: number
    graduation_status?: string
  }
  impact_metrics?: {
    career_advancement?: string
    community_involvement?: string
  }
  updates?: Array<{
    title: string
    description: string
    date: string
  }>
  recipient?: {
    id: number
    name: string
    avatar?: string
  }
  scholarship?: {
    id: number
    name: string
  }
}

interface Props {
  recipient: ScholarshipRecipient
  canEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false
})

defineEmits<{
  edit: [recipient: ScholarshipRecipient]
  share: [recipient: ScholarshipRecipient]
}>()

const yearsAgo = computed(() => {
  const awardDate = new Date(props.recipient.award_date)
  const now = new Date()
  return Math.floor((now.getTime() - awardDate.getTime()) / (1000 * 60 * 60 * 24 * 365))
})

const statusClasses = computed(() => {
  const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium'
  switch (props.recipient.status) {
    case 'active':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'completed':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'awarded':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'revoked':
      return `${baseClasses} bg-red-100 text-red-800`
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