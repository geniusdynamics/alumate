<template>
    <article :class="testimonialClasses" role="article" :aria-labelledby="headingId">
        <!-- Video Testimonial -->
        <div v-if="testimonial.content.type === 'video' && testimonial.content.videoAsset" class="relative mb-8">
            <ResponsiveVideo :src="testimonial.content.videoAsset" :poster="testimonial.content.videoAsset.thumbnail"
                :autoplay="config.videoSettings?.autoplay ?? false" :muted="config.videoSettings?.muted ?? true"
                :show-controls="config.videoSettings?.showControls ?? true"
                :show-captions="config.videoSettings?.showCaptions ?? true"
                :preload="config.videoSettings?.preload ?? 'metadata'" :lazy-load="config.lazyLoad"
                @play="handleVideoPlay" @pause="handleVideoPause" @ended="handleVideoEnded"
                class="rounded-lg overflow-hidden shadow-lg" />
        </div>

        <!-- Text Content -->
        <div class="testimonial-content">
            <!-- Quote -->
            <blockquote :class="quoteClasses" :cite="testimonial.content.id">
                <svg v-if="theme !== 'minimal'" class="quote-icon w-8 h-8 text-gray-400 dark:text-gray-500 mb-4"
                    fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z" />
                </svg>

                <p class="testimonial-quote text-lg leading-relaxed">
                    {{ testimonial.content.quote }}
                </p>
            </blockquote>

            <!-- Rating -->
            <div v-if="config.showRating && testimonial.content.rating" class="flex items-center mb-4"
                :aria-label="`Rating: ${testimonial.content.rating} out of 5 stars`">
                <div class="flex space-x-1">
                    <svg v-for="star in 5" :key="`star-${star}`" :class="[
                        'w-5 h-5',
                        star <= testimonial.content.rating
                            ? 'text-yellow-400 fill-current'
                            : 'text-gray-300 dark:text-gray-600'
                    ]" viewBox="0 0 20 20" :aria-hidden="true">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    ({{ testimonial.content.rating }}/5)
                </span>
            </div>

            <!-- Author Information -->
            <footer class="testimonial-author">
                <div class="flex items-center space-x-4">
                    <!-- Author Photo -->
                    <div v-if="config.showAuthorPhoto && testimonial.author.photo" class="flex-shrink-0">
                        <ResponsiveImage :src="testimonial.author.photo" :alt="`Photo of ${testimonial.author.name}`"
                            :lazy-load="config.lazyLoad" class="w-12 h-12 rounded-full object-cover" />
                    </div>

                    <!-- Author Details -->
                    <div class="flex-1 min-w-0">
                        <cite :id="headingId" class="block font-semibold text-gray-900 dark:text-white not-italic">
                            {{ testimonial.author.name }}
                        </cite>

                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <div v-if="config.showAuthorTitle && testimonial.author.title" class="author-title">
                                {{ testimonial.author.title }}
                            </div>

                            <div v-if="config.showAuthorCompany && testimonial.author.company" class="author-company">
                                {{ testimonial.author.company }}
                            </div>

                            <div v-if="config.showGraduationYear && testimonial.author.graduationYear"
                                class="author-graduation">
                                Class of {{ testimonial.author.graduationYear }}
                            </div>
                        </div>
                    </div>

                    <!-- Verification Badge -->
                    <div v-if="testimonial.content.verified" class="flex-shrink-0" :title="'Verified testimonial'">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"
                            aria-label="Verified">
                            <path fill-rule="evenodd"
                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Date -->
                <div v-if="config.showDate && testimonial.content.dateCreated"
                    class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(testimonial.content.dateCreated) }}
                </div>
            </footer>

            <!-- Interaction Buttons -->
            <div v-if="showInteractionButtons"
                class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <!-- Like Button -->
                <button @click="handleLike"
                    class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    :aria-label="`Like testimonial by ${testimonial.author.name}`">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span>{{ testimonial.content.likeCount || 0 }}</span>
                </button>

                <!-- Share Button -->
                <button @click="handleShare"
                    class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    :aria-label="`Share testimonial by ${testimonial.author.name}`">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                    </svg>
                    <span>Share</span>
                </button>
            </div>
        </div>
    </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Testimonial } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'

// Import child components
import ResponsiveVideo from '@/components/Common/ResponsiveVideo.vue'
import ResponsiveImage from '@/components/Common/ResponsiveImage.vue'
import { count } from 'console'
import { after } from 'lodash-es'
import { before } from 'lodash-es'
import { before } from 'lodash-es'
import { size } from 'lodash-es'
import { transform } from 'lodash-es'
import { relative } from 'path'
import { size } from 'lodash-es'
import { type } from 'os'
import { count } from 'console'
import { after } from 'lodash-es'
import { after } from 'lodash-es'
import { before } from 'lodash-es'
import { size } from 'lodash-es'
import { transform } from 'lodash-es'
import { type } from 'os'

interface TestimonialSingleConfig {
    showAuthorPhoto?: boolean
    showAuthorTitle?: boolean
    showAuthorCompany?: boolean
    showGraduationYear?: boolean
    showRating?: boolean
    showDate?: boolean
    videoSettings?: {
        autoplay?: boolean
        muted?: boolean
        showControls?: boolean
        showCaptions?: boolean
        preload?: 'none' | 'metadata' | 'auto'
    }
    lazyLoad?: boolean
    showInteractionButtons?: boolean
    trackingEnabled?: boolean
}

interface Props {
    testimonial: Testimonial
    config: TestimonialSingleConfig
    theme?: 'default' | 'minimal' | 'modern' | 'classic' | 'card'
    colorScheme?: 'default' | 'primary' | 'secondary' | 'accent'
}

const props = withDefaults(defineProps<Props>(), {
    theme: 'default',
    colorScheme: 'default',
    config: () => ({
        showAuthorPhoto: true,
        showAuthorTitle: true,
        showAuthorCompany: true,
        showGraduationYear: true,
        showRating: true,
        showDate: true,
        lazyLoad: true,
        showInteractionButtons: false,
        trackingEnabled: true
    })
})

const emit = defineEmits<{
    testimonialInteraction: [event: { type: 'view' | 'like' | 'share' | 'play' | 'pause', testimonial: Testimonial, data?: any }]
}>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const isLiked = ref(false)

// Computed properties
const headingId = computed(() => `testimonial-${props.testimonial.id}-heading`)

const testimonialClasses = computed(() => [
    'testimonial-single',
    'max-w-4xl mx-auto p-6',
    {
        // Theme-based styling
        'bg-white dark:bg-gray-800 rounded-lg shadow-lg': props.theme === 'card',
        'bg-gray-50 dark:bg-gray-900 rounded-xl': props.theme === 'modern',
        'border-l-4 border-indigo-500 pl-6': props.theme === 'classic',

        // Color scheme variations
        'border-indigo-500': props.colorScheme === 'primary' && props.theme === 'classic',
        'border-green-500': props.colorScheme === 'secondary' && props.theme === 'classic',
        'border-purple-500': props.colorScheme === 'accent' && props.theme === 'classic',
    }
])

const quoteClasses = computed(() => [
    'testimonial-quote-container',
    'mb-6',
    {
        'text-center': props.theme === 'modern',
        'italic': props.theme === 'classic',
        'text-xl': props.theme === 'card' || props.theme === 'modern',
    }
])

const showInteractionButtons = computed(() => {
    return props.config.showInteractionButtons && (
        props.testimonial.content.likeCount !== undefined ||
        props.testimonial.content.shareCount !== undefined
    )
})

// Methods
const formatDate = (dateString: string): string => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const handleVideoPlay = () => {
    emit('testimonialInteraction', {
        type: 'play',
        testimonial: props.testimonial,
        data: { timestamp: Date.now() }
    })

    if (props.config.trackingEnabled) {
        trackEvent('testimonial_video_play', {
            testimonial_id: props.testimonial.id,
            author_name: props.testimonial.author.name,
            component_type: 'testimonial_single'
        })
    }
}

const handleVideoPause = () => {
    emit('testimonialInteraction', {
        type: 'pause',
        testimonial: props.testimonial,
        data: { timestamp: Date.now() }
    })
}

const handleVideoEnded = () => {
    emit('testimonialInteraction', {
        type: 'view',
        testimonial: props.testimonial,
        data: {
            completed: true,
            timestamp: Date.now()
        }
    })

    if (props.config.trackingEnabled) {
        trackEvent('testimonial_video_completed', {
            testimonial_id: props.testimonial.id,
            author_name: props.testimonial.author.name,
            component_type: 'testimonial_single'
        })
    }
}

const handleLike = () => {
    isLiked.value = !isLiked.value

    emit('testimonialInteraction', {
        type: 'like',
        testimonial: props.testimonial,
        data: { liked: isLiked.value }
    })

    if (props.config.trackingEnabled) {
        trackEvent('testimonial_like', {
            testimonial_id: props.testimonial.id,
            author_name: props.testimonial.author.name,
            liked: isLiked.value,
            component_type: 'testimonial_single'
        })
    }
}

const handleShare = async () => {
    const shareData = {
        title: `Testimonial from ${props.testimonial.author.name}`,
        text: props.testimonial.content.quote,
        url: window.location.href
    }

    try {
        if (navigator.share) {
            await navigator.share(shareData)
        } else {
            // Fallback: copy to clipboard
            await navigator.clipboard.writeText(`"${props.testimonial.content.quote}" - ${props.testimonial.author.name}`)
            // You could show a toast notification here
        }

        emit('testimonialInteraction', {
            type: 'share',
            testimonial: props.testimonial,
            data: { method: navigator.share ? 'native' : 'clipboard' }
        })

        if (props.config.trackingEnabled) {
            trackEvent('testimonial_share', {
                testimonial_id: props.testimonial.id,
                author_name: props.testimonial.author.name,
                share_method: navigator.share ? 'native' : 'clipboard',
                component_type: 'testimonial_single'
            })
        }
    } catch (error) {
        console.error('Error sharing testimonial:', error)
    }
}<
/script>

    < style scoped >
.testimonial - single {
    container - type: inline - size;
}

/* Quote styling */
.quote - icon {
    opacity: 0.6;
}

.testimonial - quote {
    position: relative;
}

/* Author photo hover effects */
.testimonial - author img {
    transition: transform 0.2s ease;
}

.testimonial - author:hover img {
    transform: scale(1.05);
}

/* Interaction buttons */
.testimonial - single button {
    transition: all 0.2s ease;
}

.testimonial - single button:hover {
    transform: translateY(-1px);
}

/* Container queries for responsive design */
@container(max - width: 640px) {
  .testimonial - single {
        padding: 1rem;
    }
  
  .testimonial - quote {
        font - size: 1rem;
    }
  
  .quote - icon {
        width: 1.5rem;
        height: 1.5rem;
    }
}

/* High contrast mode support */
@media(prefers - contrast: high) {
  .testimonial - single {
        border: 2px solid currentColor;
    }
  
  .quote - icon {
        opacity: 1;
    }
}

/* Reduced motion support */
@media(prefers - reduced - motion: reduce) {
  .testimonial - single *,
  .testimonial - single *:: before,
  .testimonial - single *::after {
        animation - duration: 0.01ms!important;
        animation - iteration - count: 1!important;
        transition - duration: 0.01ms!important;
    }
}

/* Focus management */
.testimonial - single: focus - within {
    outline: 2px solid #6366f1;
    outline - offset: 2px;
}

/* Print styles */
@media print {
  .testimonial - single button {
        display: none;
    }
  
  .testimonial - single {
        break-inside: avoid;
    }
}
</style>