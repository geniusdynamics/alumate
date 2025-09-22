<template>
    <div class="program-effectiveness">
        <h2 class="mb-6 text-xl font-semibold text-gray-900 dark:text-white">Program Effectiveness</h2>

        <div v-if="data && data.length > 0" class="space-y-6">
            <!-- Summary Cards -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <MetricCard title="Top Performing Program" :value="topProgram?.program_name || 'N/A'" icon="🏆" color="green" />
                <MetricCard title="Average Employment Rate" :value="`${averageEmploymentRate}%`" icon="📈" color="blue" />
                <MetricCard title="Programs Analyzed" :value="data.length" icon="📚" color="purple" />
            </div>

            <!-- Program Effectiveness Table -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Program Performance Comparison</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Program
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Graduation Year
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Employment Rate (1 Year)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Avg Starting Salary
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Effectiveness Score
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Grade
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr
                                v-for="program in sortedPrograms"
                                :key="`${program.program_name}-${program.graduation_year}`"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ program.program_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ program.department }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ program.graduation_year }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="mr-2 h-2 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div class="h-2 rounded-full bg-blue-600" :style="{ width: `${program.employment_rate_1_year}%` }"></div>
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-white"> {{ program.employment_rate_1_year }}% </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ formatCurrency(program.avg_starting_salary) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="mr-2 h-2 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-2 rounded-full bg-green-600"
                                                :style="{ width: `${program.overall_effectiveness_score}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ program.overall_effectiveness_score }}
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span :class="getGradeColor(program.performance_grade)" class="rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ program.performance_grade }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Analysis -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Employment Trends -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Employment Rate Trends</h3>
                    <div class="space-y-4">
                        <div v-for="program in topPerformingPrograms" :key="program.program_name" class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ program.program_name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ program.employment_trend }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ program.employment_rate_1_year }}%</div>
                                <div class="text-xs" :class="getTrendColor(program.employment_trend)">
                                    {{ getTrendIcon(program.employment_trend) }} {{ program.employment_trend }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Performance -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Salary Performance</h3>
                    <div class="space-y-4">
                        <div v-for="program in topSalaryPrograms" :key="program.program_name" class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ program.program_name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Growth: {{ program.salary_growth_rate }}%</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(program.avg_starting_salary) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">→ {{ formatCurrency(program.avg_salary_2_years) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <div class="text-gray-500 dark:text-gray-400">
                <div class="mb-4 text-4xl">📊</div>
                <h3 class="mb-2 text-lg font-medium">No Program Data Available</h3>
                <p class="text-sm">Program effectiveness data will appear here once analytics are generated.</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import MetricCard from '@/components/Analytics/MetricCard.vue';
import { computed } from 'vue';

interface ProgramData {
    program_name: string;
    department: string;
    graduation_year: string;
    total_graduates: number;
    employment_rate_1_year: number;
    employment_rate_2_years: number;
    avg_starting_salary: number;
    avg_salary_2_years: number;
    overall_effectiveness_score: number;
    performance_grade: string;
    employment_trend: string;
    salary_growth_rate: number;
}

interface Props {
    data: ProgramData[];
}

const props = defineProps<Props>();

const sortedPrograms = computed(() => {
    return [...(props.data || [])].sort((a, b) => b.overall_effectiveness_score - a.overall_effectiveness_score);
});

const topProgram = computed(() => {
    return sortedPrograms.value[0];
});

const averageEmploymentRate = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    const sum = props.data.reduce((acc, program) => acc + program.employment_rate_1_year, 0);
    return Math.round(sum / props.data.length);
});

const topPerformingPrograms = computed(() => {
    return sortedPrograms.value.slice(0, 5);
});

const topSalaryPrograms = computed(() => {
    return [...(props.data || [])].sort((a, b) => b.avg_starting_salary - a.avg_starting_salary).slice(0, 5);
});

const formatCurrency = (amount: number): string => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const getGradeColor = (grade: string): string => {
    const gradeColors: Record<string, string> = {
        'A+': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        A: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'A-': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'B+': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        B: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'B-': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'C+': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        C: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'C-': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        D: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return gradeColors[grade] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

const getTrendColor = (trend: string): string => {
    const trendColors: Record<string, string> = {
        improving: 'text-green-600 dark:text-green-400',
        declining: 'text-red-600 dark:text-red-400',
        stable: 'text-gray-600 dark:text-gray-400',
    };
    return trendColors[trend] || 'text-gray-600 dark:text-gray-400';
};

const getTrendIcon = (trend: string): string => {
    const trendIcons: Record<string, string> = {
        improving: '📈',
        declining: '📉',
        stable: '➡️',
    };
    return trendIcons[trend] || '➡️';
};
</script>











