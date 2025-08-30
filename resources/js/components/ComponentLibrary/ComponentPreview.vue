<template>
  <div 
    class="component-preview"
    :class="containerClasses"
    role="application"
    :aria-label="ariaLabel"
  >
    <!-- Preview Header -->
    <header class="component-preview__header">
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
                {{ component.name }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ getCategoryName(component.category) }} Component Preview
              </p>
            </div>
          </div>
        </div>
        
        <!-- Preview Controls -->
        <div class="flex items-center space-x-4">
          <!-- Device Preview Toggle -->
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-700 dark:text-gray-300">Device:</span>
            <div class="flex rounded-md shadow-sm" role="group" aria-label="Device preview options">
              <button
                v-for="device in devices"
                :key="device.id"
                @click="setPreviewDevice(device.id)"
                :class="getDeviceButtonClasses(device.id)"
                :aria-pressed="previewDevice === device.id"
                :aria-label="`Preview on ${device.name}`"
              >
                <Icon :name="device.icon" class="h-4 w-4" />
                <span class="ml-1 hidden sm:inline">{{ device.name }}</span>
              </button>
            </div>
          </div>
          
          <!-- Zoom Controls -->
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-700 dark:text-gray-300">Zoom:</span>
            <div class="flex items-center space-x-1">
              <button
                @click="decreaseZoom"
                :disabled="zoomLevel <= 0.25"
                class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Zoom out"
              >
                <Icon name="minus" class="h-4 w-4" />
              </button>
              <span class="text-sm text-gray-600 dark:text-gray-400 min-w-[3rem] text-center">
                {{ Math.round(zoomLevel * 100) }}%
              </span>
              <button
                @click="increaseZoom"
                :disabled="zoomLevel >= 2"
                class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Zoom in"
              >
                <Icon name="plus" class="h-4 w-4" />
              </button>
            </div>
          </div>
          
          <!-- Share Preview -->
          <button
            @click="sharePreview"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            aria-label="Share preview"
          >
            <Icon name="share" class="h-4 w-4 mr-2" />
            Share
          </button>
        </div>
      </div>
    </header>

    <!-- Preview Content Area -->
    <div class="component-preview__content flex flex-1 overflow-hidden">
      <!-- Main Preview Area -->
      <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Preview Toolbar -->
        <div class="preview-toolbar flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center space-x-4">
            <!-- Refresh Preview -->
            <button
              @click="refreshPreview"
              :disabled="isRefreshing"
              class="inline-flex items-center px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50"
              aria-label="Refresh preview"
            >
              <Icon 
                :name="isRefreshing ? 'arrow-path' : 'arrow-path'" 
                :class="['h-4 w-4 mr-1', { 'animate-spin': isRefreshing }]" 
              />
              Refresh
            </button>
            
            <!-- Accessibility Test -->
            <button
              @click="runAccessibilityTest"
              :disabled="isRunningA11yTest"
              class="inline-flex items-center px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50"
              aria-label="Run accessibility test"
            >
              <Icon 
                :name="isRunningA11yTest ? 'arrow-path' : 'shield-check'" 
                :class="['h-4 w-4 mr-1', { 'animate-spin': isRunningA11yTest }]" 
              />
              A11y Test
            </button>
            
            <!-- Performance Test -->
            <button
              @click="runPerformanceTest"
              :disabled="isRunningPerfTest"
              class="inline-flex items-center px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50"
              aria-label="Run performance test"
            >
              <Icon 
                :name="isRunningPerfTest ? 'arrow-path' : 'bolt'" 
                :class="['h-4 w-4 mr-1', { 'animate-spin': isRunningPerfTest }]" 
              />
              Performance
            </button>
          </div>
          
          <!-- Preview URL -->
          <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">Preview URL:</span>
            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
              {{ previewUrl }}
            </code>
            <button
              @click="copyPreviewUrl"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              aria-label="Copy preview URL"
            >
              <Icon name="clipboard" class="h-4 w-4" />
            </button>
          </div>
        </div>
        
        <!-- Preview Frame Container -->
        <div class="preview-frame-container flex-1 flex items-center justify-center p-6 bg-gray-100 dark:bg-gray-900 overflow-auto">
          <div 
            :class="getPreviewFrameClasses()"
            :style="getPreviewFrameStyles()"
            class="preview-frame bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-all duration-300"
          >
            <!-- Device Frame (for mobile/tablet) -->
            <div 
              v-if="previewDevice !== 'desktop'"
              class="device-frame"
              :class="getDeviceFrameClasses()"
            >
              <!-- Device Header -->
              <div class="device-header flex items-center justify-center p-2 bg-gray-800">
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                  <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                </div>
              </div>
              
              <!-- Device Screen -->
              <div class="device-screen flex-1 overflow-hidden">
                <ComponentPreviewFrame
                  :component="component"
                  :config="previewConfig"
                  :sample-data="sampleDataConfig"
                  :device="previewDevice"
                  :zoom="zoomLevel"
                  :key="previewKey"
                  @config-updated="handleConfigUpdate"
                  @error="handlePreviewError"
                  @loaded="handlePreviewLoaded"
                />
              </div>
            </div>
            
            <!-- Desktop Frame -->
            <ComponentPreviewFrame
              v-else
              :component="component"
              :config="previewConfig"
              :sample-data="sampleDataConfig"
              :device="previewDevice"
              :zoom="zoomLevel"
              :key="previewKey"
              @config-updated="handleConfigUpdate"
              @error="handlePreviewError"
              @loaded="handlePreviewLoaded"
            />
          </div>
        </div>
        
        <!-- Preview Status Bar -->
        <div class="preview-status-bar flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-sm">
          <div class="flex items-center space-x-4">
            <!-- Loading Status -->
            <div v-if="isLoading" class="flex items-center text-gray-600 dark:text-gray-400">
              <Icon name="arrow-path" class="h-4 w-4 mr-2 animate-spin" />
              Loading preview...
            </div>
            
            <!-- Error Status -->
            <div v-else-if="previewError" class="flex items-center text-red-600 dark:text-red-400">
              <Icon name="exclamation-triangle" class="h-4 w-4 mr-2" />
              {{ previewError }}
            </div>
            
            <!-- Success Status -->
            <div v-else class="flex items-center text-green-600 dark:text-green-400">
              <Icon name="check-circle" class="h-4 w-4 mr-2" />
              Preview loaded successfully
            </div>
            
            <!-- Performance Metrics -->
            <div v-if="performanceMetrics" class="flex items-center space-x-3 text-gray-500 dark:text-gray-400">
              <span>Load: {{ performanceMetrics.loadTime }}ms</span>
              <span>Size: {{ formatFileSize(performanceMetrics.bundleSize) }}</span>
            </div>
          </div>
          
          <!-- Accessibility Score -->
          <div v-if="accessibilityScore" class="flex items-center space-x-2">
            <Icon 
              :name="getAccessibilityIcon(accessibilityScore.score)" 
              :class="getAccessibilityIconClasses(accessibilityScore.score)"
              class="h-4 w-4"
            />
            <span :class="getAccessibilityScoreClasses(accessibilityScore.score)">
              A11y: {{ accessibilityScore.score }}/100
            </span>
          </div>
        </div>
      </main>
      
      <!-- Configuration Sidebar -->
      <aside class="component-preview__sidebar w-80 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-auto">
        <div class="p-6">
          <!-- Configuration Tabs -->
          <div class="mb-6">
            <nav class="flex space-x-1" role="tablist">
              <button
                v-for="tab in configTabs"
                :key="tab.id"
                @click="setActiveConfigTab(tab.id)"
                :class="getConfigTabClasses(tab.id)"
                :aria-selected="activeConfigTab === tab.id"
                :aria-controls="`config-panel-${tab.id}`"
                role="tab"
              >
                <Icon :name="tab.icon" class="h-4 w-4 mr-2" />
                {{ tab.name }}
              </button>
            </nav>
          </div>
          
          <!-- Configuration Panels -->
          <div class="config-panels">
            <!-- Sample Data Panel -->
            <div
              v-show="activeConfigTab === 'data'"
              id="config-panel-data"
              role="tabpanel"
              aria-labelledby="tab-data"
            >
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Sample Data Configuration
              </h3>
              
              <!-- Sample Data Toggle -->
              <div class="flex items-center justify-between mb-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Use Sample Data
                </label>
                <button
                  @click="toggleSampleData"
                  :class="getSampleDataToggleClasses()"
                  role="switch"
                  :aria-checked="useSampleData"
                  aria-label="Toggle sample data"
                >
                  <span 
                    :class="getSampleDataToggleThumbClasses()"
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  ></span>
                </button>
              </div>
              
              <!-- Sample Data Options -->
              <div v-if="useSampleData" class="space-y-4">
                <!-- Audience Type -->
                <div v-if="component.category === 'hero' || component.category === 'testimonials'">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Audience Type
                  </label>
                  <select
                    v-model="sampleDataConfig.audienceType"
                    @change="updatePreview"
                    class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="individual">Individual Alumni</option>
                    <option value="institution">Institution</option>
                    <option value="employer">Employer</option>
                  </select>
                </div>
                
                <!-- Data Variation -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Data Variation
                  </label>
                  <select
                    v-model="sampleDataConfig.variation"
                    @change="updatePreview"
                    class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="default">Default</option>
                    <option value="minimal">Minimal</option>
                    <option value="rich">Rich Content</option>
                    <option value="localized">Localized</option>
                  </select>
                </div>
                
                <!-- Content Length -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Content Length
                  </label>
                  <select
                    v-model="sampleDataConfig.contentLength"
                    @change="updatePreview"
                    class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="short">Short</option>
                    <option value="medium">Medium</option>
                    <option value="long">Long</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Theme Panel -->
            <div
              v-show="activeConfigTab === 'theme'"
              id="config-panel-theme"
              role="tabpanel"
              aria-labelledby="tab-theme"
            >
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Theme Configuration
              </h3>
              
              <!-- Theme Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Theme
                </label>
                <select
                  v-model="previewConfig.theme"
                  @change="updatePreview"
                  class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="default">Default</option>
                  <option value="minimal">Minimal</option>
                  <option value="modern">Modern</option>
                  <option value="classic">Classic</option>
                </select>
              </div>
              
              <!-- Color Scheme Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Color Scheme
                </label>
                <select
                  v-model="previewConfig.colorScheme"
                  @change="updatePreview"
                  class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="default">Default</option>
                  <option value="primary">Primary</option>
                  <option value="secondary">Secondary</option>
                  <option value="accent">Accent</option>
                </select>
              </div>
              
              <!-- Dark Mode Toggle -->
              <div class="flex items-center justify-between mb-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Dark Mode
                </label>
                <button
                  @click="toggleDarkMode"
                  :class="getDarkModeToggleClasses()"
                  role="switch"
                  :aria-checked="isDarkMode"
                  aria-label="Toggle dark mode"
                >
                  <span 
                    :class="getDarkModeToggleThumbClasses()"
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  ></span>
                </button>
              </div>
            </div>
            
            <!-- Settings Panel -->
            <div
              v-show="activeConfigTab === 'settings'"
              id="config-panel-settings"
              role="tabpanel"
              aria-labelledby="tab-settings"
            >
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Preview Settings
              </h3>
              
              <!-- Auto Refresh -->
              <div class="flex items-center justify-between mb-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Auto Refresh
                </label>
                <button
                  @click="toggleAutoRefresh"
                  :class="getAutoRefreshToggleClasses()"
                  role="switch"
                  :aria-checked="autoRefresh"
                  aria-label="Toggle auto refresh"
                >
                  <span 
                    :class="getAutoRefreshToggleThumbClasses()"
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  ></span>
                </button>
              </div>
              
              <!-- Show Grid -->
              <div class="flex items-center justify-between mb-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Show Grid
                </label>
                <button
                  @click="toggleGrid"
                  :class="getGridToggleClasses()"
                  role="switch"
                  :aria-checked="showGrid"
                  aria-label="Toggle grid overlay"
                >
                  <span 
                    :class="getGridToggleThumbClasses()"
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  ></span>
                </button>
              </div>
              
              <!-- Show Rulers -->
              <div class="flex items-center justify-between mb-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Show Rulers
                </label>
                <button
                  @click="toggleRulers"
                  :class="getRulersToggleClasses()"
                  role="switch"
                  :aria-checked="showRulers"
                  aria-label="Toggle rulers"
                >
                  <span 
                    :class="getRulersToggleThumbClasses()"
                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  ></span>
                </button>
              </div>
            </div>
            
            <!-- Test Results Panel -->
            <div
              v-show="activeConfigTab === 'tests'"
              id="config-panel-tests"
              role="tabpanel"
              aria-labelledby="tab-tests"
            >
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Test Results
              </h3>
              
              <!-- Accessibility Test Results -->
              <div v-if="accessibilityScore" class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Accessibility Score
                </h4>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-lg font-semibold" :class="getAccessibilityScoreClasses(accessibilityScore.score)">
                      {{ accessibilityScore.score }}/100
                    </span>
                    <Icon 
                      :name="getAccessibilityIcon(accessibilityScore.score)" 
                      :class="getAccessibilityIconClasses(accessibilityScore.score)"
                      class="h-5 w-5"
                    />
                  </div>
                  
                  <!-- Issues List -->
                  <div v-if="accessibilityScore.issues.length > 0" class="space-y-2">
                    <h5 class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                      Issues Found
                    </h5>
                    <ul class="space-y-1">
                      <li 
                        v-for="issue in accessibilityScore.issues" 
                        :key="issue.id"
                        class="text-sm text-gray-600 dark:text-gray-400 flex items-start space-x-2"
                      >
                        <Icon 
                          :name="getIssueIcon(issue.severity)" 
                          :class="getIssueIconClasses(issue.severity)"
                          class="h-4 w-4 mt-0.5 flex-shrink-0"
                        />
                        <span>{{ issue.description }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              
              <!-- Performance Test Results -->
              <div v-if="performanceMetrics" class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Performance Metrics
                </h4>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Load Time</span>
                    <span class="text-sm font-medium">{{ performanceMetrics.loadTime }}ms</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bundle Size</span>
                    <span class="text-sm font-medium">{{ formatFileSize(performanceMetrics.bundleSize) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">First Paint</span>
                    <span class="text-sm font-medium">{{ performanceMetrics.firstPaint }}ms</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">LCP</span>
                    <span class="text-sm font-medium">{{ performanceMetrics.lcp }}ms</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="mt-8 space-y-3">
            <button
              @click="handleSelect"
              class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Icon name="plus" class="h-4 w-4 mr-2" />
              Add to Page
            </button>
            
            <button
              @click="exportPreview"
              class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Icon name="arrow-down-tray" class="h-4 w-4 mr-2" />
              Export Preview
            </button>
          </div>
        </div>
      </aside>
    </div>
    
    <!-- Share Modal -->
    <SharePreviewModal
      v-if="showShareModal"
      :preview-url="shareableUrl"
      :component="component"
      @close="closeShareModal"
    />
    
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
import type { Component, ComponentCategory, AudienceType } from '@/types/components'
import { useDebounce } from '@/composables/useDebounce'
import { useAnalytics } from '@/composables/useAnalytics'

// Import child components
import Icon from '@/components/Common/Icon.vue'
import ComponentPreviewFrame from './ComponentPreviewFrame.vue'
import SharePreviewModal from './SharePreviewModal.vue'

interface SampleDataConfig {
  audienceType: AudienceType
  variation: 'default' | 'minimal' | 'rich' | 'localized'
  contentLength: 'short' | 'medium' | 'long'
}

interface AccessibilityIssue {
  id: string
  severity: 'error' | 'warning' | 'info'
  description: string
  element?: string
  rule?: string
}

interface AccessibilityScore {
  score: number
  issues: AccessibilityIssue[]
  timestamp: string
}

interface PerformanceMetrics {
  loadTime: number
  bundleSize: number
  firstPaint: number
  lcp: number
  cls: number
  fid: number
}

interface Props {
  component: Component
  initialConfig?: Record<string, any>
  autoRefresh?: boolean
  showGrid?: boolean
  showRulers?: boolean
}

interface Emits {
  (e: 'config-updated', config: Record<string, any>): void
  (e: 'select', component: Component): void
  (e: 'share', url: string): void
}

const props = withDefaults(defineProps<Props>(), {
  initialConfig: () => ({}),
  autoRefresh: false,
  showGrid: false,
  showRulers: false
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent } = useAnalytics()

// Reactive state
const previewDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
const zoomLevel = ref(1)
const useSampleData = ref(true)
const isDarkMode = ref(false)
const autoRefresh = ref(props.autoRefresh)
const showGrid = ref(props.showGrid)
const showRulers = ref(props.showRulers)
const activeConfigTab = ref<'data' | 'theme' | 'settings' | 'tests'>('data')

// Preview state
const isLoading = ref(false)
const isRefreshing = ref(false)
const isRunningA11yTest = ref(false)
const isRunningPerfTest = ref(false)
const previewError = ref<string | null>(null)
const previewKey = ref(0)

// Test results
const accessibilityScore = ref<AccessibilityScore | null>(null)
const performanceMetrics = ref<PerformanceMetrics | null>(null)

// Share modal
const showShareModal = ref(false)

// Announcements for screen readers
const announcements = ref<string[]>([])

// Configuration
const previewConfig = ref({
  ...props.component.config,
  ...props.initialConfig,
  theme: 'default',
  colorScheme: 'default'
})

const sampleDataConfig = ref<SampleDataConfig>({
  audienceType: 'individual',
  variation: 'default',
  contentLength: 'medium'
})

// Device configurations
const devices = [
  { id: 'desktop' as const, name: 'Desktop', icon: 'computer-desktop', width: '100%', height: 'auto' },
  { id: 'tablet' as const, name: 'Tablet', icon: 'device-tablet', width: '768px', height: '1024px' },
  { id: 'mobile' as const, name: 'Mobile', icon: 'device-phone-mobile', width: '375px', height: '667px' }
]

// Configuration tabs
const configTabs = [
  { id: 'data' as const, name: 'Data', icon: 'database' },
  { id: 'theme' as const, name: 'Theme', icon: 'swatch' },
  { id: 'settings' as const, name: 'Settings', icon: 'cog-6-tooth' },
  { id: 'tests' as const, name: 'Tests', icon: 'beaker' }
]

// Debounced update function
const debouncedUpdate = useDebounce(() => {
  if (autoRefresh.value) {
    updatePreview()
  }
}, 500)

// Computed properties
const containerClasses = computed(() => [
  'component-preview h-full flex flex-col',
  {
    'dark': isDarkMode.value
  }
])

const ariaLabel = computed(() => 
  `Component preview for ${props.component.name} - ${getCategoryName(props.component.category)} component`
)

const previewUrl = computed(() => {
  const baseUrl = window.location.origin
  const params = new URLSearchParams({
    component: props.component.id,
    device: previewDevice.value,
    zoom: zoomLevel.value.toString(),
    theme: previewConfig.value.theme || 'default',
    colorScheme: previewConfig.value.colorScheme || 'default',
    sampleData: useSampleData.value.toString()
  })
  return `${baseUrl}/preview?${params.toString()}`
})

const shareableUrl = computed(() => {
  const params = new URLSearchParams({
    component: props.component.id,
    config: btoa(JSON.stringify(previewConfig.value)),
    sampleData: btoa(JSON.stringify(sampleDataConfig.value))
  })
  return `${window.location.origin}/shared-preview?${params.toString()}`
})

const currentAnnouncement = computed(() => 
  announcements.value[announcements.value.length - 1] || ''
)

// Methods
const getCategoryIcon = (category: ComponentCategory): string => {
  const iconMap: Record<ComponentCategory, string> = {
    hero: 'star',
    forms: 'document-text',
    testimonials: 'chat-bubble-left-right',
    statistics: 'chart-bar',
    ctas: 'cursor-arrow-rays',
    media: 'photo'
  }
  return iconMap[category] || 'square-3-stack-3d'
}

const getCategoryName = (category: ComponentCategory): string => {
  const nameMap: Record<ComponentCategory, string> = {
    hero: 'Hero Section',
    forms: 'Form',
    testimonials: 'Testimonial',
    statistics: 'Statistics',
    ctas: 'Call to Action',
    media: 'Media'
  }
  return nameMap[category] || category
}

const getDeviceButtonClasses = (deviceId: string) => [
  'px-3 py-2 text-sm font-medium border focus:outline-none focus:ring-1 focus:ring-indigo-500',
  deviceId === 'desktop' ? 'rounded-l-md' : deviceId === 'mobile' ? 'rounded-r-md -ml-px' : '-ml-px',
  previewDevice.value === deviceId
    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300'
    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
]

const getConfigTabClasses = (tabId: string) => [
  'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
  activeConfigTab.value === tabId
    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700'
]

const getPreviewFrameClasses = () => {
  const device = devices.find(d => d.id === previewDevice.value)
  if (!device) return 'w-full h-full'
  
  return {
    'w-full h-full': previewDevice.value === 'desktop',
    'w-[768px] h-[600px]': previewDevice.value === 'tablet',
    'w-[375px] h-[600px]': previewDevice.value === 'mobile'
  }
}

const getPreviewFrameStyles = () => {
  return {
    transform: `scale(${zoomLevel.value})`,
    transformOrigin: 'top left'
  }
}

const getDeviceFrameClasses = () => [
  'flex flex-col h-full',
  {
    'rounded-lg overflow-hidden': previewDevice.value !== 'desktop'
  }
]

// Toggle classes helpers
const getSampleDataToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  useSampleData.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getSampleDataToggleThumbClasses = () => [
  useSampleData.value ? 'translate-x-5' : 'translate-x-0'
]

const getDarkModeToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  isDarkMode.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getDarkModeToggleThumbClasses = () => [
  isDarkMode.value ? 'translate-x-5' : 'translate-x-0'
]

const getAutoRefreshToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  autoRefresh.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getAutoRefreshToggleThumbClasses = () => [
  autoRefresh.value ? 'translate-x-5' : 'translate-x-0'
]

const getGridToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  showGrid.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getGridToggleThumbClasses = () => [
  showGrid.value ? 'translate-x-5' : 'translate-x-0'
]

const getRulersToggleClasses = () => [
  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  showRulers.value ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'
]

const getRulersToggleThumbClasses = () => [
  showRulers.value ? 'translate-x-5' : 'translate-x-0'
]

// Accessibility and performance helpers
const getAccessibilityIcon = (score: number): string => {
  if (score >= 90) return 'shield-check'
  if (score >= 70) return 'shield-exclamation'
  return 'shield-x'
}

const getAccessibilityIconClasses = (score: number) => [
  score >= 90 ? 'text-green-500' : score >= 70 ? 'text-yellow-500' : 'text-red-500'
]

const getAccessibilityScoreClasses = (score: number) => [
  'text-sm font-medium',
  score >= 90 ? 'text-green-600 dark:text-green-400' : 
  score >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 
  'text-red-600 dark:text-red-400'
]

const getIssueIcon = (severity: string): string => {
  switch (severity) {
    case 'error': return 'x-circle'
    case 'warning': return 'exclamation-triangle'
    case 'info': return 'information-circle'
    default: return 'information-circle'
  }
}

const getIssueIconClasses = (severity: string) => [
  severity === 'error' ? 'text-red-500' :
  severity === 'warning' ? 'text-yellow-500' :
  'text-blue-500'
]

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Action methods
const setPreviewDevice = (deviceId: 'desktop' | 'tablet' | 'mobile') => {
  previewDevice.value = deviceId
  updatePreview()
  
  trackEvent('component_preview_device_changed', {
    component_id: props.component.id,
    device: deviceId
  })
  
  announceToScreenReader(`Preview device changed to ${deviceId}`)
}

const increaseZoom = () => {
  if (zoomLevel.value < 2) {
    zoomLevel.value = Math.min(2, zoomLevel.value + 0.25)
    announceToScreenReader(`Zoom level increased to ${Math.round(zoomLevel.value * 100)}%`)
  }
}

const decreaseZoom = () => {
  if (zoomLevel.value > 0.25) {
    zoomLevel.value = Math.max(0.25, zoomLevel.value - 0.25)
    announceToScreenReader(`Zoom level decreased to ${Math.round(zoomLevel.value * 100)}%`)
  }
}

const setActiveConfigTab = (tabId: 'data' | 'theme' | 'settings' | 'tests') => {
  activeConfigTab.value = tabId
  
  trackEvent('component_preview_tab_changed', {
    component_id: props.component.id,
    tab: tabId
  })
}

const toggleSampleData = () => {
  useSampleData.value = !useSampleData.value
  updatePreview()
  
  announceToScreenReader(`Sample data ${useSampleData.value ? 'enabled' : 'disabled'}`)
}

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  updatePreview()
  
  announceToScreenReader(`Dark mode ${isDarkMode.value ? 'enabled' : 'disabled'}`)
}

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value
  
  announceToScreenReader(`Auto refresh ${autoRefresh.value ? 'enabled' : 'disabled'}`)
}

const toggleGrid = () => {
  showGrid.value = !showGrid.value
  
  announceToScreenReader(`Grid overlay ${showGrid.value ? 'enabled' : 'disabled'}`)
}

const toggleRulers = () => {
  showRulers.value = !showRulers.value
  
  announceToScreenReader(`Rulers ${showRulers.value ? 'enabled' : 'disabled'}`)
}

const refreshPreview = async () => {
  isRefreshing.value = true
  previewKey.value++
  
  try {
    await nextTick()
    // Simulate refresh delay
    await new Promise(resolve => setTimeout(resolve, 500))
    
    announceToScreenReader('Preview refreshed successfully')
  } catch (error) {
    console.error('Failed to refresh preview:', error)
    announceToScreenReader('Failed to refresh preview')
  } finally {
    isRefreshing.value = false
  }
}

const updatePreview = () => {
  previewKey.value++
  emit('config-updated', previewConfig.value)
}

const runAccessibilityTest = async () => {
  isRunningA11yTest.value = true
  
  try {
    // Simulate accessibility test
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Mock accessibility results
    const mockScore = Math.floor(Math.random() * 40) + 60 // 60-100
    const mockIssues: AccessibilityIssue[] = []
    
    if (mockScore < 90) {
      mockIssues.push({
        id: 'missing-alt',
        severity: 'error',
        description: 'Image missing alt text',
        element: 'img',
        rule: 'image-alt'
      })
    }
    
    if (mockScore < 80) {
      mockIssues.push({
        id: 'low-contrast',
        severity: 'warning',
        description: 'Text has insufficient color contrast',
        element: 'p',
        rule: 'color-contrast'
      })
    }
    
    accessibilityScore.value = {
      score: mockScore,
      issues: mockIssues,
      timestamp: new Date().toISOString()
    }
    
    announceToScreenReader(`Accessibility test completed. Score: ${mockScore} out of 100`)
    
    // Switch to tests tab to show results
    setActiveConfigTab('tests')
    
  } catch (error) {
    console.error('Accessibility test failed:', error)
    announceToScreenReader('Accessibility test failed')
  } finally {
    isRunningA11yTest.value = false
  }
}

const runPerformanceTest = async () => {
  isRunningPerfTest.value = true
  
  try {
    // Simulate performance test
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // Mock performance metrics
    performanceMetrics.value = {
      loadTime: Math.floor(Math.random() * 500) + 200,
      bundleSize: Math.floor(Math.random() * 100000) + 50000,
      firstPaint: Math.floor(Math.random() * 300) + 100,
      lcp: Math.floor(Math.random() * 800) + 400,
      cls: Math.random() * 0.1,
      fid: Math.floor(Math.random() * 50) + 10
    }
    
    announceToScreenReader('Performance test completed')
    
    // Switch to tests tab to show results
    setActiveConfigTab('tests')
    
  } catch (error) {
    console.error('Performance test failed:', error)
    announceToScreenReader('Performance test failed')
  } finally {
    isRunningPerfTest.value = false
  }
}

const sharePreview = () => {
  showShareModal.value = true
  
  trackEvent('component_preview_share_opened', {
    component_id: props.component.id
  })
}

const closeShareModal = () => {
  showShareModal.value = false
}

const copyPreviewUrl = async () => {
  try {
    await navigator.clipboard.writeText(previewUrl.value)
    announceToScreenReader('Preview URL copied to clipboard')
  } catch (error) {
    console.error('Failed to copy URL:', error)
    announceToScreenReader('Failed to copy URL')
  }
}

const exportPreview = () => {
  const exportData = {
    component: props.component,
    config: previewConfig.value,
    sampleData: sampleDataConfig.value,
    device: previewDevice.value,
    zoom: zoomLevel.value,
    timestamp: new Date().toISOString()
  }
  
  const dataStr = JSON.stringify(exportData, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  
  const link = document.createElement('a')
  link.href = url
  link.download = `${props.component.name.toLowerCase().replace(/\s+/g, '-')}-preview.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  URL.revokeObjectURL(url)
  
  trackEvent('component_preview_exported', {
    component_id: props.component.id
  })
}

const handleSelect = () => {
  emit('select', props.component)
  
  trackEvent('component_selected_from_preview', {
    component_id: props.component.id,
    device: previewDevice.value
  })
}

const handleConfigUpdate = (config: Record<string, any>) => {
  previewConfig.value = { ...previewConfig.value, ...config }
  emit('config-updated', previewConfig.value)
}

const handlePreviewError = (error: string) => {
  previewError.value = error
  announceToScreenReader(`Preview error: ${error}`)
}

const handlePreviewLoaded = () => {
  previewError.value = null
  isLoading.value = false
  announceToScreenReader('Preview loaded successfully')
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

// Watch for config changes
watch(() => previewConfig.value, () => {
  debouncedUpdate()
}, { deep: true })

watch(() => sampleDataConfig.value, () => {
  if (useSampleData.value) {
    debouncedUpdate()
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  // Initialize preview
  updatePreview()
  
  // Track preview opened
  trackEvent('component_preview_opened', {
    component_id: props.component.id,
    component_category: props.component.category
  })
})

onUnmounted(() => {
  // Track preview closed
  trackEvent('component_preview_closed', {
    component_id: props.component.id,
    duration: Date.now() // You might want to track actual duration
  })
})
</script>

<style scoped>
.component-preview {
  container-type: inline-size;
}

.component-preview__header {
  @apply border-b border-gray-200 dark:border-gray-700 pb-6;
}

.component-preview__content {
  @apply flex-1;
}

.component-preview__sidebar {
  @apply flex-shrink-0;
}

.preview-frame-container {
  @apply relative;
}

.preview-frame {
  @apply relative;
}

.device-frame {
  @apply bg-gray-800 rounded-lg p-2;
}

.device-header {
  @apply rounded-t-md;
}

.device-screen {
  @apply bg-white rounded-b-md overflow-hidden;
}

/* Grid overlay */
.preview-frame.show-grid::before {
  content: '';
  @apply absolute inset-0 pointer-events-none z-10;
  background-image: 
    linear-gradient(rgba(0, 0, 0, 0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 0, 0, 0.1) 1px, transparent 1px);
  background-size: 20px 20px;
}

/* Rulers */
.preview-frame.show-rulers::after {
  content: '';
  @apply absolute top-0 left-0 right-0 h-4 pointer-events-none z-10;
  background: linear-gradient(90deg, 
    transparent 0px, 
    rgba(0, 0, 0, 0.2) 1px, 
    transparent 1px
  );
  background-size: 10px 100%;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .component-preview {
    @apply contrast-125;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-preview *,
  .component-preview *::before,
  .component-preview *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Container queries for responsive design */
@container (max-width: 1024px) {
  .component-preview__content {
    @apply flex-col;
  }
  
  .component-preview__sidebar {
    @apply w-full border-l-0 border-t border-gray-200 dark:border-gray-700;
  }
}

@container (max-width: 640px) {
  .component-preview__header {
    @apply space-y-4;
  }
  
  .preview-toolbar {
    @apply flex-col space-y-2;
  }
}
</style>