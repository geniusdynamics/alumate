<template>
  <div class="insights-dashboard bg-white shadow rounded-lg" :class="{ 'p-4 md:p-6': !dense }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div class="flex items-center space-x-3">
        <h2 class="text-xl font-bold text-gray-800">Analytics Insights</h2>
        <span
          v-if="insights.length > 0"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          {{ insights.length }} {{ insights.length === 1 ? 'insight' : 'insights' }}
        </span>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          @click="generateInsights"
          :disabled="isGenerating"
          class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg
            v-if="isGenerating"
            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          <span v-if="isGenerating">Generating...</span>
          <span v-else>Generate Insights</span>
        </button>
        <button
          @click="showExportModal = true"
          :disabled="insights.length === 0"
          class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="-ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="showSummary && insights.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-blue-50 rounded-lg p-3">
        <div class="text-2xl font-bold text-blue-600">{{ summary.by_type.trend }}</div>
        <div class="text-sm text-gray-600">Trends</div>
      </div>
      <div class="bg-red-50 rounded-lg p-3">
        <div class="text-2xl font-bold text-red-600">{{ summary.by_type.anomaly }}</div>
        <div class="text-sm text-gray-600">Anomalies</div>
      </div>
      <div class="bg-green-50 rounded-lg p-3">
        <div class="text-2xl font-bold text-green-600">{{ summary.active }}</div>
        <div class="text-sm text-gray-600">Active</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-2xl font-bold text-gray-600">{{ summary.dismissed }}</div>
        <div class="text-sm text-gray-600">Dismissed</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <!-- Period Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
        <select
          v-model="filters.period"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          <option value="last_7_days">Last 7 Days</option>
          <option value="last_14_days">Last 14 Days</option>
          <option value="last_30_days">Last 30 Days</option>
          <option value="last_90_days">Last 90 Days</option>
          <option value="custom">Custom Range</option>
        </select>
      </div>

      <!-- Type Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
        <select
          v-model="filters.type"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          <option value="">All Types</option>
          <option value="trend">Trends</option>
          <option value="anomaly">Anomalies</option>
          <option value="recommendation">Recommendations</option>
        </select>
      </div>

      <!-- Status Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
          v-model="filters.status"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="dismissed">Dismissed</option>
          <option value="implemented">Implemented</option>
        </select>
      </div>

      <!-- Severity Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
        <select
          v-model="filters.severity"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          <option value="">All Severity</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>

      <!-- Sort -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
        <select
          v-model="sortBy"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          <option value="timestamp-desc">Newest First</option>
          <option value="timestamp-asc">Oldest First</option>
          <option value="severity-desc">Highest Severity</option>
          <option value="severity-asc">Lowest Severity</option>
          <option value="effectiveness-desc">Most Effective</option>
        </select>
      </div>
    </div>

    <!-- Custom Date Range (conditional) -->
    <div v-if="filters.period === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
        <input
          type="date"
          v-model="filters.startDate"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
        <input
          type="date"
          v-model="filters.endDate"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        />
      </div>
    </div>

    <!-- Search -->
    <div class="mb-6">
      <div class="relative">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search insights..."
          class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        />
        <svg
          class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
    </div>

    <!-- Insights List -->
    <div v-if="paginatedInsights.length > 0" class="space-y-4">
      <TransitionGroup name="insight-list">
        <div
          v-for="insight in paginatedInsights"
          :key="insight.id"
          :class="[
            'border rounded-lg p-4 transition-all duration-200 hover:shadow-md',
            getInsightBorderClass(insight.type),
            insight.status === 'dismissed' ? 'opacity-60' : ''
          ]"
          role="article"
          aria-label="Analytics insight"
        >
          <!-- Insight Header -->
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <!-- Type Badge -->
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getTypeBadgeClass(insight.type)
                  ]"
                >
                  {{ formatType(insight.type) }}
                </span>
                <!-- Severity Badge -->
                <span
                  v-if="insight.severity"
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getSeverityBadgeClass(insight.severity)
                  ]"
                >
                  {{ insight.severity }}
                </span>
                <!-- Status Badge -->
                <span
                  v-if="insight.status"
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusBadgeClass(insight.status)
                  ]"
                >
                  {{ insight.status }}
                </span>
                <!-- Timestamp -->
                <span class="text-xs text-gray-500">{{ formatDate(insight.timestamp) }}</span>
              </div>

              <!-- Title -->
              <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ insight.description }}
              </h3>

              <!-- Metric -->
              <p v-if="insight.metric" class="text-sm text-gray-600 mb-2">
                <span class="font-medium">Metric:</span> {{ insight.metric }}
              </p>

              <!-- Trend Score -->
              <div v-if="insight.trend_score !== undefined" class="mb-2">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    insight.trend_score > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ insight.trend_score > 0 ? '+' : '' }}{{ insight.trend_score.toFixed(2) }}%
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <button
                @click="toggleDetail(insight)"
                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
                :title="expandedInsightId === insight.id ? 'Collapse' : 'Expand'"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  :class="['h-5 w-5 transition-transform', expandedInsightId === insight.id ? 'rotate-180' : '']"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <button
                v-if="insight.status !== 'dismissed'"
                @click="dismissInsight(insight)"
                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
                title="Dismiss"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Expanded Detail View -->
          <Transition name="slide-fade">
            <div v-if="expandedInsightId === insight.id" class="mt-4 pt-4 border-t">
              <!-- Trend Chart -->
              <div v-if="insight.data_points && insight.data_points.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Trend Data</h4>
                <div class="h-40">
                  <canvas :ref="(el) => setChartRef(el as HTMLCanvasElement | null, insight.id)" class="w-full h-full"></canvas>
                </div>
              </div>

              <!-- Recommendation -->
              <div v-if="insight.recommendation" class="bg-gray-50 p-4 rounded-md mb-4">
                <h4 class="font-medium text-gray-700 mb-2 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Recommendation
                </h4>
                <p class="text-sm text-gray-600 mb-3">{{ insight.recommendation.description }}</p>
                <div class="flex flex-wrap items-center gap-3">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                  >
                    {{ insight.recommendation.type }}
                  </span>
                  <span class="text-xs text-gray-500">
                    Expected Impact: {{ insight.recommendation.expected_impact }}
                  </span>
                  <span
                    v-if="insight.recommendation.priority"
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      getPriorityBadgeClass(insight.recommendation.priority)
                    ]"
                  >
                    {{ insight.recommendation.priority }} priority
                  </span>
                </div>
                <button
                  v-if="insight.recommendation.action"
                  @click="performAction(insight.recommendation.action)"
                  class="mt-3 inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md transition-colors"
                >
                  {{ insight.recommendation.action }}
                </button>
              </div>

              <!-- Effectiveness Rating -->
              <div v-if="insight.effectiveness !== undefined" class="mb-4">
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600">Effectiveness:</span>
                  <div class="flex items-center">
                    <template v-for="i in 10" :key="i">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        :class="[
                          'h-4 w-4',
                          i <= insight.effectiveness ? 'text-yellow-400' : 'text-gray-300'
                        ]"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                      </svg>
                    </template>
                  </div>
                  <span class="text-sm text-gray-500">({{ insight.effectiveness }}/10)</span>
                </div>
              </div>

              <!-- Feedback Section -->
              <div class="flex flex-wrap items-center gap-3 pt-2 border-t">
                <button
                  @click="openFeedbackModal(insight)"
                  class="inline-flex items-center px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                  Rate Effectiveness
                </button>
                <button
                  v-if="insight.status !== 'implemented'"
                  @click="markAsImplemented(insight)"
                  class="inline-flex items-center px-3 py-1.5 text-sm text-green-600 hover:text-green-800 hover:bg-green-50 rounded-md transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Mark as Implemented
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </TransitionGroup>
    </div>

    <!-- Empty State -->
    <div v-else-if="!isLoading" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No insights found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ searchQuery ? 'Try adjusting your search or filters' : 'Generate insights to see trends, anomalies, and recommendations.' }}
      </p>
      <div v-if="!searchQuery" class="mt-6">
        <button
          @click="generateInsights"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none"
        >
          Generate Insights
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else class="text-center py-12">
      <svg class="animate-spin mx-auto h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <p class="mt-2 text-sm text-gray-500">Loading insights...</p>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, filteredInsights.length) }} of {{ filteredInsights.length }} insights
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Feedback Modal -->
    <Teleport to="body">
      <div
        v-if="showFeedbackModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="feedback-modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" @click="closeFeedbackModal"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6 z-10">
            <h3 id="feedback-modal-title" class="text-lg font-medium text-gray-900 mb-4">
              Rate Recommendation Effectiveness
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Effectiveness Score (1-10)
                </label>
                <input
                  type="range"
                  min="1"
                  max="10"
                  v-model="feedbackScore"
                  class="w-full"
                />
                <div class="flex justify-between text-xs text-gray-500">
                  <span>1 (Poor)</span>
                  <span class="font-medium text-blue-600">{{ feedbackScore }}/10</span>
                  <span>10 (Excellent)</span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Implementation Status</label>
                <select
                  v-model="implementationStatus"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                  <option value="pending">Pending</option>
                  <option value="started">Started</option>
                  <option value="completed">Completed</option>
                  <option value="abandoned">Abandoned</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                <textarea
                  v-model="feedbackNotes"
                  rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                  placeholder="Any additional feedback..."
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3 pt-4">
                <button
                  @click="closeFeedbackModal"
                  class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="submitFeedback"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Submit Feedback
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Export Modal -->
    <Teleport to="body">
      <div
        v-if="showExportModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="export-modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" @click="showExportModal = false"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6 z-10">
            <h3 id="export-modal-title" class="text-lg font-medium text-gray-900 mb-4">
              Export Insights
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <select
                  v-model="exportFormat"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                  <option value="json">JSON</option>
                  <option value="csv">CSV</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Include</label>
                <div class="space-y-2">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="exportOptions.includeRecommendations" class="mr-2" />
                    <span class="text-sm">Recommendations</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="exportOptions.includeDataPoints" class="mr-2" />
                    <span class="text-sm">Data Points</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="exportOptions.includeFeedback" class="mr-2" />
                    <span class="text-sm">Feedback Data</span>
                  </label>
                </div>
              </div>
              <div class="flex justify-end space-x-3 pt-4">
                <button
                  @click="showExportModal = false"
                  class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="exportInsights"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Export
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useInsightsStore } from '@/stores/useInsightsStore';

// Extended Insight type with all properties used
interface DashboardInsight {
  id: string;
  type: 'trend' | 'anomaly' | 'recommendation';
  metric: string;
  description: string;
  trend_score?: number;
  data_points?: { date: string; value: number }[];
  anomaly?: boolean;
  severity?: 'low' | 'medium' | 'high' | 'critical';
  value?: number;
  baseline?: number;
  z_score?: number;
  recommendation?: {
    type: string;
    target: string;
    description: string;
    expected_impact: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    action: string;
    attribution_insights?: any;
  };
  effectiveness?: number;
  status?: 'active' | 'dismissed' | 'implemented';
  timestamp: string;
}

Chart.register(...registerables);

// Props
interface Props {
  dense?: boolean;
  showSummary?: boolean;
  autoRefresh?: boolean;
  refreshInterval?: number;
}

const props = withDefaults(defineProps<Props>(), {
  dense: false,
  showSummary: true,
  autoRefresh: false,
  refreshInterval: 60000
});

// Store
const insightsStore = useInsightsStore();

// State
const insights = ref<DashboardInsight[]>([]);
const isLoading = ref(false);
const isGenerating = ref(false);
const error = ref<string | null>(null);
const showFeedbackModal = ref(false);
const showExportModal = ref(false);
const selectedInsight = ref<DashboardInsight | null>(null);
const feedbackScore = ref(5);
const implementationStatus = ref('pending');
const feedbackNotes = ref('');
const expandedInsightId = ref<string | null>(null);
const chartInstances = ref<Record<string, Chart>>({});
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = 10;
const exportFormat = ref<'json' | 'csv'>('json');
const exportOptions = ref({
  includeRecommendations: true,
  includeDataPoints: true,
  includeFeedback: true
});

// Filters
const filters = ref({
  period: 'last_30_days',
  type: '',
  status: '',
  severity: '',
  startDate: '',
  endDate: ''
});

// Sort
const sortBy = ref('timestamp-desc');

// Summary
const summary = computed(() => {
  const byType: Record<string, number> = { trend: 0, anomaly: 0, recommendation: 0 };
  let active = 0;
  let dismissed = 0;
  let implemented = 0;

  insights.value.forEach(insight => {
    if (byType[insight.type] !== undefined) {
      byType[insight.type]++;
    }
    if (insight.status === 'active') active++;
    else if (insight.status === 'dismissed') dismissed++;
    else if (insight.status === 'implemented') implemented++;
  });

  return { total: insights.value.length, by_type: byType, active, dismissed, implemented };
});

// Filtered Insights
const filteredInsights = computed(() => {
  return insights.value.filter(insight => {
    // Search filter
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      const searchableText = [
        insight.description,
        insight.metric,
        insight.type,
        insight.severity,
        insight.status
      ].filter(Boolean).join(' ').toLowerCase();
      
      if (!searchableText.includes(query)) {
        return false;
      }
    }

    // Type filter
    if (filters.value.type && insight.type !== filters.value.type) {
      return false;
    }

    // Status filter
    if (filters.value.status && insight.status !== filters.value.status) {
      return false;
    }

    // Severity filter
    if (filters.value.severity && insight.severity !== filters.value.severity) {
      return false;
    }

    return true;
  }).sort((a, b) => {
    // Sorting
    switch (sortBy.value) {
      case 'timestamp-asc':
        return new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime();
      case 'timestamp-desc':
        return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime();
      case 'severity-desc':
        return getSeverityWeight(b.severity) - getSeverityWeight(a.severity);
      case 'severity-asc':
        return getSeverityWeight(a.severity) - getSeverityWeight(b.severity);
      case 'effectiveness-desc':
        return (b.effectiveness || 0) - (a.effectiveness || 0);
      default:
        return 0;
    }
  });
});

// Paginated Insights
const paginatedInsights = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredInsights.value.slice(start, start + pageSize);
});

// Total Pages
const totalPages = computed(() => {
  return Math.ceil(filteredInsights.value.length / pageSize);
});

// Real-time refresh interval
let refreshIntervalId: ReturnType<typeof setInterval> | null = null;

// Lifecycle
onMounted(async () => {
  await loadInsights();
  
  if (props.autoRefresh) {
    refreshIntervalId = setInterval(() => {
      loadInsights();
    }, props.refreshInterval);
  }
});

onUnmounted(() => {
  if (refreshIntervalId) {
    clearInterval(refreshIntervalId);
  }
  // Cleanup chart instances
  Object.values(chartInstances.value).forEach(chart => {
    chart.destroy();
  });
});

// Watchers
watch([filters, sortBy], () => {
  currentPage.value = 1;
});

// Methods
async function loadInsights() {
  isLoading.value = true;
  error.value = null;
  
  try {
    const params: Record<string, string> = {};
    
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.severity) params.severity = filters.value.severity;
    if (filters.value.period !== 'custom') {
      params.period = filters.value.period;
    } else {
      if (filters.value.startDate) params.start_date = filters.value.startDate;
      if (filters.value.endDate) params.end_date = filters.value.endDate;
    }
    
    const result = await insightsStore.fetchInsights(params);
    // Cast to DashboardInsight array
    insights.value = result as DashboardInsight[];
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load insights';
    console.error('Failed to load insights:', err);
  } finally {
    isLoading.value = false;
  }
}

async function generateInsights() {
  isGenerating.value = true;
  error.value = null;
  
  try {
    const options: Record<string, any> = {
      period: filters.value.period
    };
    
    if (filters.value.period === 'custom') {
      if (filters.value.startDate) options.start_date = filters.value.startDate;
      if (filters.value.endDate) options.end_date = filters.value.endDate;
    }
    
    await insightsStore.generateInsights(options);
    await loadInsights();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to generate insights';
    console.error('Failed to generate insights:', err);
  } finally {
    isGenerating.value = false;
  }
}

function toggleDetail(insight: DashboardInsight) {
  if (expandedInsightId.value === insight.id) {
    expandedInsightId.value = null;
  } else {
    expandedInsightId.value = insight.id;
  }
}

async function dismissInsight(insight: DashboardInsight) {
  try {
    await insightsStore.dismissInsight(insight.id);
    const index = insights.value.findIndex(i => i.id === insight.id);
    if (index !== -1) {
      insights.value[index].status = 'dismissed';
    }
  } catch (err) {
    console.error('Failed to dismiss insight:', err);
  }
}

async function markAsImplemented(insight: DashboardInsight) {
  try {
    await insightsStore.updateInsight(insight.id, { status: 'implemented' });
    const index = insights.value.findIndex(i => i.id === insight.id);
    if (index !== -1) {
      insights.value[index].status = 'implemented';
    }
  } catch (err) {
    console.error('Failed to update insight:', err);
  }
}

function openFeedbackModal(insight: DashboardInsight) {
  selectedInsight.value = insight;
  feedbackScore.value = insight.effectiveness || 5;
  implementationStatus.value = 'pending';
  feedbackNotes.value = '';
  showFeedbackModal.value = true;
}

function closeFeedbackModal() {
  showFeedbackModal.value = false;
  selectedInsight.value = null;
}

async function submitFeedback() {
  if (!selectedInsight.value) return;

  try {
    await insightsStore.trackFeedback(
      selectedInsight.value.id,
      feedbackScore.value,
      {
        implementation_status: implementationStatus.value,
        notes: feedbackNotes.value
      }
    );

    const index = insights.value.findIndex(i => i.id === selectedInsight.value?.id);
    if (index !== -1) {
      insights.value[index].effectiveness = feedbackScore.value;
    }

    closeFeedbackModal();
  } catch (err) {
    console.error('Failed to submit feedback:', err);
  }
}

async function exportInsights() {
  try {
    const data = await insightsStore.exportInsights({
      format: exportFormat.value,
      include_recommendations: exportOptions.value.includeRecommendations,
      include_data_points: exportOptions.value.includeDataPoints,
      include_feedback: exportOptions.value.includeFeedback
    });

    if (exportFormat.value === 'json') {
      downloadJson(data, 'insights-export.json');
    } else {
      downloadCsv(data, 'insights-export.csv');
    }

    showExportModal.value = false;
  } catch (err) {
    console.error('Failed to export insights:', err);
  }
}

function downloadJson(data: any, filename: string) {
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

function downloadCsv(data: any, filename: string) {
  const insights = Array.isArray(data) ? data : [data];
  if (insights.length === 0) return;

  const headers = Object.keys(insights[0]);
  const csv = [
    headers.join(','),
    ...insights.map(item => headers.map(h => {
      const value = item[h];
      if (typeof value === 'object') {
        return `"${JSON.stringify(value).replace(/"/g, '""')}"`;
      }
      return `"${value}"`;
    }).join(','))
  ].join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

function performAction(action: string) {
  // Emit event for parent components to handle
  emit('action', { action });
}

function setChartRef(el: HTMLCanvasElement | null, insightId: string) {
  if (el && insights.value.find(i => i.id === insightId)?.data_points) {
    // Destroy existing chart instance if it exists
    if (chartInstances.value[insightId]) {
      chartInstances.value[insightId].destroy();
    }

    const data = insights.value.find(i => i.id === insightId)?.data_points || [];
    const dates = data.map(d => d.date);
    const values = data.map(d => d.value);

    const ctx = el.getContext('2d');
    if (ctx) {
      chartInstances.value[insightId] = new Chart(ctx, {
        type: 'line',
        data: {
          labels: dates,
          datasets: [{
            label: 'Value',
            data: values,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }
  }
}

// Utility functions
function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatType(type: string): string {
  return type.charAt(0).toUpperCase() + type.slice(1);
}

function getInsightBorderClass(type: string): string {
  switch (type) {
    case 'trend':
      return 'border-blue-200 bg-blue-50/30';
    case 'anomaly':
      return 'border-red-200 bg-red-50/30';
    case 'recommendation':
      return 'border-green-200 bg-green-50/30';
    default:
      return 'border-gray-200';
  }
}

function getTypeBadgeClass(type: string): string {
  switch (type) {
    case 'trend':
      return 'bg-blue-100 text-blue-800';
    case 'anomaly':
      return 'bg-red-100 text-red-800';
    case 'recommendation':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function getSeverityBadgeClass(severity: string): string {
  switch (severity) {
    case 'critical':
      return 'bg-red-100 text-red-800';
    case 'high':
      return 'bg-orange-100 text-orange-800';
    case 'medium':
      return 'bg-yellow-100 text-yellow-800';
    case 'low':
      return 'bg-gray-100 text-gray-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function getStatusBadgeClass(status: string): string {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800';
    case 'dismissed':
      return 'bg-gray-100 text-gray-800';
    case 'implemented':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function getPriorityBadgeClass(priority: string): string {
  switch (priority) {
    case 'critical':
      return 'bg-red-100 text-red-800';
    case 'high':
      return 'bg-orange-100 text-orange-800';
    case 'medium':
      return 'bg-yellow-100 text-yellow-800';
    case 'low':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function getSeverityWeight(severity?: string): number {
  switch (severity) {
    case 'critical': return 4;
    case 'high': return 3;
    case 'medium': return 2;
    case 'low': return 1;
    default: return 0;
  }
}

// Emit events
const emit = defineEmits<{
  (e: 'action', payload: { action: string }): void;
  (e: 'error', error: string): void;
  (e: 'insight-click', insight: DashboardInsight): void;
}>();
</script>

<style scoped>
.insights-dashboard {
  @apply w-full;
}

/* Transition animations */
.insight-list-enter-active,
.insight-list-leave-active {
  transition: all 0.3s ease;
}

.insight-list-enter-from,
.insight-list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Range input styling */
input[type="range"] {
  @apply w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer;
}

input[type="range"]::-webkit-slider-thumb {
  @apply appearance-none w-5 h-5 bg-blue-600 rounded-full cursor-pointer;
}

input[type="range"]::-moz-range-thumb {
  @apply w-5 h-5 bg-blue-600 rounded-full cursor-pointer border-0;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .insights-dashboard {
    @apply p-3;
  }
}
</style>
