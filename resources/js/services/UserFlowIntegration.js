import { router } from '@inertiajs/vue3'

class UserFlowIntegration {
    constructor() {
        this.notifications = []
        this.callbacks = {}
    }
    
    // Social Flow Integration
    async createPostAndRefreshTimeline(postData) {
        try {
            const response = await fetch('/api/posts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(postData)
            })
            
            const result = await response.json()
            
            if (result.success) {
                // Trigger real-time update
                this.triggerCallback('postCreated', result.data)
                
                // Show success notification
                this.showNotification('Post created successfully!', 'success')
                
                // Refresh timeline if on timeline page
                if (route().current('social.timeline')) {
                    router.reload({ only: ['posts'] })
                }
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to create post')
            }
        } catch (error) {
            this.showNotification('Failed to create post: ' + error.message, 'error')
            throw error
        }
    }
    
    async updatePostAndRefresh(postId, updateData) {
        try {
            const response = await fetch(`/api/posts/${postId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(updateData)
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('postUpdated', result.data)
                this.showNotification('Post updated successfully!', 'success')
                return result.data
            } else {
                throw new Error(result.message || 'Failed to update post')
            }
        } catch (error) {
            this.showNotification('Failed to update post: ' + error.message, 'error')
            throw error
        }
    }
    
    async deletePostAndRefresh(postId) {
        try {
            const response = await fetch(`/api/posts/${postId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('postDeleted', postId)
                this.showNotification('Post deleted successfully!', 'success')
                
                // Refresh timeline
                if (route().current('social.timeline')) {
                    router.reload({ only: ['posts'] })
                }
                
                return true
            } else {
                throw new Error(result.message || 'Failed to delete post')
            }
        } catch (error) {
            this.showNotification('Failed to delete post: ' + error.message, 'error')
            throw error
        }
    }
    
    // Alumni Networking Flow Integration
    async sendConnectionRequestAndUpdate(userId, message = '') {
        try {
            const response = await fetch('/api/connections/request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ user_id: userId, message })
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('connectionRequested', result.data)
                this.showNotification('Connection request sent!', 'success')
                
                // Update UI to show pending status
                this.updateConnectionStatus(userId, 'pending')
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to send connection request')
            }
        } catch (error) {
            this.showNotification('Failed to send connection request: ' + error.message, 'error')
            throw error
        }
    }
    
    async acceptConnectionAndUpdate(connectionId) {
        try {
            const response = await fetch(`/api/connections/${connectionId}/accept`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('connectionAccepted', result.data)
                this.showNotification('Connection accepted!', 'success')
                
                // Navigate to connections page or refresh current page
                if (route().current('alumni.*')) {
                    router.reload({ only: ['connections', 'suggestedConnections'] })
                }
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to accept connection')
            }
        } catch (error) {
            this.showNotification('Failed to accept connection: ' + error.message, 'error')
            throw error
        }
    }
    
    // Career Services Flow Integration
    async saveJobAndUpdate(jobId) {
        try {
            const response = await fetch(`/api/jobs/${jobId}/save`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('jobSaved', jobId)
                this.showNotification('Job saved to your list!', 'success')
                
                // Update job card UI
                this.updateJobSavedStatus(jobId, true)
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to save job')
            }
        } catch (error) {
            this.showNotification('Failed to save job: ' + error.message, 'error')
            throw error
        }
    }
    
    async applyToJobAndTrack(jobId, applicationData) {
        try {
            const response = await fetch(`/api/jobs/${jobId}/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(applicationData)
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('jobApplicationSubmitted', result.data)
                this.showNotification('Application submitted successfully!', 'success')
                
                // Navigate to applications tracking page
                router.visit(route('graduate.applications'))
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to submit application')
            }
        } catch (error) {
            this.showNotification('Failed to submit application: ' + error.message, 'error')
            throw error
        }
    }
    
    async updateCareerTimelineAndRefresh(timelineData) {
        try {
            const response = await fetch('/api/career/timeline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(timelineData)
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('careerTimelineUpdated', result.data)
                this.showNotification('Career timeline updated!', 'success')
                
                // Refresh career timeline page
                if (route().current('career.timeline')) {
                    router.reload({ only: ['timeline', 'milestones'] })
                }
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to update career timeline')
            }
        } catch (error) {
            this.showNotification('Failed to update career timeline: ' + error.message, 'error')
            throw error
        }
    }
    
    // Events Flow Integration
    async registerForEventAndUpdate(eventId) {
        try {
            const response = await fetch(`/api/events/${eventId}/register`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('eventRegistered', result.data)
                this.showNotification('Successfully registered for event!', 'success')
                
                // Update event card UI
                this.updateEventRegistrationStatus(eventId, true)
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to register for event')
            }
        } catch (error) {
            this.showNotification('Failed to register for event: ' + error.message, 'error')
            throw error
        }
    }
    
    async submitEventFeedbackAndUpdate(eventId, feedbackData) {
        try {
            const response = await fetch(`/api/events/${eventId}/feedback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(feedbackData)
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('eventFeedbackSubmitted', result.data)
                this.showNotification('Thank you for your feedback!', 'success')
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to submit feedback')
            }
        } catch (error) {
            this.showNotification('Failed to submit feedback: ' + error.message, 'error')
            throw error
        }
    }
    
    // Mentorship Flow Integration
    async requestMentorshipAndUpdate(mentorId, requestData) {
        try {
            const response = await fetch('/api/mentorship/request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ mentor_id: mentorId, ...requestData })
            })
            
            const result = await response.json()
            
            if (result.success) {
                this.triggerCallback('mentorshipRequested', result.data)
                this.showNotification('Mentorship request sent!', 'success')
                
                // Navigate to mentorship dashboard
                router.visit(route('career.mentorship-hub'))
                
                return result.data
            } else {
                throw new Error(result.message || 'Failed to send mentorship request')
            }
        } catch (error) {
            this.showNotification('Failed to send mentorship request: ' + error.message, 'error')
            throw error
        }
    }
    
    // Utility methods
    updateConnectionStatus(userId, status) {
        const elements = document.querySelectorAll(`[data-user-id="${userId}"]`)
        elements.forEach(element => {
            element.setAttribute('data-connection-status', status)
            // Update button text/state based on status
            const button = element.querySelector('.connection-button')
            if (button) {
                switch (status) {
                    case 'pending':
                        button.textContent = 'Request Sent'
                        button.disabled = true
                        break
                    case 'connected':
                        button.textContent = 'Connected'
                        button.disabled = true
                        break
                    default:
                        button.textContent = 'Connect'
                        button.disabled = false
                }
            }
        })
    }
    
    updateJobSavedStatus(jobId, saved) {
        const elements = document.querySelectorAll(`[data-job-id="${jobId}"]`)
        elements.forEach(element => {
            const button = element.querySelector('.save-job-button')
            if (button) {
                button.textContent = saved ? 'Saved' : 'Save Job'
                button.classList.toggle('saved', saved)
            }
        })
    }
    
    updateEventRegistrationStatus(eventId, registered) {
        const elements = document.querySelectorAll(`[data-event-id="${eventId}"]`)
        elements.forEach(element => {
            const button = element.querySelector('.register-button')
            if (button) {
                button.textContent = registered ? 'Registered' : 'Register'
                button.classList.toggle('registered', registered)
            }
        })
    }
    
    showNotification(message, type = 'info') {
        const notification = {
            id: Date.now(),
            message,
            type,
            timestamp: new Date()
        }
        
        this.notifications.push(notification)
        
        // Create notification element
        const notificationEl = document.createElement('div')
        notificationEl.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-black' :
            'bg-blue-500 text-white'
        }`
        notificationEl.textContent = message
        
        document.body.appendChild(notificationEl)
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl)
            }
            this.notifications = this.notifications.filter(n => n.id !== notification.id)
        }, 5000)
        
        // Trigger callback
        this.triggerCallback('notification', notification)
    }
    
    // Callback system
    on(event, callback) {
        if (!this.callbacks[event]) {
            this.callbacks[event] = []
        }
        this.callbacks[event].push(callback)
    }
    
    off(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event] = this.callbacks[event].filter(cb => cb !== callback)
        }
    }
    
    triggerCallback(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => callback(data))
        }
    }
}

// Create singleton instance
const userFlowIntegration = new UserFlowIntegration()

export default userFlowIntegration