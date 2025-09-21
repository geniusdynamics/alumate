<template>
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Salary Analysis</h3>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Key Metrics -->
            <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-4 text-white">
                <div class="text-2xl font-bold">${{ overallMetrics.average.toLocaleString() }}</div>
                <div class="text-blue-100">Average Salary</div>
            </div>
            <div class="rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-4 text-white">
                <div class="text-2xl font-bold">${{ overallMetrics.median.toLocaleString() }}</div>
                <div class="text-green-100">Median Salary</div>
            </div>
            <div class="rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 p-4 text-white">
                <div class="text-2xl font-bold">{{ overallMetrics.growth }}%</div>
                <div class="text-purple-100">YoY Growth</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Salary Distribution -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Salary Distribution</h4>
                <div class="space-y-3">
                    <div v-for="range in salaryRanges" :key="range.range" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: range.color }"></div>
                            <span class="text-sm font-medium text-gray-700">{{ range.range }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ range.percentage }}%</div>
                            <div class="text-xs text-gray-500">{{ range.count }} graduates</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary by Experience -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Salary by Experience Level</h4>
                <div class="space-y-3">
                    <div v-for="level in experienceLevels" :key="level.level" class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ level.level }}</span>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">${{ level.avgSalary.toLocaleString() }}</div>
                            <div class="text-xs text-gray-500">{{ level.range }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Progression Timeline -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Salary Progression Over Time</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Years After Graduation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Average Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Median Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Growth Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sample Size</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="year in salaryProgression" :key="year.years">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ year.years }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">${{ year.avgSalary.toLocaleString() }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">${{ year.medianSalary.toLocaleString() }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span :class="year.growthRate >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ year.growthRate >= 0 ? '+' : '' }}{{ year.growthRate }}%
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ year.sampleSize }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Regional Salary Comparison -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Regional Salary Comparison</h4>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div v-for="region in regionalData" :key="region.region" class="rounded-lg bg-gray-50 p-4">
                    <div class="text-center">
                        <div class="text-xl font-bold text-gray-900">${{ region.avgSalary.toLocaleString() }}</div>
                        <div class="text-sm text-gray-600">{{ region.region }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ region.costOfLivingIndex }}% COL</div>
                        <div class="text-xs text-gray-500">{{ region.sampleSize }} graduates</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const overallMetrics = ref({
    average: 72500,
    median: 68000,
    growth: 8.5,
});

const salaryRanges = ref([
    { range: '$40K - $50K', percentage: 12, count: 48, color: '#EF4444' },
    { range: '$50K - $65K', percentage: 28, count: 112, color: '#F59E0B' },
    { range: '$65K - $80K', percentage: 35, count: 140, color: '#10B981' },
    { range: '$80K - $100K', percentage: 18, count: 72, color: '#3B82F6' },
    { range: '$100K+', percentage: 7, count: 28, color: '#8B5CF6' },
]);

const experienceLevels = ref([
    { level: 'Entry Level (0-2 years)', avgSalary: 58000, range: '$45K - $70K' },
    { level: 'Mid Level (3-5 years)', avgSalary: 75000, range: '$60K - $90K' },
    { level: 'Senior Level (6-10 years)', avgSalary: 95000, range: '$80K - $120K' },
    { level: 'Lead/Principal (10+ years)', avgSalary: 125000, range: '$100K - $160K' },
]);

const salaryProgression = ref([
    { years: '0-1', avgSalary: 55000, medianSalary: 52000, growthRate: 0, sampleSize: 156 },
    { years: '1-2', avgSalary: 62000, medianSalary: 58000, growthRate: 12.7, sampleSize: 142 },
    { years: '2-3', avgSalary: 71000, medianSalary: 67000, growthRate: 14.5, sampleSize: 128 },
    { years: '3-5', avgSalary: 82000, medianSalary: 78000, growthRate: 15.5, sampleSize: 98 },
    { years: '5-7', avgSalary: 98000, medianSalary: 92000, growthRate: 19.5, sampleSize: 67 },
    { years: '7-10', avgSalary: 118000, medianSalary: 112000, growthRate: 20.4, sampleSize: 34 },
]);

const regionalData = ref([
    { region: 'San Francisco', avgSalary: 95000, costOfLivingIndex: 180, sampleSize: 45 },
    { region: 'New York', avgSalary: 88000, costOfLivingIndex: 168, sampleSize: 52 },
    { region: 'Seattle', avgSalary: 82000, costOfLivingIndex: 142, sampleSize: 38 },
    { region: 'Austin', avgSalary: 75000, costOfLivingIndex: 118, sampleSize: 41 },
    { region: 'Chicago', avgSalary: 72000, costOfLivingIndex: 108, sampleSize: 35 },
    { region: 'Atlanta', avgSalary: 68000, costOfLivingIndex: 95, sampleSize: 29 },
    { region: 'Denver', avgSalary: 71000, costOfLivingIndex: 112, sampleSize: 31 },
    { region: 'Remote', avgSalary: 78000, costOfLivingIndex: 100, sampleSize: 129 },
]);
</script>
