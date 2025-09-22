<template>
    <div class="testimonial-card flex h-full flex-col rounded-lg bg-white p-6 shadow-md transition-shadow hover:shadow-lg">
        <!-- Quote -->
        <div class="mb-6 flex-grow">
            <div class="mb-3 text-blue-600">
                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h4v10h-10z"
                    />
                </svg>
            </div>

            <blockquote class="mb-4 text-lg leading-relaxed text-gray-700">"{{ testimonial.quote }}"</blockquote>
        </div>

        <!-- Author/Institution Info -->
        <div class="border-t pt-4">
            <!-- Individual Alumni -->
            <div v-if="audience === 'individual' && isIndividualTestimonial(testimonial)" class="flex items-start space-x-4">
                <img
                    :src="testimonial.author.profileImage"
                    :alt="`${testimonial.author.name} profile picture`"
                    class="h-12 w-12 flex-shrink-0 rounded-full object-cover"
                    @error="handleImageError"
                />

                <div class="min-w-0 flex-grow">
                    <div class="mb-1 flex items-center justify-between">
                        <h4 class="truncate font-semibold text-gray-900">
                            {{ testimonial.author.name }}
                        </h4>

                        <a
                            v-if="testimonial.author.linkedinUrl"
                            :href="testimonial.author.linkedinUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="ml-2 flex-shrink-0 text-blue-600 hover:text-blue-700"
                            :aria-label="`View ${testimonial.author.name}'s LinkedIn profile`"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                                />
                            </svg>
                        </a>
                    </div>

                    <p class="mb-1 text-sm text-gray-600">{{ testimonial.author.currentRole }} at {{ testimonial.author.currentCompany }}</p>

                    <p class="text-xs text-gray-500">Class of {{ testimonial.author.graduationYear }} • {{ testimonial.author.degree }}</p>

                    <!-- Career Stage Badge -->
                    <span
                        :class="[
                            'mt-2 inline-block rounded-full px-2 py-1 text-xs font-medium',
                            getCareerStageBadgeClass(testimonial.author.careerStage),
                        ]"
                    >
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
                        class="h-12 w-12 flex-shrink-0 object-contain"
                        @error="handleImageError"
                    />

                    <div class="min-w-0 flex-grow">
                        <h4 class="truncate font-semibold text-gray-900">
                            {{ testimonial.institution.name }}
                        </h4>
                        <p class="text-sm text-gray-600">
                            {{ formatInstitutionType(testimonial.institution.type) }} •
                            {{ testimonial.institution.alumniCount?.toLocaleString() }} Alumni
                        </p>
                    </div>
                </div>

                <!-- Administrator Info -->
                <div class="flex items-center space-x-3 border-l-2 border-gray-100 pl-4">
                    <img
                        :src="testimonial.administrator.profileImage"
                        :alt="`${testimonial.administrator.name} profile picture`"
                        class="h-10 w-10 flex-shrink-0 rounded-full object-cover"
                        @error="handleImageError"
                    />

                    <div class="min-w-0 flex-grow">
                        <p class="truncate text-sm font-medium text-gray-900">
                            {{ testimonial.administrator.name }}
                        </p>
                        <p class="truncate text-xs text-gray-600">
                            {{ testimonial.administrator.title }}
                        </p>
                    </div>
                </div>

                <!-- Results Metrics -->
                <div v-if="testimonial.results && testimonial.results.length > 0" class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                    <div v-for="result in testimonial.results.slice(0, 2)" :key="result.metric" class="text-center">
                        <div class="text-lg font-bold text-blue-600">+{{ result.improvementPercentage }}%</div>
                        <div class="text-xs capitalize text-gray-600">
                            {{ result.metric.replace('_', ' ') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Metrics for Individual -->
        <div
            v-if="audience === 'individual' && isIndividualTestimonial(testimonial) && testimonial.metrics && testimonial.metrics.length > 0"
            class="mt-4 border-t border-gray-100 pt-4"
        >
            <div class="grid grid-cols-2 gap-3">
                <div v-for="metric in testimonial.metrics.slice(0, 2)" :key="metric.type" class="text-center">
                    <div class="text-lg font-bold text-green-600">
                        {{ formatMetricValue(metric) }}
                    </div>
                    <div class="text-xs capitalize text-gray-600">
                        {{ metric.type.replace('_', ' ') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Testimonial Button -->
        <div v-if="testimonial.videoTestimonial" class="mt-4 border-t border-gray-100 pt-4">
            <button
                @click="playVideo"
                class="flex w-full items-center justify-center space-x-2 rounded-lg bg-blue-50 px-4 py-2 text-blue-700 transition-colors hover:bg-blue-100"
                :aria-label="`Play video testimonial from ${getTestimonialAuthorName()}`"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                <span class="text-sm font-medium">Watch Video</span>
            </button>
        </div>

        <!-- Featured Badge -->
        <div v-if="testimonial.featured" class="absolute right-4 top-4">
            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
                Featured
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AudienceType, InstitutionTestimonial, SuccessMetric, Testimonial } from '@/types/homepage';

interface Props {
    testimonial: Testimonial | InstitutionTestimonial;
    audience: AudienceType;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    playVideo: [videoUrl: string, title: string];
}>();

// Type guards
const isIndividualTestimonial = (testimonial: Testimonial | InstitutionTestimonial): testimonial is Testimonial => {
    return 'author' in testimonial;
};

const isInstitutionalTestimonial = (testimonial: Testimonial | InstitutionTestimonial): testimonial is InstitutionTestimonial => {
    return 'institution' in testimonial;
};

// Methods
const handleImageError = (event: Event): void => {
    const img = event.target as HTMLImageElement;
    img.src = '/images/placeholder-avatar.png'; // Fallback image
};

const playVideo = (): void => {
    if (props.testimonial.videoTestimonial) {
        const title = getTestimonialAuthorName();
        emit('playVideo', props.testimonial.videoTestimonial, `${title} - Video Testimonial`);
    }
};

const getTestimonialAuthorName = (): string => {
    if (isIndividualTestimonial(props.testimonial)) {
        return props.testimonial.author.name;
    } else if (isInstitutionalTestimonial(props.testimonial)) {
        return props.testimonial.administrator.name;
    }
    return 'Unknown';
};

const formatCareerStage = (stage: string): string => {
    const stageMap: Record<string, string> = {
        recent_grad: 'Recent Grad',
        mid_career: 'Mid-Career',
        senior: 'Senior',
        executive: 'Executive',
    };
    return stageMap[stage] || stage;
};

const formatInstitutionType = (type: string): string => {
    const typeMap: Record<string, string> = {
        university: 'University',
        college: 'College',
        corporate: 'Corporation',
        nonprofit: 'Non-Profit',
    };
    return typeMap[type] || type;
};

const getCareerStageBadgeClass = (stage: string): string => {
    const classMap: Record<string, string> = {
        recent_grad: 'bg-green-100 text-green-800',
        mid_career: 'bg-blue-100 text-blue-800',
        senior: 'bg-purple-100 text-purple-800',
        executive: 'bg-red-100 text-red-800',
    };
    return classMap[stage] || 'bg-gray-100 text-gray-800';
};

const formatMetricValue = (metric: SuccessMetric): string => {
    switch (metric.unit) {
        case 'percentage':
            return `+${metric.value}%`;
        case 'dollar':
            return `$${metric.value.toLocaleString()}`;
        case 'count':
            return metric.value.toString();
        case 'days':
            return `${metric.value} days`;
        default:
            return metric.value.toString();
    }
};
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

.testimonial-card img[src*='placeholder'] {
    opacity: 0.6;
}

/* Focus styles for accessibility */
.testimonial-card button:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.testimonial-card a:focus {
    @apply rounded outline-none ring-2 ring-blue-500 ring-offset-2;
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

