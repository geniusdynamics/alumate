<template>
  <div 
    :class="baseClasses"
    :aria-label="config.accessibility?.ariaLabel"
    :aria-describedby="config.accessibility?.ariaDescribedBy"
  >
    <!-- CTA Button -->
    <CTAButton
      v-if="config.type === 'button' && config.buttonConfig"
      :config="config.buttonConfig"
      :theme="config.theme"
      :color-scheme="config.colorScheme"
      :tracking-enabled="config.trackingEnabled"
      :ab-test="config.abTest"
      :context="config.context"
      @click="handleCTAClick"
      @conversion="handleConversion"
    />

    <!-- CTA Banner -->
    <CTABanner
      v-else-if="config.type === 'banner' && config.bannerConfig"
      :config="config.bannerConfig"
      :theme="config.theme"
      :color-scheme="config.colorScheme"
      :tracking-enabled="config.trackingEnabled"
      :ab-test="config.abTest"
      :context="config.context"
      @click="handleCTAClick"
      @conversion="handleConversion"
    />

    <!-- CTA Inline Link -->
    <CTAInlineLink
      v-else-if="config.type === 'inline-link' && config.inlineLinkConfig"
      :config="config.inlineLinkConfig"
      :theme="config.theme"
      :color-scheme="config.colorScheme"
      :tracking-enabled="config.trackingEnabled"
      :ab-test="config.abTest"
      :context="config.context"
      @click="handleCTAClick"
      @conversion="handleConversion"
    />

    <!-- Fallback for invalid configuration -->
    <div v-else class="p-4 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-red-800 text-sm">
        Invalid CTA configuration. Please check the component setup.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { CTAComponentConfig } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'
import CTAButton from './CTAButton.vue'
import CTABanner from './CTABanner.vue'
import CTAInlineLink from './CTAInlineLink.vue'

interface Props {
  config: CTAComponentConfig
  sampleData?: boolean
}

interface Emits {
  (e: 'click', event: MouseEvent, config: any): void
  (e: 'conversion', data: any): void
  (e: 'impression'): void
}

const props = withDefaults(defineProps<Props>(), {
  sampleData: false
})

const emit = defineEmits<Emits>()

const { trackEvent, trackUserAction } = useAnalytics()

const baseClasses = computed(() => [
  'cta-component',
  `cta-component--${props.config.type}`,
  `cta-component--theme-${props.config.theme || 'default'}`,
  `cta-component--color-${props.config.colorScheme || 'default'}`,
  {
    'cta-component--reduced-motion': props.config.respectReducedMotion,
    'cta-component--high-contrast': props.config.highContrast,
    'cta-component--lazy-load': props.config.lazyLoad
  }
])

const handleCTAClick = (event: MouseEvent, ctaConfig: any) => {
  // Track the click event
  if (props.config.trackingEnabled) {
    trackUserAction('click', 'cta', `${props.config.type}-${ctaConfig.text || 'cta'}`)
    
    // Track conversion events if configured
    if (ctaConfig.conversionEvents) {
      ctaConfig.conversionEvents.forEach((conversionEvent: any) => {
        trackEvent(conversionEvent.eventName, {
          category: conversionEvent.category,
          action: conversionEvent.action,
          label: conversionEvent.label,
          value: conversionEvent.value,
          ...conversionEvent.customProperties,
          cta_type: props.config.type,
          cta_variant: props.config.abTest?.testId,
          context: props.config.context
        })
      })
    }
  }

  // Emit click event
  emit('click', event, ctaConfig)
}

const handleConversion = (data: any) => {
  // Track conversion
  if (props.config.trackingEnabled) {
    trackEvent('cta_conversion', {
      cta_type: props.config.type,
      conversion_goal: props.config.conversionGoal,
      cta_variant: props.config.abTest?.testId,
      context: props.config.context,
      ...data
    })
  }

  // Emit conversion event
  emit('conversion', data)
}

// Track impression when component mounts
onMounted(() => {
  if (props.config.trackingEnabled) {
    trackEvent('cta_impression', {
      cta_type: props.config.type,
      cta_variant: props.config.abTest?.testId,
      context: props.config.context
    })
    
    emit('impression')
  }
})
</script>

<style scoped>
.cta-component {
  @apply relative;
}

.cta-component--reduced-motion * {
  animation-duration: 0.01ms !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0.01ms !important;
}

.cta-component--high-contrast {
  @apply contrast-125;
}

.cta-component--lazy-load {
  @apply opacity-0 transition-opacity duration-300;
}

.cta-component--lazy-load.loaded {
  @apply opacity-100;
}
</style>