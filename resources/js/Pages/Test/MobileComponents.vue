<template>
    <div class="mobile-container">
        <div class="mobile-section">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Mobile Components Test</h1>
            
            <!-- Swipeable Tab Navigation Test -->
            <div class="mobile-section">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Swipeable Tab Navigation</h2>
                <div class="h-96 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <SwipeableTabNavigation
                        :tabs="testTabs"
                        :initial-tab="0"
                        @tab-changed="handleTabChanged"
                        @tab-swiped="handleTabSwiped"
                    >
                        <template #tab1="{ tab, active }">
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ tab.label }}</h3>
                                <p class="text-gray-600 dark:text-gray-400">This is the content for the first tab. You can swipe left or right to navigate between tabs.</p>
                                <div class="mt-4 space-y-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/2"></div>
                                </div>
                            </div>
                        </template>
                        
                        <template #tab2="{ tab, active }">
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ tab.label }}</h3>
                                <p class="text-gray-600 dark:text-gray-400">This is the second tab with different content. Try swiping to see the smooth transitions.</p>
                                <div class="mt-4 grid grid-cols-2 gap-4">
                                    <div class="h-20 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Card 1</span>
                                    </div>
                                    <div class="h-20 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                        <span class="text-green-600 dark:text-green-400 font-medium">Card 2</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <template #tab3="{ tab, active }">
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ tab.label }}</h3>
                                <p class="text-gray-600 dark:text-gray-400">Third tab content with a list of items.</p>
                                <div class="mt-4 space-y-2">
                                    <div v-for="i in 5" :key="i" class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm font-medium">{{ i }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">Item {{ i }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Description for item {{ i }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </SwipeableTabNavigation>
                </div>
            </div>
            
            <!-- Touch Optimized Controls Test -->
            <div class="mobile-section">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Touch Optimized Controls</h2>
                
                <!-- Buttons -->
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buttons</h3>
                        <div class="space-y-3">
                            <TouchOptimizedControls
                                type="button"
                                variant="primary"
                                size="md"
                                label="Primary Button"
                                :icon="CheckIcon"
                                @click="showToast('Primary button clicked!')"
                            />
                            <TouchOptimizedControls
                                type="button"
                                variant="secondary"
                                size="md"
                                label="Secondary Button"
                                @click="showToast('Secondary button clicked!')"
                            />
                            <TouchOptimizedControls
                                type="button"
                                variant="outline"
                                size="md"
                                label="Outline Button"
                                :loading="isLoading"
                                @click="handleLoadingButton"
                            />
                        </div>
                    </div>
                    
                    <!-- Inputs -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Inputs</h3>
                        <div class="space-y-3">
                            <TouchOptimizedControls
                                type="input"
                                v-model="testInput"
                                label="Test Input"
                                placeholder="Enter some text..."
                                :input-icon="UserIcon"
                                clearable
                                hint="This is a touch-optimized input field"
                            />
                            <TouchOptimizedControls
                                type="input"
                                v-model="testEmail"
                                input-type="email"
                                label="Email Address"
                                placeholder="your@email.com"
                                :input-icon="AtSymbolIcon"
                                required
                                :error="emailError"
                            />
                        </div>
                    </div>
                    
                    <!-- Select -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select</h3>
                        <TouchOptimizedControls
                            type="select"
                            v-model="testSelect"
                            label="Choose an option"
                            :options="selectOptions"
                            placeholder="Select an option..."
                        />
                    </div>
                    
                    <!-- Toggle -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Toggle</h3>
                        <TouchOptimizedControls
                            type="toggle"
                            v-model="testToggle"
                            label="Enable notifications"
                            description="Receive push notifications for important updates"
                        />
                    </div>
                    
                    <!-- Slider -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slider</h3>
                        <TouchOptimizedControls
                            type="slider"
                            v-model="testSlider"
                            label="Volume"
                            :min="0"
                            :max="100"
                            :step="5"
                            show-min-max
                        />
                    </div>
                </div>
            </div>
            
            <!-- Mobile Layout Examples -->
            <div class="mobile-section">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mobile Layout Examples</h2>
                
                <!-- Mobile Cards -->
                <div class="space-y-4">
                    <div class="mobile-card-enhanced">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <UserIcon class="h-5 w-5 text-white" />
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">John Doe</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Software Engineer</div>
                            </div>
                            <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                                <EllipsisHorizontalIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            This is an example of a mobile-optimized card with proper touch targets and spacing.
                        </p>
                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                            <button class="flex items-center space-x-1 hover:text-blue-600 dark:hover:text-blue-400">
                                <HeartIcon class="h-4 w-4" />
                                <span>Like</span>
                            </button>
                            <button class="flex items-center space-x-1 hover:text-blue-600 dark:hover:text-blue-400">
                                <ChatBubbleLeftIcon class="h-4 w-4" />
                                <span>Comment</span>
                            </button>
                            <button class="flex items-center space-x-1 hover:text-blue-600 dark:hover:text-blue-400">
                                <ShareIcon class="h-4 w-4" />
                                <span>Share</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Mobile List -->
                    <div class="mobile-list-enhanced">
                        <div v-for="i in 3" :key="i" class="mobile-list-item-enhanced">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">{{ i }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">List Item {{ i }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Subtitle for item {{ i }}</div>
                                </div>
                                <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast Notification -->
        <div
            v-if="showToastMessage"
            class="mobile-notification-enhanced mobile-notification-info mobile-animate-slide-down"
        >
            <div class="flex items-center space-x-3">
                <InformationCircleIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ toastMessage }}</div>
                </div>
                <button @click="hideToast" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import SwipeableTabNavigation from '@/Components/Mobile/SwipeableTabNavigation.vue'
import TouchOptimizedControls from '@/Components/Mobile/TouchOptimizedControls.vue'
import {
    CheckIcon,
    UserIcon,
    AtSymbolIcon,
    EllipsisHorizontalIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    ShareIcon,
    ChevronRightIcon,
    InformationCircleIcon,
    XMarkIcon,
    HomeIcon,
    UsersIcon,
    CogIcon
} from '@heroicons/vue/24/outline'

// Test data
const testTabs = [
    { id: 'tab1', label: 'First', icon: HomeIcon },
    { id: 'tab2', label: 'Second', icon: UsersIcon },
    { id: 'tab3', label: 'Third', icon: CogIcon }
]

// Form data
const testInput = ref('')
const testEmail = ref('')
const testSelect = ref('')
const testToggle = ref(false)
const testSlider = ref(50)
const isLoading = ref(false)

const selectOptions = [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
    { value: 'option3', label: 'Option 3' },
    { value: 'option4', label: 'Option 4' }
]

// Toast notification
const showToastMessage = ref(false)
const toastMessage = ref('')

// Computed
const emailError = computed(() => {
    if (testEmail.value && !testEmail.value.includes('@')) {
        return 'Please enter a valid email address'
    }
    return null
})

// Methods
const handleTabChanged = (event) => {
    console.log('Tab changed:', event)
    showToast(`Switched to ${event.tab.label} tab`)
}

const handleTabSwiped = (event) => {
    console.log('Tab swiped:', event)
    showToast(`Swiped ${event.direction} to ${event.tab.label}`)
}

const handleLoadingButton = () => {
    isLoading.value = true
    setTimeout(() => {
        isLoading.value = false
        showToast('Loading completed!')
    }, 2000)
}

const showToast = (message) => {
    toastMessage.value = message
    showToastMessage.value = true
    setTimeout(() => {
        hideToast()
    }, 3000)
}

const hideToast = () => {
    showToastMessage.value = false
    toastMessage.value = ''
}
</script>

<style scoped>
/* Additional test-specific styles */
.test-section {
    @apply mb-8 p-4 border border-gray-200 dark:border-gray-700 rounded-lg;
}

.test-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white mb-4;
}
</style>