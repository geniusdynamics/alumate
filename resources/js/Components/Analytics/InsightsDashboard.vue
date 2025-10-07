<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-800">Analytics Insights</h2>
      <div class="flex space-x-3">
        <button
          @click="generateInsights"
          :disabled="isGenerating"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center disabled:opacity-50"
        >
          <span v-if="isGenerating" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating...
          </span>
          <span v-else>Generate Insights</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
        <select
          v-model="filters.period"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="last_7_days">Last 7 Days</option>
          <option value="last_14_days">Last 14 Days</option>
          <option value="last_30_days" selected>Last 30 Days</option>
          <option value="last_90_days">Last 90 Days</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
        <select
          v-model="filters.type"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All Types</option>
          <option value="trend">Trends</option>
          <option value="anomaly">Anomalies</option>
          <option value="recommendation">Recommendations</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Metric</label>
        <select
          v-model="filters.metric"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All Metrics</option>
          <option value="engagement">Engagement</option>
          <option value="learning_progress">Learning Progress</option>
          <option value="custom_events">Custom Events</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
        <select
          v-model="filters.severity"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All Severity</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>
    </div>

    <!-- Insights List -->
    <div v-if="insights.length > 0" class="space-y-4">
      <div
        v-for="insight in filteredInsights"
        :key="insight.id"
        :class="[
          'border rounded-lg p-4',
          insight.type === 'trend' ? 'border-blue-200 bg-blue-50' : '',
          insight.type === 'anomaly' ? 'border-red-200 bg-red-50' : '',
          insight.type === 'recommendation' ? 'border-green-200 bg-green-50' : ''
        ]"
        role="article"
        aria-label="Analytics insight"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center space-x-2 mb-2">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  insight.type === 'trend' ? 'bg-blue-100 text-blue-800' : '',
                  insight.type === 'anomaly' ? 'bg-red-100 text-red-800' : '',
                  insight.type === 'recommendation' ? 'bg-green-100 text-green-800' : ''
                ]"
              >
                {{ insight.type }}
              </span>
              <span
                v-if="insight.severity"
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  insight.severity === 'low' ? 'bg-gray-100 text-gray-800' : '',
                  insight.severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : '',
                  insight.severity === 'high' ? 'bg-orange-100 text-orange-800' : '',
                  insight.severity === 'critical' ? 'bg-red-100 text-red-800' : ''
                ]"
              >
                {{ insight.severity }}
              </span>
              <span class="text-xs text-gray-500">{{ formatDate(insight.timestamp) }}</span>
            </div>

            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ insight.description }}</h3>

            <!-- Trend Chart -->
            <div v-if="insight.data_points && insight.data_points.length > 0" class="mb-4">
              <div class="h-32">
                <canvas :ref="el => setChartRef(el as HTMLCanvasElement | null, insight.id)" :id="`chart-${insight.id}`" class="w-full h-full"></canvas>
              </div>
            </div>

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

            <!-- Recommendation -->
            <div v-if="insight.recommendation" class="bg-gray-50 p-3 rounded-md mb-3">
              <h4 class="font-medium text-gray-700 mb-1">Recommendation:</h4>
              <p class="text-sm text-gray-600">{{ insight.recommendation.description }}</p>
              <div class="mt-2 flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ insight.recommendation.type }}
                </span>
                <span class="text-xs text-gray-500">Expected: {{ insight.recommendation.expected_impact }}</span>
              </div>
            </div>

            <!-- Feedback Section -->
            <div class="mt-4">
              <div class="flex items-center space-x-2">
                <button
                  @click="openFeedbackModal(insight)"
                  class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                  Rate Effectiveness
                </button>
                <span v-if="insight.effectiveness !== undefined" class="text-sm text-gray-500">
                  Effectiveness: {{ insight.effectiveness }}/10
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!isLoading" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No insights yet</h3>
      <p class="mt-1 text-sm text-gray-500">Generate insights to see trends, anomalies, and recommendations.</p>
      <div class="mt-6">
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

    <!-- Feedback Modal -->
    <div v-if="showFeedbackModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" aria-labelledby="feedback-modal-title" role="dialog" aria-modal="true">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 id="feedback-modal-title" class="text-lg font-medium text-gray-900 mb-4">Rate Recommendation Effectiveness</h3>
          <div class="flex flex-col space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Effectiveness Score (1-10)</label>
              <input
                type="range"
                min="1"
                max="10"
                v-model="feedbackScore"
                class="w-full"
              />
              <div class="flex justify-between text-xs text-gray-500">
                <span>1 (Poor)</span>
                <span>{{ feedbackScore }}/10</span>
                <span>10 (Excellent)</span>
              </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Implementation Status</label>
              <select v-model="implementationStatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Any additional feedback..."
              ></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
              <button
                @click="closeFeedbackModal"
                class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
              >
                Cancel
              </button>
              <button
                @click="submitFeedback"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                Submit Feedback
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useInsightsStore } from '@/Stores/useInsightsStore';
import { Chart, registerables } from 'chart.js';
import type { Insight } from '@/Types/analytics';

Chart.register(...registerables);

interface Filters {
  period: string;
  type: string;
  metric: string;
  severity: string;
}

const insightsStore = useInsightsStore();
const insights = ref<Insight[]>([]);
const isLoading = ref(false);
const isGenerating = ref(false);
const showFeedbackModal = ref(false);
const selectedInsight = ref<Insight | null>(null);
const feedbackScore = ref(5);
const implementationStatus = ref('pending');
const feedbackNotes = ref('');
const chartInstances = ref<Record<string, Chart>>({});

const filters = ref<Filters>({
  period: 'last_30_days',
  type: '',
  metric: '',
  severity: ''
});

const filteredInsights = computed(() => {
  return insights.value.filter(insight => {
    if (filters.value.type && insight.type !== filters.value.type) return false;
    if (filters.value.metric && insight.metric !== filters.value.metric) return false;
    if (filters.value.severity && insight.severity !== filters.value.severity) return false;
    return true;
  });
});

onMounted(async () => {
  await loadInsights();
});

watch(filters, async () => {
  await loadInsights();
}, { deep: true });

async function loadInsights() {
  isLoading.value = true;
  error.value = null;
  
  try {
    insights.value = await insightsStore.fetchInsights({
      period: filters.value.period,
      type: filters.value.type,
      metric: filters.value.metric,
      severity: filters.value.severity
    });
  } catch (error) {
    error.value = error instanceof Error ? error.message : 'Failed to load insights';
    console.error('Failed to load insights:', error);
  } finally {
    isLoading.value = false;
  }
}

async function generateInsights() {
 isGenerating.value = true;
  error.value = null;
  
  try {
    const options = {
      period: filters.value.period,
      queue: true // Use queue for heavy computation
    };
    await insightsStore.generateInsights(options);
    // Refresh insights after generation
    await loadInsights();
  } catch (error) {
    error.value = error instanceof Error ? error.message : 'Failed to generate insights';
    console.error('Failed to generate insights:', error);
  } finally {
    isGenerating.value = false;
  }
}

function openFeedbackModal(insight: Insight) {
  selectedInsight.value = insight;
  feedbackScore.value = 5;
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

    // Update the local insight with the effectiveness score
    const index = insights.value.findIndex(i => i.id === selectedInsight.value?.id);
    if (index !== -1) {
      insights.value[index].effectiveness = feedbackScore.value;
    }

    closeFeedbackModal();
  } catch (error) {
    console.error('Failed to submit feedback:', error);
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
}

function setChartRef(el: HTMLCanvasElement | null, insightId: string) {
  if (el && insights.value.find(i => i.id === insightId)?.data_points) {
    // Destroy existing chart instance if it exists
    if (chartInstances.value[insightId]) {
      chartInstances.value[insightId].destroy();
    }

    // Create new chart
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

// Clean up chart instances when component unmounts
onUnmounted(() => {
  Object.values(chartInstances.value).forEach(chart => {
    chart.destroy();
  });
});
</script>