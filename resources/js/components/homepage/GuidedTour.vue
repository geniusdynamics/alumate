<template>
  <div class="guided-tour" v-if="isActive">
    <div class="fixed inset-0 z-50 bg-black bg-opacity-50 transition-opacity" @click="handleOverlayClick">
      <div v-if="currentStep" class="absolute z-60 pointer-events-auto bg-white rounded-lg shadow-xl max-w-sm p-6" :style="tooltipStyle">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
              {{ currentStepIndex + 1 }}
            </div>
            <span class="text-sm text-gray-500">{{ currentStepIndex + 1 }} of {{ steps.length }}</span>
          </div>
          <button @click="closeTour" class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors" aria-label="Close tour">✕</button>
        </div>
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ currentStep.title }}</h3>
          <p class="text-gray-600 leading-relaxed">{{ currentStep.description }}</p>
        </div>
        <div class="flex items-center justify-between">
          <button v-if="currentStepIndex > 0" @click="previousStep" class="flex items-center space-x-2 px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
            <span>← Previous</span>
          </button>
          <div v-else class="w-20"></div>
          <button v-if="currentStepIndex < steps.length - 1" @click="nextStep" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <span>Next →</span>
          </button>
          <button v-else @click="completeTour" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Complete Tour</button>
        </div>
      </div>
    </div>
    <div v-if="!currentStep && showControls" class="fixed bottom-6 right-6 z-50">
      <div class="bg-white rounded-lg shadow-lg p-4 max-w-sm">
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-semibold text-gray-900">Platform Tour</h4>
          <button @click="closeTour" class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors" aria-label="Close tour">✕</button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Discover key features and learn how to make the most of the platform.</p>
        <div class="flex space-x-2">
          <button @click="startTour" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">Start Tour</button>
          <button @click="closeTour" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">Skip</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { AudienceType } from '@/types/homepage'

interface TourStep {
  id: string
  title: string
  description: string
  target: string
  position: 'top' | 'bottom' | 'left' | 'right' | 'center'
}

interface Props {
  audience: AudienceType
  autoStart?: boolean
  showControls?: boolean
  steps?: TourStep[]
}

const props = withDefaults(defineProps<Props>(), {
  autoStart: false,
  showControls: true,
  steps: () => []
})

const emit = defineEmits<{
  start: []
  complete: []
  skip: []
  stepChange: [stepIndex: number, step: TourStep]
}>()

const isActive = ref(false)
const currentStepIndex = ref(0)

const steps = computed((): TourStep[] => {
  if (props.steps.length > 0) {
    return props.steps
  }
  return [
    {
      id: 'welcome',
      title: 'Welcome to Your Alumni Platform',
      description: 'Let\'s take a quick tour of the key features.',
      target: 'body',
      position: 'center'
    }
  ]
})

const currentStep = computed((): TourStep | null => {
  return steps.value[currentStepIndex.value] || null
})

const tooltipStyle = computed(() => {
  return {
    left: '50%',
    top: '50%',
    transform: 'translate(-50%, -50%)'
  }
})

const startTour = async (): Promise<void> => {
  isActive.value = true
  currentStepIndex.value = 0
  emit('start')
}

const closeTour = (): void => {
  isActive.value = false
  currentStepIndex.value = 0
  emit('skip')
}

const completeTour = (): void => {
  isActive.value = false
  currentStepIndex.value = 0
  emit('complete')
}

const nextStep = async (): Promise<void> => {
  if (currentStepIndex.value < steps.value.length - 1) {
    currentStepIndex.value++
  }
}

const previousStep = async (): Promise<void> => {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
  }
}

const handleOverlayClick = (event: MouseEvent): void => {
  if (event.target === event.currentTarget) {
    closeTour()
  }
}

onMounted(() => {
  if (props.autoStart) {
    setTimeout(() => {
      startTour()
    }, 1000)
  }
})
</script>

<style scoped>
.guided-tour {
  z-index: 9999;
}
</style>