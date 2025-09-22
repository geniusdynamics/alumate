<template>
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Demographic Outcomes Analysis</h3>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Gender Distribution -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Employment by Gender</h4>
                <div class="space-y-3">
                    <div v-for="item in genderData" :key="item.gender" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }"></div>
                            <span class="text-sm font-medium text-gray-700">{{ item.gender }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ item.employmentRate }}%</div>
                            <div class="text-xs text-gray-500">{{ item.count }} graduates</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Age Group Analysis -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Employment by Age Group</h4>
                <div class="space-y-3">
                    <div v-for="item in ageData" :key="item.ageGroup" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }"></div>
                            <span class="text-sm font-medium text-gray-700">{{ item.ageGroup }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ item.employmentRate }}%</div>
                            <div class="text-xs text-gray-500">{{ item.count }} graduates</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Equity Analysis -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Salary Equity Analysis</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Demographic</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Avg Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Median Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Pay Gap</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="item in salaryEquity" :key="item.demographic">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ item.demographic }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">${{ item.avgSalary.toLocaleString() }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">${{ item.medianSalary.toLocaleString() }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span :class="item.payGap >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ item.payGap >= 0 ? '+' : '' }}{{ item.payGap }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Geographic Distribution -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Geographic Employment Distribution</h4>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="region in geographicData" :key="region.region" class="rounded-lg bg-gray-50 p-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ region.employmentRate }}%</div>
                        <div class="text-sm text-gray-600">{{ region.region }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ region.count }} graduates</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const genderData = ref([
    { gender: 'Female', employmentRate: 87, count: 234, color: '#EC4899' },
    { gender: 'Male', employmentRate: 89, count: 198, color: '#3B82F6' },
    { gender: 'Non-binary', employmentRate: 85, count: 12, color: '#10B981' },
    { gender: 'Prefer not to say', employmentRate: 88, count: 8, color: '#6B7280' },
]);

const ageData = ref([
    { ageGroup: '22-25', employmentRate: 92, count: 156, color: '#3B82F6' },
    { ageGroup: '26-30', employmentRate: 89, count: 189, color: '#10B981' },
    { ageGroup: '31-35', employmentRate: 85, count: 87, color: '#F59E0B' },
    { ageGroup: '36+', employmentRate: 82, count: 20, color: '#EF4444' },
]);

const salaryEquity = ref([
    { demographic: 'Female', avgSalary: 68500, medianSalary: 65000, payGap: -3.2 },
    { demographic: 'Male', avgSalary: 71200, medianSalary: 68000, payGap: 0 },
    { demographic: 'Non-binary', avgSalary: 69800, medianSalary: 66500, payGap: -1.9 },
    { demographic: 'Age 22-25', avgSalary: 62000, medianSalary: 60000, payGap: -12.9 },
    { demographic: 'Age 26-30', avgSalary: 72500, medianSalary: 70000, payGap: 1.8 },
    { demographic: 'Age 31+', avgSalary: 78900, medianSalary: 75000, payGap: 10.8 },
]);

const geographicData = ref([
    { region: 'Urban Areas', employmentRate: 91, count: 312 },
    { region: 'Suburban Areas', employmentRate: 86, count: 98 },
    { region: 'Rural Areas', employmentRate: 79, count: 42 },
]);
</script>
