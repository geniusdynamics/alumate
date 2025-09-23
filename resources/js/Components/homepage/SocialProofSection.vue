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
import type { AudienceType } from '@/Types/homepage';
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
    @apply bg-gray-50 py-16;
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














