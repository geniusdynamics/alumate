<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - Alumni Platform</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Theme -->
    <meta name="theme-color" content="#3b82f6">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .offline-container {
            max-width: 500px;
            width: 100%;
        }
        
        .offline-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .offline-icon svg {
            width: 60px;
            height: 60px;
            opacity: 0.8;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .features {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .features h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            opacity: 0.95;
        }
        
        .feature-list {
            list-style: none;
            text-align: left;
        }
        
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            opacity: 0.8;
        }
        
        .feature-list li::before {
            content: '✓';
            color: #10b981;
            font-weight: bold;
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .retry-button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }
        
        .retry-button:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .retry-button:active {
            transform: translateY(0);
        }
        
        .network-status {
            margin-top: 30px;
            padding: 15px;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .network-status.online {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.3);
        }
        
        .tips {
            margin-top: 30px;
            text-align: left;
            opacity: 0.8;
        }
        
        .tips h4 {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .tips ul {
            list-style: none;
        }
        
        .tips li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .tips li::before {
            content: '💡';
            position: absolute;
            left: 0;
        }
        
        @media (max-width: 600px) {
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1rem;
            }
            
            .features {
                padding: 20px;
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon pulse">
            <svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.07 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
            </svg>
        </div>
        
        <h1>You're Offline</h1>
        <p class="subtitle">
            Don't worry! You can still browse cached content and your actions will sync when you're back online.
        </p>
        
        <div class="features">
            <h3>Available Offline</h3>
            <ul class="feature-list">
                <li>View your cached timeline posts</li>
                <li>Browse saved alumni profiles</li>
                <li>Access your connections</li>
                <li>View cached job listings</li>
                <li>Read saved messages</li>
            </ul>
        </div>
        
        <div class="network-status" id="networkStatus">
            <strong>🔴 No Internet Connection</strong>
            <p>Checking connection status...</p>
        </div>
        
        <button class="retry-button" onclick="checkConnection()">
            Check Connection
        </button>
        
        <a href="/" class="retry-button">
            Go to Dashboard
        </a>
        
        <div class="tips">
            <h4>Tips for Offline Use</h4>
            <ul>
                <li>Your posts and actions are saved and will sync automatically</li>
                <li>Cached content is available for up to 7 days</li>
                <li>Enable notifications to know when you're back online</li>
                <li>The app works best when installed on your device</li>
            </ul>
        </div>
    </div>
    
    <script>
        let isOnline = navigator.onLine;
        let retryCount = 0;
        const maxRetries = 3;
        
        function updateNetworkStatus() {
            const statusElement = document.getElementById('networkStatus');
            
            if (navigator.onLine) {
                statusElement.className = 'network-status online';
                statusElement.innerHTML = `
                    <strong>🟢 Connection Restored</strong>
                    <p>You're back online! Redirecting to the app...</p>
                `;
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = '/';
                }, 2000);
            } else {
                statusElement.className = 'network-status';
                statusElement.innerHTML = `
                    <strong>🔴 No Internet Connection</strong>
                    <p>Please check your network settings and try again.</p>
                `;
            }
        }
        
        async function checkConnection() {
            const button = document.querySelector('.retry-button');
            const originalText = button.textContent;
            
            button.textContent = 'Checking...';
            button.disabled = true;
            
            try {
                // Try to fetch a small resource
                const response = await fetch('/api/ping', {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    updateNetworkStatus();
                    return;
                }
            } catch (error) {
                console.log('Connection check failed:', error);
            }
            
            retryCount++;
            
            if (retryCount < maxRetries) {
                button.textContent = `Retry (${retryCount}/${maxRetries})`;
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            } else {
                button.textContent = 'Connection Failed';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                    retryCount = 0;
                }, 5000);
            }
        }
        
        // Listen for online/offline events
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        
        // Initial status check
        updateNetworkStatus();
        
        // Periodic connection check
        setInterval(() => {
            if (!navigator.onLine) {
                fetch('/api/ping', { 
                    method: 'GET', 
                    cache: 'no-cache',
                    signal: AbortSignal.timeout(5000)
                })
                .then(response => {
                    if (response.ok && !navigator.onLine) {
                        // Connection is actually available
                        navigator.onLine = true;
                        updateNetworkStatus();
                    }
                })
                .catch(() => {
                    // Still offline
                });
            }
        }, 30000); // Check every 30 seconds
        
        // Service worker registration check
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then(registration => {
                if (registration) {
                    console.log('Service Worker is active');
                    
                    // Listen for service worker messages
                    navigator.serviceWorker.addEventListener('message', event => {
                        if (event.data.type === 'NETWORK_STATUS_CHANGED') {
                            updateNetworkStatus();
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>