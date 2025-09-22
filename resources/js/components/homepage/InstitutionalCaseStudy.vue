<template>
    <div class="institutional-case-study overflow-hidden rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="mb-2 text-xl font-bold">{{ caseStudy.title }}</h3>
                    <p class="text-blue-100">{{ caseStudy.institutionName }}</p>
                    <span class="mt-2 inline-flex items-center rounded-full bg-blue-500 px-2.5 py-0.5 text-xs font-medium capitalize text-white">
                        {{ caseStudy.institutionType }}
                    </span>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ formatAlumniCount(caseStudy.alumniCount) }}</div>
                    <div class="text-sm text-blue-100">Alumni</div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Challenge -->
            <div class="mb-6">
                <h4 class="mb-3 flex items-center text-lg font-semibold text-gray-900">
                    <svg class="mr-2 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Challenge
                </h4>
                <p class="leading-relaxed text-gray-700">{{ caseStudy.challenge }}</p>
            </div>

            <!-- Solution -->
            <div class="mb-6">
                <h4 class="mb-3 flex items-center text-lg font-semibold text-gray-900">
                    <svg class="mr-2 h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Solution
                </h4>
                <p class="leading-relaxed text-gray-700">{{ caseStudy.solution }}</p>
            </div>

            <!-- Implementation Timeline -->
            <div class="mb-6" v-if="caseStudy.implementation.length > 0">
                <h4 class="mb-3 flex items-center text-lg font-semibold text-gray-900">
                    <svg class="mr-2 h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Implementation ({{ caseStudy.timeline }})
                </h4>
                <div class="space-y-3">
                    <div v-for="(phase, index) in caseStudy.implementation.slice(0, 3)" :key="index" class="flex items-start">
                        <div class="mr-3 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                            <span class="text-sm font-medium text-blue-600">{{ index + 1 }}</span>
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">{{ phase.phase }}</h5>
                            <p class="text-sm text-gray-600">{{ phase.duration }}</p>
                            <ul class="mt-1 text-sm text-gray-600">
                                <li v-for="activity in phase.activities.slice(0, 2)" :key="activity">• {{ activity }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Grid -->
            <div class="mb-6">
                <h4 class="mb-4 flex items-center text-lg font-semibold text-gray-900">
                    <svg class="mr-2 h-5 w-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"
                        />
                    </svg>
                    Results
                </h4>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div v-for="result in caseStudy.results" :key="result.metric" class="rounded-lg border bg-gray-50 p-4 text-center">
                        <div class="mb-1 text-3xl font-bold text-green-600">+{{ result.improvementPercentage }}%</div>
                        <div class="mb-1 text-sm capitalize text-gray-600">
                            {{ formatMetricLabel(result.metric) }}
                        </div>
                        <div class="text-xs text-gray-500">{{ result.beforeValue }} → {{ result.afterValue }}</div>
                        <div class="mt-1 text-xs text-gray-400">
                            {{ result.timeframe }}
                        </div>
                        <div v-if="result.verified" class="mt-2 flex items-center justify-center text-green-600">
                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-xs">Verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Highlight -->
            <div class="rounded-r-lg border-l-4 border-blue-400 bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Key Success Factor:</strong>
                            {{ caseStudy.engagementIncrease }}% increase in alumni engagement within {{ caseStudy.timeline }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between bg-gray-50 px-6 py-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                        clip-rule="evenodd"
                    />
                </svg>
                Implementation: {{ caseStudy.timeline }}
            </div>
            <button
                @click="$emit('request-demo', caseStudy)"
                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Request Similar Demo
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { InstitutionalCaseStudy } from '@/Types/homepage';

interface Props {
    caseStudy: InstitutionalCaseStudy;
}

defineProps<Props>();

defineEmits<{
    'request-demo': [caseStudy: InstitutionalCaseStudy];
}>();

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
.institutional-case-study {
    @apply border border-gray-200;
}
</style>














