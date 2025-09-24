<template>
    <div class="feature-comparison-matrix relative overflow-hidden">
        <!-- Background Effects -->
        <div class=\"absolute inset-0 bg-gradient-to-br from-blue-50/80 via-indigo-50/30 to-cyan-50/50\"></div>\n            <div class=\"absolute inset-0 bg-gradient-to-r from-blue-500/5 via-indigo-500/5 to-cyan-500/5\"></div>
        
        <!-- Header -->
        <div class="relative z-10 mb-8 flex items-center justify-between">
            <div class="group">
                <h3 class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 bg-clip-text text-3xl font-bold text-transparent">
                    Feature Comparison Matrix
                </h3>
                <p class="mt-2 text-lg text-gray-600 transition-colors group-hover:text-gray-700">
                    Compare features across different categories and personas
                </p>
            </div>

            <button
                @click="$emit('close')"
                class="group relative overflow-hidden rounded-xl bg-white/80 p-3 text-gray-400 shadow-lg backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:bg-white hover:text-gray-600 hover:shadow-xl"
                aria-label="Close comparison matrix"
            >
                <div class="absolute inset-0 bg-gradient-to-r from-red-500/0 via-red-500/10 to-red-500/0 translate-x-[-100%] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                <svg class="relative z-10 h-6 w-6 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Filters -->
        <div class="relative z-10 mb-8 overflow-hidden rounded-2xl bg-white/80 p-6 shadow-xl backdrop-blur-sm border border-white/20 hover:shadow-2xl transition-shadow duration-300">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-indigo-500/5 to-cyan-500/5"></div>
            
            <div class="relative z-10 flex flex-wrap gap-6">
                <!-- Category Filter -->
                <div class="group flex items-center space-x-3">
                    <label class="text-sm font-semibold text-gray-700 transition-colors group-hover:text-blue-600">Category:</label>
                    <select
                        v-model="selectedCategory"
                        class="rounded-xl border border-gray-200 bg-white/90 px-4 py-2 text-sm font-medium shadow-sm backdrop-blur-sm transition-all duration-300 hover:border-blue-300 hover:shadow-md focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option value="all">All Categories</option>
                        <option v-for="category in categories" :key="category" :value="category">
                            {{ formatCategory(category) }}
                        </option>
                    </select>
                </div>

                <!-- Persona Filter (for individual audience) -->
                <div v-if="audience === 'individual'" class="group flex items-center space-x-3">
                    <label class="text-sm font-semibold text-gray-700 transition-colors group-hover:text-indigo-600">Persona:</label>
                    <select
                        v-model="selectedPersona"
                        class="rounded-xl border border-gray-200 bg-white/90 px-4 py-2 text-sm font-medium shadow-sm backdrop-blur-sm transition-all duration-300 hover:border-indigo-300 hover:shadow-md focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="all">All Personas</option>
                        <option v-for="persona in personas" :key="persona" :value="persona">
                            {{ formatPersona(persona) }}
                        </option>
                    </select>
                </div>

                <!-- Institution Type Filter (for institutional audience) -->
                <div v-if="audience === 'institutional'" class="group flex items-center space-x-3">
                    <label class="text-sm font-semibold text-gray-700 transition-colors group-hover:text-green-600">Institution:</label>
                    <select
                        v-model="selectedInstitutionType"
                        class="rounded-xl border border-gray-200 bg-white/90 px-4 py-2 text-sm font-medium shadow-sm backdrop-blur-sm transition-all duration-300 hover:border-green-300 hover:shadow-md focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20"
                    >
                        <option value="all">All Types</option>
                        <option v-for="type in institutionTypes" :key="type" :value="type">
                            {{ formatInstitutionType(type) }}
                        </option>
                    </select>
                </div>

                <!-- View Toggle -->
                <div class="ml-auto flex items-center space-x-3">
                    <label class="text-sm font-semibold text-gray-700">View:</label>
                    <div class="flex overflow-hidden rounded-xl border border-gray-200 bg-white/90 shadow-sm">
                        <button
                            @click="viewMode = 'grid'"
                            :class="[
                                'group relative overflow-hidden px-4 py-2 text-sm font-semibold transition-all duration-300',
                                viewMode === 'grid' 
                                    ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' 
                                    : 'bg-white text-gray-700 hover:bg-blue-50 hover:text-blue-600',
                            ]"
                        >
                            <div v-if="viewMode !== 'grid'" class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-blue-500/0 translate-x-[-100%] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                            <span class="relative z-10">Grid</span>
                        </button>
                        <button
                            @click="viewMode = 'table'"
                            :class="[
                                'group relative overflow-hidden border-l border-gray-200 px-4 py-2 text-sm font-semibold transition-all duration-300',
                                viewMode === 'table' 
                                    ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' 
                                    : 'bg-white text-gray-700 hover:bg-blue-50 hover:text-blue-600',
                            ]"
                        >
                            <div v-if="viewMode !== 'table'" class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-blue-500/0 translate-x-[-100%] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                            <span class="relative z-10">Table</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="feature in filteredFeatures"
                :key="feature.id"
                class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/80 p-8 shadow-xl backdrop-blur-sm transition-all duration-500 hover:scale-105 hover:shadow-2xl hover:border-blue-300/50"
            >
                <!-- Background Gradient Effect -->
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-indigo-500/5 to-cyan-500/5 opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
                
                <!-- Shine Effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] transition-transform duration-1000 group-hover:translate-x-[100%] skew-x-12"></div>
                
                <div class="relative z-10">
                    <!-- Feature Header -->
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex-grow">
                            <h4 class="mb-2 text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent group-hover:from-blue-600 group-hover:to-indigo-600 transition-all duration-300">{{ feature.title }}</h4>
                            <p class="line-clamp-2 text-sm text-gray-600 leading-relaxed group-hover:text-gray-700 transition-colors duration-300">{{ feature.description }}</p>
                        </div>

                        <div class="ml-3 flex-shrink-0">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold shadow-sm transition-all duration-300 group-hover:scale-110 group-hover:shadow-md',
                                    getCategoryBadgeClass(getFeatureCategory(feature)),
                                ]"
                            >
                                {{ formatCategory(getFeatureCategory(feature)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Benefits -->
                    <div class="mb-6">
                        <h5 class="mb-3 text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">Key Benefits</h5>
                        <ul class="space-y-2">
                            <li v-for="benefit in feature.benefits?.slice(0, 3)" :key="benefit" class="flex items-start space-x-3">
                                <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-500 shadow-sm transition-all duration-300 group-hover:scale-110 group-hover:shadow-md">
                                    <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-600 group-hover:text-gray-700 transition-colors duration-300">{{ benefit }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Target Audience -->
                    <div class="mb-6">
                        <h5 class="mb-3 text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-300">Target Audience</h5>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="target in getFeatureTargets(feature)"
                                :key="target"
                                class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 px-3 py-1.5 text-xs font-semibold text-blue-800 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-md hover:from-blue-200 hover:to-indigo-200"
                            >
                                {{ target }}
                            </span>
                        </div>
                    </div>

                    <!-- Usage Stats -->
                    <div v-if="feature.usageStats && feature.usageStats.length > 0" class="rounded-xl border-t border-gradient-to-r from-gray-200/50 to-gray-300/50 bg-gradient-to-r from-gray-50/50 to-white/50 pt-6">
                        <h5 class="mb-3 text-sm font-bold text-gray-900 group-hover:text-green-600 transition-colors duration-300">Usage Statistics</h5>
                        <div class="text-center">
                            <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent transition-all duration-300 group-hover:scale-110">{{ feature.usageStats[0].value.toLocaleString() }}</div>
                            <div class="text-xs text-gray-600 group-hover:text-gray-700 transition-colors duration-300">{{ feature.usageStats[0].label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-else class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/80 shadow-2xl backdrop-blur-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
            
            <div class="relative z-10 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200/50">
                    <thead class="bg-gradient-to-r from-gray-50/80 to-white/80 backdrop-blur-sm">
                        <tr>
                            <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-blue-600 transition-colors duration-300">Feature</th>
                            <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-indigo-600 transition-colors duration-300">Category</th>
                            <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-green-600 transition-colors duration-300">Target Audience</th>
                            <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-orange-600 transition-colors duration-300">Key Benefits</th>
                            <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-cyan-600 transition-colors duration-300">Usage Stats</th>
                            <th
                                v-if="audience === 'institutional'"
                                class="px-8 py-6 text-left text-xs font-bold uppercase tracking-wider text-gray-700 hover:text-indigo-600 transition-colors duration-300"
                            >
                                Pricing Tier
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/30 bg-white/50">
                        <tr v-for="feature in filteredFeatures" :key="feature.id" class="group transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50 hover:shadow-lg">
                            <!-- Feature Name -->
                            <td class="px-8 py-6">
                                <div class="group-hover:transform group-hover:scale-105 transition-transform duration-300">
                                    <div class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">{{ feature.title }}</div>
                                    <div class="line-clamp-2 text-sm text-gray-600 group-hover:text-gray-700 transition-colors duration-300 leading-relaxed">{{ feature.description }}</div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="px-8 py-6">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold shadow-sm transition-all duration-300 group-hover:scale-110 group-hover:shadow-md',
                                        getCategoryBadgeClass(getFeatureCategory(feature)),
                                    ]"
                                >
                                    {{ formatCategory(getFeatureCategory(feature)) }}
                                </span>
                            </td>

                            <!-- Target Audience -->
                            <td class="px-8 py-6">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="target in getFeatureTargets(feature)"
                                        :key="target"
                                        class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 px-3 py-1.5 text-xs font-semibold text-blue-800 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-md hover:from-blue-200 hover:to-indigo-200"
                                    >
                                        {{ target }}
                                    </span>
                                </div>
                            </td>

                            <!-- Key Benefits -->
                            <td class="px-8 py-6">
                                <ul class="space-y-2">
                                    <li v-for="benefit in feature.benefits?.slice(0, 2)" :key="benefit" class="flex items-start text-sm text-gray-600 group-hover:text-gray-700 transition-colors duration-300">
                                        <div class="mr-3 mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-500 shadow-sm transition-all duration-300 group-hover:scale-110">
                                            <svg class="h-2.5 w-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <span>{{ benefit }}</span>
                                    </li>
                                </ul>
                            </td>

                            <!-- Usage Stats -->
                            <td class="px-8 py-6">
                                <div v-if="feature.usageStats && feature.usageStats.length > 0" class="space-y-2">
                                    <div class="text-sm font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                        {{ feature.usageStats[0].value.toLocaleString() }}
                                    </div>
                                    <div class="text-xs text-gray-600 group-hover:text-gray-700 transition-colors duration-300">{{ feature.usageStats[0].label }}</div>
                                </div>
                                <span v-else class="text-sm text-gray-400">N/A</span>
                            </td>

                            <!-- Pricing Tier (Institutional only) -->
                            <td v-if="audience === 'institutional'" class="px-8 py-6">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold shadow-sm transition-all duration-300 group-hover:scale-110 group-hover:shadow-md',
                                        getPricingTierBadgeClass((feature as InstitutionalFeature).pricingTier),
                                    ]"
                                >
                                    {{ formatPricingTier((feature as InstitutionalFeature).pricingTier) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredFeatures.length === 0" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50 via-white to-blue-50/30 py-20 text-center shadow-xl border border-white/20">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
            
            <div class="relative z-10">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 shadow-lg">
                    <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <h3 class="mb-3 text-xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">No features found</h3>
                <p class="text-gray-600 leading-relaxed">Try adjusting your filters to discover our comprehensive feature set.</p>
            </div>
        </div>

        <!-- Export Options -->
        <div class="mt-12 border-t border-gradient-to-r from-gray-200/50 to-gray-300/50 pt-8">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-600 bg-gradient-to-r from-blue-600/80 to-indigo-600/80 bg-clip-text text-transparent">Showing {{ filteredFeatures.length }} of {{ features.length }} features</div>

                <div class="flex space-x-4">
                    <button
                        @click="exportToCSV"
                        class="group relative overflow-hidden rounded-xl border border-white/20 bg-white/80 px-6 py-3 text-sm font-semibold text-gray-700 shadow-lg backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:shadow-xl hover:border-blue-300/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="absolute inset-0 translate-x-[-100%] bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                        <div class="relative z-10 flex items-center">
                            <svg class="-ml-1 mr-2 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Export CSV
                        </div>
                    </button>
                    <button
                        @click="printMatrix"
                        class="group relative overflow-hidden rounded-xl border border-white/20 bg-white/80 px-6 py-3 text-sm font-semibold text-gray-700 shadow-lg backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:shadow-xl hover:border-green-300/50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-emerald-500/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="absolute inset-0 translate-x-[-100%] bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                        <div class="relative z-10 flex items-center">
                            <svg class="-ml-1 mr-2 h-4 w-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            Print
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AudienceType, InstitutionalFeature, PlatformFeature } from '@/Types/homepage';
import { computed, ref } from 'vue';

interface Props {
    features: (PlatformFeature | InstitutionalFeature)[];
    audience: AudienceType;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    close: [];
}>();

// Reactive state
const selectedCategory = ref('all');
const selectedPersona = ref('all');
const selectedInstitutionType = ref('all');
const viewMode = ref<'grid' | 'table'>('grid');

// Computed properties
const categories = computed(() => {
    const cats = new Set<string>();
    props.features.forEach((feature) => {
        const category = getFeatureCategory(feature);
        if (category) cats.add(category);
    });
    return Array.from(cats).sort();
});

const personas = computed(() => {
    if (props.audience !== 'individual') return [];

    const personaSet = new Set<string>();
    const individualFeatures = props.features as PlatformFeature[];

    individualFeatures.forEach((feature) => {
        feature.targetPersona?.forEach((persona) => {
            personaSet.add(persona.name);
        });
    });

    return Array.from(personaSet).sort();
});

const institutionTypes = computed(() => {
    if (props.audience !== 'institutional') return [];

    const typeSet = new Set<string>();
    const institutionalFeatures = props.features as InstitutionalFeature[];

    institutionalFeatures.forEach((feature) => {
        typeSet.add(feature.targetInstitution);
    });

    return Array.from(typeSet).sort();
});

const filteredFeatures = computed(() => {
    let filtered = [...props.features];

    // Filter by category
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter((feature) => getFeatureCategory(feature) === selectedCategory.value);
    }

    // Filter by persona (individual audience)
    if (props.audience === 'individual' && selectedPersona.value !== 'all') {
        const individualFeatures = filtered as PlatformFeature[];
        filtered = individualFeatures.filter((feature) => feature.targetPersona?.some((persona) => persona.name === selectedPersona.value));
    }

    // Filter by institution type (institutional audience)
    if (props.audience === 'institutional' && selectedInstitutionType.value !== 'all') {
        const institutionalFeatures = filtered as InstitutionalFeature[];
        filtered = institutionalFeatures.filter((feature) => feature.targetInstitution === selectedInstitutionType.value);
    }

    return filtered;
});

// Methods
const getFeatureCategory = (feature: PlatformFeature | InstitutionalFeature): string => {
    if ('category' in feature) {
        return feature.category;
    }
    // For institutional features, derive category from ID or title
    if (feature.id.includes('admin')) return 'admin';
    if (feature.id.includes('app') || feature.id.includes('mobile')) return 'mobile';
    if (feature.id.includes('analytics')) return 'analytics';
    return 'general';
};

const getFeatureTargets = (feature: PlatformFeature | InstitutionalFeature): string[] => {
    if (props.audience === 'individual') {
        const individualFeature = feature as PlatformFeature;
        return individualFeature.targetPersona?.map((p) => p.name) || [];
    } else {
        const institutionalFeature = feature as InstitutionalFeature;
        return [formatInstitutionType(institutionalFeature.targetInstitution)];
    }
};

const formatCategory = (category: string): string => {
    const categoryMap: Record<string, string> = {
        networking: 'Networking',
        mentorship: 'Mentorship',
        jobs: 'Job Board',
        events: 'Events',
        analytics: 'Analytics',
        admin: 'Administration',
        mobile: 'Mobile Apps',
        general: 'General',
    };
    return categoryMap[category] || category.charAt(0).toUpperCase() + category.slice(1);
};

const formatPersona = (persona: string): string => {
    return persona;
};

const formatInstitutionType = (type: string): string => {
    const typeMap: Record<string, string> = {
        university: 'University',
        college: 'College',
        corporate: 'Corporate',
        nonprofit: 'Non-Profit',
    };
    return typeMap[type] || type;
};

const formatPricingTier = (tier: string): string => {
    const tierMap: Record<string, string> = {
        professional: 'Professional',
        enterprise: 'Enterprise',
        custom: 'Custom',
    };
    return tierMap[tier] || tier;
};

const getCategoryBadgeClass = (category: string): string => {
    const classMap: Record<string, string> = {
        networking: 'bg-blue-100 text-blue-800',
        mentorship: 'bg-green-100 text-green-800',
        jobs: 'bg-indigo-100 text-indigo-800',
        events: 'bg-yellow-100 text-yellow-800',
        analytics: 'bg-indigo-100 text-indigo-800',
        admin: 'bg-red-100 text-red-800',
        mobile: 'bg-cyan-100 text-cyan-800',
        general: 'bg-gray-100 text-gray-800',
    };
    return classMap[category] || 'bg-gray-100 text-gray-800';
};

const getPricingTierBadgeClass = (tier: string): string => {
    const classMap: Record<string, string> = {
        professional: 'bg-green-100 text-green-800',
        enterprise: 'bg-blue-100 text-blue-800',
        custom: 'bg-indigo-100 text-indigo-800',
    };
    return classMap[tier] || 'bg-gray-100 text-gray-800';
};

const exportToCSV = (): void => {
    const headers = ['Feature', 'Category', 'Description', 'Target Audience', 'Key Benefits', 'Usage Stats'];

    if (props.audience === 'institutional') {
        headers.push('Pricing Tier');
    }

    const csvContent = [
        headers.join(','),
        ...filteredFeatures.value.map((feature) => {
            const row = [
                `"${feature.title}"`,
                `"${formatCategory(getFeatureCategory(feature))}"`,
                `"${feature.description}"`,
                `"${getFeatureTargets(feature).join('; ')}"`,
                `"${feature.benefits?.join('; ') || ''}"`,
                `"${feature.usageStats?.[0]?.value || 'N/A'}"`,
            ];

            if (props.audience === 'institutional') {
                row.push(`"${formatPricingTier((feature as InstitutionalFeature).pricingTier)}"`);
            }

            return row.join(',');
        }),
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `feature-comparison-${props.audience}.csv`);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const printMatrix = (): void => {
    window.print();
};
</script>

<style scoped>
.feature-comparison-matrix {
    @apply max-w-full;
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth transitions */
.feature-comparison-matrix * {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Focus styles for accessibility */
.feature-comparison-matrix button:focus,
.feature-comparison-matrix select:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Table responsive styles */
@media (max-width: 768px) {
    .feature-comparison-matrix table {
        font-size: 0.875rem;
    }

    .feature-comparison-matrix th,
    .feature-comparison-matrix td {
        @apply px-3 py-2;
    }
}

/* Print styles */
@media print {
    .feature-comparison-matrix {
        @apply bg-white text-black;
    }

    .feature-comparison-matrix button {
        display: none;
    }

    .feature-comparison-matrix .bg-gray-50 {
        background-color: #f9fafb !important;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .feature-comparison-matrix * {
        transition: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .feature-comparison-matrix .bg-gray-50 {
        background-color: #ffffff;
        border: 1px solid #000000;
    }

    .feature-comparison-matrix .text-gray-600 {
        color: #000000;
    }
}
</style>














