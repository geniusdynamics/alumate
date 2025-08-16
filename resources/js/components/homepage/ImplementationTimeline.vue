<template>
  <div class="implementation-timeline bg-white rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="mb-8">
      <h3 class="text-xl font-bold text-gray-900 mb-2">{{ title }}</h3>
      <p v-if="subtitle" class="text-gray-600">{{ subtitle }}</p>
      <div class="flex items-center mt-2 text-sm text-gray-500">
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
        </svg>
        Total Duration: {{ totalDuration }}
      </div>
    </div>

    <!-- Timeline -->
    <div class="relative">
      <!-- Timeline Line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
      
      <!-- Timeline Items -->
      <div class="space-y-8">
        <div 
          v-for="(phase, index) in phases" 
          :key="phase.id"
          class="relative flex items-start"
        >
          <!-- Timeline Node -->
          <div class="relative z-10 flex items-center justify-center">
            <div 
              class="w-16 h-16 rounded-full border-4 border-white shadow-lg flex items-center justify-center"
              :class="getPhaseStatusColor(phase.status)"
            >
              <component 
                :is="getPhaseIcon(phase.status)" 
                class="w-6 h-6 text-white"
              />
            </div>
          </div>

          <!-- Phase Content -->
          <div class="ml-6 flex-1">
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 hover:border-blue-300 transition-colors duration-200">
              <!-- Phase Header -->
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h4 class="text-lg font-semibold text-gray-900">{{ phase.name }}</h4>
                  <p class="text-sm text-gray-600">{{ phase.description }}</p>
                </div>
                <div class="text-right">
                  <div class="text-sm font-medium text-gray-900">{{ phase.duration }}</div>
                  <div 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1"
                    :class="getStatusBadgeColor(phase.status)"
                  >
                    {{ formatStatus(phase.status) }}
                  </div>
                </div>
              </div>

              <!-- Deliverables -->
              <div class="mb-4">
                <h5 class="font-medium text-gray-900 mb-2">Key Deliverables:</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div 
                    v-for="deliverable in phase.deliverables" 
                    :key="deliverable"
                    class="flex items-center text-sm text-gray-700"
                  >
                    <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ deliverable }}
                  </div>
                </div>
              </div>

              <!-- Milestones -->
              <div v-if="phase.milestones.length > 0" class="mb-4">
                <h5 class="font-medium text-gray-900 mb-2">Milestones:</h5>
                <div class="space-y-2">
                  <div 
                    v-for="milestone in phase.milestones" 
                    :key="milestone.id"
                    class="flex items-center justify-between p-2 bg-white rounded border"
                  >
                    <div class="flex items-center">
                      <div 
                        class="w-3 h-3 rounded-full mr-3"
                        :class="getMilestoneStatusColor(milestone.status)"
                      ></div>
                      <span class="text-sm font-medium text-gray-900">{{ milestone.name }}</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ milestone.dueDate }}</span>
                  </div>
                </div>
              </div>

              <!-- Dependencies -->
              <div v-if="phase.dependencies.length > 0" class="text-sm text-gray-600">
                <strong>Dependencies:</strong> {{ phase.dependencies.join(', ') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Summary -->
    <div class="mt-8 bg-blue-50 rounded-lg p-4 border border-blue-200">
      <div class="flex items-center justify-between">
        <div>
          <h4 class="font-semibold text-gray-900 mb-1">Implementation Progress</h4>
          <p class="text-sm text-gray-600">{{ completedPhases }} of {{ totalPhases }} phases completed</p>
        </div>
        <div class="text-right">
          <div class="text-2xl font-bold text-blue-600">{{ progressPercentage }}%</div>
          <div class="text-sm text-gray-600">Complete</div>
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div class="mt-3">
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-blue-600 h-2 rounded-full transition-all duration-1000 ease-out"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { DevelopmentPhase } from '@/types/homepage'

interface Props {
  title: string
  subtitle?: string
  phases: DevelopmentPhase[]
  totalDuration: string
}

const props = defineProps<Props>()

const totalPhases = computed(() => props.phases.length)

const completedPhases = computed(() => 
  props.phases.filter(phase => phase.status === 'completed').length
)

const progressPercentage = computed(() => {
  if (totalPhases.value === 0) return 0
  return Math.round((completedPhases.value / totalPhases.value) * 100)
})

const getPhaseStatusColor = (status: string): string => {
  const colors = {
    pending: 'bg-gray-400',
    in_progress: 'bg-blue-500',
    completed: 'bg-green-500',
    delayed: 'bg-red-500'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-400'
}

const getStatusBadgeColor = (status: string): string => {
  const colors = {
    pending: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    delayed: 'bg-red-100 text-red-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const getMilestoneStatusColor = (status: string): string => {
  const colors = {
    pending: 'bg-gray-300',
    in_progress: 'bg-blue-400',
    completed: 'bg-green-400',
    delayed: 'bg-red-400'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-300'
}

const getPhaseIcon = (status: string) => {
  // Return SVG component based on status
  return 'svg' // Placeholder - would be actual icon components
}

const formatStatus = (status: string): string => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>

<style scoped>
/* Timeline animations */
.implementation-timeline .relative:hover .bg-gray-50 {
  @apply bg-blue-50 border-blue-300;
}

/* Smooth transitions for all interactive elements */
.implementation-timeline * {
  @apply transition-colors duration-200;
}

/* Timeline line gradient effect */
.implementation-timeline::before {
  content: '';
  position: absolute;
  left: 2rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #3b82f6, #10b981);
  opacity: 0.3;
}
</style>