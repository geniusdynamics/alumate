<template>
  <div class="space-y-8">
    <!-- ROI Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average ROI</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ formatPercentage(getAverageROI()) }}%
            </p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
        </div>
        <div class="mt-2 flex items-center text-sm">
          <span class="text-gray-500 dark:text-gray-400">
            Across {{ campaigns.length }} campaigns
          </span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Investment</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              ${{ formatNumber(getTotalInvestment()) }}
            </p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
        <div class="mt-2 flex items-center text-sm">
          <span class="text-gray-500 dark:text-gray-400">
            Campaign costs
          </span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Returns</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
              ${{ formatNumber(getTotalReturns()) }}
            </p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
            </svg>
          </div>
        </div>
        <div class="mt-2 flex items-center text-sm">
          <span class="text-gray-500 dark:text-gray-400">
            Funds raised
          </span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cost per Dollar</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
              ${{ formatDecimal(getAverageCostPerDollar()) }}
            </p>
          </div>
          <div class="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-full">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <div class="mt-2 flex items-center text-sm">
          <span class="text-gray-500 dark:text-gray-400">
            Average efficiency
          </span>
        </div>
      </div>
    </div>

    <!-- ROI Performance Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Campaign ROI Performance</h3>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          <canvas ref="roiChart" class="max-h-80"></canvas>
        </div>
        
        <div>
          <canvas ref="costEfficiencyChart" class="max-h-80"></canvas>
        </div>
      </div>
    </div>

    <!-- Campaign Performance Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Campaign ROI Details</h3>
        <div class="flex space-x-2">
          <button
            @click="sortBy = 'roi'"
            :class="[
              'px-3 py-1 text-sm rounded-md transition-colors',
              sortBy === 'roi' 
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' 
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
          >
            Sort by ROI
          </button>
          <button
            @click="sortBy = 'raised'"
            :class="[
              'px-3 py-1 text-sm rounded-md transition-colors',
              sortBy === 'raised' 
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' 
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
          >
            Sort by Amount
          </button>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Campaign
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Amount Raised
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Investment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                ROI
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Cost per $
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Donors
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="campaign in sortedCampaigns" :key="campaign.campaign_id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ campaign.campaign_title }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                    {{ campaign.campaign_type }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                ${{ formatNumber(campaign.performance_metrics.total_raised) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${{ formatNumber(campaign.roi_metrics.estimated_cost) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getROIBadgeClass(campaign.roi_metrics.roi_percentage)"
                >
                  {{ formatPercentage(campaign.roi_metrics.roi_percentage) }}%
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${{ formatDecimal(campaign.roi_metrics.cost_per_dollar_raised) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ campaign.performance_metrics.donor_count }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Campaign Type Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">ROI by Campaign Type</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="typeAnalysis in getCampaignTypeAnalysis()"
          :key="typeAnalysis.type"
          class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
        >
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white capitalize">
              {{ typeAnalysis.type }}
            </h4>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ typeAnalysis.count }} campaigns
            </span>
          </div>
          
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-600 dark:text-gray-400">Avg ROI</span>
              <span
                class="text-sm font-semibold"
                :class="typeAnalysis.avgROI >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ formatPercentage(typeAnalysis.avgROI) }}%
              </span>
            </div>
            
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-600 dark:text-gray-400">Total Raised</span>
              <span class="text-sm font-semibold text-gray-900 dark:text-white">
                ${{ formatNumber(typeAnalysis.totalRaised) }}
              </span>
            </div>
            
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-600 dark:text-gray-400">Avg Cost per $</span>
              <span class="text-sm font-semibold text-gray-900 dark:text-white">
                ${{ formatDecimal(typeAnalysis.avgCostPerDollar) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  campaigns: {
    type: Array,
    required: true
  }
})

const roiChart = ref(null)
const costEfficiencyChart = ref(null)
const sortBy = ref('roi')

let roiChartInstance = null
let costChartInstance = null

const formatNumber = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('en-US').format(Math.round(value))
}

const formatDecimal = (value) => {
  if (!value) return '0.00'
  return parseFloat(value).toFixed(2)
}

const formatPercentage = (value) => {
  if (!value) return '0'
  return Math.round(value)
}

const getAverageROI = () => {
  if (!props.campaigns.length) return 0
  const total = props.campaigns.reduce((sum, campaign) => sum + (campaign.roi_metrics?.roi_percentage || 0), 0)
  return total / props.campaigns.length
}

const getTotalInvestment = () => {
  return props.campaigns.reduce((sum, campaign) => sum + (campaign.roi_metrics?.estimated_cost || 0), 0)
}

const getTotalReturns = () => {
  return props.campaigns.reduce((sum, campaign) => sum + (campaign.performance_metrics?.total_raised || 0), 0)
}

const getAverageCostPerDollar = () => {
  if (!props.campaigns.length) return 0
  const total = props.campaigns.reduce((sum, campaign) => sum + (campaign.roi_metrics?.cost_per_dollar_raised || 0), 0)
  return total / props.campaigns.length
}

const getROIBadgeClass = (roi) => {
  if (roi >= 200) return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
  if (roi >= 100) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  if (roi >= 0) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
}

const sortedCampaigns = computed(() => {
  const sorted = [...props.campaigns]
  
  if (sortBy.value === 'roi') {
    return sorted.sort((a, b) => (b.roi_metrics?.roi_percentage || 0) - (a.roi_metrics?.roi_percentage || 0))
  } else if (sortBy.value === 'raised') {
    return sorted.sort((a, b) => (b.performance_metrics?.total_raised || 0) - (a.performance_metrics?.total_raised || 0))
  }
  
  return sorted
})

const getCampaignTypeAnalysis = () => {
  const typeGroups = {}
  
  props.campaigns.forEach(campaign => {
    const type = campaign.campaign_type || 'general'
    
    if (!typeGroups[type]) {
      typeGroups[type] = {
        type,
        campaigns: [],
        count: 0,
        totalRaised: 0,
        totalCost: 0,
        avgROI: 0,
        avgCostPerDollar: 0
      }
    }
    
    typeGroups[type].campaigns.push(campaign)
    typeGroups[type].count++
    typeGroups[type].totalRaised += campaign.performance_metrics?.total_raised || 0
    typeGroups[type].totalCost += campaign.roi_metrics?.estimated_cost || 0
  })
  
  // Calculate averages
  Object.values(typeGroups).forEach(group => {
    if (group.count > 0) {
      group.avgROI = group.campaigns.reduce((sum, c) => sum + (c.roi_metrics?.roi_percentage || 0), 0) / group.count
      group.avgCostPerDollar = group.campaigns.reduce((sum, c) => sum + (c.roi_metrics?.cost_per_dollar_raised || 0), 0) / group.count
    }
  })
  
  return Object.values(typeGroups)
}

const createROIChart = () => {
  if (!roiChart.value) return
  
  const ctx = roiChart.value.getContext('2d')
  
  if (roiChartInstance) {
    roiChartInstance.destroy()
  }
  
  const data = props.campaigns.map(campaign => ({
    x: campaign.performance_metrics?.total_raised || 0,
    y: campaign.roi_metrics?.roi_percentage || 0,
    label: campaign.campaign_title
  }))
  
  roiChartInstance = new Chart(ctx, {
    type: 'scatter',
    data: {
      datasets: [{
        label: 'Campaign ROI vs Amount Raised',
        data: data,
        backgroundColor: 'rgba(59, 130, 246, 0.6)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8
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
            title: (context) => context[0].raw.label,
            label: (context) => [
              `Amount Raised: $${formatNumber(context.parsed.x)}`,
              `ROI: ${formatPercentage(context.parsed.y)}%`
            ]
          }
        }
      },
      scales: {
        x: {
          title: {
            display: true,
            text: 'Amount Raised ($)'
          },
          ticks: {
            callback: function(value) {
              return '$' + formatNumber(value)
            }
          }
        },
        y: {
          title: {
            display: true,
            text: 'ROI (%)'
          },
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

const createCostEfficiencyChart = () => {
  if (!costEfficiencyChart.value) return
  
  const ctx = costEfficiencyChart.value.getContext('2d')
  
  if (costChartInstance) {
    costChartInstance.destroy()
  }
  
  const labels = props.campaigns.map(c => c.campaign_title?.substring(0, 20) + '...' || 'Campaign')
  const costData = props.campaigns.map(c => c.roi_metrics?.cost_per_dollar_raised || 0)
  
  costChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Cost per Dollar Raised',
        data: costData,
        backgroundColor: costData.map(cost => {
          if (cost <= 0.1) return 'rgba(16, 185, 129, 0.8)'
          if (cost <= 0.2) return 'rgba(245, 158, 11, 0.8)'
          return 'rgba(239, 68, 68, 0.8)'
        }),
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
            label: (context) => `Cost per $: $${formatDecimal(context.parsed.y)}`
          }
        }
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 45
          }
        },
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Cost per Dollar ($)'
          },
          ticks: {
            callback: function(value) {
              return '$' + formatDecimal(value)
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  nextTick(() => {
    createROIChart()
    createCostEfficiencyChart()
  })
})
</script>