<template>
    <section class="enterprise-testimonials">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">Trusted by Leading Institutions</h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    Universities, colleges, and organizations worldwide trust our platform to transform their alumni engagement
                </p>
            </div>

            <!-- Featured Testimonial -->
            <div v-if="featuredTestimonial" class="mb-16">
                <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 p-8 text-white">
                    <div class="mx-auto max-w-4xl">
                        <div class="mb-6 flex items-center">
                            <img
                                :src="featuredTestimonial.institution.logo"
                                :alt="`${featuredTestimonial.institution.name} logo`"
                                class="mr-6 h-20 w-20 rounded-lg bg-white object-contain p-2"
                            />
                            <div>
                                <h3 class="mb-2 text-2xl font-bold">{{ featuredTestimonial.institution.name }}</h3>
                                <p class="capitalize text-blue-100">{{ featuredTestimonial.institution.type }}</p>
                                <p class="text-sm text-blue-200">
                                    {{ formatAlumniCount(featuredTestimonial.institution.alumniCount) }} Alumni Network
                                </p>
                            </div>
                        </div>

                        <blockquote class="mb-6 text-xl italic leading-relaxed">"{{ featuredTestimonial.quote }}"</blockquote>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <img
                                    :src="featuredTestimonial.administrator.profileImage"
                                    :alt="`${featuredTestimonial.administrator.name} profile`"
                                    class="mr-4 h-12 w-12 rounded-full border-2 border-blue-300 object-cover"
                                />
                                <div>
                                    <p class="font-semibold">{{ featuredTestimonial.administrator.name }}</p>
                                    <p class="text-sm text-blue-200">{{ featuredTestimonial.administrator.title }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <div v-for="result in featuredTestimonial.results.slice(0, 3)" :key="result.metric" class="text-center">
                                    <div class="text-2xl font-bold">+{{ result.improvementPercentage }}%</div>
                                    <div class="text-sm capitalize text-blue-200">{{ formatMetricLabel(result.metric) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonials Grid -->
            <div class="mb-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <InstitutionalTestimonialCard
                    v-for="testimonial in testimonials"
                    :key="testimonial.id"
                    :testimonial="testimonial"
                    @play-video="openVideoModal"
                />
            </div>

            <!-- Case Studies Section -->
            <div v-if="caseStudies.length > 0" class="mb-12">
                <div class="mb-8 text-center">
                    <h3 class="mb-4 text-2xl font-bold text-gray-900">Success Stories</h3>
                    <p class="text-gray-600">Detailed case studies showing real results from our institutional partners</p>
                </div>

                <div class="grid gap-8 lg:grid-cols-2">
                    <InstitutionalCaseStudy
                        v-for="caseStudy in caseStudies"
                        :key="caseStudy.id"
                        :case-study="caseStudy"
                        @request-demo="handleDemoRequest"
                    />
                </div>
            </div>

            <!-- Call to Action -->
            <div class="rounded-2xl bg-gray-50 p-8 text-center">
                <h3 class="mb-4 text-2xl font-bold text-gray-900">Ready to Transform Your Alumni Engagement?</h3>
                <p class="mx-auto mb-6 max-w-2xl text-gray-600">
                    Join hundreds of institutions that have increased alumni participation by an average of 300% with our platform.
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <button
                        @click="$emit('request-demo')"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Request Demo
                    </button>
                    <button
                        @click="$emit('download-case-studies')"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
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
import type { InstitutionTestimonial, InstitutionalCaseStudy } from '@/types/homepage';
import { ref } from 'vue';
import InstitutionalTestimonialCard from './InstitutionalTestimonialCard.vue';
import InstitutionalVideoModal from './InstitutionalVideoModal.vue';

interface Props {
    testimonials: InstitutionTestimonial[];
    featuredTestimonial?: InstitutionTestimonial;
    caseStudies: InstitutionalCaseStudy[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'request-demo': [data?: any];
    'download-case-studies': [];
}>();

// Video modal state
const isVideoModalOpen = ref(false);
const currentVideoUrl = ref<string>();
const currentVideoTestimonial = ref<InstitutionTestimonial>();

const openVideoModal = (videoUrl: string) => {
    // Find the testimonial that matches this video
    const testimonial =
        props.testimonials.find((t) => t.videoTestimonial === videoUrl) ||
        (props.featuredTestimonial?.videoTestimonial === videoUrl ? props.featuredTestimonial : undefined);

    currentVideoUrl.value = videoUrl;
    currentVideoTestimonial.value = testimonial;
    isVideoModalOpen.value = true;
};

const closeVideoModal = () => {
    isVideoModalOpen.value = false;
    currentVideoUrl.value = undefined;
    currentVideoTestimonial.value = undefined;
};

const handleDemoRequest = (data?: any) => {
    emit('request-demo', data);
};

const formatAlumniCount = (count: number): string => {
    if (count >= 1000000) {
        return `${(count / 1000000).toFixed(1)}M`;
    } else if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}K`;
    }
    return count.toString();
};

const formatMetricLabel = (metric: string): string => {
    return metric.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};
</script>

<style scoped>
.enterprise-testimonials {
    @apply bg-white py-16;
}
</style>
