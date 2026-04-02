<template>
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Career Path Analysis</h3>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Career Progression Chart -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Career Progression Timeline</h4>
                <div class="flex h-64 items-center justify-center text-gray-500">
                    <div class="text-center">
                        <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                        </div>
                        <p class="text-sm">Career progression chart will be displayed here</p>
                    </div>
                </div>
            </div>

            <!-- Career Paths Summary -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h4 class="text-md mb-3 font-medium text-gray-800">Popular Career Paths</h4>
                <div class="space-y-3">
                    <div v-for="path in careerPaths" :key="path.name" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full" :style="{ backgroundColor: path.color }"></div>
                            <span class="text-sm font-medium text-gray-700">{{ path.name }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ path.percentage }}%</div>
                            <div class="text-xs text-gray-500">{{ path.count }} graduates</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Career Transition Matrix -->
        <div class="mt-6">
            <h4 class="text-md mb-3 font-medium text-gray-800">Career Transition Matrix</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">From</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Avg Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="transition in transitions" :key="`${transition.from}-${transition.to}`">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ transition.from }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ transition.to }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ transition.count }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ transition.avgTime }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const careerPaths = ref([
    { name: 'Software Engineering', percentage: 35, count: 142, color: '#3B82F6' },
    { name: 'Data Science', percentage: 22, count: 89, color: '#10B981' },
    { name: 'Product Management', percentage: 18, count: 73, color: '#F59E0B' },
    { name: 'Consulting', percentage: 15, count: 61, color: '#EF4444' },
    { name: 'Research', percentage: 10, count: 40, color: '#8B5CF6' },
]);

const transitions = ref([
    { from: 'Junior Developer', to: 'Senior Developer', count: 45, avgTime: '2.3 years' },
    { from: 'Senior Developer', to: 'Tech Lead', count: 23, avgTime: '3.1 years' },
    { from: 'Tech Lead', to: 'Engineering Manager', count: 12, avgTime: '2.8 years' },
    { from: 'Data Analyst', to: 'Data Scientist', count: 18, avgTime: '1.9 years' },
    { from: 'Associate Consultant', to: 'Senior Consultant', count: 15, avgTime: '2.5 years' },
]);
</script>
