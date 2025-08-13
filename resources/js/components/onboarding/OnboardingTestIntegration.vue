<template>
    <div class="onboarding-test-integration p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Onboarding System Test
        </h3>
        
        <!-- Test Controls -->
        <div class="space-y-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    @click="startOnboarding"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                    Start Onboarding
                </button>
                <button
                    @click="resetOnboarding"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                >
                    Reset Onboarding
                </button>
                <button
                    @click="showFeatureSpotlight"
                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
                >
                    Show Feature Spotlight
                </button>
                <button
                    @click="showContextualHelp"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                >
                    Show Contextual Help
                </button>
                <button
                    @click="showProfileCompletion"
                    class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700"
                >
                    Show Profile Completion
                </button>
            </div>
        </div>

        <!-- Status Display -->
        <div class="bg-white dark:bg-gray-700 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Onboarding Status</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Completed:</span>
                    <span :class="onboardingState.has_completed_onboarding ? 'text-green-600' : 'text-red-600'">
                        {{ onboardingState.has_completed_onboarding ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Skipped:</span>
                    <span :class="onboardingState.has_skipped_onboarding ? 'text-yellow-600' : 'text-gray-600'">
                        {{ onboardingState.has_skipped_onboarding ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Completed Steps:</span>
                    <span class="text-gray-900 dark:text-white">
                        {{ (onboardingState.completed_steps || []).length }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Explored Features:</span>
                    <span class="text-gray-900 dark:text-white">
                        {{ (onboardingState.explored_features || []).length }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div v-if="testResults.length > 0" class="mt-6">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Test Results</h4>
            <div class="space-y-2">
                <div
                    v-for="result in testResults"
                    :key="result.id"
                    :class="[
                        'p-3 rounded-md text-sm',
                        result.success ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                    ]"
                >
                    <div class="flex justify-between items-center">
                        <span>{{ result.message }}</span>
                        <span class="text-xs">{{ result.timestamp }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import OnboardingService from '@/services/OnboardingService.js'

const onboardingState = reactive({
    has_completed_onboarding: false,
    has_skipped_onboarding: false,
    completed_steps: [],
    explored_features: [],
    preferences: {}
})

const testResults = ref([])

onMounted(async () => {
    await loadOnboardingState()
})

const loadOnboardingState = async () => {
    try {
        const response = await fetch('/api/onboarding/state', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            Object.assign(onboardingState, data.state)
            addTestResult('Onboarding state loaded successfully', true)
        } else {
            addTestResult('Failed to load onboarding state', false)
        }
    } catch (error) {
        console.error('Failed to load onboarding state:', error)
        addTestResult(`Error loading onboarding state: ${error.message}`, false)
    }
}

const startOnboarding = () => {
    try {
        // Trigger onboarding start event
        window.dispatchEvent(new CustomEvent('restart-onboarding-tour'))
        addTestResult('Onboarding tour started', true)
    } catch (error) {
        addTestResult(`Failed to start onboarding: ${error.message}`, false)
    }
}

const resetOnboarding = async () => {
    try {
        const response = await fetch('/api/onboarding/state', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                has_completed_onboarding: false,
                has_skipped_onboarding: false,
                completed_steps: [],
                explored_features: [],
                profile_completion_dismissed: false,
                feature_discovery_viewed: false
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            await loadOnboardingState()
            addTestResult('Onboarding state reset successfully', true)
        } else {
            addTestResult('Failed to reset onboarding state', false)
        }
    } catch (error) {
        addTestResult(`Error resetting onboarding: ${error.message}`, false)
    }
}

const showFeatureSpotlight = () => {
    try {
        const sampleFeature = {
            id: 'test-feature',
            title: 'Test Feature',
            subtitle: 'This is a test feature spotlight',
            description: 'This is a sample feature spotlight to test the onboarding system.',
            icon: 'sparkles',
            benefits: [
                'Test benefit 1',
                'Test benefit 2',
                'Test benefit 3'
            ],
            howToUse: [
                'Step 1: Do this',
                'Step 2: Do that',
                'Step 3: Complete'
            ],
            tags: ['test', 'demo', 'onboarding'],
            actionText: 'Try Test Feature'
        }
        
        window.dispatchEvent(new CustomEvent('show-feature-spotlight', {
            detail: { feature: sampleFeature }
        }))
        
        addTestResult('Feature spotlight triggered', true)
    } catch (error) {
        addTestResult(`Failed to show feature spotlight: ${error.message}`, false)
    }
}

const showContextualHelp = () => {
    try {
        const helpContent = {
            title: 'Test Help',
            description: 'This is a test contextual help tooltip.',
            steps: [
                'Click here to start',
                'Follow the instructions',
                'Complete the action'
            ],
            tips: [
                'This is a helpful tip',
                'Remember to save your work'
            ]
        }
        
        const position = {
            x: 200,
            y: 200,
            placement: 'bottom'
        }
        
        window.dispatchEvent(new CustomEvent('show-contextual-help', {
            detail: { content: helpContent, position }
        }))
        
        addTestResult('Contextual help triggered', true)
    } catch (error) {
        addTestResult(`Failed to show contextual help: ${error.message}`, false)
    }
}

const showProfileCompletion = async () => {
    try {
        const response = await fetch('/api/onboarding/profile-completion', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Trigger profile completion prompt
            window.dispatchEvent(new CustomEvent('show-profile-completion', {
                detail: { completionData: data.completion_data }
            }))
            addTestResult('Profile completion prompt triggered', true)
        } else {
            addTestResult('Failed to get profile completion data', false)
        }
    } catch (error) {
        addTestResult(`Error showing profile completion: ${error.message}`, false)
    }
}

const addTestResult = (message, success) => {
    testResults.value.unshift({
        id: Date.now(),
        message,
        success,
        timestamp: new Date().toLocaleTimeString()
    })
    
    // Keep only last 10 results
    if (testResults.value.length > 10) {
        testResults.value = testResults.value.slice(0, 10)
    }
}
</script>
</template>