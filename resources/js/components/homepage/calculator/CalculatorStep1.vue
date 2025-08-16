<template>
  <div class="calculator-step">
    <div class="mb-8">
      <h4 class="text-2xl font-bold text-gray-900 mb-2">
        Tell us about your current situation
      </h4>
      <p class="text-gray-600">
        We'll use this information to provide personalized career insights.
      </p>
    </div>

    <div class="space-y-6">
      <!-- Current Role -->
      <div>
        <label for="currentRole" class="block text-sm font-medium text-gray-700 mb-2">
          What is your current role? *
        </label>
        <select
          id="currentRole"
          :value="formData.currentRole"
          @change="updateField('currentRole', ($event.target as HTMLSelectElement).value)"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.currentRole }"
        >
          <option value="">Select your current role</option>
          <option value="recent_graduate">Recent Graduate</option>
          <option value="junior_professional">Junior Professional (1-3 years)</option>
          <option value="mid_level">Mid-Level Professional (4-7 years)</option>
          <option value="senior_professional">Senior Professional (8-12 years)</option>
          <option value="manager">Manager/Team Lead</option>
          <option value="senior_manager">Senior Manager</option>
          <option value="director">Director</option>
          <option value="vp_executive">VP/Executive</option>
          <option value="entrepreneur">Entrepreneur/Business Owner</option>
          <option value="consultant">Consultant/Freelancer</option>
          <option value="between_jobs">Between Jobs</option>
          <option value="career_change">Looking for Career Change</option>
        </select>
        <p v-if="errors.currentRole" class="mt-1 text-sm text-red-600">
          {{ errors.currentRole }}
        </p>
      </div>

      <!-- Industry -->
      <div>
        <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
          What industry do you work in? *
        </label>
        <select
          id="industry"
          :value="formData.industry"
          @change="updateField('industry', ($event.target as HTMLSelectElement).value)"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.industry }"
        >
          <option value="">Select your industry</option>
          <option value="technology">Technology</option>
          <option value="finance">Finance & Banking</option>
          <option value="healthcare">Healthcare & Medical</option>
          <option value="education">Education</option>
          <option value="consulting">Consulting</option>
          <option value="marketing">Marketing & Advertising</option>
          <option value="sales">Sales</option>
          <option value="engineering">Engineering</option>
          <option value="legal">Legal</option>
          <option value="real_estate">Real Estate</option>
          <option value="retail">Retail & E-commerce</option>
          <option value="manufacturing">Manufacturing</option>
          <option value="media">Media & Entertainment</option>
          <option value="nonprofit">Non-Profit</option>
          <option value="government">Government</option>
          <option value="hospitality">Hospitality & Tourism</option>
          <option value="transportation">Transportation & Logistics</option>
          <option value="energy">Energy & Utilities</option>
          <option value="agriculture">Agriculture</option>
          <option value="other">Other</option>
        </select>
        <p v-if="errors.industry" class="mt-1 text-sm text-red-600">
          {{ errors.industry }}
        </p>
      </div>

      <!-- Experience Years -->
      <div>
        <label for="experienceYears" class="block text-sm font-medium text-gray-700 mb-2">
          Years of professional experience *
        </label>
        <div class="relative">
          <input
            type="range"
            id="experienceYears"
            :value="formData.experienceYears"
            @input="updateField('experienceYears', parseInt(($event.target as HTMLInputElement).value))"
            min="0"
            max="30"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
          />
          <div class="flex justify-between text-sm text-gray-500 mt-2">
            <span>0 years</span>
            <span class="font-medium text-blue-600">{{ formData.experienceYears }} years</span>
            <span>30+ years</span>
          </div>
        </div>
        <p v-if="errors.experienceYears" class="mt-1 text-sm text-red-600">
          {{ errors.experienceYears }}
        </p>
      </div>

      <!-- Current Salary (Optional) -->
      <div>
        <label for="currentSalary" class="block text-sm font-medium text-gray-700 mb-2">
          Current annual salary (optional)
          <span class="text-gray-500 text-xs ml-1">- helps provide more accurate projections</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-3 text-gray-500">$</span>
          <input
            type="number"
            id="currentSalary"
            :value="formData.currentSalary || ''"
            @input="updateField('currentSalary', ($event.target as HTMLInputElement).value ? parseInt(($event.target as HTMLInputElement).value) : undefined)"
            placeholder="75000"
            min="0"
            step="1000"
            class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <p class="mt-1 text-xs text-gray-500">
          This information is kept confidential and used only for calculations
        </p>
      </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
      <div></div> <!-- Empty div for spacing -->
      <button
        @click="handleNext"
        :disabled="!canProceed"
        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
      >
        Next Step
        <svg class="w-4 h-4 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CalculatorInput } from '@/types/homepage'

// Props
interface Props {
  formData: CalculatorInput
  errors: Record<string, string>
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false
})

// Emits
const emit = defineEmits<{
  update: [updates: Partial<CalculatorInput>]
  next: []
}>()

// Computed
const canProceed = computed(() => {
  return props.formData.currentRole && 
         props.formData.industry && 
         props.formData.experienceYears >= 0
})

// Methods
const updateField = (field: keyof CalculatorInput, value: any) => {
  emit('update', { [field]: value })
}

const handleNext = () => {
  if (canProceed.value) {
    emit('next')
  }
}
</script>

<style scoped>
/* Custom slider styling */
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #3B82F6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-webkit-slider-track {
  height: 8px;
  border-radius: 4px;
  background: #E5E7EB;
}

.slider::-moz-range-track {
  height: 8px;
  border-radius: 4px;
  background: #E5E7EB;
}
</style>