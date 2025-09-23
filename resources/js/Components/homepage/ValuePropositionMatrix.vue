<template>
    <section class="py-16 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ sectionTitle }}
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    {{ sectionSubtitle }}
                </p>
            </div>

            <!-- Value Proposition Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div 
                    v-for="(proposition, index) in valuePropositions" 
                    :key="proposition.audience"
                    class="relative group"
                    :class="{
                        'transform scale-105 z-10': proposition.audience === audience,
                        'opacity-75': proposition.audience !== audience && audience !== 'all'
                    }"
                >
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 h-full border-2"
                         :class="{
                             'border-blue-500 ring-4 ring-blue-100': proposition.audience === audience,
                             'border-gray-200 hover:border-blue-300': proposition.audience !== audience
                         }">
                        <!-- Icon -->
                        <div class="flex items-center justify-center w-16 h-16 rounded-full mb-6"
                             :class="proposition.iconBg">
                            <component :is="proposition.icon" class="w-8 h-8" :class="proposition.iconColor" />
                        </div>

                        <!-- Title -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">
                            {{ proposition.title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            {{ proposition.description }}
                        </p>

                        <!-- Key Benefits -->
                        <div class="space-y-3 mb-8">
                            <div 
                                v-for="benefit in proposition.benefits" 
                                :key="benefit.id"
                                class="flex items-start space-x-3"
                            >
                                <CheckCircleIcon class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                                <span class="text-gray-700 text-sm">{{ benefit.text }}</span>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div 
                                v-for="stat in proposition.stats" 
                                :key="stat.label"
                                class="text-center p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="text-2xl font-bold" :class="proposition.statColor">
                                    {{ stat.value }}
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ stat.label }}
                                </div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <button 
                            @click="handleCTAClick(proposition.audience)"
                            class="w-full py-3 px-6 rounded-lg font-semibold transition-all duration-200"
                            :class="proposition.ctaClass"
                        >
                            {{ proposition.ctaText }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Comparison Table -->
            <div v-if="showComparison" class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-8 py-6 bg-gray-50 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Feature Comparison</h3>
                    <p class="text-gray-600 mt-1">See what's included for each audience</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Features</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-blue-600">Alumni</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-purple-600">Institution</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-green-600">Employer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="feature in comparisonFeatures" :key="feature.name" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ feature.name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <component :is="getFeatureIcon(feature.alumni)" 
                                              :class="getFeatureIconClass(feature.alumni)" 
                                              class="w-5 h-5 mx-auto" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <component :is="getFeatureIcon(feature.institutional)" 
                                              :class="getFeatureIconClass(feature.institutional)" 
                                              class="w-5 h-5 mx-auto" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <component :is="getFeatureIcon(feature.employer)" 
                                              :class="getFeatureIconClass(feature.employer)" 
                                              class="w-5 h-5 mx-auto" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Toggle Comparison Button -->
            <div class="text-center mt-8">
                <button 
                    @click="toggleComparison"
                    class="inline-flex items-center space-x-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200"
                >
                    <component :is="showComparison ? ChevronUpIcon : ChevronDownIcon" class="w-5 h-5" />
                    <span>{{ showComparison ? 'Hide' : 'Show' }} Feature Comparison</span>
                </button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { 
    UserGroupIcon, 
    BuildingOfficeIcon, 
    BriefcaseIcon,
    CheckCircleIcon,
    XMarkIcon,
    MinusIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

interface Props {
    audience: 'individual' | 'institutional' | 'employer' | 'all';
    showComparison?: boolean;
}

interface ValueProposition {
    audience: string;
    title: string;
    description: string;
    icon: any;
    iconBg: string;
    iconColor: string;
    statColor: string;
    benefits: Array<{ id: string; text: string }>;
    stats: Array<{ value: string; label: string }>;
    ctaText: string;
    ctaClass: string;
}

interface ComparisonFeature {
    name: string;
    alumni: 'full' | 'partial' | 'none';
    institutional: 'full' | 'partial' | 'none';
    employer: 'full' | 'partial' | 'none';
}

const props = withDefaults(defineProps<Props>(), {
    audience: 'all',
    showComparison: false
});

const emit = defineEmits<{
    'cta-click': [audience: string];
}>();

const showComparison = ref(props.showComparison);

const sectionTitle = computed(() => {
    switch (props.audience) {
        case 'individual':
            return 'Accelerate Your Career Growth';
        case 'institutional':
            return 'Transform Alumni Engagement';
        case 'employer':
            return 'Access Top Alumni Talent';
        default:
            return 'Powerful Solutions for Every Stakeholder';
    }
});

const sectionSubtitle = computed(() => {
    switch (props.audience) {
        case 'individual':
            return 'Connect with alumni networks, find mentors, and unlock career opportunities through meaningful relationships.';
        case 'institutional':
            return 'Increase alumni engagement by 300% with our comprehensive platform and branded mobile apps.';
        case 'employer':
            return 'Recruit qualified candidates from exclusive alumni networks with advanced filtering and university partnerships.';
        default:
            return 'Discover how our platform creates value for alumni, institutions, and employers through innovative networking solutions.';
    }
});

const valuePropositions = computed((): ValueProposition[] => [
    {
        audience: 'individual',
        title: 'Alumni',
        description: 'Advance your career through meaningful alumni connections and mentorship opportunities.',
        icon: UserGroupIcon,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        statColor: 'text-blue-600',
        benefits: [
            { id: 'networking', text: 'Access to 50,000+ verified alumni' },
            { id: 'mentorship', text: 'AI-powered mentor matching' },
            { id: 'jobs', text: 'Exclusive job opportunities' },
            { id: 'events', text: 'Premium networking events' },
            { id: 'career', text: 'Career advancement tools' }
        ],
        stats: [
            { value: '73%', label: 'Career Growth' },
            { value: '2.3x', label: 'Salary Increase' }
        ],
        ctaText: 'Start Free Trial',
        ctaClass: 'bg-blue-600 hover:bg-blue-700 text-white'
    },
    {
        audience: 'institutional',
        title: 'Institution',
        description: 'Strengthen alumni relationships and increase engagement with comprehensive management tools.',
        icon: BuildingOfficeIcon,
        iconBg: 'bg-purple-100',
        iconColor: 'text-purple-600',
        statColor: 'text-purple-600',
        benefits: [
            { id: 'engagement', text: '300% increase in alumni participation' },
            { id: 'mobile', text: 'Custom branded mobile apps' },
            { id: 'analytics', text: 'Advanced engagement analytics' },
            { id: 'events', text: 'Integrated event management' },
            { id: 'fundraising', text: 'Enhanced fundraising tools' }
        ],
        stats: [
            { value: '300%', label: 'Engagement' },
            { value: '85%', label: 'App Adoption' }
        ],
        ctaText: 'Request Demo',
        ctaClass: 'bg-purple-600 hover:bg-purple-700 text-white'
    },
    {
        audience: 'employer',
        title: 'Employer',
        description: 'Recruit top talent from exclusive alumni networks with advanced filtering and university partnerships.',
        icon: BriefcaseIcon,
        iconBg: 'bg-green-100',
        iconColor: 'text-green-600',
        statColor: 'text-green-600',
        benefits: [
            { id: 'talent', text: 'Access to verified alumni talent pools' },
            { id: 'filtering', text: 'Advanced candidate filtering' },
            { id: 'partnerships', text: 'Direct university partnerships' },
            { id: 'analytics', text: 'Recruitment performance analytics' },
            { id: 'branding', text: 'Employer branding opportunities' }
        ],
        stats: [
            { value: '60%', label: 'Faster Hiring' },
            { value: '4.2x', label: 'Quality Match' }
        ],
        ctaText: 'Start Recruiting',
        ctaClass: 'bg-green-600 hover:bg-green-700 text-white'
    }
]);

const comparisonFeatures = computed((): ComparisonFeature[] => [
    {
        name: 'Alumni Directory Access',
        alumni: 'full',
        institutional: 'full',
        employer: 'partial'
    },
    {
        name: 'Mentorship Matching',
        alumni: 'full',
        institutional: 'partial',
        employer: 'none'
    },
    {
        name: 'Job Board Access',
        alumni: 'full',
        institutional: 'none',
        employer: 'full'
    },
    {
        name: 'Event Management',
        alumni: 'partial',
        institutional: 'full',
        employer: 'partial'
    },
    {
        name: 'Analytics Dashboard',
        alumni: 'partial',
        institutional: 'full',
        employer: 'full'
    },
    {
        name: 'Mobile App',
        alumni: 'full',
        institutional: 'full',
        employer: 'partial'
    },
    {
        name: 'Recruitment Tools',
        alumni: 'none',
        institutional: 'none',
        employer: 'full'
    },
    {
        name: 'Fundraising Tools',
        alumni: 'none',
        institutional: 'full',
        employer: 'none'
    }
]);

const toggleComparison = () => {
    showComparison.value = !showComparison.value;
};

const handleCTAClick = (audience: string) => {
    emit('cta-click', audience);
};

const getFeatureIcon = (level: 'full' | 'partial' | 'none') => {
    switch (level) {
        case 'full':
            return CheckCircleIcon;
        case 'partial':
            return MinusIcon;
        case 'none':
            return XMarkIcon;
        default:
            return XMarkIcon;
    }
};

const getFeatureIconClass = (level: 'full' | 'partial' | 'none') => {
    switch (level) {
        case 'full':
            return 'text-green-500';
        case 'partial':
            return 'text-yellow-500';
        case 'none':
            return 'text-gray-400';
        default:
            return 'text-gray-400';
    }
};
</script>

<style scoped>
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

@media (prefers-reduced-motion: reduce) {
    .transition-all,
    .transition-colors,
    .transform {
        transition: none;
    }
}
</style>