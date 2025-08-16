<template>
  <div 
    class="contextual-cta"
    :class="[
      `cta-${cta.variant || 'default'}`,
      `cta-${cta.size || 'medium'}`,
      { 'cta-urgent': cta.urgent }
    ]"
  >
    <div class="cta-content">
      <!-- Icon or visual element -->
      <div v-if="cta.icon || cta.image" class="cta-visual">
        <component 
          v-if="cta.icon" 
          :is="cta.icon" 
          class="cta-icon"
          :class="{ 'icon-animated': cta.animated }"
        />
        <img 
          v-else-if="cta.image" 
          :src="cta.image" 
          :alt="cta.imageAlt || ''"
          class="cta-image"
        />
      </div>

      <!-- Text content -->
      <div class="cta-text">
        <h4 v-if="cta.title" class="cta-title">{{ cta.title }}</h4>
        <p v-if="cta.description" class="cta-description">{{ cta.description }}</p>
        
        <!-- Benefits list -->
        <ul v-if="cta.benefits && cta.benefits.length" class="cta-benefits">
          <li v-for="benefit in cta.benefits" :key="benefit" class="benefit-item">
            <svg class="benefit-check" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            {{ benefit }}
          </li>
        </ul>

        <!-- Social proof -->
        <div v-if="cta.socialProof" class="cta-social-proof">
          <div class="social-proof-item" v-for="proof in cta.socialProof" :key="proof.text">
            <component :is="proof.icon" class="social-proof-icon" />
            <span class="social-proof-text">{{ proof.text }}</span>
          </div>
        </div>
      </div>

      <!-- Action area -->
      <div class="cta-actions">
        <button 
          class="cta-button"
          :class="[
            `button-${cta.buttonVariant || 'primary'}`,
            { 'button-loading': isLoading }
          ]"
          @click="handleClick"
          :disabled="isLoading"
        >
          <span v-if="!isLoading" class="button-content">
            <component v-if="cta.buttonIcon" :is="cta.buttonIcon" class="button-icon" />
            {{ cta.text }}
            <svg v-if="cta.showArrow" class="button-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </span>
          <span v-else class="loading-content">
            <svg class="loading-spinner" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
          </span>
        </button>

        <!-- Secondary action -->
        <button 
          v-if="cta.secondaryAction"
          class="cta-secondary-button"
          @click="handleSecondaryClick"
        >
          {{ cta.secondaryAction.text }}
        </button>
      </div>

      <!-- Urgency indicator -->
      <div v-if="cta.urgency" class="cta-urgency">
        <div class="urgency-indicator">
          <svg class="urgency-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          <span class="urgency-text">{{ cta.urgency.text }}</span>
        </div>
        
        <!-- Countdown timer -->
        <div v-if="cta.urgency.countdown" class="urgency-countdown">
          <span class="countdown-time">{{ formatCountdown(countdown) }}</span>
        </div>
      </div>

      <!-- Trust badges -->
      <div v-if="cta.trustBadges && cta.trustBadges.length" class="cta-trust-badges">
        <div 
          v-for="badge in cta.trustBadges" 
          :key="badge.name"
          class="trust-badge"
          :title="badge.description"
        >
          <img :src="badge.image" :alt="badge.name" class="badge-image" />
        </div>
      </div>
    </div>

    <!-- Background decoration -->
    <div v-if="cta.backgroundPattern" class="cta-background">
      <div class="background-pattern" :class="`pattern-${cta.backgroundPattern}`"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { AudienceType, ContextualCTAData, CTAClickEvent } from '@/types/homepage'

interface Props {
  cta: ContextualCTAData
  audience: AudienceType
}

interface Emits {
  (e: 'click', event: CTAClickEvent): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const isLoading = ref(false)
const countdown = ref(0)
const countdownInterval = ref<NodeJS.Timeout>()

// Methods
const handleClick = async () => {
  if (isLoading.value) return
  
  isLoading.value = true
  
  try {
    const event: CTAClickEvent = {
      action: props.cta.action,
      section: props.cta.section || 'contextual-cta',
      audience: props.audience,
      additionalData: {
        ctaId: props.cta.id,
        ctaType: 'contextual',
        variant: props.cta.variant,
        title: props.cta.title,
        hasUrgency: !!props.cta.urgency,
        hasSocialProof: !!(props.cta.socialProof && props.cta.socialProof.length > 0)
      }
    }
    
    // Track the click
    trackEvent('contextual_cta_click', event)
    
    // Simulate processing delay for better UX
    await new Promise(resolve => setTimeout(resolve, 300))
    
    emit('click', event)
  } finally {
    isLoading.value = false
  }
}

const handleSecondaryClick = () => {
  if (!props.cta.secondaryAction) return
  
  const event: CTAClickEvent = {
    action: props.cta.secondaryAction.action,
    section: props.cta.section || 'contextual-cta',
    audience: props.audience,
    additionalData: {
      ctaId: props.cta.id,
      ctaType: 'contextual-secondary',
      variant: props.cta.variant
    }
  }
  
  trackEvent('contextual_cta_secondary_click', event)
  emit('click', event)
}

const formatCountdown = (seconds: number) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = seconds % 60
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
  }
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const startCountdown = () => {
  if (props.cta.urgency?.countdown) {
    countdown.value = props.cta.urgency.countdown
    countdownInterval.value = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(countdownInterval.value)
        // Handle countdown expiry
        trackEvent('contextual_cta_countdown_expired', {
          ctaId: props.cta.id,
          audience: props.audience
        })
      }
    }, 1000)
  }
}

// Lifecycle
onMounted(() => {
  // Track CTA impression
  trackEvent('contextual_cta_impression', {
    ctaId: props.cta.id,
    audience: props.audience,
    variant: props.cta.variant,
    section: props.cta.section
  })
  
  // Start countdown if applicable
  startCountdown()
})

onUnmounted(() => {
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
})
</script>

<style scoped>
.contextual-cta {
  background: white;
  border-radius: 12px;
  padding: 24px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  border: 1px solid #e5e7eb;
}

.contextual-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* CTA Variants */
.cta-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
}

.cta-secondary {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.cta-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
}

.cta-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  border: none;
}

.cta-urgent {
  animation: pulse 2s infinite;
  border-color: #dc2626;
}

/* Size variants */
.cta-small {
  padding: 16px;
}

.cta-large {
  padding: 32px;
}

.cta-content {
  position: relative;
  z-index: 2;
}

.cta-visual {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.cta-icon {
  width: 48px;
  height: 48px;
  color: currentColor;
}

.icon-animated {
  animation: bounce 2s infinite;
}

.cta-image {
  width: 64px;
  height: 64px;
  border-radius: 8px;
  object-fit: cover;
}

.cta-text {
  text-align: center;
  margin-bottom: 20px;
}

.cta-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 8px;
  color: inherit;
}

.cta-description {
  font-size: 16px;
  line-height: 1.5;
  opacity: 0.9;
  margin-bottom: 16px;
}

.cta-benefits {
  list-style: none;
  padding: 0;
  margin: 16px 0;
  text-align: left;
}

.benefit-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 14px;
}

.benefit-check {
  width: 16px;
  height: 16px;
  color: #10b981;
  margin-right: 8px;
  flex-shrink: 0;
}

.cta-social-proof {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 16px;
  flex-wrap: wrap;
}

.social-proof-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  opacity: 0.8;
}

.social-proof-icon {
  width: 14px;
  height: 14px;
}

.cta-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
}

.cta-button {
  background: rgba(255, 255, 255, 0.2);
  color: inherit;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  padding: 14px 28px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  min-width: 160px;
}

.cta-primary .cta-button,
.cta-success .cta-button,
.cta-warning .cta-button {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
}

.cta-secondary .cta-button {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.cta-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-1px);
}

.cta-secondary .cta-button:hover:not(:disabled) {
  background: #2563eb;
  border-color: #2563eb;
}

.cta-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.button-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.button-icon {
  width: 18px;
  height: 18px;
}

.button-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s;
}

.cta-button:hover .button-arrow {
  transform: translateX(2px);
}

.loading-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

.cta-secondary-button {
  background: transparent;
  color: inherit;
  border: 1px solid currentColor;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  opacity: 0.8;
}

.cta-secondary-button:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

.cta-urgency {
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 8px;
  padding: 12px;
  margin-top: 16px;
  text-align: center;
}

.urgency-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 8px;
}

.urgency-icon {
  width: 16px;
  height: 16px;
  color: #dc2626;
}

.urgency-text {
  font-size: 14px;
  font-weight: 600;
  color: #dc2626;
}

.urgency-countdown {
  font-family: 'Courier New', monospace;
  font-size: 18px;
  font-weight: 700;
  color: #dc2626;
}

.cta-trust-badges {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.trust-badge {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.trust-badge:hover {
  opacity: 1;
}

.badge-image {
  height: 24px;
  width: auto;
  filter: grayscale(1);
  transition: filter 0.2s;
}

.trust-badge:hover .badge-image {
  filter: grayscale(0);
}

.cta-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  opacity: 0.1;
}

.background-pattern {
  width: 100%;
  height: 100%;
}

.pattern-dots {
  background-image: radial-gradient(circle, currentColor 1px, transparent 1px);
  background-size: 20px 20px;
}

.pattern-lines {
  background-image: linear-gradient(45deg, currentColor 1px, transparent 1px);
  background-size: 20px 20px;
}

/* Animations */
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translateY(0);
  }
  40%, 43% {
    transform: translateY(-8px);
  }
  70% {
    transform: translateY(-4px);
  }
  90% {
    transform: translateY(-2px);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive design */
@media (max-width: 640px) {
  .contextual-cta {
    padding: 20px 16px;
  }
  
  .cta-title {
    font-size: 18px;
  }
  
  .cta-description {
    font-size: 14px;
  }
  
  .cta-button {
    width: 100%;
    min-width: auto;
  }
  
  .cta-social-proof {
    flex-direction: column;
    gap: 8px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .contextual-cta,
  .cta-button,
  .button-arrow,
  .icon-animated {
    animation: none;
    transition: none;
  }
  
  .contextual-cta:hover {
    transform: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .contextual-cta {
    border-width: 2px;
  }
  
  .cta-button {
    border-width: 3px;
  }
}
</style>