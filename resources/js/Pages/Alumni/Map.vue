<template>
  <DefaultLayout>
    <div class="alumni-map-page">
      <div class="page-header">
        <div class="container-fluid">
          <div class="row align-items-center">
            <div class="col">
              <h1 class="page-title">
                <i class="fas fa-map-marked-alt"></i>
                Alumni Network Map
              </h1>
              <p class="page-description">
                Discover and connect with alumni around the world
              </p>
            </div>
            <div class="col-auto">
              <div class="page-actions">
                <button 
                  class="btn btn-outline-primary"
                  @click="showLocationSettings = true"
                >
                  <i class="fas fa-cog"></i>
                  Location Settings
                </button>
                <button 
                  class="btn btn-primary"
                  @click="findMyLocation"
                >
                  <i class="fas fa-location-arrow"></i>
                  Find Me
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="map-container">
        <AlumniMap 
          :initial-center="mapCenter"
          :initial-zoom="mapZoom"
          height="calc(100vh - 200px)"
        />
      </div>

      <!-- Location Settings Modal -->
      <div 
        v-if="showLocationSettings" 
        class="modal fade show d-block"
        tabindex="-1"
        style="background-color: rgba(0,0,0,0.5)"
      >
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Location Settings</h5>
              <button 
                type="button" 
                class="btn-close"
                @click="showLocationSettings = false"
              ></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="updateLocationSettings">
                <div class="mb-3">
                  <label class="form-label">Location Privacy</label>
                  <select v-model="locationSettings.privacy" class="form-select">
                    <option value="public">Public - Visible to everyone</option>
                    <option value="alumni_only">Alumni Only - Visible to other alumni</option>
                    <option value="private">Private - Not visible on map</option>
                  </select>
                  <div class="form-text">
                    Control who can see your location on the alumni map
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Current Location</label>
                  <div class="row">
                    <div class="col-md-6">
                      <input 
                        v-model="locationSettings.city"
                        type="text" 
                        class="form-control" 
                        placeholder="City"
                      />
                    </div>
                    <div class="col-md-6">
                      <input 
                        v-model="locationSettings.state"
                        type="text" 
                        class="form-control" 
                        placeholder="State/Province"
                      />
                    </div>
                  </div>
                  <div class="mt-2">
                    <input 
                      v-model="locationSettings.country"
                      type="text" 
                      class="form-control" 
                      placeholder="Country"
                    />
                  </div>
                </div>

                <div class="mb-3">
                  <button 
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="getCurrentLocation"
                    :disabled="gettingLocation"
                  >
                    <i class="fas fa-crosshairs"></i>
                    {{ gettingLocation ? 'Getting Location...' : 'Use Current Location' }}
                  </button>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button 
                type="button" 
                class="btn btn-secondary"
                @click="showLocationSettings = false"
              >
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-primary"
                @click="updateLocationSettings"
                :disabled="updatingLocation"
              >
                {{ updatingLocation ? 'Updating...' : 'Save Changes' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import AlumniMap from '@/Components/AlumniMap.vue'

interface LocationSettings {
  privacy: string
  city: string
  state: string
  country: string
  latitude?: number
  longitude?: number
}

// Reactive data
const showLocationSettings = ref(false)
const gettingLocation = ref(false)
const updatingLocation = ref(false)

const mapCenter = ref<[number, number]>([39.8283, -98.5795]) // Center of USA
const mapZoom = ref(4)

const locationSettings = ref<LocationSettings>({
  privacy: 'alumni_only',
  city: '',
  state: '',
  country: ''
})

// Methods
const findMyLocation = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.')
    return
  }

  gettingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords
      mapCenter.value = [latitude, longitude]
      mapZoom.value = 12
      gettingLocation.value = false
    },
    (error) => {
      console.error('Error getting location:', error)
      gettingLocation.value = false
      alert('Unable to retrieve your location.')
    }
  )
}

const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.')
    return
  }

  gettingLocation.value = true

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords
      
      locationSettings.value.latitude = latitude
      locationSettings.value.longitude = longitude

      // Try to reverse geocode to get address
      try {
        // This would typically use a geocoding service
        // For now, just set the coordinates
        console.log('Got coordinates:', latitude, longitude)
      } catch (error) {
        console.error('Error reverse geocoding:', error)
      }

      gettingLocation.value = false
    },
    (error) => {
      console.error('Error getting location:', error)
      gettingLocation.value = false
      alert('Unable to retrieve your location.')
    }
  )
}

const updateLocationSettings = async () => {
  updatingLocation.value = true

  try {
    const response = await fetch('/api/alumni/location', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        latitude: locationSettings.value.latitude,
        longitude: locationSettings.value.longitude,
        city: locationSettings.value.city,
        state: locationSettings.value.state,
        country: locationSettings.value.country,
        privacy_level: locationSettings.value.privacy
      })
    })

    if (response.ok) {
      showLocationSettings.value = false
      // Show success message
      alert('Location settings updated successfully!')
    } else {
      throw new Error('Failed to update location settings')
    }
  } catch (error) {
    console.error('Error updating location:', error)
    alert('Failed to update location settings. Please try again.')
  } finally {
    updatingLocation.value = false
  }
}

// Initialize
onMounted(() => {
  // Load user's current location settings if available
  // This would typically come from props or API call
})
</script>

<style scoped>
.alumni-map-page {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.page-header {
  background: white;
  border-bottom: 1px solid #dee2e6;
  padding: 1rem 0;
  flex-shrink: 0;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-title i {
  color: #007bff;
}

.page-description {
  margin: 0.25rem 0 0 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.page-actions {
  display: flex;
  gap: 0.5rem;
}

.map-container {
  flex: 1;
  position: relative;
  overflow: hidden;
}

/* Modal styles */
.modal.show {
  display: block !important;
}

.modal-dialog {
  margin-top: 5vh;
}

.modal-content {
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
  border-bottom: 1px solid #e9ecef;
  padding: 1.5rem;
}

.modal-title {
  font-weight: 600;
  color: #495057;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  border-top: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .page-header .row {
    flex-direction: column;
    gap: 1rem;
  }
  
  .page-actions {
    justify-content: center;
  }
  
  .modal-dialog {
    margin: 1rem;
  }
}
</style>