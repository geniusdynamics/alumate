<template>
  <div class="monitoring-dashboard">
    <div class="dashboard-header">
      <h1>Homepage Monitoring Dashboard</h1>
      <div class="last-updated">
        Last updated: {{ formatTime(lastUpdated) }}
        <button @click="refreshData" :disabled="loading" class="refresh-btn">
          <span v-if="loading">Refreshing...</span>
          <span v-else>Refresh</span>
        </button>
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Uptime Status -->
      <div class="dashboard-card">
        <h2>Uptime Status</h2>
        <div class="uptime-grid">
          <div 
            v-for="(endpoint, name) in dashboardData.uptime" 
            :key="name"
            :class="['uptime-item', endpoint.status]"
          >
            <div class="endpoint-name">{{ formatEndpointName(name) }}</div>
            <div class="endpoint-status">
              <span :class="['status-badge', endpoint.status]">
                {{ endpoint.status.toUpperCase() }}
              </span>
              <span v-if="endpoint.response_time" class="response-time">
                {{ endpoint.response_time }}ms
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- System Health -->
      <div class="dashboard-card">
        <h2>System Health</h2>
        <div class="health-grid">
          <div 
            v-for="(check, name) in dashboardData.system_health" 
            :key="name"
            :class="['health-item', check.status || 'unknown']"
          >
            <div class="health-name">{{ formatHealthName(name) }}</div>
            <div class="health-status">
              <span :class="['status-badge', check.status || 'unknown']">
                {{ (check.status || 'unknown').toUpperCase() }}
              </span>
              <span v-if="check.message" class="health-message">
                {{ check.message }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="dashboard-card full-width">
        <h2>Recent Activity</h2>
        <div class="activity-list">
          <div 
            v-for="activity in dashboardData.recent_activity" 
            :key="activity.id"
            class="activity-item"
          >
            <div class="activity-time">{{ formatTime(activity.timestamp) }}</div>
            <div class="activity-description">{{ activity.description }}</div>
            <div :class="['activity-status', activity.type]">{{ activity.type }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export default {
  name: 'MonitoringDashboard',
  setup() {
    const loading = ref(false)
    const lastUpdated = ref(new Date())
    const dashboardData = reactive({
      uptime: {},
      system_health: {},
      recent_activity: []
    })

    const formatTime = (date) => {
      return new Date(date).toLocaleString()
    }

    const formatEndpointName = (name) => {
      return name.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }

    const formatHealthName = (name) => {
      return name.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }

    const fetchDashboardData = async () => {
      try {
        loading.value = true
        const response = await axios.get('/api/monitoring/dashboard')
        Object.assign(dashboardData, response.data)
        lastUpdated.value = new Date()
      } catch (error) {
        console.error('Failed to fetch dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const refreshData = () => {
      fetchDashboardData()
    }

    let interval = null

    onMounted(() => {
      fetchDashboardData()
      // Refresh every 30 seconds
      interval = setInterval(fetchDashboardData, 30000)
    })

    onUnmounted(() => {
      if (interval) {
        clearInterval(interval)
      }
    })

    return {
      loading,
      lastUpdated,
      dashboardData,
      formatTime,
      formatEndpointName,
      formatHealthName,
      refreshData
    }
  }
}
</script>

<style scoped>
.monitoring-dashboard {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.dashboard-header h1 {
  color: #1f2937;
  margin: 0;
}

.last-updated {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.refresh-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.refresh-btn:hover:not(:disabled) {
  background: #2563eb;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
}

.dashboard-card {
  background: white;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.dashboard-card.full-width {
  grid-column: 1 / -1;
}

.dashboard-card h2 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.uptime-grid, .health-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.uptime-item, .health-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
}

.uptime-item.up, .health-item.healthy {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.uptime-item.down, .health-item.unhealthy {
  background: #fef2f2;
  border-color: #fecaca;
}

.uptime-item.unknown, .health-item.unknown {
  background: #fffbeb;
  border-color: #fed7aa;
}

.endpoint-name, .health-name {
  font-weight: 600;
  color: #1f2937;
}

.endpoint-status, .health-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.up, .status-badge.healthy {
  background: #dcfce7;
  color: #166534;
}

.status-badge.down, .status-badge.unhealthy {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.unknown {
  background: #fef3c7;
  color: #92400e;
}

.response-time, .health-message {
  font-size: 0.875rem;
  color: #6b7280;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
}

.activity-time {
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
}

.activity-description {
  color: #1f2937;
}

.activity-status {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.activity-status.info {
  background: #dbeafe;
  color: #1e40af;
}

.activity-status.warning {
  background: #fef3c7;
  color: #92400e;
}

.activity-status.error {
  background: #fee2e2;
  color: #991b1b;
}

.activity-status.success {
  background: #dcfce7;
  color: #166534;
}
</style>
