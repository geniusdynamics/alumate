<template>
    <section class="pricing-section bg-gradient-to-br from-slate-50 to-blue-50 py-16">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">
                    {{ currentAudience === 'individual' ? 'Choose Your Plan' : 'Enterprise Solutions' }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{
                        currentAudience === 'individual'
                            ? 'Transparent pricing with no hidden fees. Start free and upgrade as you grow.'
                            : 'Scalable solutions for institutions of all sizes. Custom pricing available.'
                    }}
                </p>
            </div>

            <!-- Audience Toggle -->
            <div class="mb-12 flex justify-center">
                <div class="rounded-lg bg-white p-1 shadow-md">
                    <button
                        @click="toggleAudience('individual')"
                        :class="[
                            'rounded-md px-6 py-3 font-medium transition-all duration-200',
                            currentAudience === 'individual' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Individual Alumni
                    </button>
                    <button
                        @click="toggleAudience('institutional')"
                        :class="[
                            'rounded-md px-6 py-3 font-medium transition-all duration-200',
                            currentAudience === 'institutional' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:text-gray-900',
                        ]"
                    >
                        Institutions
                    </button>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="plan in currentPlans"
                    :key="plan.id"
                    :class="[
                        'pricing-card overflow-hidden rounded-xl bg-white shadow-lg transition-all duration-300 hover:shadow-xl',
                        plan.featured ? 'scale-105 transform ring-2 ring-blue-500' : '',
                    ]"
                >
                    <!-- Plan Header -->
                    <div :class="['p-6 text-center', plan.featured ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white' : 'bg-gray-50']">
                        <h3 :class="['mb-2 text-2xl font-bold', plan.featured ? 'text-white' : 'text-gray-900']">
                            {{ plan.name }}
                        </h3>
                        <p :class="['mb-4 text-sm', plan.featured ? 'text-blue-100' : 'text-gray-600']">
                            {{ plan.description }}
                        </p>
                        <div class="pricing-display">
                            <span :class="['text-4xl font-bold', plan.featured ? 'text-white' : 'text-gray-900']">
                                {{ formatPrice(plan.price) }}
                            </span>
                            <span :class="['ml-1 text-sm', plan.featured ? 'text-blue-100' : 'text-gray-600']">
                                {{ plan.billingPeriod }}
                            </span>
                        </div>
                        <div v-if="plan.originalPrice" class="mt-2">
                            <span class="text-sm line-through opacity-75">
                                {{ formatPrice(plan.originalPrice) }}
                            </span>
                            <span class="ml-2 text-sm font-medium"> Save {{ Math.round((1 - plan.price / plan.originalPrice) * 100) }}% </span>
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="p-6">
                        <ul class="mb-8 space-y-3">
                            <li v-for="feature in plan.features" :key="feature.name" class="flex items-start">
                                <CheckIcon v-if="feature.included" class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-green-500" />
                                <XMarkIcon v-else class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-gray-400" />
                                <div>
                                    <span :class="['text-sm', feature.included ? 'text-gray-900' : 'text-gray-500']">
                                        {{ feature.name }}
                                    </span>
                                    <div v-if="feature.limit" class="mt-1 text-xs text-gray-500">
                                        {{ feature.limit }}
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <!-- CTA Button -->
                        <button
                            @click="handlePlanSelection(plan)"
                            :class="[
                                'w-full rounded-lg px-4 py-3 font-medium transition-all duration-200',
                                plan.featured
                                    ? 'bg-blue-600 text-white shadow-md hover:bg-blue-700 hover:shadow-lg'
                                    : 'bg-gray-100 text-gray-900 hover:bg-gray-200',
                            ]"
                        >
                            {{ plan.ctaText }}
                        </button>

                        <!-- Additional Info -->
                        <div v-if="plan.additionalInfo" class="mt-4 text-center">
                            <p class="text-xs text-gray-500">{{ plan.additionalInfo }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison Matrix -->
            <div class="mt-16">
                <div class="mb-8 text-center">
                    <h3 class="mb-4 text-2xl font-bold text-gray-900">Feature Comparison</h3>
                    <p class="text-gray-600">Compare all features across different plans</p>
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Features</th>
                                    <th v-for="plan in currentPlans" :key="plan.id" class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                                        {{ plan.name }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="feature in comparisonFeatures" :key="feature.name" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ feature.name }}
                                        <div v-if="feature.description" class="mt-1 text-xs text-gray-500">
                                            {{ feature.description }}
                                        </div>
                                    </td>
                                    <td v-for="plan in currentPlans" :key="plan.id" class="px-6 py-4 text-center">
                                        <div v-if="getFeatureValue(feature.key, plan)">
                                            <CheckIcon v-if="getFeatureValue(feature.key, plan) === true" class="mx-auto h-5 w-5 text-green-500" />
                                            <span v-else class="text-sm text-gray-900">
                                                {{ getFeatureValue(feature.key, plan) }}
                                            </span>
                                        </div>
                                        <XMarkIcon v-else class="mx-auto h-5 w-5 text-gray-400" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transparent Pricing Notice -->
            <div class="mt-12 text-center">
                <div class="mx-auto max-w-4xl rounded-lg bg-white p-6 shadow-md">
                    <h4 class="mb-3 text-lg font-semibold text-gray-900">Transparent Pricing Promise</h4>
                    <div class="grid grid-cols-1 gap-6 text-sm text-gray-600 md:grid-cols-3">
                        <div class="flex items-center justify-center">
                            <ShieldCheckIcon class="mr-2 h-5 w-5 text-green-500" />
                            No hidden fees
                        </div>
                        <div class="flex items-center justify-center">
                            <CurrencyDollarIcon class="mr-2 h-5 w-5 text-green-500" />
                            Cancel anytime
                        </div>
                        <div class="flex items-center justify-center">
                            <ClockIcon class="mr-2 h-5 w-5 text-green-500" />
                            30-day money back guarantee
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { AudienceType, ComparisonFeature, PricingPlan } from '@/Types/homepage';
import { CheckIcon, ClockIcon, CurrencyDollarIcon, ShieldCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

interface Props {
    audience?: AudienceType;
}

const props = withDefaults(defineProps<Props>(), {
    audience: 'individual',
});

const emit = defineEmits<{
    planSelected: [plan: PricingPlan];
    audienceChanged: [audience: AudienceType];
}>();

const currentAudience = ref<AudienceType>(props.audience);

// Individual Alumni Plans
const individualPlans: PricingPlan[] = [
    {
        id: 'free',
        name: 'Free',
        description: 'Perfect for getting started',
        price: 0,
        billingPeriod: '/month',
        ctaText: 'Start Free',
        featured: false,
        features: [
            { name: 'Basic alumni directory access', included: true, limit: 'Limited search results' },
            { name: 'Profile creation', included: true },
            { name: 'Basic messaging', included: true, limit: '5 messages/month' },
            { name: 'Event notifications', included: true },
            { name: 'Job board access', included: false },
            { name: 'Mentorship matching', included: false },
            { name: 'Advanced networking tools', included: false },
            { name: 'Priority support', included: false },
        ],
        additionalInfo: 'No credit card required',
    },
    {
        id: 'professional',
        name: 'Professional',
        description: 'For active networkers',
        price: 29,
        originalPrice: 39,
        billingPeriod: '/month',
        ctaText: 'Start Free Trial',
        featured: true,
        features: [
            { name: 'Full alumni directory access', included: true },
            { name: 'Advanced profile features', included: true },
            { name: 'Unlimited messaging', included: true },
            { name: 'Event creation & management', included: true },
            { name: 'Job board access', included: true },
            { name: 'Mentorship matching', included: true },
            { name: 'Advanced networking tools', included: true },
            { name: 'Priority support', included: true },
        ],
        additionalInfo: '14-day free trial',
    },
    {
        id: 'executive',
        name: 'Executive',
        description: 'For senior professionals',
        price: 79,
        originalPrice: 99,
        billingPeriod: '/month',
        ctaText: 'Start Free Trial',
        featured: false,
        features: [
            { name: 'Everything in Professional', included: true },
            { name: 'Executive networking events', included: true },
            { name: 'Personal brand building tools', included: true },
            { name: 'Advanced analytics', included: true },
            { name: 'Concierge support', included: true },
            { name: 'Custom integrations', included: true },
            { name: 'Speaking opportunities', included: true },
            { name: 'Board connections', included: true },
        ],
        additionalInfo: '30-day free trial',
    },
];

// Institutional Plans
const institutionalPlans: PricingPlan[] = [
    {
        id: 'professional_inst',
        name: 'Professional',
        description: 'For small institutions',
        price: 2500,
        billingPeriod: '/month',
        ctaText: 'Request Demo',
        featured: false,
        features: [
            { name: 'Up to 5,000 alumni', included: true },
            { name: 'Basic branded app', included: true },
            { name: 'Admin dashboard', included: true },
            { name: 'Basic analytics', included: true },
            { name: 'Email support', included: true },
            { name: 'Custom branding', included: false },
            { name: 'Advanced integrations', included: false },
            { name: 'Dedicated support', included: false },
        ],
        additionalInfo: 'Setup fee may apply',
    },
    {
        id: 'enterprise_inst',
        name: 'Enterprise',
        description: 'For large institutions',
        price: 7500,
        billingPeriod: '/month',
        ctaText: 'Request Demo',
        featured: true,
        features: [
            { name: 'Up to 25,000 alumni', included: true },
            { name: 'Fully branded mobile app', included: true },
            { name: 'Advanced admin dashboard', included: true },
            { name: 'Comprehensive analytics', included: true },
            { name: 'Priority support', included: true },
            { name: 'Custom branding', included: true },
            { name: 'Advanced integrations', included: true },
            { name: 'Dedicated support', included: true },
        ],
        additionalInfo: 'Includes implementation support',
    },
    {
        id: 'custom_inst',
        name: 'Custom',
        description: 'For enterprise institutions',
        price: null,
        billingPeriod: 'Custom pricing',
        ctaText: 'Contact Sales',
        featured: false,
        features: [
            { name: 'Unlimited alumni', included: true },
            { name: 'Multiple branded apps', included: true },
            { name: 'Custom admin features', included: true },
            { name: 'White-label solution', included: true },
            { name: 'Dedicated account manager', included: true },
            { name: 'Custom integrations', included: true },
            { name: 'SLA guarantees', included: true },
            { name: 'On-premise deployment', included: true },
        ],
        additionalInfo: 'Contact us for custom quote',
    },
];

const currentPlans = computed(() => {
    return currentAudience.value === 'individual' ? individualPlans : institutionalPlans;
});

const comparisonFeatures: ComparisonFeature[] = [
    {
        name: 'Alumni Directory Access',
        key: 'directory_access',
        description: 'Search and connect with alumni',
    },
    {
        name: 'Messaging',
        key: 'messaging',
        description: 'Direct messaging with other alumni',
    },
    {
        name: 'Event Management',
        key: 'events',
        description: 'Create and manage networking events',
    },
    {
        name: 'Job Board',
        key: 'job_board',
        description: 'Access to exclusive job opportunities',
    },
    {
        name: 'Mentorship Matching',
        key: 'mentorship',
        description: 'AI-powered mentor matching',
    },
    {
        name: 'Analytics',
        key: 'analytics',
        description: 'Insights and engagement metrics',
    },
    {
        name: 'Support Level',
        key: 'support',
        description: 'Customer support availability',
    },
];

const toggleAudience = (audience: AudienceType) => {
    currentAudience.value = audience;
    emit('audienceChanged', audience);
};

const formatPrice = (price: number | null): string => {
    if (price === null) return 'Custom';
    if (price === 0) return 'Free';
    return `$${price.toLocaleString()}`;
};

const getFeatureValue = (featureKey: string, plan: PricingPlan): string | boolean | null => {
    // Map feature keys to plan features
    const featureMap: Record<string, (plan: PricingPlan) => string | boolean | null> = {
        directory_access: (plan) => {
            if (plan.id === 'free') return 'Limited';
            return true;
        },
        messaging: (plan) => {
            if (plan.id === 'free') return '5/month';
            return 'Unlimited';
        },
        events: (plan) => {
            if (plan.id === 'free') return false;
            return true;
        },
        job_board: (plan) => {
            return plan.features.some((f) => f.name.includes('Job board') && f.included);
        },
        mentorship: (plan) => {
            return plan.features.some((f) => f.name.includes('Mentorship') && f.included);
        },
        analytics: (plan) => {
            if (plan.features.some((f) => f.name.includes('Advanced analytics') && f.included)) return 'Advanced';
            if (plan.features.some((f) => f.name.includes('analytics') && f.included)) return 'Basic';
            return false;
        },
        support: (plan) => {
            if (plan.features.some((f) => f.name.includes('Concierge') && f.included)) return 'Concierge';
            if (plan.features.some((f) => f.name.includes('Priority') && f.included)) return 'Priority';
            if (plan.features.some((f) => f.name.includes('support') && f.included)) return 'Standard';
            return false;
        },
    };

    return featureMap[featureKey]?.(plan) || false;
};

const handlePlanSelection = (plan: PricingPlan) => {
    emit('planSelected', plan);
};

onMounted(() => {
    // Track pricing section view
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'pricing_section_view', {
            audience: currentAudience.value,
        });
    }
});
</script>

<style scoped>
.pricing-card {
    position: relative;
}

.pricing-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.pricing-card:hover::before {
    opacity: 1;
}

.pricing-display {
    display: flex;
    align-items: baseline;
    justify-content: center;
}

@media (max-width: 768px) {
    .pricing-card {
        margin-bottom: 2rem;
    }

    .grid {
        grid-template-columns: 1fr;
    }
}
</style>


