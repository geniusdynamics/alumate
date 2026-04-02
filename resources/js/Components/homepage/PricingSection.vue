<template>
    <section class="pricing-section py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-50/80 via-blue-50/40 to-indigo-50/30"></div>
        <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/50 to-blue-100/40"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-blue-400/10 via-transparent to-transparent"></div>
        
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center relative z-10">
                <h2 class="mb-4 text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    {{ currentAudience === 'individual' ? 'Choose Your Plan' : 'Enterprise Solutions' }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-700">
                    {{
                        currentAudience === 'individual'
                            ? 'Transparent pricing with no hidden fees. Start free and upgrade as you grow.'
                            : 'Scalable solutions for institutions of all sizes. Custom pricing available.'
                    }}
                </p>
            </div>

            <!-- Audience Toggle -->
            <div class="mb-12 flex justify-center">
                <div class="rounded-2xl bg-white/20 backdrop-blur-xl p-1 border border-white/30 shadow-xl">
                    <button
                        @click="toggleAudience('individual')"
                        :class="[
                            'rounded-2xl px-6 py-3 font-medium transition-all duration-300 backdrop-blur-xl border',
                            currentAudience === 'individual' 
                                ? 'bg-gradient-to-r from-blue-600/30 to-purple-600/30 text-white border-white/30 shadow-lg' 
                                : 'bg-white/10 text-gray-300 border-transparent hover:bg-white/20 hover:text-white',
                        ]"
                    >
                        Individual Alumni
                    </button>
                    <button
                        @click="toggleAudience('institutional')"
                        :class="[
                            'rounded-2xl px-6 py-3 font-medium transition-all duration-300 backdrop-blur-xl border',
                            currentAudience === 'institutional' 
                                ? 'bg-gradient-to-r from-blue-600/30 to-purple-600/30 text-white border-white/30 shadow-lg' 
                                : 'bg-white/10 text-gray-300 border-transparent hover:bg-white/20 hover:text-white',
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
                        'pricing-card overflow-hidden rounded-2xl bg-white/20 backdrop-blur-xl shadow-xl transition-all duration-500 hover:shadow-2xl border border-white/30',
                        plan.featured ? 'scale-105 transform border-2 border-blue-500/50 relative overflow-hidden' : '',
                    ]"
                >
                    <div v-if="plan.featured" class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-white/10 rounded-2xl z-0"></div>
                    <div class="relative z-10">
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
                                'w-full rounded-2xl px-4 py-3 font-medium transition-all duration-300 border backdrop-blur-xl',
                                plan.highlighted
                                    ? 'bg-gradient-to-r from-blue-600/30 to-indigo-600/30 text-white border-white/30 shadow-lg' 
                                    : 'bg-white/20 backdrop-blur-xl text-gray-700 border-white/30 hover:shadow-lg',
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
            </div>

            <!-- Feature Comparison Matrix -->
            <div class="mt-16">
                <div class="mb-8 text-center">
                    <h3 class="mb-4 text-2xl font-bold text-gray-900">Feature Comparison</h3>
                    <p class="text-gray-600">Compare all features across different plans</p>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white/20 backdrop-blur-xl border border-white/30 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-white/30 backdrop-blur-sm">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Features</th>
                                    <th v-for="plan in currentPlans" :key="plan.id" class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                                        {{ plan.name }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/20">
                                <tr v-for="feature in comparisonFeatures" :key="feature.name" class="hover:bg-white/10 transition-colors duration-200">
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
                <div class="mx-auto max-w-4xl rounded-2xl bg-white/20 backdrop-blur-xl p-6 shadow-xl border border-white/30">
                    <h4 class="mb-3 text-lg font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Transparent Pricing Promise</h4>
                    <div class="grid grid-cols-1 gap-6 text-sm text-gray-800 md:grid-cols-3">
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
import type { AudienceType, ComparisonFeature, PricingPlan } from '@/types/homepage';
import { CheckIcon, ClockIcon, CurrencyDollarIcon, ShieldCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

interface Props {
    audience?: AudienceType;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    planSelected: [plan: PricingPlan];
    audienceChanged: [audience: AudienceType];
}>();

const currentAudience = ref<AudienceType>(props.audience || 'individual');

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
    overflow: hidden;
}

.pricing-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
    opacity: 0;
    transition: opacity 0.5s ease;
    z-index: 10;
}

.pricing-card:hover::before {
    opacity: 1;
}

.pricing-display {
    display: flex;
    align-items: baseline;
    justify-content: center;
}

/* Enhanced glassmorphism effects */
.glass-effect {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
}

/* Floating animation for interactive elements */
@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-5px);
    }
    100% {
        transform: translateY(0px);
    }
}

.glass-button {
    transition: all 0.3s ease;
    animation: float 3s ease-in-out infinite;
}

.glass-button:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .pricing-card {
        margin-bottom: 2rem;
    }

    .grid {
        grid-template-columns: 1fr;
    }
    
    .pricing-card:hover {
        transform: scale(1) !important; /* Prevent scale on mobile for better UX */
    }
}
</style>
