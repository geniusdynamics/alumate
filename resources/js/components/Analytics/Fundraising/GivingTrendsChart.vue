<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Giving Trends
      </h3>
      <div class="flex space-x-2">
        <button
          v-for="period in periods"
          :key="period.value"
          @click="selectedPeriod = period.value"
          :class="[
            'px-3 py-1 text-sm rounded-md transition-colors',
            selectedPeriod === period.value
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
              : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          {{ period.label }}
        </button>
      </div>
    </div>

    <div v-if="chartData.length > 0" class="h-80">
      <canvas ref="chartCanvas"></canvas>
    </div>
    
    <div v-else class="h-80 flex items-center justify-center text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <p>No trend data available</p>
      </div>
    </div>

    <!-- Growth Rate Indicator -->
    <div v-if="trends.growth_rate !== undefined" class="mt-4 flex items-center justify-between text-sm">
      <span class="text-gray-600 dark:text-gray-400">Growth Rate:</span>
      <span :class="[
        'font-medium',
        trends.growth_rate >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
      ]">
        {{ trends.growth_rate >= 0 ? '+' : '' }}{{ trends.growth_rate.toFixed(1) }}%
      </span>
    </div>

    <!-- Forecast -->
    <div v-if="trends.forecasted_next_month" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
      <span>Next Month Forecast: </span>
      <span class="font-medium">
        {{ formatCurrency(trends.forecasted_next_month.forecasted_amount) }}
      </span>
      <span class="text-xs ml-1">
        ({{ trends.forecasted_next_month.confidence }} confidence)
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  trends: {
    type: Object,
    required: true
  }
})

const chartCanvas = ref(null)
const chartInstance = ref(null)
const selectedPeriod = ref('monthly')

const periods = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' }
]

const chartData = computed(() => {
  if (!props.trends.monthly_trends) return []
  
  return Object.entries(props.trends.monthly_trends).map(([date, data]) => ({
    date,
    amount: data.amount,
    count: data.count,
    unique_donors: data.unique_donors
  })).sort((a, b) => new Date(a.date) - new Date(b.date))
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

const createChart = () => {
  if (!chartCanvas.value || chartData.value.length === 0) return

  const ctx = chartCanvas.value.getContext('2d')
  
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }

  chartInstance.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartData.value.map(item => {
        const date = new Date(item.date + '-01')
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
      }),
      datasets: [
        {
          label: 'Amount Raised',
          data: chartData.value.map(item => item.amount),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          fill: true,
          tension: 0.4,
          yAxisID: 'y'
        },
        {
          label: 'Number of Donations',
          data: chartData.value.map(item => item.count),
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: false,
          tension: 0.4,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              if (context.datasetIndex === 0) {
                return `Amount: ${formatCurrency(context.parsed.y)}`
              } else {
                return `Donations: ${context.parsed.y}`
              }
            }
          }
        }
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true,
            text: 'Month'
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.1)'
          }
        },
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: {
            display: true,
            text: 'Amount ($)'
          },
          ticks: {
            callback: function(value) {
              return formatCurrency(value)
            }
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.1)'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Number of Donations'
          },
          grid: {
            drawOnChartArea: false,
          },
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

watch(() => props.trends, () => {
  nextTick(() => {
    createChart()
  })
}, { deep: true })

watch(selectedPeriod, () => {
  nextTick(() => {
    createChart()
  })
})
</script>