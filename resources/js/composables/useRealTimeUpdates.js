import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useRealTimeUpdates() {
    const { props } = usePage()
    const isConnected = ref(false)
    const connection = ref(null)
    
    const connect = () => {
        if (window.Echo) {
            // Connect to user's private channel for notifications
            const userChannel = window.Echo.private(`user.${props.auth.user.id}`)
            
            userChannel.listen('PostCreated', (event) => {
                handlePostCreated(event.post)
            })
            
            userChannel.listen('PostUpdated', (event) => {
                handlePostUpdated(event.post)
            })
            
            userChannel.listen('PostEngagement', (event) => {
                handlePostEngagement(event.postId, event.engagement)
            })
            
            userChannel.listen('CommentAdded', (event) => {
                handleCommentAdded(event.postId, event.comment)
            })
            
            userChannel.listen('ConnectionRequest', (event) => {
                handleConnectionRequest(event.connection)
            })
            
            userChannel.listen('MentorshipRequest', (event) => {
                handleMentorshipRequest(event.request)
            })
            
            userChannel.listen('EventUpdate', (event) => {
                handleEventUpdate(event.event)
            })
            
            connection.value = userChannel
            isConnected.value = true
        }
    }
    
    const disconnect = () => {
        if (connection.value) {
            connection.value.stopListening()
            connection.value = null
            isConnected.value = false
        }
    }
    
    // Event handlers
    const postCreatedCallbacks = ref([])
    const postUpdatedCallbacks = ref([])
    const postEngagementCallbacks = ref([])
    const commentAddedCallbacks = ref([])
    const connectionRequestCallbacks = ref([])
    const mentorshipRequestCallbacks = ref([])
    const eventUpdateCallbacks = ref([])
    
    const handlePostCreated = (post) => {
        postCreatedCallbacks.value.forEach(callback => callback(post))
    }
    
    const handlePostUpdated = (post) => {
        postUpdatedCallbacks.value.forEach(callback => callback(post))
    }
    
    const handlePostEngagement = (postId, engagement) => {
        postEngagementCallbacks.value.forEach(callback => callback(postId, engagement))
    }
    
    const handleCommentAdded = (postId, comment) => {
        commentAddedCallbacks.value.forEach(callback => callback(postId, comment))
    }
    
    const handleConnectionRequest = (connection) => {
        connectionRequestCallbacks.value.forEach(callback => callback(connection))
    }
    
    const handleMentorshipRequest = (request) => {
        mentorshipRequestCallbacks.value.forEach(callback => callback(request))
    }
    
    const handleEventUpdate = (event) => {
        eventUpdateCallbacks.value.forEach(callback => callback(event))
    }
    
    // Subscription methods
    const onPostCreated = (callback) => {
        postCreatedCallbacks.value.push(callback)
    }
    
    const onPostUpdated = (callback) => {
        postUpdatedCallbacks.value.push(callback)
    }
    
    const onPostEngagement = (callback) => {
        postEngagementCallbacks.value.push(callback)
    }
    
    const onCommentAdded = (callback) => {
        commentAddedCallbacks.value.push(callback)
    }
    
    const onConnectionRequest = (callback) => {
        connectionRequestCallbacks.value.push(callback)
    }
    
    const onMentorshipRequest = (callback) => {
        mentorshipRequestCallbacks.value.push(callback)
    }
    
    const onEventUpdate = (callback) => {
        eventUpdateCallbacks.value.push(callback)
    }
    
    onMounted(() => {
        connect()
    })
    
    onUnmounted(() => {
        disconnect()
    })
    
    return {
        isConnected,
        connect,
        disconnect,
        onPostCreated,
        onPostUpdated,
        onPostEngagement,
        onCommentAdded,
        onConnectionRequest,
        onMentorshipRequest,
        onEventUpdate
    }
}