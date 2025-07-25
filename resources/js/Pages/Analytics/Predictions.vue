<template>
    <AppLayout title="Predictive Analytics">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Predictive Analytics
                </h2>
                <div class="flex items-center space-x-4">
                    <select 
                        v-model="selectedType" 
                        @change="filterByType"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Types</option>
                        <option v-for="type in types" :key="type" :value="type">
                            {{ formatType(type) }}
                        </option>
                    </select>
                    <button
                        @click="generatePredictions"
                        :disabled="generating"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                    >
                        {{ generating ? 'Generating...' : 'Generate Predictions' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Models Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div v-for="model in models" :key="model.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ model.name }}</h3>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ formatType(model.type) }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4">{{ model.description }}</p>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-sm text-gray-500">Accuracy</div>
                                    <div class="text-lg font-semibold text-gray-900">{{ model.accuracy }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Last Trained</div>
                                    <div class="text-sm text-gray-900">{{ formatDate(model.last_trained) }}</div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">
                                    {{ model.predictions.length }} recent predictions
                                </span>
                                <button
                                    @click="viewModelDetails(model)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Predictions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Predictions</h3>
                        
                        <div class="space-y-4">
                            <div v-for="model in models" :key="model.id" v-if="model.predictions.length > 0">
                                <h4 class="text-md font-medium text-gray-800 mb-2">{{ model.name }}</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="prediction in model.predictions.slice(0, 6)" :key="prediction.id" 
                                         class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ formatSubjectType(prediction.subject_type) }} #{{ prediction.subject_id }}
                                            </span>
                                            <span :class="getConfidenceClass(prediction.confidence)" 
                                                  class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ prediction.confidence }}
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Prediction Score</span>
                                                <span class="font-medium">{{ prediction.score }}</span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>{{ formatDate(prediction.prediction_date) }}</span>
                                            <span v-if="prediction.target_date">
                                                Target: {{ formatDate(prediction.target_date) }}
                                            </span>
                                        </div>
                                        
                                        <button
                                            @click="viewPredictionDetails(prediction, model)"
                                            class="mt-2 w-full text-center text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!hasAnyPredictions" class="text-center py-8">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No predictions available</h3>
                                <p class="mt-1 text-sm text-gray-500">Generate predictions to see insights here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Model Details Modal -->
        <Modal :show="showModelModal" @close="showModelModal = false" max-width="4xl">
            <div class="p-6" v-if="selectedModel">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ selectedModel.name }} Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Model Information</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">Type</dt>
                                <dd class="text-sm text-gray-900">{{ formatType(selectedModel.type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">{{ selectedModel.description }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Accuracy</dt>
                                <dd class="text-sm text-gray-900">{{ selectedModel.accuracy }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Last Trained</dt>
                                <dd class="text-sm text-gray-900">{{ formatDate(selectedModel.last_trained) }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Predictions</h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <div v-for="prediction in selectedModel.predictions" :key="prediction.id" 
                                 class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <div>
                                    <div class="text-sm font-medium">
                                        {{ formatSubjectType(prediction.subject_type) }} #{{ prediction.subject_id }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ formatDate(prediction.prediction_date) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium">{{ prediction.score }}</div>
                                    <div :class="getConfidenceClass(prediction.confidence)" 
                                         class="text-xs px-1 py-0.5 rounded">
                                        {{ prediction.confidence }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="showModelModal = false"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-md text-sm font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Prediction Details Modal -->
        <Modal :show="showPredictionModal" @close="showPredictionModal = false" max-width="3xl">
            <div class="p-6" v-if="selectedPrediction">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Prediction Details</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Subject</label>
                            <div class="text-sm text-gray-900">
                                {{ formatSubjectType(selectedPrediction.subject_type) }} #{{ selectedPrediction.subject_id }}
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Prediction Score</label>
                            <div class="text-sm text-gray-900">{{ selectedPrediction.score }}</div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Confidence Level</label>
                            <div>
                                <span :class="getConfidenceClass(selectedPrediction.confidence)" 
                                      class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ selectedPrediction.confidence }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Model</label>
                            <div class="text-sm text-gray-900">{{ selectedPredictionModel?.name }}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Prediction Date</label>
                            <div class="text-sm text-gray-900">{{ formatDate(selectedPrediction.prediction_date) }}</div>
                        </div>
                        <div v-if="selectedPrediction.target_date">
                            <label class="text-sm font-medium text-gray-700">Target Date</label>
                            <div class="text-sm text-gray-900">{{ formatDate(selectedPrediction.target_date) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="showPredictionModal = false"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-md text-sm font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    models: Array,
    types: Array,
    selectedType: String,
})

const generating = ref(false)
const showModelModal = ref(false)
const showPredictionModal = ref(false)
const selectedModel = ref(null)
const selectedPrediction = ref(null)
const selectedPredictionModel = ref(null)

const hasAnyPredictions = computed(() => {
    return props.models.some(model => model.predictions.length > 0)
})

const filterByType = () => {
    router.get(route('analytics.predictions'), { 
        type: selectedType.value 
    }, {
        preserveState: true
    })
}

const generatePredictions = async () => {
    generating.value = true
    
    try {
        const response = await fetch(route('analytics.generate-predictions'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Refresh the page to show new predictions
            router.reload()
        } else {
            alert('Failed to generate predictions: ' + result.message)
        }
    } catch (error) {
        alert('Error generating predictions: ' + error.message)
    } finally {
        generating.value = false
    }
}

const viewModelDetails = (model) => {
    selectedModel.value = model
    showModelModal.value = true
}

const viewPredictionDetails = (prediction, model) => {
    selectedPrediction.value = prediction
    selectedPredictionModel.value = model
    showPredictionModal.value = true
}

const formatType = (type) => {
    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSubjectType = (subjectType) => {
    const parts = subjectType.split('\\')
    const className = parts[parts.length - 1]
    return className.replace(/([A-Z])/g, ' $1').trim()
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getConfidenceClass = (confidence) => {
    const classes = {
        high: 'bg-green-100 text-green-800',
        medium: 'bg-blue-100 text-blue-800',
        low: 'bg-yellow-100 text-yellow-800',
        very_low: 'bg-red-100 text-red-800',
    }
    return classes[confidence] || classes.low
}
</script>
</template>