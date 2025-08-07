<template>
  <div class="alumni-map-container">
    <!-- Map Controls -->
    <div class="map-controls">
      <LocationFilter 
        v-model:filters="mapFilters"
        @update:filters="handleFilterChange"
      />
      
      <div class="map-view-controls">
        <button 
          @click="toggleHeatmap"
          :class="['btn', 'btn-sm', { 'btn-primary': showHeatmap, 'btn-outline-primary': !showHeatmap }]"
        >
          {{ showHeatmap ? 'Hide' : 'Show' }} Heatmap
        </button>
        
        <button 
          @click="findMyLocation"
          class="btn btn-sm btn-outline-secondary"
          :disabled="loadingLocation"
        >
          <i class="fas fa-location-arrow"></i>
          {{ loadingLocation ? 'Locating...' : 'My Location' }}
        </button>
      </div>
    </div>

    <!-- Map Container -->
    <div class="map-wrapper">
      <div ref="mapContainer" class="leaflet-map"></div>
      
      <!-- Loading Overlay -->
      <div v-if="loading" class="map-loading-overlay">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading map data...</span>
        </div>
      </div>
    </div>

    <!-- Regional Insights Panel -->
    <RegionalInsights 
      v-if="selectedRegion"
      :region="selectedRegion"
      :stats="regionalStats"
      @close="closeRegionalInsights"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import LocationFilter from './LocationFilter.vue'
import RegionalInsights from './RegionalInsights.vue'
import MapMarker from './MapMarker.vue'
import MapCluster from './MapCluster.vue'
import { useAlumniMapStore } from '@/stores/alumniMapStore'

// Fix for default markers in Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

interface AlumniMapProps {
  initialCenter?: [number, number]
  initialZoom?: number
  height?: string
}

const props = withDefaults(defineProps<AlumniMapProps>(), {
  initialCenter: () => [39.8283, -98.5795], // Center of USA
  initialZoom: 4,
  height: '600px'
})

// Reactive data
const mapContainer = ref<HTMLDivElement>()
const map = ref<L.Map>()
const loading = ref(false)
const loadingLocation = ref(false)
const showHeatmap = ref(false)
const mapFilters = ref({
  graduation_year: [],
  industry: [],
  country: [],
  state: []
})

const selectedRegion = ref<string | null>(null)
const regionalStats = ref<any>(null)

// Map layers
const alumniMarkers = ref<L.LayerGroup>(new L.LayerGroup())
const clusterMarkers = ref<L.LayerGroup>(new L.LayerGroup())
const heatmapLayer = ref<L.LayerGroup | null>(null)

// Store
const alumniMapStore = useAlumniMapStore()

// Initialize map
onMounted(async () => {
  await nextTick()
  initializeMap()
  loadMapData()
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})

const initializeMap = () => {
  if (!mapContainer.value) return

  map.value = L.map(mapContainer.value, {
    center: props.initialCenter,
    zoom: props.initialZoom,
    zoomControl: true,
    attributionControl: true
  })

  // Add tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
  }).addTo(map.value)

  // Add layer groups
  alumniMarkers.value.addTo(map.value)
  clusterMarkers.value.addTo(map.value)

  // Map event listeners
  map.value.on('moveend', handleMapMove)
  map.value.on('zoomend', handleZoomChange)
  map.value.on('click', handleMapClick)
}

const loadMapData = async () => {
  if (!map.value) return

  loading.value = true
  
  try {
    const bounds = map.value.getBounds()
    const zoom = map.value.getZoom()
    
    const boundsData = {
      north: bounds.getNorth(),
      south: bounds.getSouth(),
      east: bounds.getEast(),
      west: bounds.getWest()
    }

    if (zoom <= 8) {
      // Show clusters for lower zoom levels
      await loadClusters(zoom, boundsData)
    } else {
      // Show individual markers for higher zoom levels
      await loadIndividualAlumni(boundsData)
    }
  } catch (error) {
    console.error('Error loading map data:', error)
  } finally {
    loading.value = false
  }
}

const loadClusters = async (zoomLevel: number, bounds: any) => {
  try {
    const response = await fetch('/api/alumni/map/clusters', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        zoom_level: zoomLevel,
        bounds,
        filters: mapFilters.value
      })
    })

    const clusters = await response.json()
    
    // Clear existing markers
    alumniMarkers.value.clearLayers()
    clusterMarkers.value.clearLayers()

    // Add cluster markers
    clusters.forEach((cluster: any) => {
      const marker = L.marker([cluster.latitude, cluster.longitude], {
        icon: createClusterIcon(cluster.count)
      })

      marker.bindPopup(createClusterPopup(cluster))
      marker.on('click', () => handleClusterClick(cluster))
      
      clusterMarkers.value.addLayer(marker)
    })
  } catch (error) {
    console.error('Error loading clusters:', error)
  }
}

const loadIndividualAlumni = async (bounds: any) => {
  try {
    const response = await fetch('/api/alumni/map', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        bounds,
        filters: mapFilters.value
      })
    })

    const alumni = await response.json()
    
    // Clear existing markers
    alumniMarkers.value.clearLayers()
    clusterMarkers.value.clearLayers()

    // Add individual alumni markers
    alumni.forEach((alumnus: any) => {
      const marker = L.marker([alumnus.latitude, alumnus.longitude], {
        icon: createAlumniIcon(alumnus)
      })

      marker.bindPopup(createAlumniPopup(alumnus))
      marker.on('click', () => handleAlumniClick(alumnus))
      
      alumniMarkers.value.addLayer(marker)
    })
  } catch (error) {
    console.error('Error loading alumni:', error)
  }
}

const createClusterIcon = (count: number) => {
  const size = Math.min(Math.max(count * 2 + 20, 30), 60)
  
  return L.divIcon({
    html: `<div class="cluster-marker" style="width: ${size}px; height: ${size}px;">
             <span class="cluster-count">${count}</span>
           </div>`,
    className: 'custom-cluster-icon',
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2]
  })
}

const createAlumniIcon = (alumnus: any) => {
  const photoUrl = alumnus.profile_photo_path || '/images/default-avatar.png'
  
  return L.divIcon({
    html: `<div class="alumni-marker">
             <img src="${photoUrl}" alt="${alumnus.first_name}" class="alumni-photo" />
           </div>`,
    className: 'custom-alumni-icon',
    iconSize: [40, 40],
    iconAnchor: [20, 20]
  })
}

const createClusterPopup = (cluster: any) => {
  return `
    <div class="cluster-popup">
      <h6>${cluster.count} Alumni in this area</h6>
      <p><strong>Industries:</strong> ${cluster.industries.slice(0, 3).join(', ')}</p>
      <p><strong>Graduation Years:</strong> ${cluster.year_range.min} - ${cluster.year_range.max}</p>
      <button class="btn btn-sm btn-primary" onclick="zoomToCluster(${cluster.latitude}, ${cluster.longitude})">
        View Details
      </button>
    </div>
  `
}

const createAlumniPopup = (alumnus: any) => {
  return `
    <div class="alumni-popup">
      <div class="alumni-header">
        <img src="${alumnus.profile_photo_path || '/images/default-avatar.png'}" 
             alt="${alumnus.first_name}" class="alumni-popup-photo" />
        <div>
          <h6>${alumnus.first_name} ${alumnus.last_name}</h6>
          <p class="text-muted">${alumnus.graduation_year}</p>
        </div>
      </div>
      <div class="alumni-details">
        <p><strong>${alumnus.current_position}</strong></p>
        <p>${alumnus.current_company}</p>
        <p class="text-muted">${alumnus.city}, ${alumnus.state}</p>
      </div>
      <button class="btn btn-sm btn-primary" onclick="viewProfile(${alumnus.id})">
        View Profile
      </button>
    </div>
  `
}

const handleMapMove = () => {
  // Debounce map data loading
  clearTimeout(window.mapMoveTimeout)
  window.mapMoveTimeout = setTimeout(loadMapData, 300)
}

const handleZoomChange = () => {
  loadMapData()
}

const handleMapClick = (e: L.LeafletMouseEvent) => {
  // Handle map click events
  console.log('Map clicked at:', e.latlng)
}

const handleClusterClick = (cluster: any) => {
  if (map.value) {
    map.value.setView([cluster.latitude, cluster.longitude], map.value.getZoom() + 2)
  }
}

const handleAlumniClick = (alumnus: any) => {
  // Handle individual alumni marker click
  console.log('Alumni clicked:', alumnus)
}

const handleFilterChange = (filters: any) => {
  mapFilters.value = filters
  loadMapData()
}

const toggleHeatmap = () => {
  showHeatmap.value = !showHeatmap.value
  
  if (showHeatmap.value) {
    loadHeatmapData()
  } else if (heatmapLayer.value) {
    map.value?.removeLayer(heatmapLayer.value)
    heatmapLayer.value = null
  }
}

const loadHeatmapData = async () => {
  // Implementation for heatmap would require additional library like leaflet.heat
  console.log('Heatmap toggle - would load heatmap data')
}

const findMyLocation = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.')
    return
  }

  loadingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords
      
      if (map.value) {
        map.value.setView([latitude, longitude], 12)
        
        // Add user location marker
        L.marker([latitude, longitude], {
          icon: L.divIcon({
            html: '<div class="user-location-marker"><i class="fas fa-user"></i></div>',
            className: 'custom-user-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
          })
        }).addTo(map.value).bindPopup('Your Location')
      }
      
      loadingLocation.value = false
    },
    (error) => {
      console.error('Error getting location:', error)
      loadingLocation.value = false
      alert('Unable to retrieve your location.')
    }
  )
}

const closeRegionalInsights = () => {
  selectedRegion.value = null
  regionalStats.value = null
}

// Global functions for popup buttons
;(window as any).zoomToCluster = (lat: number, lng: number) => {
  if (map.value) {
    map.value.setView([lat, lng], map.value.getZoom() + 2)
  }
}

;(window as any).viewProfile = (id: number) => {
  // Navigate to profile page
  window.location.href = `/alumni/${id}`
}

// Watch for filter changes
watch(mapFilters, () => {
  loadMapData()
}, { deep: true })
</script>

<style scoped>
.alumni-map-container {
  position: relative;
  width: 100%;
  height: v-bind(height);
}

.map-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-bottom: 1px solid #dee2e6;
  gap: 1rem;
}

.map-view-controls {
  display: flex;
  gap: 0.5rem;
}

.map-wrapper {
  position: relative;
  height: calc(100% - 80px);
}

.leaflet-map {
  width: 100%;
  height: 100%;
}

.map-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Custom marker styles */
:deep(.cluster-marker) {
  background: #007bff;
  border: 3px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

:deep(.cluster-count) {
  color: white;
  font-weight: bold;
  font-size: 12px;
}

:deep(.alumni-marker) {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 3px solid white;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

:deep(.alumni-photo) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

:deep(.user-location-marker) {
  width: 30px;
  height: 30px;
  background: #28a745;
  border: 3px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Popup styles */
:deep(.cluster-popup) {
  min-width: 200px;
}

:deep(.alumni-popup) {
  min-width: 250px;
}

:deep(.alumni-header) {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

:deep(.alumni-popup-photo) {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

:deep(.alumni-details) {
  margin-bottom: 10px;
}

:deep(.alumni-details p) {
  margin: 2px 0;
}
</style>