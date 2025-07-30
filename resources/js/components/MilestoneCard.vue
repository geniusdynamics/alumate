<template>
  <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200 p-6 shadow-sm hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-start space-x-4">
        <!-- Milestone icon -->
        <div class="flex-shrink-0">
          <div :class="[
            'w-12 h-12 rounded-full flex items-center justify-center',
            getIconBackground(milestone.type)
          ]">
            <component :is="getIcon(milestone.type)" :class="[
              'w-6 h-6',
              getIconColor(milestone.type)
            ]" />
          </div>
        </div>

        <!-- Milestone info -->
        <div class="flex-1">
          <div class="flex items-center space-x-2 mb-1">
            <h3 class="text-lg font-semibold text-gray-900">{{ milestone.title }}</h3>
            <span v-if="milestone.is_featured" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
              <StarIcon class="w-3 h-3 mr-1" />
              Featured
            </span>
          </div>
          
          <p class="text-sm text-gray-600 mb-2">{{ formatMilestoneType(milestone.type) }}</p>
          
          <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span class="flex items-center">
              <CalendarIcon class="w-4 h-4 mr-1" />
              {{ formatDate(milestone.date) }}
            </span>
            <span v-if="milestone.company" class="flex items-center">
              <BuildingOfficeIcon class="w-4 h-4 mr-1" />
              {{ milestone.company }}
            </span>
            <span v-else-if="milestone.organization" class="flex items-center">
              <AcademicCapIcon class="w-4 h-4 mr-1" />
              {{ milestone.organization }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="canEdit" class="flex items-center space-x-2">
        <button
          @click="$emit('edit', milestone)"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          title="Edit milestone"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button
          @click="$emit('delete', milestone.id)"
          class="p-2 text-gray-400 hover:text-red-600 transition-colors"
          title="Delete milestone"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Description -->
    <div v-if="milestone.description" class="mb-4">
      <p class="text-gray-700 leading-relaxed">{{ milestone.description }}</p>
    </div>

    <!-- Metadata -->
    <div v-if="milestone.metadata && Object.keys(milestone.metadata).length > 0" class="mb-4">
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div v-for="(value, key) in milestone.metadata" :key="key">
          <span class="font-medium text-gray-600">{{ formatMetadataKey(key) }}:</span>
          <span class="text-gray-800 ml-1">{{ value }}</span>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <span :class="[
          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
          getTypeColor(milestone.type)
        ]">
          {{ formatMilestoneType(milestone.type) }}
        </span>
        
        <span :class="[
          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
          getVisibilityColor(milestone.visibility)
        ]">
          {{ formatVisibility(milestone.visibility) }}
        </span>
      </div>

      <!-- Social actions -->
      <div class="flex items-center space-x-2">
        <button
          v-if="!canEdit"
          @click="congratulate"
          class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full hover:bg-green-200 transition-colors"
        >
          <HandThumbUpIcon class="w-3 h-3 mr-1" />
          Congratulate
        </button>
        
        <button
          @click="share"
          class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200 transition-colors"
        >
          <ShareIcon class="w-3 h-3 mr-1" />
          Share
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  StarIcon,
  CalendarIcon,
  BuildingOfficeIcon,
  AcademicCapIcon,
  PencilIcon,
  TrashIcon,
  HandThumbUpIcon,
  ShareIcon,
  TrophyIcon,
  BriefcaseIcon,
  AwardIcon,
  CertificateIcon,
  FlagIcon,
  TrendingUpIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  milestone: {
    type: Object,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: false
  }
})

defineEmits(['edit', 'delete', 'congratulate', 'share'])

// Methods
const getIcon = (type) => {
  const icons = {
    'promotion': TrendingUpIcon,
    'job_change': BriefcaseIcon,
    'award': AwardIcon,
    'certification': CertificateIcon,
    'education': AcademicCapIcon,
    'achievement': TrophyIcon
  }
  
  return icons[type] || FlagIcon
}

const getIconBackground = (type) => {
  const backgrounds = {
    'promotion': 'bg-green-100',
    'job_change': 'bg-blue-100',
    'award': 'bg-yellow-100',
    'certification': 'bg-purple-100',
    'education': 'bg-indigo-100',
    'achievement': 'bg-orange-100'
  }
  
  return backgrounds[type] || 'bg-gray-100'
}

const getIconColor = (type) => {
  const colors = {
    'promotion': 'text-green-600',
    'job_change': 'text-blue-600',
    'award': 'text-yellow-600',
    'certification': 'text-purple-600',
    'education': 'text-indigo-600',
    'achievement': 'text-orange-600'
  }
  
  return colors[type] || 'text-gray-600'
}

const getTypeColor = (type) => {
  const colors = {
    'promotion': 'bg-green-100 text-green-800',
    'job_change': 'bg-blue-100 text-blue-800',
    'award': 'bg-yellow-100 text-yellow-800',
    'certification': 'bg-purple-100 text-purple-800',
    'education': 'bg-indigo-100 text-indigo-800',
    'achievement': 'bg-orange-100 text-orange-800'
  }
  
  return colors[type] || 'bg-gray-100 text-gray-800'
}

const getVisibilityColor = (visibility) => {
  const colors = {
    'public': 'bg-green-100 text-green-800',
    'connections': 'bg-blue-100 text-blue-800',
    'private': 'bg-gray-100 text-gray-800'
  }
  
  return colors[visibility] || 'bg-gray-100 text-gray-800'
}

const formatMilestoneType = (type) => {
  const types = {
    'promotion': 'Promotion',
    'job_change': 'Job Change',
    'award': 'Award',
    'certification': 'Certification',
    'education': 'Education',
    'achievement': 'Achievement'
  }
  
  return types[type] || type
}

const formatVisibility = (visibility) => {
  const visibilities = {
    'public': 'Public',
    'connections': 'Connections',
    'private': 'Private'
  }
  
  return visibilities[visibility] || visibility
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatMetadataKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const congratulate = () => {
  // Implement congratulation logic
  console.log('Congratulating milestone:', props.milestone.id)
}

const share = () => {
  // Implement sharing logic
  console.log('Sharing milestone:', props.milestone.id)
}
</script>