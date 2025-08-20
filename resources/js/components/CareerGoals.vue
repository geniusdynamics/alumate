<template>
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center">
        <FlagIcon class="w-5 h-5 mr-2 text-blue-600" />
        Career Goals & Suggestions
      </h3>
      <button
        @click="collapsed = !collapsed"
        class="text-gray-400 hover:text-gray-600 transition-colors"
      >
        <ChevronDownIcon 
          :class="['w-5 h-5 transition-transform', { 'rotate-180': !collapsed }]" 
        />
      </button>
    </div>

    <div v-if="!collapsed" class="space-y-4">
      <p class="text-sm text-gray-600 mb-4">
        Based on your career progression, here are some personalized suggestions for your next steps:
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="suggestion in suggestions"
          :key="suggestion.type"
          class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow"
        >
          <!-- Priority indicator -->
          <div class="flex items-center justify-between mb-3">
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              getPriorityColor(suggestion.priority)
            ]">
              {{ formatPriority(suggestion.priority) }}
            </span>
            <component 
              :is="getSuggestionIcon(suggestion.type)" 
              class="w-5 h-5 text-gray-400"
            />
          </div>

          <!-- Content -->
          <h4 class="font-medium text-gray-900 mb-2">{{ suggestion.title }}</h4>
          <p class="text-sm text-gray-600 mb-4">{{ suggestion.description }}</p>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <button
              @click="markAsCompleted(suggestion)"
              class="flex-1 px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors"
            >
              Mark as Goal
            </button>
            <button
              @click="dismissSuggestion(suggestion)"
              class="px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
            >
              Dismiss
            </button>
          </div>
        </div>
      </div>

      <!-- Custom goal input -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex items-center space-x-3">
          <input
            v-model="customGoal"
            type="text"
            placeholder="Add your own career goal..."
            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @keyup.enter="addCustomGoal"
          />
          <button
            @click="addCustomGoal"
            :disabled="!customGoal.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Add Goal
          </button>
        </div>
      </div>

      <!-- Active goals -->
      <div v-if="activeGoals.length > 0" class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="font-medium text-gray-900 mb-3">Active Goals</h4>
        <div class="space-y-2">
          <div
            v-for="goal in activeGoals"
            :key="goal.id"
            class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200"
          >
            <div class="flex items-center space-x-3">
              <input
                type="checkbox"
                :checked="goal.completed"
                @change="toggleGoalCompletion(goal)"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span :class="[
                'text-sm',
                goal.completed ? 'line-through text-gray-500' : 'text-gray-900'
              ]">
                {{ goal.title }}
              </span>
            </div>
            <button
              @click="removeGoal(goal)"
              class="text-gray-400 hover:text-red-600 transition-colors"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  FlagIcon,
  ChevronDownIcon,
  XMarkIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  TrophyIcon,
  UserGroupIcon,
  ArrowTrendingUpIcon as TrendingUpIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  suggestions: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['goalAdded', 'goalCompleted', 'suggestionDismissed'])

// Reactive data
const collapsed = ref(false)
const customGoal = ref('')
const activeGoals = ref([
  // Mock data - in real app this would come from API
  { id: 1, title: 'Complete leadership training', completed: false, type: 'custom' },
  { id: 2, title: 'Get AWS certification', completed: true, type: 'certification' }
])

// Methods
const getPriorityColor = (priority) => {
  const colors = {
    'high': 'bg-red-100 text-red-800',
    'medium': 'bg-yellow-100 text-yellow-800',
    'low': 'bg-green-100 text-green-800'
  }
  return colors[priority] || 'bg-gray-100 text-gray-800'
}

const formatPriority = (priority) => {
  return priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority'
}

const getSuggestionIcon = (type) => {
  const icons = {
    'skill_development': AcademicCapIcon,
    'specialization': TrophyIcon,
    'leadership': UserGroupIcon,
    'career_move': BriefcaseIcon,
    'certification': AcademicCapIcon,
    'networking': UserGroupIcon
  }
  return icons[type] || TrendingUpIcon
}

const markAsCompleted = (suggestion) => {
  const newGoal = {
    id: Date.now(),
    title: suggestion.title,
    completed: false,
    type: suggestion.type,
    description: suggestion.description
  }
  
  activeGoals.value.push(newGoal)
  emit('goalAdded', newGoal)
}

const dismissSuggestion = (suggestion) => {
  emit('suggestionDismissed', suggestion)
}

const addCustomGoal = () => {
  if (!customGoal.value.trim()) return
  
  const newGoal = {
    id: Date.now(),
    title: customGoal.value.trim(),
    completed: false,
    type: 'custom'
  }
  
  activeGoals.value.push(newGoal)
  customGoal.value = ''
  emit('goalAdded', newGoal)
}

const toggleGoalCompletion = (goal) => {
  goal.completed = !goal.completed
  emit('goalCompleted', goal)
}

const removeGoal = (goal) => {
  const index = activeGoals.value.findIndex(g => g.id === goal.id)
  if (index > -1) {
    activeGoals.value.splice(index, 1)
  }
}
</script>