<template>
  <div 
    class="section-cta"
    :class="[
      `cta-${cta.placement}`,
      `cta-${cta.style || 'default'}`,
      { 'cta-sticky': cta.sticky }
    ]"
  >
    <div class="cta-container">
      <!-- Section-specific messaging -->
      <div class="cta-message" v-if="cta.contextualMessage">
        <div class="message-content">
          <h3 class="message-title">{{ cta.contextualMessage.title }}</h3>
          <p class="message-description">{{ cta.contextualMessage.description }}</p>
          
          <!-- Progress indicator -->
          <div v-if="cta.contextualMessage.progress" class="message-progress">
            <div class="progress-bar">
              <div 
                class="progress-fill"
                :style="{ width: cta.contextualMessage.progress.percentage + '%' }"
              ></div>
            </div>
            <span class="progress-text">{{ cta.contextualMessage.progress.text }}</span>
          </div>
        </div>
      </div>

      <!-- CTA Actions -->
      <div class="cta-actions">
        <!-- Primary CTA -->
        <button 
          class="primary-cta-button"
          :class="{ 'button-loading': isLoading }"
          @click="handlePrimaryClick"
          :disabled="isLoading"
        >
          <span v-if="!isLoading" class="button-content">
            <component v-if="cta.primaryIcon" :is="cta.primaryIcon" class="button-icon" />
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

        <!-- Secondary CTA -->
        <button 
          v-if="cta.secondaryAction"
          class="secondary-cta-button"
          @click="handleSecondaryClick"
        >
          <component v-if="cta.secondaryAction.icon" :is="cta.secondaryAction.icon" class="secondary-icon" />
          {{ cta.secondaryAction.text }}
        </button>

        <!-- Tertiary action (usually a link) -->
        <a 
          v-if="cta.tertiaryAction"
          :href="cta.tertiaryAction.href"
          class="tertiary-cta-link"
          @click="handleTertiaryClick"
        >
          {{ cta.tertiaryAction.text }}
          <svg class="tertiary-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </a>
      </div>

      <!-- Additional context -->
      <div v-if="cta.additionalContext" class="cta-context">
        <!-- Value proposition -->
        <div v-if="cta.additionalContext.valueProps" class="value-props">
          <div 
            v-for="prop in cta.additionalContext.valueProps"
            :key="prop.text"
            class="value-prop-item"
          >
            <component :is="prop.icon" class="value-prop-icon" />
            <span class="value-prop-text">{{ prop.text }}</span>
          </div>
        </div>

        <!-- Social proof snippet -->
        <div v-if="cta.additionalContext.socialProof" class="context-social-proof">
          <div class="social-proof-content">
            <div class="social-proof-avatars">
              <img 
                v-for="(avatar, index) in cta.additionalContext.socialProof.avatars.slice(0, 3)"
                :key="index"
                :src="avatar"
                :alt="`User ${index + 1}`"
                class="social-proof-avatar"
                :style="{ zIndex: 3 - index, marginLeft: index > 0 ? '-8px' : '0' }"
              />
            </div>
            <div class="social-proof-text">
              <span class="social-proof-count">{{ cta.additionalContext.socialProof.count }}</span>
              <span class="social-proof-label">{{ cta.additionalContext.socialProof.label }}</span>
            </div>
          </div>
        </div>

        <!-- Risk-free messaging -->
        <div v-if="cta.additionalContext.riskFree" class="risk-free-message">
          <svg class="risk-free-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <span class="risk-free-text">{{ cta.additionalContext.riskFree }}</span>
        </div>
      </div>

      <!-- Dismissible option -->
      <button 
        v-if="cta.dismissible"
        class="cta-dismiss-button"
        @click="handleDismiss"
        aria-label="Dismiss this call-to-action"
      >
        <svg class="dismiss-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Background elements -->
    <div v-if="cta.backgroundElements" class="cta-background-elements">
      <div 
        v-for="element in cta.backgroundElements"
        :key="element.id"
        class="background-element"
        :class="`element-${element.type}`"
        :style="element.style"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { AudienceType, SectionCTAData, CTAClickEvent } from '@/types/homepage'

interface Props {
  cta: SectionCTAData
  audience: AudienceType
}

interface Emits {
  (e: 'click', event: CTAClickEvent): void
  (e: 'dismiss'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const isLoading = ref(false)

// Methods
const handlePrimaryClick = async () => {
  if (isLoading.value) return
  
  isLoading.value = true
  
  try {
    const event: CTAClickEvent = {
      action: props.cta.action,
      section: props.cta.section || 'section-cta',
      audience: props.audience,
      additionalData: {
        ctaId: props.cta.id,
        ctaType: 'section-primary',
        placement: props.cta.placement,
        style: props.cta.style,
        hasContext: !!props.cta.additionalContext
      }
    }
    
    trackEvent('section_cta_primary_click', event)
    
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
    section: props.cta.section || 'section-cta',
    audience: props.audience,
    additionalData: {
      ctaId: props.cta.id,
      ctaType: 'section-secondary',
      placement: props.cta.placement
    }
  }
  
  trackEvent('section_cta_secondary_click', event)
  emit('click', event)
}

const handleTertiaryClick = (e: Event) => {
  if (!props.cta.tertiaryAction) return
  
  // Prevent default if we want to handle the navigation ourselves
  if (props.cta.tertiaryAction.preventDefault) {
    e.preventDefault()
  }
  
  const event: CTAClickEvent = {
    action: props.cta.tertiaryAction.action || 'navigate',
    section: props.cta.section || 'section-cta',
    audience: props.audience,
    additionalData: {
      ctaId: props.cta.id,
      ctaType: 'section-tertiary',
      href: props.cta.tertiaryAction.href
    }
  }
  
  trackEvent('section_cta_tertiary_click', event)
  
  if (props.cta.tertiaryAction.preventDefault) {
    emit('click', event)
  }
}

const handleDismiss = () => {
  trackEvent('section_cta_dismissed', {
    ctaId: props.cta.id,
    audience: props.audience,
    placement: props.cta.placement
  })
  
  emit('dismiss')
}

// Lifecycle
onMounted(() => {
  trackEvent('section_cta_impression', {
    ctaId: props.cta.id,
    audience: props.audience,
    placement: props.cta.placement,
    style: props.cta.style,
    section: props.cta.section
  })
})
</script>

<style scoped>
.section-cta {
  position: relative;
  margin: 32px 0;
  transition: all 0.3s ease;
}

.cta-sticky {
  position: sticky;
  top: 20px;
  z-index: 50;
}

.cta-container {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

/* Placement styles */
.cta-inline .cta-container {
  background: transparent;
  box-shadow: none;
  padding: 16px 0;
}

.cta-floating .cta-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  max-width: 320px;
  z-index: 100;
}

.cta-banner .cta-container {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-radius: 0;
  padding: 20px 24px;
}

.cta-sidebar .cta-container {
  position: sticky;
  top: 100px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

/* Style variants */
.cta-minimal .cta-container {
  background: transparent;
  box-shadow: none;
  border: 1px solid #e5e7eb;
  padding: 20px;
}

.cta-prominent .cta-container {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 32px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.cta-urgent .cta-container {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
  animation: pulse 2s infinite;
}

.cta-message {
  text-align: center;
  margin-bottom: 20px;
}

.message-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 8px;
  color: inherit;
}

.message-description {
  font-size: 16px;
  line-height: 1.5;
  opacity: 0.9;
  margin-bottom: 16px;
}

.message-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
}

.progress-bar {
  flex: 1;
  max-width: 200px;
  height: 6px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  font-weight: 600;
  opacity: 0.8;
}

.cta-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.primary-cta-button {
  background: #3b82f6;
  color: white;
  border: none;
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

.cta-banner .primary-cta-button,
.cta-prominent .primary-cta-button,
.cta-urgent .primary-cta-button {
  background: rgba(255, 255, 255, 0.15);
  color: inherit;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.primary-cta-button:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.cta-banner .primary-cta-button:hover:not(:disabled),
.cta-prominent .primary-cta-button:hover:not(:disabled),
.cta-urgent .primary-cta-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
}

.primary-cta-button:disabled {
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

.primary-cta-button:hover .button-arrow {
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

.secondary-cta-button {
  background: transparent;
  color: inherit;
  border: 1px solid currentColor;
  border-radius: 6px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  opacity: 0.8;
}

.secondary-cta-button:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

.secondary-icon {
  width: 16px;
  height: 16px;
}

.tertiary-cta-link {
  color: inherit;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
  opacity: 0.7;
  transition: all 0.2s;
}

.tertiary-cta-link:hover {
  opacity: 1;
  text-decoration: underline;
}

.tertiary-arrow {
  width: 14px;
  height: 14px;
  transition: transform 0.2s;
}

.tertiary-cta-link:hover .tertiary-arrow {
  transform: translateX(2px);
}

.cta-context {
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding-top: 16px;
  margin-top: 16px;
}

.cta-minimal .cta-context,
.cta-inline .cta-context {
  border-color: #e5e7eb;
}

.value-props {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.value-prop-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  opacity: 0.8;
}

.value-prop-icon {
  width: 14px;
  height: 14px;
  color: #10b981;
}

.context-social-proof {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.social-proof-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.social-proof-avatars {
  display: flex;
}

.social-proof-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid white;
  object-fit: cover;
}

.social-proof-text {
  font-size: 12px;
  opacity: 0.8;
}

.social-proof-count {
  font-weight: 600;
  margin-right: 4px;
}

.risk-free-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  opacity: 0.8;
}

.risk-free-icon {
  width: 14px;
  height: 14px;
  color: #10b981;
}

.cta-dismiss-button {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 4px;
  padding: 4px;
  cursor: pointer;
  transition: all 0.2s;
  opacity: 0.5;
}

.cta-dismiss-button:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.2);
}

.dismiss-icon {
  width: 16px;
  height: 16px;
  color: currentColor;
}

.cta-background-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  overflow: hidden;
}

.background-element {
  position: absolute;
  opacity: 0.1;
}

.element-circle {
  border-radius: 50%;
  background: currentColor;
}

.element-square {
  background: currentColor;
}

.element-triangle {
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-bottom: 20px solid currentColor;
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .cta-floating .cta-container {
    position: static;
    max-width: none;
    margin: 20px 0;
  }
  
  .cta-sidebar .cta-container {
    position: static;
  }
  
  .cta-actions {
    flex-direction: column;
  }
  
  .primary-cta-button {
    width: 100%;
    min-width: auto;
  }
  
  .value-props {
    flex-direction: column;
    gap: 12px;
  }
}

@media (max-width: 640px) {
  .section-cta {
    margin: 20px 0;
  }
  
  .cta-container {
    padding: 20px 16px;
  }
  
  .message-title {
    font-size: 18px;
  }
  
  .message-description {
    font-size: 14px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .section-cta,
  .primary-cta-button,
  .button-arrow,
  .tertiary-arrow {
    animation: none;
    transition: none;
  }
  
  .primary-cta-button:hover {
    transform: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .cta-container {
    border: 2px solid;
  }
  
  .primary-cta-button,
  .secondary-cta-button {
    border-width: 2px;
  }
}
</style>