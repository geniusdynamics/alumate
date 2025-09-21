<template>
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Industry Placement Analysis</h3>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Industry Distribution -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Graduate Distribution by Industry</h4>
                <div class="space-y-3">
                    <div v-for="industry in industryData" :key="industry.name" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: industry.color }"></div>
                            <span class="text-sm font-medium text-gray-700">{{ industry.name }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ industry.percentage }}%</div>
                            <div class="text-xs text-gray-500">{{ industry.count }} graduates</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary by Industry -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Average Salary by Industry</h4>
                <div class="space-y-3">
                    <div v-for="industry in salaryData" :key="industry.name" class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ industry.name }}</span>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">${{ industry.avgSalary.toLocaleString() }}</div>
                            <div class="text-xs text-gray-500">{{ industry.range }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Industry Growth Trends -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Industry Growth Trends</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Industry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">2022</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">2023</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">2024</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Growth</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="trend in growthTrends" :key="trend.industry">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ trend.industry }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ trend.y2022 }}%</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ trend.y2023 }}%</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ trend.y2024 }}%</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span :class="trend.growth >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ trend.growth >= 0 ? '+' : '' }}{{ trend.growth }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Employers -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Top Employers by Industry</h4>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="employer in topEmployers" :key="employer.name" class="rounded-lg bg-gray-50 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <h5 class="font-medium text-gray-900">{{ employer.name }}</h5>
                        <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">{{ employer.industry }}</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <div>{{ employer.hiredCount }} graduates hired</div>
                        <div class="mt-1 text-xs text-gray-500">Avg salary: ${{ employer.avgSalary.toLocaleString() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const industryData = ref([
    { name: 'Technology', percentage: 42, count: 168, color: '#3B82F6' },
    { name: 'Finance', percentage: 18, count: 72, color: '#10B981' },
    { name: 'Healthcare', percentage: 15, count: 60, color: '#F59E0B' },
    { name: 'Consulting', percentage: 12, count: 48, color: '#EF4444' },
    { name: 'Education', percentage: 8, count: 32, color: '#8B5CF6' },
    { name: 'Other', percentage: 5, count: 20, color: '#6B7280' },
]);

const salaryData = ref([
    { name: 'Technology', avgSalary: 85000, range: '$65K - $120K' },
    { name: 'Finance', avgSalary: 78000, range: '$60K - $110K' },
    { name: 'Consulting', avgSalary: 75000, range: '$55K - $105K' },
    { name: 'Healthcare', avgSalary: 68000, range: '$50K - $95K' },
    { name: 'Education', avgSalary: 52000, range: '$40K - $70K' },
]);

const growthTrends = ref([
    { industry: 'Technology', y2022: 38, y2023: 40, y2024: 42, growth: 4.0 },
    { industry: 'Finance', y2022: 20, y2023: 19, y2024: 18, growth: -2.0 },
    { industry: 'Healthcare', y2022: 12, y2023: 14, y2024: 15, growth: 3.0 },
    { industry: 'Consulting', y2022: 15, y2023: 13, y2024: 12, growth: -3.0 },
    { industry: 'Education', y2022: 10, y2023: 9, y2024: 8, growth: -2.0 },
]);

const topEmployers = ref([
    { name: 'TechCorp', industry: 'Technology', hiredCount: 23, avgSalary: 92000 },
    { name: 'DataSoft', industry: 'Technology', hiredCount: 18, avgSalary: 88000 },
    { name: 'FinanceFirst', industry: 'Finance', hiredCount: 15, avgSalary: 82000 },
    { name: 'HealthPlus', industry: 'Healthcare', hiredCount: 12, avgSalary: 71000 },
    { name: 'ConsultPro', industry: 'Consulting', hiredCount: 11, avgSalary: 79000 },
    { name: 'EduTech', industry: 'Education', hiredCount: 8, avgSalary: 58000 },
]);
</script>
