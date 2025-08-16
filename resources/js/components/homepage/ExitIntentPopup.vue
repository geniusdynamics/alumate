<template>
  <Teleport to="body">
    <div 
      v-if="isVisible"
      class="exit-intent-overlay"
      @click="handleOverlayClick"
    >
      <div 
        class="exit-intent-popup"
        @click.stop
        :class="{ 'popup-mobile': isMobile }"
      >
        <!-- Close button -->
        <button 
          class="close-button"
          @click="$emit('close')"
          aria-label="Close popup"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Popup content based on audience -->
        <div class="popup-content">
          <div class="popup-header">
            <h2 class="popup-title">{{ popupContent.title }}</h2>
            <p class="popup-subtitle">{{ popupContent.subtitle }}</p>
          </div>

          <!-- Special offer content -->
          <div class="special-offer" v-if="specialOffer">
            <div class="offer-badge">{{ specialOffer.badge }}</div>
            <div class="offer-content">
              <h3 class="offer-title">{{ specialOffer.title }}</h3>
              <p class="offer-description">{{ specialOffer.description }}</p>
              
              <!-- Offer details -->
              <div class="offer-details" v-if="specialOffer.details">
                <ul class="offer-list">
                  <li v-for="detail in specialOffer.details" :key="detail" class="offer-item">
                    <svg class="offer-check" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ detail }}
                  </li>
                </ul>
              </div>

              <!-- Countdown timer if applicable -->
              <div v-if="specialOffer.countdown" class="countdown-timer">
                <span class="countdown-label">{{ specialOffer.countdownLabel }}:</span>
                <div class="countdown-display">
                  <span class="countdown-time">{{ formatCountdown(countdown) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- CTA buttons -->
          <div class="popup-actions">
            <button 
              class="primary-cta-button"
              @click="handlePrimaryCTA"
              :disabled="isProcessing"
            >
              <span v-if="!isProcessing">{{ popupContent.primaryCTA.text }}</span>
              <span v-else class="loading-spinner">Processing...</span>
            </button>
            
            <button 
              v-if="popupContent.secondaryCTA"
              class="secondary-cta-button"
              @click="handleSecondaryCTA"
            >
              {{ popupContent.secondaryCTA.text }}
            </button>
          </div>

          <!-- Trust indicators -->
          <div class="trust-indicators" v-if="popupContent.trustIndicators">
            <div class="trust-item" v-for="indicator in popupContent.trustIndicators" :key="indicator.text">
              <component :is="indicator.icon" class="trust-icon" />
              <span class="trust-text">{{ indicator.text }}</span>
            </div>
          </div>

          <!-- Social proof -->
          <div class="social-proof" v-if="popupContent.socialProof">
            <p class="social-proof-text">{{ popupContent.socialProof }}</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import { useAudienceDetection } from '@/composables/useAudienceDetection'
import type { AudienceType, ExitIntentOffer } from '@/types/homepage'

interface Props {
  audience: AudienceType
  specialOffer?: ExitIntentOffer
}

interface Emits {
  (e: 'close'): void
  (e: 'convert', action: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()
const { isMobile } = useAudienceDetection()

// Reactive state
const isVisible = ref(true)
const isProcessing = ref(false)
const countdown = ref(0)
const countdownInterval = ref<NodeJS.Timeout>()

// Computed properties
const popupContent = computed(() => {
  if (props.audience === 'institutional') {
    return {
      title: "Before You Go...",
      subtitle: "See how universities are increasing alumni engagement by 300%",
      primaryCTA: {
        text: "Schedule Free Demo",
        action: "demo"
      },
      secondaryCTA: {
        text: "Download Case Studies",
        action: "download"
      },
      trustIndicators: [
        { icon: 'ShieldCheckIcon', text: 'SOC 2 Certified' },
        { icon: 'UserGroupIcon', text: '500+ Universities Trust Us' },
        { icon: 'ClockIcon', text: '30-Day Implementation' }
      ],
      socialProof: "Join 500+ institutions already transforming their alumni engagement"
    }
  } else {
    return {
      title: "Wait! Don't Miss Out",
      subtitle: "Join thousands of alumni advancing their careers",
      primaryCTA: {
        text: "Start Free Trial",
        action: "trial"
      },
      secondaryCTA: {
        text: "Join Waitlist",
        action: "waitlist"
      },
      trustIndicators: [
        { icon: 'StarIcon', text: '4.9/5 Rating' },
        { icon: 'UserGroupIcon', text: '50,000+ Alumni' },
        { icon: 'TrendingUpIcon', text: 'Avg 40% Salary Increase' }
      ],
      socialProof: "Join 50,000+ alumni who've advanced their careers"
    }
  }
})

// Methods
const handleOverlayClick = () => {
  emit('close')
}

const handlePrimaryCTA = async () => {
  isProcessing.value = true
  
  try {
    // Track conversion
    trackEvent('exit_intent_primary_cta', {
      audience: props.audience,
      action: popupContent.value.primaryCTA.action,
      hasSpecialOffer: !!props.specialOffer
    })
    
    // Simulate processing delay
    await new Promise(resolve => setTimeout(resolve, 500))
    
    emit('convert', popupContent.value.primaryCTA.action)
  } finally {
    isProcessing.value = false
  }
}

const handleSecondaryCTA = () => {
  if (popupContent.value.secondaryCTA) {
    trackEvent('exit_intent_secondary_cta', {
      audience: props.audience,
      action: popupContent.value.secondaryCTA.action,
      hasSpecialOffer: !!props.specialOffer
    })
    
    emit('convert', popupContent.value.secondaryCTA.action)
  }
}

const formatCountdown = (seconds: number) => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const startCountdown = () => {
  if (props.specialOffer?.countdown) {
    countdown.value = props.specialOffer.countdown
    countdownInterval.value = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(countdownInterval.value)
        // Auto-close or update offer when countdown expires
        emit('close')
      }
    }, 1000)
  }
}

// Lifecycle
onMounted(() => {
  // Track popup display
  trackEvent('exit_intent_popup_shown', {
    audience: props.audience,
    hasSpecialOffer: !!props.specialOffer
  })
  
  // Start countdown if applicable
  startCountdown()
  
  // Prevent body scroll
  document.body.style.overflow = 'hidden'
  
  // Handle escape key
  const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
      emit('close')
    }
  }
  document.addEventListener('keydown', handleEscape)
  
  return () => {
    document.removeEventListener('keydown', handleEscape)
  }
})

onUnmounted(() => {
  // Restore body scroll
  document.body.style.overflow = ''
  
  // Clear countdown
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
})
</script>

<style scoped>
.exit-intent-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease-out;
}

.exit-intent-popup {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: slideIn 0.3s ease-out;
}

.popup-mobile {
  max-width: 95%;
  margin: 10px;
}

.close-button {
  position: absolute;
  top: 16px;
  right: 16px;
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
  z-index: 10;
}

.close-button:hover {
  color: #374151;
  background: #f3f4f6;
}

.popup-content {
  padding: 32px;
}

.popup-header {
  text-align: center;
  margin-bottom: 24px;
}

.popup-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.popup-subtitle {
  font-size: 16px;
  color: #6b7280;
  line-height: 1.5;
}

.special-offer {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}

.offer-badge {
  background: #dc2626;
  color: white;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 12px;
  display: inline-block;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.offer-title {
  font-size: 20px;
  font-weight: 600;
  color: #92400e;
  margin-bottom: 8px;
}

.offer-description {
  color: #92400e;
  margin-bottom: 16px;
  line-height: 1.5;
}

.offer-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.offer-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  color: #92400e;
}

.offer-check {
  width: 16px;
  height: 16px;
  color: #059669;
  margin-right: 8px;
  flex-shrink: 0;
}

.countdown-timer {
  background: rgba(146, 64, 14, 0.1);
  border-radius: 6px;
  padding: 12px;
  text-align: center;
  margin-top: 16px;
}

.countdown-label {
  font-size: 14px;
  color: #92400e;
  font-weight: 500;
}

.countdown-display {
  margin-top: 4px;
}

.countdown-time {
  font-size: 18px;
  font-weight: 700;
  color: #dc2626;
  font-family: 'Courier New', monospace;
}

.popup-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.primary-cta-button {
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px 24px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
}

.primary-cta-button:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.primary-cta-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.secondary-cta-button {
  background: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 12px 24px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.secondary-cta-button:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.trust-indicators {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.trust-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
}

.trust-icon {
  width: 16px;
  height: 16px;
  color: #059669;
}

.social-proof {
  text-align: center;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.social-proof-text {
  font-size: 14px;
  color: #6b7280;
  font-style: italic;
}

.loading-spinner {
  display: flex;
  align-items: center;
  gap: 8px;
}

.loading-spinner::after {
  content: '';
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to { 
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .popup-content {
    padding: 24px 20px;
  }
  
  .popup-title {
    font-size: 20px;
  }
  
  .trust-indicators {
    flex-direction: column;
    gap: 12px;
  }
  
  .popup-actions {
    gap: 8px;
  }
}
</style>