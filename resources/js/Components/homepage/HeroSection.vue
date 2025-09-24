<template>
    <section class="hero-section" role="banner" aria-label="Homepage hero section">
        <!-- Video Background -->
        <div class="hero-background">
            <video
                v-if="computedHeroData?.backgroundVideo && !reducedMotion"
                ref="videoElement"
                class="hero-video"
                :src="computedHeroData.backgroundVideo"
                autoplay
                muted
                loop
                playsinline
                :aria-hidden="true"
                @error="handleVideoError"
            />
            <div class="hero-background-image" :style="{ backgroundImage: `url(${backgroundImageUrl})` }" :aria-hidden="true" />
            <div class="hero-overlay" aria-hidden="true" />
            
            <!-- Floating Elements -->
            <div class="floating-elements">
                <div class="floating-circle floating-circle-1"></div>
                <div class="floating-circle floating-circle-2"></div>
                <div class="floating-circle floating-circle-3"></div>
                <div class="floating-circle floating-circle-4"></div>
                <div class="floating-square floating-square-1"></div>
                <div class="floating-square floating-square-2"></div>
                <div class="floating-triangle floating-triangle-1"></div>
                <div class="floating-triangle floating-triangle-2"></div>
                <div class="floating-orb floating-orb-1"></div>
                <div class="floating-orb floating-orb-2"></div>
                <div class="floating-orb floating-orb-3"></div>
            </div>
        </div>

        <div class="hero-content">
            <div class="hero-container">
                <div class="hero-text">
                    <h1 class="hero-headline" :id="headlineId">
                        {{ computedHeroData?.headline || 'Welcome' }}
                    </h1>
                    <p class="hero-subtitle" :aria-describedby="headlineId">
                        {{ computedHeroData?.subtitle || 'Connect with your alumni network' }}
                    </p>

                    <!-- Statistics Counter -->
                    <div
                        v-if="computedHeroData?.statisticsHighlight?.length"
                        class="hero-statistics"
                        ref="statisticsRef"
                        role="region"
                        aria-label="Platform statistics"
                    >
                        <div v-for="stat in computedHeroData.statisticsHighlight" :key="stat.key" class="hero-stat">
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
                            v-if="computedHeroData?.primaryCTA"
                            class="hero-cta-primary group"
                            :aria-describedby="computedHeroData.primaryCTA.trackingEvent"
                            @click="handleCTAClick(computedHeroData.primaryCTA)"
                            @keydown.enter="handleCTAClick(computedHeroData.primaryCTA)"
                            @keydown.space.prevent="handleCTAClick(computedHeroData.primaryCTA)"
                        >
                            {{ computedHeroData.primaryCTA.text }}
                        </button>

                        <button
                            v-if="computedHeroData?.secondaryCTA"
                            class="hero-cta-secondary group"
                            :aria-describedby="computedHeroData.secondaryCTA.trackingEvent"
                            @click="handleCTAClick(computedHeroData.secondaryCTA)"
                            @keydown.enter="handleCTAClick(computedHeroData.secondaryCTA)"
                            @keydown.space.prevent="handleCTAClick(computedHeroData.secondaryCTA)"
                        >
                            {{ computedHeroData.secondaryCTA.text }}
                        </button>
                    </div>
                </div>

                <!-- Rotating Testimonials -->
                <div
                    v-if="computedHeroData?.testimonialRotation?.length"
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
                            v-for="(testimonial, index) in computedHeroData.testimonialRotation"
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
import AnimatedCounter from '@/Components/ui/AnimatedCounter.vue';
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
const computedHeroData = computed(() => {
    if (!props.heroData) return null;

    // Handle A/B test variants
    const baseData = props.heroData;
    const variant = baseData.abTestVariant || 'default';

    // Apply audience-specific overrides
    const audienceOverrides = baseData.audienceOverrides?.[props.audience] || {};
    const variantOverrides = baseData.variants?.[variant] || {};

    // Ensure employer audience is handled
    const validAudience = ['individual', 'institutional', 'employer'].includes(props.audience) ? props.audience : 'individual';
    const finalAudienceOverrides = baseData.audienceOverrides?.[validAudience] || {};

    return {
        ...baseData,
        ...variantOverrides,
        ...finalAudienceOverrides,
    };
});

const currentTestimonial = computed(() => {
    if (!computedHeroData.value?.testimonialRotation?.length) return null;
    return computedHeroData.value.testimonialRotation[currentTestimonialIndex.value];
});

const backgroundImageUrl = computed(() => {
    if (videoError.value || !computedHeroData.value?.backgroundVideo) {
        return computedHeroData.value?.backgroundImage || '/images/hero-fallback.jpg';
    }
    return computedHeroData.value?.backgroundImage || '/images/hero-fallback.jpg';
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
    if (!computedHeroData.value?.testimonialRotation?.length) return;

    currentTestimonialIndex.value = (currentTestimonialIndex.value + 1) % computedHeroData.value.testimonialRotation.length;
};

const resetTestimonialInterval = () => {
    if (testimonialInterval.value) {
        clearInterval(testimonialInterval.value);
    }

    if (!reducedMotion.value && computedHeroData.value?.testimonialRotation?.length > 1) {
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
    if (computedHeroData.value?.backgroundVideo && videoElement.value) {
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

/* Modern Hero Background with Enhanced Gradients */
.hero-background {
    @apply absolute inset-0 z-0;
}

.hero-video {
    @apply absolute inset-0 h-full w-full object-cover;
}

.hero-background-image {
    @apply absolute inset-0 h-full w-full bg-cover bg-center bg-no-repeat;
    background: 
        radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 25%),
        radial-gradient(circle at 90% 80%, rgba(29, 78, 216, 0.15) 0%, transparent 25%),
        radial-gradient(circle at 50% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 20%),
        linear-gradient(135deg, #667eea 0%, #764ba2 30%, #667eea 100%);
    background-size: 600% 600%, 500% 500%, 400% 400%, 200% 200%;
    animation: 
        gradientShift 25s ease infinite, 
        gradientPulse 10s ease-in-out infinite alternate,
        floatingElements 15s ease-in-out infinite;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%, 0% 50%, 0% 50%, 0% 50%; }
    50% { background-position: 100% 50%, 100% 50%, 100% 50%, 100% 50%; }
    100% { background-position: 0% 50%, 0% 50%, 0% 50%, 0% 50%; }
}

@keyframes gradientPulse {
    0% { opacity: 0.85; transform: scale(1); }
    100% { opacity: 1; transform: scale(1.03); }
}

@keyframes floatingElements {
    0%, 100% {
        background-position: 0% 50%, 0% 50%, 0% 50%, 0% 50%;
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        background-position: 100% 50%, 25% 75%, 50% 25%, 25% 75%;
        transform: translate(-5px, -5px) rotate(1deg);
    }
    50% {
        background-position: 50% 100%, 75% 25%, 100% 50%, 75% 25%;
        transform: translate(0, 0) rotate(0deg);
    }
    75% {
        background-position: 25% 25%, 50% 75%, 25% 75%, 50% 50%;
        transform: translate(5px, -5px) rotate(-1deg);
    }
}

/* Modern Floating Elements */
.floating-elements {
    @apply absolute inset-0 pointer-events-none overflow-hidden;
}

.floating-circle {
    @apply absolute rounded-full opacity-70;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(29, 78, 216, 0.15));
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 
        0 8px 32px rgba(29, 78, 216, 0.15),
        inset 0 4px 4px rgba(255, 255, 255, 0.1),
        inset 0 -4px 4px rgba(0, 0, 0, 0.1);
    animation: floatPulse 6s ease-in-out infinite;
}

.floating-circle-1 {
    width: 120px;
    height: 120px;
    top: 8%;
    left: 5%;
    animation-delay: 0s;
}

.floating-circle-2 {
    width: 80px;
    height: 80px;
    top: 55%;
    right: 10%;
    animation-delay: 1s;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(29, 78, 216, 0.15));
}

.floating-circle-3 {
    width: 60px;
    height: 60px;
    bottom: 15%;
    left: 15%;
    animation-delay: 2s;
    background: linear-gradient(135deg, rgba(29, 78, 216, 0.2), rgba(30, 64, 175, 0.15));
}

.floating-circle-4 {
    width: 100px;
    height: 100px;
    top: 65%;
    right: 25%;
    animation-delay: 3s;
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.2), rgba(59, 130, 246, 0.15));
}

.floating-square {
    @apply absolute rounded-lg opacity-60;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(139, 92, 246, 0.2));
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    transform: rotate(45deg);
    box-shadow: 
        0 6px 24px rgba(59, 130, 246, 0.2),
        inset 0 4px 4px rgba(255, 255, 255, 0.1);
    animation: floatRotate 8s linear infinite;
}

.floating-triangle {
    @apply absolute opacity-50;
    width: 0;
    height: 0;
    border-style: solid;
}

.floating-triangle-1 {
    border-left: 25px solid transparent;
    border-right: 25px solid transparent;
    border-bottom: 43px solid rgba(139, 92, 246, 0.3);
    top: 20%;
    left: 65%;
    animation: floatFloat 12s ease-in-out infinite;
}

.floating-triangle-2 {
    border-left: 20px solid transparent;
    border-right: 20px solid transparent;
    border-bottom: 35px solid rgba(59, 130, 246, 0.3);
    bottom: 25%;
    left: 10%;
    animation: floatFloat 14s ease-in-out infinite;
}

.floating-orb {
    @apply absolute rounded-full opacity-40;
    background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.4), rgba(139, 92, 246, 0.2), transparent);
    backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 
        0 10px 40px rgba(99, 102, 241, 0.2),
        inset 0 8px 8px rgba(255, 255, 255, 0.1);
    animation: floatGlow 10s ease-in-out infinite;
}

.floating-orb-1 {
    width: 140px;
    height: 140px;
    top: 12%;
    right: 5%;
    animation-delay: 0s;
}

.floating-orb-2 {
    width: 70px;
    height: 70px;
    bottom: 12%;
    right: 15%;
    animation-delay: 4s;
}

.floating-orb-3 {
    width: 110px;
    height: 110px;
    top: 45%;
    left: 0%;
    animation-delay: 8s;
}

.floating-square-1 {
    width: 40px;
    height: 40px;
    top: 25%;
    right: 20%;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.25), rgba(139, 92, 246, 0.2));
    animation: floatRotate 10s linear infinite;
}

.floating-square-2 {
    width: 30px;
    height: 30px;
    bottom: 35%;
    right: 5%;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(99, 102, 241, 0.2));
    animation: floatRotate 12s linear infinite;
}

@keyframes floatPulse {
    0%, 100% { 
        transform: translateY(0) translateX(0) scale(1); 
        opacity: 0.6; 
    }
    50% { 
        transform: translateY(-15px) translateX(5px) scale(1.05); 
        opacity: 0.8; 
    }
}

@keyframes floatRotate {
    from { 
        transform: rotate(0deg) translateY(0) translateX(0) rotate(45deg); 
    }
    to { 
        transform: rotate(360deg) translateY(0) translateX(0) rotate(45deg); 
    }
}

@keyframes floatFloat {
    0%, 100% { 
        transform: translateY(0) translateX(0) rotate(0deg); 
    }
    25% { 
        transform: translateY(-10px) translateX(5px) rotate(5deg); 
    }
    50% { 
        transform: translateY(0) translateX(0) rotate(0deg); 
    }
    75% { 
        transform: translateY(10px) translateX(-5px) rotate(-5deg); 
    }
}

@keyframes floatGlow {
    0%, 100% { 
        transform: translateY(0) translateX(0) scale(1); 
        opacity: 0.4; 
        filter: blur(1px); 
    }
    50% { 
        transform: translateY(-10px) translateX(10px) scale(1.1); 
        opacity: 0.6; 
        filter: blur(2px); 
    }
}

.hero-overlay {
    @apply absolute inset-0;
    background: 
        linear-gradient(135deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.4) 50%, rgba(0, 0, 0, 0.2) 100%),
        radial-gradient(ellipse at center, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.3) 100%);
}

.hero-overlay::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(102, 126, 234, 0.15) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(240, 147, 251, 0.12) 0%, transparent 40%),
        radial-gradient(circle at 50% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 30%),
        linear-gradient(45deg, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    pointer-events: none;
    animation: overlayShimmer 12s ease-in-out infinite;
}

@keyframes overlayShimmer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

/* Content */
.hero-content {
    @apply relative z-20 flex flex-col items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8;
    padding-top: 100px;
    padding-bottom: 100px;
}

.hero-container {
    @apply max-w-7xl mx-auto text-center;
    animation: heroContentFadeIn 1.5s ease-out;
}

@keyframes heroContentFadeIn {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

.hero-text {
    @apply mb-16;
}

.hero-headline {
    @apply text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold mb-8;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 40%, #3b82f6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    background-size: 300% 300%;
    line-height: 1.1;
    letter-spacing: -0.02em;
    text-shadow: 0 0 40px rgba(255, 255, 255, 0.1);
    animation: 
        gradientShift 8s ease infinite,
        headlineGlow 4s ease-in-out infinite alternate,
        slideInUp 1.2s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    font-weight: 800;
    text-wrap: balance;
}

@keyframes headlineGlow {
    0% { filter: drop-shadow(0 0 15px rgba(102, 126, 234, 0.4)); }
    100% { filter: drop-shadow(0 0 40px rgba(102, 126, 234, 0.7)); }
}

.hero-subtitle {
    @apply text-lg sm:text-xl md:text-2xl text-gray-200 mb-12 max-w-4xl mx-auto;
    line-height: 1.7;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    animation: subtitleSlideIn 1.5s ease-out 0.3s both;
}

@keyframes subtitleSlideIn {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Statistics */
.hero-statistics {
    @apply grid grid-cols-1 sm:grid-cols-3 gap-8 mb-16 max-w-5xl mx-auto;
    animation: statisticsSlideUp 1.5s ease-out 0.6s both;
}

@keyframes statisticsSlideUp {
    0% { opacity: 0; transform: translateY(40px); }
    100% { opacity: 1; transform: translateY(0); }
}

.hero-stat {
    @apply bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 text-center relative overflow-hidden;
    transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: 
        0 10px 30px -10px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    position: relative;
    z-index: 1;
}

.hero-stat::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    transition: left 0.8s ease;
    z-index: -1;
}

.hero-stat::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, 
        rgba(59, 130, 246, 0.3), 
        rgba(139, 92, 246, 0.3), 
        rgba(59, 130, 246, 0.3));
    border-radius: 1.25rem;
    z-index: -2;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.hero-stat:hover {
    @apply bg-white/20 border-white/30;
    transform: translateY(-10px) scale(1.05);
    box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.hero-stat:hover::after {
    opacity: 1;
}

.hero-stat:hover::before {
    left: 100%;
}

.hero-stat-value {
    @apply text-4xl font-bold mb-3;
    background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 50%, #c7d2fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
    font-weight: 800;
}

.hero-stat-label {
    @apply text-gray-200 text-sm uppercase tracking-wider font-medium;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    font-weight: 600;
}

/* Actions */
.hero-actions {
    @apply flex flex-col sm:flex-row gap-6 justify-center items-center mb-20;
    animation: actionsSlideIn 1.5s ease-out 0.9s both;
}

@keyframes actionsSlideIn {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

.hero-cta-primary {
    @apply relative overflow-hidden font-bold py-5 px-10 rounded-2xl transition-all duration-500 transform focus:outline-none focus:ring-4 focus:ring-primary/50;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.9) 0%, rgba(29, 78, 216, 0.9) 50%, rgba(30, 64, 175, 0.9) 100%);
    background-size: 200% 200%;
    animation: gradientShift 8s ease infinite;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    box-shadow: 
        0 10px 25px -5px rgba(37, 99, 235, 0.3),
        0 8px 10px -6px rgba(37, 99, 235, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    letter-spacing: 0.025em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.hero-cta-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.8s ease;
}

.hero-cta-primary:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 
        0 20px 40px -10px rgba(59, 130, 246, 0.4),
        0 16px 20px -8px rgba(59, 130, 246, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.4);
}

.hero-cta-primary:hover::before {
    left: 100%;
}

.hero-cta-primary:active {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 
        0 5px 15px -3px rgba(59, 130, 246, 0.3),
        0 4px 8px -2px rgba(59, 130, 246, 0.2);
}

.hero-cta-secondary {
    @apply relative overflow-hidden font-bold py-5 px-10 rounded-2xl transition-all duration-500 transform focus:outline-none focus:ring-4 focus:ring-white/30;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    box-shadow: 
        0 8px 24px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    letter-spacing: 0.025em;
    font-weight: 600;
    text-transform: uppercase;
}

.hero-cta-secondary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.8s ease;
}

.hero-cta-secondary:hover {
    transform: translateY(-4px) scale(1.05);
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 
        0 16px 48px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.hero-cta-secondary:hover::before {
    left: 100%;
}

.hero-cta-secondary:active {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

/* Testimonials */
.hero-testimonials {
    @apply max-w-5xl mx-auto relative;
    animation: testimonialsSlideIn 1.5s ease-out 1.2s both;
}

@keyframes testimonialsSlideIn {
    0% { opacity: 0; transform: translateY(40px); }
    100% { opacity: 1; transform: translateY(0); }
}

.hero-testimonial {
    @apply mb-8 text-center;
}

.hero-testimonial-quote {
    @apply text-xl md:text-2xl text-gray-100 mb-8 italic leading-relaxed text-center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    font-weight: 300;
    position: relative;
}

.hero-testimonial-quote::before,
.hero-testimonial-quote::after {
    content: '"';
    @apply text-4xl text-white/40 absolute;
    font-family: serif;
}

.hero-testimonial-quote::before {
    top: -10px;
    left: -20px;
}

.hero-testimonial-quote::after {
    bottom: -20px;
    right: -20px;
}

.hero-testimonial-author {
    @apply flex items-center justify-center gap-6 not-italic;
}

.hero-testimonial-avatar {
    @apply w-16 h-16 rounded-full border-4 border-white/40 shadow-lg object-cover;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 3px rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.hero-testimonial-avatar:hover {
    transform: scale(1.1);
    border-color: rgba(255, 255, 255, 0.6);
}

.hero-testimonial-info {
    @apply text-center;
}

.hero-testimonial-name {
    @apply text-white font-bold text-lg mb-1;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.hero-testimonial-role {
    @apply text-gray-300 text-base font-medium;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Testimonial Navigation */
.hero-testimonial-nav {
    @apply flex justify-center gap-3 mt-12;
}

.hero-testimonial-dot {
    @apply w-4 h-4 rounded-full transition-all duration-500 relative overflow-hidden focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50;
    background: rgba(255, 255, 255, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

.hero-testimonial-dot::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
    border-radius: 50%;
    transition: all 0.4s ease;
    transform: translate(-50%, -50%);
}

.hero-testimonial-dot.active {
    @apply w-10;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.6) 100%);
    border-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

.hero-testimonial-dot.active::before {
    width: 8px;
    height: 8px;
}

.hero-testimonial-dot:hover {
    background: rgba(255, 255, 255, 0.5);
    border-color: rgba(255, 255, 255, 0.4);
    transform: scale(1.2);
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1);
}

.hero-testimonial-dot:hover::before {
    width: 6px;
    height: 6px;
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
    .hero-background-image {
        animation: none;
    }

    .floating-circle-1,
    .floating-circle-2,
    .floating-circle-3,
    .floating-square-1,
    .floating-square-2 {
        animation: none;
    }

    .hero-cta-primary,
    .hero-cta-secondary {
        @apply transform-none;
    }

    .hero-cta-primary:hover,
    .hero-cta-secondary:hover {
        @apply scale-100;
        transform: none;
    }

    .hero-stat:hover {
        transform: none;
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
