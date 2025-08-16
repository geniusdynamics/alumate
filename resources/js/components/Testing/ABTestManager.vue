<template>
  <div class="ab-test-manager">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              A/B Test Manager
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Create and manage A/B tests for feature optimization
            </p>
          </div>
          <button
            @click="showCreateModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
          >
            Create Test
          </button>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.total_tests }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Total Tests
            </div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">
              {{ stats.active_tests }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Active Tests
            </div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">
              {{ stats.total_participants }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Participants
            </div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">
              {{ stats.total_conversions }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Conversions
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tests List -->
    <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Active Tests
        </h3>
      </div>

      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="test in tests"
          :key="test.id"
          class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ test.name }}
                </h4>
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusClass(test.status)"
                >
                  {{ test.status }}
                </span>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ test.description }}
              </p>
              
              <!-- Variants -->
              <div class="mt-3 flex flex-wrap gap-2">
                <div
                  v-for="(percentage, variant) in test.distribution"
                  :key="variant"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                >
                  {{ variant }}: {{ percentage }}%
                </div>
              </div>

              <!-- Metrics -->
              <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Participants:</span>
                  <span class="ml-1 font-medium text-gray-900 dark:text-white">
                    {{ test.participants_count || 0 }}
                  </span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Conversions:</span>
                  <span class="ml-1 font-medium text-gray-900 dark:text-white">
                    {{ test.conversions_count || 0 }}
                  </span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Conversion Rate:</span>
                  <span class="ml-1 font-medium text-gray-900 dark:text-white">
                    {{ getConversionRate(test) }}%
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2">
              <button
                @click="viewTestDetails(test)"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                View Details
              </button>
              <button
                v-if="test.status === 'active'"
                @click="pauseTest(test)"
                class="text-yellow-600 hover:text-yellow-700 text-sm font-medium"
              >
                Pause
              </button>
              <button
                v-if="test.status === 'paused'"
                @click="resumeTest(test)"
                class="text-green-600 hover:text-green-700 text-sm font-medium"
              >
                Resume
              </button>
              <button
                v-if="['active', 'paused'].includes(test.status)"
                @click="completeTest(test)"
                class="text-red-600 hover:text-red-700 text-sm font-medium"
              >
                Complete
              </button>
            </div>
          </div>
        </div>

        <div v-if="tests.length === 0" class="px-6 py-8 text-center">
          <div class="text-gray-500 dark:text-gray-400">
            No A/B tests found. Create your first test to get started.
          </div>
        </div>
      </div>
    </div>

    <!-- Create Test Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Create A/B Test
          </h3>
        </div>

        <form @submit.prevent="createTest" class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Test Name
            </label>
            <input
              v-model="newTest.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
              placeholder="e.g., homepage_cta_button"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description
            </label>
            <textarea
              v-model="newTest.description"
              rows="3"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
              placeholder="Describe what you're testing..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Variants (one per line)
            </label>
            <textarea
              v-model="variantsText"
              rows="3"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
              placeholder="control&#10;variant_a&#10;variant_b"
            ></textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Equal distribution will be applied automatically
            </p>
          </div>

          <div class="flex space-x-3 pt-4">
            <button
              type="button"
              @click="showCreateModal = false"
              class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ creating ? 'Creating...' : 'Create Test' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Test Details Modal -->
    <div
      v-if="selectedTest"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ selectedTest.name }}
            </h3>
            <button
              @click="selectedTest = null"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div class="px-6 py-4">
          <div class="space-y-6">
            <!-- Test Info -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                Test Information
              </h4>
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatusClass(selectedTest.status)"
                  >
                    {{ selectedTest.status }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Started:</span>
                  <span class="text-sm text-gray-900 dark:text-white">
                    {{ formatDate(selectedTest.started_at) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Participants:</span>
                  <span class="text-sm text-gray-900 dark:text-white">
                    {{ selectedTest.participants_count || 0 }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Variant Performance -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                Variant Performance
              </h4>
              <div class="space-y-3">
                <div
                  v-for="variant in selectedTest.variants"
                  :key="variant"
                  class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ variant }}
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ selectedTest.distribution[variant] }}% traffic
                    </span>
                  </div>
                  <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                      <div class="text-gray-500 dark:text-gray-400">Participants</div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ getVariantParticipants(selectedTest, variant) }}
                      </div>
                    </div>
                    <div>
                      <div class="text-gray-500 dark:text-gray-400">Conversions</div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ getVariantConversions(selectedTest, variant) }}
                      </div>
                    </div>
                    <div>
                      <div class="text-gray-500 dark:text-gray-400">Rate</div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ getVariantConversionRate(selectedTest, variant) }}%
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { XMarkIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  initialTests: {
    type: Array,
    default: () => []
  },
  initialStats: {
    type: Object,
    default: () => ({
      total_tests: 0,
      active_tests: 0,
      total_participants: 0,
      total_conversions: 0
    })
  }
})

// State
const tests = ref(props.initialTests)
const stats = ref(props.initialStats)
const showCreateModal = ref(false)
const selectedTest = ref(null)
const creating = ref(false)

// Form data
const newTest = ref({
  name: '',
  description: '',
  variants: []
})

const variantsText = ref('')

// Methods
const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
  }
  return classes[status] || classes.draft
}

const getConversionRate = (test) => {
  const participants = test.participants_count || 0
  const conversions = test.conversions_count || 0
  return participants > 0 ? ((conversions / participants) * 100).toFixed(1) : '0.0'
}

const getVariantParticipants = (test, variant) => {
  return test.variant_stats?.[variant]?.participants || 0
}

const getVariantConversions = (test, variant) => {
  return test.variant_stats?.[variant]?.conversions || 0
}

const getVariantConversionRate = (test, variant) => {
  const participants = getVariantParticipants(test, variant)
  const conversions = getVariantConversions(test, variant)
  return participants > 0 ? ((conversions / participants) * 100).toFixed(1) : '0.0'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Not started'
  return new Date(dateString).toLocaleDateString()
}

const createTest = async () => {
  creating.value = true

  try {
    const variants = variantsText.value
      .split('\n')
      .map(v => v.trim())
      .filter(v => v.length > 0)

    if (variants.length < 2) {
      alert('Please provide at least 2 variants')
      return
    }

    await router.post('/api/admin/ab-tests', {
      name: newTest.value.name,
      description: newTest.value.description,
      variants: variants
    }, {
      preserveState: true,
      onSuccess: (page) => {
        tests.value = page.props.tests || tests.value
        stats.value = page.props.stats || stats.value
        showCreateModal.value = false
        newTest.value = { name: '', description: '', variants: [] }
        variantsText.value = ''
      }
    })
  } catch (error) {
    console.error('Error creating test:', error)
  } finally {
    creating.value = false
  }
}

const viewTestDetails = (test) => {
  selectedTest.value = test
}

const pauseTest = async (test) => {
  await router.patch(`/api/admin/ab-tests/${test.id}`, {
    status: 'paused'
  }, {
    preserveState: true,
    onSuccess: (page) => {
      tests.value = page.props.tests || tests.value
      stats.value = page.props.stats || stats.value
    }
  })
}

const resumeTest = async (test) => {
  await router.patch(`/api/admin/ab-tests/${test.id}`, {
    status: 'active'
  }, {
    preserveState: true,
    onSuccess: (page) => {
      tests.value = page.props.tests || tests.value
      stats.value = page.props.stats || stats.value
    }
  })
}

const completeTest = async (test) => {
  if (confirm('Are you sure you want to complete this test? This action cannot be undone.')) {
    await router.patch(`/api/admin/ab-tests/${test.id}`, {
      status: 'completed'
    }, {
      preserveState: true,
      onSuccess: (page) => {
        tests.value = page.props.tests || tests.value
        stats.value = page.props.stats || stats.value
      }
    })
  }
}

// Load data on mount
onMounted(() => {
  // Refresh data periodically
  setInterval(() => {
    router.reload({ only: ['tests', 'stats'] })
  }, 30000) // Refresh every 30 seconds
})
</script>