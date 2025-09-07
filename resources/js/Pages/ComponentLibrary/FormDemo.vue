<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
          Form Component Library Demo
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
          Explore our comprehensive form components with drag-and-drop field builder, 
          real-time validation, and mobile-optimized design.
        </p>
      </div>

      <!-- Navigation Tabs -->
      <div class="mb-8">
        <nav class="flex space-x-8 justify-center" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
            @click="activeTab = tab.id"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Form Builder Tab -->
      <div v-if="activeTab === 'builder'" class="space-y-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              Drag-and-Drop Form Builder
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-300">
              Build forms visually by dragging field types from the sidebar and configuring their properties.
            </p>
          </div>
          
          <FormBuilder
            :initial-config="builderConfig"
            @update:config="updateBuilderConfig"
            @preview="previewForm"
            @save="saveForm"
          />
        </div>
      </div>

      <!-- Form Templates Tab -->
      <div v-if="activeTab === 'templates'" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="template in formTemplates"
            :key="template.id"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow cursor-pointer"
            @click="selectTemplate(template)"
          >
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ template.name }}
                </h3>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getCategoryColor(template.category)
                  ]"
                >
                  {{ template.category }}
                </span>
              </div>
              
              <p class="text-gray-600 dark:text-gray-300 mb-4">
                {{ template.description }}
              </p>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ template.config.fields.length }} fields
                </span>
                <button
                  class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @click.stop="previewTemplate(template)"
                >
                  Preview
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Forms Tab -->
      <div v-if="activeTab === 'live'" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Lead Capture Form -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Lead Capture Form
              </h3>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Individual alumni signup form with validation
              </p>
            </div>
            
            <div class="p-6">
              <FormBase
                :config="leadCaptureTemplate.config"
                @submit="handleFormSubmit"
                @validation-change="handleValidationChange"
              />
            </div>
          </div>

          <!-- Demo Request Form -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Demo Request Form
              </h3>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Institution demo request with qualification fields
              </p>
            </div>
            
            <div class="p-6">
              <FormBase
                :config="demoRequestTemplate.config"
                @submit="handleFormSubmit"
                @validation-change="handleValidationChange"
              />
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="max-w-2xl mx-auto">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Contact Form
              </h3>
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                General contact form with anti-spam protection
              </p>
            </div>
            
            <div class="p-6">
              <FormBase
                :config="contactTemplate.config"
                @submit="handleFormSubmit"
                @validation-change="handleValidationChange"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Features Tab -->
      <div v-if="activeTab === 'features'" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6"
          >
            <div class="flex items-center mb-4">
              <div :class="['p-2 rounded-lg', feature.iconBg]">
                <component
                  :is="feature.icon"
                  class="w-6 h-6"
                  :class="feature.iconColor"
                  aria-hidden="true"
                />
              </div>
              <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">
                {{ feature.title }}
              </h3>
            </div>
            
            <p class="text-gray-600 dark:text-gray-300 mb-4">
              {{ feature.description }}
            </p>
            
            <ul class="space-y-2">
              <li
                v-for="item in feature.items"
                :key="item"
                class="flex items-center text-sm text-gray-600 dark:text-gray-300"
              >
                <svg
                  class="w-4 h-4 text-green-500 mr-2 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
                {{ item }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div
      v-if="previewConfig"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          aria-hidden="true"
          @click="closePreview"
        />

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Form Preview
            </h3>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
              @click="closePreview"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="max-h-96 overflow-y-auto">
            <FormBase
              :config="previewConfig"
              :readonly="true"
              @submit="() => {}"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { FormComponentConfig } from '@/types/components'
import { FormBase, FormBuilder } from '@/components/ComponentLibrary/Forms'
import { formTemplates, leadCaptureTemplate, demoRequestTemplate, contactTemplate } from '@/components/ComponentLibrary/Forms/templates'

// Refs
const activeTab = ref('builder')
const previewConfig = ref<FormComponentConfig | null>(null)
const builderConfig = ref<FormComponentConfig>({
  title: 'My Custom Form',
  description: 'Build your form using the drag-and-drop interface',
  fields: [],
  layout: 'single-column',
  spacing: 'default',
  submission: {
    method: 'POST',
    action: '/api/forms/submit'
  }
})

// Tab configuration
const tabs = [
  { id: 'builder', name: 'Form Builder' },
  { id: 'templates', name: 'Templates' },
  { id: 'live', name: 'Live Forms' },
  { id: 'features', name: 'Features' }
]

// Features showcase
const features = [
  {
    title: 'Drag & Drop Builder',
    description: 'Visually build forms by dragging field types and arranging them.',
    icon: 'CursorArrowRaysIcon',
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400',
    items: [
      'Visual field arrangement',
      'Real-time preview',
      'Field property editor',
      'Responsive layouts'
    ]
  },
  {
    title: 'Real-time Validation',
    description: 'Comprehensive validation with instant feedback and accessibility.',
    icon: 'CheckCircleIcon',
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400',
    items: [
      'Client & server validation',
      'Custom validation rules',
      'Accessible error messages',
      'Validation summary'
    ]
  },
  {
    title: 'Mobile Optimized',
    description: 'Touch-friendly interfaces optimized for all devices.',
    icon: 'DevicePhoneMobileIcon',
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400',
    items: [
      'Responsive design',
      'Touch-optimized inputs',
      'Mobile-first approach',
      'Gesture support'
    ]
  },
  {
    title: 'Accessibility First',
    description: 'WCAG compliant with comprehensive accessibility features.',
    icon: 'EyeIcon',
    iconBg: 'bg-orange-100 dark:bg-orange-900/30',
    iconColor: 'text-orange-600 dark:text-orange-400',
    items: [
      'Screen reader support',
      'Keyboard navigation',
      'ARIA labels',
      'High contrast support'
    ]
  },
  {
    title: 'CRM Integration',
    description: 'Seamless integration with popular CRM platforms.',
    icon: 'LinkIcon',
    iconBg: 'bg-indigo-100 dark:bg-indigo-900/30',
    iconColor: 'text-indigo-600 dark:text-indigo-400',
    items: [
      'HubSpot integration',
      'Salesforce support',
      'Lead scoring',
      'Automated workflows'
    ]
  },
  {
    title: 'Advanced Features',
    description: 'Auto-save, analytics, and anti-spam protection.',
    icon: 'CogIcon',
    iconBg: 'bg-gray-100 dark:bg-gray-700',
    iconColor: 'text-gray-600 dark:text-gray-400',
    items: [
      'Auto-save progress',
      'Analytics tracking',
      'Honeypot protection',
      'reCAPTCHA support'
    ]
  }
]

// Methods
const updateBuilderConfig = (config: FormComponentConfig) => {
  builderConfig.value = { ...config }
}

const previewForm = (config: FormComponentConfig) => {
  previewConfig.value = { ...config }
}

const previewTemplate = (template: any) => {
  previewConfig.value = { ...template.config }
}

const closePreview = () => {
  previewConfig.value = null
}

const selectTemplate = (template: any) => {
  builderConfig.value = { ...template.config }
  activeTab.value = 'builder'
}

const saveForm = (config: FormComponentConfig) => {
  console.log('Saving form configuration:', config)
  // Here you would typically save to your backend
  alert('Form configuration saved! (Check console for details)')
}

const handleFormSubmit = (data: Record<string, any>) => {
  console.log('Form submitted:', data)
  alert('Form submitted successfully! (Check console for details)')
}

const handleValidationChange = (isValid: boolean, errors: Record<string, string>) => {
  console.log('Validation changed:', { isValid, errors })
}

const getCategoryColor = (category: string) => {
  const colors = {
    'lead-capture': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    'demo-request': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'contact': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
    'newsletter': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    'survey': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300'
  }
  return colors[category as keyof typeof colors] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

// Icon components (simplified)
const CursorArrowRaysIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>' }
const CheckCircleIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' }
const DevicePhoneMobileIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' }
const EyeIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>' }
const LinkIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>' }
const CogIcon = { template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' }
</script>

<style scoped>
/* Custom scrollbar for modal */
.max-h-96::-webkit-scrollbar {
  width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Dark mode scrollbar */
.dark .max-h-96::-webkit-scrollbar-track {
  background: #374151;
}

.dark .max-h-96::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark .max-h-96::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>