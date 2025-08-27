<template>
  <section
    :class="heroClasses"
    :style="heroStyles"
    role="banner"
    :aria-labelledby="headingId"
  >
    <!-- Background Media -->
    <div
      v-if="config.backgroundMedia"
      class="absolute inset-0 overflow-hidden"
      :class="{ 'z-0': true }"
    >
      <!-- Video Background -->
      <ResponsiveVideo
        v-if="config.backgroundMedia.type === 'video' && config.backgroundMedia.video"
        :src="config.backgroundMedia.video"
        :poster="config.backgroundMedia.video.poster"
        :autoplay="config.backgroundMedia.video.autoplay ?? true"
        :muted="config.backgroundMedia.video.muted ?? true"
        :loop="config.backgroundMedia.video.loop ?? true"
        :preload="config.backgroundMedia.video.preload || 'metadata'"
        :lazy-load="config.backgroundMedia.lazyLoad ?? config.lazyLoad"
        :priority="!config.lazyLoad"
        :disable-on-mobile="config.backgroundMedia.video.disableOnMobile"
        :mobile-quality="config.backgroundMedia.video.quality || 'medium'"
        :adaptive-bitrate="config.backgroundMedia.video.adaptiveBitrate ?? true"
        :show-bandwidth-warning="true"
        object-fit="cover"
        class="w-full h-full"
        :poster-alt="config.backgroundMedia.video.alt || 'Background video'"
        @loadstart="handleVideoLoadStart"
        @canplay="handleVideoCanPlay"
        @error="handleVideoError"
      />

      <!-- Image Background -->
      <ResponsiveImage
        v-else-if="config.backgroundMedia.type === 'image' && config.backgroundMedia.image"
        :src="config.backgroundMedia.image"
        :alt="config.backgroundMedia.image.alt || ''"
        :responsive="true"
        :lazy-load="config.backgroundMedia.lazyLoad ?? config.lazyLoad"
        :priority="!config.lazyLoad"
        :preload="config.backgroundMedia.preload || config.preloadImages"
        :formats="['webp', 'avif', 'jpeg']"
        :quality="85"
        object-fit="cover"
        class="w-full h-full"
        :fallback-src="config.backgroundMedia.fallback?.image?.url"
        @load="handleImageLoad"
        @error="handleImageError"
      />

      <!-- Gradient Background -->
      <div
        v-else-if="config.backgroundMedia.type === 'gradient' && config.backgroundMedia.gradient"
        class="w-full h-full"
        :style="gradientStyles"
      />

      <!-- Overlay -->
      <div
        v-if="config.backgroundMedia.overlay"
        class="absolute inset-0"
        :style="overlayStyles"
      />
    </div>

    <!-- Content Container -->
    <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 h-full">
      <div :class="contentContainerClasses">
        <div :class="contentWrapperClasses">
          <!-- Heading -->
          <component
            :is="`h${config.headingLevel}`"
            :id="headingId"
            :class="headlineClasses"
            v-html="config.headline"
          />

          <!-- Subheading -->
          <p
            v-if="config.subheading"
            :class="subheadingClasses"
            v-html="config.subheading"
          />

          <!-- Description -->
          <p
            v-if="config.description"
            :class="descriptionClasses"
            v-html="config.description"
          />

          <!-- Statistics -->
          <div
            v-if="config.statistics && config.statistics.length > 0"
            class="mt-8 sm:mt-12"
          >
            <StatisticsDisplay
              :statistics="config.statistics"
              :layout="getStatisticsLayout()"
              :size="getStatisticsSize()"
              :animation-duration="2000"
              :animation-easing="'ease-out'"
              :stagger-delay="200"
              :locale="'en-US'"
              :refresh-interval="300000"
              :retry-attempts="3"
              :respect-reduced-motion="true"
              :show-last-updated="false"
              :show-actions="false"
              :show-refresh="false"
              :enable-real-time="true"
              :cache-enabled="true"
              @animation-start="handleStatisticAnimationStart"
              @animation-complete="handleStatisticAnimationComplete"
              @data-loaded="handleStatisticDataLoaded"
              @data-error="handleStatisticDataError"
              @all-animations-complete="handleAllStatisticAnimationsComplete"
            />
          </div>

          <!-- CTA Buttons -->
          <div
            v-if="config.ctaButtons && config.ctaButtons.length > 0"
            class="mt-8 sm:mt-12"
          >
            <div :class="ctaContainerClasses">
              <component
                v-for="cta in config.ctaButtons"
                :key="cta.id"
                :is="cta.url.startsWith('http') ? 'a' : 'Link'"
                :href="cta.url"
                :class="getCtaButtonClasses(cta)"
                :aria-label="cta.text"
                @click="handleCtaClick(cta)"
              >
                <Icon
                  v-if="cta.icon"
                  :name="cta.icon"
                  class="mr-2 h-5 w-5"
                  aria-hidden="true"
                />
                {{ cta.text }}
              </component>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="absolute inset-0 bg-gray-100 dark:bg-gray-900 flex items-center justify-center z-20"
      role="status"
      aria-label="Loading hero content"
    >
      <div class="animate-pulse space-y-4 w-full max-w-2xl px-4">
        <div class="h-8 bg-gray-300 dark:bg-gray-700 rounded w-3/4 mx-auto"></div>
        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/2 mx-auto"></div>
        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-2/3 mx-auto"></div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { HeroComponentConfig, CTAButton, StatisticCounter } from '@/types/components'
import { getVariantStyleClasses, getBackgroundGradient } from '@/utils/variantStyling'
import StatisticsDisplay from './StatisticsDisplay.vue'
import ResponsiveImage from '../ResponsiveImage.vue'
import ResponsiveVideo from '../ResponsiveVideo.vue'
import Icon from '@/components/Icon.vue'

interface Props {
  config: HeroComponentConfig
  sampleData?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  sampleData: false
})

// Refs
const videoRef = ref<HTMLVideoElement>()
const imageLoaded = ref(false)
const isLoading = ref(true)

// Computed properties
const headingId = computed(() => `hero-heading-${Math.random().toString(36).substr(2, 9)}`)

const variantStyles = computed(() => getVariantStyleClasses(props.config))

const heroClasses = computed(() => [
  'relative min-h-screen flex items-center',
  {
    'text-left': props.config.textAlignment === 'left',
    'text-center': props.config.textAlignment === 'center',
    'text-right': props.config.textAlignment === 'right',
  },
  {
    'justify-start': props.config.contentPosition === 'top',
    'justify-center': props.config.contentPosition === 'center',
    'justify-end': props.config.contentPosition === 'bottom',
  },
  ...variantStyles.value.hero
])

const heroStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (!props.config.backgroundMedia) {
    // Use variant-specific background gradient
    const gradientStyles = getBackgroundGradient(
      props.config.variantStyling?.colorScheme,
      props.config.audienceType
    )
    Object.assign(styles, gradientStyles)
  }
  
  return styles
})

const gradientStyles = computed(() => {
  if (!props.config.backgroundMedia?.gradient) return {}
  
  const { gradient } = props.config.backgroundMedia
  const colorStops = gradient.colors
    .map(c => `${c.color} ${c.stop}%`)
    .join(', ')
  
  if (gradient.type === 'radial') {
    return {
      background: `radial-gradient(circle, ${colorStops})`
    }
  }
  
  return {
    background: `linear-gradient(${gradient.direction || '135deg'}, ${colorStops})`
  }
})

const overlayStyles = computed(() => {
  if (!props.config.backgroundMedia?.overlay) return {}
  
  const { overlay } = props.config.backgroundMedia
  return {
    backgroundColor: overlay.color,
    opacity: overlay.opacity.toString()
  }
})

const contentContainerClasses = computed(() => [
  'flex h-full',
  {
    'items-start pt-20': props.config.contentPosition === 'top',
    'items-center': props.config.contentPosition === 'center',
    'items-end pb-20': props.config.contentPosition === 'bottom',
  },
  {
    'justify-start': props.config.layout === 'left-aligned',
    'justify-center': props.config.layout === 'centered',
    'justify-end': props.config.layout === 'right-aligned',
  }
])

const contentWrapperClasses = computed(() => [
  'max-w-4xl',
  {
    'w-full': props.config.layout === 'centered',
    'w-full lg:w-1/2': props.config.layout === 'split',
  }
])

const headlineClasses = computed(() => [
  'leading-tight mb-4 sm:mb-6',
  'drop-shadow-lg',
  {
    'animate-fade-in-up': props.config.animations?.enabled && props.config.animations.entrance === 'slide',
    'animate-fade-in': props.config.animations?.enabled && props.config.animations.entrance === 'fade',
    'animate-zoom-in': props.config.animations?.enabled && props.config.animations.entrance === 'zoom',
  },
  ...variantStyles.value.headline
])

const subheadingClasses = computed(() => [
  'drop-shadow',
  {
    'animate-fade-in-up': props.config.animations?.enabled,
  },
  ...variantStyles.value.subheading
])

const descriptionClasses = computed(() => [
  'drop-shadow',
  {
    'mx-auto': props.config.textAlignment === 'center',
    'animate-fade-in-up': props.config.animations?.enabled,
  },
  ...variantStyles.value.description
])



const ctaContainerClasses = computed(() => [
  'flex flex-wrap gap-4',
  {
    'justify-start': props.config.textAlignment === 'left',
    'justify-center': props.config.textAlignment === 'center',
    'justify-end': props.config.textAlignment === 'right',
  },
  'sm:flex-row flex-col sm:gap-6'
])

// Methods
const getCtaButtonClasses = (cta: CTAButton) => {
  const baseClasses = [
    'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent',
    'touch-manipulation', // Optimize for touch devices
    {
      'px-4 py-2 text-sm': cta.size === 'sm',
      'px-6 py-3 text-base': cta.size === 'md',
      'px-8 py-4 text-lg': cta.size === 'lg',
    }
  ]
  
  // Use variant-specific styling for primary buttons, fallback for others
  if (cta.style === 'primary') {
    return [
      ...baseClasses,
      ...variantStyles.value.cta
    ]
  }
  
  return [
    ...baseClasses,
    {
      'bg-transparent text-white border-2 border-white hover:bg-white hover:text-gray-900 focus:ring-white': cta.style === 'outline',
      'bg-white/20 text-white backdrop-blur-sm hover:bg-white/30 focus:ring-white': cta.style === 'secondary',
      'text-white hover:text-white/80 focus:ring-white': cta.style === 'ghost',
    }
  ]
}

const getStatisticsLayout = (): 'horizontal' | 'vertical' | 'grid' | 'compact' => {
  // Determine layout based on screen size and number of statistics
  const statCount = props.config.statistics?.length || 0
  
  if (statCount <= 2) return 'horizontal'
  if (statCount <= 4) return 'horizontal'
  return 'grid'
}

const getStatisticsSize = (): 'sm' | 'md' | 'lg' => {
  // Determine size based on hero layout and content
  if (props.config.layout === 'compact') return 'sm'
  if (props.config.layout === 'split') return 'md'
  return 'lg'
}

const handleCtaClick = (cta: CTAButton) => {
  // Track CTA click for analytics
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', 'cta_click', {
      cta_text: cta.text,
      cta_url: cta.url,
      component_type: 'hero',
      audience_type: props.config.audienceType,
      ab_test_variant: cta.abTestVariant,
      variant_styling: props.config.variantStyling?.colorScheme,
      ...cta.trackingParams
    })
  }
  
  // Track A/B test conversion if variant is present
  if (cta.abTestVariant && props.config.abTest?.testId) {
    // This would typically be handled by the parent component
    // but we can emit an event for it to handle
    const event = new CustomEvent('ab-test-conversion', {
      detail: {
        testId: props.config.abTest.testId,
        variant: cta.abTestVariant,
        conversionType: 'cta_click',
        ctaId: cta.id
      }
    })
    window.dispatchEvent(event)
  }
}

const handleVideoLoadStart = () => {
  isLoading.value = true
}

const handleVideoCanPlay = () => {
  isLoading.value = false
}

const handleVideoError = (event: Event) => {
  console.error('Video failed to load:', event)
  isLoading.value = false
}

const handleImageLoad = () => {
  imageLoaded.value = true
  isLoading.value = false
}

const handleImageError = (event: Event) => {
  console.error('Image failed to load:', event)
  isLoading.value = false
}

// Statistics event handlers
const handleStatisticAnimationStart = (statisticId: string) => {
  // Track animation start for analytics
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', 'statistic_animation_start', {
      statistic_id: statisticId,
      component_type: 'hero',
      audience_type: props.config.audienceType
    })
  }
}

const handleStatisticAnimationComplete = (statisticId: string) => {
  // Track animation completion for analytics
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', 'statistic_animation_complete', {
      statistic_id: statisticId,
      component_type: 'hero',
      audience_type: props.config.audienceType
    })
  }
}

const handleStatisticDataLoaded = (statisticId: string, value: number) => {
  // Track successful data loading
  console.log(`Statistic ${statisticId} loaded with value:`, value)
}

const handleStatisticDataError = (statisticId: string, error: Error) => {
  // Track data loading errors
  console.error(`Failed to load statistic ${statisticId}:`, error)
  
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', 'statistic_load_error', {
      statistic_id: statisticId,
      error_message: error.message,
      component_type: 'hero',
      audience_type: props.config.audienceType
    })
  }
}

const handleAllStatisticAnimationsComplete = () => {
  // All statistics have finished animating
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', 'all_statistics_animated', {
      component_type: 'hero',
      audience_type: props.config.audienceType,
      statistics_count: props.config.statistics?.length || 0
    })
  }
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  
  // If no background media, hide loading immediately
  if (!props.config.backgroundMedia) {
    isLoading.value = false
  }
  
  // Preload images if enabled
  if (props.config.preloadImages && props.config.backgroundMedia?.image) {
    const img = new Image()
    img.onload = () => {
      imageLoaded.value = true
      isLoading.value = false
    }
    img.onerror = handleImageError
    img.src = props.config.backgroundMedia.image.url
  }
  
  // Handle reduced motion preference
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    // Disable animations for users who prefer reduced motion
    if (props.config.animations) {
      props.config.animations.enabled = false
    }
    
    // Pause video if autoplay is enabled
    if (videoRef.value && props.config.backgroundMedia?.video?.autoplay) {
      videoRef.value.pause()
    }
  }
})
</script>

<style scoped>
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes zoom-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out forwards;
}

.animate-fade-in {
  animation: fade-in 0.6s ease-out forwards;
}

.animate-zoom-in {
  animation: zoom-in 0.6s ease-out forwards;
}

/* Ensure proper contrast for accessibility */
.drop-shadow-lg {
  filter: drop-shadow(0 10px 8px rgb(0 0 0 / 0.04)) drop-shadow(0 4px 3px rgb(0 0 0 / 0.1));
}

.drop-shadow {
  filter: drop-shadow(0 1px 2px rgb(0 0 0 / 0.1)) drop-shadow(0 1px 1px rgb(0 0 0 / 0.06));
}

/* Responsive video handling */
video {
  object-position: center;
}

@media (max-width: 768px) {
  video {
    object-position: center top;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .text-white\/90 {
    color: white;
  }
  
  .text-white\/80 {
    color: white;
  }
}
</style>