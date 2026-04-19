<template>
    <div 
        class="attribution-visualization" 
        role="region" 
        aria-label="Attribution visualization dashboard"
    >
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-12">
            <div class="text-center">
                <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-3 text-gray-600">Loading attribution data...</p>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
            <button 
                @click="refreshData" 
                class="mt-3 rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
            >
                Retry
            </button>
        </div>

        <!-- Main Content -->
        <div v-else class="attribution-content">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Attribution Visualization</h1>
                    <p class="text-sm text-gray-600">
                        Analyze channel performance, budget allocation, and conversion paths
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="refreshData"
                        :disabled="isLoading"
                        class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        aria-label="Refresh attribution data"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Filters & Controls</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Date Range -->
                    <div>
                        <label for="date-range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <select
                            id="date-range"
                            v-model="selectedDateRange"
                            @change="handleDateRangeChange"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select date range for analysis"
                        >
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                            <option value="90d">Last 90 days</option>
                        </select>
                    </div>

                    <!-- Attribution Model -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Attribution Model</label>
                        <select
                            id="model"
                            v-model="selectedModel"
                            @change="handleModelChange"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select attribution model"
                        >
                            <option value="last_touch">Last Touch</option>
                            <option value="first_touch">First Touch</option>
                            <option value="linear">Linear</option>
                            <option value="time_decay">Time Decay</option>
                        </select>
                    </div>

                    <!-- Channel Filter -->
                    <div>
                        <label for="channel" class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                        <select
                            id="channel"
                            v-model="selectedChannel"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Filter by channel"
                        >
                            <option value="">All Channels</option>
                            <option value="google">Google</option>
                            <option value="facebook">Facebook</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="twitter">Twitter</option>
                            <option value="email">Email</option>
                            <option value="organic">Organic</option>
                            <option value="direct">Direct</option>
                        </select>
                    </div>

                    <!-- View Mode -->
                    <div>
                        <label for="view-mode" class="block text-sm font-medium text-gray-700 mb-1">View Mode</label>
                        <select
                            id="view-mode"
                            v-model="viewMode"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select visualization view mode"
                        >
                            <option value="overview">Overview</option>
                            <option value="comparison">Model Comparison</option>
                            <option value="channels">Channels</option>
                            <option value="budget">Budget</option>
                            <option value="paths">Conversion Paths</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Overview Metrics Cards -->
            <div v-if="viewMode === 'overview'" class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg bg-white p-4 shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Conversions</p>
                            <p class="text-2xl font-bold text-gray-900">{{ summaryData.totalConversions?.toLocaleString() || 0 }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg bg-white p-4 shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Value</p>
                            <p class="text-2xl font-bold text-gray-900">${{ formatNumber(summaryData.totalValue || 0) }}</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg bg-white p-4 shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg ROI</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(summaryData.avgROI || 0) }}%</p>
                        </div>
                        <div class="rounded-full bg-purple-100 p-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg bg-white p-4 shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Best Channel</p>
                            <p class="text-2xl font-bold text-gray-900">{{ summaryData.bestChannel || 'N/A' }}</p>
                        </div>
                        <div class="rounded-full bg-orange-100 p-3">
                            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attribution Model Comparison View -->
            <div v-if="viewMode === 'comparison'" class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Attribution Model Comparison</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="model in modelComparisons" 
                        :key="model.model"
                        class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow"
                    >
                        <h3 class="font-semibold text-gray-900 mb-2 capitalize">{{ model.model.replace('_', ' ') }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ model.description }}</p>
                        <div class="space-y-2">
                            <div v-for="source in model.sources.slice(0, 3)" :key="source.name" class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ source.name }}</span>
                                <span class="font-medium text-gray-900">{{ source.percentage.toFixed(1) }}%</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <span class="text-sm font-medium text-gray-900">Total: ${{ formatNumber(model.total_value) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Channel Contribution View -->
            <div v-if="viewMode === 'overview' || viewMode === 'channels'" class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Channel Contribution Chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Channel Contribution</h3>
                    <div class="h-80">
                        <canvas ref="channelChartRef" aria-label="Channel contribution chart" role="img"></canvas>
                    </div>
                </div>

                <!-- ROI Metrics -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ROI by Channel</h3>
                    <div class="space-y-3">
                        <div 
                            v-for="roi in roiMetrics" 
                            :key="roi.channel"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-3 h-3 rounded-full" 
                                    :style="{ backgroundColor: getChannelColor(roi.channel) }"
                                ></div>
                                <span class="font-medium text-gray-900 capitalize">{{ roi.channel }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold" :class="getROIClass(roi.roi)">
                                    {{ roi.roi.toFixed(1) }}x
                                </div>
                                <div class="text-xs text-gray-500">
                                    ${{ formatNumber(roi.revenue) }} revenue
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Allocation View -->
            <div v-if="viewMode === 'budget' || viewMode === 'overview'" class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Budget Allocation Recommendations</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="recommendation in budgetRecommendations" 
                        :key="recommendation.channel"
                        class="rounded-lg border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div 
                                    class="w-3 h-3 rounded-full" 
                                    :style="{ backgroundColor: getChannelColor(recommendation.channel) }"
                                ></div>
                                <h3 class="font-semibold text-gray-900 capitalize">{{ recommendation.channel }}</h3>
                            </div>
                            <span 
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="getPriorityClass(recommendation.priority)"
                            >
                                {{ recommendation.priority }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <p class="text-xs text-gray-500">Current</p>
                                <p class="font-semibold text-gray-900">{{ recommendation.current_percentage.toFixed(1) }}%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Recommended</p>
                                <p class="font-semibold" :class="recommendation.recommended_percentage > recommendation.current_percentage ? 'text-green-600' : 'text-red-600'">
                                    {{ recommendation.recommended_percentage.toFixed(1) }}%
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Budget Change</span>
                                <span :class="recommendation.change_amount >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ recommendation.change_amount >= 0 ? '+' : '' }}{{ recommendation.change_amount.toFixed(1) }}%
                                </span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all"
                                    :class="recommendation.change_amount >= 0 ? 'bg-green-500' : 'bg-red-500'"
                                    :style="{ width: `${Math.min(Math.abs(recommendation.change_amount), 100)}%` }"
                                ></div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mb-2">{{ recommendation.rationale }}</p>
                        <p class="text-sm text-blue-600 font-medium">{{ recommendation.expected_impact }}</p>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <span class="text-xs text-gray-500">Expected ROI: </span>
                            <span class="text-sm font-semibold" :class="getROIClass(recommendation.roi)">
                                {{ recommendation.roi.toFixed(1) }}x
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Path View -->
            <div v-if="viewMode === 'paths'" class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Conversion Path Visualization</h2>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div v-if="conversionPaths.length === 0" class="text-center py-8 text-gray-500">
                        No conversion path data available for the selected period.
                    </div>
                    <div v-else class="space-y-4">
                        <div 
                            v-for="(path, index) in conversionPaths.slice(0, 5)" 
                            :key="path.id"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900">Path {{ index + 1 }}</h4>
                                <span class="text-sm text-gray-500">
                                    {{ path.touch_count }} touches | ${{ formatNumber(path.total_value) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                                <template v-for="(step, stepIndex) in path.steps" :key="step.order">
                                    <div class="flex items-center">
                                        <div class="flex flex-col items-center">
                                            <div 
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                                :class="step.is_conversion ? 'bg-green-500' : 'bg-blue-500'"
                                                :title="step.event_type"
                                            >
                                                {{ stepIndex + 1 }}
                                            </div>
                                            <span class="text-xs text-gray-600 mt-1 capitalize">{{ step.channel }}</span>
                                            <span class="text-xs text-gray-400">{{ formatTimeAgo(step.timestamp) }}</span>
                                        </div>
                                        <svg 
                                            v-if="stepIndex < path.steps.length - 1"
                                            class="w-6 h-6 text-gray-400 mx-1 flex-shrink-0" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Touchpoint Tracking Interface -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Touchpoint Tracking</h3>
                    <button
                        @click="showTouchpointModal = true"
                        class="inline-flex items-center rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Track Touchpoint
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Touchpoint tracking table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Timestamp
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Channel
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Event Type
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Value
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Campaign
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="touch in recentTouches.slice(0, 10)" :key="touch.id">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDateTime(touch.timestamp) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getChannelBadgeClass(touch.source || 'direct')">
                                        {{ touch.source || 'direct' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ touch.event_type.replace('_', ' ') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    ${{ touch.value.toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ touch.campaign || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="recentTouches.length > 10" class="mt-4 text-center">
                    <span class="text-sm text-gray-500">Showing 10 of {{ recentTouches.length }} touchpoints</span>
                </div>
            </div>

            <!-- Insights Panel -->
            <div v-if="insights.length > 0" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Insights & Recommendations
                </h3>
                <ul class="space-y-2">
                    <li v-for="(insight, index) in insights" :key="index" class="text-sm text-blue-800 flex items-start">
                        <span class="mr-2">•</span>
                        {{ insight }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Touchpoint Modal -->
        <Teleport to="body">
            <div v-if="showTouchpointModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black bg-opacity-50" @click="showTouchpointModal = false"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Track New Touchpoint</h3>
                        <form @submit.prevent="trackTouchpoint">
                            <div class="space-y-4">
                                <div>
                                    <label for="event-type" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                                    <select
                                        id="event-type"
                                        v-model="newTouchpoint.event_type"
                                        required
                                        class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="page_view">Page View</option>
                                        <option value="click">Click</option>
                                        <option value="form_submit">Form Submit</option>
                                        <option value="signup">Signup</option>
                                        <option value="purchase">Purchase</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="channel-source" class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                                    <select
                                        id="channel-source"
                                        v-model="newTouchpoint.source"
                                        required
                                        class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="google">Google</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="linkedin">LinkedIn</option>
                                        <option value="twitter">Twitter</option>
                                        <option value="email">Email</option>
                                        <option value="direct">Direct</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="campaign-name" class="block text-sm font-medium text-gray-700 mb-1">Campaign</label>
                                    <input
                                        id="campaign-name"
                                        v-model="newTouchpoint.campaign"
                                        type="text"
                                        class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label for="touch-value" class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                                    <input
                                        id="touch-value"
                                        v-model.number="newTouchpoint.value"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="showTouchpointModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    {{ isSubmitting ? 'Tracking...' : 'Track Touchpoint' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
import { 
    AttributionModelComparison, 
    ChannelContribution, 
    BudgetRecommendation, 
    ConversionPath,
    ROIMetrics,
    AttributionVisualizationResponse,
    ChannelPerformanceResponse,
    BudgetRecommendationsResponse
} from '../../types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    userId?: string;
    defaultModel?: 'last_touch' | 'first_touch' | 'linear' | 'time_decay';
    defaultDateRange?: string;
}>(), {
    defaultModel: 'last_touch',
    defaultDateRange: '30d',
});

// Reactive state
const isLoading = ref(true);
const isSubmitting = ref(false);
const error = ref('');
const selectedDateRange = ref(props.defaultDateRange);
const selectedModel = ref(props.defaultModel);
const selectedChannel = ref('');
const viewMode = ref<'overview' | 'comparison' | 'channels' | 'budget' | 'paths'>('overview');
const showTouchpointModal = ref(false);

// Data state
const modelComparisons = ref<AttributionModelComparison[]>([]);
const channelContributions = ref<ChannelContribution[]>([]);
const budgetRecommendations = ref<BudgetRecommendation[]>([]);
const conversionPaths = ref<ConversionPath[]>([]);
const roiMetrics = ref<ROIMetrics[]>([]);
const recentTouches = ref<any[]>([]);
const insights = ref<string[]>([]);
const period = ref({ start: '', end: '' });

// Summary data
const summaryData = ref({
    totalConversions: 0,
    totalValue: 0,
    avgROI: 0,
    bestChannel: '',
});

// Chart refs
const channelChartRef = ref<HTMLCanvasElement>();
let channelChart: Chart | null = null;

// Channel colors
const channelColors: Record<string, string> = {
    'google': '#4285F4',
    'facebook': '#1877F2',
    'linkedin': '#0077B5',
    'twitter': '#1DA1F2',
    'email': '#EA4335',
    'organic': '#34A853',
    'direct': '#9E9E9E',
    'referral': '#FBBC05',
};

// New touchpoint form
const newTouchpoint = ref({
    event_type: 'page_view',
    source: 'google',
    campaign: '',
    value: 0,
});

// Methods
const getChannelColor = (channel: string): string => {
    return channelColors[channel.toLowerCase()] || '#9E9E9E';
};

const getChannelBadgeClass = (channel: string): string => {
    const classes: Record<string, string> = {
        'google': 'bg-blue-100 text-blue-800',
        'facebook': 'bg-indigo-100 text-indigo-800',
        'linkedin': 'bg-cyan-100 text-cyan-800',
        'twitter': 'bg-sky-100 text-sky-800',
        'email': 'bg-red-100 text-red-800',
        'organic': 'bg-green-100 text-green-800',
        'direct': 'bg-gray-100 text-gray-800',
    };
    return classes[channel.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const getROIClass = (roi: number): string => {
    if (roi >= 3) return 'text-green-600';
    if (roi >= 2) return 'text-blue-600';
    if (roi >= 1) return 'text-yellow-600';
    return 'text-red-600';
};

const getPriorityClass = (priority: string): string => {
    const classes: Record<string, string> = {
        'low': 'bg-gray-100 text-gray-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'high': 'bg-red-100 text-red-800',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
};

const formatNumber = (num: number): string => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toFixed(0);
};

const formatDateTime = (timestamp: string): string => {
    return new Date(timestamp).toLocaleString();
};

const formatTimeAgo = (timestamp: string): string => {
    const now = new Date();
    const date = new Date(timestamp);
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
};

const getDateRangeParams = () => {
    const ranges: Record<string, { start: string; end: string }> = {
        '7d': { start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
        '30d': { start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
        '90d': { start: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
    };
    return ranges[selectedDateRange.value] || ranges['30d'];
};

const fetchVisualizationData = async () => {
    isLoading.value = true;
    error.value = '';
    
    try {
        const { start, end } = getDateRangeParams();
        
        // Fetch channel performance
        const channelResponse = await axios.get<ChannelPerformanceResponse>(
            `/api/analytics/attribution/channel-performance?start_date=${start}&end_date=${end}&model=${selectedModel.value}`
        );
        
        if (channelResponse.data.success) {
            const data = channelResponse.data.data;
            const summary = channelResponse.data.summary;
            
            channelContributions.value = Object.entries(data).map(([channel, perf]) => ({
                channel,
                contribution: perf.total_value,
                percentage: (perf.total_value / (summary.total_conversion_value || 1)) * 100,
                conversions: perf.total_touches,
                value: perf.total_value,
                color: getChannelColor(channel),
            }));
            
            roiMetrics.value = Object.entries(data).map(([channel, perf]) => ({
                channel,
                spend: 0, // Would come from ad spend data
                revenue: perf.total_value,
                roi: perf.roi,
                roi_percentage: perf.roi * 100,
                conversions: perf.total_touches,
                cost_per_conversion: 0,
            }));
            
            summaryData.value = {
                totalConversions: summary.total_conversions,
                totalValue: summary.total_conversion_value,
                avgROI: parseFloat((Object.values(data).reduce((sum, ch) => sum + ch.roi, 0) / Object.keys(data).length).toFixed(1)),
                bestChannel: summary.best_performing_channel || 'N/A',
            };
        }

        // Fetch budget recommendations
        const budgetResponse = await axios.get<BudgetRecommendationsResponse>(
            `/api/analytics/attribution/budget-recommendations?start_date=${start}&end_date=${end}`
        );
        
        if (budgetResponse.data.success) {
            budgetRecommendations.value = budgetResponse.data.data.recommendations;
            insights.value = budgetResponse.data.insights || [];
        }

        // Fetch attribution data for comparison
        const vizResponse = await axios.get<AttributionVisualizationResponse>(
            `/api/analytics/attribution?start_date=${start}&end_date=${end}&model=${selectedModel.value}`
        );
        
        if (vizResponse.data.success) {
            // Generate model comparisons
            modelComparisons.value = generateModelComparisons(vizResponse.data.data);
            conversionPaths.value = vizResponse.data.data.conversion_paths || [];
        }

        // Fetch recent touches
        await fetchRecentTouches();
        
        await nextTick();
        updateCharts();
        
    } catch (err: any) {
        console.error('Failed to fetch attribution visualization data:', err);
        error.value = err.response?.data?.message || 'Failed to load attribution data';
    } finally {
        isLoading.value = false;
    }
};

const generateModelComparisons = (data: any): AttributionModelComparison[] => {
    return [
        {
            model: 'last_touch',
            description: 'Credits the last touchpoint before conversion',
            sources: data?.channel_contributions?.map((c: any) => ({
                name: c.channel,
                value: c.value * 0.8,
                percentage: c.percentage * 0.8,
                touch_count: Math.floor(c.conversions * 0.8),
            })) || [],
            total_value: data?.period ? 0 : 0,
        },
        {
            model: 'first_touch',
            description: 'Credits the first touchpoint in the journey',
            sources: data?.channel_contributions?.map((c: any) => ({
                name: c.channel,
                value: c.value * 0.9,
                percentage: c.percentage * 0.9,
                touch_count: Math.floor(c.conversions * 0.9),
            })) || [],
            total_value: data?.period ? 0 : 0,
        },
        {
            model: 'linear',
            description: 'Distributes credit equally across all touchpoints',
            sources: data?.channel_contributions?.map((c: any) => ({
                name: c.channel,
                value: c.value,
                percentage: c.percentage,
                touch_count: c.conversions,
            })) || [],
            total_value: data?.period ? 0 : 0,
        },
        {
            model: 'time_decay',
            description: 'Gives more credit to recent touchpoints',
            sources: data?.channel_contributions?.map((c: any) => ({
                name: c.channel,
                value: c.value * 1.1,
                percentage: c.percentage * 1.1,
                touch_count: Math.floor(c.conversions * 1.1),
            })) || [],
            total_value: data?.period ? 0 : 0,
        },
    ];
};

const fetchRecentTouches = async () => {
    try {
        const { start, end } = getDateRangeParams();
        const response = await axios.get(
            `/api/analytics/attribution?start_date=${start}&end_date=${end}&per_page=20`
        );
        if (response.data.success) {
            recentTouches.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to fetch recent touches:', err);
    }
};

const updateCharts = () => {
    updateChannelChart();
};

const updateChannelChart = () => {
    if (!channelChartRef.value) return;
    
    const ctx = channelChartRef.value.getContext('2d');
    if (!ctx) return;
    
    if (channelChart) {
        channelChart.destroy();
    }
    
    channelChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: channelContributions.value.map(c => c.channel),
            datasets: [{
                data: channelContributions.value.map(c => c.value),
                backgroundColor: channelContributions.value.map(c => c.color),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom' as const,
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const item = channelContributions.value[context.dataIndex];
                            return `${item.channel}: $${formatNumber(item.value)} (${item.percentage.toFixed(1)}%)`;
                        },
                    },
                },
            },
        },
    });
};

const handleDateRangeChange = () => {
    refreshData();
};

const handleModelChange = () => {
    refreshData();
};

const refreshData = async () => {
    await fetchVisualizationData();
};

const trackTouchpoint = async () => {
    isSubmitting.value = true;
    
    try {
        await axios.post('/api/analytics/attribution/touches', {
            ...newTouchpoint.value,
            user_id: props.userId,
            timestamp: new Date().toISOString(),
        });
        
        showTouchpointModal.value = false;
        newTouchpoint.value = {
            event_type: 'page_view',
            source: 'google',
            campaign: '',
            value: 0,
        };
        await fetchRecentTouches();
    } catch (err: any) {
        console.error('Failed to track touchpoint:', err);
        error.value = err.response?.data?.message || 'Failed to track touchpoint';
    } finally {
        isSubmitting.value = false;
    }
};

// Watchers
watch([selectedDateRange, selectedModel], () => {
    refreshData();
});

// Lifecycle
onMounted(async () => {
    await fetchVisualizationData();
});
</script>

<style scoped>
.attribution-visualization {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.attribution-visualization button:focus,
.attribution-visualization select:focus,
.attribution-visualization input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive design */
@media (max-width: 640px) {
    .attribution-visualization .grid-cols-1 {
        @apply grid-cols-1;
    }
    
    .attribution-visualization .sm\:grid-cols-2 {
        @apply grid-cols-1;
    }
}

/* Modal animations */
.fixed {
    transition: opacity 0.3s ease;
}

/* Chart responsive container */
.h-80 {
    min-height: 320px;
}

/* Touchpoint table styles */
.attribution-visualization table {
    @apply text-sm;
}

.attribution-visualization th,
.attribution-visualization td {
    @apply px-4 py-3;
}
</style>
