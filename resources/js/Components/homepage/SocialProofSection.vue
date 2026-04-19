<template>
    <section class="social-proof-section relative overflow-hidden" ref="sectionRef">
        <!-- Enhanced Background with Multiple Gradient Layers -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-50/80 via-blue-50/40 to-indigo-50/30"></div>
        <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/50 to-blue-100/40"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-blue-400/10 via-transparent to-transparent"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Floating Orbs -->
            <div class="absolute top-20 left-10 w-32 h-32 rounded-full blur-xl animate-blob"></div>
            <div class="absolute top-40 right-20 w-24 h-24 rounded-full blur-lg animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-32 left-1/4 w-20 h-20 rounded-full blur-lg animate-blob animation-delay-4000"></div>
            
            <!-- Floating Particles -->
            <div class="absolute top-1/4 left-1/3 w-2 h-2 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-full animate-ping animation-delay-1000"></div>
            <div class="absolute top-1/2 right-1/4 w-1.5 h-1.5 bg-gradient-to-r from-indigo-400 to-cyan-400 rounded-full animate-ping animation-delay-3000"></div>
            <div class="absolute bottom-1/3 left-1/2 w-1 h-1 bg-gradient-to-r from-cyan-400 to-blue-400 rounded-full animate-ping animation-delay-5000"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <!-- Platform Statistics -->
            <PlatformStatistics
                :audience="audience"
                :title="statisticsTitle"
                :subtitle="statisticsSubtitle"
                :auto-fetch="true"
                ref="platformStatisticsRef"
            />

            <!-- Testimonials Carousel -->
            <div class="mt-20 relative">
                <!-- Section Divider -->
                <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 w-24 h-1 bg-gradient-to-r from-blue-400 via-indigo-400 to-cyan-400 rounded-full"></div>
                
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
            <div class="mt-20 relative">
                <!-- Section Divider -->
                <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 w-24 h-1 bg-gradient-to-r from-green-400 via-blue-400 to-indigo-400 rounded-full"></div>
                
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
import type { AudienceType } from '@/types/homepage';
import { computed, ref } from 'vue';
import PlatformStatistics from './PlatformStatistics.vue';
import TestimonialsCarousel from './TestimonialsCarousel.vue';
import TrustBadges from './TrustBadges.vue';

interface Props {
    audience: AudienceType;
}

const props = defineProps<Props>();

// Template refs
const sectionRef = ref<HTMLElement>();
const platformStatisticsRef = ref<InstanceType<typeof PlatformStatistics>>();
const testimonialsCarouselRef = ref<InstanceType<typeof TestimonialsCarousel>>();
const trustBadgesRef = ref<InstanceType<typeof TrustBadges>>();

// Computed properties for audience-specific content
const statisticsTitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Trusted by Leading Institutions';
        case 'employer':
            return 'Trusted by Top Employers';
        default:
            return 'Trusted by Alumni Worldwide';
    }
});

const statisticsSubtitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'See how universities and organizations are transforming alumni engagement';
        case 'employer':
            return 'Discover how leading companies are finding and hiring top talent through alumni networks';
        default:
            return 'Join thousands of professionals advancing their careers through meaningful connections';
    }
});

const testimonialsTitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'What Institutions Say';
        case 'employer':
            return 'What Employers Say';
        default:
            return 'What Our Alumni Say';
    }
});

const testimonialsSubtitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Hear from administrators who have transformed their alumni communities';
        case 'employer':
            return 'Hear from HR leaders and recruiters who have found exceptional talent through our platform';
        default:
            return 'Hear from professionals who have transformed their careers through our platform';
    }
});

const trustBadgesTitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Enterprise Security & Compliance';
        case 'employer':
            return 'Enterprise-Grade Security';
        default:
            return 'Trusted & Secure';
    }
});

const trustBadgesSubtitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Meeting the highest standards for institutional data protection and compliance';
        case 'employer':
            return 'Secure talent acquisition with enterprise-grade data protection and compliance standards';
        default:
            return 'Your data is protected by industry-leading security standards';
    }
});

const companyLogosTitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Trusted by Leading Organizations';
        case 'employer':
            return 'Trusted by Industry Leaders';
        default:
            return 'Alumni Work At';
    }
});

const companyLogosSubtitle = computed(() => {
    switch (props.audience) {
        case 'institutional':
            return 'Join institutions that trust us with their alumni communities';
        case 'employer':
            return 'Join companies that have successfully hired through our platform';
        default:
            return 'Join professionals from leading companies worldwide';
    }
});

// Methods
const refreshStatistics = async (): Promise<void> => {
    if (platformStatisticsRef.value) {
        await platformStatisticsRef.value.refresh();
    }
};

const refreshTestimonials = async (): Promise<void> => {
    if (testimonialsCarouselRef.value) {
        await testimonialsCarouselRef.value.refresh();
    }
};

const refreshTrustBadges = async (): Promise<void> => {
    if (trustBadgesRef.value) {
        await trustBadgesRef.value.refresh();
    }
};

const refreshAll = async (): Promise<void> => {
    await Promise.all([refreshStatistics(), refreshTestimonials(), refreshTrustBadges()]);
};

// Expose methods for parent Components
defineExpose({
    refreshStatistics,
    refreshTestimonials,
    refreshTrustBadges,
    refreshAll,
    platformStatistics: platformStatisticsRef,
    testimonialsCarousel: testimonialsCarouselRef,
    trustBadges: trustBadgesRef,
});
</script>

<style scoped>
.social-proof-section {
    @apply py-20;
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.95) 0%, 
        rgba(248, 250, 252, 0.95) 25%, 
        rgba(241, 245, 249, 0.95) 50%, 
        rgba(230, 230, 250, 0.9) 75%, 
        rgba(253, 244, 255, 0.9) 100%);
    min-height: 100vh;
    backdrop-filter: blur(8px);
}

/* Enhanced smooth transitions */
.social-proof-section * {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Blob animation for floating elements */
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
        background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
        background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
        background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
        background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
    }
}

/* Ping animation for particles */
@keyframes ping {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}

.social-proof-section .animate-blob {
    animation: blob 7s infinite;
    background: radial-gradient(circle, 
        rgba(99, 102, 241, 0.3) 0%, 
        rgba(139, 92, 246, 0.2) 25%, 
        rgba(139, 92, 246, 0.1) 50%, 
        transparent 70%);
}

.social-proof-section .animate-ping {
    animation: ping 2s cubic-bezier(0,0,0.2,1) infinite;
}

.animation-delay-1000 {
    animation-delay: 1s;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-3000 {
    animation-delay: 3s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.animation-delay-5000 {
    animation-delay: 5s;
}

/* Shine effect for section dividers */
.social-proof-section .bg-gradient-to-r {
    position: relative;
    overflow: hidden;
}

.social-proof-section .bg-gradient-to-r::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .social-proof-section {
        @apply py-16;
        min-height: auto;
    }
    
    .social-proof-section .absolute.w-20 {
        width: 5rem;
        height: 5rem;
    }
    
    .social-proof-section .absolute.w-24 {
        @apply w-16 h-16;
    }
    
    .social-proof-section .absolute.w-20 {
        width: 3rem;
        height: 3rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .social-proof-section .absolute,
    .social-proof-section .bg-gradient-to-r::before {
        animation: none;
    }
    
    .social-proof-section * {
        transition: none;
    }
}
</style>














