<template>
  <section class="enterprise-testimonials">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          Trusted by Leading Institutions
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Universities, colleges, and organizations worldwide trust our platform to transform their alumni engagement
        </p>
      </div>

      <!-- Featured Testimonial -->
      <div v-if="featuredTestimonial" class="mb-16">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 text-white">
          <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-6">
              <img 
                :src="featuredTestimonial.institution.logo" 
                :alt="`${featuredTestimonial.institution.name} logo`"
                class="w-20 h-20 object-contain bg-white rounded-lg p-2 mr-6"
              />
              <div>
                <h3 class="text-2xl font-bold mb-2">{{ featuredTestimonial.institution.name }}</h3>
                <p class="text-blue-100 capitalize">{{ featuredTestimonial.institution.type }}</p>
                <p class="text-blue-200 text-sm">{{ formatAlumniCount(featuredTestimonial.institution.alumniCount) }} Alumni Network</p>
              </div>
            </div>
            
            <blockquote class="text-xl leading-relaxed mb-6 italic">
              "{{ featuredTestimonial.quote }}"
            </blockquote>
            
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <img 
                  :src="featuredTestimonial.administrator.profileImage" 
                  :alt="`${featuredTestimonial.administrator.name} profile`"
                  class="w-12 h-12 rounded-full object-cover border-2 border-blue-300 mr-4"
                />
                <div>
                  <p class="font-semibold">{{ featuredTestimonial.administrator.name }}</p>
                  <p class="text-blue-200 text-sm">{{ featuredTestimonial.administrator.title }}</p>
                </div>
              </div>
              
              <div class="flex items-center space-x-6">
                <div v-for="result in featuredTestimonial.results.slice(0, 3)" :key="result.metric" class="text-center">
                  <div class="text-2xl font-bold">+{{ result.improvementPercentage }}%</div>
                  <div class="text-blue-200 text-sm capitalize">{{ formatMetricLabel(result.metric) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <InstitutionalTestimonialCard
          v-for="testimonial in testimonials"
          :key="testimonial.id"
          :testimonial="testimonial"
          @play-video="openVideoModal"
        />
      </div>

      <!-- Case Studies Section -->
      <div v-if="caseStudies.length > 0" class="mb-12">
        <div class="text-center mb-8">
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Success Stories</h3>
          <p class="text-gray-600">Detailed case studies showing real results from our institutional partners</p>
        </div>
        
        <div class="grid lg:grid-cols-2 gap-8">
          <InstitutionalCaseStudy
            v-for="caseStudy in caseStudies"
            :key="caseStudy.id"
            :case-study="caseStudy"
            @request-demo="handleDemoRequest"
          />
        </div>
      </div>

      <!-- Call to Action -->
      <div class="text-center bg-gray-50 rounded-2xl p-8">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">
          Ready to Transform Your Alumni Engagement?
        </h3>
        <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
          Join hundreds of institutions that have increased alumni participation by an average of 300% with our platform.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="$emit('request-demo')"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            Request Demo
          </button>
          <button
            @click="$emit('download-case-studies')"
            class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          >
            Download Case Studies
          </button>
        </div>
      </div>
    </div>

    <!-- Video Modal -->
    <InstitutionalVideoModal
      :is-open="isVideoModalOpen"
      :video-url="currentVideoUrl"
      :testimonial="currentVideoTestimonial"
      @close="closeVideoModal"
      @request-demo="handleDemoRequest"
    />
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { InstitutionTestimonial, InstitutionalCaseStudy } from '@/types/homepage'
import InstitutionalTestimonialCard from './InstitutionalTestimonialCard.vue'
import InstitutionalCaseStudy from './InstitutionalCaseStudy.vue'
import InstitutionalVideoModal from './InstitutionalVideoModal.vue'

interface Props {
  testimonials: InstitutionTestimonial[]
  featuredTestimonial?: InstitutionTestimonial
  caseStudies: InstitutionalCaseStudy[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'request-demo': [data?: any]
  'download-case-studies': []
}>()

// Video modal state
const isVideoModalOpen = ref(false)
const currentVideoUrl = ref<string>()
const currentVideoTestimonial = ref<InstitutionTestimonial>()

const openVideoModal = (videoUrl: string) => {
  // Find the testimonial that matches this video
  const testimonial = props.testimonials.find(t => t.videoTestimonial === videoUrl) || 
                     (props.featuredTestimonial?.videoTestimonial === videoUrl ? props.featuredTestimonial : undefined)
  
  currentVideoUrl.value = videoUrl
  currentVideoTestimonial.value = testimonial
  isVideoModalOpen.value = true
}

const closeVideoModal = () => {
  isVideoModalOpen.value = false
  currentVideoUrl.value = undefined
  currentVideoTestimonial.value = undefined
}

const handleDemoRequest = (data?: any) => {
  emit('request-demo', data)
}

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
.enterprise-testimonials {
  @apply py-16 bg-white;
}
</style>