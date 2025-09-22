<template>
    <div class="feature-comparison-matrix">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Feature Comparison Matrix</h3>
                <p class="mt-1 text-gray-600">Compare features across different categories and personas</p>
            </div>

            <button
                @click="$emit('close')"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                aria-label="Close comparison matrix"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4 rounded-lg bg-gray-50 p-4">
            <!-- Category Filter -->
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Category:</label>
                <select
                    v-model="selectedCategory"
                    class="rounded-md border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="all">All Categories</option>
                    <option v-for="category in categories" :key="category" :value="category">
                        {{ formatCategory(category) }}
                    </option>
                </select>
            </div>

            <!-- Persona Filter (for individual audience) -->
            <div v-if="audience === 'individual'" class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Persona:</label>
                <select
                    v-model="selectedPersona"
                    class="rounded-md border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="all">All Personas</option>
                    <option v-for="persona in personas" :key="persona" :value="persona">
                        {{ formatPersona(persona) }}
                    </option>
                </select>
            </div>

            <!-- Institution Type Filter (for institutional audience) -->
            <div v-if="audience === 'institutional'" class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Institution:</label>
                <select
                    v-model="selectedInstitutionType"
                    class="rounded-md border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="all">All Types</option>
                    <option v-for="type in institutionTypes" :key="type" :value="type">
                        {{ formatInstitutionType(type) }}
                    </option>
                </select>
            </div>

            <!-- View Toggle -->
            <div class="ml-auto flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">View:</label>
                <div class="flex overflow-hidden rounded-md border border-gray-300">
                    <button
                        @click="viewMode = 'grid'"
                        :class="[
                            'px-3 py-1 text-sm font-medium transition-colors',
                            viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        Grid
                    </button>
                    <button
                        @click="viewMode = 'table'"
                        :class="[
                            'border-l border-gray-300 px-3 py-1 text-sm font-medium transition-colors',
                            viewMode === 'table' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        Table
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="feature in filteredFeatures"
                :key="feature.id"
                class="rounded-lg border border-gray-200 bg-white p-6 transition-shadow hover:shadow-md"
            >
                <!-- Feature Header -->
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex-grow">
                        <h4 class="mb-1 font-semibold text-gray-900">{{ feature.title }}</h4>
                        <p class="line-clamp-2 text-sm text-gray-600">{{ feature.description }}</p>
                    </div>

                    <div class="ml-3 flex-shrink-0">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                getCategoryBadgeClass(getFeatureCategory(feature)),
                            ]"
                        >
                            {{ formatCategory(getFeatureCategory(feature)) }}
                        </span>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="mb-4">
                    <h5 class="mb-2 text-sm font-medium text-gray-900">Key Benefits</h5>
                    <ul class="space-y-1">
                        <li v-for="benefit in feature.benefits?.slice(0, 3)" :key="benefit" class="flex items-start space-x-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            <span class="text-sm text-gray-700">{{ benefit }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Target Audience -->
                <div class="mb-4">
                    <h5 class="mb-2 text-sm font-medium text-gray-900">Target Audience</h5>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="target in getFeatureTargets(feature)"
                            :key="target"
                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                        >
                            {{ target }}
                        </span>
                    </div>
                </div>

                <!-- Usage Stats -->
                <div v-if="feature.usageStats && feature.usageStats.length > 0" class="border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ feature.usageStats[0].label }}</span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ feature.usageStats[0].value.toLocaleString() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-else class="overflow-x-auto">
            <table class="min-w-full overflow-hidden rounded-lg border border-gray-200 bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Feature</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Target Audience</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Key Benefits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usage Stats</th>
                        <th
                            v-if="audience === 'institutional'"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Pricing Tier
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="feature in filteredFeatures" :key="feature.id" class="transition-colors hover:bg-gray-50">
                        <!-- Feature Name -->
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-medium text-gray-900">{{ feature.title }}</div>
                                <div class="line-clamp-2 text-sm text-gray-600">{{ feature.description }}</div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                    getCategoryBadgeClass(getFeatureCategory(feature)),
                                ]"
                            >
                                {{ formatCategory(getFeatureCategory(feature)) }}
                            </span>
                        </td>

                        <!-- Target Audience -->
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="target in getFeatureTargets(feature)"
                                    :key="target"
                                    class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                                >
                                    {{ target }}
                                </span>
                            </div>
                        </td>

                        <!-- Key Benefits -->
                        <td class="px-6 py-4">
                            <ul class="space-y-1 text-sm text-gray-700">
                                <li v-for="benefit in feature.benefits?.slice(0, 2)" :key="benefit" class="flex items-start space-x-1">
                                    <span class="text-green-500">•</span>
                                    <span>{{ benefit }}</span>
                                </li>
                            </ul>
                        </td>

                        <!-- Usage Stats -->
                        <td class="px-6 py-4">
                            <div v-if="feature.usageStats && feature.usageStats.length > 0" class="text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ feature.usageStats[0].value.toLocaleString() }}
                                </div>
                                <div class="text-gray-600">{{ feature.usageStats[0].label }}</div>
                            </div>
                            <span v-else class="text-sm text-gray-400">N/A</span>
                        </td>

                        <!-- Pricing Tier (Institutional only) -->
                        <td v-if="audience === 'institutional'" class="px-6 py-4">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
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

        <!-- Empty State -->
        <div v-if="filteredFeatures.length === 0" class="py-12 text-center">
            <div class="mb-4 text-gray-400">
                <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"
                    ></path>
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No features match your criteria</h3>
            <p class="text-gray-600">Try adjusting your filters to see more features.</p>
        </div>

        <!-- Export Options -->
        <div class="mt-8 border-t border-gray-200 pt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">Showing {{ filteredFeatures.length }} of {{ features.length }} features</div>

                <div class="flex space-x-3">
                    <button
                        @click="exportToCSV"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Export CSV
                    </button>
                    <button
                        @click="printMatrix"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Print
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
        jobs: 'bg-purple-100 text-purple-800',
        events: 'bg-yellow-100 text-yellow-800',
        analytics: 'bg-indigo-100 text-indigo-800',
        admin: 'bg-red-100 text-red-800',
        mobile: 'bg-pink-100 text-pink-800',
        general: 'bg-gray-100 text-gray-800',
    };
    return classMap[category] || 'bg-gray-100 text-gray-800';
};

const getPricingTierBadgeClass = (tier: string): string => {
    const classMap: Record<string, string> = {
        professional: 'bg-green-100 text-green-800',
        enterprise: 'bg-blue-100 text-blue-800',
        custom: 'bg-purple-100 text-purple-800',
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














