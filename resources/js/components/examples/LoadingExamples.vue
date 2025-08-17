<template>
    <div class="loading-examples">
        <div class="loading-examples__header">
            <h1 class="loading-examples__title">
                Loading States & Skeleton Screens
            </h1>
            <p class="loading-examples__description">
                Comprehensive loading states with contextual feedback and accessibility support
            </p>
        </div>
        
        <div class="loading-examples__grid">
            <!-- Skeleton Card Examples -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Skeleton Cards</h2>
                <div class="loading-examples__cards">
                    <div class="loading-examples__card">
                        <h3>Social Post</h3>
                        <SkeletonCard 
                            variant="post" 
                            size="md"
                            :show-image="true"
                            :content-lines="2"
                        />
                    </div>
                    
                    <div class="loading-examples__card">
                        <h3>Job Listing</h3>
                        <SkeletonCard 
                            variant="job" 
                            size="md"
                            :show-image="false"
                            :content-lines="3"
                            :footer-actions="2"
                        />
                    </div>
                    
                    <div class="loading-examples__card">
                        <h3>Profile Card</h3>
                        <SkeletonCard 
                            variant="profile" 
                            size="md"
                            :show-image="false"
                            :content-lines="2"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Skeleton List Examples -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Skeleton Lists</h2>
                <div class="loading-examples__lists">
                    <div class="loading-examples__list">
                        <h3>Alumni Directory</h3>
                        <SkeletonList 
                            :count="5"
                            variant="detailed"
                            :show-avatar="true"
                            :show-secondary="true"
                            :show-tertiary="true"
                            :show-actions="true"
                            :action-count="2"
                        />
                    </div>
                    
                    <div class="loading-examples__list">
                        <h3>Simple List</h3>
                        <SkeletonList 
                            :count="4"
                            variant="simple"
                            :show-avatar="true"
                            :show-secondary="true"
                            :show-tertiary="false"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Contextual Loaders -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Contextual Loaders</h2>
                <div class="loading-examples__contextual">
                    <div class="loading-examples__contextual-item">
                        <h3>File Upload</h3>
                        <ContextualLoader 
                            context="upload"
                            :show-progress="true"
                            :progress="uploadProgress"
                            :progress-text="`${uploadProgress}% uploaded`"
                            size="md"
                        />
                        <button 
                            @click="simulateUpload"
                            class="loading-examples__button"
                            :disabled="isUploading"
                        >
                            {{ isUploading ? 'Uploading...' : 'Start Upload' }}
                        </button>
                    </div>
                    
                    <div class="loading-examples__contextual-item">
                        <h3>Multi-step Process</h3>
                        <ContextualLoader 
                            context="processing"
                            :steps="processSteps"
                            :current-step="currentProcessStep"
                            size="md"
                        />
                        <button 
                            @click="simulateProcess"
                            class="loading-examples__button"
                            :disabled="isProcessing"
                        >
                            {{ isProcessing ? 'Processing...' : 'Start Process' }}
                        </button>
                    </div>
                    
                    <div class="loading-examples__contextual-item">
                        <h3>Search Operation</h3>
                        <ContextualLoader 
                            context="search"
                            indicator-type="dots"
                            message="Finding the best matches for you..."
                            size="md"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Shimmer Effects -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Shimmer Effects</h2>
                <div class="loading-examples__shimmer">
                    <div class="loading-examples__shimmer-item">
                        <h3>Text Shimmer</h3>
                        <div class="space-y-2">
                            <ShimmerEffect 
                                shape="text" 
                                size="lg" 
                                width="80%"
                            />
                            <ShimmerEffect 
                                shape="text" 
                                size="md" 
                                width="60%"
                            />
                            <ShimmerEffect 
                                shape="text" 
                                size="sm" 
                                width="40%"
                            />
                        </div>
                    </div>
                    
                    <div class="loading-examples__shimmer-item">
                        <h3>Card Shimmer</h3>
                        <ShimmerEffect 
                            shape="card" 
                            size="lg"
                            :animated="true"
                            animation-speed="normal"
                        />
                    </div>
                    
                    <div class="loading-examples__shimmer-item">
                        <h3>Avatar Shimmer</h3>
                        <div class="flex items-center space-x-3">
                            <ShimmerEffect 
                                shape="circle" 
                                size="lg"
                            />
                            <div class="flex-1 space-y-2">
                                <ShimmerEffect 
                                    shape="text" 
                                    size="md" 
                                    width="70%"
                                />
                                <ShimmerEffect 
                                    shape="text" 
                                    size="sm" 
                                    width="50%"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Smart Loader Examples -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Smart Loader (Auto-Detection)</h2>
                <div class="loading-examples__smart">
                    <div class="loading-examples__smart-item">
                        <h3>Posts Context</h3>
                        <SmartLoader 
                            :loading="true"
                            context="posts"
                            skeleton-variant="card"
                            card-variant="post"
                            :skeleton-count="2"
                            :show-image="true"
                        />
                    </div>
                    
                    <div class="loading-examples__smart-item">
                        <h3>Profile Context</h3>
                        <SmartLoader 
                            :loading="true"
                            context="profile"
                            skeleton-variant="card"
                            card-variant="profile"
                        />
                    </div>
                    
                    <div class="loading-examples__smart-item">
                        <h3>Jobs Context</h3>
                        <SmartLoader 
                            :loading="true"
                            context="jobs"
                            skeleton-variant="list"
                            list-variant="detailed"
                            :skeleton-count="3"
                            :show-actions="true"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Error States -->
            <div class="loading-examples__section">
                <h2 class="loading-examples__section-title">Error States</h2>
                <div class="loading-examples__error">
                    <SmartLoader 
                        :loading="false"
                        error="Failed to load content. Please check your connection."
                        :on-retry="() => console.log('Retry clicked')"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import SkeletonCard from '../ui/SkeletonCard.vue'
import SkeletonList from '../ui/SkeletonList.vue'
import ContextualLoader from '../ui/ContextualLoader.vue'
import ShimmerEffect from '../ui/ShimmerEffect.vue'
import SmartLoader from '../ui/SmartLoader.vue'

// Upload simulation
const uploadProgress = ref(0)
const isUploading = ref(false)
let uploadInterval: number | null = null

const simulateUpload = () => {
    if (isUploading.value) return
    
    isUploading.value = true
    uploadProgress.value = 0
    
    uploadInterval = setInterval(() => {
        uploadProgress.value += Math.random() * 15
        
        if (uploadProgress.value >= 100) {
            uploadProgress.value = 100
            isUploading.value = false
            if (uploadInterval) {
                clearInterval(uploadInterval)
                uploadInterval = null
            }
            
            // Reset after a delay
            setTimeout(() => {
                uploadProgress.value = 0
            }, 2000)
        }
    }, 200)
}

// Process simulation
const processSteps = [
    'Validating input data',
    'Processing information',
    'Updating database',
    'Sending notifications',
    'Finalizing changes'
]
const currentProcessStep = ref(0)
const isProcessing = ref(false)
let processInterval: number | null = null

const simulateProcess = () => {
    if (isProcessing.value) return
    
    isProcessing.value = true
    currentProcessStep.value = 0
    
    processInterval = setInterval(() => {
        currentProcessStep.value++
        
        if (currentProcessStep.value >= processSteps.length) {
            isProcessing.value = false
            if (processInterval) {
                clearInterval(processInterval)
                processInterval = null
            }
            
            // Reset after a delay
            setTimeout(() => {
                currentProcessStep.value = 0
            }, 2000)
        }
    }, 1000)
}

onUnmounted(() => {
    if (uploadInterval) {
        clearInterval(uploadInterval)
    }
    if (processInterval) {
        clearInterval(processInterval)
    }
})
</script>

<style scoped>
.loading-examples {
    @apply max-w-7xl mx-auto p-6 space-y-8;
}

.loading-examples__header {
    @apply text-center space-y-2;
}

.loading-examples__title {
    @apply text-3xl font-bold text-gray-900 dark:text-gray-100;
}

.loading-examples__description {
    @apply text-lg text-gray-600 dark:text-gray-400;
}

.loading-examples__grid {
    @apply space-y-12;
}

.loading-examples__section {
    @apply space-y-6;
}

.loading-examples__section-title {
    @apply text-2xl font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2;
}

/* Cards */
.loading-examples__cards {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.loading-examples__card {
    @apply space-y-3;
}

.loading-examples__card h3 {
    @apply text-lg font-medium text-gray-900 dark:text-gray-100;
}

/* Lists */
.loading-examples__lists {
    @apply grid grid-cols-1 lg:grid-cols-2 gap-8;
}

.loading-examples__list {
    @apply space-y-3;
}

.loading-examples__list h3 {
    @apply text-lg font-medium text-gray-900 dark:text-gray-100;
}

/* Contextual */
.loading-examples__contextual {
    @apply grid grid-cols-1 lg:grid-cols-3 gap-6;
}

.loading-examples__contextual-item {
    @apply space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg;
}

.loading-examples__contextual-item h3 {
    @apply text-lg font-medium text-gray-900 dark:text-gray-100;
}

/* Shimmer */
.loading-examples__shimmer {
    @apply grid grid-cols-1 lg:grid-cols-3 gap-6;
}

.loading-examples__shimmer-item {
    @apply space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg;
}

.loading-examples__shimmer-item h3 {
    @apply text-lg font-medium text-gray-900 dark:text-gray-100;
}

/* Smart Loader */
.loading-examples__smart {
    @apply grid grid-cols-1 lg:grid-cols-3 gap-6;
}

.loading-examples__smart-item {
    @apply space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg;
}

.loading-examples__smart-item h3 {
    @apply text-lg font-medium text-gray-900 dark:text-gray-100;
}

/* Error */
.loading-examples__error {
    @apply p-4 bg-gray-50 dark:bg-gray-800 rounded-lg;
}

/* Button */
.loading-examples__button {
    @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors;
}
</style>