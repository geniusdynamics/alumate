<template>
  <div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ scholarship.name }} - Impact Report</h2>
      <button
        @click="exportReport"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Export Report
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="report" class="space-y-8">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-green-50 p-6 rounded-lg">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-green-600">Total Awarded</p>
              <p class="text-2xl font-bold text-green-900">${{ formatCurrency(report.total_awarded) }}</p>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 p-6 rounded-lg">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-blue-600">Recipients</p>
              <p class="text-2xl font-bold text-blue-900">{{ report.recipients_count }}</p>
            </div>
          </div>
        </div>

        <div class="bg-purple-50 p-6 rounded-lg">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-purple-600">Success Stories</p>
              <p class="text-2xl font-bold text-purple-900">{{ report.success_stories_count }}</p>
            </div>
          </div>
        </div>

        <div class="bg-yellow-50 p-6 rounded-lg">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-yellow-600">Active Recipients</p>
              <p class="text-2xl font-bold text-yellow-900">{{ report.active_recipients }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recipients by Year Chart -->
      <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recipients by Year</h3>
        <div class="space-y-3">
          <div
            v-for="(count, year) in report.recipients_by_year"
            :key="year"
            class="flex items-center justify-between"
          >
            <span class="text-sm font-medium text-gray-700">{{ year }}</span>
            <div class="flex items-center space-x-3">
              <div class="w-32 bg-gray-200 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full"
                  :style="{ width: `${(count / Math.max(...Object.values(report.recipients_by_year))) * 100}%` }"
                ></div>
              </div>
              <span class="text-sm text-gray-600 w-8">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Metrics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-50 p-6 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Program Impact</h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Completed Recipients</span>
              <span class="font-semibold text-gray-900">{{ report.completed_recipients }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Average Years Since Award</span>
              <span class="font-semibold text-gray-900">{{ Math.round(report.average_years_since_award || 0) }} years</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Success Story Rate</span>
              <span class="font-semibold text-gray-900">
                {{ Math.round((report.success_stories_count / report.recipients_count) * 100) }}%
              </span>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Overview</h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Total Fund Amount</span>
              <span class="font-semibold text-gray-900">${{ formatCurrency(scholarship.total_fund_amount) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Amount Awarded</span>
              <span class="font-semibold text-gray-900">${{ formatCurrency(report.total_awarded) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Remaining Funds</span>
              <span class="font-semibold text-gray-900">
                ${{ formatCurrency(scholarship.total_fund_amount - report.total_awarded) }}
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-green-600 h-2 rounded-full"
                :style="{ width: `${(report.total_awarded / scholarship.total_fund_amount) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Success Stories -->
      <div v-if="successStories.length > 0" class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Success Stories</h3>
        <div class="space-y-4">
          <div
            v-for="story in successStories.slice(0, 3)"
            :key="story.id"
            class="bg-white p-4 rounded-lg border border-gray-200"
          >
            <div class="flex items-start space-x-3">
              <img
                :src="story.recipient?.avatar || '/default-avatar.png'"
                :alt="story.recipient?.name"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div class="flex-1">
                <h4 class="font-medium text-gray-900">{{ story.recipient?.name }}</h4>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ story.success_story }}</p>
                <p class="text-xs text-gray-500 mt-2">
                  Awarded ${{ formatCurrency(story.awarded_amount) }} in {{ formatYear(story.award_date) }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-4 text-center">
          <button
            @click="$emit('view-all-stories')"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            View All Success Stories →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Scholarship {
  id: number
  name: string
  total_fund_amount: number
}

interface ImpactReport {
  total_awarded: number
  recipients_count: number
  success_stories_count: number
  active_recipients: number
  completed_recipients: number
  average_years_since_award: number
  recipients_by_year: Record<string, number>
}

interface SuccessStory {
  id: number
  success_story: string
  awarded_amount: number
  award_date: string
  recipient?: {
    name: string
    avatar?: string
  }
}

interface Props {
  scholarship: Scholarship
}

const props = defineProps<Props>()

defineEmits<{
  'view-all-stories': []
}>()

const loading = ref(true)
const report = ref<ImpactReport | null>(null)
const successStories = ref<SuccessStory[]>([])

const fetchReport = async () => {
  try {
    const [reportResponse, storiesResponse] = await Promise.all([
      axios.get(`/api/scholarships/${props.scholarship.id}/impact-report`),
      axios.get(`/api/scholarships/${props.scholarship.id}/recipients`)
    ])

    if (reportResponse.data.success) {
      report.value = reportResponse.data.data
    }

    if (storiesResponse.data.success) {
      successStories.value = storiesResponse.data.data.filter((recipient: any) => recipient.success_story)
    }
  } catch (error) {
    console.error('Error fetching impact report:', error)
  } finally {
    loading.value = false
  }
}

const exportReport = () => {
  // Implementation for exporting report as PDF or Excel
  console.log('Exporting report...')
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US').format(amount)
}

const formatYear = (dateString: string): string => {
  return new Date(dateString).getFullYear().toString()
}

onMounted(() => {
  fetchReport()
})
</script>