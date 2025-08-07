<template>
  <section class="hero-section">
    <div class="hero-content">
      <div class="hero-container">
        <div class="hero-text">
          <h1 class="hero-headline">{{ heroData?.headline || 'Welcome' }}</h1>
          <p class="hero-subtitle">{{ heroData?.subtitle || 'Connect with your alumni network' }}</p>
          
          <div class="hero-actions">
            <button 
              v-if="heroData?.primaryCTA"
              class="hero-cta-primary"
              @click="handleCTAClick(heroData.primaryCTA)"
            >
              {{ heroData.primaryCTA.text }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { AudienceType, CTAButton, CTAClickEvent } from '@/types/homepage'

interface Props {
  audience: AudienceType
  heroData?: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'cta-click': [event: CTAClickEvent]
}>()

const handleCTAClick = (cta: CTAButton) => {
  const event: CTAClickEvent = {
    action: cta.action,
    section: 'hero',
    audience: props.audience,
    additionalData: {
      text: cta.text,
      variant: cta.variant,
      trackingEvent: cta.trackingEvent || ''
    }
  }
  
  emit('cta-click', event)
}
</script>

<style scoped>
.hero-section {
  @apply relative min-h-screen flex items-center justify-center bg-gradient-to-r from-blue-600 to-purple-600;
}

.hero-content {
  @apply relative z-10 w-full;
}

.hero-container {
  @apply max-w-7xl mx-auto py-20 px-4 text-center text-white;
}

.hero-text {
  @apply mb-12;
}

.hero-headline {
  @apply text-4xl md:text-6xl mb-6 text-white font-bold;
}

.hero-subtitle {
  @apply text-lg md:text-xl mb-8 text-gray-100 max-w-3xl mx-auto;
}

.hero-actions {
  @apply flex justify-center;
}

.hero-cta-primary {
  @apply px-8 py-4 bg-white text-blue-600 rounded-lg font-medium hover:bg-gray-100 transition-colors;
}
</style>