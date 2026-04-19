<template>
    <div class="performance-monitoring">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Performance Monitoring</h1>
                <p class="text-gray-600 dark:text-gray-400">Real-time system performance metrics and optimization</p>
            </div>
            <div class="flex space-x-3">
                <button
                    @click="refreshMetrics"
                    :disabled="loading"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="mr-2 h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Refresh
                </button>
                <button
                    @click="clearCaches"
                    :disabled="loading"
                    class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-300 dark:hover:bg-red-900/20"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                    Clear Caches
                </button>
                <div class="relative">
                    <button
                        @click="showOptimizationMenu = !showOptimizationMenu"
                        :disabled="loading"
                        class="inline-flex items-center rounded-md border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:border-blue-600 dark:bg-gray-800 dark:text-blue-300 dark:hover:bg-blue-900/20"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Optimize
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Optimization Dropdown Menu -->
                    <div
                        v-if="showOptimizationMenu"
                        class="absolute right-0 z-10 mt-2 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800"
                    >
                        <div class="py-1">
                            <button
                                @click="optimizeSocialGraph"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                Optimize Social Graph
                            </button>
                            <button
                                @click="optimizeTimeline"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                Optimize Timeline
                            </button>
                            <button
                                @click="optimizeCdn"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"
                                    />
                                </svg>
                                Optimize CDN
                            </button>
                            <hr class="my-1 border-gray-200 dark:border-gray-600" />
                            <button
                                @click="setupAlerts"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 17h5l-5 5v-5zM4.343 12.344l1.414 1.414L9 10.414V3a1 1 0 011-1h4a1 1 0 011 1v7.414l3.243 3.243 1.414-1.414L15 8.586V3a3 3 0 00-3-3H8a3 3 0 00-3 3v5.586l-4.657 4.657z"
                                    />
                                </svg>
                                Setup Alerts
                            </button>
                            <button
                                @click="executeAutomatedOptimization"
                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                    />
                                </svg>
                                Auto Optimize
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Alerts -->
        <div v-if="alerts.length > 0" class="mb-6">
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Performance Alerts</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <ul class="list-disc space-y-1 pl-5">
                                <li v-for="alert in alerts" :key="alert.type">{{ alert.message }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Budget Status -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div v-for="(budget, metric) in performanceBudgets" :key="metric" class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium capitalize text-gray-600 dark:text-gray-400">{{ formatMetricName(metric) }}</p>
                        <p class="text-2xl font-bold" :class="getBudgetStatusColor(budget.status)">
                            {{ formatMetricValue(metric, budget.current) }}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full" :class="getBudgetStatusBg(budget.status)">
                            <svg class="h-6 w-6" :class="getBudgetStatusColor(budget.status)" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    v-if="budget.status === 'within_budget'"
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                                <path
                                    v-else-if="budget.status === 'approaching_limit'"
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                                <path
                                    v-else
                                    fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Budget: {{ formatMetricValue(metric, budget.budget) }}</span>
                        <span :class="getBudgetStatusColor(budget.status)">{{ Math.round(budget.percentage) }}%</span>
                    </div>
                    <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full transition-all duration-300"
                            :class="getBudgetProgressColor(budget.status)"
                            :style="{ width: Math.min(100, budget.percentage) + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Metrics -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- System Metrics -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Metrics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Cache Hit Rate</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.cache_hit_rate?.toFixed(1) }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Query Time</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.average_query_time?.toFixed(1) }}ms</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Connections</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.active_connections }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Memory Usage</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatBytes(metrics.memory_usage) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Timeline Generation</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.timeline_generation_time?.toFixed(1) }}ms</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redis Metrics -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Redis Metrics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Used Memory</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.redis_memory_usage?.used_memory_human }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Peak Memory</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{
                                metrics.redis_memory_usage?.used_memory_peak_human
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Slow Queries</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ metrics.slow_queries_count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optimization Results -->
        <div v-if="optimizationResults.length > 0" class="mb-8">
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Optimizations</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="result in optimizationResults.slice(-5)" :key="result.timestamp" class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full"
                                    :class="result.success ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20'"
                                >
                                    <svg
                                        v-if="result.success"
                                        class="h-4 w-4 text-green-600 dark:text-green-400"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium capitalize text-gray-900 dark:text-white">{{ result.type.replace('-', ' ') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ new Date(result.timestamp).toLocaleTimeString() }}</p>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ result.message }}</p>
                                <div v-if="result.data && result.data.recommendations" class="mt-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span v-if="result.data.assets_analyzed">{{ result.data.assets_analyzed }} assets analyzed</span>
                                        <span v-if="result.data.rules_configured">, {{ result.data.rules_configured }} alert rules configured</span>
                                        <span v-if="result.data.actions_triggered">, {{ result.data.actions_triggered }} actions triggered</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Cache Hit Rate Chart -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Cache Hit Rate Trend</h3>
                </div>
                <div class="p-6">
                    <div class="flex h-64 items-center justify-center text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto mb-4 h-12 w-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            <p>Chart visualization would be implemented here</p>
                            <p class="mt-1 text-sm">Current: {{ metrics.cache_hit_rate?.toFixed(1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Query Performance Chart -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Query Performance Trend</h3>
                </div>
                <div class="p-6">
                    <div class="flex h-64 items-center justify-center text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto mb-4 h-12 w-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <p>Chart visualization would be implemented here</p>
                            <p class="mt-1 text-sm">Current: {{ metrics.average_query_time?.toFixed(1) }}ms</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="loading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="flex items-center space-x-3 rounded-lg bg-white p-6 dark:bg-gray-800">
                <svg class="h-5 w-5 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <span class="text-gray-900 dark:text-white">{{ loadingMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

interface PerformanceMetrics {
    cache_hit_rate: number;
    average_query_time: number;
    active_connections: number;
    memory_usage: number;
    redis_memory_usage: {
        used_memory: number;
        used_memory_human: string;
        used_memory_peak: number;
        used_memory_peak_human: string;
    };
    slow_queries_count: number;
    timeline_generation_time: number;
}

interface PerformanceBudget {
    budget: number;
    current: number;
    status: 'within_budget' | 'approaching_limit' | 'over_budget';
    percentage: number;
}

interface PerformanceAlert {
    type: string;
    message: string;
    severity: 'warning' | 'critical';
}

const loading = ref(false);
const loadingMessage = ref('');
const metrics = ref<PerformanceMetrics>({} as PerformanceMetrics);
const performanceBudgets = ref<Record<string, PerformanceBudget>>({});
const alerts = ref<PerformanceAlert[]>([]);
const showOptimizationMenu = ref(false);
const optimizationResults = ref<any[]>([]);
let refreshInterval: NodeJS.Timeout | null = null;

onMounted(() => {
    loadPerformanceData();
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(loadPerformanceData, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

const loadPerformanceData = async () => {
    try {
        const response = await fetch('/api/admin/performance/metrics');
        const data = await response.json();

        metrics.value = data.metrics;
        performanceBudgets.value = data.budgets;
        alerts.value = data.alerts || [];
    } catch (error) {
        console.error('Failed to load performance data:', error);
    }
};

const refreshMetrics = async () => {
    loading.value = true;
    loadingMessage.value = 'Refreshing performance metrics...';

    try {
        await loadPerformanceData();
    } finally {
        loading.value = false;
    }
};

const clearCaches = async () => {
    loading.value = true;
    loadingMessage.value = 'Clearing performance caches...';

    try {
        await fetch('/api/admin/performance/clear-caches', { method: 'POST' });
        await loadPerformanceData();
        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to clear caches:', error);
    } finally {
        loading.value = false;
    }
};

const optimizeSocialGraph = async () => {
    loading.value = true;
    loadingMessage.value = 'Optimizing social graph caching...';

    try {
        const response = await fetch('/api/admin/performance/optimize-social-graph', { method: 'POST' });
        const result = await response.json();

        optimizationResults.value.push({
            type: 'social-graph',
            timestamp: new Date().toISOString(),
            success: result.success,
            message: result.message,
        });

        await loadPerformanceData();
        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to optimize social graph:', error);
    } finally {
        loading.value = false;
    }
};

const optimizeTimeline = async () => {
    loading.value = true;
    loadingMessage.value = 'Optimizing timeline queries...';

    try {
        const response = await fetch('/api/admin/performance/optimize-timeline', { method: 'POST' });
        const result = await response.json();

        optimizationResults.value.push({
            type: 'timeline',
            timestamp: new Date().toISOString(),
            success: result.success,
            message: result.message,
        });

        await loadPerformanceData();
        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to optimize timeline:', error);
    } finally {
        loading.value = false;
    }
};

const optimizeCdn = async () => {
    loading.value = true;
    loadingMessage.value = 'Optimizing CDN integration...';

    try {
        const response = await fetch('/api/admin/performance/optimize-cdn', { method: 'POST' });
        const result = await response.json();

        optimizationResults.value.push({
            type: 'cdn',
            timestamp: new Date().toISOString(),
            success: result.success,
            message: result.message,
            data: result.data,
        });

        await loadPerformanceData();
        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to optimize CDN:', error);
    } finally {
        loading.value = false;
    }
};

const setupAlerts = async () => {
    loading.value = true;
    loadingMessage.value = 'Setting up automated alerts...';

    try {
        const response = await fetch('/api/admin/performance/setup-alerts', { method: 'POST' });
        const result = await response.json();

        optimizationResults.value.push({
            type: 'alerts',
            timestamp: new Date().toISOString(),
            success: result.success,
            message: result.message,
            data: result.data,
        });

        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to setup alerts:', error);
    } finally {
        loading.value = false;
    }
};

const executeAutomatedOptimization = async () => {
    loading.value = true;
    loadingMessage.value = 'Executing automated optimization...';

    try {
        const response = await fetch('/api/admin/performance/execute-optimization', { method: 'POST' });
        const result = await response.json();

        optimizationResults.value.push({
            type: 'auto-optimization',
            timestamp: new Date().toISOString(),
            success: result.success,
            message: result.message,
            data: result.data,
        });

        await loadPerformanceData();
        showOptimizationMenu.value = false;
    } catch (error) {
        console.error('Failed to execute automated optimization:', error);
    } finally {
        loading.value = false;
    }
};

const formatMetricName = (metric: string): string => {
    return metric.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatMetricValue = (metric: string, value: number): string => {
    switch (metric) {
        case 'timeline_generation':
            return `${value.toFixed(1)}ms`;
        case 'cache_hit_rate':
            return `${value.toFixed(1)}%`;
        case 'memory_usage_mb':
            return `${value.toFixed(1)}MB`;
        case 'active_connections':
            return value.toString();
        default:
            return value.toFixed(1);
    }
};

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getBudgetStatusColor = (status: string): string => {
    switch (status) {
        case 'within_budget':
            return 'text-green-600 dark:text-green-400';
        case 'approaching_limit':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'over_budget':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getBudgetStatusBg = (status: string): string => {
    switch (status) {
        case 'within_budget':
            return 'bg-green-100 dark:bg-green-900/20';
        case 'approaching_limit':
            return 'bg-yellow-100 dark:bg-yellow-900/20';
        case 'over_budget':
            return 'bg-red-100 dark:bg-red-900/20';
        default:
            return 'bg-gray-100 dark:bg-gray-900/20';
    }
};

const getBudgetProgressColor = (status: string): string => {
    switch (status) {
        case 'within_budget':
            return 'bg-green-500';
        case 'approaching_limit':
            return 'bg-yellow-500';
        case 'over_budget':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
};
</script>
