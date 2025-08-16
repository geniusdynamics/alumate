<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Trend Analysis</h3>
    
    <!-- Key Trend Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div v-for="indicator in keyIndicators" :key="indicator.label" class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ indicator.value }}</div>
            <div class="text-sm text-gray-600">{{ indicator.label }}</div>
          </div>
          <div :class="indicator.trend === 'up' ? 'text-green-500' : indicator.trend === 'down' ? 'text-red-500' : 'text-gray-400'">
            <svg v-if="indicator.trend === 'up'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
            </svg>
            <svg v-else-if="indicator.trend === 'down'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8"></path>
            </svg>
          </div>
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ indicator.change }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Employment Trends -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-md font-medium text-gray-800 mb-3">Employment Rate Trends</h4>
        <div class="h-64 flex items-center justify-center text-gray-500">
          <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <p class="text-sm">Employment trend chart will be displayed here</p>
          </div>
        </div>
      </div>

      <!-- Salary Trends -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-md font-medium text-gray-800 mb-3">Salary Growth Trends</h4>
        <div class="h-64 flex items-center justify-center text-gray-500">
          <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <p class="text-sm">Salary trend chart will be displayed here</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Yearly Comparison -->
    <div class="mt-6">
      <h4 class="text-md font-medium text-gray-800 mb-3">Year-over-Year Comparison</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">2022</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">2023</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">2024</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="metric in yearlyComparison" :key="metric.metric">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ metric.metric }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ metric.y2022 }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ metric.y2023 }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ metric.y2024 }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="metric.change.includes('+') ? 'text-green-600' : metric.change.includes('-') ? 'text-red-600' : 'text-gray-600'">
                  {{ metric.change }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Emerging Trends -->
    <div class="mt-6">
      <h4 class="text-md font-medium text-gray-800 mb-3">Emerging Trends & Insights</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="trend in emergingTrends" :key="trend.title" class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-start">
            <div :class="trend.type === 'positive' ? 'bg-green-100 text-green-600' : trend.type === 'negative' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'" class="p-2 rounded-lg mr-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <div>
              <h5 class="font-medium text-gray-900 mb-1">{{ trend.title }}</h5>
              <p class="text-sm text-gray-600">{{ trend.description }}</p>
              <div class="text-xs text-gray-500 mt-2">Impact: {{ trend.impact }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const keyIndicators = ref([
  { label: 'Employment Rate', value: '89%', trend: 'up', change: '+3.2% from last year' },
  { label: 'Avg Salary', value: '$72.5K', trend: 'up', change: '+8.5% from last year' },
  { label: 'Job Placement Time', value: '3.2 months', trend: 'down', change: '-0.8 months improvement' },
  { label: 'Industry Diversity', value: '12 sectors', trend: 'up', change: '+2 new sectors' }
])

const yearlyComparison = ref([
  { metric: 'Employment Rate', y2022: '84%', y2023: '86%', y2024: '89%', change: '+5% over 2 years' },
  { metric: 'Average Salary', y2022: '$65,200', y2023: '$66,800', y2024: '$72,500', change: '+11.2% over 2 years' },
  { metric: 'Median Salary', y2022: '$62,000', y2023: '$63,500', y2024: '$68,000', change: '+9.7% over 2 years' },
  { metric: 'Time to Employment', y2022: '4.1 months', y2023: '4.0 months', y2024: '3.2 months', change: '-0.9 months improvement' },
  { metric: 'Job Satisfaction', y2022: '7.2/10', y2023: '7.4/10', y2024: '7.8/10', change: '+0.6 points' },
  { metric: 'Career Advancement', y2022: '68%', y2023: '71%', y2024: '75%', change: '+7% over 2 years' }
])

const emergingTrends = ref([
  {
    title: 'Remote Work Adoption',
    description: 'Significant increase in remote and hybrid work opportunities, with 65% of graduates now working remotely.',
    impact: 'Positive - Expanded job market access',
    type: 'positive'
  },
  {
    title: 'Tech Skills Premium',
    description: 'Graduates with technical skills command 25% higher salaries across all industries.',
    impact: 'High - Drives curriculum updates',
    type: 'positive'
  },
  {
    title: 'Industry Consolidation',
    description: 'Some traditional industries showing reduced hiring, offset by growth in emerging sectors.',
    impact: 'Medium - Requires career guidance',
    type: 'neutral'
  },
  {
    title: 'Skills Gap Widening',
    description: 'Increasing mismatch between graduate skills and employer requirements in certain fields.',
    impact: 'Concerning - Needs intervention',
    type: 'negative'
  },
  {
    title: 'Entrepreneurship Growth',
    description: '18% increase in graduates starting their own businesses within 2 years of graduation.',
    impact: 'Positive - Economic contribution',
    type: 'positive'
  },
  {
    title: 'Geographic Mobility',
    description: 'More graduates willing to relocate for better opportunities, with 42% moving to different states.',
    impact: 'Positive - Career flexibility',
    type: 'positive'
  }
])
</script>