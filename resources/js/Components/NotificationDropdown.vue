<template>
    <div class="notification-dropdown relative">
        <!-- Notification Bell Button -->
        <button
            @click="toggleDropdown"
            class="relative rounded-lg p-2 text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'text-blue-600': hasUnread }"
        >
            <i class="fas fa-bell text-xl"></i>

            <!-- Unread Count Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-medium text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>

            <!-- Notification Dot -->
            <span v-else-if="hasUnread" class="absolute -right-1 -top-1 h-3 w-3 rounded-full bg-red-500"></span>
        </button>

        <!-- Dropdown Panel -->
        <div
            v-if="showDropdown"
            class="absolute right-0 z-50 mt-2 max-h-96 w-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                <div class="flex space-x-2">
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-sm text-blue-600 hover:text-blue-800"
                        :disabled="markingAllRead"
                    >
                        <i v-if="markingAllRead" class="fas fa-spinner fa-spin mr-1"></i>
                        Mark all read
                    </button>
                    <button @click="showPreferences = true" class="text-gray-400 hover:text-gray-600" title="Notification settings">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-4 text-center">
                <i class="fas fa-spinner fa-spin text-gray-400"></i>
                <p class="mt-2 text-gray-500">Loading notifications...</p>
            </div>

            <!-- Notifications List -->
            <div v-else-if="notifications.length > 0" class="max-h-80 overflow-y-auto">
                <NotificationItem
                    v-for="notification in notifications"
                    :key="notification.id"
                    :notification="notification"
                    @mark-read="handleMarkAsRead"
                    @delete="handleDelete"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="p-8 text-center">
                <i class="fas fa-bell-slash mb-4 text-4xl text-gray-300"></i>
                <p class="text-gray-500">No notifications yet</p>
                <p class="mt-1 text-sm text-gray-400">You'll see notifications here when people interact with your posts</p>
            </div>

            <!-- Footer -->
            <div v-if="notifications.length > 0" class="border-t border-gray-200 px-4 py-3 text-center">
                <button @click="viewAllNotifications" class="text-sm font-medium text-blue-600 hover:text-blue-800">View all notifications</button>
            </div>
        </div>

        <!-- Preferences Modal -->
        <NotificationPreferences v-if="showPreferences" @close="showPreferences = false" @updated="handlePreferencesUpdated" />

        <!-- Backdrop -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="closeDropdown"></div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import NotificationItem from './NotificationItem.vue';
import NotificationPreferences from './NotificationPreferences.vue';

const showDropdown = ref(false);
const showPreferences = ref(false);
const loading = ref(false);
const markingAllRead = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const lastFetchTime = ref(null);

// Auto-refresh interval
let refreshInterval = null;

const hasUnread = computed(() => unreadCount.value > 0);

onMounted(() => {
    fetchNotifications();
    startAutoRefresh();
});

onUnmounted(() => {
    stopAutoRefresh();
});

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        fetchNotifications();
    }
};

const closeDropdown = () => {
    showDropdown.value = false;
};

const fetchNotifications = async () => {
    if (loading.value) return;

    loading.value = true;

    try {
        const response = await fetch('/api/notifications?per_page=10');
        const data = await response.json();

        if (data.success) {
            notifications.value = data.notifications.data;
            unreadCount.value = data.unread_count;
            lastFetchTime.value = new Date();
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    } finally {
        loading.value = false;
    }
};

const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/api/notifications/unread-count');
        const data = await response.json();

        if (data.success) {
            unreadCount.value = data.unread_count;
        }
    } catch (error) {
        console.error('Error fetching unread count:', error);
    }
};

const handleMarkAsRead = async (notificationId) => {
    try {
        const response = await fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            // Update local state
            const notification = notifications.value.find((n) => n.id === notificationId);
            if (notification) {
                notification.read_at = new Date().toISOString();
            }
            unreadCount.value = data.unread_count;
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    if (markingAllRead.value) return;

    markingAllRead.value = true;

    try {
        const response = await fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            // Update local state
            notifications.value.forEach((notification) => {
                if (!notification.read_at) {
                    notification.read_at = new Date().toISOString();
                }
            });
            unreadCount.value = 0;
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    } finally {
        markingAllRead.value = false;
    }
};

const handleDelete = async (notificationId) => {
    try {
        const response = await fetch(`/api/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            // Remove from local state
            notifications.value = notifications.value.filter((n) => n.id !== notificationId);
            unreadCount.value = data.unread_count;
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
    }
};

const viewAllNotifications = () => {
    closeDropdown();
    router.visit('/notifications');
};

const handlePreferencesUpdated = () => {
    showPreferences.value = false;
    // Optionally refresh notifications
    fetchNotifications();
};

const startAutoRefresh = () => {
    // Refresh unread count every 30 seconds
    refreshInterval = setInterval(() => {
        if (!showDropdown.value) {
            fetchUnreadCount();
        }
    }, 30000);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

// Listen for real-time notifications (would integrate with WebSocket/Pusher)
const handleRealtimeNotification = (notification) => {
    // Add new notification to the beginning of the list
    notifications.value.unshift(notification);
    unreadCount.value++;

    // Show browser notification if permission granted
    if (Notification.permission === 'granted') {
        new Notification(notification.data.title || 'New notification', {
            body: notification.data.message,
            icon: '/favicon.ico',
        });
    }
};

// Expose method for parent Components
defineExpose({
    refresh: fetchNotifications,
    handleRealtimeNotification,
});
</script>

<style scoped>
.notification-dropdown {
    @apply relative;
}

/* Animation for notification badge */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.notification-dropdown button:hover .bg-red-500 {
    animation: pulse 2s infinite;
}
</style>
