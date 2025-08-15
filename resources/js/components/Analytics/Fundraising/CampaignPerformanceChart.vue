<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Campaign Performance
      </h3>
      <div class="flex space-x-2">
        <select
          v-model="selectedMetric"
          class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
        >
          <option value="goal_achievement">Goal Achievement</option>
          <option value="roi">ROI</option>
          <option value="donor_count">Donor Count</option>
          <option value="daily_average">Daily Average</option>
        </select>
      </div>
    </div>

    <div v-if="chartData.length > 0" class="h-80">
      <canvas ref="chartCanvas"></canvas>
    </div>
    
    <div v-else class="h-80 flex items-center justify-center text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <p>No campaign data available</p>
      </div>
    </div>

    <!-- Top Performing Campaigns -->
    <div v-if="topCampaigns.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
        Top Performing Campaigns
      </h4>
      <div class="space-y-2">
        <div
          v-for="campaign in topCampaigns"
          :key="campaign.campaign_id"
          class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
        >
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ campaign.campaign_title }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ campaign.campaign_type }} • {{ campaign.performance_metrics.days_active }} days
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ formatCurrency(campaign.performance_metrics.total_raised) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ campaign.performance_metrics.goal_achievement_percentage.toFixed(1) }}% of goal
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  campaigns: {
    type: Array,
    required: true
  }
})

const chartCanvas = ref(null)
const chartInstance = ref(null)
const selectedMetric = ref('goal_achievement')

const chartData = computed(() => {
  if (!props.campaigns || props.campaigns.length === 0) return []
  
  return props.campaigns.map(campaign => ({
    id: campaign.campaign_id,
    title: campaign.campaign_title,
    type: campaign.campaign_type,
    goal_achievement: campaign.performance_metrics.goal_achievement_percentage,
    roi: campaign.roi_metrics.roi_percentage,
    donor_count: campaign.performance_metrics.donor_count,
    daily_average: campaign.performance_metrics.daily_average,
    total_raised: campaign.performance_metrics.total_raised
  })).sort((a, b) => b[selectedMetric.value] - a[selectedMetric.value])
})

const topCampaigns = computed(() => {
  return chartData.value.slice(0, 5)
})

const formatCurrency = (value) => {
  if (!value) return '$0'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const getMetricLabel = (metric) => {
  const labels = {
    goal_achievement: 'Goal Achievement (%)',
    roi: 'ROI (%)',
    donor_count: 'Number of Donors',
    daily_average: 'Daily Average ($)'
  }
  return labels[metric] || metric
}

const getMetricColor = (value, metric) => {
  if (metric === 'goal_achievement') {
    if (value >= 100) return 'rgba(16, 185, 129, 0.8)' // Green
    if (value >= 75) return 'rgba(245, 158, 11, 0.8)' // Yellow
    return 'rgba(239, 68, 68, 0.8)' // Red
  }
  
  if (metric === 'roi') {
    if (value >= 200) return 'rgba(16, 185, 129, 0.8)' // Green
    if (value >= 100) return 'rgba(245, 158, 11, 0.8)' // Yellow
    return 'rgba(239, 68, 68, 0.8)' // Red
  }
  
  return 'rgba(59, 130, 246, 0.8)' // Blue
}

const createChart = () => {
  if (!chartCanvas.value || chartData.value.length === 0) return

  const ctx = chartCanvas.value.getContext('2d')
  
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }

  const data = chartData.value.slice(0, 10) // Show top 10 campaigns
  
  chartInstance.value = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(item => item.title.length > 20 ? item.title.substring(0, 20) + '...' : item.title),
      datasets: [{
        label: getMetricLabel(selectedMetric.value),
        data: data.map(item => item[selectedMetric.value]),
        backgroundColor: data.map(item => getMetricColor(item[selectedMetric.value], selectedMetric.value)),
        borderColor: data.map(item => getMetricColor(item[selectedMetric.value], selectedMetric.value).replace('0.8', '1')),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            title: function(context) {
              const index = context[0].dataIndex
              return data[index].title
            },
            label: function(context) {
              const value = context.parsed.y
              if (selectedMetric.value === 'daily_average') {
                return `${getMetricLabel(selectedMetric.value)}: ${formatCurrency(value)}`
              } else if (selectedMetric.value === 'goal_achievement' || selectedMetric.value === 'roi') {
                return `${getMetricLabel(selectedMetric.value)}: ${value.toFixed(1)}%`
              } else {
                return `${getMetricLabel(selectedMetric.value)}: ${value}`
              }
            },
            afterLabel: function(context) {
              const index = context.dataIndex
              const campaign = data[index]
              return [
                `Type: ${campaign.type}`,
                `Total Raised: ${formatCurrency(campaign.total_raised)}`
              ]
            }
          }
        }
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true,
            text: 'Campaigns'
          },
          ticks: {
            maxRotation: 45,
            minRotation: 45
          }
        },
        y: {
          display: true,
          title: {
            display: true,
            text: getMetricLabel(selectedMetric.value)
          },
          ticks: {
            callback: function(value) {
              if (selectedMetric.value === 'daily_average') {
                return formatCurrency(value)
              } else if (selectedMetric.value === 'goal_achievement' || selectedMetric.value === 'roi') {
                return value.toFixed(0) + '%'
              } else {
                return value
              }
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  nextTick(() => {
    createChart()
  })
})

watch(() => props.campaigns, () => {
  nextTick(() => {
    createChart()
  })
}, { deep: true })

watch(selectedMetric, () => {
  nextTick(() => {
    createChart()
  })
})
</script>