<template>
  <div class="space-y-8">
    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
          Total Donations
        </h4>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatNumber(patterns.total_donations) }}
        </p>
      </div>
      
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
          Total Amount
        </h4>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(patterns.total_amount) }}
        </p>
      </div>
      
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
          Average Gift
        </h4>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(patterns.average_donation) }}
        </p>
      </div>
      
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
          Median Gift
        </h4>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(patterns.median_donation) }}
        </p>
      </div>
    </div>

    <!-- Giving Frequency Analysis -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Donor Frequency Analysis
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">One-time Donors</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatNumber(patterns.giving_frequency.one_time_donors) }}
              </span>
            </div>
            
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Repeat Donors</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatNumber(patterns.giving_frequency.repeat_donors) }}
              </span>
            </div>
            
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Avg Gifts per Donor</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ patterns.giving_frequency.average_gifts_per_donor?.toFixed(1) }}
              </span>
            </div>
          </div>
        </div>
        
        <div class="h-48">
          <canvas ref="frequencyChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Gift Size Distribution -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Gift Size Distribution
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-3">
          <div
            v-for="(count, range) in patterns.gift_size_distribution"
            :key="range"
            class="flex justify-between items-center"
          >
            <span class="text-sm text-gray-600 dark:text-gray-400">
              {{ formatGiftRange(range) }}
            </span>
            <div class="flex items-center space-x-2">
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatNumber(count) }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                ({{ getPercentage(count, patterns.total_donations) }}%)
              </span>
            </div>
          </div>
        </div>
        
        <div class="h-48">
          <canvas ref="distributionChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Seasonal Patterns -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Seasonal Giving Patterns
      </h3>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Peak Periods
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Peak Month</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ formatMonth(patterns.seasonal_patterns.peak_month) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Peak Quarter</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ patterns.seasonal_patterns.peak_quarter }}
              </span>
            </div>
          </div>
        </div>
        
        <div class="h-48">
          <canvas ref="seasonalChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recurring vs One-time -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Recurring vs One-time Donations
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
              Recurring Donations
            </h4>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">
              {{ formatCurrency(patterns.recurring_vs_one_time.recurring_amount) }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ formatNumber(patterns.recurring_vs_one_time.recurring_count) }} donations
            </p>
          </div>
          
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
              One-time Donations
            </h4>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
              {{ formatCurrency(patterns.recurring_vs_one_time.one_time_amount) }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ formatNumber(patterns.recurring_vs_one_time.one_time_count) }} donations
            </p>
          </div>
        </div>
        
        <div class="h-48">
          <canvas ref="recurringChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Donor Retention -->
    <div v-if="patterns.donor_retention" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Donor Retention Analysis
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="mb-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">Average Retention Rate</span>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ patterns.donor_retention.average_retention_rate?.toFixed(1) }}%
            </p>
          </div>
          
          <div class="space-y-2">
            <div
              v-for="(rate, period) in patterns.donor_retention.retention_rates"
              :key="period"
              class="flex justify-between items-center"
            >
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ period }}</span>
              <span class="font-medium text-gray-900 dark:text-white">
                {{ rate.toFixed(1) }}%
              </span>
            </div>
          </div>
        </div>
        
        <div class="h-48">
          <canvas ref="retentionChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  patterns: {
    type: Object,
    required: true
  }
})

const frequencyChart = ref(null)
const distributionChart = ref(null)
const seasonalChart = ref(null)
const recurringChart = ref(null)
const retentionChart = ref(null)

const formatCurrency = (value) => {
  if (!value) return '$0'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const formatNumber = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('en-US').format(value)
}

const formatGiftRange = (range) => {
  const ranges = {
    under_100: 'Under $100',
    '100_to_500': '$100 - $500',
    '500_to_1000': '$500 - $1,000',
    '1000_to_5000': '$1,000 - $5,000',
    '5000_plus': '$5,000+'
  }
  return ranges[range] || range
}

const formatMonth = (monthYear) => {
  if (!monthYear) return 'N/A'
  const [year, month] = monthYear.split('-')
  const date = new Date(year, month - 1)
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}

const getPercentage = (value, total) => {
  if (!total || total === 0) return 0
  return ((value / total) * 100).toFixed(1)
}

const createFrequencyChart = () => {
  if (!frequencyChart.value) return
  
  const ctx = frequencyChart.value.getContext('2d')
  
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['One-time Donors', 'Repeat Donors'],
      datasets: [{
        data: [
          props.patterns.giving_frequency.one_time_donors,
          props.patterns.giving_frequency.repeat_donors
        ],
        backgroundColor: [
          'rgba(239, 68, 68, 0.8)',
          'rgba(16, 185, 129, 0.8)'
        ],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  })
}

const createDistributionChart = () => {
  if (!distributionChart.value) return
  
  const ctx = distributionChart.value.getContext('2d')
  
  const data = Object.entries(props.patterns.gift_size_distribution)
  
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(([range]) => formatGiftRange(range)),
      datasets: [{
        label: 'Number of Donations',
        data: data.map(([, count]) => count),
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
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
  })
}

const createSeasonalChart = () => {
  if (!seasonalChart.value || !props.patterns.seasonal_patterns.monthly) return
  
  const ctx = seasonalChart.value.getContext('2d')
  
  const monthlyData = Object.entries(props.patterns.seasonal_patterns.monthly)
    .sort(([a], [b]) => new Date(a + '-01') - new Date(b + '-01'))
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: monthlyData.map(([date]) => {
        const [year, month] = date.split('-')
        return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short' })
      }),
      datasets: [{
        label: 'Amount',
        data: monthlyData.map(([, data]) => data.amount),
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4
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
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return formatCurrency(value)
            }
          }
        }
      }
    }
  })
}

const createRecurringChart = () => {
  if (!recurringChart.value) return
  
  const ctx = recurringChart.value.getContext('2d')
  
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Recurring', 'One-time'],
      datasets: [{
        data: [
          props.patterns.recurring_vs_one_time.recurring_amount,
          props.patterns.recurring_vs_one_time.one_time_amount
        ],
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(59, 130, 246, 0.8)'
        ],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `${context.label}: ${formatCurrency(context.parsed)}`
            }
          }
        }
      }
    }
  })
}

const createRetentionChart = () => {
  if (!retentionChart.value || !props.patterns.donor_retention) return
  
  const ctx = retentionChart.value.getContext('2d')
  
  const retentionData = Object.entries(props.patterns.donor_retention.retention_rates)
  
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: retentionData.map(([period]) => period),
      datasets: [{
        label: 'Retention Rate (%)',
        data: retentionData.map(([, rate]) => rate),
        backgroundColor: 'rgba(245, 158, 11, 0.8)',
        borderColor: 'rgba(245, 158, 11, 1)',
        borderWidth: 1
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
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: function(value) {
              return value + '%'
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  nextTick(() => {
    createFrequencyChart()
    createDistributionChart()
    createSeasonalChart()
    createRecurringChart()
    createRetentionChart()
  })
})
</script>