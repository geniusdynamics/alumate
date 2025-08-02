<template>
  <div class="alumni-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden">
    <!-- Header with Avatar and Basic Info -->
    <div class="card-header p-6 pb-4">
      <div class="flex items-start space-x-4">
        <div class="avatar-container flex-shrink-0">
          <img
            :src="alumni.avatar_url || '/images/default-avatar.png'"
            :alt="alumni.name"
            class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
          />
          <div v-if="isOnline" class="online-indicator absolute -mt-3 -mr-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></div>
        </div>
        
        <div class="alumni-info flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-gray-900 truncate">
            {{ alumni.name }}
          </h3>
          
          <p v-if="currentPosition" class="text-sm text-gray-600 truncate">
            {{ currentPosition.title }}
            <span v-if="currentPosition.company" class="text-gray-500">
              at {{ currentPosition.company }}
            </span>
          </p>
          
          <p v-if="alumni.location" class="text-sm text-gray-500 flex items-center mt-1">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ alumni.location }}
          </p>
        </div>
      </div>
    </div>

    <!-- Education Info -->
    <div v-if="primaryEducation" class="education-info px-6 pb-4">
      <div class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
        </svg>
        <span>{{ primaryEducation.institution.name }}</span>
        <span v-if="primaryEducation.graduation_year" class="ml-2 text-gray-500">
          '{{ primaryEducation.graduation_year.toString().slice(-2) }}
        </span>
      </div>
    </div>

    <!-- Shared Connections -->
    <div v-if="sharedConnections.length > 0" class="shared-connections px-6 pb-4">
      <div class="flex items-center text-sm text-gray-600">
        <div class="flex -space-x-2 mr-3">
          <img
            v-for="connection in sharedConnections.slice(0, 3)"
            :key="connection.id"
            :src="connection.avatar_url || '/images/default-avatar.png'"
            :alt="connection.name"
            class="w-6 h-6 rounded-full border-2 border-white object-cover"
          />
        </div>
        <span>
          {{ sharedConnections.length }} mutual connection{{ sharedConnections.length !== 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <!-- Skills Tags -->
    <div v-if="displaySkills.length > 0" class="skills-section px-6 pb-4">
      <div class="flex flex-wrap gap-2">
        <span
          v-for="skill in displaySkills"
          :key="skill"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          {{ skill }}
        </span>
        <span
          v-if="remainingSkillsCount > 0"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
        >
          +{{ remainingSkillsCount }} more
        </span>
      </div>
    </div>

    <!-- Shared Circles/Groups -->
    <div v-if="sharedCommunities.length > 0" class="shared-communities px-6 pb-4">
      <div class="text-sm text-gray-600">
        <span class="font-medium">Shared communities:</span>
        <span class="ml-1">
          {{ sharedCommunities.slice(0, 2).map(c => c.name).join(', ') }}
          <span v-if="sharedCommunities.length > 2" class="text-gray-500">
            +{{ sharedCommunities.length - 2 }} more
          </span>
        </span>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="card-actions px-6 py-4 bg-gray-50 border-t border-gray-200">
      <div class="flex space-x-3">
        <button
          @click="$emit('view-profile', alumni)"
          class="flex-1 bg-white text-gray-700 border border-gray-300 rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
        >
          View Profile
        </button>
        
        <button
          v-if="canConnect"
          @click="$emit('connect', alumni)"
          class="flex-1 bg-blue-600 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
        >
          Connect
        </button>
        
        <button
          v-else-if="connectionStatus === 'pending'"
          disabled
          class="flex-1 bg-gray-300 text-gray-500 rounded-md px-4 py-2 text-sm font-medium cursor-not-allowed"
        >
          Request Sent
        </button>
        
        <button
          v-else-if="connectionStatus === 'accepted'"
          disabled
          class="flex-1 bg-green-100 text-green-700 rounded-md px-4 py-2 text-sm font-medium cursor-not-allowed"
        >
          Connected
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'AlumniCard',
  props: {
    alumni: {
      type: Object,
      required: true
    }
  },
  emits: ['view-profile', 'connect'],
  setup(props) {
    // Computed properties
    const currentPosition = computed(() => {
      if (!props.alumni.work_experiences || props.alumni.work_experiences.length === 0) {
        return null
      }
      
      return props.alumni.work_experiences.find(exp => exp.is_current) || 
             props.alumni.work_experiences[0]
    })

    const primaryEducation = computed(() => {
      if (!props.alumni.educations || props.alumni.educations.length === 0) {
        return null
      }
      
      // Return the most recent education or the first one
      return props.alumni.educations.sort((a, b) => 
        (b.graduation_year || 0) - (a.graduation_year || 0)
      )[0]
    })

    const displaySkills = computed(() => {
      if (!props.alumni.skills || !Array.isArray(props.alumni.skills)) {
        return []
      }
      
      return props.alumni.skills.slice(0, 3)
    })

    const remainingSkillsCount = computed(() => {
      if (!props.alumni.skills || !Array.isArray(props.alumni.skills)) {
        return 0
      }
      
      return Math.max(0, props.alumni.skills.length - 3)
    })

    const sharedConnections = computed(() => {
      return props.alumni.mutual_connections || []
    })

    const sharedCommunities = computed(() => {
      const communities = []
      
      if (props.alumni.shared_circles) {
        communities.push(...props.alumni.shared_circles)
      }
      
      if (props.alumni.shared_groups) {
        communities.push(...props.alumni.shared_groups)
      }
      
      return communities
    })

    const connectionStatus = computed(() => {
      return props.alumni.connection_status || 'none'
    })

    const canConnect = computed(() => {
      return connectionStatus.value === 'none'
    })

    const isOnline = computed(() => {
      // This would typically come from a real-time presence system
      return props.alumni.is_online || false
    })

    return {
      currentPosition,
      primaryEducation,
      displaySkills,
      remainingSkillsCount,
      sharedConnections,
      sharedCommunities,
      connectionStatus,
      canConnect,
      isOnline
    }
  }
}
</script>

<style scoped>
.alumni-card {
  @apply relative;
}

.avatar-container {
  @apply relative;
}

.online-indicator {
  position: absolute;
  top: 0;
  right: 0;
}

.card-actions button:disabled {
  @apply cursor-not-allowed opacity-60;
}

.skills-section .inline-flex {
  @apply transition-colors duration-150;
}

.shared-connections img {
  box-shadow: 0 0 0 2px white;
}
</style>