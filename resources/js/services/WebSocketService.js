import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

class WebSocketService {
    constructor() {
        this.echo = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.listeners = new Map();
        this.channels = new Map();
        
        this.init();
    }

    /**
     * Initialize Echo instance
     */
    init() {
        try {
            // Configure Pusher
            window.Pusher = Pusher;

            this.echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                    },
                },
                authEndpoint: '/broadcasting/auth',
            });

            this.setupConnectionHandlers();
            this.isConnected = true;
            
            console.log('WebSocket service initialized successfully');
        } catch (error) {
            console.error('Failed to initialize WebSocket service:', error);
            this.scheduleReconnect();
        }
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        // Try to get token from various sources
        const token = localStorage.getItem('auth_token') || 
                     sessionStorage.getItem('auth_token') ||
                     document.querySelector('meta[name="api-token"]')?.getAttribute('content');
        return token;
    }

    /**
     * Setup connection event handlers
     */
    setupConnectionHandlers() {
        if (!this.echo) return;

        // Connection established
        this.echo.connector.pusher.connection.bind('connected', () => {
            console.log('WebSocket connected');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.emit('connected');
        });

        // Connection lost
        this.echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('WebSocket disconnected');
            this.isConnected = false;
            this.emit('disconnected');
            this.scheduleReconnect();
        });

        // Connection error
        this.echo.connector.pusher.connection.bind('error', (error) => {
            console.error('WebSocket error:', error);
            this.emit('error', error);
        });

        // Connection state change
        this.echo.connector.pusher.connection.bind('state_change', (states) => {
            console.log('WebSocket state change:', states.previous, '->', states.current);
            this.emit('state_change', states);
        });
    }

    /**
     * Schedule reconnection attempt
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.emit('max_reconnect_attempts_reached');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
        
        console.log(`Scheduling reconnection attempt ${this.reconnectAttempts} in ${delay}ms`);
        
        setTimeout(() => {
            this.reconnect();
        }, delay);
    }

    /**
     * Reconnect to WebSocket
     */
    reconnect() {
        console.log('Attempting to reconnect...');
        this.disconnect();
        this.init();
    }

    /**
     * Listen to a channel
     */
    channel(channelName) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return null;
        }

        if (this.channels.has(channelName)) {
            return this.channels.get(channelName);
        }

        const channel = this.echo.channel(channelName);
        this.channels.set(channelName, channel);
        return channel;
    }

    /**
     * Listen to a private channel
     */
    private(channelName) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return null;
        }

        const fullChannelName = `private-${channelName}`;
        
        if (this.channels.has(fullChannelName)) {
            return this.channels.get(fullChannelName);
        }

        const channel = this.echo.private(channelName);
        this.channels.set(fullChannelName, channel);
        return channel;
    }

    /**
     * Listen to a presence channel
     */
    join(channelName) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return null;
        }

        const fullChannelName = `presence-${channelName}`;
        
        if (this.channels.has(fullChannelName)) {
            return this.channels.get(fullChannelName);
        }

        const channel = this.echo.join(channelName);
        this.channels.set(fullChannelName, channel);
        return channel;
    }

    /**
     * Leave a channel
     */
    leave(channelName) {
        if (!this.echo) return;

        // Try different channel name formats
        const channelVariants = [
            channelName,
            `private-${channelName}`,
            `presence-${channelName}`
        ];

        channelVariants.forEach(variant => {
            if (this.channels.has(variant)) {
                this.echo.leave(channelName);
                this.channels.delete(variant);
            }
        });
    }

    /**
     * Listen for timeline updates
     */
    listenForTimelineUpdates(userId, callback) {
        const channel = this.private(`user.${userId}.timeline`);
        if (channel) {
            channel.listen('post.created', callback);
            return () => this.leave(`user.${userId}.timeline`);
        }
        return null;
    }

    /**
     * Listen for post engagement updates
     */
    listenForPostEngagement(postId, callback) {
        const channel = this.channel(`post.${postId}.engagement`);
        if (channel) {
            channel.listen('post.engagement', callback);
            return () => this.leave(`post.${postId}.engagement`);
        }
        return null;
    }

    /**
     * Listen for connection requests
     */
    listenForConnectionRequests(userId, callback) {
        const channel = this.private(`user.${userId}.notifications`);
        if (channel) {
            channel.listen('connection.request', callback);
            return () => this.leave(`user.${userId}.notifications`);
        }
        return null;
    }

    /**
     * Listen for circle activity
     */
    listenForCircleActivity(circleId, callback) {
        const channel = this.private(`circle.${circleId}`);
        if (channel) {
            channel.listen('post.created', callback);
            return () => this.leave(`circle.${circleId}`);
        }
        return null;
    }

    /**
     * Listen for group activity
     */
    listenForGroupActivity(groupId, callback) {
        const channel = this.private(`group.${groupId}`);
        if (channel) {
            channel.listen('post.created', callback);
            return () => this.leave(`group.${groupId}`);
        }
        return null;
    }

    /**
     * Listen for public timeline updates
     */
    listenForPublicTimeline(callback) {
        const channel = this.channel('timeline.public');
        if (channel) {
            channel.listen('post.created', callback);
            return () => this.leave('timeline.public');
        }
        return null;
    }

    /**
     * Generic event listener
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }
        this.listeners.get(event).add(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).delete(callback);
        }
    }

    /**
     * Emit event to listeners
     */
    emit(event, data = null) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    }

    /**
     * Get connection status
     */
    isConnectedToWebSocket() {
        return this.isConnected && this.echo && this.echo.connector.pusher.connection.state === 'connected';
    }

    /**
     * Get connection state
     */
    getConnectionState() {
        if (!this.echo) return 'disconnected';
        return this.echo.connector.pusher.connection.state;
    }

    /**
     * Disconnect from WebSocket
     */
    disconnect() {
        if (this.echo) {
            // Leave all channels
            this.channels.forEach((channel, channelName) => {
                try {
                    this.echo.leave(channelName.replace(/^(private-|presence-)/, ''));
                } catch (error) {
                    console.warn(`Error leaving channel ${channelName}:`, error);
                }
            });
            
            this.channels.clear();
            this.echo.disconnect();
            this.echo = null;
        }
        
        this.isConnected = false;
        this.listeners.clear();
    }

    /**
     * Listen for messages in a conversation
     */
    listenForConversationMessages(conversationId, callback) {
        const channel = this.private(`conversation.${conversationId}`);
        if (channel) {
            channel.listen('message.sent', callback);
            return () => this.leave(`conversation.${conversationId}`);
        }
        return null;
    }

    /**
     * Listen for message read receipts
     */
    listenForMessageReads(conversationId, callback) {
        const channel = this.private(`conversation.${conversationId}`);
        if (channel) {
            channel.listen('message.read', callback);
            return () => this.leave(`conversation.${conversationId}`);
        }
        return null;
    }

    /**
     * Listen for typing indicators
     */
    listenForTypingIndicators(conversationId, callback) {
        const channel = this.private(`conversation.${conversationId}`);
        if (channel) {
            channel.listen('user.typing', callback);
            return () => this.leave(`conversation.${conversationId}`);
        }
        return null;
    }

    /**
     * Listen for user's personal messages
     */
    listenForPersonalMessages(userId, callback) {
        const channel = this.private(`user.${userId}.messages`);
        if (channel) {
            channel.listen('message.sent', callback);
            return () => this.leave(`user.${userId}.messages`);
        }
        return null;
    }

    /**
     * Join conversation channel
     */
    joinConversation(conversationId) {
        return this.private(`conversation.${conversationId}`);
    }

    /**
     * Leave conversation channel
     */
    leaveConversation(conversationId) {
        this.leave(`conversation.${conversationId}`);
    }

    /**
     * Get channel statistics
     */
    getChannelStats() {
        return {
            totalChannels: this.channels.size,
            channels: Array.from(this.channels.keys()),
            isConnected: this.isConnectedToWebSocket(),
            connectionState: this.getConnectionState(),
            reconnectAttempts: this.reconnectAttempts,
        };
    }
}

// Create singleton instance
const webSocketService = new WebSocketService();

export default webSocketService;