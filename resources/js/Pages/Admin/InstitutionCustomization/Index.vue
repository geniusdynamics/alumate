<template>
  <AdminLayout>
    <div class="institution-customization">
      <div class="header">
        <h1 class="page-title">Institution Customization</h1>
        <div class="header-actions">
          <button @click="exportConfig" class="btn btn-secondary">
            <Icon name="download" class="w-4 h-4" />
            Export Config
          </button>
          <button @click="generateWhiteLabel" class="btn btn-primary">
            <Icon name="globe" class="w-4 h-4" />
            Generate White-Label
          </button>
        </div>
      </div>

      <div class="customization-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="['tab-button', { active: activeTab === tab.key }]"
        >
          <Icon :name="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Branding Tab -->
      <div v-if="activeTab === 'branding'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">Brand Identity</h2>
          <form @submit.prevent="updateBranding" class="branding-form">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Primary Color</label>
                <div class="color-input-group">
                  <input
                    v-model="brandingForm.primary_color"
                    type="color"
                    class="color-input"
                  />
                  <input
                    v-model="brandingForm.primary_color"
                    type="text"
                    class="form-input"
                    placeholder="#007bff"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Secondary Color</label>
                <div class="color-input-group">
                  <input
                    v-model="brandingForm.secondary_color"
                    type="color"
                    class="color-input"
                  />
                  <input
                    v-model="brandingForm.secondary_color"
                    type="text"
                    class="form-input"
                    placeholder="#6c757d"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Font Family</label>
                <select v-model="brandingForm.font_family" class="form-select">
                  <option value="inter">Inter</option>
                  <option value="roboto">Roboto</option>
                  <option value="open-sans">Open Sans</option>
                  <option value="lato">Lato</option>
                  <option value="montserrat">Montserrat</option>
                  <option value="poppins">Poppins</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Theme Style</label>
                <select v-model="brandingForm.theme_style" class="form-select">
                  <option value="modern">Modern</option>
                  <option value="classic">Classic</option>
                  <option value="minimal">Minimal</option>
                  <option value="corporate">Corporate</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Logo</label>
              <div class="file-upload-area">
                <input
                  ref="logoInput"
                  type="file"
                  accept="image/*"
                  @change="handleLogoUpload"
                  class="file-input"
                />
                <div class="upload-preview">
                  <img
                    v-if="institution.logo_url"
                    :src="institution.logo_url"
                    alt="Current logo"
                    class="current-logo"
                  />
                  <div class="upload-placeholder" v-else>
                    <Icon name="image" class="w-8 h-8" />
                    <span>Upload Logo</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Banner Image</label>
              <div class="file-upload-area">
                <input
                  ref="bannerInput"
                  type="file"
                  accept="image/*"
                  @change="handleBannerUpload"
                  class="file-input"
                />
                <div class="upload-preview banner-preview">
                  <img
                    v-if="institution.banner_url"
                    :src="institution.banner_url"
                    alt="Current banner"
                    class="current-banner"
                  />
                  <div class="upload-placeholder" v-else>
                    <Icon name="image" class="w-8 h-8" />
                    <span>Upload Banner</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Custom CSS</label>
              <textarea
                v-model="brandingForm.custom_css"
                class="form-textarea"
                rows="8"
                placeholder="/* Add custom CSS styles here */"
              ></textarea>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="brandingLoading">
                <Icon v-if="brandingLoading" name="loader" class="w-4 h-4 animate-spin" />
                {{ brandingLoading ? 'Saving...' : 'Save Branding' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Features Tab -->
      <div v-if="activeTab === 'features'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">Feature Configuration</h2>
          <div class="features-grid">
            <div
              v-for="(feature, key) in availableFeatures"
              :key="key"
              class="feature-card"
            >
              <div class="feature-header">
                <div class="feature-info">
                  <h3 class="feature-name">{{ feature.name }}</h3>
                  <p class="feature-description">{{ feature.description }}</p>
                  <span class="feature-category">{{ feature.category }}</span>
                </div>
                <label class="toggle-switch">
                  <input
                    v-model="featureFlags[key]"
                    type="checkbox"
                    @change="updateFeatures"
                  />
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Fields Tab -->
      <div v-if="activeTab === 'custom_fields'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">Custom Fields Configuration</h2>
          <div class="custom-fields-manager">
            <div class="fields-header">
              <button @click="addCustomField" class="btn btn-primary">
                <Icon name="plus" class="w-4 h-4" />
                Add Custom Field
              </button>
            </div>

            <div class="fields-list">
              <div
                v-for="(field, index) in customFields"
                :key="index"
                class="field-item"
              >
                <div class="field-config">
                  <div class="field-basic">
                    <input
                      v-model="field.name"
                      type="text"
                      placeholder="Field Name"
                      class="form-input"
                    />
                    <select v-model="field.type" class="form-select">
                      <option value="text">Text</option>
                      <option value="textarea">Textarea</option>
                      <option value="select">Select</option>
                      <option value="checkbox">Checkbox</option>
                      <option value="date">Date</option>
                      <option value="number">Number</option>
                    </select>
                    <select v-model="field.section" class="form-select">
                      <option value="profile">Profile</option>
                      <option value="registration">Registration</option>
                      <option value="career">Career</option>
                    </select>
                  </div>

                  <div class="field-options">
                    <label class="checkbox-label">
                      <input v-model="field.required" type="checkbox" />
                      Required
                    </label>

                    <div v-if="field.type === 'select'" class="select-options">
                      <label class="form-label">Options (one per line)</label>
                      <textarea
                        v-model="field.optionsText"
                        @input="updateFieldOptions(field)"
                        class="form-textarea"
                        rows="3"
                        placeholder="Option 1&#10;Option 2&#10;Option 3"
                      ></textarea>
                    </div>
                  </div>

                  <button
                    @click="removeCustomField(index)"
                    class="btn btn-danger btn-sm"
                  >
                    <Icon name="trash" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button @click="saveCustomFields" class="btn btn-primary" :disabled="customFieldsLoading">
                <Icon v-if="customFieldsLoading" name="loader" class="w-4 h-4 animate-spin" />
                {{ customFieldsLoading ? 'Saving...' : 'Save Custom Fields' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Workflows Tab -->
      <div v-if="activeTab === 'workflows'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">Workflow Automation</h2>
          <div class="workflows-manager">
            <div class="workflows-header">
              <button @click="addWorkflow" class="btn btn-primary">
                <Icon name="plus" class="w-4 h-4" />
                Add Workflow
              </button>
            </div>

            <div class="workflows-list">
              <div
                v-for="(workflow, index) in workflows"
                :key="index"
                class="workflow-item"
              >
                <div class="workflow-header">
                  <input
                    v-model="workflow.name"
                    type="text"
                    placeholder="Workflow Name"
                    class="form-input workflow-name"
                  />
                  <label class="toggle-switch">
                    <input v-model="workflow.enabled" type="checkbox" />
                    <span class="toggle-slider"></span>
                  </label>
                </div>

                <div class="workflow-config">
                  <div class="form-group">
                    <label class="form-label">Trigger</label>
                    <select v-model="workflow.trigger" class="form-select">
                      <option value="user_registration">User Registration</option>
                      <option value="profile_completion">Profile Completion</option>
                      <option value="job_application">Job Application</option>
                      <option value="event_registration">Event Registration</option>
                      <option value="donation_made">Donation Made</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Actions</label>
                    <div class="actions-list">
                      <div
                        v-for="(action, actionIndex) in workflow.actions"
                        :key="actionIndex"
                        class="action-item"
                      >
                        <select v-model="action.type" class="form-select">
                          <option value="send_email">Send Email</option>
                          <option value="add_to_group">Add to Group</option>
                          <option value="create_task">Create Task</option>
                          <option value="send_notification">Send Notification</option>
                        </select>
                        <input
                          v-model="action.config"
                          type="text"
                          placeholder="Action configuration"
                          class="form-input"
                        />
                        <button
                          @click="removeWorkflowAction(workflow, actionIndex)"
                          class="btn btn-danger btn-sm"
                        >
                          <Icon name="trash" class="w-4 h-4" />
                        </button>
                      </div>
                      <button
                        @click="addWorkflowAction(workflow)"
                        class="btn btn-secondary btn-sm"
                      >
                        <Icon name="plus" class="w-4 h-4" />
                        Add Action
                      </button>
                    </div>
                  </div>
                </div>

                <button
                  @click="removeWorkflow(index)"
                  class="btn btn-danger btn-sm"
                >
                  <Icon name="trash" class="w-4 h-4" />
                  Remove Workflow
                </button>
              </div>
            </div>

            <div class="form-actions">
              <button @click="saveWorkflows" class="btn btn-primary" :disabled="workflowsLoading">
                <Icon v-if="workflowsLoading" name="loader" class="w-4 h-4 animate-spin" />
                {{ workflowsLoading ? 'Saving...' : 'Save Workflows' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reporting Tab -->
      <div v-if="activeTab === 'reporting'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">Reporting Configuration</h2>
          <form @submit.prevent="saveReportingConfig" class="reporting-form">
            <div class="form-group">
              <label class="form-label">Default Metrics</label>
              <div class="metrics-grid">
                <label
                  v-for="metric in availableMetrics"
                  :key="metric.key"
                  class="metric-option"
                >
                  <input
                    v-model="reportingConfig.default_metrics"
                    type="checkbox"
                    :value="metric.key"
                    class="metric-checkbox"
                  />
                  <div class="metric-content">
                    <span class="metric-name">{{ metric.label }}</span>
                    <span class="metric-description">{{ metric.description }}</span>
                  </div>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Data Retention (Days)</label>
              <input
                v-model.number="reportingConfig.data_retention_days"
                type="number"
                min="30"
                max="2555"
                class="form-input"
              />
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="reportingLoading">
                <Icon v-if="reportingLoading" name="loader" class="w-4 h-4 animate-spin" />
                {{ reportingLoading ? 'Saving...' : 'Save Configuration' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Integrations Tab -->
      <div v-if="activeTab === 'integrations'" class="tab-content">
        <div class="section-card">
          <h2 class="section-title">External Integrations</h2>
          <div class="integrations-grid">
            <div
              v-for="(integration, key) in integrationOptions"
              :key="key"
              class="integration-card"
            >
              <div class="integration-header">
                <div class="integration-info">
                  <h3 class="integration-name">{{ integration.name }}</h3>
                  <p class="integration-description">{{ integration.description }}</p>
                </div>
                <label class="toggle-switch">
                  <input
                    v-model="integrations[key].enabled"
                    type="checkbox"
                    @change="updateIntegrations"
                  />
                  <span class="toggle-slider"></span>
                </label>
              </div>

              <div v-if="integrations[key].enabled" class="integration-config">
                <div class="form-group">
                  <label class="form-label">Provider</label>
                  <select
                    v-model="integrations[key].provider"
                    class="form-select"
                  >
                    <option
                      v-for="provider in integration.providers"
                      :key="provider"
                      :value="provider"
                    >
                      {{ provider.replace('_', ' ').toUpperCase() }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Configuration</label>
                  <textarea
                    v-model="integrations[key].configJson"
                    @input="updateIntegrationConfig(key)"
                    class="form-textarea"
                    rows="4"
                    placeholder='{"api_key": "your_key", "secret": "your_secret"}'
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- White-Label Modal -->
    <div v-if="showWhiteLabelModal" class="modal-overlay" @click="closeWhiteLabelModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">White-Label Configuration</h3>
          <button @click="closeWhiteLabelModal" class="close-button">
            <Icon name="x" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="modal-body">
          <div class="config-preview">
            <pre class="config-json">{{ JSON.stringify(whiteLabelConfig, null, 2) }}</pre>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="downloadConfig" class="btn btn-primary">
            <Icon name="download" class="w-4 h-4" />
            Download Config
          </button>
          <button @click="closeWhiteLabelModal" class="btn btn-secondary">
            Close
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Icon from '@/Components/Icon.vue'

interface Institution {
  id: number
  name: string
  logo_url?: string
  banner_url?: string
  primary_color?: string
  secondary_color?: string
  settings?: InstitutionSettings
  feature_flags?: Record<string, boolean>
  integration_settings?: IntegrationSetting[]
}

interface InstitutionSettings {
  branding?: BrandingSettings
  custom_fields?: CustomField[]
  workflows?: Workflow[]
  reporting?: ReportingSettings
}

interface BrandingSettings {
  font_family?: string
  theme_style?: string
  custom_css?: string
}

interface CustomField {
  name: string
  type: 'text' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'date' | 'number'
  required: boolean
  section: 'profile' | 'education' | 'experience' | 'contact'
  options: string[]
  optionsText?: string
}

interface Workflow {
  name: string
  trigger: 'user_registration' | 'profile_update' | 'job_application' | 'event_signup'
  actions: WorkflowAction[]
  conditions: WorkflowCondition[]
  enabled: boolean
}

interface WorkflowAction {
  type: 'send_email' | 'create_task' | 'update_field' | 'send_notification'
  config: string | Record<string, unknown>
}

interface WorkflowCondition {
  field: string
  operator: 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than'
  value: string | number
}

interface ReportingSettings {
  default_metrics?: string[]
  data_retention_days?: number
}

interface IntegrationSetting {
  name: string
  enabled: boolean
  config: Record<string, unknown>
}

interface IntegrationConfig {
  enabled: boolean
  provider: string
  config: Record<string, unknown>
  configJson: string
}

interface AvailableFeature {
  key: string
  label: string
  description: string
  enabled: boolean
}

interface IntegrationOption {
  name: string
  label: string
  description: string
  providers: string[]
  config_fields: ConfigField[]
}

interface ConfigField {
  key: string
  label: string
  type: 'text' | 'password' | 'url' | 'select'
  required: boolean
  options?: string[]
}

const props = defineProps<{
  institution: Institution
  availableFeatures: Record<string, AvailableFeature>
  integrationOptions: Record<string, IntegrationOption>
}>()

// Reactive state
const activeTab = ref('branding')
const brandingLoading = ref(false)
const customFieldsLoading = ref(false)
const workflowsLoading = ref(false)
const reportingLoading = ref(false)
const showWhiteLabelModal = ref(false)
const whiteLabelConfig = ref({})

// Form data
const brandingForm = reactive({
  primary_color: props.institution.primary_color || '#007bff',
  secondary_color: props.institution.secondary_color || '#6c757d',
  font_family: props.institution.settings?.branding?.font_family || 'inter',
  theme_style: props.institution.settings?.branding?.theme_style || 'modern',
  custom_css: props.institution.settings?.branding?.custom_css || '',
})

const featureFlags = reactive({ ...props.institution.feature_flags })
const customFields = ref(props.institution.settings?.custom_fields || [])
const workflows = ref(props.institution.settings?.workflows || [])

const reportingConfig = reactive({
  default_metrics: props.institution.settings?.reporting?.default_metrics || [],
  data_retention_days: props.institution.settings?.reporting?.data_retention_days || 365,
})

const integrations = reactive(
  Object.keys(props.integrationOptions).reduce((acc, key) => {
    const existing = props.institution.integration_settings?.find(i => i.name === key)
    acc[key] = {
      enabled: existing?.enabled || false,
      provider: existing?.config?.provider || props.integrationOptions[key].providers[0],
      config: existing?.config || {},
      configJson: JSON.stringify(existing?.config || {}, null, 2)
    }
    return acc
  }, {} as Record<string, IntegrationConfig>)
)

// Computed
const tabs = computed(() => [
  { key: 'branding', label: 'Branding', icon: 'palette' },
  { key: 'features', label: 'Features', icon: 'toggle-left' },
  { key: 'custom_fields', label: 'Custom Fields', icon: 'form-input' },
  { key: 'workflows', label: 'Workflows', icon: 'git-branch' },
  { key: 'reporting', label: 'Reporting', icon: 'bar-chart' },
  { key: 'integrations', label: 'Integrations', icon: 'link' },
])

const availableMetrics = computed(() => [
  { key: 'engagement_rate', label: 'Engagement Rate', description: 'Overall platform engagement' },
  { key: 'active_users', label: 'Active Users', description: 'Monthly active users' },
  { key: 'new_registrations', label: 'New Registrations', description: 'New user sign-ups' },
  { key: 'job_placements', label: 'Job Placements', description: 'Successful job matches' },
  { key: 'event_attendance', label: 'Event Attendance', description: 'Event participation rates' },
  { key: 'donation_amounts', label: 'Donation Amounts', description: 'Fundraising totals' },
])

// Methods
const updateBranding = async () => {
  brandingLoading.value = true
  try {
    const formData = new FormData()
    Object.keys(brandingForm).forEach(key => {
      if (brandingForm[key]) {
        formData.append(key, brandingForm[key])
      }
    })

    await router.post(`/admin/institutions/${props.institution.id}/branding`, formData, {
      preserveState: true,
      preserveScroll: true,
    })
  } finally {
    brandingLoading.value = false
  }
}

const updateFeatures = async () => {
  try {
    await router.post(`/admin/institutions/${props.institution.id}/features`, {
      features: featureFlags
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  } catch (error) {
    console.error('Failed to update features:', error)
  }
}

const addCustomField = () => {
  customFields.value.push({
    name: '',
    type: 'text',
    required: false,
    section: 'profile',
    options: [],
    optionsText: ''
  })
}

const removeCustomField = (index: number) => {
  customFields.value.splice(index, 1)
}

const updateFieldOptions = (field: CustomField) => {
  field.options = field.optionsText?.split('\n').filter(option => option.trim()) || []
}

const saveCustomFields = async () => {
  customFieldsLoading.value = true
  try {
    await router.post(`/admin/institutions/${props.institution.id}/custom-fields`, {
      custom_fields: customFields.value
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  } finally {
    customFieldsLoading.value = false
  }
}

const addWorkflow = () => {
  workflows.value.push({
    name: '',
    trigger: 'user_registration',
    actions: [],
    conditions: [],
    enabled: true
  })
}

const removeWorkflow = (index: number) => {
  workflows.value.splice(index, 1)
}

const addWorkflowAction = (workflow: Workflow) => {
  workflow.actions.push({
    type: 'send_email',
    config: ''
  })
}

const removeWorkflowAction = (workflow: Workflow, actionIndex: number) => {
  workflow.actions.splice(actionIndex, 1)
}

const saveWorkflows = async () => {
  workflowsLoading.value = true
  try {
    await router.post(`/admin/institutions/${props.institution.id}/workflows`, {
      workflows: workflows.value
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  } finally {
    workflowsLoading.value = false
  }
}

const saveReportingConfig = async () => {
  reportingLoading.value = true
  try {
    await router.post(`/admin/institutions/${props.institution.id}/reporting`, {
      reporting_config: reportingConfig
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  } finally {
    reportingLoading.value = false
  }
}

const updateIntegrationConfig = (key: string) => {
  try {
    integrations[key].config = JSON.parse(integrations[key].configJson)
  } catch (error) {
    // Invalid JSON, keep as string
  }
}

const updateIntegrations = async () => {
  try {
    const integrationsData = Object.keys(integrations).map(key => ({
      name: key,
      enabled: integrations[key].enabled,
      config: {
        provider: integrations[key].provider,
        ...integrations[key].config
      }
    }))

    await router.post(`/admin/institutions/${props.institution.id}/integrations`, {
      integrations: integrationsData
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  } catch (error) {
    console.error('Failed to update integrations:', error)
  }
}

const generateWhiteLabel = async () => {
  try {
    const response = await fetch(`/admin/institutions/${props.institution.id}/white-label-config`)
    const data = await response.json()
    
    if (data.success) {
      whiteLabelConfig.value = data.config
      showWhiteLabelModal.value = true
    }
  } catch (error) {
    console.error('Failed to generate white-label config:', error)
  }
}

const closeWhiteLabelModal = () => {
  showWhiteLabelModal.value = false
}

const downloadConfig = () => {
  const blob = new Blob([JSON.stringify(whiteLabelConfig.value, null, 2)], {
    type: 'application/json'
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.institution.name.toLowerCase().replace(/\s+/g, '-')}-white-label-config.json`
  a.click()
  URL.revokeObjectURL(url)
}

const exportConfig = () => {
  const config = {
    branding: brandingForm,
    features: featureFlags,
    custom_fields: customFields.value,
    workflows: workflows.value,
    reporting: reportingConfig,
    integrations: integrations
  }
  
  const blob = new Blob([JSON.stringify(config, null, 2)], {
    type: 'application/json'
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.institution.name.toLowerCase().replace(/\s+/g, '-')}-config.json`
  a.click()
  URL.revokeObjectURL(url)
}

const handleLogoUpload = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    // Handle logo upload logic
  }
}

const handleBannerUpload = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    // Handle banner upload logic
  }
}
</script>

<style scoped>
.institution-customization {
  @apply max-w-7xl mx-auto px-4 py-6;
}

.header {
  @apply flex items-center justify-between mb-8;
}

.page-title {
  @apply text-3xl font-bold text-gray-900 dark:text-white;
}

.header-actions {
  @apply flex items-center space-x-3;
}

.customization-tabs {
  @apply flex space-x-1 mb-8 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg;
}

.tab-button {
  @apply flex items-center space-x-2 px-4 py-2 rounded-md text-sm font-medium transition-colors;
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.tab-button.active {
  @apply bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm;
}

.tab-content {
  @apply space-y-6;
}

.section-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.section-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-6;
}

.form-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply font-mono text-sm;
}

.color-input-group {
  @apply flex items-center space-x-2;
}

.color-input {
  @apply w-12 h-10 border border-gray-300 dark:border-gray-600 rounded cursor-pointer;
}

.file-upload-area {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4;
  @apply hover:border-blue-400 dark:hover:border-blue-500 transition-colors;
}

.file-input {
  @apply sr-only;
}

.upload-preview {
  @apply flex items-center justify-center;
}

.current-logo {
  @apply max-h-20 max-w-40 object-contain;
}

.current-banner {
  @apply max-h-32 w-full object-cover rounded;
}

.banner-preview {
  @apply min-h-32;
}

.upload-placeholder {
  @apply flex flex-col items-center space-y-2 text-gray-500 dark:text-gray-400;
}

.features-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4;
}

.feature-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4;
  @apply hover:shadow-md transition-shadow;
}

.feature-header {
  @apply flex items-start justify-between;
}

.feature-info {
  @apply flex-1 pr-4;
}

.feature-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.feature-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.feature-category {
  @apply inline-block px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded mt-2;
}

.toggle-switch {
  @apply relative inline-block w-12 h-6;
}

.toggle-switch input {
  @apply sr-only;
}

.toggle-slider {
  @apply absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 dark:bg-gray-600 rounded-full transition-colors;
}

.toggle-slider:before {
  @apply absolute content-[''] h-5 w-5 left-0.5 bottom-0.5 bg-white rounded-full transition-transform;
}

.toggle-switch input:checked + .toggle-slider {
  @apply bg-blue-600;
}

.toggle-switch input:checked + .toggle-slider:before {
  @apply transform translate-x-6;
}

.fields-header {
  @apply flex justify-between items-center mb-6;
}

.fields-list {
  @apply space-y-4;
}

.field-item {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4;
}

.field-config {
  @apply space-y-4;
}

.field-basic {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.field-options {
  @apply space-y-3;
}

.checkbox-label {
  @apply flex items-center space-x-2 text-sm;
}

.select-options {
  @apply space-y-2;
}

.workflows-header {
  @apply flex justify-between items-center mb-6;
}

.workflows-list {
  @apply space-y-6;
}

.workflow-item {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4;
}

.workflow-header {
  @apply flex items-center justify-between;
}

.workflow-name {
  @apply flex-1 mr-4;
}

.workflow-config {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.actions-list {
  @apply space-y-3;
}

.action-item {
  @apply flex items-center space-x-3;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-3;
}

.metric-option {
  @apply relative cursor-pointer;
}

.metric-checkbox {
  @apply sr-only;
}

.metric-content {
  @apply flex flex-col p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg;
  @apply hover:border-blue-300 dark:hover:border-blue-500 transition-colors;
}

.metric-option input:checked + .metric-content {
  @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.metric-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.metric-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.integrations-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.integration-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4;
}

.integration-header {
  @apply flex items-start justify-between;
}

.integration-info {
  @apply flex-1 pr-4;
}

.integration-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.integration-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

.integration-config {
  @apply space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700;
}

.form-actions {
  @apply flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500;
  @apply dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600;
}

.btn-danger {
  @apply text-white bg-red-600 hover:bg-red-700 focus:ring-red-500;
}

.btn-sm {
  @apply px-3 py-1.5 text-xs;
}

.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors;
}

.modal-body {
  @apply p-6;
}

.config-preview {
  @apply bg-gray-50 dark:bg-gray-900 rounded-lg p-4;
}

.config-json {
  @apply text-sm font-mono text-gray-800 dark:text-gray-200 whitespace-pre-wrap;
  @apply max-h-96 overflow-y-auto;
}

.modal-footer {
  @apply flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700;
}
</style>