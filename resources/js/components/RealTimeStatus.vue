<template>
  <div class="real-time-status">
    <!-- Connection Status Indicator -->
    <div 
      class="flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-medium transition-all duration-300"
      :class="statusClasses"
    >
      <div 
        class="w-2 h-2 rounded-full transition-all duration-300"
        :class="indicatorClasses"
      />
      <span>{{ statusText }}</span>
      
      <!-- Reconnect Button -->
      <button
        v-if="!isConnected && canReconnect"
        @click="handleReconnect"
        class="ml-2 px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600 transition-colors"
        :disabled="isReconnecting"
      >
        {{ isReconnecting ? 'Reconnecting...' : 'Reconnect' }}
      </button>
    </div>

    <!-- Detailed Status (expandable) -->
    <div v-if="showDetails" class="mt-2 p-3 bg-gray-50 rounded-lg text-xs">
      <div class="grid grid-cols-2 gap-2">
        <div>
          <span class="font-medium">Status:</span>
          <span class="ml-1">{{ connectionState }}</span>
        </div>
        <div>
          <span class="font-medium">Channels:</span>
          <span class="ml-1">{{ channelCount }}</span>
        </div>
        <div>
          <span class="font-medium">Last Activity:</span>
          <span class="ml-1">{{ lastActivityText }}</span>
        </div>
        <div>
          <span class="font-medium">Reconnect Attempts:</span>
          <span class="ml-1">{{ reconnectAttempts }}</span>
        </div>
      </div>
      
      <!-- Channel List -->
      <div v-if="channels.length > 0" class="mt-2">
        <span class="font-medium">Active Channels:</span>
        <div class="mt-1 flex flex-wrap gap-1">
          <span
            v-for="channel in channels"
            :key="channel"
            class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"
          >
            {{ channel }}
          </span>
        </div>
      </div>
    </div>

    <!-- Toggle Details Button -->
    <button
      @click="showDetails = !showDetails"
      class="mt-1 text-xs text-gray-500 hover:text-gray-700 transition-colors"
    >
      {{ showDetails ? 'Hide Details' : 'Show Details' }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRealTimeUpdates } from '@/Composables/useRealTimeUpdates';
import { formatDistanceToNow } from 'date-fns';

// Props
interface Props {
  showDetails?: boolean;
  autoHide?: boolean;
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left';
}

const props = withDefaults(defineProps<Props>(), {
  showDetails: false,
  autoHide: false,
  position: 'top-right',
});

// Composables
const {
  isConnected,
  connectionState,
  reconnectAttempts,
  lastActivity,
  reconnect,
  getConnectionStats,
} = useRealTimeUpdates();

// State
const showDetails = ref(props.showDetails);
const isReconnecting = ref(false);
const stats = ref({
  totalChannels: 0,
  channels: [],
  isConnected: false,
  connectionState: 'disconnected',
  reconnectAttempts: 0,
});

// Computed
const statusText = computed(() => {
  switch (connectionState.value) {
    case 'connected':
      return 'Real-time updates active';
    case 'connecting':
      return 'Connecting...';
    case 'disconnected':
      return 'Real-time updates offline';
    case 'unavailable':
      return 'Real-time updates unavailable';
    case 'failed':
      return 'Connection failed';
    default:
      return 'Unknown status';
  }
});

const statusClasses = computed(() => {
  const base = 'transition-all duration-300';
  
  switch (connectionState.value) {
    case 'connected':
      return `${base} bg-green-100 text-green-800 border border-green-200`;
    case 'connecting':
      return `${base} bg-yellow-100 text-yellow-800 border border-yellow-200`;
    case 'disconnected':
      return `${base} bg-red-100 text-red-800 border border-red-200`;
    case 'unavailable':
      return `${base} bg-gray-100 text-gray-800 border border-gray-200`;
    case 'failed':
      return `${base} bg-red-100 text-red-800 border border-red-200`;
    default:
      return `${base} bg-gray-100 text-gray-600 border border-gray-200`;
  }
});

const indicatorClasses = computed(() => {
  const base = 'transition-all duration-300';
  
  switch (connectionState.value) {
    case 'connected':
      return `${base} bg-green-500 animate-pulse`;
    case 'connecting':
      return `${base} bg-yellow-500 animate-spin`;
    case 'disconnected':
      return `${base} bg-red-500`;
    case 'unavailable':
      return `${base} bg-gray-400`;
    case 'failed':
      return `${base} bg-red-500 animate-pulse`;
    default:
      return `${base} bg-gray-400`;
  }
});

const canReconnect = computed(() => {
  return ['disconnected', 'failed'].includes(connectionState.value) && 
         reconnectAttempts.value < 5;
});

const channelCount = computed(() => stats.value.totalChannels);
const channels = computed(() => stats.value.channels);

const lastActivityText = computed(() => {
  if (!lastActivity.value) return 'Never';
  return formatDistanceToNow(new Date(lastActivity.value), { addSuffix: true });
});

// Methods
const handleReconnect = async () => {
  isReconnecting.value = true;
  try {
    await reconnect();
  } catch (error) {
    console.error('Failed to reconnect:', error);
  } finally {
    isReconnecting.value = false;
  }
};

const updateStats = () => {
  stats.value = getConnectionStats();
};

// Lifecycle
let statsInterval: NodeJS.Timeout;

onMounted(() => {
  updateStats();
  
  // Update stats every 5 seconds
  statsInterval = setInterval(updateStats, 5000);
});

onUnmounted(() => {
  if (statsInterval) {
    clearInterval(statsInterval);
  }
});
</script>

<style scoped>
.real-time-status {
  @apply select-none;
}

/* Position classes for floating status */
.real-time-status.fixed {
  z-index: 1000;
}

.real-time-status.top-right {
  position: fixed;
  top: 1rem;
  right: 1rem;
}

.real-time-status.top-left {
  position: fixed;
  top: 1rem;
  left: 1rem;
}

.real-time-status.bottom-right {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
}

.real-time-status.bottom-left {
  position: fixed;
  bottom: 1rem;
  left: 1rem;
}

/* Animation for connection status changes */
@keyframes pulse-green {
  0%, 100% {
    background-color: rgb(34 197 94);
  }
  50% {
    background-color: rgb(74 222 128);
  }
}

@keyframes pulse-red {
  0%, 100% {
    background-color: rgb(239 68 68);
  }
  50% {
    background-color: rgb(248 113 113);
  }
}

.animate-pulse-green {
  animation: pulse-green 2s infinite;
}

.animate-pulse-red {
  animation: pulse-red 2s infinite;
}
</style>