<template>
  <component
    :is="linkElement"
    :href="isLink ? finalUrl : undefined"
    :target="config.openInNewTab ? '_blank' : undefined"
    :rel="linkRel"
    :download="config.downloadAttribute"
    :aria-label="config.accessibility?.ariaLabel || config.text"
    :aria-describedby="config.accessibility?.ariaDescribedBy"
    :role="config.accessibility?.role"
    :tabindex="config.accessibility?.tabIndex"
    :class="linkClasses"
    :style="linkStyles"
    @click="handleClick"
    @keydown="handleKeydown"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
    @focus="handleFocus"
    @blur="handleBlur"
  >
    <!-- Left icon -->
    <Icon
      v-if="config.icon && config.icon.position === 'left'"
      :name="config.icon.name"
      :size="config.icon.size || 'md'"
      :class="iconClasses"
      aria-hidden="true"
    />

    <!-- Link text -->
    <span class="cta-inline-link__text">
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

    <!-- External link indicator -->
    <Icon
      v-if="isExternalLink && config.style !== 'external'"
      name="external-link"
      size="sm"
      class="cta-inline-link__external-icon"
      aria-hidden="true"
    />

    <!-- Arrow for arrow style -->
    <Icon
      v-if="config.style === 'arrow'"
      name="arrow-right"
      size="sm"
      class="cta-inline-link__arrow"
      aria-hidden="true"
    />
  </component>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { CTAInlineLinkConfig, CTAComponentConfig } from '@/types/components'
import Icon from '@/components/Common/Icon.vue'

interface Props {
  config: CTAInlineLinkConfig
  theme?: string
  colorScheme?: string
  trackingEnabled?: boolean
  abTest?: CTAComponentConfig['abTest']
  context?: CTAComponentConfig['context']
}

interface Emits {
  (e: 'click', event: MouseEvent, config: CTAInlineLinkConfig): void
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

// Computed properties
const isLink = computed(() => !!props.config.url)
const linkElement = computed(() => isLink.value ? 'a' : 'span')

const isExternalLink = computed(() => {
  if (!props.config.url) return false
  
  try {
    const url = new URL(props.config.url, window.location.origin)
    return url.origin !== window.location.origin
  } catch {
    return false
  }
})

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

const linkRel = computed(() => {
  const rels = []
  
  if (props.config.openInNewTab) {
    rels.push('noopener', 'noreferrer')
  }
  
  if (isExternalLink.value) {
    rels.push('external')
  }
  
  return rels.length > 0 ? rels.join(' ') : undefined
})

const linkClasses = computed(() => [
  'cta-inline-link',
  `cta-inline-link--${props.config.style || 'default'}`,
  `cta-inline-link--${props.config.size || 'base'}`,
  `cta-inline-link--${props.config.weight || 'normal'}`,
  `cta-inline-link--theme-${props.theme}`,
  `cta-inline-link--color-${props.colorScheme}`,
  {
    'cta-inline-link--hovered': isHovered.value,
    'cta-inline-link--focused': isFocused.value,
    'cta-inline-link--external': isExternalLink.value,
    'cta-inline-link--has-icon': !!props.config.icon,
    [`cta-inline-link--hover-${props.config.animation?.hover}`]: props.config.animation?.hover && props.config.animation.hover !== 'none',
    [`cta-inline-link--transition-${props.config.animation?.transition}`]: props.config.animation?.transition
  }
])

const linkStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.config.color) {
    styles.color = props.config.color
  }
  
  return styles
})

const iconClasses = computed(() => [
  'cta-inline-link__icon',
  {
    'cta-inline-link__icon--left': props.config.icon?.position === 'left',
    'cta-inline-link__icon--right': props.config.icon?.position === 'right'
  }
])

// Event handlers
const handleClick = (event: MouseEvent) => {
  // Emit click event
  emit('click', event, props.config)

  // Handle conversion tracking
  if (props.config.conversionEvents) {
    emit('conversion', {
      link_text: props.config.text,
      link_style: props.config.style,
      link_url: props.config.url,
      is_external: isExternalLink.value,
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
</script>

<style scoped>
.cta-inline-link {
  @apply inline-flex items-center transition-colors duration-200 ease-in-out;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500;
  @apply cursor-pointer;
}

/* Sizes */
.cta-inline-link--xs {
  @apply text-xs;
}

.cta-inline-link--sm {
  @apply text-sm;
}

.cta-inline-link--base {
  @apply text-base;
}

.cta-inline-link--lg {
  @apply text-lg;
}

.cta-inline-link--xl {
  @apply text-xl;
}

/* Weights */
.cta-inline-link--normal {
  @apply font-normal;
}

.cta-inline-link--medium {
  @apply font-medium;
}

.cta-inline-link--semibold {
  @apply font-semibold;
}

.cta-inline-link--bold {
  @apply font-bold;
}

/* Styles */
.cta-inline-link--default {
  @apply text-blue-600;
}

.cta-inline-link--default:hover {
  @apply text-blue-800;
}

.cta-inline-link--underline {
  @apply text-blue-600 underline;
}

.cta-inline-link--underline:hover {
  @apply text-blue-800;
}

.cta-inline-link--button-like {
  @apply text-blue-600 px-2 py-1 rounded border border-blue-600;
}

.cta-inline-link--button-like:hover {
  @apply bg-blue-600 text-white;
}

.cta-inline-link--arrow {
  @apply text-blue-600;
}

.cta-inline-link--arrow:hover {
  @apply text-blue-800;
}

.cta-inline-link--arrow .cta-inline-link__arrow {
  @apply ml-1 transition-transform duration-200;
}

.cta-inline-link--arrow:hover .cta-inline-link__arrow {
  @apply transform translate-x-1;
}

.cta-inline-link--external {
  @apply text-blue-600;
}

.cta-inline-link--external:hover {
  @apply text-blue-800;
}

/* Icons */
.cta-inline-link__icon--left {
  @apply mr-1;
}

.cta-inline-link__icon--right {
  @apply ml-1;
}

.cta-inline-link__external-icon {
  @apply ml-1 opacity-75;
}

/* Hover animations */
.cta-inline-link--hover-underline:hover {
  @apply underline;
}

.cta-inline-link--hover-color-change:hover {
  @apply text-blue-800;
}

.cta-inline-link--hover-scale:hover {
  @apply transform scale-105;
}

/* Transition speeds */
.cta-inline-link--transition-fast {
  @apply transition-all duration-100;
}

.cta-inline-link--transition-normal {
  @apply transition-all duration-200;
}

.cta-inline-link--transition-slow {
  @apply transition-all duration-300;
}

/* Focus styles */
.cta-inline-link:focus {
  @apply ring-blue-500;
}

/* External link styling */
.cta-inline-link--external::after {
  content: '';
  @apply inline-block w-3 h-3 ml-1 opacity-75;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'/%3e%3c/svg%3e");
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .cta-inline-link {
    @apply underline;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .cta-inline-link {
    @apply transition-none;
  }
  
  .cta-inline-link--hover-scale:hover,
  .cta-inline-link--arrow:hover .cta-inline-link__arrow {
    @apply transform-none;
  }
}
</style>