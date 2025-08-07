<template>
  <div 
    class="map-marker"
    :class="markerClass"
    @click="handleClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <div class="marker-content">
      <img 
        v-if="alumni.profile_photo_path" 
        :src="alumni.profile_photo_path" 
        :alt="`${alumni.first_name} ${alumni.last_name}`"
        class="marker-photo"
        @error="handleImageError"
      />
      <div v-else class="marker-initials">
        {{ getInitials(alumni.first_name, alumni.last_name) }}
      </div>
    </div>
    
    <!-- Status indicator -->
    <div 
      v-if="alumni.online_status" 
      class="status-indicator"
      :class="alumni.online_status"
    ></div>
    
    <!-- Hover tooltip -->
    <div v-if="showTooltip" class="marker-tooltip">
      <div class="tooltip-header">
        <strong>{{ alumni.first_name }} {{ alumni.last_name }}</strong>
        <span class="graduation-year">{{ alumni.graduation_year }}</span>
      </div>
      <div class="tooltip-body">
        <p v-if="alumni.current_position" class="position">
          {{ alumni.current_position }}
        </p>
        <p v-if="alumni.current_company" class="company">
          {{ alumni.current_company }}
        </p>
        <p v-if="alumni.industry" class="industry">
          <i class="fas fa-industry"></i> {{ alumni.industry }}
        </p>
        <p class="location">
          <i class="fas fa-map-marker-alt"></i> 
          {{ formatLocation(alumni) }}
        </p>
      </div>
      <div class="tooltip-actions">
        <button 
          class="btn btn-sm btn-primary"
          @click.stop="viewProfile"
        >
          View Profile
        </button>
        <button 
          v-if="canConnect"
          class="btn btn-sm btn-outline-primary"
          @click.stop="sendConnection"
        >
          Connect
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Alumni {
  id: number
  first_name: string
  last_name: string
  graduation_year: number
  current_position?: string
  current_company?: string
  industry?: string
  city?: string
  state?: string
  country?: string
  profile_photo_path?: string
  online_status?: 'online' | 'away' | 'offline'
  latitude: number
  longitude: number
  profile_visibility: string
}

interface MapMarkerProps {
  alumni: Alumni
  selected?: boolean
  size?: 'small' | 'medium' | 'large'
  interactive?: boolean
}

const props = withDefaults(defineProps<MapMarkerProps>(), {
  selected: false,
  size: 'medium',
  interactive: true
})

const emit = defineEmits<{
  click: [alumni: Alumni]
  hover: [alumni: Alumni]
  connect: [alumni: Alumni]
}>()

const showTooltip = ref(false)
const imageError = ref(false)

const markerClass = computed(() => ({
  'marker-selected': props.selected,
  'marker-small': props.size === 'small',
  'marker-medium': props.size === 'medium',
  'marker-large': props.size === 'large',
  'marker-interactive': props.interactive
}))

const canConnect = computed(() => {
  // Logic to determine if current user can connect with this alumni
  // This would check if they're already connected, if connection is pending, etc.
  return true // Simplified for now
})

const getInitials = (firstName: string, lastName: string): string => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const formatLocation = (alumni: Alumni): string => {
  const parts = [alumni.city, alumni.state, alumni.country].filter(Boolean)
  return parts.join(', ')
}

const handleClick = () => {
  if (props.interactive) {
    emit('click', props.alumni)
  }
}

const handleMouseEnter = () => {
  if (props.interactive) {
    showTooltip.value = true
    emit('hover', props.alumni)
  }
}

const handleMouseLeave = () => {
  showTooltip.value = false
}

const handleImageError = () => {
  imageError.value = true
}

const viewProfile = () => {
  router.visit(`/alumni/${props.alumni.id}`)
}

const sendConnection = () => {
  emit('connect', props.alumni)
}
</script>

<style scoped>
.map-marker {
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
}

.marker-content {
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.marker-small .marker-content {
  width: 30px;
  height: 30px;
  border-width: 2px;
}

.marker-medium .marker-content {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

.marker-large .marker-content {
  width: 50px;
  height: 50px;
  border-width: 3px;
}

.marker-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.marker-initials {
  font-weight: bold;
  color: #495057;
  font-size: 0.8em;
}

.marker-small .marker-initials {
  font-size: 0.6em;
}

.marker-large .marker-initials {
  font-size: 1em;
}

.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-indicator.online {
  background-color: #28a745;
}

.status-indicator.away {
  background-color: #ffc107;
}

.status-indicator.offline {
  background-color: #6c757d;
}

.marker-interactive:hover .marker-content {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.marker-selected .marker-content {
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.marker-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 12px;
  min-width: 250px;
  z-index: 1000;
  margin-bottom: 8px;
}

.marker-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: white;
}

.tooltip-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e9ecef;
}

.graduation-year {
  color: #6c757d;
  font-size: 0.9em;
}

.tooltip-body p {
  margin: 4px 0;
  font-size: 0.9em;
}

.position {
  font-weight: 600;
  color: #495057;
}

.company {
  color: #6c757d;
}

.industry,
.location {
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 4px;
}

.industry i,
.location i {
  width: 12px;
  font-size: 0.8em;
}

.tooltip-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 8px;
  border-top: 1px solid #e9ecef;
}

.tooltip-actions .btn {
  flex: 1;
  font-size: 0.8em;
  padding: 4px 8px;
}

/* Animation for marker appearance */
@keyframes markerAppear {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.map-marker {
  animation: markerAppear 0.3s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .marker-tooltip {
    min-width: 200px;
    font-size: 0.9em;
  }
  
  .tooltip-actions {
    flex-direction: column;
  }
  
  .tooltip-actions .btn {
    flex: none;
  }
}
</style>