<template>
  <Head title="Alumni Map - Geographic Distribution" />
  
  <DefaultLayout>
    <div class="alumni-map-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">Alumni Map</h1>
            <p class="page-description">
              Discover where our alumni are located around the world and connect with fellow graduates in your area.
            </p>
          </div>
          
          <div class="header-actions">
            <button 
              @click="showPrivacySettings = true"
              class="btn-secondary"
              aria-label="Manage location privacy settings"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Privacy Settings
            </button>
            
            <button 
              @click="showLocationUpdate = true"
              class="btn-primary"
              aria-label="Update your location"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Update Location
            </button>
          </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
          <div class="stat-card">
            <div class="stat-icon">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.total_alumni }}</div>
              <div class="stat-label">Total Alumni</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.by_country?.length || 0 }}</div>
              <div class="stat-label">Countries</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.by_industry?.length || 0 }}</div>
              <div class="stat-label">Industries</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ nearbyCount }}</div>
              <div class="stat-label">Nearby Alumni</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Map Component -->
      <div class="map-section">
        <AlumniMap
          :initial-alumni="alumni"
          :schools="schools"
          :industries="industries"
          :countries="countries"
          :graduation-years="graduationYears"
          @alumni-selected="handleAlumniSelected"
          @message-alumni="handleMessageAlumni"
        />
      </div>

      <!-- Regional Insights Panel -->
      <div class="insights-panel" v-if="showInsights">
        <div class="panel-header">
          <h2 class="panel-title">Regional Insights</h2>
          <button 
            @click="showInsights = false"
            class="close-btn"
            aria-label="Close insights panel"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="insights-content">
          <!-- Top Countries -->
          <div class="insight-section">
            <h3 class="insight-title">Top Countries</h3>
            <div class="insight-list">
              <div 
                v-for="country in stats.by_country?.slice(0, 5)" 
                :key="country.country"
                class="insight-item"
              >
                <span class="insight-name">{{ country.country }}</span>
                <span class="insight-count">{{ country.count }}</span>
              </div>
            </div>
          </div>
          
          <!-- Top Industries -->
          <div class="insight-section">
            <h3 class="insight-title">Top Industries</h3>
            <div class="insight-list">
              <div 
                v-for="industry in stats.by_industry?.slice(0, 5)" 
                :key="industry.industry"
                class="insight-item"
              >
                <span class="insight-name">{{ industry.industry }}</span>
                <span class="insight-count">{{ industry.count }}</span>
              </div>
            </div>
          </div>
          
          <!-- Regional Distribution -->
          <div class="insight-section">
            <h3 class="insight-title">Regional Distribution</h3>
            <div class="insight-list">
              <div 
                v-for="region in stats.by_region?.slice(0, 5)" 
                :key="region.region"
                class="insight-item"
              >
                <span class="insight-name">{{ region.region }}</span>
                <span class="insight-count">{{ region.count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Privacy Settings Modal -->
      <div v-if="showPrivacySettings" class="modal-overlay" @click="showPrivacySettings = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">Location Privacy Settings</h2>
            <button 
              @click="showPrivacySettings = false"
              class="close-btn"
              aria-label="Close privacy settings"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <p class="privacy-description">
              Control who can see your location on the alumni map. Your privacy is important to us.
            </p>
            
            <div class="privacy-options">
              <label class="privacy-option">
                <input 
                  type="radio" 
                  v-model="privacySettings.location_privacy" 
                  value="public"
                  name="location_privacy"
                />
                <div class="option-content">
                  <div class="option-title">Public</div>
                  <div class="option-description">
                    Your location is visible to everyone, including prospective students and employers.
                  </div>
                </div>
              </label>
              
              <label class="privacy-option">
                <input 
                  type="radio" 
                  v-model="privacySettings.location_privacy" 
                  value="alumni_only"
                  name="location_privacy"
                />
                <div class="option-content">
                  <div class="option-title">Alumni Only</div>
                  <div class="option-description">
                    Only verified alumni from your institution can see your location.
                  </div>
                </div>
              </label>
              
              <label class="privacy-option">
                <input 
                  type="radio" 
                  v-model="privacySettings.location_privacy" 
                  value="private"
                  name="location_privacy"
                />
                <div class="option-content">
                  <div class="option-title">Private</div>
                  <div class="option-description">
                    Your location is not shown on the map to anyone.
                  </div>
                </div>
              </label>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="showPrivacySettings = false" class="btn-secondary">
              Cancel
            </button>
            <button @click="savePrivacySettings" class="btn-primary">
              Save Settings
            </button>
          </div>
        </div>
      </div>

      <!-- Location Update Modal -->
      <div v-if="showLocationUpdate" class="modal-overlay" @click="showLocationUpdate = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h2 class="modal-title">Update Your Location</h2>
            <button 
              @click="showLocationUpdate = false"
              class="close-btn"
              aria-label="Close location update"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="updateLocation">
              <div class="form-group">
                <label for="location-input" class="form-label">
                  Current Location
                </label>
                <input 
                  id="location-input"
                  type="text" 
                  v-model="locationForm.address"
                  class="form-input"
                  placeholder="Enter your city, state/province, country"
                  required
                />
                <p class="form-help">
                  Enter your current location to help fellow alumni find you.
                </p>
              </div>
              
              <div class="form-actions">
                <button 
                  type="button" 
                  @click="getCurrentLocation"
                  class="btn-secondary"
                  :disabled="gettingLocation"
                >
                  <svg v-if="!gettingLocation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <div v-else class="loading-spinner-sm"></div>
                  {{ gettingLocation ? 'Getting Location...' : 'Use Current Location' }}
                </button>
              </div>
            </form>
          </div>
          
          <div class="modal-footer">
            <button @click="showLocationUpdate = false" class="btn-secondary">
              Cancel
            </button>
            <button @click="updateLocation" class="btn-primary" :disabled="!locationForm.address">
              Update Location
            </button>
          </div>
        </div>
      </div>

      <!-- Insights Toggle Button -->
      <button 
        @click="showInsights = !showInsights"
        class="insights-toggle"
        :class="{ active: showInsights }"
        aria-label="Toggle regional insights panel"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        Insights
      </button>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AlumniMap from '@/Components/AlumniMap.vue'
import LoadingOptimizer from '@/Components/Performance/LoadingOptimizer.vue'
import { usePerformanceMonitoring } from '@/Composables/usePerformanceMonitoring'

interface Alumni {
  id: number
  name: string
  avatar_url?: string
  current_title?: string
  current_company?: string
  location?: string
  latitude: number
  longitude: number
  location_privacy: string
}

interface Stats {
  total_alumni: number
  by_country: Array<{ country: string; count: number }>
  by_region: Array<{ region: string; count: number }>
  by_industry: Array<{ industry: string; count: number }>
}

interface Props {
  alumni: Alumni[]
  stats: Stats
  schools: Array<{ id: number; name: string }>
  industries: string[]
  countries: string[]
  graduationYears: number[]
  userLocation?: {
    latitude: number
    longitude: number
    privacy: string
  }
}

const props = defineProps<Props>()

// Performance monitoring
const { 
  isLoading, 
  trackInteraction, 
  trackApiCall, 
  startLoading, 
  endLoading 
} = usePerformanceMonitoring('AlumniMap')

// Reactive state
const showInsights = ref(false)
const showPrivacySettings = ref(false)
const showLocationUpdate = ref(false)
const gettingLocation = ref(false)

const privacySettings = ref({
  location_privacy: props.userLocation?.privacy || 'alumni_only'
})

const locationForm = ref({
  address: ''
})

// Computed properties
const nearbyCount = computed(() => {
  if (!props.userLocation) return 0
  
  // Calculate nearby alumni within 50km
  return props.alumni.filter(alumnus => {
    if (!props.userLocation) return false
    
    const distance = calculateDistance(
      props.userLocation.latitude,
      props.userLocation.longitude,
      alumnus.latitude,
      alumnus.longitude
    )
    
    return distance <= 50 // 50km radius
  }).length
})

// Methods
const handleAlumniSelected = (alumni: Alumni) => {
  // Handle alumni selection from map
  console.log('Alumni selected:', alumni)
}

const handleMessageAlumni = (alumni: Alumni) => {
  router.visit(`/messages/new?recipient=${alumni.id}`)
}

const savePrivacySettings = async () => {
  try {
    await router.post('/api/user/location-privacy', {
      location_privacy: privacySettings.value.location_privacy
    })
    
    showPrivacySettings.value = false
    
    // Refresh the page to update the map
    router.reload()
    
  } catch (error) {
    console.error('Failed to save privacy settings:', error)
  }
}

const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.')
    return
  }

  gettingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        // Reverse geocode the coordinates to get address
        const response = await fetch(`/api/geocode/reverse?lat=${position.coords.latitude}&lng=${position.coords.longitude}`)
        const data = await response.json()
        
        if (data.address) {
          locationForm.value.address = data.address
        }
      } catch (error) {
        console.error('Failed to reverse geocode:', error)
        locationForm.value.address = `${position.coords.latitude}, ${position.coords.longitude}`
      } finally {
        gettingLocation.value = false
      }
    },
    (error) => {
      console.error('Geolocation error:', error)
      gettingLocation.value = false
      alert('Unable to get your current location. Please enter it manually.')
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000 // 5 minutes
    }
  )
}

const updateLocation = async () => {
  if (!locationForm.value.address) return

  try {
    await router.post('/api/user/location', {
      address: locationForm.value.address
    })
    
    showLocationUpdate.value = false
    
    // Refresh the page to update the map
    router.reload()
    
  } catch (error) {
    console.error('Failed to update location:', error)
  }
}

const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
  const R = 6371 // Radius of the Earth in kilometers
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
  return R * c
}

onMounted(() => {
  // Any initialization logic
})
</script>

<style scoped>
.alumni-map-page {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  padding: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.page-description {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
  max-width: 600px;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: #3b82f6;
  color: white;
  border-radius: 8px;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 2px;
}

.map-section {
  flex: 1;
  position: relative;
}

.insights-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 320px;
  height: 100vh;
  background: white;
  border-left: 1px solid #e5e7eb;
  box-shadow: -4px 0 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  overflow-y: auto;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.insights-content {
  padding: 20px;
}

.insight-section {
  margin-bottom: 24px;
}

.insight-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.insight-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.insight-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.insight-name {
  font-size: 13px;
  color: #374151;
  flex: 1;
}

.insight-count {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  background: #e5e7eb;
  padding: 2px 8px;
  border-radius: 12px;
}

.insights-toggle {
  position: fixed;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 999;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.insights-toggle:hover {
  background: #f9fafb;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.insights-toggle.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 16px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
}

.privacy-description {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 20px 0;
  line-height: 1.5;
}

.privacy-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.privacy-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.privacy-option:hover {
  border-color: #d1d5db;
  background: #f9fafb;
}

.privacy-option:has(input:checked) {
  border-color: #3b82f6;
  background: #eff6ff;
}

.privacy-option input[type="radio"] {
  margin-top: 2px;
}

.option-content {
  flex: 1;
}

.option-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.option-description {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.4;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
  font-size: 12px;
  color: #6b7280;
  margin: 4px 0 0 0;
}

.form-actions {
  margin-top: 16px;
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Button Styles */
.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-spinner-sm {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 1024px) {
  .insights-panel {
    width: 280px;
  }
  
  .insights-toggle {
    right: 12px;
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 16px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: flex-end;
  }
  
  .quick-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .insights-panel {
    width: 100%;
    height: 100vh;
  }
  
  .insights-toggle {
    position: fixed;
    bottom: 16px;
    right: 16px;
    top: auto;
    transform: none;
  }
  
  .modal-content {
    margin: 0;
    border-radius: 0;
    height: 100vh;
    max-height: none;
  }
}

@media (max-width: 480px) {
  .quick-stats {
    grid-template-columns: 1fr;
  }
  
  .stat-card {
    padding: 12px;
  }
  
  .stat-icon {
    width: 40px;
    height: 40px;
  }
  
  .stat-number {
    font-size: 20px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}

@media (prefers-contrast: high) {
  .stat-card,
  .insight-item,
  .privacy-option {
    border-width: 2px;
  }
}
</style>