<template>
  <div class="testimonials-carousel">
    <div class="container mx-auto px-4">
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          {{ title }}
        </h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          {{ subtitle }}
        </p>
      </div>

      <!-- Persona Filters -->
      <div v-if="showFilters && personaFilters.length > 0" class="flex flex-wrap justify-center gap-2 mb-8">
        <button
          v-for="filter in personaFilters"
          :key="filter.value"
          @click="setActiveFilter(filter.value)"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            activeFilter === filter.value
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
          :aria-pressed="activeFilter === filter.value"
        >
          {{ filter.label }}
          <span v-if="filter.count" class="ml-1 text-xs opacity-75">
            ({{ filter.count }})
          </span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="i in 3" 
          :key="i"
          class="bg-white rounded-lg shadow-md p-6 animate-pulse"
        >
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gray-200 rounded-full mr-4"></div>
            <div>
              <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-32"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="h-4 bg-gray-200 rounded"></div>
            <div class="h-4 bg-gray-200 rounded w-5/6"></div>
            <div class="h-4 bg-gray-200 rounded w-4/6"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button 
          @click="fetchTestimonials"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          Try Again
        </button>
      </div>

      <!-- Testimonials Grid -->
      <div v-else-if="filteredTestimonials.length > 0" class="relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden" ref="carouselContainer">
          <div 
            class="flex transition-transform duration-500 ease-in-out"
            :style="{ transform: `translateX(-${currentSlide * slideWidth}%)` }"
          >
            <div
              v-for="testimonial in filteredTestimonials"
              :key="testimonial.id"
              :class="[
                'flex-shrink-0 px-3',
                slidesPerView === 1 ? 'w-full' : 
                slidesPerView === 2 ? 'w-1/2' : 'w-1/3'
              ]"
            >
              <TestimonialCard
                :testimonial="testimonial"
                :audience="audience"
                @play-video="handleVideoPlay"
              />
            </div>
          </div>
        </div>

        <!-- Navigation Arrows -->
        <button
          v-if="showNavigation && canNavigatePrev"
          @click="prevSlide"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 bg-white rounded-full shadow-lg p-2 hover:bg-gray-50 transition-colors z-10"
          aria-label="Previous testimonials"
        >
          <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>

        <button
          v-if="showNavigation && canNavigateNext"
          @click="nextSlide"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 bg-white rounded-full shadow-lg p-2 hover:bg-gray-50 transition-colors z-10"
          aria-label="Next testimonials"
        >
          <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>

        <!-- Pagination Dots -->
        <div v-if="showPagination && totalSlides > 1" class="flex justify-center mt-8 space-x-2">
          <button
            v-for="slide in totalSlides"
            :key="slide"
            @click="goToSlide(slide - 1)"
            :class="[
              'w-3 h-3 rounded-full transition-colors',
              currentSlide === slide - 1 ? 'bg-blue-600' : 'bg-gray-300 hover:bg-gray-400'
            ]"
            :aria-label="`Go to slide ${slide}`"
          />
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
        </div>
        <p class="text-gray-600">No testimonials available for the selected filter.</p>
      </div>
    </div>

    <!-- Video Modal -->
    <VideoModal
      v-if="showVideoModal"
      :video-url="selectedVideoUrl"
      :title="selectedVideoTitle"
      @close="closeVideoModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useResizeObserver } from '@vueuse/core'
import TestimonialCard from './TestimonialCard.vue'
import VideoModal from './VideoModal.vue'
import type { Testimonial, InstitutionTestimonial, AudienceType } from '@/types/homepage'

interface PersonaFilter {
  value: string
  label: string
  count?: number
}

interface Props {
  audience: AudienceType
  title?: string
  subtitle?: string
  showFilters?: boolean
  showNavigation?: boolean
  showPagination?: boolean
  autoPlay?: boolean
  autoPlayInterval?: number
  slidesPerView?: number | 'auto'
}

const props = withDefaults(defineProps<Props>(), {
  title: 'What Our Alumni Say',
  subtitle: 'Hear from professionals who have transformed their careers through our platform',
  showFilters: true,
  showNavigation: true,
  showPagination: true,
  autoPlay: false,
  autoPlayInterval: 5000,
  slidesPerView: 3
})

// Reactive state
const testimonials = ref<(Testimonial | InstitutionTestimonial)[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const activeFilter = ref<string>('all')
const currentSlide = ref(0)
const carouselContainer = ref<HTMLElement>()
const autoPlayTimer = ref<number>()

// Video modal state
const showVideoModal = ref(false)
const selectedVideoUrl = ref<string>('')
const selectedVideoTitle = ref<string>('')

// Computed properties
const audienceSpecificTitle = computed(() => {
  if (props.audience === 'institutional') {
    return 'Trusted by Leading Institutions'
  }
  return props.title
})

const audienceSpecificSubtitle = computed(() => {
  if (props.audience === 'institutional') {
    return 'See how universities and organizations are transforming alumni engagement'
  }
  return props.subtitle
})

const personaFilters = computed((): PersonaFilter[] => {
  if (!props.showFilters) return []

  const filters: PersonaFilter[] = [
    { value: 'all', label: 'All Stories', count: testimonials.value.length }
  ]

  if (props.audience === 'individual') {
    const individualTestimonials = testimonials.value as Testimonial[]
    const careerStages = [...new Set(individualTestimonials.map(t => t.author.careerStage))]
    
    careerStages.forEach(stage => {
      const count = individualTestimonials.filter(t => t.author.careerStage === stage).length
      filters.push({
        value: stage,
        label: formatCareerStage(stage),
        count
      })
    })
  } else {
    const institutionalTestimonials = testimonials.value as InstitutionTestimonial[]
      const institutionTypes = [...new Set(institutionalTestimonials.filter(t => t.institution).map(t => t.institution.type))]
    
    institutionTypes.forEach(type => {
      const count = institutionalTestimonials.filter(t => t.institution.type === type).length
      filters.push({
        value: type,
        label: formatInstitutionType(type),
        count
      })
    })
  }

  return filters
})

const filteredTestimonials = computed(() => {
  if (activeFilter.value === 'all') {
    return testimonials.value
  }

  if (props.audience === 'individual') {
    const individualTestimonials = testimonials.value as Testimonial[]
    return individualTestimonials.filter(t => t.author.careerStage === activeFilter.value)
  } else {
    const institutionalTestimonials = testimonials.value as InstitutionTestimonial[]
    return institutionalTestimonials.filter(t => t.institution.type === activeFilter.value)
  }
})

const slidesPerView = computed(() => {
  if (typeof props.slidesPerView === 'number') {
    return props.slidesPerView
  }
  // Auto-calculate based on container width
  return 3 // Default fallback
})

const slideWidth = computed(() => {
  return 100 / slidesPerView.value
})

const totalSlides = computed(() => {
  return Math.max(0, filteredTestimonials.value.length - slidesPerView.value + 1)
})

const canNavigatePrev = computed(() => {
  return currentSlide.value > 0
})

const canNavigateNext = computed(() => {
  return currentSlide.value < totalSlides.value - 1
})

// Methods
const fetchTestimonials = async (): Promise<void> => {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/homepage/testimonials?audience=${props.audience}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    
    if (data.success) {
      testimonials.value = data.data
    } else {
      throw new Error(data.message || 'Failed to fetch testimonials')
    }
  } catch (err) {
    console.error('Error fetching testimonials:', err)
    error.value = err instanceof Error ? err.message : 'Failed to load testimonials'
    
    // Fallback to mock data for development
    if (process.env.NODE_ENV === 'development') {
      testimonials.value = getMockTestimonials()
    }
  } finally {
    loading.value = false
  }
}

const getMockTestimonials = (): (Testimonial | InstitutionTestimonial)[] => {
  if (props.audience === 'institutional') {
    return [
      {
        id: '1',
        quote: 'Our alumni engagement increased by 400% after implementing the branded mobile app. The analytics dashboard gives us incredible insights into our community.',
        institution: {
          id: '1',
          name: 'Stanford University',
          type: 'university',
          logo: '/images/institutions/stanford-logo.png',
          website: 'https://stanford.edu',
          alumniCount: 50000,
          establishedYear: 1885,
          location: 'Stanford, CA',
          tier: 'enterprise'
        },
        administrator: {
          id: '1',
          name: 'Dr. Jennifer Walsh',
          title: 'Director of Alumni Relations',
          institution: 'Stanford University',
          email: 'j.walsh@stanford.edu',
          profileImage: '/images/testimonials/jennifer-walsh.jpg',
          responsibilities: ['Alumni Engagement', 'Digital Strategy'],
          experience: 12
        },
        results: [
          {
            metric: 'engagement',
            beforeValue: 15,
            afterValue: 60,
            improvementPercentage: 300,
            timeframe: '12 months',
            verified: true
          }
        ],
        videoTestimonial: '/videos/testimonials/stanford-testimonial.mp4',
        featured: true
      }
    ] as InstitutionTestimonial[]
  }

  return [
    {
      id: '1',
      quote: 'This platform helped me land my dream job at Google. The alumni connections were invaluable, and the mentorship program guided me through the entire process.',
      author: {
        id: '1',
        name: 'Sarah Chen',
        graduationYear: 2019,
        degree: 'Computer Science',
        currentRole: 'Software Engineer',
        currentCompany: 'Google',
        industry: 'Technology',
        location: 'Mountain View, CA',
        profileImage: '/images/testimonials/sarah-chen.jpg',
        linkedinUrl: 'https://linkedin.com/in/sarahchen',
        careerStage: 'mid_career',
        specialties: ['Machine Learning', 'Backend Development'],
        mentorshipAvailable: true
      },
      careerProgression: {
        before: {
          role: 'Junior Developer',
          company: 'Local Startup',
          salary: 75000,
          level: 'Junior',
          responsibilities: ['Bug fixes', 'Basic features']
        },
        after: {
          role: 'Software Engineer',
          company: 'Google',
          salary: 180000,
          level: 'L4',
          responsibilities: ['System design', 'Team leadership']
        },
        timeframe: '18 months',
        keyMilestones: [
          {
            date: new Date('2023-01-15'),
            title: 'Started networking',
            description: 'Joined the platform and connected with Google alumni',
            type: 'achievement'
          }
        ]
      },
      metrics: [
        {
          type: 'salary_increase',
          value: 140,
          unit: 'percentage',
          timeframe: '18 months',
          verified: true
        }
      ],
      featured: true
    },
    {
      id: '2',
      quote: 'The mentorship program connected me with industry leaders who guided my career transition from engineering to product management.',
      author: {
        id: '2',
        name: 'Michael Rodriguez',
        graduationYear: 2016,
        degree: 'Mechanical Engineering',
        currentRole: 'Senior Product Manager',
        currentCompany: 'Microsoft',
        industry: 'Technology',
        location: 'Seattle, WA',
        profileImage: '/images/testimonials/michael-rodriguez.jpg',
        linkedinUrl: 'https://linkedin.com/in/michaelrodriguez',
        careerStage: 'senior',
        specialties: ['Product Strategy', 'User Experience'],
        mentorshipAvailable: true
      },
      metrics: [
        {
          type: 'promotion',
          value: 1,
          unit: 'count',
          timeframe: '12 months',
          verified: true
        }
      ],
      featured: false
    }
  ] as Testimonial[]
}

const setActiveFilter = (filter: string): void => {
  activeFilter.value = filter
  currentSlide.value = 0 // Reset to first slide when filter changes
}

const nextSlide = (): void => {
  if (canNavigateNext.value) {
    currentSlide.value++
  }
}

const prevSlide = (): void => {
  if (canNavigatePrev.value) {
    currentSlide.value--
  }
}

const goToSlide = (slideIndex: number): void => {
  if (slideIndex >= 0 && slideIndex < totalSlides.value) {
    currentSlide.value = slideIndex
  }
}

const handleVideoPlay = (videoUrl: string, title: string): void => {
  selectedVideoUrl.value = videoUrl
  selectedVideoTitle.value = title
  showVideoModal.value = true
}

const closeVideoModal = (): void => {
  showVideoModal.value = false
  selectedVideoUrl.value = ''
  selectedVideoTitle.value = ''
}

const startAutoPlay = (): void => {
  if (props.autoPlay && props.autoPlayInterval > 0) {
    autoPlayTimer.value = window.setInterval(() => {
      if (canNavigateNext.value) {
        nextSlide()
      } else {
        currentSlide.value = 0 // Loop back to start
      }
    }, props.autoPlayInterval)
  }
}

const stopAutoPlay = (): void => {
  if (autoPlayTimer.value) {
    clearInterval(autoPlayTimer.value)
    autoPlayTimer.value = undefined
  }
}

const formatCareerStage = (stage: string): string => {
  const stageMap: Record<string, string> = {
    'recent_grad': 'Recent Graduates',
    'mid_career': 'Mid-Career',
    'senior': 'Senior Professionals',
    'executive': 'Executives'
  }
  return stageMap[stage] || stage
}

const formatInstitutionType = (type: string): string => {
  const typeMap: Record<string, string> = {
    'university': 'Universities',
    'college': 'Colleges',
    'corporate': 'Corporations',
    'nonprofit': 'Non-Profits'
  }
  return typeMap[type] || type
}

// Responsive behavior
useResizeObserver(carouselContainer, () => {
  // Adjust slides per view based on container width
  // This could be enhanced with more sophisticated responsive logic
})

// Watchers
watch(() => props.autoPlay, (newValue) => {
  if (newValue) {
    startAutoPlay()
  } else {
    stopAutoPlay()
  }
})

watch(activeFilter, () => {
  // Reset slide position when filter changes
  currentSlide.value = 0
})

// Lifecycle hooks
onMounted(() => {
  fetchTestimonials()
  if (props.autoPlay) {
    startAutoPlay()
  }
})

onUnmounted(() => {
  stopAutoPlay()
})

// Expose methods for parent components
defineExpose({
  fetchTestimonials,
  refresh: fetchTestimonials,
  nextSlide,
  prevSlide,
  goToSlide,
  setActiveFilter
})
</script>

<style scoped>
.testimonials-carousel {
  @apply py-16 bg-white;
}

/* Smooth transitions */
.testimonials-carousel * {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Navigation button hover effects */
.testimonials-carousel button:hover {
  transform: scale(1.05);
}

.testimonials-carousel button:active {
  transform: scale(0.95);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .testimonials-carousel {
    @apply py-12;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .testimonials-carousel * {
    transition: none;
  }
  
  .testimonials-carousel button:hover,
  .testimonials-carousel button:active {
    transform: none;
  }
}

/* Focus styles for keyboard navigation */
.testimonials-carousel button:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.testimonials-carousel button:focus-visible {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}
</style>