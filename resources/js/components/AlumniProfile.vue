<template>
  <div class="alumni-profile max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="profile-header bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-6">
      <div class="flex items-start space-x-6">
        <!-- Avatar -->
        <div class="avatar-section flex-shrink-0">
          <img
            :src="alumni.avatar_url || '/images/default-avatar.png'"
            :alt="alumni.name"
            class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
          />
          <div v-if="alumni.is_online" class="online-indicator absolute -mt-6 -mr-2 w-6 h-6 bg-green-400 border-4 border-white rounded-full"></div>
        </div>

        <!-- Basic Info -->
        <div class="profile-info flex-1">
          <div class="flex items-start justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ alumni.name }}</h1>
              
              <div v-if="currentPosition" class="current-position mb-3">
                <p class="text-xl text-gray-700 font-medium">{{ currentPosition.title }}</p>
                <p v-if="currentPosition.company" class="text-lg text-gray-600">
                  at {{ currentPosition.company }}
                </p>
              </div>
              
              <div class="location-info flex items-center text-gray-600 mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ alumni.location || 'Location not specified' }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="profile-actions flex space-x-3">
              <button
                v-if="canConnect"
                @click="$emit('connect', alumni)"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                Connect
              </button>
              
              <button
                v-else-if="connectionStatus === 'pending'"
                disabled
                class="bg-gray-300 text-gray-500 px-6 py-2 rounded-lg font-medium cursor-not-allowed"
              >
                Request Sent
              </button>
              
              <button
                v-else-if="connectionStatus === 'accepted'"
                class="bg-green-100 text-green-700 px-6 py-2 rounded-lg font-medium"
              >
                Connected
              </button>
              
              <button
                v-if="canMessage"
                class="bg-white text-gray-700 border border-gray-300 px-6 py-2 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                Message
              </button>
            </div>
          </div>

          <!-- Bio -->
          <div v-if="alumni.bio" class="bio-section mt-4">
            <p class="text-gray-700 leading-relaxed">{{ alumni.bio }}</p>
          </div>

          <!-- Social Profiles -->
          <div v-if="alumni.social_profiles && alumni.social_profiles.length > 0" class="social-profiles mt-4">
            <div class="flex space-x-4">
              <a
                v-for="profile in alumni.social_profiles"
                :key="profile.id"
                :href="getSocialProfileUrl(profile)"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-600 hover:text-gray-900 transition-colors"
              >
                <component :is="getSocialIcon(profile.provider)" class="w-6 h-6" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Content Grid -->
    <div class="profile-content grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="main-content lg:col-span-2 space-y-6">
        <!-- Career Timeline -->
        <div class="career-timeline bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
            </svg>
            Career Timeline
          </h2>
          
          <div v-if="alumni.work_experiences && alumni.work_experiences.length > 0" class="timeline">
            <div
              v-for="(experience, index) in alumni.work_experiences"
              :key="index"
              class="timeline-item relative pb-8"
              :class="{ 'pb-0': index === alumni.work_experiences.length - 1 }"
            >
              <!-- Timeline Line -->
              <div
                v-if="index < alumni.work_experiences.length - 1"
                class="absolute left-4 top-8 w-0.5 h-full bg-gray-200"
              ></div>
              
              <!-- Timeline Dot -->
              <div class="absolute left-2 top-2 w-4 h-4 bg-blue-600 rounded-full border-2 border-white shadow"></div>
              
              <!-- Experience Content -->
              <div class="ml-10">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ experience.title }}</h3>
                    <p class="text-blue-600 font-medium">{{ experience.company }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ formatDate(experience.start_date) }} - 
                      {{ experience.is_current ? 'Present' : formatDate(experience.end_date) }}
                    </p>
                  </div>
                  <span
                    v-if="experience.is_current"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    Current
                  </span>
                </div>
                
                <p v-if="experience.description" class="text-gray-700 mt-3 leading-relaxed">
                  {{ experience.description }}
                </p>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
            </svg>
            <p>No work experience information available</p>
          </div>
        </div>

        <!-- Education -->
        <div class="education-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            </svg>
            Education
          </h2>
          
          <div v-if="alumni.educations && alumni.educations.length > 0" class="education-list space-y-4">
            <div
              v-for="education in alumni.educations"
              :key="education.id"
              class="education-item p-4 border border-gray-200 rounded-lg"
            >
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">{{ education.institution.name }}</h3>
                  <p v-if="education.degree" class="text-gray-700">{{ education.degree }}</p>
                  <p v-if="education.field_of_study" class="text-gray-600">{{ education.field_of_study }}</p>
                </div>
                <div class="text-right text-sm text-gray-600">
                  <p v-if="education.graduation_year">Class of {{ education.graduation_year }}</p>
                  <p v-if="education.start_year && education.end_year">
                    {{ education.start_year }} - {{ education.end_year }}
                  </p>
                </div>
              </div>
              
              <p v-if="education.description" class="text-gray-700 mt-3">
                {{ education.description }}
              </p>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
            </svg>
            <p>No education information available</p>
          </div>
        </div>

        <!-- Skills -->
        <div v-if="alumni.skills && alumni.skills.length > 0" class="skills-section bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            Skills & Expertise
          </h2>
          
          <div class="skills-grid">
            <div class="flex flex-wrap gap-3">
              <span
                v-for="skill in alumni.skills"
                :key="skill"
                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
              >
                {{ skill }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="sidebar space-y-6">
        <!-- Mutual Connections -->
        <div v-if="mutualConnections.length > 0" class="mutual-connections bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Mutual Connections ({{ mutualConnections.length }})
          </h3>
          
          <div class="connections-list space-y-3">
            <div
              v-for="connection in mutualConnections.slice(0, 5)"
              :key="connection.id"
              class="connection-item flex items-center space-x-3"
            >
              <img
                :src="connection.avatar_url || '/images/default-avatar.png'"
                :alt="connection.name"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ connection.name }}</p>
              </div>
            </div>
          </div>
          
          <button
            v-if="mutualConnections.length > 5"
            class="mt-4 text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            View all {{ mutualConnections.length }} mutual connections
          </button>
        </div>

        <!-- Shared Communities -->
        <div v-if="sharedCommunities.length > 0" class="shared-communities bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Shared Communities
          </h3>
          
          <div class="communities-list space-y-3">
            <div
              v-for="community in sharedCommunities"
              :key="`${community.type}-${community.id}`"
              class="community-item flex items-center space-x-3"
            >
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg v-if="community.type === 'circle'" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ community.name }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ community.type }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Information -->
        <div v-if="canViewContactInfo" class="contact-info bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
          
          <div class="contact-details space-y-3">
            <div v-if="alumni.email" class="contact-item flex items-center space-x-3">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <a :href="`mailto:${alumni.email}`" class="text-blue-600 hover:text-blue-800">
                {{ alumni.email }}
              </a>
            </div>
            
            <div v-if="alumni.phone" class="contact-item flex items-center space-x-3">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <a :href="`tel:${alumni.phone}`" class="text-blue-600 hover:text-blue-800">
                {{ alumni.phone }}
              </a>
            </div>
            
            <div v-if="alumni.website" class="contact-item flex items-center space-x-3">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9m0 9c-5 0-9-4-9-9s4-9 9-9" />
              </svg>
              <a :href="alumni.website" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800">
                {{ alumni.website }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'AlumniProfile',
  props: {
    alumni: {
      type: Object,
      required: true
    }
  },
  emits: ['connect', 'message'],
  setup(props) {
    // Computed properties
    const currentPosition = computed(() => {
      if (!props.alumni.work_experiences || props.alumni.work_experiences.length === 0) {
        return null
      }
      
      return props.alumni.work_experiences.find(exp => exp.is_current) || 
             props.alumni.work_experiences[0]
    })

    const connectionStatus = computed(() => {
      return props.alumni.connection_status || 'none'
    })

    const canConnect = computed(() => {
      return connectionStatus.value === 'none'
    })

    const canMessage = computed(() => {
      return connectionStatus.value === 'accepted'
    })

    const canViewContactInfo = computed(() => {
      return props.alumni.email || props.alumni.phone || props.alumni.website
    })

    const mutualConnections = computed(() => {
      return props.alumni.mutual_connections || []
    })

    const sharedCommunities = computed(() => {
      const communities = []
      
      if (props.alumni.shared_circles) {
        communities.push(...props.alumni.shared_circles.map(circle => ({
          ...circle,
          type: 'circle'
        })))
      }
      
      if (props.alumni.shared_groups) {
        communities.push(...props.alumni.shared_groups.map(group => ({
          ...group,
          type: 'group'
        })))
      }
      
      return communities
    })

    // Methods
    const formatDate = (dateString) => {
      if (!dateString) return ''
      
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long'
      })
    }

    const getSocialProfileUrl = (profile) => {
      // This would typically be handled by the backend
      const baseUrls = {
        linkedin: 'https://linkedin.com/in/',
        github: 'https://github.com/',
        twitter: 'https://twitter.com/',
        facebook: 'https://facebook.com/'
      }
      
      return profile.profile_data?.url || `${baseUrls[profile.provider] || ''}${profile.provider_id}`
    }

    const getSocialIcon = (provider) => {
      // Return appropriate icon component based on provider
      // This would typically use a proper icon library
      return 'div' // Placeholder
    }

    return {
      currentPosition,
      connectionStatus,
      canConnect,
      canMessage,
      canViewContactInfo,
      mutualConnections,
      sharedCommunities,
      formatDate,
      getSocialProfileUrl,
      getSocialIcon
    }
  }
}
</script>

<style scoped>
.alumni-profile {
  @apply py-8;
}

.avatar-section {
  @apply relative;
}

.online-indicator {
  @apply absolute;
}

.timeline-item:last-child .absolute {
  @apply hidden;
}

.contact-item a {
  @apply transition-colors duration-150;
}
</style>