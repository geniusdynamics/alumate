<template>
  <div 
    class="floating-mobile-cta"
    :class="{ 
      'cta-visible': isVisible,
      'cta-expanded': isExpanded,
      'cta-institutional': audience === 'institutional'
    }"
  >
    <!-- Collapsed state -->
    <div v-if="!isExpanded" class="cta-collapsed">
      <button 
        class="cta-main-button"
        @click="handleMainCTAClick"
        :aria-label="primaryCTA.text"
      >
        <component :is="getMainIcon()" class="cta-main-icon" />
        <span class="cta-main-text">{{ primaryCTA.text }}</span>
      </button>
      
      <!-- Expand button -->
      <button 
        class="cta-expand-button"
        @click="toggleExpanded"
        aria-label="More options"
      >
        <svg class="expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
      </button>
    </div>

    <!-- Expanded state -->
    <div v-else class="cta-expanded-content">
      <!-- Close button -->
      <button 
        class="cta-close-button"
        @click="toggleExpanded"
        aria-label="Close options"
      >
        <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Expanded CTA options -->
      <div class="expanded-options">
        <div class="option-header">
          <h4 class="option-title">{{ expandedContent.title }}</h4>
          <p class="option-subtitle">{{ expandedContent.subtitle }}</p>
        </div>

        <div class="option-buttons">
          <button 
            class="option-primary-button"
            @click="handleCTAClick(expandedContent.primaryCTA)"
          >
            <component :is="expandedContent.primaryCTA.icon" class="option-icon" />
            <div class="option-text">
              <span class="option-label">{{ expandedContent.primaryCTA.text }}</span>
              <span class="option-description">{{ expandedContent.primaryCTA.description }}</span>
            </div>
          </button>

          <button 
            v-if="expandedContent.secondaryCTA"
            class="option-secondary-button"
            @click="handleCTAClick(expandedContent.secondaryCTA)"
          >
            <component :is="expandedContent.secondaryCTA.icon" class="option-icon" />
            <div class="option-text">
              <span class="option-label">{{ expandedContent.secondaryCTA.text }}</span>
              <span class="option-description">{{ expandedContent.secondaryCTA.description }}</span>
            </div>
          </button>

          <button 
            v-if="expandedContent.tertiaryCTA"
            class="option-tertiary-button"
            @click="handleCTAClick(expandedContent.tertiaryCTA)"
          >
            <component :is="expandedContent.tertiaryCTA.icon" class="option-icon" />
            <div class="option-text">
              <span class="option-label">{{ expandedContent.tertiaryCTA.text }}</span>
              <span class="option-description">{{ expandedContent.tertiaryCTA.description }}</span>
            </div>
          </button>
        </div>

        <!-- Trust indicators -->
        <div class="mobile-trust-indicators" v-if="expandedContent.trustIndicators">
          <div 
            class="trust-item"
            v-for="indicator in expandedContent.trustIndicators"
            :key="indicator.text"
          >
            <component :is="indicator.icon" class="trust-icon" />
            <span class="trust-text">{{ indicator.text }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress indicator -->
    <div class="cta-progress" v-if="showProgress">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: progressPercentage + '%' }"
        ></div>
      </div>
      <span class="progress-text">{{ progressText }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { AudienceType, CTAButton, CTAClickEvent, FloatingCTAContent } from '@/types/homepage'

interface Props {
  audience: AudienceType
  primaryCTA: CTAButton
  isVisible?: boolean
  showProgress?: boolean
  progressPercentage?: number
  progressText?: string
}

interface Emits {
  (e: 'click', event: CTAClickEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  isVisible: true,
  showProgress: false,
  progressPercentage: 0,
  progressText: ''
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const isExpanded = ref(false)
const touchStartY = ref(0)
const isDragging = ref(false)

// Computed properties
const expandedContent = computed((): FloatingCTAContent => {
  if (props.audience === 'institutional') {
    return {
      title: 'Transform Alumni Engagement',
      subtitle: 'See how universities increase engagement by 300%',
      primaryCTA: {
        text: 'Schedule Demo',
        description: 'Personalized 30-min demo',
        action: 'demo',
        icon: 'CalendarIcon'
      },
      secondaryCTA: {
        text: 'Download Guide',
        description: 'Free implementation guide',
        action: 'download',
        icon: 'DocumentDownloadIcon'
      },
      tertiaryCTA: {
        text: 'Contact Sales',
        description: 'Speak with an expert',
        action: 'contact',
        icon: 'PhoneIcon'
      },
      trustIndicators: [
        { icon: 'ShieldCheckIcon', text: 'SOC 2 Certified' },
        { icon: 'UserGroupIcon', text: '500+ Universities' },
        { icon: 'ClockIcon', text: '30-Day Setup' }
      ]
    }
  }
  
  return {
    title: 'Advance Your Career',
    subtitle: 'Join 50,000+ successful alumni',
    primaryCTA: {
      text: 'Start Free Trial',
      description: '14-day free trial',
      action: 'trial',
      icon: 'PlayIcon'
    },
    secondaryCTA: {
      text: 'Join Waitlist',
      description: 'Get early access',
      action: 'waitlist',
      icon: 'UserAddIcon'
    },
    tertiaryCTA: {
      text: 'Learn More',
      description: 'See success stories',
      action: 'learn-more',
      icon: 'InformationCircleIcon'
    },
    trustIndicators: [
      { icon: 'StarIcon', text: '4.9/5 Rating' },
      { icon: 'TrendingUpIcon', text: '40% Salary Increase' },
      { icon: 'UserGroupIcon', text: '50,000+ Alumni' }
    ]
  }
})

// Methods
const getMainIcon = () => {
  const iconMap = {
    'register': 'UserAddIcon',
    'trial': 'PlayIcon',
    'demo': 'CalendarIcon',
    'contact': 'PhoneIcon',
    'learn-more': 'InformationCircleIcon'
  }
  return iconMap[props.primaryCTA.action] || 'ArrowRightIcon'
}

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
  
  trackEvent('floating_cta_toggle', {
    audience: props.audience,
    expanded: isExpanded.value,
    action: 'toggle'
  })
}

const handleMainCTAClick = () => {
  const event: CTAClickEvent = {
    action: props.primaryCTA.action,
    section: 'floating-mobile-cta',
    audience: props.audience,
    additionalData: {
      ctaType: 'floating-main',
      expanded: false
    }
  }
  
  trackEvent('floating_cta_main_click', event)
  emit('click', event)
}

const handleCTAClick = (cta: any) => {
  const event: CTAClickEvent = {
    action: cta.action,
    section: 'floating-mobile-cta',
    audience: props.audience,
    additionalData: {
      ctaType: 'floating-expanded',
      expanded: true,
      ctaText: cta.text
    }
  }
  
  trackEvent('floating_cta_expanded_click', event)
  emit('click', event)
  
  // Close expanded state after click
  isExpanded.value = false
}

// Touch handling for swipe gestures
const handleTouchStart = (e: TouchEvent) => {
  touchStartY.value = e.touches[0].clientY
  isDragging.value = false
}

const handleTouchMove = (e: TouchEvent) => {
  if (!touchStartY.value) return
  
  const touchY = e.touches[0].clientY
  const deltaY = touchStartY.value - touchY
  
  if (Math.abs(deltaY) > 10) {
    isDragging.value = true
    
    // Swipe up to expand, swipe down to collapse
    if (deltaY > 30 && !isExpanded.value) {
      toggleExpanded()
    } else if (deltaY < -30 && isExpanded.value) {
      toggleExpanded()
    }
  }
}

const handleTouchEnd = () => {
  touchStartY.value = 0
  isDragging.value = false
}

// Lifecycle
onMounted(() => {
  trackEvent('floating_mobile_cta_mounted', {
    audience: props.audience,
    primaryAction: props.primaryCTA.action
  })
  
  // Add touch event listeners
  const ctaElement = document.querySelector('.floating-mobile-cta')
  if (ctaElement) {
    ctaElement.addEventListener('touchstart', handleTouchStart, { passive: true })
    ctaElement.addEventListener('touchmove', handleTouchMove, { passive: true })
    ctaElement.addEventListener('touchend', handleTouchEnd, { passive: true })
  }
})

onUnmounted(() => {
  // Remove touch event listeners
  const ctaElement = document.querySelector('.floating-mobile-cta')
  if (ctaElement) {
    ctaElement.removeEventListener('touchstart', handleTouchStart)
    ctaElement.removeEventListener('touchmove', handleTouchMove)
    ctaElement.removeEventListener('touchend', handleTouchEnd)
  }
})
</script>

<style scoped>
.floating-mobile-cta {
  position: fixed;
  bottom: 20px;
  left: 20px;
  right: 20px;
  z-index: 1000;
  opacity: 0;
  transform: translateY(100%);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.floating-mobile-cta.cta-visible {
  opacity: 1;
  transform: translateY(0);
}

.cta-collapsed {
  display: flex;
  background: #2563eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.cta-institutional .cta-collapsed {
  background: #7c3aed;
  box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
}

.cta-main-button {
  flex: 1;
  background: none;
  border: none;
  color: white;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 56px;
}

.cta-main-button:active {
  background: rgba(255, 255, 255, 0.1);
}

.cta-main-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.cta-main-text {
  font-size: 16px;
  font-weight: 600;
  text-align: left;
}

.cta-expand-button {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
}

.cta-expand-button:active {
  background: rgba(255, 255, 255, 0.2);
}

.expand-icon {
  width: 20px;
  height: 20px;
  transition: transform 0.3s;
}

.cta-expanded .expand-icon {
  transform: rotate(180deg);
}

.cta-expanded-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  position: relative;
}

.cta-close-button {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
}

.cta-close-button:hover {
  background: #e5e7eb;
}

.close-icon {
  width: 16px;
  height: 16px;
  color: #6b7280;
}

.expanded-options {
  padding: 24px 20px 20px;
}

.option-header {
  text-align: center;
  margin-bottom: 20px;
}

.option-title {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.option-subtitle {
  font-size: 14px;
  color: #6b7280;
}

.option-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.option-primary-button,
.option-secondary-button,
.option-tertiary-button {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  min-height: 64px;
}

.option-primary-button {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

.cta-institutional .option-primary-button {
  background: #7c3aed;
  border-color: #7c3aed;
}

.option-primary-button:active {
  background: #1d4ed8;
}

.cta-institutional .option-primary-button:active {
  background: #6d28d9;
}

.option-secondary-button:active,
.option-tertiary-button:active {
  background: #f9fafb;
  border-color: #d1d5db;
}

.option-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.option-primary-button .option-icon {
  color: white;
}

.option-secondary-button .option-icon,
.option-tertiary-button .option-icon {
  color: #6b7280;
}

.option-text {
  flex: 1;
}

.option-label {
  display: block;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 2px;
}

.option-primary-button .option-label {
  color: white;
}

.option-secondary-button .option-label,
.option-tertiary-button .option-label {
  color: #111827;
}

.option-description {
  display: block;
  font-size: 13px;
  opacity: 0.8;
}

.option-primary-button .option-description {
  color: rgba(255, 255, 255, 0.9);
}

.option-secondary-button .option-description,
.option-tertiary-button .option-description {
  color: #6b7280;
}

.mobile-trust-indicators {
  display: flex;
  justify-content: center;
  gap: 20px;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
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
  width: 14px;
  height: 14px;
  color: #10b981;
}

.cta-progress {
  background: rgba(255, 255, 255, 0.1);
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.progress-bar {
  flex: 1;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: white;
  border-radius: 2px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: white;
  font-weight: 500;
  white-space: nowrap;
}

/* Animation for expanded state */
.cta-expanded {
  animation: expandUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes expandUp {
  from {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

/* Safe area handling for devices with notches */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  .floating-mobile-cta {
    bottom: calc(20px + env(safe-area-inset-bottom));
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .floating-mobile-cta,
  .expand-icon,
  .option-primary-button,
  .option-secondary-button,
  .option-tertiary-button {
    transition: none;
  }
  
  .cta-expanded {
    animation: none;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .cta-collapsed {
    border: 2px solid white;
  }
  
  .cta-expanded-content {
    border: 2px solid #000;
  }
  
  .option-primary-button,
  .option-secondary-button,
  .option-tertiary-button {
    border-width: 2px;
  }
}
</style>