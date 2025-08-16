<template>
  <div class="space-y-8">
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">High Likelihood Donors</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ predictions.giving_likelihood?.filter(d => d.category === 'high').length || 0 }}
            </p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upgrade Prospects</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ predictions.upgrade_potential?.length || 0 }}
            </p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">At-Risk Donors</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
              {{ predictions.lapse_risk?.filter(d => d.risk_category === 'high').length || 0 }}
            </p>
          </div>
          <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-full">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Predicted Ask</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
              ${{ formatNumber(getAverageAsk()) }}
            </p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Giving Likelihood Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Giving Likelihood Predictions</h3>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          <canvas ref="likelihoodChart" class="max-h-80"></canvas>
        </div>
        
        <div class="space-y-4">
          <div class="max-h-80 overflow-y-auto">
            <div
              v-for="donor in predictions.giving_likelihood?.slice(0, 10) || []"
              :key="donor.donor_id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-2"
            >
              <div class="flex items-center space-x-3">
                <div
                  class="w-3 h-3 rounded-full"
                  :class="{
                    'bg-green-500': donor.category === 'high',
                    'bg-yellow-500': donor.category === 'medium',
                    'bg-red-500': donor.category === 'low'
                  }"
                ></div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Donor #{{ donor.donor_id }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ donor.category }} likelihood
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                  {{ donor.likelihood_score }}%
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upgrade Potential -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Donor Upgrade Potential</h3>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Donor ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Current Giving
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Capacity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Upgrade Potential
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Action
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="donor in predictions.upgrade_potential?.slice(0, 10) || []" :key="donor.donor_id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                #{{ donor.donor_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${{ formatNumber(donor.current_giving) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${{ formatNumber(donor.capacity) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                  ${{ formatNumber(donor.upgrade_potential) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                  Contact
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lapse Risk Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Donor Lapse Risk Analysis</h3>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div
          v-for="riskLevel in ['high', 'medium', 'low']"
          :key="riskLevel"
          class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
        >
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white capitalize">
              {{ riskLevel }} Risk
            </h4>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="{
                'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': riskLevel === 'high',
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': riskLevel === 'medium',
                'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': riskLevel === 'low'
              }"
            >
              {{ getRiskCount(riskLevel) }} donors
            </span>
          </div>
          
          <div class="space-y-2 max-h-40 overflow-y-auto">
            <div
              v-for="donor in getRiskDonors(riskLevel).slice(0, 5)"
              :key="donor.donor_id"
              class="flex justify-between items-center text-sm"
            >
              <span class="text-gray-600 dark:text-gray-400">
                #{{ donor.donor_id }}
              </span>
              <span class="text-gray-900 dark:text-white">
                {{ donor.days_since_last_gift }}d ago
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Optimal Ask Amounts -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Optimal Ask Amount Recommendations</h3>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Donor ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Suggested Ask
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Confidence
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Best Contact Time
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(ask, index) in predictions.optimal_ask_amounts?.slice(0, 10) || []" :key="ask.donor_id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                #{{ ask.donor_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-semibold">
                ${{ formatNumber(ask.suggested_ask) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': ask.confidence === 'high',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': ask.confidence === 'medium',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400': ask.confidence === 'low'
                  }"
                >
                  {{ ask.confidence }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ getContactTiming(ask.donor_id) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  predictions: {
    type: Object,
    required: true
  }
})

const likelihoodChart = ref(null)
let chartInstance = null

const formatNumber = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('en-US').format(value)
}

const getAverageAsk = () => {
  if (!props.predictions.optimal_ask_amounts?.length) return 0
  const total = props.predictions.optimal_ask_amounts.reduce((sum, ask) => sum + ask.suggested_ask, 0)
  return Math.round(total / props.predictions.optimal_ask_amounts.length)
}

const getRiskCount = (riskLevel) => {
  return props.predictions.lapse_risk?.filter(d => d.risk_category === riskLevel).length || 0
}

const getRiskDonors = (riskLevel) => {
  return props.predictions.lapse_risk?.filter(d => d.risk_category === riskLevel) || []
}

const getContactTiming = (donorId) => {
  const timing = props.predictions.best_contact_timing?.find(t => t.donor_id === donorId)
  if (!timing) return 'N/A'
  
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  
  const month = timing.best_month ? months[timing.best_month - 1] : ''
  const day = timing.best_day_of_week !== null ? days[timing.best_day_of_week] : ''
  
  return [month, day].filter(Boolean).join(', ') || 'N/A'
}

const createLikelihoodChart = () => {
  if (!likelihoodChart.value || !props.predictions.giving_likelihood) return
  
  const ctx = likelihoodChart.value.getContext('2d')
  
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const categories = ['high', 'medium', 'low']
  const data = categories.map(category => 
    props.predictions.giving_likelihood.filter(d => d.category === category).length
  )
  
  chartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['High Likelihood', 'Medium Likelihood', 'Low Likelihood'],
      datasets: [{
        data: data,
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)'
        ],
        borderWidth: 2,
        borderColor: '#ffffff'
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
            label: (context) => {
              const total = data.reduce((sum, value) => sum + value, 0)
              const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0
              return `${context.label}: ${context.parsed} donors (${percentage}%)`
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  nextTick(() => {
    createLikelihoodChart()
  })
})
</script>