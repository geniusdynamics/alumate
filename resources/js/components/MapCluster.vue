<template>
  <div 
    class="map-cluster"
    :class="clusterClass"
    @click="handleClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <div class="cluster-content">
      <span class="cluster-count">{{ cluster.count }}</span>
    </div>
    
    <!-- Hover tooltip -->
    <div v-if="showTooltip" class="cluster-tooltip">
      <div class="tooltip-header">
        <strong>{{ cluster.count }} Alumni</strong>
        <span class="cluster-location">{{ formatLocation() }}</span>
      </div>
      
      <div class="tooltip-body">
        <div v-if="cluster.industries?.length" class="cluster-stat">
          <h6>Top Industries:</h6>
          <ul class="industry-list">
            <li 
              v-for="industry in cluster.industries.slice(0, 3)" 
              :key="industry"
              class="industry-item"
            >
              {{ industry }}
            </li>
          </ul>
        </div>
        
        <div v-if="cluster.year_range" class="cluster-stat">
          <h6>Graduation Years:</h6>
          <p class="year-range">
            {{ cluster.year_range.min }} - {{ cluster.year_range.max }}
          </p>
        </div>
        
        <div v-if="cluster.countries?.length" class="cluster-stat">
          <h6>Countries:</h6>
          <p class="countries">
            {{ cluster.countries.slice(0, 3).join(', ') }}
            <span v-if="cluster.countries.length > 3">
              +{{ cluster.countries.length - 3 }} more
            </span>
          </p>
        </div>
      </div>
      
      <div class="tooltip-actions">
        <button 
          class="btn btn-sm btn-primary"
          @click.stop="zoomToCluster"
        >
          <i class="fas fa-search-plus"></i>
          Zoom In
        </button>
        <button 
          class="btn btn-sm btn-outline-primary"
          @click.stop="viewClusterDetails"
        >
          <i class="fas fa-list"></i>
          View List
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface ClusterData {
  latitude: number
  longitude: number
  count: number
  industries?: string[]
  year_range?: {
    min: number
    max: number
  }
  countries?: string[]
  region?: string
  zoom_level?: number
}

interface MapClusterProps {
  cluster: ClusterData
  selected?: boolean
  size?: 'small' | 'medium' | 'large'
  interactive?: boolean
}

const props = withDefaults(defineProps<MapClusterProps>(), {
  selected: false,
  size: 'medium',
  interactive: true
})

const emit = defineEmits<{
  click: [cluster: ClusterData]
  zoom: [cluster: ClusterData]
  viewDetails: [cluster: ClusterData]
}>()

const showTooltip = ref(false)

const clusterClass = computed(() => {
  const sizeClass = getClusterSizeClass()
  return {
    'cluster-selected': props.selected,
    'cluster-interactive': props.interactive,
    [sizeClass]: true
  }
})

const getClusterSizeClass = (): string => {
  const count = props.cluster.count
  
  if (count < 5) return 'cluster-xs'
  if (count < 10) return 'cluster-sm'
  if (count < 25) return 'cluster-md'
  if (count < 50) return 'cluster-lg'
  return 'cluster-xl'
}

const formatLocation = (): string => {
  if (props.cluster.region) {
    return props.cluster.region
  }
  
  if (props.cluster.countries?.length === 1) {
    return props.cluster.countries[0]
  }
  
  if (props.cluster.countries?.length) {
    return `${props.cluster.countries.length} countries`
  }
  
  return 'Multiple locations'
}

const handleClick = () => {
  if (props.interactive) {
    emit('click', props.cluster)
  }
}

const handleMouseEnter = () => {
  if (props.interactive) {
    showTooltip.value = true
  }
}

const handleMouseLeave = () => {
  showTooltip.value = false
}

const zoomToCluster = () => {
  emit('zoom', props.cluster)
}

const viewClusterDetails = () => {
  emit('viewDetails', props.cluster)
}
</script>

<style scoped>
.map-cluster {
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cluster-content {
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  background: linear-gradient(135deg, #007bff, #0056b3);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.cluster-count {
  color: white;
  font-weight: bold;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Size variations */
.cluster-xs .cluster-content {
  width: 30px;
  height: 30px;
  border-width: 2px;
}

.cluster-xs .cluster-count {
  font-size: 11px;
}

.cluster-sm .cluster-content {
  width: 35px;
  height: 35px;
  border-width: 2px;
}

.cluster-sm .cluster-count {
  font-size: 12px;
}

.cluster-md .cluster-content {
  width: 45px;
  height: 45px;
  border-width: 3px;
}

.cluster-md .cluster-count {
  font-size: 13px;
}

.cluster-lg .cluster-content {
  width: 55px;
  height: 55px;
  border-width: 3px;
}

.cluster-lg .cluster-count {
  font-size: 14px;
}

.cluster-xl .cluster-content {
  width: 65px;
  height: 65px;
  border-width: 4px;
}

.cluster-xl .cluster-count {
  font-size: 16px;
}

/* Color variations based on size */
.cluster-xs .cluster-content {
  background: linear-gradient(135deg, #28a745, #1e7e34);
}

.cluster-sm .cluster-content {
  background: linear-gradient(135deg, #17a2b8, #117a8b);
}

.cluster-md .cluster-content {
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.cluster-lg .cluster-content {
  background: linear-gradient(135deg, #6f42c1, #5a32a3);
}

.cluster-xl .cluster-content {
  background: linear-gradient(135deg, #dc3545, #a71e2a);
}

.cluster-interactive:hover .cluster-content {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.cluster-selected .cluster-content {
  border-color: #ffc107;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.3);
}

.cluster-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 12px;
  min-width: 280px;
  z-index: 1000;
  margin-bottom: 8px;
}

.cluster-tooltip::after {
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
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e9ecef;
}

.cluster-location {
  color: #6c757d;
  font-size: 0.9em;
}

.tooltip-body {
  margin-bottom: 12px;
}

.cluster-stat {
  margin-bottom: 12px;
}

.cluster-stat:last-child {
  margin-bottom: 0;
}

.cluster-stat h6 {
  margin: 0 0 4px 0;
  font-size: 0.9em;
  color: #495057;
  font-weight: 600;
}

.industry-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.industry-item {
  padding: 2px 0;
  font-size: 0.85em;
  color: #6c757d;
  position: relative;
  padding-left: 12px;
}

.industry-item::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #007bff;
}

.year-range,
.countries {
  margin: 0;
  font-size: 0.85em;
  color: #6c757d;
}

.tooltip-actions {
  display: flex;
  gap: 8px;
  padding-top: 8px;
  border-top: 1px solid #e9ecef;
}

.tooltip-actions .btn {
  flex: 1;
  font-size: 0.8em;
  padding: 6px 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.tooltip-actions .btn i {
  font-size: 0.9em;
}

/* Pulse animation for large clusters */
.cluster-xl .cluster-content::before {
  content: '';
  position: absolute;
  top: -4px;
  left: -4px;
  right: -4px;
  bottom: -4px;
  border-radius: 50%;
  border: 2px solid rgba(220, 53, 69, 0.3);
  animation: clusterPulse 2s infinite;
}

@keyframes clusterPulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.2);
    opacity: 0;
  }
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
  .cluster-tooltip {
    min-width: 240px;
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