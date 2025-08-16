<template>
  <section class="admin-dashboard-preview">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 mb-4">
          Powerful Admin Dashboard
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Comprehensive analytics and management tools designed for institutional administrators 
          to drive alumni engagement and measure success.
        </p>
      </div>

      <!-- Dashboard Preview -->
      <div class="dashboard-mockup mb-12">
        <div class="browser-frame">
          <div class="browser-header">
            <div class="browser-dots">
              <span class="dot red"></span>
              <span class="dot yellow"></span>
              <span class="dot green"></span>
            </div>
            <div class="browser-url">
              <span class="text-gray-500">{{ institutionName }}.alumni-platform.com/admin</span>
            </div>
          </div>
          
          <div class="dashboard-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
              <div class="flex justify-between items-center mb-6">
                <div>
                  <h3 class="text-2xl font-bold text-gray-900">Alumni Engagement Dashboard</h3>
                  <p class="text-gray-600">{{ currentPeriod }}</p>
                </div>
                <button 
                  @click="requestDemo"
                  class="demo-cta-button"
                  :aria-label="`Request demo for ${institutionName}`"
                >
                  Request Demo
                </button>
              </div>
            </div>

            <!-- Key Metrics Grid -->
            <div class="metrics-grid">
              <div 
                v-for="metric in keyMetrics" 
                :key="metric.id"
                class="metric-card"
                :class="{ 'animate-pulse': isLoading }"
              >
                <div class="metric-icon">
                  <component :is="metric.icon" class="w-6 h-6" />
                </div>
                <div class="metric-content">
                  <div class="metric-value">
                    <AnimatedCounter 
                      :value="metric.value" 
                      :format="metric.format"
                      :duration="1500"
                    />
                  </div>
                  <div class="metric-label">{{ metric.label }}</div>
                  <div class="metric-change" :class="metric.trend">
                    <span class="trend-icon">
                      {{ metric.trend === 'up' ? '↗' : metric.trend === 'down' ? '↘' : '→' }}
                    </span>
                    {{ metric.change }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Interactive Features Tabs -->
            <div class="features-tabs mt-8">
              <div class="tab-navigation">
                <button
                  v-for="tab in featureTabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  class="tab-button"
                  :class="{ 'active': activeTab === tab.id }"
                  :aria-selected="activeTab === tab.id"
                  role="tab"
                >
                  <component :is="tab.icon" class="w-5 h-5 mr-2" />
                  {{ tab.label }}
                </button>
              </div>

              <div class="tab-content">
                <div 
                  v-for="tab in featureTabs"
                  :key="tab.id"
                  v-show="activeTab === tab.id"
                  class="tab-panel"
                  role="tabpanel"
                >
                  <div class="feature-preview">
                    <div class="feature-screenshot">
                      <img 
                        :src="tab.screenshot" 
                        :alt="`${tab.label} feature preview`"
                        class="w-full h-auto rounded-lg shadow-lg"
                        loading="lazy"
                      />
                      <!-- Interactive Hotspots -->
                      <div 
                        v-for="hotspot in tab.hotspots"
                        :key="hotspot.id"
                        class="hotspot"
                        :style="{ top: hotspot.y + '%', left: hotspot.x + '%' }"
                        @click="showHotspotDetail(hotspot)"
                        :aria-label="hotspot.description"
                      >
                        <div class="hotspot-pulse"></div>
                        <div class="hotspot-dot"></div>
                      </div>
                    </div>
                    <div class="feature-description">
                      <h4 class="text-xl font-semibold mb-3">{{ tab.title }}</h4>
                      <p class="text-gray-600 mb-4">{{ tab.description }}</p>
                      <ul class="feature-benefits">
                        <li v-for="benefit in tab.benefits" :key="benefit" class="benefit-item">
                          <CheckIcon class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                          {{ benefit }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Feature Comparison -->
            <div class="comparison-section mt-12">
              <h4 class="text-2xl font-bold text-center mb-8">Individual vs Institutional Features</h4>
              <div class="comparison-table">
                <div class="comparison-header">
                  <div class="comparison-cell feature-header">Features</div>
                  <div class="comparison-cell plan-header individual">
                    <div class="plan-icon">👤</div>
                    <div>Individual Alumni</div>
                  </div>
                  <div class="comparison-cell plan-header institutional">
                    <div class="plan-icon">🏛️</div>
                    <div>Institutional Admin</div>
                  </div>
                </div>
                
                <div 
                  v-for="feature in comparisonFeatures"
                  :key="feature.id"
                  class="comparison-row"
                >
                  <div class="comparison-cell feature-name">
                    <div class="feature-title">{{ feature.name }}</div>
                    <div class="feature-subtitle">{{ feature.description }}</div>
                  </div>
                  <div class="comparison-cell feature-availability">
                    <FeatureAvailability :level="feature.individual" />
                  </div>
                  <div class="comparison-cell feature-availability">
                    <FeatureAvailability :level="feature.institutional" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Demo CTA Section -->
      <div class="demo-cta-section">
        <div class="cta-content">
          <h3 class="text-3xl font-bold text-white mb-4">
            Ready to Transform Your Alumni Engagement?
          </h3>
          <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Schedule a personalized demo to see how our institutional dashboard 
            can increase alumni participation by up to 300%.
          </p>
          <div class="cta-buttons">
            <button 
              @click="requestDemo"
              class="primary-cta-button"
              :disabled="isRequestingDemo"
            >
              <span v-if="!isRequestingDemo">Request Live Demo</span>
              <span v-else class="flex items-center">
                <LoadingSpinner class="w-5 h-5 mr-2" />
                Scheduling...
              </span>
            </button>
            <button 
              @click="downloadCaseStudies"
              class="secondary-cta-button"
            >
              Download Case Studies
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Demo Request Modal -->
    <DemoRequestModal 
      v-if="showDemoModal"
      @close="showDemoModal = false"
      @submit="handleDemoRequest"
      :institution-name="institutionName"
    />

    <!-- Hotspot Detail Modal -->
    <HotspotDetailModal
      v-if="selectedHotspot"
      :hotspot="selectedHotspot"
      @close="selectedHotspot = null"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ChartBarIcon, 
  UsersIcon, 
  CalendarIcon, 
  CogIcon,
  CheckIcon,
  DevicePhoneMobileIcon,
  ChatBubbleLeftRightIcon,
  AcademicCapIcon
} from '@heroicons/vue/24/outline'
import AnimatedCounter from '../ui/AnimatedCounter.vue'
import DemoRequestModal from './DemoRequestModal.vue'
import HotspotDetailModal from './HotspotDetailModal.vue'
import FeatureAvailability from './FeatureAvailability.vue'
import LoadingSpinner from '../ui/LoadingSpinner.vue'
import type { 
  InstitutionalFeature, 
  PlatformStatistic, 
  DemoRequestData,
  AdminDashboard 
} from '../../types/homepage'

interface Props {
  institutionName?: string
  demoData?: AdminDashboard
}

const props = withDefaults(defineProps<Props>(), {
  institutionName: 'Your University',
  demoData: () => ({
    features: [],
    analytics: {
      totalAlumni: 15420,
      activeUsers: 8934,
      engagementRate: 67,
      eventsThisMonth: 24
    },
    managementTools: [],
    customization: []
  })
})

const emit = defineEmits<{
  'demo-request': [data: DemoRequestData]
  'case-study-download': []
  'hotspot-click': [hotspotId: string]
}>()

// Reactive state
const activeTab = ref('analytics')
const showDemoModal = ref(false)
const selectedHotspot = ref(null)
const isRequestingDemo = ref(false)
const isLoading = ref(false)

// Computed properties
const currentPeriod = computed(() => {
  const now = new Date()
  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December']
  return `${monthNames[now.getMonth()]} ${now.getFullYear()}`
})

// Demo data
const keyMetrics = ref([
  {
    id: 'total-alumni',
    label: 'Total Alumni',
    value: 15420,
    format: 'number',
    icon: UsersIcon,
    trend: 'up',
    change: '+12% this month'
  },
  {
    id: 'active-users',
    label: 'Active Users',
    value: 8934,
    format: 'number',
    icon: ChartBarIcon,
    trend: 'up',
    change: '+23% this month'
  },
  {
    id: 'engagement-rate',
    label: 'Engagement Rate',
    value: 67,
    format: 'percentage',
    icon: ChatBubbleLeftRightIcon,
    trend: 'up',
    change: '+8% this month'
  },
  {
    id: 'events-hosted',
    label: 'Events This Month',
    value: 24,
    format: 'number',
    icon: CalendarIcon,
    trend: 'up',
    change: '+4 from last month'
  }
])

const featureTabs = ref([
  {
    id: 'analytics',
    label: 'Analytics Dashboard',
    icon: ChartBarIcon,
    title: 'Comprehensive Alumni Analytics',
    description: 'Track engagement, measure success, and identify opportunities with detailed analytics and reporting.',
    screenshot: '/images/admin-analytics-preview.jpg',
    benefits: [
      'Real-time engagement metrics',
      'Alumni participation tracking',
      'Event attendance analytics',
      'Custom report generation',
      'ROI measurement tools'
    ],
    hotspots: [
      { id: 'engagement-chart', x: 25, y: 30, description: 'Interactive engagement trends chart' },
      { id: 'alumni-map', x: 70, y: 45, description: 'Geographic alumni distribution' },
      { id: 'export-tools', x: 85, y: 15, description: 'Data export and reporting tools' }
    ]
  },
  {
    id: 'management',
    label: 'User Management',
    icon: UsersIcon,
    title: 'Alumni Community Management',
    description: 'Efficiently manage your alumni community with powerful administrative tools and bulk operations.',
    screenshot: '/images/admin-management-preview.jpg',
    benefits: [
      'Bulk user import/export',
      'Alumni verification system',
      'Role and permission management',
      'Communication tools',
      'Profile moderation'
    ],
    hotspots: [
      { id: 'user-list', x: 20, y: 40, description: 'Searchable alumni directory' },
      { id: 'bulk-actions', x: 80, y: 25, description: 'Bulk operation tools' },
      { id: 'verification', x: 50, y: 70, description: 'Alumni verification workflow' }
    ]
  },
  {
    id: 'customization',
    label: 'Branding & Apps',
    icon: DevicePhoneMobileIcon,
    title: 'Custom Branded Experience',
    description: 'Create a fully branded experience with custom mobile apps and institutional theming.',
    screenshot: '/images/admin-branding-preview.jpg',
    benefits: [
      'Custom mobile app creation',
      'Institutional branding',
      'App Store deployment',
      'White-label solutions',
      'Brand consistency tools'
    ],
    hotspots: [
      { id: 'app-builder', x: 30, y: 35, description: 'Mobile app customization tools' },
      { id: 'brand-settings', x: 70, y: 50, description: 'Institutional branding options' },
      { id: 'app-preview', x: 85, y: 25, description: 'Live app preview' }
    ]
  },
  {
    id: 'integrations',
    label: 'Integrations',
    icon: CogIcon,
    title: 'System Integrations',
    description: 'Seamlessly integrate with your existing CRM, email systems, and institutional databases.',
    screenshot: '/images/admin-integrations-preview.jpg',
    benefits: [
      'CRM system integration',
      'Email platform sync',
      'Single sign-on (SSO)',
      'API access',
      'Data synchronization'
    ],
    hotspots: [
      { id: 'crm-integration', x: 25, y: 40, description: 'CRM integration setup' },
      { id: 'sso-config', x: 75, y: 30, description: 'Single sign-on configuration' },
      { id: 'api-docs', x: 50, y: 65, description: 'API documentation and tools' }
    ]
  }
])

const comparisonFeatures = ref([
  {
    id: 'networking',
    name: 'Alumni Networking',
    description: 'Connect with fellow alumni',
    individual: 'full',
    institutional: 'enhanced'
  },
  {
    id: 'analytics',
    name: 'Analytics & Reporting',
    description: 'Track engagement and success',
    individual: 'basic',
    institutional: 'full'
  },
  {
    id: 'events',
    name: 'Event Management',
    description: 'Create and manage events',
    individual: 'limited',
    institutional: 'full'
  },
  {
    id: 'branding',
    name: 'Custom Branding',
    description: 'Institutional theming',
    individual: 'none',
    institutional: 'full'
  },
  {
    id: 'mobile-app',
    name: 'Branded Mobile App',
    description: 'Custom mobile application',
    individual: 'none',
    institutional: 'full'
  },
  {
    id: 'integrations',
    name: 'System Integrations',
    description: 'CRM, email, and database sync',
    individual: 'limited',
    institutional: 'full'
  },
  {
    id: 'support',
    name: 'Support Level',
    description: 'Customer support access',
    individual: 'standard',
    institutional: 'priority'
  }
])

// Methods
const requestDemo = () => {
  showDemoModal.value = true
}

const handleDemoRequest = async (data: DemoRequestData) => {
  isRequestingDemo.value = true
  try {
    emit('demo-request', data)
    showDemoModal.value = false
    // Show success message or redirect
  } catch (error) {
    console.error('Demo request failed:', error)
  } finally {
    isRequestingDemo.value = false
  }
}

const downloadCaseStudies = () => {
  emit('case-study-download')
}

const showHotspotDetail = (hotspot: any) => {
  selectedHotspot.value = hotspot
  emit('hotspot-click', hotspot.id)
}

// Lifecycle
onMounted(() => {
  // Simulate loading state
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
  }, 1000)
})
</script>