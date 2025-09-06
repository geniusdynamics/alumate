<!-- ABOUTME: Security incidents monitoring component for tracking security events and threats -->
<!-- ABOUTME: Displays security alerts, incident reports, and threat analysis with severity classification -->
<template>
  <div class="security-incidents-container">
    <div class="incidents-header">
      <h3 class="incidents-title">Security Incidents</h3>
      <div class="incidents-controls">
        <select 
          v-model="selectedSeverity" 
          @change="filterIncidents"
          class="severity-filter"
        >
          <option value="all">All Severities</option>
          <option value="critical">Critical</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
        <select 
          v-model="selectedStatus" 
          @change="filterIncidents"
          class="status-filter"
        >
          <option value="all">All Status</option>
          <option value="open">Open</option>
          <option value="investigating">Investigating</option>
          <option value="resolved">Resolved</option>
        </select>
      </div>
    </div>

    <!-- Security Overview -->
    <div class="security-overview">
      <div class="overview-card critical">
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ securityStats.critical }}</div>
          <div class="card-label">Critical Threats</div>
        </div>
      </div>

      <div class="overview-card high">
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ securityStats.blocked }}</div>
          <div class="card-label">Threats Blocked</div>
        </div>
      </div>

      <div class="overview-card medium">
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ securityStats.monitoring }}</div>
          <div class="card-label">Under Monitoring</div>
        </div>
      </div>

      <div class="overview-card low">
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="card-content">
          <div class="card-value">{{ securityStats.resolved }}</div>
          <div class="card-label">Resolved Today</div>
        </div>
      </div>
    </div>

    <!-- Recent Incidents -->
    <div class="incidents-list">
      <div class="list-header">
        <h4 class="list-title">Recent Incidents</h4>
        <button @click="refreshIncidents" class="refresh-button" :disabled="loading">
          <svg class="refresh-icon" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
        </button>
      </div>

      <div v-if="loading" class="incidents-loading">
        <div class="loading-spinner"></div>
        <span>Loading security incidents...</span>
      </div>

      <div v-else-if="filteredIncidents.length === 0" class="no-incidents">
        <svg class="no-incidents-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <p>No security incidents found</p>
      </div>

      <div v-else class="incidents-items">
        <div
          v-for="incident in filteredIncidents"
          :key="incident.id"
          :class="[
            'incident-item',
            `incident-${incident.severity}`,
            `status-${incident.status}`
          ]"
          @click="handleIncidentClick(incident)"
        >
          <div class="incident-indicator">
            <div :class="['severity-badge', incident.severity]">{{ incident.severity.toUpperCase() }}</div>
          </div>

          <div class="incident-content">
            <div class="incident-title">{{ incident.title }}</div>
            <div class="incident-description">{{ incident.description }}</div>
            <div class="incident-meta">
              <span class="incident-type">{{ incident.type }}</span>
              <span class="incident-source">{{ incident.source }}</span>
              <span class="incident-time">{{ formatTime(incident.timestamp) }}</span>
            </div>
          </div>

          <div class="incident-status">
            <span :class="['status-badge', incident.status]">{{ incident.status }}</span>
          </div>

          <div class="incident-actions">
            <button
              v-if="incident.status === 'open'"
              @click.stop="investigateIncident(incident.id)"
              class="action-button investigate"
              title="Start Investigation"
            >
              <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>

            <button
              v-if="incident.status !== 'resolved'"
              @click.stop="resolveIncident(incident.id)"
              class="action-button resolve"
              title="Mark as Resolved"
            >
              <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </button>

            <button
              @click.stop="viewIncidentDetails(incident.id)"
              class="action-button details"
              title="View Details"
            >
              <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface SecurityIncident {
  id: string
  title: string
  description: string
  severity: 'critical' | 'high' | 'medium' | 'low'
  status: 'open' | 'investigating' | 'resolved'
  type: string
  source: string
  timestamp: string
  metadata?: Record<string, any>
}

interface Props {
  incidents?: SecurityIncident[]
  autoRefresh?: boolean
  refreshInterval?: number
}

interface Emits {
  'incident-click': [incident: SecurityIncident]
  'incident-investigate': [incidentId: string]
  'incident-resolve': [incidentId: string]
  'incident-details': [incidentId: string]
  'refresh': []
}

const props = withDefaults(defineProps<Props>(), {
  incidents: () => [],
  autoRefresh: false,
  refreshInterval: 60000
})

const emit = defineEmits<Emits>()

// Refs
const loading = ref(false)
const selectedSeverity = ref('all')
const selectedStatus = ref('all')

// Sample data
const sampleIncidents = ref<SecurityIncident[]>([
  {
    id: '1',
    title: 'Suspicious Login Attempts',
    description: 'Multiple failed login attempts detected from unusual IP addresses',
    severity: 'high',
    status: 'investigating',
    type: 'Authentication',
    source: 'Auth Service',
    timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString()
  },
  {
    id: '2',
    title: 'SQL Injection Attempt',
    description: 'Malicious SQL injection attempt blocked by WAF',
    severity: 'critical',
    status: 'open',
    type: 'Web Attack',
    source: 'WAF',
    timestamp: new Date(Date.now() - 45 * 60 * 1000).toISOString()
  },
  {
    id: '3',
    title: 'Unusual Data Access Pattern',
    description: 'User accessing large amounts of sensitive data outside normal hours',
    severity: 'medium',
    status: 'resolved',
    type: 'Data Access',
    source: 'DLP System',
    timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
  }
])

const securityStats = ref({
  critical: 2,
  blocked: 147,
  monitoring: 23,
  resolved: 8
})

// Computed
const incidentsData = computed(() => {
  return props.incidents.length > 0 ? props.incidents : sampleIncidents.value
})

const filteredIncidents = computed(() => {
  let filtered = incidentsData.value

  if (selectedSeverity.value !== 'all') {
    filtered = filtered.filter(incident => incident.severity === selectedSeverity.value)
  }

  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter(incident => incident.status === selectedStatus.value)
  }

  // Sort by severity and timestamp
  const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 }
  const statusOrder = { open: 0, investigating: 1, resolved: 2 }

  return filtered.sort((a, b) => {
    // First sort by status
    const statusDiff = statusOrder[a.status] - statusOrder[b.status]
    if (statusDiff !== 0) return statusDiff

    // Then by severity
    const severityDiff = severityOrder[a.severity] - severityOrder[b.severity]
    if (severityDiff !== 0) return severityDiff

    // Finally by timestamp (newest first)
    return new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  })
})

// Methods
const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`
  return date.toLocaleDateString()
}

const filterIncidents = () => {
  // Filtering is handled by computed property
}

const refreshIncidents = async () => {
  loading.value = true
  try {
    emit('refresh')
    await new Promise(resolve => setTimeout(resolve, 500))
  } finally {
    loading.value = false
  }
}

const handleIncidentClick = (incident: SecurityIncident) => {
  emit('incident-click', incident)
}

const investigateIncident = (incidentId: string) => {
  emit('incident-investigate', incidentId)
}

const resolveIncident = (incidentId: string) => {
  emit('incident-resolve', incidentId)
}

const viewIncidentDetails = (incidentId: string) => {
  emit('incident-details', incidentId)
}

// Lifecycle
onMounted(() => {
  if (props.autoRefresh) {
    setInterval(refreshIncidents, props.refreshInterval)
  }
})
</script>

<style scoped>
.security-incidents-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
}

.incidents-header {
  @apply flex items-center justify-between mb-6;
}

.incidents-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.incidents-controls {
  @apply flex items-center gap-3;
}

.severity-filter,
.status-filter {
  @apply px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md;
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.security-overview {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6;
}

.overview-card {
  @apply rounded-lg p-4 flex items-center gap-4;
}

.overview-card.critical {
  @apply bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800;
}

.overview-card.high {
  @apply bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800;
}

.overview-card.medium {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800;
}

.overview-card.low {
  @apply bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800;
}

.card-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
}

.overview-card.critical .card-icon {
  @apply bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400;
}

.overview-card.high .card-icon {
  @apply bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400;
}

.overview-card.medium .card-icon {
  @apply bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400;
}

.overview-card.low .card-icon {
  @apply bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400;
}

.card-icon svg {
  @apply w-5 h-5;
}

.card-content {
  @apply flex-grow;
}

.card-value {
  @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.card-label {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.incidents-list {
  @apply space-y-4;
}

.list-header {
  @apply flex items-center justify-between;
}

.list-title {
  @apply text-base font-medium text-gray-900 dark:text-white;
}

.refresh-button {
  @apply p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
  @apply border border-gray-300 dark:border-gray-600 rounded-md;
  @apply hover:bg-gray-50 dark:hover:bg-gray-700;
  @apply transition-colors duration-200;
}

.refresh-icon {
  @apply w-4 h-4;
}

.incidents-loading {
  @apply flex items-center justify-center gap-2 py-8 text-gray-600 dark:text-gray-400;
}

.loading-spinner {
  @apply w-5 h-5 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin;
}

.no-incidents {
  @apply flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400;
}

.no-incidents-icon {
  @apply w-12 h-12 mb-3 text-green-500;
}

.incidents-items {
  @apply space-y-3;
}

.incident-item {
  @apply flex items-start gap-4 p-4 rounded-lg border cursor-pointer;
  @apply transition-all duration-200 ease-in-out;
  @apply hover:shadow-md;
}

.incident-critical {
  @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800;
}

.incident-high {
  @apply bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800;
}

.incident-medium {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800;
}

.incident-low {
  @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800;
}

.status-resolved {
  @apply opacity-60;
}

.incident-indicator {
  @apply flex-shrink-0;
}

.severity-badge {
  @apply px-2 py-1 text-xs font-bold rounded-full;
}

.severity-badge.critical {
  @apply bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-400;
}

.severity-badge.high {
  @apply bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-400;
}

.severity-badge.medium {
  @apply bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-400;
}

.severity-badge.low {
  @apply bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-400;
}

.incident-content {
  @apply flex-grow min-w-0;
}

.incident-title {
  @apply font-medium text-gray-900 dark:text-white mb-1;
}

.incident-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-2;
}

.incident-meta {
  @apply flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400;
}

.incident-type,
.incident-source {
  @apply font-medium;
}

.incident-status {
  @apply flex-shrink-0;
}

.status-badge {
  @apply px-2 py-1 text-xs font-medium rounded-full;
}

.status-badge.open {
  @apply bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400;
}

.status-badge.investigating {
  @apply bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400;
}

.status-badge.resolved {
  @apply bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400;
}

.incident-actions {
  @apply flex items-center gap-2;
}

.action-button {
  @apply p-1.5 rounded-md transition-colors duration-200;
}

.action-button.investigate {
  @apply text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/20;
}

.action-button.resolve {
  @apply text-green-600 hover:bg-green-100 dark:hover:bg-green-900/20;
}

.action-button.details {
  @apply text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900/20;
}

.action-icon {
  @apply w-4 h-4;
}

/* Responsive Design */
@media (max-width: 768px) {
  .incidents-header {
    @apply flex-col items-start gap-3;
  }
  
  .security-overview {
    @apply grid-cols-1;
  }
  
  .incident-item {
    @apply flex-col items-start gap-3;
  }
  
  .incident-meta {
    @apply flex-col items-start gap-1;
  }
  
  .incident-actions {
    @apply w-full justify-end;
  }
}

@media (max-width: 640px) {
  .security-incidents-container {
    @apply p-4;
  }
}
</style>