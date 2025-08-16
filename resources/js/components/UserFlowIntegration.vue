<template>
    <div class="user-flow-integration">
        <!-- Real-time Notifications -->
        <div 
            v-if="notifications.length > 0"
            class="fixed top-4 right-4 z-50 space-y-2"
        >
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="getNotificationClass(notification.type)"
                class="p-4 rounded-md shadow-lg max-w-sm transition-all duration-300"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium">{{ notification.message }}</p>
                    <button
                        @click="dismissNotification(notification.id)"
                        class="ml-2 text-white hover:text-gray-200"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Connection Status Updates -->
        <div v-if="connectionUpdates.length > 0" class="connection-updates">
            <div
                v-for="update in connectionUpdates"
                :key="update.id"
                class="connection-update"
                :data-user-id="update.userId"
                :data-connection-status="update.status"
            >
                <!-- This will be used by the UserFlowIntegration service to update UI -->
            </div>
        </div>

        <!-- Job Status Updates -->
        <div v-if="jobUpdates.length > 0" class="job-updates">
            <div
                v-for="update in jobUpdates"
                :key="update.id"
                class="job-update"
                :data-job-id="update.jobId"
                :data-saved="update.saved"
            >
                <!-- This will be used by the UserFlowIntegration service to update UI -->
            </div>
        </div>

        <!-- Event Registration Updates -->
        <div v-if="eventUpdates.length > 0" class="event-updates">
            <div
                v-for="update in eventUpdates"
                :key="update.id"
                class="event-update"
                :data-event-id="update.eventId"
                :data-registered="update.registered"
            >
                <!-- This will be used by the UserFlowIntegration service to update UI -->
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates'
import userFlowIntegration from '@/services/UserFlowIntegration'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const { props } = usePage()
const realTimeUpdates = useRealTimeUpdates()

const notifications = reactive([])
const connectionUpdates = reactive([])
const jobUpdates = reactive([])
const eventUpdates = reactive([])

onMounted(() => {
    // Set up real-time event listeners
    realTimeUpdates.onPostCreated((post) => {
        showNotification('New post from your network!', 'info')
        // Trigger timeline refresh if on social pages
        if (window.location.pathname.includes('/social/')) {
            userFlowIntegration.triggerCallback('postCreated', post)
        }
    })
    
    realTimeUpdates.onConnectionRequest((connection) => {
        showNotification(`${connection.user.name} sent you a connection request`, 'info')
        connectionUpdates.push({
            id: Date.now(),
            userId: connection.user_id,
            status: 'pending'
        })
    })
    
    realTimeUpdates.onMentorshipRequest((request) => {
        showNotification(`New mentorship request from ${request.mentee.name}`, 'info')
    })
    
    realTimeUpdates.onEventUpdate((event) => {
        showNotification(`Event "${event.title}" has been updated`, 'info')
    })
    
    // Set up user flow integration callbacks
    userFlowIntegration.on('notification', (notification) => {
        notifications.push(notification)
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            dismissNotification(notification.id)
        }, 5000)
    })
    
    userFlowIntegration.on('connectionRequested', (connection) => {
        connectionUpdates.push({
            id: Date.now(),
            userId: connection.connected_user_id,
            status: 'pending'
        })
    })
    
    userFlowIntegration.on('connectionAccepted', (connection) => {
        connectionUpdates.push({
            id: Date.now(),
            userId: connection.user_id,
            status: 'connected'
        })
    })
    
    userFlowIntegration.on('jobSaved', (jobId) => {
        jobUpdates.push({
            id: Date.now(),
            jobId: jobId,
            saved: true
        })
    })
    
    userFlowIntegration.on('eventRegistered', (registration) => {
        eventUpdates.push({
            id: Date.now(),
            eventId: registration.event_id,
            registered: true
        })
    })
})

const showNotification = (message, type = 'info') => {
    const notification = {
        id: Date.now(),
        message,
        type,
        timestamp: new Date()
    }
    
    notifications.push(notification)
}

const dismissNotification = (id) => {
    const index = notifications.findIndex(n => n.id === id)
    if (index > -1) {
        notifications.splice(index, 1)
    }
}

const getNotificationClass = (type) => {
    const classes = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-black',
        info: 'bg-blue-500 text-white'
    }
    return classes[type] || classes.info
}
</script>

<style scoped>
.user-flow-integration {
    position: relative;
}

.connection-update,
.job-update,
.event-update {
    display: none; /* Hidden elements used for data attributes */
}
</style>