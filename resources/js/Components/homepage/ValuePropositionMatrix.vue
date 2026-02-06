<template>
    <section class="py-20 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 relative overflow-hidden">
        <!-- Enhanced Background decorative elements -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Multiple gradient layers for depth -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/80 via-indigo-50/60 to-cyan-50/80"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-cyan-50/40 via-transparent to-blue-50/40"></div>
            
            <!-- Animated floating orbs -->
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/30 to-indigo-600/30 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-indigo-400/30 to-cyan-600/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-indigo-300/20 to-cyan-300/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            
            <!-- Floating particles -->
            <div class="absolute top-20 left-20 w-2 h-2 bg-blue-400/60 rounded-full animate-bounce" style="animation-delay: 0.5s;"></div>
            <div class="absolute top-40 right-32 w-3 h-3 bg-indigo-400/60 rounded-full animate-bounce" style="animation-delay: 1.5s;"></div>
            <div class="absolute bottom-32 left-1/3 w-2 h-2 bg-indigo-400/60 rounded-full animate-bounce" style="animation-delay: 2.5s;"></div>
            <div class="absolute bottom-20 right-20 w-3 h-3 bg-cyan-400/60 rounded-full animate-bounce" style="animation-delay: 3s;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full border border-blue-200/50 mb-6">
                    <span class="text-sm font-medium text-blue-600">✨ Trusted by 500+ institutions</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-800 bg-clip-text text-transparent mb-6">
                    {{ sectionTitle }}
                </h2>
                <p class="text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
                    {{ sectionSubtitle }}
                </p>
            </div>

            <!-- Value Proposition Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <div 
                    v-for="(proposition, index) in valuePropositions" 
                    :key="proposition.audience"
                    class="relative group proposition-card"
                    :class="{
                        'transform scale-105 z-10': proposition.audience === audience,
                        'opacity-75': proposition.audience !== audience && audience !== 'all'
                    }"
                >
                    <!-- Card glow effect -->
                    <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl blur-xl"
                         :class="proposition.glowClass"></div>
                    
                    <div class="relative bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 p-8 h-full border border-white/30 group-hover:border-white/50 group-hover:transform group-hover:scale-[1.03] group-hover:-translate-y-2 overflow-hidden"
                         :class="{
                             'border-blue-500 ring-4 ring-blue-100 shadow-blue-100/50 shadow-2xl': proposition.audience === audience,
                             'hover:shadow-xl': proposition.audience !== audience
                         }">
                        <!-- Card shine effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out"></div>
                        <!-- Enhanced Icon -->
                        <div class="relative mb-8 z-10">
                            <div class="flex items-center justify-center w-20 h-20 rounded-2xl mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg group-hover:shadow-xl"
                                 :class="proposition.iconBg">
                                <component :is="proposition.icon" class="w-10 h-10 group-hover:scale-110 transition-transform duration-300" :class="proposition.iconColor" />
                            </div>
                            <!-- Enhanced icon glow effect -->
                            <div class="absolute inset-0 w-20 h-20 rounded-2xl opacity-0 group-hover:opacity-70 transition-opacity duration-300 blur-lg"
                                 :class="proposition.iconBg"></div>
                            <!-- Additional glow ring -->
                            <div class="absolute inset-0 w-20 h-20 rounded-2xl opacity-0 group-hover:opacity-30 transition-opacity duration-500 blur-xl scale-150"
                                 :class="proposition.glowClass"></div>
                        </div>

                        <!-- Enhanced Title -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-gray-800 transition-colors duration-300 relative z-10">
                            {{ proposition.title }}
                        </h3>

                        <!-- Enhanced Description -->
                        <p class="text-gray-600 mb-6 leading-relaxed group-hover:text-gray-700 transition-colors duration-300 relative z-10">
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

                        <!-- Enhanced Statistics -->
                        <div class="grid grid-cols-2 gap-4 mb-8 relative z-10">
                            <div 
                                v-for="stat in proposition.stats" 
                                :key="stat.label"
                                class="text-center p-4 bg-gradient-to-br from-white via-gray-50/30 to-white rounded-xl border border-gray-100/50 hover:shadow-xl hover:scale-110 hover:-translate-y-1 transition-all duration-300 group/stat relative overflow-hidden"
                            >
                                <!-- Stat card shine effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover/stat:translate-x-full transition-transform duration-700"></div>
                                <div class="text-3xl font-bold bg-gradient-to-r bg-clip-text text-transparent group-hover/stat:scale-110 transition-transform duration-300 relative z-10" 
                                     :class="proposition.statGradient">
                                    {{ stat.value }}
                                </div>
                                <div class="text-xs text-gray-600 mt-2 font-medium group-hover/stat:text-gray-700 transition-colors duration-300 relative z-10">
                                    {{ stat.label }}
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced CTA Button -->
                        <button 
                            @click="handleCtaClick(proposition.audience)"
                            class="w-full px-6 py-4 rounded-xl font-semibold transition-all duration-300 relative overflow-hidden group/btn shadow-lg hover:shadow-xl hover:scale-105 hover:-translate-y-1"
                            :class="proposition.ctaClass"
                        >
                            <!-- Enhanced button shine effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                            <!-- Button glow effect -->
                            <div class="absolute inset-0 opacity-0 group-hover/btn:opacity-20 transition-opacity duration-300 blur-lg rounded-xl" :class="proposition.glowClass"></div>
                            <span class="relative z-10 group-hover/btn:scale-105 transition-transform duration-300">{{ proposition.ctaText }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Comparison Table -->
            <div v-if="showComparison" class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 backdrop-blur-sm relative group">
                <!-- Table glow effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl"></div>
                
                <div class="px-8 py-6 bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 relative overflow-hidden">
                    <!-- Header shine effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <h3 class="text-xl font-bold text-white relative z-10">Feature Comparison</h3>
                    <p class="text-blue-100 mt-1 relative z-10">See what's included for each audience</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Features</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">Alumni</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">Institution</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent">Employer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="feature in comparisonFeatures" :key="feature.name" class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:via-indigo-50/30 hover:to-cyan-50/50 transition-all duration-300 group/row">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 group-hover/row:text-gray-800 transition-colors duration-300">{{ feature.name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center transform group-hover/row:scale-110 group-hover/row:shadow-lg transition-all duration-300">
                                        <component v-if="getFeatureIcon(feature.alumni)" 
                                                  :is="getFeatureIcon(feature.alumni)" 
                                                  :class="getFeatureIconClass(feature.alumni)" 
                                                  class="w-5 h-5" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center transform group-hover/row:scale-110 group-hover/row:shadow-lg transition-all duration-300">
                                        <component v-if="getFeatureIcon(feature.institutional)" 
                                                  :is="getFeatureIcon(feature.institutional)" 
                                                  :class="getFeatureIconClass(feature.institutional)" 
                                                  class="w-5 h-5" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center transform group-hover/row:scale-110 group-hover/row:shadow-lg transition-all duration-300">
                                        <component v-if="getFeatureIcon(feature.employer)" 
                                                  :is="getFeatureIcon(feature.employer)" 
                                                  :class="getFeatureIconClass(feature.employer)" 
                                                  class="w-5 h-5" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Enhanced Toggle Button -->
            <div class="text-center mt-12">
                <button 
                    @click="toggleComparison"
                    class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group"
                >
                    <!-- Enhanced button shine effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <!-- Button glow effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 via-indigo-400/20 to-cyan-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 blur-lg rounded-xl"></div>
                    <span class="relative z-10 mr-2 group-hover:scale-105 transition-transform duration-300">
                        {{ showComparison ? 'Hide' : 'Show' }} Feature Comparison
                    </span>
                    <ChevronDownIcon 
                        class="w-5 h-5 relative z-10 transition-all duration-300 group-hover:scale-110"
                        :class="{ 'rotate-180': showComparison }"
                    />
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
    statGradient: string;
    glowClass: string;
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
        iconBg: 'bg-gradient-to-br from-blue-100 to-blue-200',
        iconColor: 'text-blue-600',
        statColor: 'text-blue-600',
        statGradient: 'from-blue-600 to-blue-800',
        glowClass: 'from-blue-400/30 to-blue-600/30',
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
        ctaClass: 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-lg hover:shadow-xl'
    },
    {
        audience: 'institutional',
        title: 'Institution',
        description: 'Strengthen alumni relationships and increase engagement with comprehensive management tools.',
        icon: BuildingOfficeIcon,
        iconBg: 'bg-gradient-to-br from-indigo-100 to-indigo-200',
        iconColor: 'text-indigo-600',
        statColor: 'text-indigo-600',
        statGradient: 'from-indigo-600 to-indigo-800',
        glowClass: 'from-indigo-400/30 to-indigo-600/30',
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
        ctaClass: 'bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white shadow-lg hover:shadow-xl'
    },
    {
        audience: 'employer',
        title: 'Employer',
        description: 'Recruit top talent from exclusive alumni networks with advanced filtering and university partnerships.',
        icon: BriefcaseIcon,
        iconBg: 'bg-gradient-to-br from-green-100 to-green-200',
        iconColor: 'text-green-600',
        statColor: 'text-green-600',
        statGradient: 'from-green-600 to-green-800',
        glowClass: 'from-green-400/30 to-green-600/30',
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
        ctaClass: 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white shadow-lg hover:shadow-xl'
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

const handleCtaClick = (audience: string) => {
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