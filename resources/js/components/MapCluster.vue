<template>
  <div class="map-cluster" :class="clusterClass" @click="handleClick">
    <div class="cluster-content">
      <div class="cluster-count">{{ cluster.count }}</div>
      <div class="cluster-preview" v-if="showPreview">
        <div class="preview-avatars">
          <img 
            v-for="(alumni, index) in previewAlumni" 
            :key="alumni.id"
            :src="alumni.avatar_url || '/images/default-avatar.png'"
            :alt="alumni.name"
            class="preview-avatar"
            :style="{ zIndex: previewAlumni.length - index }"
          />
        </div>
        <div class="preview-info">
          <h4 class="cluster-title">{{ cluster.count }} Alumni</h4>
          <p class="cluster-location">{{ locationName }}</p>
          <div class="preview-names">
            <span v-for="(alumni, index) in previewAlumni" :key="alumni.id">
              {{ alumni.name }}<span v-if="index < previewAlumni.length - 1">, </span>
            </span>
            <span v-if="cluster.count > previewLimit">
              and {{ cluster.count - previewLimit }} more...
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Alumni {
  id: number
  name: string
  avatar_url?: string
  current_title?: string
  current_company?: string
  latitude: number
  longitude: number
}

interface Cluster {
  cluster_lat: number
  cluster_lng: number
  count: number
  alumni: Alumni[]
}

interface Props {
  cluster: Cluster
  showPreview?: boolean
  previewLimit?: number
  locationName?: string
}

const props = withDefaults(defineProps<Props>(), {
  showPreview: false,
  previewLimit: 3,
  locationName: 'This Area'
})

const emit = defineEmits<{
  click: [cluster: Cluster]
  zoomTo: [cluster: Cluster]
}>()

const clusterClass = computed(() => ({
  'cluster-small': props.cluster.count < 5,
  'cluster-medium': props.cluster.count >= 5 && props.cluster.count < 20,
  'cluster-large': props.cluster.count >= 20 && props.cluster.count < 50,
  'cluster-xlarge': props.cluster.count >= 50,
  'cluster-with-preview': props.showPreview
}))

const previewAlumni = computed(() => 
  props.cluster.alumni.slice(0, props.previewLimit)
)

const handleClick = () => {
  if (props.showPreview) {
    emit('click', props.cluster)
  } else {
    emit('zoomTo', props.cluster)
  }
}
</script>

<style scoped>
.map-cluster {
  position: relative;
  cursor: pointer;
  z-index: 1000;
}

.cluster-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
  border: 3px solid white;
}

/* Cluster sizes */
.cluster-small .cluster-content {
  width: 40px;
  height: 40px;
  font-size: 12px;
}

.cluster-medium .cluster-content {
  width: 50px;
  height: 50px;
  font-size: 14px;
}

.cluster-large .cluster-content {
  width: 60px;
  height: 60px;
  font-size: 16px;
}

.cluster-xlarge .cluster-content {
  width: 70px;
  height: 70px;
  font-size: 18px;
  background: linear-gradient(135deg, #dc2626, #991b1b);
}

.cluster-count {
  position: relative;
  z-index: 2;
}

/* Hover effects */
.map-cluster:hover .cluster-content {
  transform: scale(1.1);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}

/* Preview popup */
.cluster-preview {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  min-width: 280px;
  max-width: 320px;
  margin-top: 12px;
  z-index: 1001;
  border: 1px solid #e5e7eb;
}

.cluster-preview::before {
  content: '';
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-bottom: 8px solid white;
}

.preview-avatars {
  display: flex;
  margin-bottom: 12px;
  position: relative;
  height: 32px;
}

.preview-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid white;
  object-fit: cover;
  margin-left: -8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.preview-avatar:first-child {
  margin-left: 0;
}

.cluster-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.cluster-location {
  font-size: 12px;
  color: #6b7280;
  margin: 0 0 8px 0;
}

.preview-names {
  font-size: 12px;
  color: #374151;
  line-height: 1.4;
}

/* Pulse animation for active clusters */
@keyframes clusterPulse {
  0% {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
  50% {
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4), 0 0 0 8px rgba(59, 130, 246, 0.1);
  }
  100% {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
}

.cluster-with-preview .cluster-content {
  animation: clusterPulse 2s infinite;
}

/* Accessibility */
.map-cluster:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-radius: 50%;
}

/* Animation for cluster appearance */
@keyframes clusterAppear {
  from {
    opacity: 0;
    transform: scale(0.3);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.map-cluster {
  animation: clusterAppear 0.4s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .cluster-preview {
    min-width: 240px;
    max-width: 280px;
    padding: 12px;
  }
  
  .preview-avatar {
    width: 28px;
    height: 28px;
  }
  
  .preview-avatars {
    height: 28px;
    margin-bottom: 10px;
  }
  
  .cluster-title {
    font-size: 14px;
  }
  
  .preview-names {
    font-size: 11px;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .cluster-content {
    border-width: 4px;
    border-color: #000;
  }
  
  .cluster-preview {
    border: 2px solid #000;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .cluster-content,
  .map-cluster:hover .cluster-content {
    transition: none;
    transform: none;
  }
  
  .map-cluster {
    animation: none;
  }
  
  .cluster-with-preview .cluster-content {
    animation: none;
  }
}
</style>