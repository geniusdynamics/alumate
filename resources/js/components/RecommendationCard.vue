<template>
  <div class="recommendation-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <!-- User Info -->
    <div class="flex items-start space-x-4 mb-4">
      <div class="flex-shrink-0">
        <img
          :src="recommendation.user.avatar_url || '/images/default-avatar.png'"
          :alt="recommendation.user.name"
          class="w-16 h-16 rounded-full object-cover"
        />
      </div>
      
      <div class="flex-1 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 truncate">
          {{ recommendation.user.name }}
        </h3>
        
        <p v-if="recommendation.user.current_title" class="text-sm text-gray-600 truncate">
          {{ recommendation.user.current_title }}
          <span v-if="recommendation.user.current_company">
            at {{ recommendation.user.current_company }}
          </span>
        </p>
        
        <p v-if="recommendation.user.location" class="text-sm text-gray-500 mt-1">
          <Icon name="map-pin" class="w-4 h-4 inline mr-1" />
          {{ recommendation.user.location }}
        </p>
        
        <div class="flex items-center mt-2">
          <div class="flex items-center text-sm text-blue-600">
            <Icon name="star" class="w-4 h-4 mr-1" />
            <span>{{ Math.round(recommendation.score * 100) }}% match</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Connection Reasons -->
    <ConnectionReasons 
      :reasons="recommendation.reasons"
      :mutual-connections="recommendation.mutual_connections"
      :shared-circles="recommendation.shared_circles"
      class="mb-4"
    />

    <!-- Mutual Connections Preview -->
    <div v-if="recommendation.mutual_connections && recommendation.mutual_connections.length > 0" class="mb-4">
      <div class="flex items-center space-x-2">
        <div class="flex -space-x-2">
          <img
            v-for="connection in recommendation.mutual_connections.slice(0, 3)"
            :key="connection.id"
            :src="connection.avatar_url || '/images/default-avatar.png'"
            :alt="connection.name"
            :title="connection.name"
            class="w-6 h-6 rounded-full border-2 border-white object-cover"
          />
        </div>
        <span class="text-sm text-gray-600">
          {{ recommendation.mutual_connections.length }} mutual connection{{ recommendation.mutual_connections.length !== 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
      <div class="flex space-x-2">
        <button
          @click="$emit('connect', recommendation)"
          :disabled="connecting"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors text-sm font-medium"
        >
          <Icon v-if="connecting" name="spinner" class="w-4 h-4 mr-2 animate-spin" />
          <Icon v-else name="user-plus" class="w-4 h-4 mr-2" />
          Connect
        </button>
        
        <button
          @click="viewProfile"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors text-sm font-medium"
        >
          <Icon name="eye" class="w-4 h-4 mr-2" />
          View
        </button>
      </div>
      
      <div class="flex items-center space-x-1 ml-4">
        <button
          @click="$emit('dismiss', recommendation)"
          :title="'Dismiss recommendation'"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
        >
          <Icon name="x" class="w-4 h-4" />
        </button>
        
        <button
          @click="$emit('feedback', recommendation)"
          :title="'Provide feedback'"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
        >
          <Icon name="flag" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Selection checkbox for bulk actions -->
    <div v-if="showBulkSelect" class="absolute top-4 right-4">
      <input
        :id="`select-${recommendation.user.id}`"
        v-model="isSelected"
        type="checkbox"
        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ConnectionReasons from './ConnectionReasons.vue'
import Icon from './Icon.vue'

const props = defineProps({
  recommendation: {
    type: Object,
    required: true
  },
  showBulkSelect: {
    type: Boolean,
    default: false
  },
  selected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['connect', 'dismiss', 'feedback', 'select'])

const connecting = ref(false)

const isSelected = computed({
  get: () => props.selected,
  set: (value) => emit('select', { recommendation: props.recommendation, selected: value })
})

const viewProfile = () => {
  router.visit(`/alumni/${props.recommendation.user.id}`)
}
</script>

<style scoped>
.recommendation-card {
  position: relative;
}
</style>