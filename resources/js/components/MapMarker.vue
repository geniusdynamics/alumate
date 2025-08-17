<template>
  <div class="map-marker" :class="markerClass">
    <div class="marker-content" @click="handleClick">
      <div class="marker-avatar">
        <img 
          :src="alumni.avatar_url || '/images/default-avatar.png'" 
          :alt="alumni.name"
          class="avatar-image"
        />
      </div>
      <div class="marker-info" v-if="showInfo">
        <h4 class="alumni-name">{{ alumni.name }}</h4>
        <p class="alumni-title" v-if="alumni.current_title">
          {{ alumni.current_title }}
        </p>
        <p class="alumni-company" v-if="alumni.current_company">
          at {{ alumni.current_company }}
        </p>
        <div class="marker-actions">
          <button 
            @click.stop="viewProfile" 
            class="btn-primary btn-sm"
            :aria-label="`View ${alumni.name}'s profile`"
          >
            View Profile
          </button>
          <button 
            @click.stop="sendMessage" 
            class="btn-secondary btn-sm"
            :aria-label="`Send message to ${alumni.name}`"
          >
            Message
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Alumni {
  id: number
  name: string
  avatar_url?: string
  current_title?: string
  current_company?: string
  latitude: number
  longitude: number
}

interface Props {
  alumni: Alumni
  isSelected?: boolean
  showInfo?: boolean
  size?: 'small' | 'medium' | 'large'
}

const props = withDefaults(defineProps<Props>(), {
  isSelected: false,
  showInfo: false,
  size: 'medium'
})

const emit = defineEmits<{
  select: [alumni: Alumni]
  message: [alumni: Alumni]
}>()

const markerClass = computed(() => ({
  'marker-selected': props.isSelected,
  'marker-with-info': props.showInfo,
  [`marker-${props.size}`]: true
}))

const handleClick = () => {
  emit('select', props.alumni)
}

const viewProfile = () => {
  router.visit(`/alumni/${props.alumni.id}`)
}

const sendMessage = () => {
  emit('message', props.alumni)
}
</script>

<style scoped>
.map-marker {
  position: relative;
  cursor: pointer;
  z-index: 1000;
}

.marker-content {
  position: relative;
  transition: all 0.2s ease;
}

.marker-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  background: #f3f4f6;
  transition: all 0.2s ease;
}

.marker-small .marker-avatar {
  width: 32px;
  height: 32px;
  border-width: 2px;
}

.marker-large .marker-avatar {
  width: 48px;
  height: 48px;
  border-width: 4px;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.marker-selected .marker-avatar {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  transform: scale(1.1);
}

.marker-info {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 8px;
  padding: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  min-width: 200px;
  max-width: 250px;
  margin-top: 8px;
  z-index: 1001;
}

.marker-info::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid white;
}

.alumni-name {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.alumni-title {
  font-size: 12px;
  color: #6b7280;
  margin: 0 0 2px 0;
}

.alumni-company {
  font-size: 12px;
  color: #6b7280;
  margin: 0 0 8px 0;
}

.marker-actions {
  display: flex;
  gap: 6px;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 11px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

/* Hover effects */
.map-marker:hover .marker-avatar {
  transform: scale(1.05);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
}

.marker-selected:hover .marker-avatar {
  transform: scale(1.15);
}

/* Accessibility */
.map-marker:focus-within .marker-avatar {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
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
  .marker-info {
    min-width: 180px;
    max-width: 200px;
    padding: 10px;
  }
  
  .alumni-name {
    font-size: 13px;
  }
  
  .alumni-title,
  .alumni-company {
    font-size: 11px;
  }
  
  .btn-sm {
    padding: 3px 6px;
    font-size: 10px;
  }
}
</style>