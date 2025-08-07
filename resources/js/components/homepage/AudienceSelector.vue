<template>
  <div class="audience-selector-container">
    <div class="audience-selector">
      <div class="selector-wrapper">
        <button
          :class="[
            'selector-button',
            { 'active': audience === 'individual' }
          ]"
          @click="selectAudience('individual')"
          aria-label="Switch to individual alumni view"
        >
          <Icon name="user" class="selector-icon" />
          <span class="selector-text">I'm an Alumnus</span>
        </button>
        
        <button
          :class="[
            'selector-button',
            { 'active': audience === 'institutional' }
          ]"
          @click="selectAudience('institutional')"
          aria-label="Switch to institutional administrator view"
        >
          <Icon name="building" class="selector-icon" />
          <span class="selector-text">I'm an Administrator</span>
        </button>
      </div>
      
      <div v-if="showDescription" class="selector-description">
        <div v-if="isDetecting" class="description-text">
          <span class="detecting-text">Detecting audience...</span>
        </div>
        <div v-else>
          <p v-if="audience === 'individual'" class="description-text">
            Discover career opportunities and connect with your alumni network
          </p>
          <p v-else class="description-text">
            Engage your alumni community with branded solutions and analytics
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { AudienceType, AudiencePreference, AudienceDetectionResult, DetectionFactor } from '@/types/homepage'
import Icon from '@/components/Icon.vue'

// Props
interface Props {
  audience: AudienceType
  autoDetect?: boolean
  showDescription?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  audience: 'individual',
  autoDetect: true,
  showDescription: true
})

// Emits
const emit = defineEmits<{
  'update:audience': [audience: AudienceType]
  'audience-changed': [audience: AudienceType, preference: AudiencePreference]
}>()

// Reactive data
const audience = ref<AudienceType>(props.audience)
const isDetecting = ref(false)

// Session storage key
const STORAGE_KEY = 'homepage_audience_preference'

// Methods
const selectAudience = (newAudience: AudienceType, source: 'manual' | 'auto_detected' | 'url_param' = 'manual') => {
  if (audience.value !== newAudience) {
    const previousAudience = audience.value
    audience.value = newAudience
    
    // Create preference object
    const preference: AudiencePreference = {
      type: newAudience,
      timestamp: new Date(),
      source,
      sessionId: getSessionId()
    }
    
    // Store preference in session storage
    storeAudiencePreference(preference)
    
    // Emit events
    emit('update:audience', newAudience)
    emit('audience-changed', newAudience, preference)
    
    // Track analytics
    trackAudienceChange(newAudience, previousAudience, source)
  }
}

const detectAudience = (): AudienceDetectionResult => {
  const factors: DetectionFactor[] = []
  let totalWeight = 0
  let institutionalScore = 0
  
  // Check URL parameters
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('audience')) {
    const audienceParam = urlParams.get('audience')
    if (audienceParam === 'institutional' || audienceParam === 'admin') {
      factors.push({
        type: 'url_param',
        value: audienceParam,
        weight: 0.8,
        contribution: 0.8
      })
      institutionalScore += 0.8
      totalWeight += 0.8
    }
  }
  
  // Check referrer
  if (document.referrer) {
    const referrer = new URL(document.referrer)
    const institutionalDomains = ['.edu', '.ac.', 'university', 'college', 'admin']
    const isInstitutional = institutionalDomains.some(domain => 
      referrer.hostname.includes(domain)
    )
    
    if (isInstitutional) {
      factors.push({
        type: 'referrer',
        value: referrer.hostname,
        weight: 0.6,
        contribution: 0.6
      })
      institutionalScore += 0.6
      totalWeight += 0.6
    }
  }
  
  // Check stored preference
  const storedPreference = getStoredAudiencePreference()
  if (storedPreference) {
    const weight = storedPreference.source === 'manual' ? 0.9 : 0.5
    factors.push({
      type: 'session_history',
      value: storedPreference.type,
      weight,
      contribution: storedPreference.type === 'institutional' ? weight : 0
    })
    
    if (storedPreference.type === 'institutional') {
      institutionalScore += weight
    }
    totalWeight += weight
  }
  
  // Calculate confidence and determine audience
  const confidence = totalWeight > 0 ? Math.min(institutionalScore / totalWeight, 1) : 0
  const detectedAudience: AudienceType = confidence > 0.5 ? 'institutional' : 'individual'
  
  return {
    detectedAudience,
    confidence,
    factors,
    fallback: 'individual'
  }
}

const storeAudiencePreference = (preference: AudiencePreference) => {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(preference))
  } catch (error) {
    console.warn('Failed to store audience preference:', error)
  }
}

const getStoredAudiencePreference = (): AudiencePreference | null => {
  try {
    const stored = sessionStorage.getItem(STORAGE_KEY)
    if (stored) {
      const preference = JSON.parse(stored) as AudiencePreference
      // Convert timestamp back to Date object
      preference.timestamp = new Date(preference.timestamp)
      return preference
    }
  } catch (error) {
    console.warn('Failed to retrieve audience preference:', error)
  }
  return null
}

const getSessionId = (): string => {
  let sessionId = sessionStorage.getItem('homepage_session_id')
  if (!sessionId) {
    sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    sessionStorage.setItem('homepage_session_id', sessionId)
  }
  return sessionId
}

const trackAudienceChange = (newAudience: AudienceType, previousAudience: AudienceType, source: string) => {
  // Analytics tracking
  if (typeof window !== 'undefined' && 'gtag' in window) {
    (window as any).gtag('event', 'audience_change', {
      new_audience: newAudience,
      previous_audience: previousAudience,
      change_source: source,
      session_id: getSessionId()
    })
  }
  
  // Custom analytics event
  const event = new CustomEvent('homepage:audience-changed', {
    detail: {
      newAudience,
      previousAudience,
      source,
      timestamp: new Date()
    }
  })
  window.dispatchEvent(event)
}

// Initialize audience detection on mount
onMounted(() => {
  if (props.autoDetect) {
    isDetecting.value = true
    
    // Small delay to ensure DOM is ready
    setTimeout(() => {
      const detection = detectAudience()
      
      // Use detected audience if confidence is high enough
      if (detection.confidence > 0.7 && detection.detectedAudience !== audience.value) {
        selectAudience(detection.detectedAudience, 'auto_detected')
      }
      
      isDetecting.value = false
    }, 100)
  }
})

// Watch for prop changes
watch(() => props.audience, (newAudience) => {
  if (audience.value !== newAudience) {
    audience.value = newAudience
  }
})
</script>

<style scoped>
.audience-selector-container {
  @apply fixed top-4 right-4 z-50;
}

.audience-selector {
  @apply bg-white rounded-lg shadow-lg border border-gray-200 p-4;
  @apply backdrop-blur-sm bg-white/95;
}

.selector-wrapper {
  @apply flex gap-2 mb-3;
}

.selector-button {
  @apply flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300;
  @apply text-sm font-medium text-gray-700 bg-white;
  @apply hover:bg-gray-50 hover:border-gray-400;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
  @apply transition-all duration-200;
}

.selector-button.active {
  @apply bg-blue-600 text-white border-blue-600;
  @apply hover:bg-blue-700 hover:border-blue-700;
}

.selector-icon {
  @apply w-4 h-4;
}

.selector-text {
  @apply whitespace-nowrap;
}

.selector-description {
  @apply text-center;
}

.description-text {
  @apply text-xs text-gray-600 leading-tight;
  @apply max-w-48;
}

.detecting-text {
  @apply text-xs text-blue-600 italic;
  @apply animate-pulse;
}

/* Mobile responsive */
@media (max-width: 767px) {
  .audience-selector-container {
    @apply top-2 right-2 left-2;
  }
  
  .audience-selector {
    @apply p-3;
  }
  
  .selector-wrapper {
    @apply flex-col gap-2;
  }
  
  .selector-button {
    @apply justify-center px-3 py-2;
  }
  
  .selector-text {
    @apply text-xs;
  }
  
  .description-text {
    @apply max-w-none;
  }
}

@media (min-width: 768px) and (max-width: 1023px) {
  .audience-selector-container {
    @apply top-3 right-3;
  }
}
</style>