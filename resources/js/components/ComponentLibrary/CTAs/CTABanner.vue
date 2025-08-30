<template>
  <div 
    :class="bannerClasses"
    :style="bannerStyles"
    @click="handleBannerClick"
  >
    <!-- Background Image -->
    <ResponsiveImage
      v-if="config.backgroundImage"
      :asset="config.backgroundImage"
      :class="backgroundImageClasses"
      :lazy-load="true"
      :aria-hidden="true"
    />

    <!-- Overlay -->
    <div 
      v-if="config.overlay?.enabled"
      class="cta-banner__overlay"
      :style="overlayStyles"
      aria-hidden="true"
    />

    <!-- Content Container -->
    <div :class="contentContainerClasses">
      <div :class="contentClasses">
        <!-- Title -->
        <h2 
          v-if="config.title"
          :class="titleClasses"
        >
          {{ config.title }}
        </h2>

        <!-- Subtitle -->
        <h3 
          v-if="config.subtitle"
          :class="subtitleClasses"
        >
          {{ config.subtitle }}
        </h3>

        <!-- Description -->
        <p 
          v-if="config.description"
          :class="descriptionClasses"
        >
          {{ config.description }}
        </p>

        <!-- CTA Buttons -->
        <div 
          v-if="config.primaryCTA || config.secondaryCTA"
          :class="ctaContainerClasses"
        >
          <!-- Primary CTA -->
          <CTAButton
            v-if="config.primaryCTA"
            :config="config.primaryCTA"
            :theme="theme"
            :color-scheme="colorScheme"
            :tracking-enabled="trackingEnabled"
            :ab-test="abTest"
            :context="context"
            @click="handleCTAClick"
            @conversion="handleConversion"
          />

          <!-- Secondary CTA -->
          <CTAButton
            v-if="config.secondaryCTA"
            :config="config.secondaryCTA"
            :theme="theme"
            :color-scheme="colorScheme"
            :tracking-enabled="trackingEnabled"
            :ab-test="abTest"
            :context="context"
            @click="handleCTAClick"
            @conversion="handleConversion"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CTABannerConfig, CTAComponentConfig } from '@/types/components'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'
import CTAButton from './CTAButton.vue'

interface Props {
  config: CTABannerConfig
  theme?: string
  colorScheme?: string
  trackingEnabled?: boolean
  abTest?: CTAComponentConfig['abTest']
  context?: CTAComponentConfig['context']
}

interface Emits {
  (e: 'click', event: MouseEvent, config: CTABannerConfig): void
  (e: 'conversion', data: any): void
}

const props = withDefaults(defineProps<Props>(), {
  theme: 'default',
  colorScheme: 'default',
  trackingEnabled: true
})

const emit = defineEmits<Emits>()

// Computed classes and styles
const bannerClasses = computed(() => [
  'cta-banner',
  `cta-banner--${props.config.layout}`,
  `cta-banner--${props.config.height || 'medium'}`,
  `cta-banner--theme-${props.theme}`,
  `cta-banner--color-${props.colorScheme}`,
  {
    'cta-banner--has-background': !!props.config.backgroundImage,
    'cta-banner--parallax': props.config.parallax,
    'cta-banner--animate-scroll': props.config.animateOnScroll,
    'cta-banner--clickable': !!(props.config.primaryCTA || props.config.secondaryCTA)
  }
])

const bannerStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.config.backgroundColor) {
    styles.backgroundColor = props.config.backgroundColor
  }
  
  if (props.config.textColor) {
    styles.color = props.config.textColor
  }
  
  return styles
})

const backgroundImageClasses = computed(() => [
  'cta-banner__background',
  {
    'cta-banner__background--parallax': props.config.parallax
  }
])

const overlayStyles = computed(() => {
  if (!props.config.overlay?.enabled) return {}
  
  return {
    backgroundColor: props.config.overlay.color,
    opacity: props.config.overlay.opacity.toString()
  }
})

const contentContainerClasses = computed(() => [
  'cta-banner__container',
  `cta-banner__container--${props.config.padding || 'md'}`,
  {
    'cta-banner__container--mobile-stacked': props.config.mobileLayout === 'stacked',
    'cta-banner__container--mobile-overlay': props.config.mobileLayout === 'overlay',
    'cta-banner__container--mobile-hidden': props.config.mobileLayout === 'hidden'
  }
])

const contentClasses = computed(() => [
  'cta-banner__content',
  `cta-banner__content--${props.config.contentPosition || 'center'}`,
  `cta-banner__content--${props.config.textAlignment || 'center'}`
])

const titleClasses = computed(() => [
  'cta-banner__title',
  {
    'cta-banner__title--left': props.config.textAlignment === 'left',
    'cta-banner__title--center': props.config.textAlignment === 'center',
    'cta-banner__title--right': props.config.textAlignment === 'right'
  }
])

const subtitleClasses = computed(() => [
  'cta-banner__subtitle',
  {
    'cta-banner__subtitle--left': props.config.textAlignment === 'left',
    'cta-banner__subtitle--center': props.config.textAlignment === 'center',
    'cta-banner__subtitle--right': props.config.textAlignment === 'right'
  }
])

const descriptionClasses = computed(() => [
  'cta-banner__description',
  {
    'cta-banner__description--left': props.config.textAlignment === 'left',
    'cta-banner__description--center': props.config.textAlignment === 'center',
    'cta-banner__description--right': props.config.textAlignment === 'right'
  }
])

const ctaContainerClasses = computed(() => [
  'cta-banner__cta-container',
  {
    'cta-banner__cta-container--left': props.config.textAlignment === 'left',
    'cta-banner__cta-container--center': props.config.textAlignment === 'center',
    'cta-banner__cta-container--right': props.config.textAlignment === 'right'
  }
])

// Event handlers
const handleBannerClick = (event: MouseEvent) => {
  // Only handle clicks if there's a primary CTA and the click wasn't on a button
  if (props.config.primaryCTA && !(event.target as Element).closest('.cta-button')) {
    emit('click', event, props.config)
  }
}

const handleCTAClick = (event: MouseEvent, ctaConfig: any) => {
  emit('click', event, props.config)
}

const handleConversion = (data: any) => {
  emit('conversion', {
    ...data,
    banner_title: props.config.title,
    banner_layout: props.config.layout,
    banner_height: props.config.height
  })
}
</script>

<style scoped>
.cta-banner {
  @apply relative w-full overflow-hidden;
}

/* Heights */
.cta-banner--compact {
  @apply h-32 md:h-40;
}

.cta-banner--medium {
  @apply h-48 md:h-64;
}

.cta-banner--large {
  @apply h-64 md:h-80;
}

.cta-banner--full-screen {
  @apply h-screen;
}

/* Background */
.cta-banner__background {
  @apply absolute inset-0 w-full h-full object-cover;
}

.cta-banner__background--parallax {
  @apply transform-gpu;
  will-change: transform;
}

.cta-banner__overlay {
  @apply absolute inset-0 z-10;
}

/* Container */
.cta-banner__container {
  @apply relative z-20 h-full flex items-center justify-center;
}

.cta-banner__container--none {
  @apply p-0;
}

.cta-banner__container--sm {
  @apply p-4;
}

.cta-banner__container--md {
  @apply p-6 md:p-8;
}

.cta-banner__container--lg {
  @apply p-8 md:p-12;
}

.cta-banner__container--xl {
  @apply p-12 md:p-16;
}

/* Content positioning */
.cta-banner__content {
  @apply max-w-4xl mx-auto;
}

.cta-banner__content--top {
  @apply self-start;
}

.cta-banner__content--center {
  @apply self-center;
}

.cta-banner__content--bottom {
  @apply self-end;
}

/* Text alignment */
.cta-banner__content--left {
  @apply text-left;
}

.cta-banner__content--center {
  @apply text-center;
}

.cta-banner__content--right {
  @apply text-right;
}

/* Typography */
.cta-banner__title {
  @apply text-3xl md:text-4xl lg:text-5xl font-bold mb-4;
}

.cta-banner__subtitle {
  @apply text-xl md:text-2xl font-semibold mb-3;
}

.cta-banner__description {
  @apply text-lg md:text-xl mb-6 max-w-2xl;
}

.cta-banner__description--center {
  @apply mx-auto;
}

/* CTA Container */
.cta-banner__cta-container {
  @apply flex flex-col sm:flex-row gap-4;
}

.cta-banner__cta-container--left {
  @apply justify-start;
}

.cta-banner__cta-container--center {
  @apply justify-center;
}

.cta-banner__cta-container--right {
  @apply justify-end;
}

/* Layout variants */
.cta-banner--left-aligned .cta-banner__container {
  @apply justify-start;
}

.cta-banner--center-aligned .cta-banner__container {
  @apply justify-center;
}

.cta-banner--right-aligned .cta-banner__container {
  @apply justify-end;
}

.cta-banner--split .cta-banner__container {
  @apply grid grid-cols-1 md:grid-cols-2 gap-8 items-center;
}

/* Mobile layouts */
@media (max-width: 768px) {
  .cta-banner__container--mobile-stacked {
    @apply flex-col;
  }
  
  .cta-banner__container--mobile-hidden {
    @apply hidden;
  }
  
  .cta-banner__container--mobile-overlay .cta-banner__content {
    @apply absolute inset-0 flex flex-col justify-center items-center p-4;
  }
}

/* Animations */
.cta-banner--animate-scroll {
  @apply transform transition-transform duration-300 ease-out;
}

.cta-banner--parallax .cta-banner__background {
  @apply transition-transform duration-300 ease-out;
}

/* Clickable state */
.cta-banner--clickable {
  @apply cursor-pointer;
}

.cta-banner--clickable:hover .cta-banner__background {
  @apply scale-105;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .cta-banner--animate-scroll,
  .cta-banner--parallax .cta-banner__background,
  .cta-banner--clickable:hover .cta-banner__background {
    @apply transform-none transition-none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .cta-banner__overlay {
    @apply opacity-75;
  }
  
  .cta-banner__title,
  .cta-banner__subtitle,
  .cta-banner__description {
    @apply drop-shadow-lg;
  }
}
</style>