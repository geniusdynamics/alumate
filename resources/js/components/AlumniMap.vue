<template>
  <div class="alumni-map-container">
    <!-- Map Controls -->
    <div class="map-controls">
      <div class="control-group">
        <label for="view-mode" class="control-label">View Mode:</label>
        <select 
          id="view-mode"
          v-model="viewMode" 
          class="control-select"
          @change="handleViewModeChange"
        >
          <option value="markers">Individual Alumni</option>
          <option value="clusters">Clustered View</option>
          <option value="heatmap">Heat Map</option>
        </select>
      </div>
      
      <div class="control-group">
        <label for="privacy-filter" class="control-label">Privacy:</label>
        <select 
          id="privacy-filter"
          v-model="privacyFilter" 
          class="control-select"
          @change="applyFilters"
        >
          <option value="all">All Visible</option>
          <option value="public">Public Only</option>
          <option value="alumni_only">Alumni Network</option>
        </select>
      </div>

      <button 
        @click="toggleFilters" 
        class="filter-toggle-btn"
        :aria-expanded="showFilters"
        aria-controls="map-filters"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707L9 19.414V13.414a1 1 0 00-.293-.707L2.293 6.293A1 1 0 012 5.586V4z" />
        </svg>
        Filters
      </button>
    </div>

    <!-- Advanced Filters Panel -->
    <div 
      id="map-filters"
      v-show="showFilters" 
      class="filters-panel"
      :aria-hidden="!showFilters"
    >
      <div class="filters-grid">
        <div class="filter-group">
          <label for="graduation-year" class="filter-label">Graduation Year:</label>
          <select 
            id="graduation-year"
            v-model="filters.graduation_year" 
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Years</option>
            <option v-for="year in graduationYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="school-filter" class="filter-label">School:</label>
          <select 
            id="school-filter"
            v-model="filters.school_id" 
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Schools</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="industry-filter" class="filter-label">Industry:</label>
          <select 
            id="industry-filter"
            v-model="filters.industry" 
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Industries</option>
            <option v-for="industry in industries" :key="industry" :value="industry">
              {{ industry }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="country-filter" class="filter-label">Country:</label>
          <select 
            id="country-filter"
            v-model="filters.country" 
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Countries</option>
            <option v-for="country in countries" :key="country" :value="country">
              {{ country }}
            </option>
          </select>
        </div>
      </div>

      <div class="filter-actions">
        <button @click="clearFilters" class="btn-secondary">
          Clear Filters
        </button>
        <button @click="showFilters = false" class="btn-primary">
          Apply & Close
        </button>
      </div>
    </div>

    <!-- Map Container -->
    <div 
      ref="mapContainer" 
      class="map-container"
      role="application"
      aria-label="Interactive alumni location map"
    >
      <div v-if="loading" class="map-loading">
        <div class="loading-spinner"></div>
        <p>Loading alumni locations...</p>
      </div>
      
      <div v-if="error" class="map-error">
        <p>{{ error }}</p>
        <button @click="retryLoad" class="btn-primary">Retry</button>
      </div>
    </div>

    <!-- Map Legend -->
    <div class="map-legend">
      <h3 class="legend-title">Legend</h3>
      <div class="legend-items">
        <div class="legend-item" v-if="viewMode === 'markers'">
          <div class="legend-marker individual"></div>
          <span>Individual Alumni</span>
        </div>
        <div class="legend-item" v-if="viewMode === 'clusters'">
          <div class="legend-marker cluster-small"></div>
          <span>2-4 Alumni</span>
        </div>
        <div class="legend-item" v-if="viewMode === 'clusters'">
          <div class="legend-marker cluster-medium"></div>
          <span>5-19 Alumni</span>
        </div>
        <div class="legend-item" v-if="viewMode === 'clusters'">
          <div class="legend-marker cluster-large"></div>
          <span>20+ Alumni</span>
        </div>
      </div>
    </div>

    <!-- Alumni Info Panel -->
    <div 
      v-if="selectedAlumni" 
      class="alumni-info-panel"
      role="dialog"
      aria-labelledby="alumni-info-title"
    >
      <div class="panel-header">
        <h3 id="alumni-info-title">{{ selectedAlumni.name }}</h3>
        <button 
          @click="selectedAlumni = null" 
          class="close-btn"
          aria-label="Close alumni information panel"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="panel-content">
        <div class="alumni-avatar">
          <img 
            :src="selectedAlumni.avatar_url || '/images/default-avatar.png'" 
            :alt="selectedAlumni.name"
            class="avatar-image"
          />
        </div>
        
        <div class="alumni-details">
          <p v-if="selectedAlumni.current_title" class="alumni-title">
            {{ selectedAlumni.current_title }}
          </p>
          <p v-if="selectedAlumni.current_company" class="alumni-company">
            {{ selectedAlumni.current_company }}
          </p>
          <p v-if="selectedAlumni.location" class="alumni-location">
            📍 {{ selectedAlumni.location }}
          </p>
        </div>
        
        <div class="panel-actions">
          <button @click="viewProfile(selectedAlumni)" class="btn-primary">
            View Profile
          </button>
          <button @click="sendMessage(selectedAlumni)" class="btn-secondary">
            Send Message
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Panel -->
    <div class="stats-panel">
      <h3 class="stats-title">Alumni Distribution</h3>
      <div class="stats-grid">
        <div class="stat-item">
          <span class="stat-number">{{ totalAlumni }}</span>
          <span class="stat-label">Total Alumni</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ visibleAlumni }}</span>
          <span class="stat-label">Visible</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ countriesCount }}</span>
          <span class="stat-label">Countries</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix for default markers in Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

interface Alumni {
  id: number
  name: string
  avatar_url?: string
  current_title?: string
  current_company?: string
  location?: string
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
  initialAlumni?: Alumni[]
  schools?: Array<{ id: number; name: string }>
  industries?: string[]
  countries?: string[]
  graduationYears?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  initialAlumni: () => [],
  schools: () => [],
  industries: () => [],
  countries: () => [],
  graduationYears: () => []
})

// Reactive state
const mapContainer = ref<HTMLElement>()
const map = ref<L.Map>()
const loading = ref(false)
const error = ref('')
const viewMode = ref<'markers' | 'clusters' | 'heatmap'>('markers')
const privacyFilter = ref('all')
const showFilters = ref(false)
const selectedAlumni = ref<Alumni | null>(null)

const filters = ref({
  graduation_year: '',
  school_id: '',
  industry: '',
  country: ''
})

const alumni = ref<Alumni[]>(props.initialAlumni)
const clusters = ref<Cluster[]>([])
const markersLayer = ref<L.LayerGroup>()
const clustersLayer = ref<L.LayerGroup>()

// Computed properties
const totalAlumni = computed(() => alumni.value.length)
const visibleAlumni = computed(() => {
  // Apply current filters to count visible alumni
  return alumni.value.filter(alumnus => {
    if (privacyFilter.value === 'public' && alumnus.location_privacy !== 'public') return false
    if (privacyFilter.value === 'alumni_only' && alumnus.location_privacy === 'private') return false
    return true
  }).length
})
const countriesCount = computed(() => {
  const uniqueCountries = new Set(alumni.value.map(a => a.country).filter(Boolean))
  return uniqueCountries.size
})

// Map initialization
onMounted(async () => {
  await initializeMap()
  await loadAlumniData()
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})

const initializeMap = async () => {
  if (!mapContainer.value) return

  try {
    map.value = L.map(mapContainer.value, {
      center: [39.8283, -98.5795], // Center of US
      zoom: 4,
      zoomControl: true,
      attributionControl: true
    })

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 18
    }).addTo(map.value)

    // Initialize layers
    markersLayer.value = L.layerGroup().addTo(map.value)
    clustersLayer.value = L.layerGroup()

    // Add event listeners
    map.value.on('zoomend', handleZoomChange)
    map.value.on('moveend', handleMapMove)

  } catch (err) {
    error.value = 'Failed to initialize map'
    console.error('Map initialization error:', err)
  }
}

const loadAlumniData = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/alumni/map-data', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        filters: filters.value,
        privacy_filter: privacyFilter.value
      })
    })

    if (!response.ok) {
      throw new Error('Failed to load alumni data')
    }

    const data = await response.json()
    alumni.value = data.alumni || []
    
    updateMapDisplay()

  } catch (err) {
    error.value = 'Failed to load alumni data'
    console.error('Data loading error:', err)
  } finally {
    loading.value = false
  }
}

const updateMapDisplay = () => {
  if (!map.value) return

  // Clear existing layers
  markersLayer.value?.clearLayers()
  clustersLayer.value?.clearLayers()

  if (viewMode.value === 'markers') {
    displayIndividualMarkers()
  } else if (viewMode.value === 'clusters') {
    displayClusters()
  }
}

const displayIndividualMarkers = () => {
  if (!markersLayer.value) return

  alumni.value.forEach(alumnus => {
    const marker = L.marker([alumnus.latitude, alumnus.longitude])
      .bindPopup(createPopupContent(alumnus))
      .on('click', () => {
        selectedAlumni.value = alumnus
      })

    markersLayer.value?.addLayer(marker)
  })
}

const displayClusters = async () => {
  if (!map.value || !clustersLayer.value) return

  const bounds = map.value.getBounds()
  const zoom = map.value.getZoom()

  try {
    const response = await fetch('/api/alumni/map-clusters', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        bounds: {
          north: bounds.getNorth(),
          south: bounds.getSouth(),
          east: bounds.getEast(),
          west: bounds.getWest()
        },
        zoom_level: zoom,
        filters: filters.value,
        privacy_filter: privacyFilter.value
      })
    })

    const data = await response.json()
    clusters.value = data.clusters || []

    clusters.value.forEach(cluster => {
      const marker = L.marker([cluster.cluster_lat, cluster.cluster_lng], {
        icon: createClusterIcon(cluster.count)
      })
        .bindPopup(createClusterPopupContent(cluster))
        .on('click', () => {
          if (cluster.count === 1) {
            selectedAlumni.value = cluster.alumni[0]
          } else {
            // Zoom to cluster
            map.value?.setView([cluster.cluster_lat, cluster.cluster_lng], Math.min(map.value.getZoom() + 2, 18))
          }
        })

      clustersLayer.value?.addLayer(marker)
    })

    // Switch to clusters layer
    if (map.value.hasLayer(markersLayer.value!)) {
      map.value.removeLayer(markersLayer.value!)
    }
    if (!map.value.hasLayer(clustersLayer.value)) {
      map.value.addLayer(clustersLayer.value)
    }

  } catch (err) {
    console.error('Cluster loading error:', err)
  }
}

const createPopupContent = (alumnus: Alumni): string => {
  return `
    <div class="map-popup">
      <div class="popup-header">
        <img src="${alumnus.avatar_url || '/images/default-avatar.png'}" alt="${alumnus.name}" class="popup-avatar">
        <h4>${alumnus.name}</h4>
      </div>
      <div class="popup-content">
        ${alumnus.current_title ? `<p><strong>${alumnus.current_title}</strong></p>` : ''}
        ${alumnus.current_company ? `<p>at ${alumnus.current_company}</p>` : ''}
        ${alumnus.location ? `<p>📍 ${alumnus.location}</p>` : ''}
      </div>
      <div class="popup-actions">
        <button onclick="viewAlumniProfile(${alumnus.id})" class="btn-primary btn-sm">View Profile</button>
      </div>
    </div>
  `
}

const createClusterPopupContent = (cluster: Cluster): string => {
  const previewAlumni = cluster.alumni.slice(0, 3)
  const remainingCount = cluster.count - 3

  return `
    <div class="cluster-popup">
      <h4>${cluster.count} Alumni in this area</h4>
      <div class="cluster-preview">
        ${previewAlumni.map(alumnus => `
          <div class="preview-item">
            <img src="${alumnus.avatar_url || '/images/default-avatar.png'}" alt="${alumnus.name}" class="preview-avatar">
            <span>${alumnus.name}</span>
          </div>
        `).join('')}
        ${remainingCount > 0 ? `<p>and ${remainingCount} more...</p>` : ''}
      </div>
      <button onclick="zoomToCluster(${cluster.cluster_lat}, ${cluster.cluster_lng})" class="btn-primary btn-sm">
        View Details
      </button>
    </div>
  `
}

const createClusterIcon = (count: number): L.Icon => {
  const size = count < 5 ? 30 : count < 20 ? 40 : count < 50 ? 50 : 60
  const color = count < 5 ? '#3b82f6' : count < 20 ? '#059669' : count < 50 ? '#d97706' : '#dc2626'

  return L.divIcon({
    html: `<div style="
      width: ${size}px;
      height: ${size}px;
      background: ${color};
      border: 3px solid white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: ${Math.max(10, size * 0.3)}px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    ">${count}</div>`,
    className: 'cluster-marker',
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2]
  })
}

// Event handlers
const handleViewModeChange = () => {
  updateMapDisplay()
}

const handleZoomChange = () => {
  if (viewMode.value === 'clusters') {
    displayClusters()
  }
}

const handleMapMove = () => {
  if (viewMode.value === 'clusters') {
    displayClusters()
  }
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const applyFilters = () => {
  loadAlumniData()
}

const clearFilters = () => {
  filters.value = {
    graduation_year: '',
    school_id: '',
    industry: '',
    country: ''
  }
  privacyFilter.value = 'all'
  applyFilters()
}

const retryLoad = () => {
  loadAlumniData()
}

const viewProfile = (alumnus: Alumni) => {
  router.visit(`/alumni/${alumnus.id}`)
}

const sendMessage = (alumnus: Alumni) => {
  router.visit(`/messages/new?recipient=${alumnus.id}`)
}

// Global functions for popup buttons
;(window as any).viewAlumniProfile = (id: number) => {
  router.visit(`/alumni/${id}`)
}

;(window as any).zoomToCluster = (lat: number, lng: number) => {
  map.value?.setView([lat, lng], Math.min(map.value.getZoom() + 2, 18))
}

// Watch for filter changes
watch([filters, privacyFilter], () => {
  applyFilters()
}, { deep: true })
</script>

<style scoped>
.alumni-map-container {
  position: relative;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.map-controls {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  flex-wrap: wrap;
}

.control-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.control-label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.control-select {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.filter-toggle-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-toggle-btn:hover {
  background: #e5e7eb;
}

.filters-panel {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  padding: 16px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.filter-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.map-container {
  flex: 1;
  position: relative;
}

.map-loading,
.map-error {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 1000;
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f3f4f6;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.map-legend {
  position: absolute;
  top: 80px;
  right: 16px;
  background: white;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 150px;
}

.legend-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: #1f2937;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #6b7280;
}

.legend-marker {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.legend-marker.individual {
  background: #3b82f6;
}

.legend-marker.cluster-small {
  background: #3b82f6;
}

.legend-marker.cluster-medium {
  background: #059669;
}

.legend-marker.cluster-large {
  background: #dc2626;
}

.alumni-info-panel {
  position: absolute;
  top: 80px;
  left: 16px;
  width: 300px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.close-btn {
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.panel-content {
  padding: 16px;
}

.alumni-avatar {
  text-align: center;
  margin-bottom: 12px;
}

.avatar-image {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #e5e7eb;
}

.alumni-details {
  text-align: center;
  margin-bottom: 16px;
}

.alumni-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.alumni-company {
  font-size: 13px;
  color: #6b7280;
  margin: 0 0 4px 0;
}

.alumni-location {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

.panel-actions {
  display: flex;
  gap: 8px;
}

.stats-panel {
  position: absolute;
  bottom: 16px;
  right: 16px;
  background: white;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.stats-title {
  font-size: 12px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 8px 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stats-grid {
  display: flex;
  gap: 16px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-number {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.stat-label {
  font-size: 10px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Button styles */
.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

/* Responsive design */
@media (max-width: 768px) {
  .map-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .control-group {
    justify-content: space-between;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .alumni-info-panel {
    left: 8px;
    right: 8px;
    width: auto;
  }
  
  .stats-panel {
    left: 8px;
    right: 8px;
    bottom: 8px;
  }
  
  .stats-grid {
    justify-content: space-around;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    animation: none;
  }
  
  * {
    transition: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .map-controls,
  .filters-panel,
  .map-legend,
  .alumni-info-panel,
  .stats-panel {
    border: 2px solid #000;
  }
}
</style>

<style>
/* Global styles for Leaflet popups */
.map-popup {
  min-width: 200px;
}

.popup-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.popup-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.popup-header h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.popup-content p {
  margin: 4px 0;
  font-size: 12px;
  color: #6b7280;
}

.popup-actions {
  margin-top: 8px;
}

.cluster-popup h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.cluster-preview {
  margin-bottom: 8px;
}

.preview-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.preview-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}

.preview-item span {
  font-size: 12px;
}
</style>