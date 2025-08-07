<template>
    <div class="guided-tour-overlay">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>
        
        <!-- Highlight Area -->
        <div
            v-if="currentStepData.target"
            class="tour-highlight"
            :style="highlightStyle"
        ></div>
        
        <!-- Tour Tooltip -->
        <div
            class="tour-tooltip fixed z-50 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-w-sm"
            :style="tooltipStyle"
        >
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <component :is="getStepIcon(currentStepData.icon)" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ currentStepData.title }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Step {{ currentStep + 1 }} of {{ tourSteps.length }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="$emit('skip-tour')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4">
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    {{ currentStepData.description }}
                </p>
                
                <!-- Interactive Elements -->
                <div v-if="currentStepData.interactive" class="mb-4">
                    <div
                        v-if="currentStepData.interactive.type === 'form'"
                        class="space-y-3"
                    >
                        <div
                            v-for="field in currentStepData.interactive.fields"
                            :key="field.name"
                        >
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ field.label }}
                            </label>
                            <input
                                v-if="field.type === 'text'"
                                v-model="interactiveData[field.name]"
                                type="text"
                                :placeholder="field.placeholder"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                            <select
                                v-else-if="field.type === 'select'"
                                v-model="interactiveData[field.name]"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">{{ field.placeholder }}</option>
                                <option
                                    v-for="option in field.options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div
                        v-else-if="currentStepData.interactive.type === 'action'"
                        class="text-center"
                    >
                        <button
                            @click="performInteractiveAction"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium"
                        >
                            {{ currentStepData.interactive.buttonText }}
                        </button>
                    </div>
                </div>

                <!-- Tips -->
                <div v-if="currentStepData.tips && currentStepData.tips.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">💡 Pro Tips:</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li
                            v-for="tip in currentStepData.tips"
                            :key="tip"
                            class="flex items-start space-x-2"
                        >
                            <span class="text-blue-500 mt-0.5">•</span>
                            <span>{{ tip }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span>Progress</span>
                        <span>{{ Math.round(((currentStep + 1) / tourSteps.length) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: ((currentStep + 1) / tourSteps.length) * 100 + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between">
                    <button
                        v-if="currentStep > 0"
                        @click="$emit('previous-step')"
                        class="flex items-center space-x-1 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        <ChevronLeftIcon class="w-4 h-4" />
                        <span class="text-sm">Previous</span>
                    </button>
                    <div v-else></div>

                    <div class="flex space-x-2">
                        <button
                            @click="$emit('skip-tour')"
                            class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                        >
                            Skip Tour
                        </button>
                        <button
                            @click="nextStep"
                            :disabled="!canProceed"
                            class="flex items-center space-x-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                        >
                            <span>{{ isLastStep ? 'Finish' : 'Next' }}</span>
                            <ChevronRightIcon v-if="!isLastStep" class="w-4 h-4" />
                            <CheckIcon v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import {
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CheckIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChartBarIcon,
    MapIcon,
    AcademicCapIcon,
    HeartIcon,
    CurrencyDollarIcon,
    TrophyIcon,
    SparklesIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tourSteps: {
        type: Array,
        required: true
    },
    currentStep: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['next-step', 'previous-step', 'complete-tour', 'skip-tour'])

const interactiveData = reactive({})
const highlightStyle = ref({})
const tooltipStyle = ref({})

const currentStepData = computed(() => {
    return props.tourSteps[props.currentStep] || {}
})

const isLastStep = computed(() => {
    return props.currentStep === props.tourSteps.length - 1
})

const canProceed = computed(() => {
    if (!currentStepData.value.interactive) return true
    
    if (currentStepData.value.interactive.type === 'form') {
        const requiredFields = currentStepData.value.interactive.fields.filter(field => field.required)
        return requiredFields.every(field => interactiveData[field.name])
    }
    
    return true
})

watch(() => props.currentStep, () => {
    updateHighlight()
    resetInteractiveData()
}, { immediate: true })

onMounted(() => {
    updateHighlight()
    window.addEventListener('resize', updateHighlight)
    window.addEventListener('scroll', updateHighlight)
})

onUnmounted(() => {
    window.removeEventListener('resize', updateHighlight)
    window.removeEventListener('scroll', updateHighlight)
})

const updateHighlight = () => {
    if (!currentStepData.value.target) return
    
    const targetElement = document.querySelector(currentStepData.value.target)
    if (!targetElement) return
    
    const rect = targetElement.getBoundingClientRect()
    const padding = 8
    
    // Update highlight style
    highlightStyle.value = {
        position: 'fixed',
        top: `${rect.top - padding}px`,
        left: `${rect.left - padding}px`,
        width: `${rect.width + padding * 2}px`,
        height: `${rect.height + padding * 2}px`,
        border: '3px solid #3B82F6',
        borderRadius: '8px',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        pointerEvents: 'none',
        zIndex: 45
    }
    
    // Update tooltip position
    const tooltipWidth = 384 // max-w-sm = 24rem = 384px
    const tooltipHeight = 400 // estimated height
    
    let tooltipTop = rect.bottom + padding + 10
    let tooltipLeft = rect.left + (rect.width / 2) - (tooltipWidth / 2)
    
    // Adjust if tooltip goes off screen
    if (tooltipLeft < 10) tooltipLeft = 10
    if (tooltipLeft + tooltipWidth > window.innerWidth - 10) {
        tooltipLeft = window.innerWidth - tooltipWidth - 10
    }
    
    if (tooltipTop + tooltipHeight > window.innerHeight - 10) {
        tooltipTop = rect.top - tooltipHeight - 10
    }
    
    tooltipStyle.value = {
        top: `${tooltipTop}px`,
        left: `${tooltipLeft}px`
    }
}

const resetInteractiveData = () => {
    Object.keys(interactiveData).forEach(key => {
        delete interactiveData[key]
    })
    
    if (currentStepData.value.interactive?.fields) {
        currentStepData.value.interactive.fields.forEach(field => {
            interactiveData[field.name] = field.defaultValue || ''
        })
    }
}

const getStepIcon = (iconName) => {
    const icons = {
        'chat': ChatBubbleLeftRightIcon,
        'users': UsersIcon,
        'briefcase': BriefcaseIcon,
        'calendar': CalendarIcon,
        'chart': ChartBarIcon,
        'map': MapIcon,
        'academic': AcademicCapIcon,
        'heart': HeartIcon,
        'currency': CurrencyDollarIcon,
        'trophy': TrophyIcon,
        'sparkles': SparklesIcon,
        'rocket': RocketLaunchIcon
    }
    return icons[iconName] || SparklesIcon
}

const performInteractiveAction = () => {
    const action = currentStepData.value.interactive.action
    if (action) {
        window.dispatchEvent(new CustomEvent(action, {
            detail: { stepData: currentStepData.value, interactiveData }
        }))
    }
}

const nextStep = () => {
    if (isLastStep.value) {
        emit('complete-tour')
    } else {
        emit('next-step')
    }
}
</script>

<style scoped>
.guided-tour-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
}

.tour-highlight {
    transition: all 0.3s ease-in-out;
}

.tour-tooltip {
    transition: all 0.3s ease-in-out;
}
</style>