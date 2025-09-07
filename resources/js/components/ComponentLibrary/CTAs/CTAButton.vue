<template>
  <component
    :is="buttonElement"
    :href="isLink ? finalUrl : undefined"
    :target="config.openInNewTab ? '_blank' : undefined"
    :rel="config.openInNewTab ? 'noopener noreferrer' : undefined"
    :disabled="config.disabled || config.loading"
    :aria-label="config.accessibility?.ariaLabel || config.text"
    :aria-describedby="config.accessibility?.ariaDescribedBy"
    :role="config.accessibility?.role"
    :tabindex="config.accessibility?.tabIndex"
    :class="buttonClasses"
    :style="buttonStyles"
    @click="handleClick"
    @keydown="handleKeydown"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
    @focus="handleFocus"
    @blur="handleBlur"
  >
    <!-- Loading state -->
    <div v-if="config.loading" class="cta-button__loading">
      <div 
        v-if="config.animation?.loading === 'spinner'"
        class="cta-button__spinner"
        aria-hidden="true"
      />
      <div 
        v-else-if="config.animation?.loading === 'dots'"
        class="cta-button__dots"
        aria-hidden="true"
      >
        <span></span>
        <span></span>
        <span></span>
      </div>
      <div 
        v-else
        class="cta-button__pulse"
        aria-hidden="true"
      />
    </div>

    <!-- Button content -->
    <div v-else class="cta-button__content">
      <!-- Left icon -->
      <Icon
        v-if="config.icon && config.icon.position === 'left'"
        :name="config.icon.name"
        :size="config.icon.size || 'md'"
        :class="iconClasses"
        aria-hidden="true"
      />

      <!-- Icon only -->
      <Icon
        v-if="config.icon && config.icon.position === 'only'"
        :name="config.icon.name"
        :size="config.icon.size || 'md'"
        :class="iconClasses"
        :aria-label="config.text"
      />

      <!-- Button text -->
      <span 
        v-if="config.icon?.position !== 'only'"
        class="cta-button__text"
      >
        {{ config.text }}
      </span>

      <!-- Right icon -->
      <Icon
        v-if="config.icon && config.icon.position === 'right'"
        :name="config.icon.name"
        :size="config.icon.size || 'md'"
        :class="iconClasses"
        aria-hidden="true"
      />
    </div>

    <!-- Ripple effect for click animation -->
    <div 
      v-if="showRipple && config.animation?.click === 'ripple'"
      class="cta-button__ripple"
      :style="rippleStyles"
    />
  </component>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import type { CTAButtonConfig, CTAComponentConfig } from '@/types/components'
import Icon from '@/components/Common/Icon.vue'

interface Props {
  config: CTAButtonConfig
  theme?: string
  colorScheme?: string
  trackingEnabled?: boolean
  abTest?: CTAComponentConfig['abTest']
  context?: CTAComponentConfig['context']
}

interface Emits {
  (e: 'click', event: MouseEvent, config: CTAButtonConfig): void
  (e: 'conversion', data: any): void
}

const props = withDefaults(defineProps<Props>(), {
  theme: 'default',
  colorScheme: 'default',
  trackingEnabled: true
})

const emit = defineEmits<Emits>()

// Reactive state
const isHovered = ref(false)
const isFocused = ref(false)
const showRipple = ref(false)
const rippleStyles = ref({})

// Computed properties
const isLink = computed(() => props.config.url && !props.config.disabled)
const buttonElement = computed(() => isLink.value ? 'a' : 'button')

const finalUrl = computed(() => {
  if (!props.config.url) return ''
  
  const url = new URL(props.config.url, window.location.origin)
  
  // Add tracking parameters
  if (props.config.trackingParams) {
    Object.entries(props.config.trackingParams).forEach(([key, value]) => {
      if (value) {
        url.searchParams.set(key, value)
      }
    })
  }
  
  // Add A/B test variant parameter
  if (props.abTest?.testId && props.config.abTestVariant) {
    url.searchParams.set('ab_test', props.abTest.testId)
    url.searchParams.set('variant', props.config.abTestVariant)
  }
  
  return url.toString()
})

const buttonClasses = computed(() => [
  'cta-button',
  `cta-button--${props.config.style}`,
  `cta-button--${props.config.size}`,
  `cta-button--theme-${props.theme}`,
  `cta-button--color-${props.colorScheme}`,
  {
    'cta-button--full-width': props.config.fullWidth,
    'cta-button--disabled': props.config.disabled,
    'cta-button--loading': props.config.loading,
    'cta-button--hovered': isHovered.value,
    'cta-button--focused': isFocused.value,
    'cta-button--icon-only': props.config.icon?.position === 'only',
    'cta-button--has-icon': !!props.config.icon,
    [`cta-button--hover-${props.config.animation?.hover}`]: props.config.animation?.hover && props.config.animation.hover !== 'none'
  }
])

const buttonStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.config.customColors) {
    const colors = props.config.customColors
    
    if (colors.background) {
      styles['--cta-bg'] = colors.background
    }
    if (colors.text) {
      styles['--cta-text'] = colors.text
    }
    if (colors.border) {
      styles['--cta-border'] = colors.border
    }
    if (colors.hover?.background) {
      styles['--cta-hover-bg'] = colors.hover.background
    }
    if (colors.hover?.text) {
      styles['--cta-hover-text'] = colors.hover.text
    }
    if (colors.hover?.border) {
      styles['--cta-hover-border'] = colors.hover.border
    }
  }
  
  return styles
})

const iconClasses = computed(() => [
  'cta-button__icon',
  {
    'cta-button__icon--left': props.config.icon?.position === 'left',
    'cta-button__icon--right': props.config.icon?.position === 'right',
    'cta-button__icon--only': props.config.icon?.position === 'only'
  }
])

// Event handlers
const handleClick = async (event: MouseEvent) => {
  if (props.config.disabled || props.config.loading) {
    event.preventDefault()
    return
  }

  // Handle ripple animation
  if (props.config.animation?.click === 'ripple') {
    await createRippleEffect(event)
  }

  // Handle bounce animation
  if (props.config.animation?.click === 'bounce') {
    // CSS animation will be handled by classes
  }

  // Emit click event
  emit('click', event, props.config)

  // Handle conversion tracking
  if (props.config.conversionEvents) {
    emit('conversion', {
      button_text: props.config.text,
      button_style: props.config.style,
      button_size: props.config.size,
      url: props.config.url,
      tracking_params: props.config.trackingParams
    })
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  // Handle keyboard shortcuts
  if (props.config.accessibility?.keyboardShortcut) {
    const shortcut = props.config.accessibility.keyboardShortcut.toLowerCase()
    const pressed = `${event.ctrlKey ? 'ctrl+' : ''}${event.altKey ? 'alt+' : ''}${event.shiftKey ? 'shift+' : ''}${event.key.toLowerCase()}`
    
    if (pressed === shortcut) {
      event.preventDefault()
      handleClick(event as any)
    }
  }

  // Handle Enter and Space for accessibility
  if (event.key === 'Enter' || event.key === ' ') {
    if (buttonElement.value === 'button') {
      event.preventDefault()
      handleClick(event as any)
    }
  }
}

const handleMouseEnter = () => {
  isHovered.value = true
}

const handleMouseLeave = () => {
  isHovered.value = false
}

const handleFocus = () => {
  isFocused.value = true
}

const handleBlur = () => {
  isFocused.value = false
}

const createRippleEffect = async (event: MouseEvent) => {
  const button = event.currentTarget as HTMLElement
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2

  rippleStyles.value = {
    width: `${size}px`,
    height: `${size}px`,
    left: `${x}px`,
    top: `${y}px`
  }

  showRipple.value = true

  await nextTick()

  setTimeout(() => {
    showRipple.value = false
  }, 600)
}
</script>

<style scoped>
.cta-button {
  @apply relative inline-flex items-center justify-center font-medium transition-all duration-200 ease-in-out;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
  @apply overflow-hidden;
  
  /* CSS Custom Properties for dynamic colors */
  background-color: var(--cta-bg, theme('colors.blue.600'));
  color: var(--cta-text, theme('colors.white'));
  border-color: var(--cta-border, transparent);
}

/* Sizes */
.cta-button--xs {
  @apply px-2.5 py-1.5 text-xs rounded;
}

.cta-button--sm {
  @apply px-3 py-2 text-sm rounded-md;
}

.cta-button--md {
  @apply px-4 py-2 text-sm rounded-md;
}

.cta-button--lg {
  @apply px-4 py-2 text-base rounded-md;
}

.cta-button--xl {
  @apply px-6 py-3 text-base rounded-lg;
}

/* Styles */
.cta-button--primary {
  @apply bg-blue-600 text-white border border-transparent;
}

.cta-button--primary:hover:not(:disabled) {
  background-color: var(--cta-hover-bg, theme('colors.blue.700'));
  color: var(--cta-hover-text, theme('colors.white'));
}

.cta-button--secondary {
  @apply bg-gray-600 text-white border border-transparent;
}

.cta-button--secondary:hover:not(:disabled) {
  @apply bg-gray-700;
}

.cta-button--outline {
  @apply bg-transparent text-blue-600 border border-blue-600;
}

.cta-button--outline:hover:not(:disabled) {
  @apply bg-blue-600 text-white;
}

.cta-button--ghost {
  @apply bg-transparent text-blue-600 border border-transparent;
}

.cta-button--ghost:hover:not(:disabled) {
  @apply bg-blue-50;
}

.cta-button--link {
  @apply bg-transparent text-blue-600 border border-transparent p-0 h-auto;
}

.cta-button--link:hover:not(:disabled) {
  @apply text-blue-800 underline;
}

/* Full width */
.cta-button--full-width {
  @apply w-full;
}

/* Icon positioning */
.cta-button__icon--left {
  @apply mr-2;
}

.cta-button__icon--right {
  @apply ml-2;
}

.cta-button--icon-only {
  @apply p-2;
}

/* Loading states */
.cta-button--loading {
  @apply cursor-wait;
}

.cta-button__loading {
  @apply flex items-center justify-center;
}

.cta-button__spinner {
  @apply w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin;
}

.cta-button__dots {
  @apply flex space-x-1;
}

.cta-button__dots span {
  @apply w-1 h-1 bg-current rounded-full animate-pulse;
}

.cta-button__dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.cta-button__dots span:nth-child(3) {
  animation-delay: 0.4s;
}

.cta-button__pulse {
  @apply w-4 h-4 bg-current rounded-full animate-pulse;
}

/* Hover animations */
.cta-button--hover-scale:hover:not(:disabled) {
  @apply transform scale-105;
}

.cta-button--hover-lift:hover:not(:disabled) {
  @apply transform -translate-y-0.5 shadow-lg;
}

.cta-button--hover-glow:hover:not(:disabled) {
  @apply shadow-lg;
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
}

.cta-button--hover-pulse:hover:not(:disabled) {
  @apply animate-pulse;
}

/* Click animations */
.cta-button__ripple {
  @apply absolute bg-white bg-opacity-30 rounded-full pointer-events-none;
  animation: ripple 0.6s linear;
  transform: scale(0);
}

@keyframes ripple {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

/* Focus styles */
.cta-button:focus {
  @apply ring-blue-500;
}

.cta-button--primary:focus {
  @apply ring-blue-500;
}

.cta-button--secondary:focus {
  @apply ring-gray-500;
}

.cta-button--outline:focus {
  @apply ring-blue-500;
}

.cta-button--ghost:focus {
  @apply ring-blue-500;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .cta-button {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .cta-button {
    @apply transition-none;
  }
  
  .cta-button__spinner {
    @apply animate-none;
  }
  
  .cta-button--hover-scale:hover:not(:disabled),
  .cta-button--hover-lift:hover:not(:disabled) {
    @apply transform-none;
  }
}
</style>