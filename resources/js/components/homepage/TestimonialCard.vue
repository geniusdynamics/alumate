<template>
  <div class="testimonial-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 h-full flex flex-col">
    <!-- Quote -->
    <div class="flex-grow mb-6">
      <div class="text-blue-600 mb-3">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
          <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"/>
        </svg>
      </div>
      
      <blockquote class="text-gray-700 text-lg leading-relaxed mb-4">
        "{{ testimonial.quote }}"
      </blockquote>
    </div>

    <!-- Author/Institution Info -->
    <div class="border-t pt-4">
      <!-- Individual Alumni -->
      <div v-if="audience === 'individual' && isIndividualTestimonial(testimonial)" class="flex items-start space-x-4">
        <img
          :src="testimonial.author.profileImage"
          :alt="`${testimonial.author.name} profile picture`"
          class="w-12 h-12 rounded-full object-cover flex-shrink-0"
          @error="handleImageError"
        />
        
        <div class="flex-grow min-w-0">
          <div class="flex items-center justify-between mb-1">
            <h4 class="font-semibold text-gray-900 truncate">
              {{ testimonial.author.name }}
            </h4>
            
            <a
              v-if="testimonial.author.linkedinUrl"
              :href="testimonial.author.linkedinUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="text-blue-600 hover:text-blue-700 flex-shrink-0 ml-2"
              :aria-label="`View ${testimonial.author.name}'s LinkedIn profile`"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
          </div>
          
          <p class="text-sm text-gray-600 mb-1">
            {{ testimonial.author.currentRole }} at {{ testimonial.author.currentCompany }}
          </p>
          
          <p class="text-xs text-gray-500">
            Class of {{ testimonial.author.graduationYear }} • {{ testimonial.author.degree }}
          </p>
          
          <!-- Career Stage Badge -->
          <span :class="[
            'inline-block px-2 py-1 text-xs font-medium rounded-full mt-2',
            getCareerStageBadgeClass(testimonial.author.careerStage)
          ]">
            {{ formatCareerStage(testimonial.author.careerStage) }}
          </span>
        </div>
      </div>

      <!-- Institutional -->
      <div v-else-if="audience === 'institutional' && isInstitutionalTestimonial(testimonial)" class="space-y-4">
        <!-- Institution Info -->
        <div class="flex items-center space-x-4">
          <img
            :src="testimonial.institution.logo"
            :alt="`${testimonial.institution.name} logo`"
            class="w-12 h-12 object-contain flex-shrink-0"
            @error="handleImageError"
          />
          
          <div class="flex-grow min-w-0">
            <h4 class="font-semibold text-gray-900 truncate">
              {{ testimonial.institution.name }}
            </h4>
            <p class="text-sm text-gray-600">
              {{ formatInstitutionType(testimonial.institution.type) }} • {{ testimonial.institution.alumniCount?.toLocaleString() }} Alumni
            </p>
          </div>
        </div>

        <!-- Administrator Info -->
        <div class="flex items-center space-x-3 pl-4 border-l-2 border-gray-100">
          <img
            :src="testimonial.administrator.profileImage"
            :alt="`${testimonial.administrator.name} profile picture`"
            class="w-10 h-10 rounded-full object-cover flex-shrink-0"
            @error="handleImageError"
          />
          
          <div class="flex-grow min-w-0">
            <p class="font-medium text-gray-900 text-sm truncate">
              {{ testimonial.administrator.name }}
            </p>
            <p class="text-xs text-gray-600 truncate">
              {{ testimonial.administrator.title }}
            </p>
          </div>
        </div>

        <!-- Results Metrics -->
        <div v-if="testimonial.results && testimonial.results.length > 0" class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
          <div
            v-for="result in testimonial.results.slice(0, 2)"
            :key="result.metric"
            class="text-center"
          >
            <div class="text-lg font-bold text-blue-600">
              +{{ result.improvementPercentage }}%
            </div>
            <div class="text-xs text-gray-600 capitalize">
              {{ result.metric.replace('_', ' ') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Metrics for Individual -->
    <div v-if="audience === 'individual' && isIndividualTestimonial(testimonial) && testimonial.metrics && testimonial.metrics.length > 0" class="mt-4 pt-4 border-t border-gray-100">
      <div class="grid grid-cols-2 gap-3">
        <div
          v-for="metric in testimonial.metrics.slice(0, 2)"
          :key="metric.type"
          class="text-center"
        >
          <div class="text-lg font-bold text-green-600">
            {{ formatMetricValue(metric) }}
          </div>
          <div class="text-xs text-gray-600 capitalize">
            {{ metric.type.replace('_', ' ') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Video Testimonial Button -->
    <div v-if="testimonial.videoTestimonial" class="mt-4 pt-4 border-t border-gray-100">
      <button
        @click="playVideo"
        class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors"
        :aria-label="`Play video testimonial from ${getTestimonialAuthorName()}`"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z"/>
        </svg>
        <span class="text-sm font-medium">Watch Video</span>
      </button>
    </div>

    <!-- Featured Badge -->
    <div v-if="testimonial.featured" class="absolute top-4 right-4">
      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        Featured
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Testimonial, InstitutionTestimonial, AudienceType, SuccessMetric } from '@/types/homepage'

interface Props {
  testimonial: Testimonial | InstitutionTestimonial
  audience: AudienceType
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  playVideo: [videoUrl: string, title: string]
}>()

// Type guards
const isIndividualTestimonial = (testimonial: Testimonial | InstitutionTestimonial): testimonial is Testimonial => {
  return 'author' in testimonial
}

const isInstitutionalTestimonial = (testimonial: Testimonial | InstitutionTestimonial): testimonial is InstitutionTestimonial => {
  return 'institution' in testimonial
}

// Methods
const handleImageError = (event: Event): void => {
  const img = event.target as HTMLImageElement
  img.src = '/images/placeholder-avatar.png' // Fallback image
}

const playVideo = (): void => {
  if (props.testimonial.videoTestimonial) {
    const title = getTestimonialAuthorName()
    emit('playVideo', props.testimonial.videoTestimonial, `${title} - Video Testimonial`)
  }
}

const getTestimonialAuthorName = (): string => {
  if (isIndividualTestimonial(props.testimonial)) {
    return props.testimonial.author.name
  } else if (isInstitutionalTestimonial(props.testimonial)) {
    return props.testimonial.administrator.name
  }
  return 'Unknown'
}

const formatCareerStage = (stage: string): string => {
  const stageMap: Record<string, string> = {
    'recent_grad': 'Recent Grad',
    'mid_career': 'Mid-Career',
    'senior': 'Senior',
    'executive': 'Executive'
  }
  return stageMap[stage] || stage
}

const formatInstitutionType = (type: string): string => {
  const typeMap: Record<string, string> = {
    'university': 'University',
    'college': 'College',
    'corporate': 'Corporation',
    'nonprofit': 'Non-Profit'
  }
  return typeMap[type] || type
}

const getCareerStageBadgeClass = (stage: string): string => {
  const classMap: Record<string, string> = {
    'recent_grad': 'bg-green-100 text-green-800',
    'mid_career': 'bg-blue-100 text-blue-800',
    'senior': 'bg-purple-100 text-purple-800',
    'executive': 'bg-red-100 text-red-800'
  }
  return classMap[stage] || 'bg-gray-100 text-gray-800'
}

const formatMetricValue = (metric: SuccessMetric): string => {
  switch (metric.unit) {
    case 'percentage':
      return `+${metric.value}%`
    case 'dollar':
      return `$${metric.value.toLocaleString()}`
    case 'count':
      return metric.value.toString()
    case 'days':
      return `${metric.value} days`
    default:
      return metric.value.toString()
  }
}
</script>

<style scoped>
.testimonial-card {
  position: relative;
  min-height: 300px;
}

.testimonial-card:hover {
  transform: translateY(-2px);
}

/* Smooth transitions */
.testimonial-card * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Image loading states */
.testimonial-card img {
  background-color: #f3f4f6;
}

.testimonial-card img[src*="placeholder"] {
  opacity: 0.6;
}

/* Focus styles for accessibility */
.testimonial-card button:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.testimonial-card a:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2 rounded;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .testimonial-card {
    min-height: 250px;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .testimonial-card,
  .testimonial-card * {
    transition: none;
  }
  
  .testimonial-card:hover {
    transform: none;
  }
}

/* Print styles */
@media print {
  .testimonial-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #e5e7eb;
  }
}
</style>