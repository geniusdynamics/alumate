<template>
  <div class="overview-metrics">
    <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
      Overview Metrics
    </h2>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <MetricCard
        title="Total Alumni"
        :value="data.total_alumni || 0"
        icon="👥"
        color="blue"
      />
      <MetricCard
        title="Employment Rate"
        :value="`${data.employment_rate || 0}%`"
        icon="💼"
        color="green"
      />
      <MetricCard
        title="Average Salary"
        :value="formatCurrency(data.average_salary || 0)"
        icon="💰"
        color="yellow"
      />
      <MetricCard
        title="Tracking Rate"
        :value="`${data.tracking_rate || 0}%`"
        icon="📊"
        color="purple"
      />
    </div>

    <!-- Charts and Detailed Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Top Industries -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Top Industries
        </h3>
        <div v-if="topIndustries.length > 0" class="space-y-3">
          <div
            v-for="(count, industry) in topIndustries"
            :key="industry"
            class="flex justify-between items-center"
          >
            <span class="text-gray-700 dark:text-gray-300">{{ industry }}</span>
            <div class="flex items-center space-x-2">
              <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full"
                  :style="{ width: `${(count / maxIndustryCount) * 100}%` }"
                ></div>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ count }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
          No industry data available
        </div>
      </div>

      <!-- Top Employers -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Top Employers
        </h3>
        <div v-if="topEmployers.length > 0" class="space-y-3">
          <div
            v-for="(count, employer) in topEmployers"
            :key="employer"
            class="flex justify-between items-center"
          >
            <span class="text-gray-700 dark:text-gray-300">{{ employer }}</span>
            <div class="flex items-center space-x-2">
              <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-green-600 h-2 rounded-full"
                  :style="{ width: `${(count / maxEmployerCount) * 100}%` }"
                ></div>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ count }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
          No employer data available
        </div>
      </div>

      <!-- Geographic Distribution -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Geographic Distribution
        </h3>
        <div v-if="geographicData.length > 0" class="space-y-3">
          <div
            v-for="(count, location) in geographicData"
            :key="location"
            class="flex justify-between items-center"
          >
            <span class="text-gray-700 dark:text-gray-300">{{ location }}</span>
            <div class="flex items-center space-x-2">
              <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-purple-600 h-2 rounded-full"
                  :style="{ width: `${(count / maxLocationCount) * 100}%` }"
                ></div>
              </div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ count }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 dark:text-gray-400 text-center py-4">
          No geographic data available
        </div>
      </div>

      <!-- Summary Statistics -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
          Summary Statistics
        </h3>
        <div class="space-y-4">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Data Coverage</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ data.tracking_rate || 0 }}% of alumni tracked
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Industries Represented</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ Object.keys(data.top_industries || {}).length }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Employer Partners</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ Object.keys(data.top_employers || {}).length }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Geographic Reach</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ Object.keys(data.geographic_distribution || {}).length }} locations
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import MetricCard from '@/Components/Analytics/MetricCard.vue'

interface OverviewData {
  total_alumni: number
  employment_rate: number
  average_salary: number
  tracking_rate: number
  top_industries: Record<string, number>
  top_employers: Record<string, number>
  geographic_distribution: Record<string, number>
}

interface Props {
  data: OverviewData
}

const props = defineProps<Props>()

const topIndustries = computed(() => {
  return Object.entries(props.data.top_industries || {})
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10)
})

const topEmployers = computed(() => {
  return Object.entries(props.data.top_employers || {})
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10)
})

const geographicData = computed(() => {
  return Object.entries(props.data.geographic_distribution || {})
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10)
})

const maxIndustryCount = computed(() => {
  return Math.max(...Object.values(props.data.top_industries || {}), 1)
})

const maxEmployerCount = computed(() => {
  return Math.max(...Object.values(props.data.top_employers || {}), 1)
})

const maxLocationCount = computed(() => {
  return Math.max(...Object.values(props.data.geographic_distribution || {}), 1)
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>