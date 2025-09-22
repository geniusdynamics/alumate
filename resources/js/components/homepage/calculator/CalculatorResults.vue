<template>
    <div class="calculator-results" :class="{ visible: isVisible }">
        <!-- Header -->
        <div class="mb-8 text-center">
            <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="mb-2 text-3xl font-bold text-gray-900">Your Career Value Report</h3>
            <p class="mx-auto max-w-2xl text-gray-600">
                Based on your profile and our platform data, here's what alumni networking could mean for your career.
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Salary Increase -->
            <div class="metric-card">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Salary Potential</h4>
                    </div>
                </div>
                <div class="mb-2 text-3xl font-bold text-green-600">
                    +<AnimatedCounter :target="result.projectedSalaryIncrease" :format="formatCurrency" :duration="2000" />
                </div>
                <p class="text-sm text-gray-600">Average increase within {{ result.careerAdvancementTimeline }}</p>
                <div class="mt-3 text-xs text-gray-500">Based on {{ getSimilarAlumniCount() }} similar alumni profiles</div>
            </div>

            <!-- Success Probability -->
            <div class="metric-card">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Success Rate</h4>
                    </div>
                </div>
                <div class="mb-2 text-3xl font-bold text-blue-600">
                    <AnimatedCounter :target="result.successProbability" suffix="%" :duration="1500" />
                </div>
                <p class="text-sm text-gray-600">Likelihood of achieving your goals</p>
                <div class="mt-3">
                    <div class="h-2 w-full rounded-full bg-gray-200">
                        <div
                            class="h-2 rounded-full bg-blue-600 transition-all duration-1000"
                            :style="{ width: `${result.successProbability}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- ROI Estimate -->
            <div class="metric-card">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100">
                            <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                ></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">ROI</h4>
                    </div>
                </div>
                <div class="mb-2 text-3xl font-bold text-purple-600">
                    <AnimatedCounter :target="result.roiEstimate" suffix="x" :duration="1800" :decimals="1" />
                </div>
                <p class="text-sm text-gray-600">Return on networking investment</p>
                <div class="mt-3 text-xs text-gray-500">Over 2-year period</div>
            </div>
        </div>

        <!-- Networking Value -->
        <div class="mb-8 rounded-xl bg-gradient-to-r from-blue-50 to-purple-50 p-6">
            <h4 class="mb-4 text-xl font-bold text-gray-900">Your Networking Value</h4>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h5 class="mb-2 font-semibold text-gray-800">Professional Connections</h5>
                    <p class="mb-4 text-sm text-gray-600">{{ result.networkingValue }}</p>

                    <h5 class="mb-2 font-semibold text-gray-800">Career Timeline</h5>
                    <p class="text-sm text-gray-600">{{ result.careerAdvancementTimeline }}</p>
                </div>
                <div>
                    <h5 class="mb-2 font-semibold text-gray-800">Key Benefits</h5>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            Access to hidden job market
                        </li>
                        <li class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            Mentorship opportunities
                        </li>
                        <li class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            Industry insights and trends
                        </li>
                        <li class="flex items-center">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            Referrals and recommendations
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Personalized Recommendations -->
        <div class="mb-8">
            <h4 class="mb-6 text-xl font-bold text-gray-900">Personalized Recommendations</h4>
            <div class="space-y-4">
                <div
                    v-for="(recommendation, index) in result.personalizedRecommendations"
                    :key="index"
                    class="recommendation-card"
                    :class="getPriorityClass(recommendation.priority)"
                >
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="priority-badge" :class="getPriorityBadgeClass(recommendation.priority)">
                                {{ recommendation.priority.toUpperCase() }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <h5 class="mb-1 font-semibold text-gray-900">{{ recommendation.category }}</h5>
                            <p class="mb-2 text-gray-700">{{ recommendation.action }}</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span>Timeline: {{ recommendation.timeframe }}</span>
                                <span>•</span>
                                <span>Expected: {{ recommendation.expectedOutcome }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Report -->
        <div class="mb-8 rounded-xl bg-gray-50 p-6">
            <div class="text-center">
                <h4 class="mb-2 text-lg font-bold text-gray-900">Get Your Detailed Report</h4>
                <p class="mb-4 text-gray-600">Receive a comprehensive PDF report with personalized action steps and industry benchmarks.</p>

                <div v-if="!emailSubmitted" class="mx-auto max-w-md">
                    <div class="flex space-x-2">
                        <input
                            v-model="emailAddress"
                            type="email"
                            placeholder="Enter your email address"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-500': emailError }"
                        />
                        <button
                            @click="handleEmailReport"
                            :disabled="isEmailLoading || !emailAddress"
                            class="rounded-lg bg-blue-600 px-6 py-2 font-semibold text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            <span v-if="!isEmailLoading">Send Report</span>
                            <span v-else class="flex items-center">
                                <svg
                                    class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Sending...
                            </span>
                        </button>
                    </div>
                    <p v-if="emailError" class="mt-2 text-sm text-red-600">{{ emailError }}</p>
                </div>

                <div v-else class="text-center">
                    <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="font-medium text-green-600">Report sent successfully!</p>
                    <p class="mt-1 text-sm text-gray-500">Check your email for the detailed career report.</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col justify-center gap-4 sm:flex-row">
            <button
                @click="emit('restart')"
                class="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-600 transition-colors duration-200 hover:border-gray-400 hover:text-gray-800"
            >
                Calculate Again
            </button>
            <button
                class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-3 font-semibold text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-purple-700 hover:shadow-xl"
            >
                Start Your Free Trial
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue';
import type { CalculationResult, CalculatorInput } from '@/types/homepage';
import { onMounted, ref } from 'vue';

// Props
interface Props {
    result: CalculationResult;
    formData: CalculatorInput;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    restart: [];
    'email-report': [email: string];
}>();

// Reactive state
const emailAddress = ref('');
const emailError = ref('');
const isEmailLoading = ref(false);
const emailSubmitted = ref(false);
const isVisible = ref(false);

// Methods
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const getSimilarAlumniCount = (): number => {
    // Mock calculation based on form data
    const baseCount = 150;
    const industryMultiplier = getIndustryMultiplier(props.formData.industry);
    const experienceMultiplier = Math.max(0.5, props.formData.experienceYears / 10);

    return Math.round(baseCount * industryMultiplier * experienceMultiplier);
};

const getIndustryMultiplier = (industry: string): number => {
    const multipliers: Record<string, number> = {
        technology: 1.5,
        finance: 1.3,
        consulting: 1.2,
        healthcare: 1.1,
        education: 0.9,
        nonprofit: 0.8,
    };
    return multipliers[industry] || 1.0;
};

const getPriorityClass = (priority: string): string => {
    const classes = {
        high: 'border-l-4 border-red-500 bg-red-50',
        medium: 'border-l-4 border-yellow-500 bg-yellow-50',
        low: 'border-l-4 border-green-500 bg-green-50',
    };
    return classes[priority as keyof typeof classes] || classes.medium;
};

const getPriorityBadgeClass = (priority: string): string => {
    const classes = {
        high: 'bg-red-100 text-red-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-green-100 text-green-800',
    };
    return classes[priority as keyof typeof classes] || classes.medium;
};

const validateEmail = (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

const handleEmailReport = async () => {
    emailError.value = '';

    if (!emailAddress.value) {
        emailError.value = 'Email address is required';
        return;
    }

    if (!validateEmail(emailAddress.value)) {
        emailError.value = 'Please enter a valid email address';
        return;
    }

    isEmailLoading.value = true;

    try {
        emit('email-report', emailAddress.value);
        emailSubmitted.value = true;
    } catch (error) {
        emailError.value = 'Failed to send report. Please try again.';
    } finally {
        isEmailLoading.value = false;
    }
};

// Lifecycle
onMounted(() => {
    // Trigger entrance animation
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<style scoped>
.metric-card {
    @apply rounded-xl border border-gray-100 bg-white p-6 shadow-sm;
}

.recommendation-card {
    @apply rounded-lg border border-gray-100 bg-white p-4 shadow-sm;
}

.priority-badge {
    @apply rounded-full px-2 py-1 text-xs font-semibold;
}

/* Animation for metrics */
.calculator-results {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease-out;
}

.calculator-results.visible {
    opacity: 1;
    transform: translateY(0);
}

.metric-card {
    opacity: 0;
    transform: translateY(30px);
    animation: slideUpFade 0.8s ease-out forwards;
}

.metric-card:nth-child(1) {
    animation-delay: 0.2s;
}

.metric-card:nth-child(2) {
    animation-delay: 0.4s;
}

.metric-card:nth-child(3) {
    animation-delay: 0.6s;
}

.metric-card:nth-child(4) {
    animation-delay: 0.8s;
}

@keyframes slideUpFade {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Recommendation cards animation */
.recommendation-card {
    opacity: 0;
    transform: translateX(-20px);
    animation: slideInLeft 0.6s ease-out forwards;
}

.recommendation-card:nth-child(1) {
    animation-delay: 1.2s;
}

.recommendation-card:nth-child(2) {
    animation-delay: 1.4s;
}

.recommendation-card:nth-child(3) {
    animation-delay: 1.6s;
}

.recommendation-card:nth-child(4) {
    animation-delay: 1.8s;
}

.recommendation-card:nth-child(5) {
    animation-delay: 2s;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Hover effects */
.metric-card {
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.recommendation-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.recommendation-card:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
</style>

