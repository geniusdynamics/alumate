<template>
  <section class="social-proof-section" ref="sectionRef">
    <div class="container mx-auto px-4">
      <!-- Platform Statistics -->
      <PlatformStatistics
        :audience="audience"
        :title="statisticsTitle"
        :subtitle="statisticsSubtitle"
        :auto-fetch="true"
        ref="platformStatisticsRef"
      />

      <!-- Testimonials Carousel -->
      <div class="mt-16">
        <TestimonialsCarousel
          :audience="audience"
          :title="testimonialsTitle"
          :subtitle="testimonialsSubtitle"
          :show-filters="true"
          :show-navigation="true"
          :show-pagination="true"
          :auto-play="false"
          :slides-per-view="3"
          ref="testimonialsCarouselRef"
        />
      </div>

      <!-- Trust Badges -->
      <div class="mt-16">
        <TrustBadges
          :audience="audience"
          :title="trustBadgesTitle"
          :subtitle="trustBadgesSubtitle"
          :show-company-logos="true"
          :company-logos-title="companyLogosTitle"
          :company-logos-subtitle="companyLogosSubtitle"
          :auto-scroll-logos="true"
          :auto-scroll-interval="3000"
          ref="trustBadgesRef"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import PlatformStatistics from './PlatformStatistics.vue'
import TestimonialsCarousel from './TestimonialsCarousel.vue'
import TrustBadges from './TrustBadges.vue'
import type { AudienceType } from '@/types/homepage'

interface Props {
  audience: AudienceType
}

const props = defineProps<Props>()

// Template refs
const sectionRef = ref<HTMLElement>()
const platformStatisticsRef = ref<InstanceType<typeof PlatformStatistics>>()
const testimonialsCarouselRef = ref<InstanceType<typeof TestimonialsCarousel>>()
const trustBadgesRef = ref<InstanceType<typeof TrustBadges>>()

// Computed properties for audience-specific content
const statisticsTitle = computed(() => {
  return props.audience === 'institutional' 
    ? 'Trusted by Leading Institutions'
    : 'Trusted by Alumni Worldwide'
})

const statisticsSubtitle = computed(() => {
  return props.audience === 'institutional'
    ? 'See how universities and organizations are transforming alumni engagement'
    : 'Join thousands of professionals advancing their careers through meaningful connections'
})

const testimonialsTitle = computed(() => {
  return props.audience === 'institutional'
    ? 'What Institutions Say'
    : 'What Our Alumni Say'
})

const testimonialsSubtitle = computed(() => {
  return props.audience === 'institutional'
    ? 'Hear from administrators who have transformed their alumni communities'
    : 'Hear from professionals who have transformed their careers through our platform'
})

const trustBadgesTitle = computed(() => {
  return props.audience === 'institutional'
    ? 'Enterprise Security & Compliance'
    : 'Trusted & Secure'
})

const trustBadgesSubtitle = computed(() => {
  return props.audience === 'institutional'
    ? 'Meeting the highest standards for institutional data protection and compliance'
    : 'Your data is protected by industry-leading security standards'
})

const companyLogosTitle = computed(() => {
  return props.audience === 'institutional'
    ? 'Trusted by Leading Organizations'
    : 'Alumni Work At'
})

const companyLogosSubtitle = computed(() => {
  return props.audience === 'institutional'
    ? 'Join institutions that trust us with their alumni communities'
    : 'Join professionals from leading companies worldwide'
})

// Methods
const refreshStatistics = async (): Promise<void> => {
  if (platformStatisticsRef.value) {
    await platformStatisticsRef.value.refresh()
  }
}

const refreshTestimonials = async (): Promise<void> => {
  if (testimonialsCarouselRef.value) {
    await testimonialsCarouselRef.value.refresh()
  }
}

const refreshTrustBadges = async (): Promise<void> => {
  if (trustBadgesRef.value) {
    await trustBadgesRef.value.refresh()
  }
}

const refreshAll = async (): Promise<void> => {
  await Promise.all([
    refreshStatistics(),
    refreshTestimonials(),
    refreshTrustBadges()
  ])
}

// Expose methods for parent components
defineExpose({
  refreshStatistics,
  refreshTestimonials,
  refreshTrustBadges,
  refreshAll,
  platformStatistics: platformStatisticsRef,
  testimonialsCarousel: testimonialsCarouselRef,
  trustBadges: trustBadgesRef
})
</script>

<style scoped>
.social-proof-section {
  @apply py-16 bg-gray-50;
}

/* Ensure smooth transitions */
.social-proof-section * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .social-proof-section {
    @apply py-12;
  }
}
</style>