<template>
  <div 
    class="sticky-header-cta"
    :class="{ 
      'header-visible': isVisible,
      'header-compact': isCompact,
      'header-institutional': audience === 'institutional'
    }"
  >
    <div class="header-container">
      <!-- Logo/Brand -->
      <div class="header-brand">
        <img src="/logo.svg" alt="Alumni Platform" class="brand-logo" />
        <span class="brand-name">AlumniConnect</span>
      </div>

      <!-- Progress indicator -->
      <div v-if="showProgress" class="header-progress">
        <div class="progress-track">
          <div 
            class="progress-indicator"
            :style="{ width: scrollProgress + '%' }"
          ></div>
        </div>
        <span class="progress-text">{{ Math.round(scrollProgress) }}% explored</span>
      </div>

      <!-- CTA Actions -->
      <div class="header-actions">
        <!-- Compact info -->
        <div v-if="isCompact" class="compact-info">
          <div class="compact-message">
            <span class="compact-title">{{ compactMessage.title }}</span>
            <span class="compact-subtitle">{{ compactMessage.subtitle }}</span>
          </div>
        </div>

        <!-- Secondary action -->
        <button 
          v-if="cta.secondaryAction && !isCompact"
          class="header-secondary-button"
          @click="handleSecondaryClick"
        >
          <component v-if="cta.secondaryAction.icon" :is="cta.secondaryAction.icon" class="secondary-icon" />
          {{ cta.secondaryAction.text }}
        </button>

        <!-- Primary CTA -->
        <button 
          class="header-primary-button"
          :class="{ 'button-loading': isLoading }"
          @click="handlePrimaryClick"
          :disabled="isLoading"
        >
          <span v-if="!isLoading" class="button-content">
            <component v-if="cta.primaryIcon" :is="cta.primaryIcon" class="button-icon" />
            {{ isCompact ? cta.compactText || cta.text : cta.text }}
          </span>
          <span v-else class="loading-content">
            <svg class="loading-spinner" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
        </button>

        <!-- Menu toggle for mobile -->
        <button 
          class="header-menu-toggle"
          @click="toggleMobileMenu"
          aria-label="Toggle menu"
        >
          <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="showMobileMenu" class="mobile-menu">
      <div class="mobile-menu-content">
        <!-- Mobile CTA options -->
        <div class="mobile-cta-options">
          <button 
            class="mobile-primary-button"
            @click="handlePrimaryClick"
            :disabled="isLoading"
          >
            <component v-if="cta.primaryIcon" :is="cta.primaryIcon" class="mobile-button-icon" />
            {{ cta.text }}
          </button>

          <button 
            v-if="cta.secondaryAction"
            class="mobile-secondary-button"
            @click="handleSecondaryClick"
          >
            <component v-if="cta.secondaryAction.icon" :is="cta.secondaryAction.icon" class="mobile-button-icon" />
            {{ cta.secondaryAction.text }}
          </button>
        </div>

        <!-- Mobile trust indicators -->
        <div v-if="cta.trustIndicators" class="mobile-trust-indicators">
          <div 
            v-for="indicator in cta.trustIndicators"
            :key="indicator.text"
            class="mobile-trust-item"
          >
            <component :is="indicator.icon" class="mobile-trust-icon" />
            <span class="mobile-trust-text">{{ indicator.text }}</span>
          </div>
        </div>

        <!-- Close menu -->
        <button 
          class="mobile-menu-close"
          @click="closeMobileMenu"
          aria-label="Close menu"
        >
          <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Notification badge -->
    <div v-if="cta.notification" class="header-notification">
      <div class="notification-badge">
        <span class="notification-text">{{ cta.notification.text }}</span>
        <button 
          class="notification-close"
          @click="dismissNotification"
          aria-label="Dismiss notification"
        >
          <svg class="notification-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import { useScrollTracking } from '@/composables/useScrollTracking'
import type { AudienceType, StickyHeaderCTAData, CTAClickEvent } from '@/types/homepage'

interface Props {
  cta: StickyHeaderCTAData
  audience: AudienceType
  isVisible?: boolean
  showProgress?: boolean
}

interface Emits {
  (e: 'click', event: CTAClickEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  isVisible: true,
  showProgress: false
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()
const { scrollDepth } = useScrollTracking()

// Reactive state
const isLoading = ref(false)
const isCompact = ref(false)
const showMobileMenu = ref(false)
const scrollProgress = ref(0)

// Computed properties
const compactMessage = computed(() => {
  if (props.audience === 'institutional') {
    return {
      title: 'Transform Alumni Engagement',
      subtitle: '500+ universities trust us'
    }
  }
  
  return {
    title: 'Advance Your Career',
    subtitle: '50,000+ successful alumni'
  }
})

// Methods
const handlePrimaryClick = async () => {
  if (isLoading.value) return
  
  isLoading.value = true
  
  try {
    const event: CTAClickEvent = {
      action: props.cta.action,
      section: 'sticky-header-cta',
      audience: props.audience,
      additionalData: {
        ctaId: props.cta.id,
        ctaType: 'sticky-header-primary',
        isCompact: isCompact.value,
        scrollProgress: scrollProgress.value
      }
    }
    
    trackEvent('sticky_header_cta_primary_click', event)
    
    // Simulate processing delay
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
    section: 'sticky-header-cta',
    audience: props.audience,
    additionalData: {
      ctaId: props.cta.id,
      ctaType: 'sticky-header-secondary',
      isCompact: isCompact.value
    }
  }
  
  trackEvent('sticky_header_cta_secondary_click', event)
  emit('click', event)
}

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
  
  trackEvent('sticky_header_mobile_menu_toggle', {
    audience: props.audience,
    opened: showMobileMenu.value
  })
}

const closeMobileMenu = () => {
  showMobileMenu.value = false
}

const dismissNotification = () => {
  trackEvent('sticky_header_notification_dismissed', {
    audience: props.audience,
    notificationText: props.cta.notification?.text
  })
  
  // Emit event to parent to handle notification dismissal
  emit('click', {
    action: 'dismiss-notification',
    section: 'sticky-header-cta',
    audience: props.audience
  })
}

const updateScrollProgress = () => {
  scrollProgress.value = scrollDepth.value
  
  // Switch to compact mode after scrolling past hero
  isCompact.value = scrollDepth.value > 25
}

// Lifecycle
onMounted(() => {
  trackEvent('sticky_header_cta_mounted', {
    audience: props.audience,
    ctaId: props.cta.id
  })
  
  // Listen for scroll events
  window.addEventListener('scroll', updateScrollProgress)
  
  // Close mobile menu when clicking outside
  const handleClickOutside = (e: Event) => {
    const target = e.target as Element
    if (showMobileMenu.value && !target.closest('.mobile-menu') && !target.closest('.header-menu-toggle')) {
      closeMobileMenu()
    }
  }
  
  document.addEventListener('click', handleClickOutside)
  
  return () => {
    document.removeEventListener('click', handleClickOutside)
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateScrollProgress)
})
</script>

<style scoped>
.sticky-header-cta {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  transform: translateY(-100%);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sticky-header-cta.header-visible {
  transform: translateY(0);
}

.header-institutional {
  background: rgba(124, 58, 237, 0.95);
  color: white;
  border-bottom-color: rgba(255, 255, 255, 0.2);
}

.header-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 24px;
  max-width: 1200px;
  margin: 0 auto;
  min-height: 64px;
  transition: all 0.3s ease;
}

.header-compact .header-container {
  min-height: 56px;
  padding: 8px 24px;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.brand-logo {
  height: 32px;
  width: auto;
}

.header-compact .brand-logo {
  height: 28px;
}

.brand-name {
  font-size: 18px;
  font-weight: 700;
  color: inherit;
}

.header-compact .brand-name {
  font-size: 16px;
}

.header-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  max-width: 200px;
  margin: 0 24px;
}

.progress-track {
  flex: 1;
  height: 4px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 2px;
  overflow: hidden;
}

.header-institutional .progress-track {
  background: rgba(255, 255, 255, 0.2);
}

.progress-indicator {
  height: 100%;
  background: #3b82f6;
  border-radius: 2px;
  transition: width 0.3s ease;
}

.header-institutional .progress-indicator {
  background: white;
}

.progress-text {
  font-size: 12px;
  font-weight: 500;
  opacity: 0.7;
  white-space: nowrap;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.compact-info {
  display: flex;
  align-items: center;
  margin-right: 16px;
}

.compact-message {
  display: flex;
  flex-direction: column;
  text-align: right;
}

.compact-title {
  font-size: 14px;
  font-weight: 600;
  line-height: 1.2;
}

.compact-subtitle {
  font-size: 12px;
  opacity: 0.7;
  line-height: 1.2;
}

.header-secondary-button {
  background: transparent;
  color: inherit;
  border: 1px solid currentColor;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  opacity: 0.8;
}

.header-secondary-button:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

.secondary-icon {
  width: 14px;
  height: 14px;
}

.header-primary-button {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 40px;
  min-width: 120px;
}

.header-institutional .header-primary-button {
  background: rgba(255, 255, 255, 0.15);
  color: inherit;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.header-primary-button:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.header-institutional .header-primary-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
}

.header-primary-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.button-content {
  display: flex;
  align-items: center;
  gap: 6px;
}

.button-icon {
  width: 16px;
  height: 16px;
}

.loading-content {
  display: flex;
  align-items: center;
  gap: 6px;
}

.loading-spinner {
  width: 14px;
  height: 14px;
  animation: spin 1s linear infinite;
}

.header-menu-toggle {
  display: none;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.header-menu-toggle:hover {
  background: rgba(255, 255, 255, 0.1);
}

.menu-icon {
  width: 20px;
  height: 20px;
}

.mobile-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: inherit;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  animation: slideDown 0.3s ease-out;
}

.header-institutional .mobile-menu {
  border-bottom-color: rgba(255, 255, 255, 0.2);
}

.mobile-menu-content {
  padding: 20px 24px;
  position: relative;
}

.mobile-cta-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.mobile-primary-button {
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px 20px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.header-institutional .mobile-primary-button {
  background: rgba(255, 255, 255, 0.15);
  color: inherit;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.mobile-secondary-button {
  background: transparent;
  color: inherit;
  border: 1px solid currentColor;
  border-radius: 8px;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.mobile-button-icon {
  width: 16px;
  height: 16px;
}

.mobile-trust-indicators {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
  padding-top: 16px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.header-institutional .mobile-trust-indicators {
  border-top-color: rgba(255, 255, 255, 0.2);
}

.mobile-trust-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  opacity: 0.7;
}

.mobile-trust-icon {
  width: 14px;
  height: 14px;
  color: #10b981;
}

.header-institutional .mobile-trust-icon {
  color: rgba(255, 255, 255, 0.8);
}

.mobile-menu-close {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 4px;
  padding: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.mobile-menu-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.close-icon {
  width: 16px;
  height: 16px;
  color: currentColor;
}

.header-notification {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  animation: slideDown 0.3s ease-out;
}

.notification-badge {
  background: #dc2626;
  color: white;
  border-radius: 8px;
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
  white-space: nowrap;
}

.notification-text {
  font-size: 12px;
  font-weight: 600;
}

.notification-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 2px;
  border-radius: 2px;
  transition: all 0.2s;
}

.notification-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.notification-close-icon {
  width: 12px;
  height: 12px;
}

/* Animations */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .header-menu-toggle {
    display: block;
  }
  
  .header-secondary-button {
    display: none;
  }
  
  .header-progress {
    display: none;
  }
  
  .compact-info {
    display: none;
  }
  
  .header-container {
    padding: 12px 16px;
  }
  
  .header-actions {
    gap: 12px;
  }
  
  .header-primary-button {
    min-width: 100px;
    padding: 8px 16px;
    font-size: 13px;
  }
}

@media (max-width: 640px) {
  .brand-name {
    display: none;
  }
  
  .header-primary-button {
    min-width: 80px;
    padding: 6px 12px;
    font-size: 12px;
  }
  
  .mobile-menu-content {
    padding: 16px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .sticky-header-cta,
  .header-primary-button,
  .mobile-menu {
    animation: none;
    transition: none;
  }
  
  .header-primary-button:hover {
    transform: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .sticky-header-cta {
    border-bottom-width: 2px;
  }
  
  .header-primary-button,
  .header-secondary-button {
    border-width: 2px;
  }
}

/* Safe area handling for devices with notches */
@supports (padding-left: env(safe-area-inset-left)) {
  .header-container {
    padding-left: max(24px, env(safe-area-inset-left));
    padding-right: max(24px, env(safe-area-inset-right));
  }
  
  @media (max-width: 768px) {
    .header-container {
      padding-left: max(16px, env(safe-area-inset-left));
      padding-right: max(16px, env(safe-area-inset-right));
    }
  }
}
</style>