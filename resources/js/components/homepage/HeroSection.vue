<template>
    <section class="hero-section" role="banner" aria-label="Homepage hero section">
        <!-- Video Background -->
        <div class="hero-background">
            <video
                v-if="heroData?.backgroundVideo && !reducedMotion"
                ref="videoElement"
                class="hero-video"
                :src="heroData.backgroundVideo"
                autoplay
                muted
                loop
                playsinline
                :aria-hidden="true"
                @error="handleVideoError"
            />
            <div class="hero-background-image" :style="{ backgroundImage: `url(${backgroundImageUrl})` }" :aria-hidden="true" />
            <div class="hero-overlay" aria-hidden="true" />
        </div>

        <div class="hero-content">
            <div class="hero-container">
                <div class="hero-text">
                    <h1 class="hero-headline" :id="headlineId">
                        {{ heroData?.headline || 'Welcome' }}
                    </h1>
                    <p class="hero-subtitle" :aria-describedby="headlineId">
                        {{ heroData?.subtitle || 'Connect with your alumni network' }}
                    </p>

                    <!-- Statistics Counter -->
                    <div
                        v-if="heroData?.statisticsHighlight?.length"
                        class="hero-statistics"
                        ref="statisticsRef"
                        role="region"
                        aria-label="Platform statistics"
                    >
                        <div v-for="stat in heroData.statisticsHighlight" :key="stat.key" class="hero-stat">
                            <div class="hero-stat-value">
                                <AnimatedCounter
                                    :target-value="stat.value"
                                    :format="stat.format"
                                    :suffix="stat.suffix"
                                    :animate="shouldAnimateStats"
                                    :aria-label="`${stat.label}: ${formatStatValue(stat)}`"
                                />
                            </div>
                            <div class="hero-stat-label">{{ stat.label }}</div>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <button
                            v-if="heroData?.primaryCTA"
                            class="hero-cta-primary"
                            :aria-describedby="heroData.primaryCTA.trackingEvent"
                            @click="handleCTAClick(heroData.primaryCTA)"
                            @keydown.enter="handleCTAClick(heroData.primaryCTA)"
                            @keydown.space.prevent="handleCTAClick(heroData.primaryCTA)"
                        >
                            {{ heroData.primaryCTA.text }}
                        </button>

                        <button
                            v-if="heroData?.secondaryCTA"
                            class="hero-cta-secondary"
                            :aria-describedby="heroData.secondaryCTA.trackingEvent"
                            @click="handleCTAClick(heroData.secondaryCTA)"
                            @keydown.enter="handleCTAClick(heroData.secondaryCTA)"
                            @keydown.space.prevent="handleCTAClick(heroData.secondaryCTA)"
                        >
                            {{ heroData.secondaryCTA.text }}
                        </button>
                    </div>
                </div>

                <!-- Rotating Testimonials -->
                <div
                    v-if="heroData?.testimonialRotation?.length"
                    class="hero-testimonials"
                    role="region"
                    aria-label="Alumni testimonials"
                    aria-live="polite"
                >
                    <Transition name="testimonial-fade" mode="out-in">
                        <div :key="currentTestimonial.id" class="hero-testimonial">
                            <blockquote class="hero-testimonial-quote">"{{ currentTestimonial.quote }}"</blockquote>
                            <cite class="hero-testimonial-author">
                                <img
                                    :src="currentTestimonial.author.profileImage"
                                    :alt="`${currentTestimonial.author.name} profile photo`"
                                    class="hero-testimonial-avatar"
                                    loading="lazy"
                                />
                                <div class="hero-testimonial-info">
                                    <div class="hero-testimonial-name">
                                        {{ currentTestimonial.author.name }}
                                    </div>
                                    <div class="hero-testimonial-role">
                                        {{ currentTestimonial.author.currentRole }} at {{ currentTestimonial.author.currentCompany }}
                                    </div>
                                </div>
                            </cite>
                        </div>
                    </Transition>

                    <!-- Testimonial Navigation -->
                    <div class="hero-testimonial-nav" role="tablist" aria-label="Testimonial navigation">
                        <button
                            v-for="(testimonial, index) in heroData.testimonialRotation"
                            :key="testimonial.id"
                            class="hero-testimonial-dot"
                            :class="{ active: index === currentTestimonialIndex }"
                            :aria-selected="index === currentTestimonialIndex"
                            :aria-label="`View testimonial from ${testimonial.author.name}`"
                            role="tab"
                            @click="setCurrentTestimonial(index)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue';
import type { AudienceType, CTAButton, CTAClickEvent, HeroSectionProps, PlatformStatistic } from '@/types/homepage';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    audience: AudienceType;
    heroData?: HeroSectionProps;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'cta-click': [event: CTAClickEvent];
}>();

// Refs
const videoElement = ref<HTMLVideoElement>();
const statisticsRef = ref<HTMLElement>();

// State
const currentTestimonialIndex = ref(0);
const shouldAnimateStats = ref(false);
const videoError = ref(false);
const testimonialInterval = ref<NodeJS.Timeout>();

// Accessibility
const reducedMotion = ref(false);
const headlineId = computed(() => `hero-headline-${Math.random().toString(36).substr(2, 9)}`);

// Computed
const currentTestimonial = computed(() => {
    if (!props.heroData?.testimonialRotation?.length) return null;
    return props.heroData.testimonialRotation[currentTestimonialIndex.value];
});

const backgroundImageUrl = computed(() => {
    if (videoError.value || !props.heroData?.backgroundVideo) {
        return props.heroData?.backgroundImage || '/images/hero-fallback.jpg';
    }
    return props.heroData?.backgroundImage || '/images/hero-fallback.jpg';
});

// Methods
const handleCTAClick = (cta: CTAButton) => {
    const event: CTAClickEvent = {
        action: cta.action,
        section: 'hero',
        audience: props.audience,
        additionalData: {
            text: cta.text,
            variant: cta.variant,
            trackingEvent: cta.trackingEvent || '',
        },
    };

    // Track analytics
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'hero_cta_click', {
            cta_text: cta.text,
            cta_action: cta.action,
            audience: props.audience,
            section: 'hero',
        });
    }

    emit('cta-click', event);
};

const handleVideoError = () => {
    videoError.value = true;
    console.warn('Hero video failed to load, falling back to background image');
};

const formatStatValue = (stat: PlatformStatistic): string => {
    let value = stat.value.toString();

    if (stat.format === 'percentage') {
        value = `${stat.value}%`;
    } else if (stat.format === 'currency') {
        value = `$${stat.value.toLocaleString()}`;
    } else if (stat.format === 'number') {
        value = stat.value.toLocaleString();
    }

    return stat.suffix ? `${value}${stat.suffix}` : value;
};

const setCurrentTestimonial = (index: number) => {
    currentTestimonialIndex.value = index;
    resetTestimonialInterval();
};

const nextTestimonial = () => {
    if (!props.heroData?.testimonialRotation?.length) return;

    currentTestimonialIndex.value = (currentTestimonialIndex.value + 1) % props.heroData.testimonialRotation.length;
};

const resetTestimonialInterval = () => {
    if (testimonialInterval.value) {
        clearInterval(testimonialInterval.value);
    }

    if (!reducedMotion.value && props.heroData?.testimonialRotation?.length > 1) {
        testimonialInterval.value = setInterval(nextTestimonial, 5000);
    }
};

const setupIntersectionObserver = () => {
    if (!statisticsRef.value || typeof window === 'undefined') return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    shouldAnimateStats.value = true;
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 },
    );

    observer.observe(statisticsRef.value);
};

const checkReducedMotion = () => {
    if (typeof window !== 'undefined') {
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        reducedMotion.value = mediaQuery.matches;

        mediaQuery.addEventListener('change', (e) => {
            reducedMotion.value = e.matches;
            if (e.matches && testimonialInterval.value) {
                clearInterval(testimonialInterval.value);
            } else {
                resetTestimonialInterval();
            }
        });
    }
};

// Lifecycle
onMounted(async () => {
    checkReducedMotion();

    await nextTick();

    setupIntersectionObserver();
    resetTestimonialInterval();

    // Preload video if available
    if (props.heroData?.backgroundVideo && videoElement.value) {
        videoElement.value.load();
    }
});

onUnmounted(() => {
    if (testimonialInterval.value) {
        clearInterval(testimonialInterval.value);
    }
});
</script>

<style scoped>
.hero-section {
    @apply relative flex min-h-screen items-center justify-center overflow-hidden;
}

/* Background */
.hero-background {
    @apply absolute inset-0 z-0;
}

.hero-video {
    @apply absolute inset-0 h-full w-full object-cover;
}

.hero-background-image {
    @apply absolute inset-0 h-full w-full bg-cover bg-center bg-no-repeat;
    background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-overlay {
    @apply absolute inset-0 bg-black bg-opacity-40;
}

/* Content */
.hero-content {
    @apply relative z-10 w-full;
}

.hero-container {
    @apply mx-auto max-w-7xl px-4 py-20 text-center text-white;
}

.hero-text {
    @apply mb-12;
}

.hero-headline {
    @apply mb-6 text-4xl font-bold leading-tight text-white md:text-6xl lg:text-7xl;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    @apply mx-auto mb-8 max-w-4xl text-lg leading-relaxed text-gray-100 md:text-xl lg:text-2xl;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Statistics */
.hero-statistics {
    @apply mx-auto mb-12 flex max-w-4xl flex-wrap justify-center gap-8;
}

.hero-stat {
    @apply min-w-0 flex-1 text-center;
}

.hero-stat-value {
    @apply mb-2 text-2xl font-bold text-white md:text-3xl lg:text-4xl;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-stat-label {
    @apply text-sm uppercase tracking-wide text-gray-200 md:text-base;
}

/* Actions */
.hero-actions {
    @apply mb-16 flex flex-col justify-center gap-4 sm:flex-row;
}

.hero-cta-primary {
    @apply transform rounded-lg bg-white px-8 py-4 font-semibold text-blue-600 shadow-lg transition-all duration-300 hover:scale-105 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-50;
}

.hero-cta-secondary {
    @apply transform rounded-lg border-2 border-white bg-transparent px-8 py-4 font-semibold text-white transition-all duration-300 hover:scale-105 hover:bg-white hover:text-blue-600 focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-50;
}

/* Testimonials */
.hero-testimonials {
    @apply mx-auto max-w-4xl;
}

.hero-testimonial {
    @apply mb-8 text-center;
}

.hero-testimonial-quote {
    @apply mb-6 text-lg italic leading-relaxed text-gray-100 md:text-xl;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.hero-testimonial-author {
    @apply flex items-center justify-center gap-4 not-italic;
}

.hero-testimonial-avatar {
    @apply h-12 w-12 rounded-full border-2 border-white object-cover shadow-lg;
}

.hero-testimonial-info {
    @apply text-left;
}

.hero-testimonial-name {
    @apply text-base font-semibold text-white;
}

.hero-testimonial-role {
    @apply text-sm text-gray-200;
}

/* Testimonial Navigation */
.hero-testimonial-nav {
    @apply flex justify-center gap-2;
}

.hero-testimonial-dot {
    @apply h-3 w-3 rounded-full bg-white bg-opacity-50 transition-all duration-300 hover:bg-opacity-75 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50;
}

.hero-testimonial-dot.active {
    @apply bg-white bg-opacity-100;
}

/* Transitions */
.testimonial-fade-enter-active,
.testimonial-fade-leave-active {
    transition: opacity 0.5s ease-in-out;
}

.testimonial-fade-enter-from,
.testimonial-fade-leave-to {
    opacity: 0;
}

/* Responsive Design */
@media (max-width: 640px) {
    .hero-container {
        @apply px-6 py-16;
    }

    .hero-headline {
        @apply mb-4 text-3xl;
    }

    .hero-subtitle {
        @apply mb-6 text-base;
    }

    .hero-statistics {
        @apply mb-8 gap-4;
    }

    .hero-stat-value {
        @apply text-xl;
    }

    .hero-actions {
        @apply mb-12;
    }

    .hero-cta-primary,
    .hero-cta-secondary {
        @apply px-6 py-3 text-sm;
    }

    .hero-testimonial-quote {
        @apply mb-4 text-base;
    }

    .hero-testimonial-author {
        @apply flex-col gap-2;
    }

    .hero-testimonial-info {
        @apply text-center;
    }
}

@media (max-width: 480px) {
    .hero-statistics {
        @apply flex-col gap-6;
    }

    .hero-stat {
        @apply min-w-full;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .hero-cta-primary,
    .hero-cta-secondary {
        @apply transform-none;
    }

    .hero-cta-primary:hover,
    .hero-cta-secondary:hover {
        @apply scale-100;
    }

    .testimonial-fade-enter-active,
    .testimonial-fade-leave-active {
        transition: none;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .hero-overlay {
        @apply bg-opacity-60;
    }

    .hero-headline,
    .hero-subtitle,
    .hero-testimonial-quote {
        text-shadow: 0 0 4px rgba(0, 0, 0, 0.8);
    }
}

/* Print styles */
@media print {
    .hero-section {
        @apply min-h-0 py-8;
    }

    .hero-video,
    .hero-background-image,
    .hero-overlay {
        @apply hidden;
    }

    .hero-headline,
    .hero-subtitle,
    .hero-testimonial-quote {
        @apply text-black;
        text-shadow: none;
    }
}
</style>
