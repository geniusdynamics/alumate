<template>
  <div class="ab-test-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">A/B Test Manager</h1>
        <p class="text-gray-600">Create and manage A/B tests for homepage optimization</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium"
      >
        Create New Test
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Audience</label>
          <select
            v-model="filters.audience"
            @change="loadTests"
            class="border border-gray-300 rounded-md px-3 py-2 text-sm"
          >
            <option value="">All Audiences</option>
            <option value="individual">Individual</option>
            <option value="institutional">Institutional</option>
            <option value="both">Both</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            @change="loadTests"
            class="border border-gray-300 rounded-md px-3 py-2 text-sm"
          >
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="running">Running</option>
            <option value="paused">Paused</option>
            <option value="completed">Completed</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tests List -->
    <div class="bg-white rounded-lg shadow-sm border">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">A/B Tests</h2>
      </div>

      <div v-if="loading" class="p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-2">Loading tests...</p>
      </div>

      <div v-else-if="tests.length === 0" class="p-6 text-center text-gray-500">
        No A/B tests found. Create your first test to get started.
      </div>

      <div v-else class="divide-y divide-gray-200">
        <div
          v-for="test in tests"
          :key="test.id"
          class="p-6 hover:bg-gray-50"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-medium text-gray-900">{{ test.name }}</h3>
                <span
                  :class="getStatusBadgeClass(test.status)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ test.status.charAt(0).toUpperCase() + test.status.slice(1) }}
                </span>
                <span
                  :class="getAudienceBadgeClass(test.audience)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getAudienceLabel(test.audience) }}
                </span>
              </div>

              <div class="text-sm text-gray-600 mb-3">
                <span v-if="test.start_date">
                  Started: {{ formatDate(test.start_date) }}
                </span>
                <span v-if="test.end_date" class="ml-4">
                  Ended: {{ formatDate(test.end_date) }}
                </span>
                <span class="ml-4">
                  Traffic: {{ test.traffic_allocation }}%
                </span>
              </div>

              <!-- Test Statistics -->
              <div v-if="testStats[test.id]" class="grid grid-cols-4 gap-4 mt-4">
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-sm font-medium text-gray-500">Assignments</div>
                  <div class="text-lg font-semibold text-gray-900">
                    {{ testStats[test.id].totalAssignments.toLocaleString() }}
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-sm font-medium text-gray-500">Conversions</div>
                  <div class="text-lg font-semibold text-gray-900">
                    {{ testStats[test.id].totalConversions.toLocaleString() }}
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-sm font-medium text-gray-500">Conversion Rate</div>
                  <div class="text-lg font-semibold text-gray-900">
                    {{ (testStats[test.id].overallConversionRate * 100).toFixed(2) }}%
                  </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-sm font-medium text-gray-500">Total Value</div>
                  <div class="text-lg font-semibold text-gray-900">
                    ${{ testStats[test.id].totalValue.toLocaleString() }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 ml-4">
              <button
                @click="viewResults(test.id)"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                View Results
              </button>
              <button
                v-if="test.status === 'draft'"
                @click="startTest(test.id)"
                class="text-green-600 hover:text-green-800 text-sm font-medium"
              >
                Start
              </button>
              <button
                v-if="test.status === 'running'"
                @click="pauseTest(test.id)"
                class="text-yellow-600 hover:text-yellow-800 text-sm font-medium"
              >
                Pause
              </button>
              <button
                v-if="test.status === 'paused'"
                @click="resumeTest(test.id)"
                class="text-green-600 hover:text-green-800 text-sm font-medium"
              >
                Resume
              </button>
              <button
                v-if="['running', 'paused'].includes(test.status)"
                @click="endTest(test.id)"
                class="text-red-600 hover:text-red-800 text-sm font-medium"
              >
                End
              </button>
              <button
                @click="editTest(test)"
                class="text-gray-600 hover:text-gray-800 text-sm font-medium"
              >
                Edit
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Test Modal -->
    <div
      v-if="showCreateModal || showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeModals"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">
            {{ showCreateModal ? 'Create New A/B Test' : 'Edit A/B Test' }}
          </h2>
        </div>

        <form @submit.prevent="saveTest" class="p-6 space-y-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Test Name</label>
              <input
                v-model="testForm.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2"
                placeholder="e.g., Hero CTA Button Test"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Audience</label>
              <select
                v-model="testForm.audience"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2"
              >
                <option value="individual">Individual Alumni</option>
                <option value="institutional">Institutional Admins</option>
                <option value="both">Both Audiences</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Traffic Allocation (%)</label>
              <input
                v-model.number="testForm.trafficAllocation"
                type="number"
                min="1"
                max="100"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea
              v-model="testForm.description"
              rows="3"
              class="w-full border border-gray-300 rounded-md px-3 py-2"
              placeholder="Describe what this test is measuring..."
            ></textarea>
          </div>

          <!-- Variants -->
          <div>
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Variants</h3>
              <button
                type="button"
                @click="addVariant"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                Add Variant
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(variant, index) in testForm.variants"
                :key="index"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-start mb-4">
                  <h4 class="font-medium text-gray-900">Variant {{ index + 1 }}</h4>
                  <button
                    v-if="testForm.variants.length > 2"
                    type="button"
                    @click="removeVariant(index)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    Remove
                  </button>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Variant ID</label>
                    <input
                      v-model="variant.id"
                      type="text"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="e.g., control, variant_a"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                      v-model="variant.name"
                      type="text"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="e.g., Control, Blue Button"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (%)</label>
                    <input
                      v-model.number="variant.weight"
                      type="number"
                      min="1"
                      max="100"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                    >
                  </div>
                </div>

                <!-- Component Overrides -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Component Overrides</label>
                  <div class="space-y-2">
                    <div
                      v-for="(override, overrideIndex) in variant.componentOverrides"
                      :key="overrideIndex"
                      class="flex gap-2 items-center"
                    >
                      <input
                        v-model="override.component"
                        type="text"
                        placeholder="Component name"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                      >
                      <textarea
                        v-model="override.props"
                        placeholder="Props (JSON)"
                        rows="2"
                        class="flex-2 border border-gray-300 rounded-md px-3 py-2 text-sm"
                      ></textarea>
                      <button
                        type="button"
                        @click="removeOverride(variant, overrideIndex)"
                        class="text-red-600 hover:text-red-800 text-sm"
                      >
                        Remove
                      </button>
                    </div>
                    <button
                      type="button"
                      @click="addOverride(variant)"
                      class="text-blue-600 hover:text-blue-800 text-sm"
                    >
                      Add Override
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Conversion Goals -->
          <div>
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Conversion Goals</h3>
              <button
                type="button"
                @click="addConversionGoal"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                Add Goal
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(goal, index) in testForm.conversionGoals"
                :key="index"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-start mb-4">
                  <h4 class="font-medium text-gray-900">Goal {{ index + 1 }}</h4>
                  <button
                    v-if="testForm.conversionGoals.length > 1"
                    type="button"
                    @click="removeConversionGoal(index)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    Remove
                  </button>
                </div>

                <div class="grid grid-cols-4 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Goal ID</label>
                    <input
                      v-model="goal.id"
                      type="text"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="e.g., trial_signup"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                      v-model="goal.name"
                      type="text"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="e.g., Trial Signup"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select
                      v-model="goal.type"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                    >
                      <option value="trial_signup">Trial Signup</option>
                      <option value="demo_request">Demo Request</option>
                      <option value="contact_form">Contact Form</option>
                      <option value="calculator_completion">Calculator Completion</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value ($)</label>
                    <input
                      v-model.number="goal.value"
                      type="number"
                      min="0"
                      step="0.01"
                      required
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="closeModals"
              class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : (showCreateModal ? 'Create Test' : 'Update Test') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Results Modal -->
    <div
      v-if="showResultsModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showResultsModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">Test Results</h2>
        </div>

        <div v-if="selectedTestResults" class="p-6">
          <!-- Test Overview -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ selectedTestResults.testName }}</h3>
            <div class="flex items-center gap-4 text-sm text-gray-600">
              <span>Status: {{ selectedTestResults.status }}</span>
              <span v-if="selectedTestResults.startDate">Started: {{ formatDate(selectedTestResults.startDate) }}</span>
              <span v-if="selectedTestResults.endDate">Ended: {{ formatDate(selectedTestResults.endDate) }}</span>
            </div>
          </div>

          <!-- Statistical Significance -->
          <div v-if="selectedTestResults.statisticalSignificance" class="mb-6 p-4 rounded-lg"
               :class="selectedTestResults.statisticalSignificance.significant ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
            <div class="flex items-center gap-2 mb-2">
              <span class="font-medium">Statistical Significance:</span>
              <span :class="selectedTestResults.statisticalSignificance.significant ? 'text-green-600' : 'text-yellow-600'">
                {{ selectedTestResults.statisticalSignificance.significant ? 'Significant' : 'Not Significant' }}
              </span>
            </div>
            <div class="text-sm text-gray-600">
              <span>Confidence: {{ selectedTestResults.statisticalSignificance.confidence }}%</span>
              <span class="ml-4">P-Value: {{ selectedTestResults.statisticalSignificance.pValue }}</span>
              <span v-if="selectedTestResults.statisticalSignificance.improvement" class="ml-4">
                Improvement: {{ selectedTestResults.statisticalSignificance.improvement }}%
              </span>
            </div>
          </div>

          <!-- Variant Results -->
          <div class="space-y-4">
            <h4 class="text-lg font-medium text-gray-900">Variant Performance</h4>
            <div
              v-for="variant in selectedTestResults.variants"
              :key="variant.variantId"
              class="border border-gray-200 rounded-lg p-4"
              :class="selectedTestResults.winner === variant.variantId ? 'border-green-500 bg-green-50' : ''"
            >
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h5 class="font-medium text-gray-900">{{ variant.variantName }}</h5>
                  <p class="text-sm text-gray-600">{{ variant.assignments.toLocaleString() }} assignments</p>
                </div>
                <div v-if="selectedTestResults.winner === variant.variantId" class="text-green-600 font-medium text-sm">
                  Winner
                </div>
              </div>

              <div class="grid grid-cols-3 gap-4">
                <div v-for="goal in variant.goals" :key="goal.goalId" class="bg-gray-50 rounded-lg p-3">
                  <div class="text-sm font-medium text-gray-500">{{ goal.goalName }}</div>
                  <div class="text-lg font-semibold text-gray-900">{{ goal.conversions }}</div>
                  <div class="text-sm text-gray-600">{{ (goal.conversionRate * 100).toFixed(2) }}% rate</div>
                  <div class="text-sm text-gray-600">${{ goal.totalValue.toLocaleString() }} value</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
          <button
            @click="showResultsModal = false"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-medium"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import type { ABTest, ABTestResult, ABTestStatistics } from '@/types/homepage'

// State
const loading = ref(false)
const saving = ref(false)
const tests = ref<any[]>([])
const testStats = ref<Record<string, ABTestStatistics>>({})
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showResultsModal = ref(false)
const selectedTestResults = ref<ABTestResult | null>(null)

// Filters
const filters = reactive({
  audience: '',
  status: ''
})

// Form
const testForm = reactive({
  id: null as string | null,
  name: '',
  audience: 'individual' as 'individual' | 'institutional' | 'both',
  trafficAllocation: 100,
  description: '',
  variants: [
    {
      id: 'control',
      name: 'Control',
      weight: 50,
      componentOverrides: []
    },
    {
      id: 'variant_a',
      name: 'Variant A',
      weight: 50,
      componentOverrides: []
    }
  ],
  conversionGoals: [
    {
      id: 'trial_signup',
      name: 'Trial Signup',
      type: 'trial_signup',
      value: 100
    }
  ]
})

// Methods
const loadTests = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.audience) params.append('audience', filters.audience)
    if (filters.status) params.append('status', filters.status)

    const response = await fetch(`/api/ab-tests?${params}`)
    if (response.ok) {
      const data = await response.json()
      tests.value = data.data || data
      
      // Load statistics for each test
      for (const test of tests.value) {
        await loadTestStatistics(test.id)
      }
    }
  } catch (error) {
    console.error('Failed to load tests:', error)
  } finally {
    loading.value = false
  }
}

const loadTestStatistics = async (testId: string) => {
  try {
    const response = await fetch(`/api/ab-tests/${testId}/statistics`)
    if (response.ok) {
      const stats = await response.json()
      testStats.value[testId] = stats
    }
  } catch (error) {
    console.error(`Failed to load statistics for test ${testId}:`, error)
  }
}

const saveTest = async () => {
  saving.value = true
  try {
    // Validate variant weights sum to 100
    const totalWeight = testForm.variants.reduce((sum, variant) => sum + variant.weight, 0)
    if (totalWeight !== 100) {
      alert('Variant weights must sum to 100%')
      return
    }

    // Process component overrides
    const processedVariants = testForm.variants.map(variant => ({
      ...variant,
      componentOverrides: variant.componentOverrides.map(override => ({
        component: override.component,
        props: typeof override.props === 'string' ? JSON.parse(override.props || '{}') : override.props
      }))
    }))

    const testData = {
      ...testForm,
      variants: processedVariants
    }

    const url = testForm.id ? `/api/ab-tests/${testForm.id}` : '/api/ab-tests'
    const method = testForm.id ? 'PATCH' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(testData)
    })

    if (response.ok) {
      closeModals()
      await loadTests()
    } else {
      const error = await response.json()
      alert(error.error || 'Failed to save test')
    }
  } catch (error) {
    console.error('Failed to save test:', error)
    alert('Failed to save test')
  } finally {
    saving.value = false
  }
}

const editTest = (test: any) => {
  testForm.id = test.id
  testForm.name = test.name
  testForm.audience = test.audience
  testForm.trafficAllocation = test.traffic_allocation
  testForm.description = test.description || ''
  
  // Load variants and goals from the test data
  // This would need to be implemented based on your data structure
  
  showEditModal.value = true
}

const startTest = async (testId: string) => {
  await updateTestStatus(testId, 'running', { startDate: new Date().toISOString() })
}

const pauseTest = async (testId: string) => {
  await updateTestStatus(testId, 'paused')
}

const resumeTest = async (testId: string) => {
  await updateTestStatus(testId, 'running')
}

const endTest = async (testId: string) => {
  await updateTestStatus(testId, 'completed', { endDate: new Date().toISOString() })
}

const updateTestStatus = async (testId: string, status: string, additionalData: any = {}) => {
  try {
    const response = await fetch(`/api/ab-tests/${testId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status, ...additionalData })
    })

    if (response.ok) {
      await loadTests()
    } else {
      const error = await response.json()
      alert(error.error || 'Failed to update test')
    }
  } catch (error) {
    console.error('Failed to update test status:', error)
    alert('Failed to update test')
  }
}

const viewResults = async (testId: string) => {
  try {
    const response = await fetch(`/api/ab-tests/${testId}/results`)
    if (response.ok) {
      selectedTestResults.value = await response.json()
      showResultsModal.value = true
    }
  } catch (error) {
    console.error('Failed to load test results:', error)
  }
}

const addVariant = () => {
  testForm.variants.push({
    id: `variant_${testForm.variants.length}`,
    name: `Variant ${String.fromCharCode(65 + testForm.variants.length - 1)}`,
    weight: 0,
    componentOverrides: []
  })
}

const removeVariant = (index: number) => {
  testForm.variants.splice(index, 1)
}

const addOverride = (variant: any) => {
  variant.componentOverrides.push({
    component: '',
    props: '{}'
  })
}

const removeOverride = (variant: any, index: number) => {
  variant.componentOverrides.splice(index, 1)
}

const addConversionGoal = () => {
  testForm.conversionGoals.push({
    id: '',
    name: '',
    type: 'trial_signup',
    value: 0
  })
}

const removeConversionGoal = (index: number) => {
  testForm.conversionGoals.splice(index, 1)
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  resetForm()
}

const resetForm = () => {
  testForm.id = null
  testForm.name = ''
  testForm.audience = 'individual'
  testForm.trafficAllocation = 100
  testForm.description = ''
  testForm.variants = [
    { id: 'control', name: 'Control', weight: 50, componentOverrides: [] },
    { id: 'variant_a', name: 'Variant A', weight: 50, componentOverrides: [] }
  ]
  testForm.conversionGoals = [
    { id: 'trial_signup', name: 'Trial Signup', type: 'trial_signup', value: 100 }
  ]
}

// Utility methods
const getStatusBadgeClass = (status: string) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    running: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getAudienceBadgeClass = (audience: string) => {
  const classes = {
    individual: 'bg-purple-100 text-purple-800',
    institutional: 'bg-orange-100 text-orange-800',
    both: 'bg-indigo-100 text-indigo-800'
  }
  return classes[audience as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getAudienceLabel = (audience: string) => {
  const labels = {
    individual: 'Individual',
    institutional: 'Institutional',
    both: 'Both'
  }
  return labels[audience as keyof typeof labels] || audience
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

// Initialize
onMounted(() => {
  loadTests()
})
</script>

<style scoped>
.ab-test-manager {
  @apply max-w-7xl mx-auto p-6;
}
</style>