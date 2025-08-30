<template>
  <div 
    class="component-configurator"
    :class="containerClasses"
    role="application"
    :aria-label="ariaLabel"
  >
    <!-- Configurator Header -->
    <header class="component-configurator__header">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
          <div class="flex items-center space-x-2">
            <Icon 
              :name="getCategoryIcon(component.category)" 
              class="h-6 w-6 text-gray-500 dark:text-gray-400" 
              aria-hidden="true" 
            />
            <div>
              <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Configure {{ component.name }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Customize component settings and styling
              </p>
            </div>
          </div>
        </div>
        
        <!-- Configuration Controls -->
        <div class="flex items-center space-x-3">
          <!-- Undo/Redo -->
          <div class="flex items-center space-x-1">
            <button
              @click="undo"
              :disabled="!canUndo"
              class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
              aria-label="Undo changes"
              title="Undo (Ctrl+Z)"
            >
              <Icon name="arrow-uturn-left" class="h-4 w-4" />
            </button>
            <button
              @click="redo"
              :disabled="!canRedo"
              class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
              aria-label="Redo changes"
              title="Redo (Ctrl+Y)"
            >
              <Icon name="arrow-uturn-right" class="h-4 w-4" />
            </button>
          </div>
          
          <!-- Reset Configuration -->
          <button
            @click="resetConfiguration"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            aria-label="Reset to default configuration"
          >
            <Icon name="arrow-path" class="h-4 w-4 mr-2" />
            Reset
          </button>
          
          <!-- Save Configuration -->
          <button
            @click="saveConfiguration"
            :disabled="!hasChanges"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            aria-label="Save configuration changes"
          >
            <Icon name="check" class="h-4 w-4 mr-2" />
            Save Changes
          </button>
        </div>
      </div>
    </header>

    <!-- Configuration Content -->
    <div class="component-configurator__content flex flex-1 overflow-hidden">
      <!-- Configuration Form -->
      <main class="flex-1 overflow-auto">
        <div class="max-w-4xl mx-auto p-6">
          <!-- Configuration Tabs -->
          <div class="mb-8">
            <nav class="flex space-x-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1" role="tablist">
              <button
                v-for="tab in configurationTabs"
                :key="tab.id"
                @click="setActiveTab(tab.id)"
                :class="getTabClasses(tab.id)"
                :aria-selected="activeTab === tab.id"
                :aria-controls="`config-panel-${tab.id}`"
                role="tab"
              >
                <Icon :name="tab.icon" class="h-4 w-4 mr-2" />
                {{ tab.name }}
              </button>
            </nav>
          </div>
          
          <!-- Configuration Panels -->
          <div class="configuration-panels">
            <!-- Content Configuration -->
            <div
              v-show="activeTab === 'content'"
              id="config-panel-content"
              role="tabpanel"
              aria-labelledby="tab-content"
              class="space-y-6"
            >
              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Content Settings
                </h3>
                
                <!-- Dynamic form generation based on component schema -->
                <div class="space-y-6">
                  <template v-for="field in contentFields" :key="field.name">
                    <!-- Text Input -->
                    <div v-if="field.type === 'text'" class="form-field">
                      <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                      </label>
                      <input
                        :id="field.name"
                        v-model="configuration[field.name]"
                        :type="field.inputType || 'text'"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        :maxlength="field.maxLength"
                        @input="handleFieldChange(field.name, $event.target.value)"
                        class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      />
                      <p v-if="field.helpText" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ field.helpText }}
                      </p>
                      <div v-if="getFieldError(field.name)" class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ getFieldError(field.name) }}
                      </div>
                    </div>
                    
                    <!-- Textarea -->
                    <div v-else-if="field.type === 'textarea'" class="form-field">
                      <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                      </label>
                      <textarea
                        :id="field.name"
                        v-model="configuration[field.name]"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        :rows="field.rows || 3"
                        :maxlength="field.maxLength"
                        @input="handleFieldChange(field.name, $event.target.value)"
                        class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 resize-vertical"
                      ></textarea>
                      <p v-if="field.helpText" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ field.helpText }}
                      </p>
                      <div v-if="getFieldError(field.name)" class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ getFieldError(field.name) }}
                      </div>
                    </div>
                    
                    <!-- Select -->
                    <div v-else-if="field.type === 'select'" class="form-field">
                      <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ field.label }}
                        <span v-if="field.required" class="text-red-500">*</span>
                      </label>
                      <select
                        :id="field.name"
                        v-model="configuration[field.name]"
                        :required="field.required"
                        @change="handleFieldChange(field.name, $event.target.value)"
                        class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option v-if="field.placeholder" value="" disabled>{{ field.placeholder }}</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">
                          {{ option.label }}
                        </option>
                      </select>
                      <p v-if="field.helpText" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ field.helpText }}
                      </p>
                      <div v-if="getFieldError(field.name)" class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ getFieldError(field.name) }}
                      </div>
                    </div>
                    
                    <!-- Checkbox -->
                    <div v-else-if="field.type === 'checkbox'" class="form-field">
                      <div class="flex items-center">
                        <input
                          :id="field.name"
                          v-model="configuration[field.name]"
                          type="checkbox"
                          @change="handleFieldChange(field.name, $event.target.checked)"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                        />
                        <label :for="field.name" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          {{ field.label }}
                        </label>
                      </div>
                      <p v-if="field.helpText" class="mt-1 text-xs text-gray-500 dark:text-gray-400 ml-6">
                        {{ field.helpText }}
                      </p>
                      <div v-if="getFieldError(field.name)" class="mt-1 text-xs text-red-600 dark:text-red-400 ml-6">
                        {{ getFieldError(field.name) }}
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            
            <!-- Visual Styling -->
            <div
              v-show="activeTab === 'styling'"
              id="config-panel-styling"
              role="tabpanel"
              aria-labelledby="tab-styling"
              class="space-y-6"
            >
              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Visual Styling
                </h3>
                
                <div class="space-y-6">
                  <!-- Color Picker -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Primary Color
                    </label>
                    <div class="flex items-center space-x-3">
                      <div class="relative">
                        <input
                          v-model="configuration.primaryColor"
                          type="color"
                          @input="handleFieldChange('primaryColor', $event.target.value)"
                          class="h-10 w-16 border border-gray-300 dark:border-gray-600 rounded-md cursor-pointer"
                        />
                      </div>
                      <input
                        v-model="configuration.primaryColor"
                        type="text"
                        @input="handleFieldChange('primaryColor', $event.target.value)"
                        class="flex-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="#000000"
                      />
                    </div>
                  </div>
                  
                  <!-- Font Selector -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Font Family
                    </label>
                    <select
                      v-model="configuration.fontFamily"
                      @change="handleFieldChange('fontFamily', $event.target.value)"
                      class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="system">System Default</option>
                      <option value="inter">Inter</option>
                      <option value="roboto">Roboto</option>
                      <option value="open-sans">Open Sans</option>
                      <option value="lato">Lato</option>
                      <option value="montserrat">Montserrat</option>
                      <option value="poppins">Poppins</option>
                    </select>
                  </div>
                  
                  <!-- Spacing Controls -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Spacing
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Padding</label>
                        <select
                          v-model="configuration.padding"
                          @change="handleFieldChange('padding', $event.target.value)"
                          class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="none">None</option>
                          <option value="sm">Small</option>
                          <option value="md">Medium</option>
                          <option value="lg">Large</option>
                          <option value="xl">Extra Large</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Margin</label>
                        <select
                          v-model="configuration.margin"
                          @change="handleFieldChange('margin', $event.target.value)"
                          class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                          <option value="none">None</option>
                          <option value="sm">Small</option>
                          <option value="md">Medium</option>
                          <option value="lg">Large</option>
                          <option value="xl">Extra Large</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Layout Configuration -->
            <div
              v-show="activeTab === 'layout'"
              id="config-panel-layout"
              role="tabpanel"
              aria-labelledby="tab-layout"
              class="space-y-6"
            >
              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Layout Settings
                </h3>
                
                <div class="space-y-6">
                  <!-- Layout Type -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Layout Type
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                      <button
                        v-for="layout in layoutOptions"
                        :key="layout.value"
                        @click="handleFieldChange('layout', layout.value)"
                        :class="getLayoutButtonClasses(layout.value)"
                        class="p-3 border rounded-lg text-left transition-colors"
                      >
                        <div class="flex items-center space-x-2">
                          <Icon :name="layout.icon" class="h-5 w-5" />
                          <div>
                            <div class="font-medium text-sm">{{ layout.label }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ layout.description }}</div>
                          </div>
                        </div>
                      </button>
                    </div>
                  </div>
                  
                  <!-- Responsive Settings -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Responsive Behavior
                    </label>
                    <div class="space-y-3">
                      <div class="flex items-center">
                        <input
                          id="mobile-optimized"
                          v-model="configuration.mobileOptimized"
                          type="checkbox"
                          @change="handleFieldChange('mobileOptimized', $event.target.checked)"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                        />
                        <label for="mobile-optimized" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Mobile Optimized
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="tablet-optimized"
                          v-model="configuration.tabletOptimized"
                          type="checkbox"
                          @change="handleFieldChange('tabletOptimized', $event.target.checked)"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                        />
                        <label for="tablet-optimized" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Tablet Optimized
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Advanced Settings -->
            <div
              v-show="activeTab === 'advanced'"
              id="config-panel-advanced"
              role="tabpanel"
              aria-labelledby="tab-advanced"
              class="space-y-6"
            >
              <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                  Advanced Settings
                </h3>
                
                <div class="space-y-6">
                  <!-- Custom CSS -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Custom CSS
                    </label>
                    <textarea
                      v-model="configuration.customCSS"
                      @input="handleFieldChange('customCSS', $event.target.value)"
                      rows="6"
                      placeholder="/* Add custom CSS styles here */"
                      class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-mono resize-vertical"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Add custom CSS to override default styles. Use with caution.
                    </p>
                  </div>
                  
                  <!-- Animation Settings -->
                  <div class="form-field">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Animation Settings
                    </label>
                    <div class="space-y-3">
                      <div class="flex items-center">
                        <input
                          id="animations-enabled"
                          v-model="configuration.animationsEnabled"
                          type="checkbox"
                          @change="handleFieldChange('animationsEnabled', $event.target.checked)"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                        />
                        <label for="animations-enabled" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                          Enable Animations
                        </label>
                      </div>
                      <div v-if="configuration.animationsEnabled" class="ml-6 space-y-3">
                        <div>
                          <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Animation Duration</label>
                          <select
                            v-model="configuration.animationDuration"
                            @change="handleFieldChange('animationDuration', $event.target.value)"
                            class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                          >
                            <option value="fast">Fast (200ms)</option>
                            <option value="normal">Normal (300ms)</option>
                            <option value="slow">Slow (500ms)</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
      
      <!-- Configuration Sidebar -->
      <aside class="component-configurator__sidebar w-80 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-auto">
        <div class="p-6">
          <!-- Configuration Presets -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Configuration Presets
            </h3>
            
            <!-- Preset Selection -->
            <div class="space-y-2 mb-4">
              <button
                v-for="preset in configurationPresets"
                :key="preset.id"
                @click="applyPreset(preset)"
                class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                <div class="font-medium text-sm text-gray-900 dark:text-white">{{ preset.name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ preset.description }}</div>
              </button>
            </div>
            
            <!-- Save Current as Preset -->
            <button
              @click="saveAsPreset"
              class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Icon name="bookmark" class="h-4 w-4 mr-2" />
              Save as Preset
            </button>
          </div>
          
          <!-- Import/Export -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Import/Export
            </h3>
            
            <div class="space-y-3">
              <!-- Export Configuration -->
              <button
                @click="exportConfiguration"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <Icon name="arrow-down-tray" class="h-4 w-4 mr-2" />
                Export Config
              </button>
              
              <!-- Import Configuration -->
              <div class="relative">
                <input
                  ref="importFileInput"
                  type="file"
                  accept=".json"
                  @change="importConfiguration"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                />
                <button
                  class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <Icon name="arrow-up-tray" class="h-4 w-4 mr-2" />
                  Import Config
                </button>
              </div>
            </div>
          </div>
          
          <!-- Configuration Summary -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Configuration Summary
            </h3>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Fields Modified:</span>
                <span class="font-medium">{{ modifiedFieldsCount }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Validation Errors:</span>
                <span class="font-medium" :class="validationErrors.length > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                  {{ validationErrors.length }}
                </span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Last Modified:</span>
                <span class="font-medium">{{ formatLastModified }}</span>
              </div>
            </div>
          </div>
          
          <!-- Validation Errors -->
          <div v-if="validationErrors.length > 0" class="mb-6">
            <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">
              Validation Errors
            </h3>
            
            <div class="space-y-2">
              <div
                v-for="error in validationErrors"
                :key="error.field"
                class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3"
              >
                <div class="font-medium text-sm text-red-800 dark:text-red-200">{{ error.field }}</div>
                <div class="text-xs text-red-600 dark:text-red-400 mt-1">{{ error.message }}</div>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
    
    <!-- Save Preset Modal -->
    <div
      v-if="showSavePresetModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeSavePresetModal"></div>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
              Save Configuration Preset
            </h3>
            
            <div class="space-y-4">
              <div>
                <label for="preset-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Preset Name
                </label>
                <input
                  id="preset-name"
                  v-model="newPresetName"
                  type="text"
                  placeholder="Enter preset name"
                  class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              
              <div>
                <label for="preset-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Description (Optional)
                </label>
                <textarea
                  id="preset-description"
                  v-model="newPresetDescription"
                  rows="3"
                  placeholder="Describe this preset configuration"
                  class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 resize-vertical"
                ></textarea>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="confirmSavePreset"
              :disabled="!newPresetName.trim()"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              Save Preset
            </button>
            <button
              @click="closeSavePresetModal"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Screen Reader Announcements -->
    <div
      :aria-live="announcements.length > 0 ? 'polite' : 'off'"
      :aria-atomic="true"
      class="sr-only"
    >
      {{ currentAnnouncement }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { Component, ComponentCategory } from '@/types/components'
import { useDebounce } from '@/composables/useDebounce'
import { useAnalytics } from '@/composables/useAnalytics'

// Import child components
import Icon from '@/components/Common/Icon.vue'

interface ConfigurationField {
  name: string
  label: string
  type: 'text' | 'textarea' | 'select' | 'checkbox' | 'color' | 'number' | 'range'
  inputType?: string
  required?: boolean
  placeholder?: string
  helpText?: string
  maxLength?: number
  rows?: number
  options?: Array<{ label: string; value: any }>
  min?: number
  max?: number
  step?: number
}

interface ConfigurationPreset {
  id: string
  name: string
  description: string
  config: Record<string, any>
  createdAt: string
}

interface ValidationError {
  field: string
  message: string
}

interface ConfigurationTab {
  id: string
  name: string
  icon: string
}

interface Props {
  component: Component
  initialConfig?: Record<string, any>
}

interface Emits {
  (e: 'config-updated', config: Record<string, any>): void
  (e: 'config-saved', config: Record<string, any>): void
  (e: 'preset-applied', preset: ConfigurationPreset): void
}

const props = withDefaults(defineProps<Props>(), {
  initialConfig: () => ({})
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const activeTab = ref<string>('content')
const configuration = ref<Record<string, any>>({})
const originalConfiguration = ref<Record<string, any>>({})
const configurationHistory = ref<Record<string, any>[]>([])
const historyIndex = ref(-1)
const validationErrors = ref<ValidationError[]>([])
const lastModified = ref<Date>(new Date())
const announcements = ref<string[]>([])

// Modal state
const showSavePresetModal = ref(false)
const newPresetName = ref('')
const newPresetDescription = ref('')

// File input ref
const importFileInput = ref<HTMLInputElement>()

// Configuration tabs
const configurationTabs = ref<ConfigurationTab[]>([
  { id: 'content', name: 'Content', icon: 'document-text' },
  { id: 'styling', name: 'Styling', icon: 'paint-brush' },
  { id: 'layout', name: 'Layout', icon: 'squares-2x2' },
  { id: 'advanced', name: 'Advanced', icon: 'cog-6-tooth' }
])

// Configuration presets (would be loaded from API in real implementation)
const configurationPresets = ref<ConfigurationPreset[]>([
  {
    id: 'minimal',
    name: 'Minimal',
    description: 'Clean and simple design with minimal styling',
    config: {
      theme: 'minimal',
      primaryColor: '#6366f1',
      fontFamily: 'system',
      padding: 'sm',
      margin: 'sm'
    },
    createdAt: new Date().toISOString()
  },
  {
    id: 'modern',
    name: 'Modern',
    description: 'Contemporary design with bold colors and typography',
    config: {
      theme: 'modern',
      primaryColor: '#3b82f6',
      fontFamily: 'inter',
      padding: 'lg',
      margin: 'md'
    },
    createdAt: new Date().toISOString()
  },
  {
    id: 'classic',
    name: 'Classic',
    description: 'Traditional design with serif fonts and conservative colors',
    config: {
      theme: 'classic',
      primaryColor: '#1f2937',
      fontFamily: 'lato',
      padding: 'md',
      margin: 'lg'
    },
    createdAt: new Date().toISOString()
  }
])

// Layout options
const layoutOptions = ref([
  {
    value: 'centered',
    label: 'Centered',
    description: 'Content centered on page',
    icon: 'squares-2x2'
  },
  {
    value: 'left-aligned',
    label: 'Left Aligned',
    description: 'Content aligned to left',
    icon: 'bars-3-bottom-left'
  },
  {
    value: 'right-aligned',
    label: 'Right Aligned',
    description: 'Content aligned to right',
    icon: 'bars-3-bottom-right'
  },
  {
    value: 'split',
    label: 'Split Layout',
    description: 'Content split into sections',
    icon: 'view-columns'
  }
])

// Debounced configuration update
const debouncedConfigUpdate = useDebounce((config: Record<string, any>) => {
  emit('config-updated', config)
  validateConfiguration()
}, 300)

// Computed properties
const containerClasses = computed(() => [
  'max-w-full mx-auto h-full flex flex-col',
  'bg-gray-50 dark:bg-gray-900'
])

const ariaLabel = computed(() => 
  `Component configurator for ${props.component.name}`
)

const contentFields = computed((): ConfigurationField[] => {
  // Generate dynamic fields based on component type and schema
  const baseFields: ConfigurationField[] = []
  
  // Add component-specific fields based on category
  switch (props.component.category) {
    case 'hero':
      baseFields.push(
        {
          name: 'headline',
          label: 'Headline',
          type: 'text',
          required: true,
          placeholder: 'Enter compelling headline',
          helpText: 'Main heading that captures attention',
          maxLength: 100
        },
        {
          name: 'subheading',
          label: 'Subheading',
          type: 'text',
          placeholder: 'Supporting subheading',
          helpText: 'Secondary text that supports the headline',
          maxLength: 150
        },
        {
          name: 'description',
          label: 'Description',
          type: 'textarea',
          rows: 3,
          placeholder: 'Detailed description',
          helpText: 'Longer description text',
          maxLength: 500
        },
        {
          name: 'audienceType',
          label: 'Audience Type',
          type: 'select',
          required: true,
          options: [
            { label: 'Individual Alumni', value: 'individual' },
            { label: 'Institution', value: 'institution' },
            { label: 'Employer', value: 'employer' }
          ],
          helpText: 'Target audience for this component'
        }
      )
      break
      
    case 'forms':
      baseFields.push(
        {
          name: 'title',
          label: 'Form Title',
          type: 'text',
          placeholder: 'Enter form title',
          helpText: 'Title displayed above the form'
        },
        {
          name: 'submitButtonText',
          label: 'Submit Button Text',
          type: 'text',
          required: true,
          placeholder: 'Submit',
          helpText: 'Text displayed on the submit button'
        },
        {
          name: 'enableValidation',
          label: 'Enable Validation',
          type: 'checkbox',
          helpText: 'Enable client-side form validation'
        }
      )
      break
      
    case 'testimonials':
      baseFields.push(
        {
          name: 'title',
          label: 'Section Title',
          type: 'text',
          placeholder: 'What our alumni say',
          helpText: 'Title for the testimonials section'
        },
        {
          name: 'layout',
          label: 'Layout Style',
          type: 'select',
          required: true,
          options: [
            { label: 'Single Quote', value: 'single' },
            { label: 'Carousel', value: 'carousel' },
            { label: 'Grid', value: 'grid' }
          ],
          helpText: 'How testimonials should be displayed'
        },
        {
          name: 'showAuthorPhoto',
          label: 'Show Author Photos',
          type: 'checkbox',
          helpText: 'Display author profile photos'
        }
      )
      break
      
    default:
      baseFields.push(
        {
          name: 'title',
          label: 'Title',
          type: 'text',
          placeholder: 'Component title',
          helpText: 'Main title for this component'
        }
      )
  }
  
  return baseFields
})

const hasChanges = computed(() => {
  return JSON.stringify(configuration.value) !== JSON.stringify(originalConfiguration.value)
})

const canUndo = computed(() => {
  return historyIndex.value > 0
})

const canRedo = computed(() => {
  return historyIndex.value < configurationHistory.value.length - 1
})

const modifiedFieldsCount = computed(() => {
  let count = 0
  for (const key in configuration.value) {
    if (configuration.value[key] !== originalConfiguration.value[key]) {
      count++
    }
  }
  return count
})

const formatLastModified = computed(() => {
  const now = new Date()
  const diff = now.getTime() - lastModified.value.getTime()
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  
  const days = Math.floor(hours / 24)
  return `${days}d ago`
})

const currentAnnouncement = computed(() => 
  announcements.value[announcements.value.length - 1] || ''
)

// Methods
const getCategoryIcon = (category: ComponentCategory): string => {
  const icons = {
    hero: 'star',
    forms: 'document-text',
    testimonials: 'chat-bubble-left-right',
    statistics: 'chart-bar',
    ctas: 'cursor-arrow-rays',
    media: 'photo'
  }
  return icons[category] || 'square-3-stack-3d'
}

const getTabClasses = (tabId: string) => [
  'flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors',
  activeTab.value === tabId
    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-gray-700/50'
]

const getLayoutButtonClasses = (layoutValue: string) => [
  configuration.value.layout === layoutValue
    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300'
    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300'
]

const setActiveTab = (tabId: string) => {
  activeTab.value = tabId
  
  trackEvent('configurator_tab_changed', {
    component_id: props.component.id,
    tab: tabId
  })
}

const handleFieldChange = (fieldName: string, value: any) => {
  // Add to history before making changes
  addToHistory()
  
  configuration.value[fieldName] = value
  lastModified.value = new Date()
  
  // Debounced update
  debouncedConfigUpdate(configuration.value)
  
  trackEvent('configurator_field_changed', {
    component_id: props.component.id,
    field: fieldName,
    value: typeof value === 'string' ? value.substring(0, 100) : value
  })
}

const getFieldError = (fieldName: string): string | null => {
  const error = validationErrors.value.find(e => e.field === fieldName)
  return error ? error.message : null
}

const validateConfiguration = () => {
  const errors: ValidationError[] = []
  
  // Validate required fields
  contentFields.value.forEach(field => {
    if (field.required && !configuration.value[field.name]) {
      errors.push({
        field: field.name,
        message: `${field.label} is required`
      })
    }
    
    // Validate max length
    if (field.maxLength && configuration.value[field.name] && 
        configuration.value[field.name].length > field.maxLength) {
      errors.push({
        field: field.name,
        message: `${field.label} must be ${field.maxLength} characters or less`
      })
    }
  })
  
  // Validate color format
  if (configuration.value.primaryColor && 
      !/^#[0-9A-F]{6}$/i.test(configuration.value.primaryColor)) {
    errors.push({
      field: 'primaryColor',
      message: 'Primary color must be a valid hex color'
    })
  }
  
  validationErrors.value = errors
}

const addToHistory = () => {
  // Remove any history after current index
  configurationHistory.value = configurationHistory.value.slice(0, historyIndex.value + 1)
  
  // Add current state to history
  configurationHistory.value.push({ ...configuration.value })
  historyIndex.value = configurationHistory.value.length - 1
  
  // Limit history size
  if (configurationHistory.value.length > 50) {
    configurationHistory.value.shift()
    historyIndex.value--
  }
}

const undo = () => {
  if (canUndo.value) {
    historyIndex.value--
    configuration.value = { ...configurationHistory.value[historyIndex.value] }
    lastModified.value = new Date()
    debouncedConfigUpdate(configuration.value)
    
    announceToScreenReader('Configuration change undone')
    
    trackEvent('configurator_undo', {
      component_id: props.component.id
    })
  }
}

const redo = () => {
  if (canRedo.value) {
    historyIndex.value++
    configuration.value = { ...configurationHistory.value[historyIndex.value] }
    lastModified.value = new Date()
    debouncedConfigUpdate(configuration.value)
    
    announceToScreenReader('Configuration change redone')
    
    trackEvent('configurator_redo', {
      component_id: props.component.id
    })
  }
}

const resetConfiguration = () => {
  if (confirm('Are you sure you want to reset all configuration changes?')) {
    addToHistory()
    configuration.value = { ...originalConfiguration.value }
    lastModified.value = new Date()
    debouncedConfigUpdate(configuration.value)
    
    announceToScreenReader('Configuration reset to defaults')
    
    trackEvent('configurator_reset', {
      component_id: props.component.id
    })
  }
}

const saveConfiguration = () => {
  if (validationErrors.value.length > 0) {
    announceToScreenReader(`Cannot save: ${validationErrors.value.length} validation errors`)
    return
  }
  
  emit('config-saved', configuration.value)
  originalConfiguration.value = { ...configuration.value }
  
  announceToScreenReader('Configuration saved successfully')
  
  trackEvent('configurator_saved', {
    component_id: props.component.id,
    fields_modified: modifiedFieldsCount.value
  })
}

const applyPreset = (preset: ConfigurationPreset) => {
  if (hasChanges.value && !confirm('Applying a preset will overwrite your current changes. Continue?')) {
    return
  }
  
  addToHistory()
  configuration.value = { ...configuration.value, ...preset.config }
  lastModified.value = new Date()
  debouncedConfigUpdate(configuration.value)
  
  announceToScreenReader(`Applied ${preset.name} preset`)
  
  emit('preset-applied', preset)
  
  trackEvent('configurator_preset_applied', {
    component_id: props.component.id,
    preset_id: preset.id,
    preset_name: preset.name
  })
}

const saveAsPreset = () => {
  showSavePresetModal.value = true
  newPresetName.value = ''
  newPresetDescription.value = ''
}

const closeSavePresetModal = () => {
  showSavePresetModal.value = false
  newPresetName.value = ''
  newPresetDescription.value = ''
}

const confirmSavePreset = () => {
  if (!newPresetName.value.trim()) return
  
  const newPreset: ConfigurationPreset = {
    id: `custom-${Date.now()}`,
    name: newPresetName.value.trim(),
    description: newPresetDescription.value.trim() || 'Custom configuration preset',
    config: { ...configuration.value },
    createdAt: new Date().toISOString()
  }
  
  configurationPresets.value.push(newPreset)
  
  announceToScreenReader(`Preset "${newPreset.name}" saved successfully`)
  
  trackEvent('configurator_preset_saved', {
    component_id: props.component.id,
    preset_name: newPreset.name
  })
  
  closeSavePresetModal()
}

const exportConfiguration = () => {
  const exportData = {
    component: {
      id: props.component.id,
      name: props.component.name,
      category: props.component.category
    },
    configuration: configuration.value,
    exportedAt: new Date().toISOString(),
    version: '1.0'
  }
  
  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.component.name.toLowerCase().replace(/\s+/g, '-')}-config.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  
  announceToScreenReader('Configuration exported successfully')
  
  trackEvent('configurator_exported', {
    component_id: props.component.id
  })
}

const importConfiguration = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const importData = JSON.parse(e.target?.result as string)
      
      if (!importData.configuration) {
        throw new Error('Invalid configuration file format')
      }
      
      if (hasChanges.value && !confirm('Importing will overwrite your current changes. Continue?')) {
        return
      }
      
      addToHistory()
      configuration.value = { ...configuration.value, ...importData.configuration }
      lastModified.value = new Date()
      debouncedConfigUpdate(configuration.value)
      
      announceToScreenReader('Configuration imported successfully')
      
      trackEvent('configurator_imported', {
        component_id: props.component.id
      })
      
    } catch (error) {
      console.error('Failed to import configuration:', error)
      announceToScreenReader('Failed to import configuration file')
    }
  }
  
  reader.readAsText(file)
  
  // Reset file input
  if (importFileInput.value) {
    importFileInput.value.value = ''
  }
}

const announceToScreenReader = (message: string) => {
  announcements.value.push(message)
  
  // Remove announcement after 3 seconds
  setTimeout(() => {
    const index = announcements.value.indexOf(message)
    if (index > -1) {
      announcements.value.splice(index, 1)
    }
  }, 3000)
}

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'z':
        event.preventDefault()
        if (event.shiftKey) {
          redo()
        } else {
          undo()
        }
        break
      case 'y':
        event.preventDefault()
        redo()
        break
      case 's':
        event.preventDefault()
        saveConfiguration()
        break
    }
  }
}

// Lifecycle
onMounted(() => {
  // Initialize configuration
  configuration.value = {
    // Default values
    theme: 'default',
    primaryColor: '#6366f1',
    fontFamily: 'system',
    padding: 'md',
    margin: 'md',
    layout: 'centered',
    mobileOptimized: true,
    tabletOptimized: true,
    animationsEnabled: true,
    animationDuration: 'normal',
    customCSS: '',
    // Merge with initial config and component config
    ...props.component.config,
    ...props.initialConfig
  }
  
  originalConfiguration.value = { ...configuration.value }
  
  // Initialize history
  configurationHistory.value = [{ ...configuration.value }]
  historyIndex.value = 0
  
  // Add keyboard event listeners
  document.addEventListener('keydown', handleKeydown)
  
  // Initial validation
  validateConfiguration()
  
  trackEvent('configurator_opened', {
    component_id: props.component.id,
    component_category: props.component.category
  })
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

// Watch for external config changes
watch(() => props.initialConfig, (newConfig) => {
  if (newConfig && Object.keys(newConfig).length > 0) {
    configuration.value = { ...configuration.value, ...newConfig }
    debouncedConfigUpdate(configuration.value)
  }
}, { deep: true })
</script>

<style scoped>
.component-configurator {
  container-type: inline-size;
}

.component-configurator__header {
  @apply bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4;
}

.component-configurator__content {
  @apply flex-1 overflow-hidden;
}

.component-configurator__sidebar {
  @apply flex-shrink-0;
}

.form-field {
  @apply relative;
}

/* Focus styles for better accessibility */
.component-configurator input:focus,
.component-configurator textarea:focus,
.component-configurator select:focus,
.component-configurator button:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .component-configurator {
    @apply contrast-125;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-configurator *,
  .component-configurator *::before,
  .component-configurator *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Container queries for responsive design */
@container (max-width: 768px) {
  .component-configurator__content {
    @apply flex-col;
  }
  
  .component-configurator__sidebar {
    @apply w-full border-l-0 border-t border-gray-200 dark:border-gray-700;
  }
}

/* Custom scrollbar for sidebar */
.component-configurator__sidebar {
  scrollbar-width: thin;
  scrollbar-color: rgb(156 163 175) transparent;
}

.component-configurator__sidebar::-webkit-scrollbar {
  width: 6px;
}

.component-configurator__sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.component-configurator__sidebar::-webkit-scrollbar-thumb {
  background-color: rgb(156 163 175);
  border-radius: 3px;
}

.component-configurator__sidebar::-webkit-scrollbar-thumb:hover {
  background-color: rgb(107 114 128);
}

/* Dark mode scrollbar */
.dark .component-configurator__sidebar {
  scrollbar-color: rgb(75 85 99) transparent;
}

.dark .component-configurator__sidebar::-webkit-scrollbar-thumb {
  background-color: rgb(75 85 99);
}

.dark .component-configurator__sidebar::-webkit-scrollbar-thumb:hover {
  background-color: rgb(55 65 81);
}
</style>