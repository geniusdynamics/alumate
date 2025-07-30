<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-start space-x-4">
        <!-- Company logo -->
        <div class="flex-shrink-0">
          <img
            v-if="entry.company_logo_url"
            :src="entry.company_logo_url"
            :alt="entry.company"
            class="w-12 h-12 rounded-lg object-cover border border-gray-200"
          />
          <div
            v-else
            class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200"
          >
            <BriefcaseIcon class="w-6 h-6 text-gray-400" />
          </div>
        </div>

        <!-- Position info -->
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-gray-900">{{ entry.title }}</h3>
          <p class="text-base text-gray-700 font-medium">{{ entry.company }}</p>
          <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
            <span class="flex items-center">
              <CalendarIcon class="w-4 h-4 mr-1" />
              {{ formatDateRange(entry.start_date, entry.end_date, entry.is_current) }}
            </span>
            <span class="flex items-center">
              <ClockIcon class="w-4 h-4 mr-1" />
              {{ entry.formatted_duration }}
            </span>
            <span v-if="entry.location" class="flex items-center">
              <MapPinIcon class="w-4 h-4 mr-1" />
              {{ entry.location }}
            </span>
          </div>
          <div v-if="entry.employment_type" class="mt-1">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {{ formatEmploymentType(entry.employment_type) }}
            </span>
            <span v-if="entry.is_current" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Current
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="canEdit" class="flex items-center space-x-2">
        <button
          @click="$emit('edit', entry)"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          title="Edit position"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button
          @click="$emit('delete', entry.id)"
          class="p-2 text-gray-400 hover:text-red-600 transition-colors"
          title="Delete position"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Description -->
    <div v-if="entry.description" class="mb-4">
      <p class="text-gray-700 leading-relaxed">{{ entry.description }}</p>
    </div>

    <!-- Achievements -->
    <div v-if="entry.achievements && entry.achievements.length > 0" class="mb-4">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Key Achievements</h4>
      <ul class="space-y-1">
        <li
          v-for="(achievement, index) in entry.achievements"
          :key="index"
          class="flex items-start text-sm text-gray-700"
        >
          <span class="text-green-500 mr-2 mt-0.5">•</span>
          <span>{{ achievement }}</span>
        </li>
      </ul>
    </div>

    <!-- Industry tag -->
    <div v-if="entry.industry" class="flex items-center justify-between">
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
        {{ entry.industry }}
      </span>
      
      <!-- Promotion indicator -->
      <div v-if="isPromotion" class="flex items-center text-xs text-green-600">
        <TrendingUpIcon class="w-4 h-4 mr-1" />
        Promotion
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  BriefcaseIcon,
  CalendarIcon,
  ClockIcon,
  MapPinIcon,
  PencilIcon,
  TrashIcon,
  TrendingUpIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  entry: {
    type: Object,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: false
  },
  previousEntry: {
    type: Object,
    default: null
  }
})

defineEmits(['edit', 'delete'])

// Computed
const isPromotion = computed(() => {
  if (!props.previousEntry) return false
  
  return props.entry.company === props.previousEntry.company &&
         new Date(props.entry.start_date) >= new Date(props.previousEntry.start_date)
})

// Methods
const formatDateRange = (startDate, endDate, isCurrent) => {
  const start = new Date(startDate).toLocaleDateString('en-US', {
    month: 'short',
    year: 'numeric'
  })
  
  if (isCurrent) {
    return `${start} - Present`
  }
  
  if (!endDate) {
    return start
  }
  
  const end = new Date(endDate).toLocaleDateString('en-US', {
    month: 'short',
    year: 'numeric'
  })
  
  return `${start} - ${end}`
}

const formatEmploymentType = (type) => {
  const types = {
    'full-time': 'Full-time',
    'part-time': 'Part-time',
    'contract': 'Contract',
    'internship': 'Internship',
    'freelance': 'Freelance'
  }
  
  return types[type] || type
}
</script>