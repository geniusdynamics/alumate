/**
 * Push Notification Service
 * Handles push notification subscription, management, and preferences
 */

class PushNotificationService {
    constructor() {
        this.swRegistration = null;
        this.subscription = null;
        this.vapidPublicKey = null;
        this.isSupported = this.checkSupport();
        this.preferences = this.loadPreferences();
        
        this.init();
    }
    
    checkSupport() {
        return (
            'Notification' in window &&
            'PushManager' in window &&
            'serviceWorker' in navigator
        );
    }
    
    async init() {
        if (!this.isSupported) {
            console.warn('Push notifications are not supported in this browser');
            return;
        }
        
        // Wait for service worker registration
        if (navigator.serviceWorker.controller) {
            this.swRegistration = await navigator.serviceWorker.getRegistration();
        } else {
            navigator.serviceWorker.addEventListener('controllerchange', async () => {
                this.swRegistration = await navigator.serviceWorker.getRegistration();
            });
        }
        
        // Load VAPID key
        await this.loadVapidKey();
        
        // Check existing subscription
        await this.checkExistingSubscription();
        
        console.log('Push Notification Service initialized');
    }
    
    async loadVapidKey() {
        try {
            const response = await fetch('/api/push/vapid-key', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.vapidPublicKey = data.publicKey;
            } else {
                console.error('Failed to load VAPID key:', response.statusText);
            }
        } catch (error) {
            console.error('Error loading VAPID key:', error);
        }
    }
    
    async checkExistingSubscription() {
        if (!this.swRegistration) return;
        
        try {
            this.subscription = await this.swRegistration.pushManager.getSubscription();
            
            if (this.subscription) {
                // Verify subscription with server
                await this.verifySubscription();
            }
        } catch (error) {
            console.error('Error checking existing subscription:', error);
        }
    }
    
    async verifySubscription() {
        try {
            const response = await fetch('/api/push/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint
                })
            });
            
            if (!response.ok) {
                console.warn('Subscription verification failed, may need to resubscribe');
                this.subscription = null;
            }
        } catch (error) {
            console.error('Error verifying subscription:', error);
        }
    }
    
    async requestPermission() {
        if (!this.isSupported) {
            throw new Error('Push notifications are not supported');
        }
        
        const permission = await Notification.requestPermission();
        
        switch (permission) {
            case 'granted':
                console.log('Notification permission granted');
                return true;
            case 'denied':
                console.log('Notification permission denied');
                this.showPermissionDeniedHelp();
                return false;
            case 'default':
                console.log('Notification permission dismissed');
                return false;
            default:
                return false;
        }
    }
    
    async subscribe() {
        if (!this.swRegistration || !this.vapidPublicKey) {
            throw new Error('Service worker or VAPID key not available');
        }
        
        if (Notification.permission !== 'granted') {
            const granted = await this.requestPermission();
            if (!granted) {
                throw new Error('Notification permission not granted');
            }
        }
        
        try {
            this.subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });
            
            // Send subscription to server
            await this.sendSubscriptionToServer();
            
            // Save subscription locally
            this.saveSubscription();
            
            console.log('Push notification subscription successful');
            return this.subscription;
            
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
            throw error;
        }
    }
    
    async unsubscribe() {
        if (!this.subscription) {
            console.log('No active subscription to unsubscribe from');
            return;
        }
        
        try {
            // Unsubscribe from push manager
            await this.subscription.unsubscribe();
            
            // Notify server
            await this.removeSubscriptionFromServer();
            
            // Clear local subscription
            this.subscription = null;
            this.clearSubscription();
            
            console.log('Push notification unsubscription successful');
            
        } catch (error) {
            console.error('Failed to unsubscribe from push notifications:', error);
            throw error;
        }
    }
    
    async sendSubscriptionToServer() {
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                subscription: this.subscription.toJSON(),
                preferences: this.preferences
            })
        });
        
        if (!response.ok) {
            throw new Error(`Failed to send subscription to server: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    async removeSubscriptionFromServer() {
        const response = await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                endpoint: this.subscription.endpoint
            })
        });
        
        if (!response.ok) {
            console.warn(`Failed to remove subscription from server: ${response.statusText}`);
        }
    }
    
    async updatePreferences(newPreferences) {
        this.preferences = { ...this.preferences, ...newPreferences };
        this.savePreferences();
        
        if (this.subscription) {
            try {
                const response = await fetch('/api/push/preferences', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        endpoint: this.subscription.endpoint,
                        preferences: this.preferences
                    })
                });
                
                if (!response.ok) {
                    console.warn('Failed to update preferences on server');
                }
            } catch (error) {
                console.error('Error updating preferences:', error);
            }
        }
    }
    
    async sendTestNotification() {
        if (!this.subscription) {
            throw new Error('No active subscription');
        }
        
        try {
            const response = await fetch('/api/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint
                })
            });
            
            if (!response.ok) {
                throw new Error(`Failed to send test notification: ${response.statusText}`);
            }
            
            console.log('Test notification sent');
            
        } catch (error) {
            console.error('Error sending test notification:', error);
            throw error;
        }
    }
    
    // Utility methods
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
    
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    loadPreferences() {
        try {
            const stored = localStorage.getItem('push-notification-preferences');
            return stored ? JSON.parse(stored) : this.getDefaultPreferences();
        } catch (error) {
            console.error('Error loading notification preferences:', error);
            return this.getDefaultPreferences();
        }
    }
    
    savePreferences() {
        try {
            localStorage.setItem('push-notification-preferences', JSON.stringify(this.preferences));
        } catch (error) {
            console.error('Error saving notification preferences:', error);
        }
    }
    
    getDefaultPreferences() {
        return {
            posts: true,
            comments: true,
            likes: true,
            connections: true,
            messages: true,
            events: true,
            jobs: true,
            mentions: true,
            digest: true,
            quietHours: {
                enabled: true,
                start: '22:00',
                end: '08:00'
            },
            frequency: 'immediate' // immediate, hourly, daily
        };
    }
    
    saveSubscription() {
        if (this.subscription) {
            try {
                localStorage.setItem('push-subscription', JSON.stringify(this.subscription.toJSON()));
            } catch (error) {
                console.error('Error saving subscription:', error);
            }
        }
    }
    
    clearSubscription() {
        try {
            localStorage.removeItem('push-subscription');
        } catch (error) {
            console.error('Error clearing subscription:', error);
        }
    }
    
    showPermissionDeniedHelp() {
        // This could trigger a modal or notification with instructions
        console.log('To enable notifications, please:');
        console.log('1. Click the lock icon in your browser address bar');
        console.log('2. Set notifications to "Allow"');
        console.log('3. Refresh the page and try again');
        
        // Dispatch custom event for UI to handle
        window.dispatchEvent(new CustomEvent('push-permission-denied', {
            detail: {
                message: 'Notifications are blocked. Please enable them in your browser settings.',
                instructions: [
                    'Click the lock icon in your browser address bar',
                    'Set notifications to "Allow"',
                    'Refresh the page and try again'
                ]
            }
        }));
    }
    
    // Public API
    isSubscribed() {
        return !!this.subscription;
    }
    
    getPermissionStatus() {
        return Notification.permission;
    }
    
    getPreferences() {
        return { ...this.preferences };
    }
    
    isSupported() {
        return this.isSupported;
    }
    
    getSubscriptionInfo() {
        if (!this.subscription) return null;
        
        return {
            endpoint: this.subscription.endpoint,
            keys: this.subscription.keys,
            expirationTime: this.subscription.expirationTime
        };
    }
}

export default PushNotificationService;