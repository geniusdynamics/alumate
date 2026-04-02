<template>
    <div 
        class="cohort-analysis-visualization" 
        role="region" 
        aria-label="Cohort analysis visualization dashboard"
    >
        <!-- Loading State -->
        <div v-if="isLoading && !cohortData" class="flex items-center justify-center p-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Loading cohort data...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 mb-6">
            <div class="flex items-center">
                <svg class="mr-3 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
                <button 
                    @click="refreshData" 
                    class="ml-4 text-sm text-red-600 hover:text-red-800 underline"
                >
                    Retry
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else class="cohort-visualization-container">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cohort Analysis</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Analyze user retention, engagement, and conversion across cohorts
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="!comparisonMode"
                        @click="enableComparisonMode"
                        :disabled="selectedCohorts.length < 2"
                        class="rounded bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="Enable cohort comparison mode"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Compare Cohorts
                    </button>
                    <button
                        v-else
                        @click="disableComparisonMode"
                        class="rounded bg-gray-600 px-4 py-2 text-sm text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        Exit Comparison
                    </button>
                    <button
                        @click="refreshData"
                        :disabled="isLoading"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        aria-label="Refresh cohort data"
                    >
                        <svg 
                            class="mr-2 h-4 w-4 inline" 
                            :class="{ 'animate-spin': isLoading }" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <nav class="flex space-x-1 border-b border-gray-200" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'py-3 px-4 border-b-2 font-medium text-sm transition-colors',
                            activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        :aria-current="activeTab === tab.id ? 'page' : undefined"
                    >
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Cohort List View -->
                <div v-if="activeTab === 'list'" class="space-y-6">
                    <!-- Filters and Search -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search-cohorts" class="sr-only">Search cohorts</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    id="search-cohorts"
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search cohorts..."
                                    class="w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                        <select
                            v-model="filterMetric"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Filter by metric"
                        >
                            <option value="all">All Metrics</option>
                            <option value="retention">High Retention</option>
                            <option value="engagement">High Engagement</option>
                            <option value="conversion">High Conversion</option>
                        </select>
                    </div>

                    <!-- Cohort Cards Grid -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div 
                            v-for="cohort in filteredCohorts" 
                            :key="cohort.id"
                            class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow cursor-pointer"
                            :class="{ 'ring-2 ring-blue-500': selectedCohortId === cohort.id }"
                            @click="selectCohort(cohort.id)"
                            role="button"
                            :tabindex="0"
                            @keydown.enter="selectCohort(cohort.id)"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ cohort.name }}</h3>
                                    <p class="text-sm text-gray-500">{{ cohort.members_count }} members</p>
                                </div>
                                <span 
                                    v-if="cohort.insights?.length"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                    :class="getInsightBadgeClass(cohort)"
                                >
                                    {{ cohort.insights.length }} insights
                                </span>
                            </div>

                            <!-- Quick Metrics -->
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <div class="text-lg font-bold" :class="getMetricColor(cohort.metrics?.retention_30d)">
                                        {{ cohort.metrics?.retention_30d?.toFixed(1) || 'N/A' }}%
                                    </div>
                                    <div class="text-xs text-gray-500">30-Day Retention</div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold" :class="getMetricColor(cohort.metrics?.engagement?.score)">
                                        {{ cohort.metrics?.engagement?.score?.toFixed(1) || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">Engagement</div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold" :class="getMetricColor(cohort.metrics?.conversion?.rate)">
                                        {{ cohort.metrics?.conversion?.rate?.toFixed(1) || 'N/A' }}%
                                    </div>
                                    <div class="text-xs text-gray-500">Conversion</div>
                                </div>
                            </div>

                            <!-- Selection Checkbox for Comparison -->
                            <div v-if="comparisonMode" class="mt-3 pt-3 border-t border-gray-100">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        :checked="isCohortSelected(cohort.id)"
                                        @change="toggleCohortSelection(cohort.id)"
                                        @click.stop
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-600">Select for comparison</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredCohorts.length === 0" class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No cohorts found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters.</p>
                    </div>
                </div>

                <!-- Cohort Detail View -->
                <div v-if="activeTab === 'detail' && selectedCohort" class="space-y-6">
                    <div class="flex items-center gap-4 mb-6">
                        <button
                            @click="activeTab = 'list'"
                            class="text-gray-600 hover:text-gray-800"
                            aria-label="Back to cohort list"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">{{ selectedCohort.name }}</h2>
                            <p class="text-sm text-gray-600">{{ selectedCohort.members_count }} members</p>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Cohort Size</div>
                            <div class="text-2xl font-bold text-gray-900 mt-1">{{ selectedCohort.members_count }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">7-Day Retention</div>
                            <div class="text-2xl font-bold mt-1" :class="getMetricColor(selectedCohort.metrics?.retention?.day7)">
                                {{ selectedCohort.metrics?.retention?.day7?.toFixed(1) || 'N/A' }}%
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">30-Day Retention</div>
                            <div class="text-2xl font-bold mt-1" :class="getMetricColor(selectedCohort.metrics?.retention?.day30)">
                                {{ selectedCohort.metrics?.retention?.day30?.toFixed(1) || 'N/A' }}%
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Engagement Score</div>
                            <div class="text-2xl font-bold mt-1" :class="getMetricColor(selectedCohort.metrics?.engagement?.score)">
                                {{ selectedCohort.metrics?.engagement?.score?.toFixed(1) || 'N/A' }}
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- Retention Chart -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Retention Over Time</h3>
                            <div class="h-64">
                                <canvas ref="retentionChartRef" aria-label="Retention chart" role="img"></canvas>
                            </div>
                        </div>

                        <!-- Engagement Chart -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Engagement Metrics</h3>
                            <div class="h-64">
                                <canvas ref="engagementChartRef" aria-label="Engagement chart" role="img"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Funnel -->
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversion Funnel</h3>
                        <div class="h-64">
                            <canvas ref="funnelChartRef" aria-label="Conversion funnel chart" role="img"></canvas>
                        </div>
                    </div>

                    <!-- Insights Section -->
                    <div v-if="cohortInsights.length > 0" class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Automated Insights</h3>
                        <div class="space-y-3">
                            <div 
                                v-for="(insight, index) in cohortInsights" 
                                :key="index"
                                class="flex items-start gap-3 p-3 rounded-lg"
                                :class="getInsightClass(insight.type)"
                            >
                                <svg 
                                    class="h-5 w-5 mt-0.5 flex-shrink-0" 
                                    :class="getInsightIconClass(insight.type)"
                                    fill="currentColor" 
                                    viewBox="0 0 20 20"
                                >
                                    <path v-if="insight.type === 'positive'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    <path v-else-if="insight.type === 'warning'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium" :class="getInsightTextClass(insight.type)">
                                        {{ insight.message }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">{{ insight.recommendation }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cohort Comparison View -->
                <div v-if="activeTab === 'comparison'" class="space-y-6">
                    <div class="flex items-center gap-4 mb-6">
                        <button
                            @click="activeTab = 'list'"
                            class="text-gray-600 hover:text-gray-800"
                            aria-label="Back to cohort list"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Cohort Comparison</h2>
                            <p class="text-sm text-gray-600">Compare metrics across selected cohorts</p>
                        </div>
                    </div>

                    <!-- Cohort Selection -->
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Select Cohorts to Compare (Max 4)</h3>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="cohort in availableCohorts"
                                :key="cohort.id"
                                @click="toggleCohortSelection(cohort.id)"
                                :disabled="!isCohortSelected(cohort.id) && selectedForComparison.length >= 4"
                                class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                                :class="isCohortSelected(cohort.id) 
                                    ? 'bg-blue-600 text-white' 
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                {{ cohort.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Comparison Chart -->
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Retention Comparison</h3>
                        <div class="h-80">
                            <canvas ref="comparisonChartRef" aria-label="Cohort comparison chart" role="img"></canvas>
                        </div>
                    </div>

                    <!-- Comparison Table -->
                    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                                    <th 
                                        v-for="cohort in selectedForComparison" 
                                        :key="cohort.id"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ cohort.name }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Size</td>
                                    <td v-for="cohort in selectedForComparison" :key="cohort.id" class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ cohort.members_count }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">7-Day Retention</td>
                                    <td v-for="cohort in selectedForComparison" :key="cohort.id" class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getMetricColor(cohort.metrics?.retention?.day7)">
                                            {{ cohort.metrics?.retention?.day7?.toFixed(1) || 'N/A' }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">30-Day Retention</td>
                                    <td v-for="cohort in selectedForComparison" :key="cohort.id" class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getMetricColor(cohort.metrics?.retention?.day30)">
                                            {{ cohort.metrics?.retention?.day30?.toFixed(1) || 'N/A' }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Engagement Score</td>
                                    <td v-for="cohort in selectedForComparison" :key="cohort.id" class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getMetricColor(cohort.metrics?.engagement?.score)">
                                            {{ cohort.metrics?.engagement?.score?.toFixed(1) || 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Conversion Rate</td>
                                    <td v-for="cohort in selectedForComparison" :key="cohort.id" class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getMetricColor(cohort.metrics?.conversion?.rate)">
                                            {{ cohort.metrics?.conversion?.rate?.toFixed(1) || 'N/A' }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Trend Analysis View -->
                <div v-if="activeTab === 'trends'" class="space-y-6">
                    <div class="flex items-center gap-4 mb-6">
                        <button
                            @click="activeTab = 'list'"
                            class="text-gray-600 hover:text-gray-800"
                            aria-label="Back to cohort list"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Trend Analysis</h2>
                            <p class="text-sm text-gray-600">Historical trends and forecasting</p>
                        </div>
                    </div>

                    <!-- Trend Controls -->
                    <div class="flex flex-wrap gap-4">
                        <select
                            v-model="selectedCohortId"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select cohort for trend analysis"
                        >
                            <option value="">Select Cohort</option>
                            <option v-for="cohort in availableCohorts" :key="cohort.id" :value="cohort.id">
                                {{ cohort.name }}
                            </option>
                        </select>
                        <select
                            v-model="trendPeriod"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select trend period"
                        >
                            <option value="day">Daily</option>
                            <option value="week">Weekly</option>
                            <option value="month">Monthly</option>
                        </select>
                        <button
                            @click="loadTrendData"
                            :disabled="!selectedCohortId || isLoading"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            Analyze Trends
                        </button>
                    </div>

                    <!-- Trend Chart -->
                    <div v-if="trendData" class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Active Users Trend 
                            <span class="text-sm font-normal text-gray-500">({{ trendPeriod }})</span>
                        </h3>
                        <div class="h-80">
                            <canvas ref="trendChartRef" aria-label="Trend analysis chart" role="img"></canvas>
                        </div>
                    </div>

                    <!-- Trend Summary -->
                    <div v-if="trendData?.summary" class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Overall Trend</div>
                            <div class="text-xl font-bold mt-1 capitalize" :class="getTrendColor(trendData.summary.overall_trend)">
                                {{ trendData.summary.overall_trend }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Avg Active Users</div>
                            <div class="text-xl font-bold text-gray-900 mt-1">{{ trendData.summary.avg_active_users?.toFixed(0) }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Total Events</div>
                            <div class="text-xl font-bold text-gray-900 mt-1">{{ trendData.summary.total_events?.toLocaleString() }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="text-sm text-gray-500">Growth Periods</div>
                            <div class="text-xl font-bold text-gray-900 mt-1">
                                {{ trendData.summary.periods_with_growth }} / {{ trendData.summary.periods_with_decline + trendData.summary.periods_with_growth }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insights View -->
                <div v-if="activeTab === 'insights'" class="space-y-6">
                    <h2 class="text-xl font-bold text-gray-900">Automated Insights</h2>
                    
                    <!-- Filter by Type -->
                    <div class="flex gap-2">
                        <button
                            v-for="filter in insightFilters"
                            :key="filter.value"
                            @click="insightFilter = filter.value"
                            class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                            :class="insightFilter === filter.value 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            {{ filter.label }}
                        </button>
                    </div>

                    <!-- All Insights -->
                    <div class="space-y-4">
                        <template v-for="cohort in availableCohorts" :key="cohort.id">
                            <div v-if="getCohortInsights(cohort.id).length > 0">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ cohort.name }}</h3>
                                <div class="space-y-3">
                                    <div 
                                        v-for="(insight, idx) in getCohortInsights(cohort.id)" 
                                        :key="idx"
                                        class="flex items-start gap-3 p-4 rounded-lg bg-white border border-gray-200"
                                        :class="getInsightClass(insight.type)"
                                    >
                                        <svg 
                                            class="h-6 w-6 mt-0.5 flex-shrink-0" 
                                            :class="getInsightIconClass(insight.type)"
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path v-if="insight.type === 'positive'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            <path v-else-if="insight.type === 'warning'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span 
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                    :class="getSeverityBadgeClass(insight.severity)"
                                                >
                                                    {{ insight.severity }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ insight.metric }}</span>
                                            </div>
                                            <p class="text-sm font-medium" :class="getInsightTextClass(insight.type)">
                                                {{ insight.message }}
                                            </p>
                                            <p class="text-sm text-gray-600 mt-2">
                                                <span class="font-medium">Recommendation:</span> {{ insight.recommendation }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-if="allInsights.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No insights available</h3>
                            <p class="mt-1 text-sm text-gray-500">All cohorts are performing well based on current metrics.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useCohortStore } from '../../Stores/useCohortStore';
import type { 
    CohortData, 
    CohortInsight,
    ConversionStep,
    Trend
} from '../../Types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    initialTab?: 'list' | 'detail' | 'comparison' | 'trends' | 'insights';
    preselectedCohortId?: string;
}>(), {
    initialTab: 'list',
});

// Store
const cohortStore = useCohortStore();

// Reactive state
const activeTab = ref<'list' | 'detail' | 'comparison' | 'trends' | 'insights'>(props.initialTab);
const searchQuery = ref('');
const filterMetric = ref('all');
const comparisonMode = ref(false);
const selectedCohortId = ref(props.preselectedCohortId || '');
const trendPeriod = ref<'day' | 'week' | 'month'>('week');
const insightFilter = ref('all');
const trendData = ref<{ trends: Trend[]; summary: any } | null>(null);

// Chart refs
const retentionChartRef = ref<HTMLCanvasElement>();
const engagementChartRef = ref<HTMLCanvasElement>();
const funnelChartRef = ref<HTMLCanvasElement>();
const comparisonChartRef = ref<HTMLCanvasElement>();
const trendChartRef = ref<HTMLCanvasElement>();

// Chart instances
let retentionChart: Chart | null = null;
let engagementChart: Chart | null = null;
let funnelChart: Chart | null = null;
let comparisonChart: Chart | null = null;
let trendChart: Chart | null = null;

// Tabs configuration
const tabs = [
    { id: 'list', name: 'Cohort List' },
    { id: 'detail', name: 'Cohort Details' },
    { id: 'comparison', name: 'Comparison' },
    { id: 'trends', name: 'Trends' },
    { id: 'insights', name: 'Insights' },
];

// Insight filters
const insightFilters = [
    { value: 'all', label: 'All' },
    { value: 'positive', label: 'Positive' },
    { value: 'warning', label: 'Warnings' },
    { value: 'critical', label: 'Critical' },
];

// Computed properties
const isLoading = computed(() => cohortStore.isLoading);
const error = computed(() => cohortStore.error);
const availableCohorts = computed(() => cohortStore.availableCohorts);
const cohortData = computed(() => cohortStore.cohortData);
const comparisonData = computed(() => cohortStore.comparisonData);

const selectedCohort = computed(() => {
    if (!selectedCohortId.value) return null;
    return availableCohorts.value.find(c => c.id === selectedCohortId.value) || null;
});

const cohortInsights = computed(() => {
    if (!selectedCohort.value) return [];
    return selectedCohort.value.insights || [];
});

const filteredCohorts = computed(() => {
    let cohorts = [...availableCohorts.value];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        cohorts = cohorts.filter(c => c.name.toLowerCase().includes(query));
    }

    // Metric filter
    if (filterMetric.value !== 'all') {
        cohorts = cohorts.filter(c => {
            const metrics = c.metrics;
            switch (filterMetric.value) {
                case 'retention':
                    return (metrics?.retention?.day30 || 0) > 40;
                case 'engagement':
                    return (metrics?.engagement?.score || 0) > 50;
                case 'conversion':
                    return (metrics?.conversion?.rate || 0) > 10;
                default:
                    return true;
            }
        });
    }

    return cohorts;
});

const selectedForComparison = computed(() => {
    return availableCohorts.value.filter(c => 
        cohortStore.filters.cohortIds.includes(c.id)
    );
});

const allInsights = computed(() => {
    const insights: Array<CohortInsight & { cohortId: string; cohortName: string }> = [];
    
    availableCohorts.value.forEach(cohort => {
        if (cohort.insights) {
            cohort.insights.forEach(insight => {
                if (insightFilter.value === 'all' || insight.type === insightFilter.value) {
                    insights.push({
                        ...insight,
                        cohortId: cohort.id,
                        cohortName: cohort.name,
                    });
                }
            });
        }
    });

    return insights;
});

// Methods
const selectCohort = async (cohortId: string) => {
    selectedCohortId.value = cohortId;
    activeTab.value = 'detail';
    await cohortStore.fetchCohort(cohortId);
    await nextTick();
    updateCharts();
};

const toggleCohortSelection = (cohortId: string) => {
    cohortStore.toggleCohortSelection(cohortId);
};

const isCohortSelected = (cohortId: string): boolean => {
    return cohortStore.filters.cohortIds.includes(cohortId);
};

const enableComparisonMode = () => {
    comparisonMode.value = true;
    cohortStore.setComparisonMode(true);
    activeTab.value = 'comparison';
};

const disableComparisonMode = () => {
    comparisonMode.value = false;
    cohortStore.setComparisonMode(false);
    activeTab.value = 'list';
};

const loadTrendData = async () => {
    if (!selectedCohortId.value) return;
    
    try {
        const response = await fetch(`/api/analytics/cohort-analysis/${selectedCohortId.value}/trends?period=${trendPeriod.value}`);
        const data = await response.json();
        trendData.value = data.data;
        await nextTick();
        updateTrendChart();
    } catch (err) {
        console.error('Failed to load trend data:', err);
    }
};

const getCohortInsights = (cohortId: string): CohortInsight[] => {
    const cohort = availableCohorts.value.find(c => c.id === cohortId);
    return cohort?.insights || [];
};

const refreshData = async () => {
    try {
        await cohortStore.listCohorts();
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

// Chart update methods
const updateCharts = () => {
    updateRetentionChart();
    updateEngagementChart();
    updateFunnelChart();
    updateComparisonChart();
};

const updateRetentionChart = () => {
    if (!retentionChartRef.value || !selectedCohort.value) return;

    const ctx = retentionChartRef.value.getContext('2d');
    if (!ctx) return;

    if (retentionChart) {
        retentionChart.destroy();
    }

    const retention = selectedCohort.value.metrics?.retention;
    const labels = ['Day 7', 'Day 30', 'Day 90', 'Day 180'];
    const data = [
        retention?.day7 || 0,
        retention?.day30 || 0,
        retention?.day90 || 0,
        retention?.day180 || 0,
    ];
    const trend = retention?.trend || [];

    retentionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Retention Rate',
                    data,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                },
                ...(trend.length > 0 ? [{
                    label: 'Trend',
                    data: trend,
                    borderColor: 'rgba(107, 114, 128, 0.5)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                }] : []),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y?.toFixed(1)}%`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
            },
        },
    });
};

const updateEngagementChart = () => {
    if (!engagementChartRef.value || !selectedCohort.value) return;

    const ctx = engagementChartRef.value.getContext('2d');
    if (!ctx) return;

    if (engagementChart) {
        engagementChart.destroy();
    }

    const engagement = selectedCohort.value.metrics?.engagement;
    const labels = ['Sessions/Week', 'Pages/Session', 'Active Days/Week'];
    const data = [
        engagement?.sessionsPerWeek || 0,
        engagement?.pagesPerSession || 0,
        engagement?.activeDaysPerWeek || 0,
    ];

    engagementChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Engagement Metrics',
                data,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                ],
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
};

const updateFunnelChart = () => {
    if (!funnelChartRef.value || !selectedCohort.value) return;

    const ctx = funnelChartRef.value.getContext('2d');
    if (!ctx) return;

    if (funnelChart) {
        funnelChart.destroy();
    }

    const conversion = selectedCohort.value.metrics?.conversion;
    const funnel = conversion?.funnel || [];

    if (funnel.length === 0) {
        // Create placeholder chart
        funnelChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Signup', 'Login', 'Profile', 'Purchase'],
                datasets: [{
                    label: 'Conversion Steps',
                    data: [100, 75, 50, 25],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
        return;
    }

    const labels = funnel.map((step: ConversionStep) => step.step);
    const data = funnel.map((step: ConversionStep) => step.rate);

    funnelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Conversion Rate',
                data,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.parsed.x?.toFixed(1)}%`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
            },
        },
    });
};

const updateComparisonChart = () => {
    if (!comparisonChartRef.value) return;

    const ctx = comparisonChartRef.value.getContext('2d');
    if (!ctx) return;

    if (comparisonChart) {
        comparisonChart.destroy();
    }

    const cohorts = selectedForComparison.value;
    if (cohorts.length === 0) return;

    const labels = ['7-Day', '30-Day', '90-Day'];
    const datasets = cohorts.map((cohort, index) => {
        const colors = [
            { bg: 'rgba(59, 130, 246, 0.8)', border: 'rgba(59, 130, 246, 1)' },
            { bg: 'rgba(16, 185, 129, 0.8)', border: 'rgba(16, 185, 129, 1)' },
            { bg: 'rgba(245, 158, 11, 0.8)', border: 'rgba(245, 158, 11, 1)' },
            { bg: 'rgba(139, 92, 246, 0.8)', border: 'rgba(139, 92, 246, 1)' },
        ];

        return {
            label: cohort.name,
            data: [
                cohort.metrics?.retention?.day7 || 0,
                cohort.metrics?.retention?.day30 || 0,
                cohort.metrics?.retention?.day90 || 0,
            ],
            backgroundColor: colors[index % colors.length].bg,
            borderColor: colors[index % colors.length].border,
            borderWidth: 1,
        };
    });

    comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
            },
        },
    });
};

const updateTrendChart = () => {
    if (!trendChartRef.value || !trendData.value) return;

    const ctx = trendChartRef.value.getContext('2d');
    if (!ctx) return;

    if (trendChart) {
        trendChart.destroy();
    }

    const trends = trendData.value.trends;
    const labels = trends.map((t: any) => t.period);
    const activeUsers = trends.map((t: any) => t.active_users);

    // Calculate simple forecast (linear regression for next 3 periods)
    const n = trends.length;
    if (n >= 2) {
        const x = [...Array(n).keys()];
        const y = activeUsers;
        const xMean = x.reduce((a, b) => a + b, 0) / n;
        const yMean = y.reduce((a, b) => a + b, 0) / n;
        const numerator = x.reduce((sum, xi, i) => sum + (xi - xMean) * (y[i] - yMean), 0);
        const denominator = x.reduce((sum, xi) => sum + Math.pow(xi - xMean, 2), 0);
        const slope = numerator / denominator;
        const intercept = yMean - slope * xMean;

        // Add forecast points
        for (let i = 1; i <= 3; i++) {
            labels.push(`Forecast ${i}`);
            activeUsers.push(Math.round(intercept + slope * (n + i - 1)));
        }
    }

    const splitIndex = n;

    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Active Users',
                    data: activeUsers,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    segment: {
                        borderDash: (ctx: any) => {
                            if (ctx.p0DataIndex >= splitIndex - 1) {
                                return [5, 5];
                            }
                            return undefined;
                        },
                    },
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                annotation: {
                    annotations: {
                        forecastLine: {
                            type: 'line',
                            xMin: splitIndex - 0.5,
                            xMax: splitIndex - 0.5,
                            borderColor: 'rgba(107, 114, 128, 0.5)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            label: {
                                display: true,
                                content: 'Forecast',
                                position: 'start',
                            },
                        },
                    },
                },
            },
        },
    });
};

// Utility methods
const getMetricColor = (value: number | undefined): string => {
    if (value === undefined || value === null) return 'text-gray-400';
    if (value >= 70) return 'text-green-600';
    if (value >= 40) return 'text-yellow-600';
    return 'text-red-600';
};

const getTrendColor = (trend: string): string => {
    switch (trend) {
        case 'improving':
        case 'up':
            return 'text-green-600';
        case 'declining':
        case 'down':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};

const getInsightBadgeClass = (cohort: CohortData): string => {
    const hasCritical = cohort.insights?.some(i => i.type === 'critical');
    const hasWarning = cohort.insights?.some(i => i.type === 'warning');
    const hasPositive = cohort.insights?.some(i => i.type === 'positive');

    if (hasCritical) return 'bg-red-100 text-red-800';
    if (hasWarning) return 'bg-yellow-100 text-yellow-800';
    if (hasPositive) return 'bg-green-100 text-green-800';
    return 'bg-gray-100 text-gray-800';
};

const getInsightClass = (type: string): string => {
    switch (type) {
        case 'positive':
            return 'bg-green-50 border-green-200';
        case 'warning':
            return 'bg-yellow-50 border-yellow-200';
        case 'critical':
            return 'bg-red-50 border-red-200';
        default:
            return 'bg-gray-50 border-gray-200';
    }
};

const getInsightIconClass = (type: string): string => {
    switch (type) {
        case 'positive':
            return 'text-green-500';
        case 'warning':
            return 'text-yellow-500';
        case 'critical':
            return 'text-red-500';
        default:
            return 'text-gray-500';
    }
};

const getInsightTextClass = (type: string): string => {
    switch (type) {
        case 'positive':
            return 'text-green-800';
        case 'warning':
            return 'text-yellow-800';
        case 'critical':
            return 'text-red-800';
        default:
            return 'text-gray-800';
    }
};

const getSeverityBadgeClass = (severity: string | undefined): string => {
    switch (severity) {
        case 'critical':
            return 'bg-red-100 text-red-800';
        case 'high':
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Lifecycle
onMounted(async () => {
    await cohortStore.listCohorts();
    
    if (props.preselectedCohortId) {
        await selectCohort(props.preselectedCohortId);
    }
});

// Watchers
watch(activeTab, async (newTab) => {
    if (newTab === 'comparison') {
        await nextTick();
        updateComparisonChart();
    }
});

watch(() => cohortStore.filters.cohortIds, async () => {
    if (activeTab.value === 'comparison') {
        await nextTick();
        updateComparisonChart();
    }
}, { deep: true });
</script>

<style scoped>
.cohort-analysis-visualization {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.cohort-analysis-visualization button:focus,
.cohort-analysis-visualization select:focus,
.cohort-analysis-visualization input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Tab styles */
.tab-content {
    @apply min-h-96;
}

/* Chart responsive design */
@media (max-width: 768px) {
    .cohort-visualization-container {
        @apply px-2;
    }

    .grid-cols-1.lg\:grid-cols-2 {
        @apply grid-cols-1;
    }

    .grid-cols-1.md\:grid-cols-4 {
        @apply grid-cols-1;
    }
}

/* Smooth transitions */
.cohort-analysis-visualization .rounded-lg {
    @apply transition-all duration-200;
}

/* Hover effects */
.cohort-analysis-visualization .hover\:shadow-md:hover {
    @apply shadow-md;
}
</style>
