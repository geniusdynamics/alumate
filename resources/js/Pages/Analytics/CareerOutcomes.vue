<template>
    <div class="career-outcomes-analytics">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Career Outcome Analytics</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Comprehensive analysis of alumni career progression and program effectiveness</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Graduation Year </label>
                    <select
                        v-model="filters.graduation_year"
                        @change="loadAnalytics"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">All Years</option>
                        <option v-for="year in filterOptions.graduation_years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Program </label>
                    <select
                        v-model="filters.program"
                        @change="loadAnalytics"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">All Programs</option>
                        <option v-for="program in filterOptions.programs" :key="program" :value="program">
                            {{ program }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Industry </label>
                    <select
                        v-model="filters.industry"
                        @change="loadAnalytics"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">All Industries</option>
                        <option v-for="industry in filterOptions.industries" :key="industry" :value="industry">
                            {{ industry }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Department </label>
                    <select
                        v-model="filters.department"
                        @change="loadAnalytics"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">All Departments</option>
                        <option v-for="dept in filterOptions.departments" :key="dept" :value="dept">
                            {{ dept }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"></div>
        </div>

        <!-- Analytics Content -->
        <div v-else class="space-y-8">
            <!-- Overview Metrics -->
            <OverviewMetrics :data="analytics.overview" />

            <!-- Program Effectiveness -->
            <ProgramEffectiveness :data="analytics.program_effectiveness" />

            <!-- Salary Analysis -->
            <SalaryAnalysis :data="analytics.salary_analysis" />

            <!-- Industry Placement -->
            <IndustryPlacement :data="analytics.industry_placement" />

            <!-- Demographic Outcomes -->
            <DemographicOutcomes :data="analytics.demographic_outcomes" />

            <!-- Career Path Analysis -->
            <CareerPathAnalysis :data="analytics.career_paths" />

            <!-- Trend Analysis -->
            <TrendAnalysis :data="analytics.trends" />
        </div>

        <!-- Export Options -->
        <div class="mt-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Export Data</h2>
            <div class="flex space-x-4">
                <button @click="exportData('csv')" class="rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                    Export CSV
                </button>
                <button @click="exportData('xlsx')" class="rounded-md bg-green-600 px-4 py-2 text-white transition-colors hover:bg-green-700">
                    Export Excel
                </button>
                <button @click="exportData('json')" class="rounded-md bg-purple-600 px-4 py-2 text-white transition-colors hover:bg-purple-700">
                    Export JSON
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import CareerPathAnalysis from '@/components/Analytics/CareerPathAnalysis.vue';
import DemographicOutcomes from '@/components/Analytics/DemographicOutcomes.vue';
import IndustryPlacement from '@/components/Analytics/IndustryPlacement.vue';
import OverviewMetrics from '@/components/Analytics/OverviewMetrics.vue';
import ProgramEffectiveness from '@/components/Analytics/ProgramEffectiveness.vue';
import SalaryAnalysis from '@/components/Analytics/SalaryAnalysis.vue';
import TrendAnalysis from '@/components/Analytics/TrendAnalysis.vue';
import type {
    AnalyticsOverview,
    CareerPaths,
    DemographicOutcome,
    IndustryPlacement as IndustryPlacementData,
    ProgramEffectiveness as ProgramEffectivenessData,
    SalaryAnalysis as SalaryAnalysisData,
    TrendData,
} from '@/types';
import { onMounted, reactive, ref } from 'vue';

interface FilterOptions {
    graduation_years: string[];
    programs: string[];
    departments: string[];
    industries: string[];
    demographic_types: Record<string, string>;
    career_path_types: Record<string, string>;
    trend_types: Record<string, string>;
}

interface Analytics {
    overview: AnalyticsOverview;
    program_effectiveness: ProgramEffectivenessData[];
    salary_analysis: SalaryAnalysisData;
    industry_placement: IndustryPlacementData[];
    demographic_outcomes: DemographicOutcome[];
    career_paths: CareerPaths;
    trends: TrendData[];
}

const loading = ref(true);
const analytics = ref<Analytics>({
    overview: {
        total_graduates: 0,
        employment_rate: 0,
        average_salary: 0,
        time_to_employment: 0,
    },
    program_effectiveness: [],
    salary_analysis: {
        median_salary: 0,
        salary_range: {
            min: 0,
            max: 0,
            percentile_25: 0,
            percentile_75: 0,
        },
        by_industry: [],
        by_experience: [],
    } as SalaryAnalysisData,
    industry_placement: [],
    demographic_outcomes: [],
    career_paths: {
        common_progressions: [],
        industry_transitions: [],
    },
    trends: [],
});

const filterOptions = ref<FilterOptions>({
    graduation_years: [],
    programs: [],
    departments: [],
    industries: [],
    demographic_types: {},
    career_path_types: {},
    trend_types: {},
});

const filters = reactive({
    graduation_year: '',
    program: '',
    industry: '',
    department: '',
});

onMounted(async () => {
    await loadFilterOptions();
    await loadAnalytics();
});

const loadFilterOptions = async () => {
    try {
        const response = await fetch('/api/career-analytics/filter-options');
        const result = await response.json();

        if (result.success) {
            filterOptions.value = result.data;
        }
    } catch (error) {
        console.error('Failed to load filter options:', error);
    }
};

const loadAnalytics = async () => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        const response = await fetch(`/api/career-analytics?${params}`);
        const result = await response.json();

        if (result.success) {
            analytics.value = result.data;
        }
    } catch (error) {
        console.error('Failed to load analytics:', error);
    } finally {
        loading.value = false;
    }
};

const exportData = async (format: 'csv' | 'xlsx' | 'json') => {
    try {
        const response = await fetch('/api/career-analytics/export', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                format,
                data_type: 'overview',
                filters,
            }),
        });

        const result = await response.json();

        if (result.success && result.download_url) {
            // In a real implementation, this would trigger a file download
            window.open(result.download_url, '_blank');
        }
    } catch (error) {
        console.error('Failed to export data:', error);
    }
};
</script>

<style scoped>
.career-outcomes-analytics {
    @apply mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8;
}
</style>
