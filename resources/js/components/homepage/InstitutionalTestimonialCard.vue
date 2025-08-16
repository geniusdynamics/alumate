<template>
  <div class="institutional-testimonial-card bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
    <!-- Institution Header -->
    <div class="flex items-center mb-4">
      <div class="flex-shrink-0">
        <img 
          :src="testimonial.institution.logo" 
          :alt="`${testimonial.institution.name} logo`"
          class="w-16 h-16 object-contain rounded-lg border border-gray-200"
          loading="lazy"
        />
      </div>
      <div class="ml-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ testimonial.institution.name }}</h3>
        <p class="text-sm text-gray-600 capitalize">{{ testimonial.institution.type }}</p>
        <p class="text-xs text-gray-500">{{ formatAlumniCount(testimonial.institution.alumniCount) }} Alumni</p>
      </div>
    </div>

    <!-- Testimonial Quote -->
    <blockquote class="text-gray-700 mb-6 italic leading-relaxed">
      "{{ testimonial.quote }}"
    </blockquote>

    <!-- Administrator Profile -->
    <div class="flex items-center mb-4">
      <img 
        :src="testimonial.administrator.profileImage" 
        :alt="`${testimonial.administrator.name} profile`"
        class="w-12 h-12 rounded-full object-cover border-2 border-gray-200"
        loading="lazy"
      />
      <div class="ml-3">
        <p class="font-medium text-gray-900">{{ testimonial.administrator.name }}</p>
        <p class="text-sm text-gray-600">{{ testimonial.administrator.title }}</p>
        <p class="text-xs text-gray-500">{{ testimonial.administrator.experience }} years experience</p>
      </div>
    </div>

    <!-- Results Metrics -->
    <div class="grid grid-cols-2 gap-4 mb-4" v-if="testimonial.results.length > 0">
      <div 
        v-for="result in testimonial.results.slice(0, 4)" 
        :key="result.metric"
        class="text-center p-3 bg-gray-50 rounded-lg"
      >
        <div class="text-2xl font-bold text-blue-600">
          +{{ result.improvementPercentage }}%
        </div>
        <div class="text-xs text-gray-600 capitalize">
          {{ formatMetricLabel(result.metric) }}
        </div>
      </div>
    </div>

    <!-- Video Testimonial Button -->
    <div class="flex justify-between items-center">
      <button
        v-if="testimonial.videoTestimonial"
        @click="$emit('play-video', testimonial.videoTestimonial)"
        class="flex items-center text-blue-600 hover:text-blue-700 transition-colors duration-200"
        :aria-label="`Play video testimonial from ${testimonial.administrator.name}`"
      >
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path d="M8 5v10l8-5-8-5z"/>
        </svg>
        Watch Video
      </button>
      
      <!-- Institution Type Badge -->
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
        {{ testimonial.institution.type }}
      </span>
    </div>

    <!-- Verification Badge -->
    <div v-if="testimonial.results.some(r => r.verified)" class="mt-3 flex items-center text-green-600 text-xs">
      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
      </svg>
      Verified Results
    </div>
  </div>
</template>

<script setup lang="ts">
import type { InstitutionTestimonial } from '@/types/homepage'

interface Props {
  testimonial: InstitutionTestimonial
}

defineProps<Props>()

defineEmits<{
  'play-video': [videoUrl: string]
}>()

const formatAlumniCount = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`
  } else if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`
  }
  return count.toString()
}

const formatMetricLabel = (metric: string): string => {
  return metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>

<style scoped>
.institutional-testimonial-card {
  @apply border border-gray-200;
}

.institutional-testimonial-card:hover {
  @apply border-blue-200;
}
</style>