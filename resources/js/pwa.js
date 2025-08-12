/**
 * Progressive Web App (PWA) Integration
 * Handles service worker registration, push notifications, and offline functionality
 */

class PWAManager {
    constructor() {
        this.swRegistration = null;
        this.isOnline = navigator.onLine;
        this.notificationPermission = 'default';
        this.vapidPublicKey = null;
        
        this.init();
    }
    
    async init() {
        // Register service worker
        await this.registerServiceWorker();
        
        // Setup network status monitoring
        this.setupNetworkMonitoring();
        
        // Setup push notifications
        await this.setupPushNotifications();
        
        // Setup app install prompt
        this.setupInstallPrompt();
        
        // Setup background sync
        this.setupBackgroundSync();
        
        // Setup periodic sync (if supported)
        this.setupPeriodicSync();
        
        console.log('PWA Manager initialized');
    }
    
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                this.swRegistration = await navigator.serviceWorker.register('/sw.js', {
                    scope: '/'
                });
                
                console.log('Service Worker registered successfully:', this.swRegistration);
                
                // Handle service worker updates
                this.swRegistration.addEventListener('updatefound', () => {
                    const newWorker = this.swRegistration.installing;
                    
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailableNotification();
                        }
                    });
                });
                
                // Listen for messages from service worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    this.handleServiceWorkerMessage(event.data);
                });
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        } else {
            console.warn('Service Workers are not supported in this browser');
        }
    }
    
    setupNetworkMonitoring() {
        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showNetworkStatusNotification('You\'re back online!', 'success');
            this.syncOfflineActions();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showNetworkStatusNotification('You\'re offline. Some features may be limited.', 'warning');
        });
        
        // Add network status indicator to UI
        this.addNetworkStatusIndicator();
    }
    
    addNetworkStatusIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'network-status-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10000;
            transition: all 0.3s ease;
            display: none;
        `;
        
        document.body.appendChild(indicator);
        
        this.updateNetworkIndicator();
    }
    
    updateNetworkIndicator() {
        const indicator = document.getElementById('network-status-indicator');
        if (!indicator) return;
        
        if (this.isOnline) {
            indicator.style.background = '#10b981';
            indicator.style.color = 'white';
            indicator.textContent = '🟢 Online';
            indicator.style.display = 'none'; // Hide when online
        } else {
            indicator.style.background = '#ef4444';
            indicator.style.color = 'white';
            indicator.textContent = '🔴 Offline';
            indicator.style.display = 'block';
        }
    }
    
    async setupPushNotifications() {
        if (!('Notification' in window) || !('PushManager' in window)) {
            console.warn('Push notifications are not supported');
            return;
        }
        
        this.notificationPermission = Notification.permission;
        
        // Load VAPID public key from server
        try {
            const response = await fetch('/api/push/vapid-key');
            if (response.ok) {
                const data = await response.json();
                this.vapidPublicKey = data.publicKey;
            }
        } catch (error) {
            console.error('Failed to load VAPID key:', error);
        }
    }
    
    async requestNotificationPermission() {
        if (this.notificationPermission === 'granted') {
            return true;
        }
        
        if (this.notificationPermission === 'denied') {
            this.showNotificationPermissionDeniedMessage();
            return false;
        }
        
        const permission = await Notification.requestPermission();
        this.notificationPermission = permission;
        
        if (permission === 'granted') {
            await this.subscribeToPushNotifications();
            return true;
        }
        
        return false;
    }
    
    async subscribeToPushNotifications() {
        if (!this.swRegistration || !this.vapidPublicKey) {
            console.error('Service worker or VAPID key not available');
            return;
        }
        
        try {
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });
            
            // Send subscription to server
            await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(subscription)
            });
            
            console.log('Push notification subscription successful');
            
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
        }
    }
    
    async unsubscribeFromPushNotifications() {
        if (!this.swRegistration) return;
        
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
                
                // Notify server
                await fetch('/api/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint })
                });
                
                console.log('Push notification unsubscription successful');
            }
        } catch (error) {
            console.error('Failed to unsubscribe from push notifications:', error);
        }
    }
    
    setupInstallPrompt() {
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            
            // Show custom install button
            this.showInstallButton(deferredPrompt);
        });
        
        // Handle app installation
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            this.hideInstallButton();
            this.showInstallSuccessMessage();
        });
    }
    
    showInstallButton(deferredPrompt) {
        const installButton = document.createElement('button');
        installButton.id = 'pwa-install-button';
        installButton.innerHTML = '📱 Install App';
        installButton.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            z-index: 10000;
            transition: all 0.3s ease;
        `;
        
        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                
                deferredPrompt = null;
                this.hideInstallButton();
            }
        });
        
        installButton.addEventListener('mouseenter', () => {
            installButton.style.transform = 'scale(1.05)';
        });
        
        installButton.addEventListener('mouseleave', () => {
            installButton.style.transform = 'scale(1)';
        });
        
        document.body.appendChild(installButton);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (document.getElementById('pwa-install-button')) {
                this.hideInstallButton();
            }
        }, 10000);
    }
    
    hideInstallButton() {
        const button = document.getElementById('pwa-install-button');
        if (button) {
            button.remove();
        }
    }
    
    setupBackgroundSync() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            // Background sync is supported
            console.log('Background sync is supported');
        } else {
            console.warn('Background sync is not supported');
        }
    }
    
    async setupPeriodicSync() {
        if ('serviceWorker' in navigator && 'periodicSync' in window.ServiceWorkerRegistration.prototype) {
            try {
                await this.swRegistration.periodicSync.register('background-sync', {
                    minInterval: 24 * 60 * 60 * 1000, // 24 hours
                });
                console.log('Periodic background sync registered');
            } catch (error) {
                console.error('Periodic background sync registration failed:', error);
            }
        } else {
            console.warn('Periodic background sync is not supported');
        }
    }
    
    async syncOfflineActions() {
        if (this.swRegistration && 'sync' in window.ServiceWorkerRegistration.prototype) {
            try {
                await this.swRegistration.sync.register('background-sync');
                console.log('Background sync registered');
            } catch (error) {
                console.error('Background sync registration failed:', error);
            }
        }
    }
    
    handleServiceWorkerMessage(data) {
        switch (data.type) {
            case 'CACHE_UPDATED':
                this.showCacheUpdateNotification();
                break;
            case 'OFFLINE_ACTION_QUEUED':
                this.showOfflineActionQueuedNotification();
                break;
            case 'BACKGROUND_SYNC_SUCCESS':
                this.showBackgroundSyncSuccessNotification();
                break;
            default:
                console.log('Unknown service worker message:', data);
        }
    }
    
    showUpdateAvailableNotification() {
        const notification = this.createNotification(
            'App Update Available',
            'A new version of the app is available. Refresh to update.',
            'info',
            [
                {
                    text: 'Refresh Now',
                    action: () => window.location.reload()
                },
                {
                    text: 'Later',
                    action: () => {}
                }
            ]
        );
        
        this.showNotification(notification);
    }
    
    showNetworkStatusNotification(message, type) {
        this.updateNetworkIndicator();
        
        const notification = this.createNotification(
            'Network Status',
            message,
            type
        );
        
        this.showNotification(notification, 3000);
    }
    
    showNotificationPermissionDeniedMessage() {
        const notification = this.createNotification(
            'Notifications Blocked',
            'To receive notifications, please enable them in your browser settings.',
            'warning'
        );
        
        this.showNotification(notification);
    }
    
    showInstallSuccessMessage() {
        const notification = this.createNotification(
            'App Installed',
            'The Alumni Platform has been installed successfully!',
            'success'
        );
        
        this.showNotification(notification);
    }
    
    showCacheUpdateNotification() {
        const notification = this.createNotification(
            'Content Updated',
            'New content has been cached for offline use.',
            'info'
        );
        
        this.showNotification(notification, 2000);
    }
    
    showOfflineActionQueuedNotification() {
        const notification = this.createNotification(
            'Action Queued',
            'Your action has been saved and will be processed when you\'re back online.',
            'info'
        );
        
        this.showNotification(notification);
    }
    
    showBackgroundSyncSuccessNotification() {
        const notification = this.createNotification(
            'Sync Complete',
            'Your offline actions have been synchronized.',
            'success'
        );
        
        this.showNotification(notification, 2000);
    }
    
    createNotification(title, message, type = 'info', actions = []) {
        return {
            id: Date.now(),
            title,
            message,
            type,
            actions,
            timestamp: new Date()
        };
    }
    
    showNotification(notification, duration = 5000) {
        const container = this.getNotificationContainer();
        const element = this.createNotificationElement(notification);
        
        container.appendChild(element);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(element);
            }, duration);
        }
    }
    
    getNotificationContainer() {
        let container = document.getElementById('pwa-notifications');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'pwa-notifications';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10001;
                max-width: 350px;
            `;
            document.body.appendChild(container);
        }
        
        return container;
    }
    
    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = `pwa-notification pwa-notification-${notification.type}`;
        element.style.cssText = `
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid ${this.getNotificationColor(notification.type)};
            animation: slideIn 0.3s ease;
        `;
        
        const title = document.createElement('div');
        title.style.cssText = 'font-weight: 600; margin-bottom: 4px; color: #1f2937;';
        title.textContent = notification.title;
        
        const message = document.createElement('div');
        message.style.cssText = 'font-size: 14px; color: #6b7280; margin-bottom: 8px;';
        message.textContent = notification.message;
        
        element.appendChild(title);
        element.appendChild(message);
        
        if (notification.actions && notification.actions.length > 0) {
            const actionsContainer = document.createElement('div');
            actionsContainer.style.cssText = 'display: flex; gap: 8px; margin-top: 12px;';
            
            notification.actions.forEach(action => {
                const button = document.createElement('button');
                button.textContent = action.text;
                button.style.cssText = `
                    padding: 6px 12px;
                    border: 1px solid #d1d5db;
                    background: white;
                    border-radius: 4px;
                    font-size: 12px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                `;
                
                button.addEventListener('click', () => {
                    action.action();
                    this.removeNotification(element);
                });
                
                button.addEventListener('mouseenter', () => {
                    button.style.background = '#f3f4f6';
                });
                
                button.addEventListener('mouseleave', () => {
                    button.style.background = 'white';
                });
                
                actionsContainer.appendChild(button);
            });
            
            element.appendChild(actionsContainer);
        }
        
        // Add close button
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '×';
        closeButton.style.cssText = `
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #9ca3af;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        closeButton.addEventListener('click', () => {
            this.removeNotification(element);
        });
        
        element.style.position = 'relative';
        element.appendChild(closeButton);
        
        return element;
    }
    
    removeNotification(element) {
        element.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }
    
    getNotificationColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        return colors[type] || colors.info;
    }
    
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
    
    // Public API methods
    async enableNotifications() {
        return await this.requestNotificationPermission();
    }
    
    async disableNotifications() {
        await this.unsubscribeFromPushNotifications();
    }
    
    getNetworkStatus() {
        return {
            online: this.isOnline,
            effectiveType: navigator.connection?.effectiveType || 'unknown',
            downlink: navigator.connection?.downlink || 0,
            rtt: navigator.connection?.rtt || 0
        };
    }
    
    async clearCache() {
        if ('caches' in window) {
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames.map(cacheName => caches.delete(cacheName))
            );
            console.log('All caches cleared');
        }
    }
    
    async updateServiceWorker() {
        if (this.swRegistration) {
            await this.swRegistration.update();
            console.log('Service worker update check completed');
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize PWA Manager
const pwaManager = new PWAManager();

// Export for global access
window.pwaManager = pwaManager;

export default pwaManager;