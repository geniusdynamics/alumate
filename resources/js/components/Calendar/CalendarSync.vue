<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Calendar Integration
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Connect your calendars to sync events and manage availability
          </p>
        </div>
        <button
          @click="refreshConnections"
          :disabled="loading"
          class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
        >
          <svg
            class="w-4 h-4 mr-2"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Refresh
        </button>
      </div>

      <!-- Connection Status Overview -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Total Connections</p>
              <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">{{ syncStatus.summary?.total_connections || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-900 dark:text-green-100">Active</p>
              <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ syncStatus.summary?.active_connections || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-red-900 dark:text-red-100">Failed Syncs</p>
              <p class="text-lg font-semibold text-red-600 dark:text-red-400">{{ syncStatus.summary?.failed_syncs || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Last Sync</p>
              <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                {{ syncStatus.summary?.last_sync ? formatDate(syncStatus.summary.last_sync) : 'Never' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar Providers -->
      <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-white">Calendar Providers</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Google Calendar -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-8 h-8" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">Google Calendar</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getConnectionStatus('google') }}
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  v-if="!isConnected('google')"
                  @click="connectProvider('google')"
                  :disabled="loading"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  Connect
                </button>
                <template v-else>
                  <button
                    @click="syncProvider('google')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    Sync
                  </button>
                  <button
                    @click="disconnectProvider('google')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50"
                  >
                    Disconnect
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- Microsoft Outlook -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-8 h-8" viewBox="0 0 24 24">
                    <path fill="#0078D4" d="M7 3a4 4 0 00-4 4v10a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7zm5 2h5a2 2 0 012 2v10a2 2 0 01-2 2h-5V5zm-2 2v12H7a2 2 0 01-2-2V7a2 2 0 012-2h3z"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">Microsoft Outlook</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getConnectionStatus('outlook') }}
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  v-if="!isConnected('outlook')"
                  @click="connectProvider('outlook')"
                  :disabled="loading"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  Connect
                </button>
                <template v-else>
                  <button
                    @click="syncProvider('outlook')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    Sync
                  </button>
                  <button
                    @click="disconnectProvider('outlook')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50"
                  >
                    Disconnect
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- Apple Calendar -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-8 h-8" viewBox="0 0 24 24">
                    <path fill="#000000" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">Apple Calendar</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getConnectionStatus('apple') }}
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  v-if="!isConnected('apple')"
                  @click="connectProvider('apple')"
                  :disabled="loading"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  Connect
                </button>
                <template v-else>
                  <button
                    @click="syncProvider('apple')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    Sync
                  </button>
                  <button
                    @click="disconnectProvider('apple')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50"
                  >
                    Disconnect
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- CalDAV -->
          <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">CalDAV</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getConnectionStatus('caldav') }}
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  v-if="!isConnected('caldav')"
                  @click="connectProvider('caldav')"
                  :disabled="loading"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  Connect
                </button>
                <template v-else>
                  <button
                    @click="syncProvider('caldav')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                  >
                    Sync
                  </button>
                  <button
                    @click="disconnectProvider('caldav')"
                    :disabled="loading"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50"
                  >
                    Disconnect
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Sync Activity -->
      <div v-if="connections.length > 0" class="mt-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Recent Activity</h4>
        <div class="space-y-3">
          <div
            v-for="connection in connections"
            :key="connection.id"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div class="flex items-center">
              <div
                class="w-2 h-2 rounded-full mr-3"
                :class="{
                  'bg-green-500': connection.sync_status === 'success',
                  'bg-red-500': connection.sync_status === 'failed',
                  'bg-yellow-500': connection.sync_status === 'pending',
                  'bg-gray-400': !connection.sync_status
                }"
              ></div>
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                  {{ connection.provider }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ connection.last_sync_at ? `Last synced: ${formatDate(connection.last_sync_at)}` : 'Never synced' }}
                </p>
              </div>
            </div>
            <div class="text-right">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': connection.is_active,
                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !connection.is_active
                }"
              >
                {{ connection.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface CalendarConnection {
  id: number
  provider: string
  is_active: boolean
  last_sync_at: string | null
  sync_status: string | null
  sync_error?: string
}

interface SyncStatus {
  summary: {
    total_connections: number
    active_connections: number
    failed_syncs: number
    last_sync: string | null
  }
  connections: CalendarConnection[]
}

const loading = ref(false)
const connections = ref<CalendarConnection[]>([])
const syncStatus = ref<SyncStatus>({ summary: { total_connections: 0, active_connections: 0, failed_syncs: 0, last_sync: null }, connections: [] })

const isConnected = (provider: string): boolean => {
  return connections.value.some(conn => conn.provider === provider && conn.is_active)
}

const getConnectionStatus = (provider: string): string => {
  const connection = connections.value.find(conn => conn.provider === provider)
  if (!connection) return 'Not connected'
  if (!connection.is_active) return 'Disconnected'
  if (connection.sync_status === 'failed') return 'Sync failed'
  if (connection.sync_status === 'success') return 'Connected'
  return 'Connected'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const fetchConnections = async (): Promise<void> => {
  try {
    loading.value = true
    const response = await fetch('/api/calendar/connections')
    const data = await response.json()
    connections.value = data.connections || []
  } catch (error) {
    console.error('Failed to fetch calendar connections:', error)
  } finally {
    loading.value = false
  }
}

const fetchSyncStatus = async (): Promise<void> => {
  try {
    const response = await fetch('/api/calendar/sync-status')
    const data = await response.json()
    syncStatus.value = data
  } catch (error) {
    console.error('Failed to fetch sync status:', error)
  }
}

const refreshConnections = async (): Promise<void> => {
  await Promise.all([fetchConnections(), fetchSyncStatus()])
}

const connectProvider = async (provider: string): Promise<void> => {
  // This would typically open OAuth flow
  // For now, we'll simulate the connection
  console.log(`Connecting to ${provider}...`)
  
  // In a real implementation, this would:
  // 1. Open OAuth popup/redirect
  // 2. Handle OAuth callback
  // 3. Send credentials to backend
  // 4. Refresh connections
  
  // Placeholder for OAuth integration
  alert(`${provider} connection would open OAuth flow here`)
}

const disconnectProvider = async (provider: string): Promise<void> => {
  const connection = connections.value.find(conn => conn.provider === provider)
  if (!connection) return

  try {
    loading.value = true
    const response = await fetch(`/api/calendar/connections/${connection.id}/disconnect`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      await refreshConnections()
    } else {
      console.error('Failed to disconnect calendar')
    }
  } catch (error) {
    console.error('Error disconnecting calendar:', error)
  } finally {
    loading.value = false
  }
}

const syncProvider = async (provider: string): Promise<void> => {
  const connection = connections.value.find(conn => conn.provider === provider)
  if (!connection) return

  try {
    loading.value = true
    const response = await fetch(`/api/calendar/connections/${connection.id}/sync`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      await refreshConnections()
    } else {
      console.error('Failed to sync calendar')
    }
  } catch (error) {
    console.error('Error syncing calendar:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  refreshConnections()
})
</script>