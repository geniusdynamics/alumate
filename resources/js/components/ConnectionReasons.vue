<template>
  <div class="connection-reasons">
    <div v-if="reasons.length > 0" class="space-y-2">
      <div
        v-for="reason in reasons"
        :key="reason.type"
        class="flex items-start space-x-2 text-sm"
      >
        <div class="flex-shrink-0 mt-0.5">
          <Icon :name="getReasonIcon(reason.type)" :class="getReasonIconClass(reason.type)" />
        </div>
        
        <div class="flex-1">
          <span class="text-gray-700">{{ reason.message }}</span>
          
          <!-- Details for specific reason types -->
          <div v-if="reason.details && reason.details.length > 0" class="mt-1">
            <div v-if="reason.type === 'shared_circles'" class="flex flex-wrap gap-1">
              <span
                v-for="circle in reason.details.slice(0, 3)"
                :key="circle"
                class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
              >
                {{ circle }}
              </span>
              <span
                v-if="reason.details.length > 3"
                class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
              >
                +{{ reason.details.length - 3 }} more
              </span>
            </div>
            
            <div v-else-if="reason.type === 'mutual_connections'" class="text-xs text-gray-500">
              <span v-for="(name, index) in reason.details" :key="name">
                {{ name }}<span v-if="index < reason.details.length - 1">, </span>
              </span>
              <span v-if="mutualConnections && mutualConnections.length > reason.details.length">
                and {{ mutualConnections.length - reason.details.length }} others
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Fallback if no specific reasons -->
    <div v-else class="flex items-center space-x-2 text-sm text-gray-500">
      <Icon name="users" class="w-4 h-4" />
      <span>Suggested based on your network</span>
    </div>
  </div>
</template>

<script setup>
import Icon from './Icon.vue'

const props = defineProps({
  reasons: {
    type: Array,
    default: () => []
  },
  mutualConnections: {
    type: Array,
    default: () => []
  },
  sharedCircles: {
    type: Array,
    default: () => []
  }
})

const getReasonIcon = (type) => {
  const iconMap = {
    'shared_circles': 'users',
    'mutual_connections': 'user-check',
    'similar_interests': 'heart',
    'same_location': 'map-pin',
    'same_school': 'academic-cap',
    'same_company': 'building-office'
  }
  
  return iconMap[type] || 'star'
}

const getReasonIconClass = (type) => {
  const classMap = {
    'shared_circles': 'w-4 h-4 text-blue-500',
    'mutual_connections': 'w-4 h-4 text-green-500',
    'similar_interests': 'w-4 h-4 text-pink-500',
    'same_location': 'w-4 h-4 text-purple-500',
    'same_school': 'w-4 h-4 text-indigo-500',
    'same_company': 'w-4 h-4 text-orange-500'
  }
  
  return classMap[type] || 'w-4 h-4 text-gray-500'
}
</script>
</style>