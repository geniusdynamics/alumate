<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - {{ config('app.name', 'Alumni Platform') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Theme colors -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            font-size: 48px;
        }
        
        .offline-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .offline-message {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .offline-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: rgba(59, 130, 246, 0.8);
            border-color: rgba(59, 130, 246, 0.5);
        }
        
        .btn-primary:hover {
            background: rgba(59, 130, 246, 1);
        }
        
        .network-status {
            margin-top: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-offline {
            background: #ef4444;
        }
        
        .status-online {
            background: #10b981;
        }
        
        .cached-content {
            margin-top: 30px;
            text-align: left;
        }
        
        .cached-content h3 {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .cached-links {
            list-style: none;
        }
        
        .cached-links li {
            margin-bottom: 8px;
        }
        
        .cached-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .cached-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .offline-title {
                font-size: 2rem;
            }
            
            .offline-message {
                font-size: 1rem;
            }
            
            .offline-icon {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">
            📡
        </div>
        
        <h1 class="offline-title">You're Offline</h1>
        
        <p class="offline-message">
            It looks like you've lost your internet connection. Don't worry - you can still access some features of the Alumni Platform while offline.
        </p>
        
        <div class="offline-actions">
            <button onclick="window.location.reload()" class="btn btn-primary">
                🔄 Try Again
            </button>
            
            <a href="/" class="btn">
                🏠 Go to Homepage
            </a>
        </div>
        
        <div class="network-status">
            <span class="status-indicator status-offline" id="status-indicator"></span>
            <span id="status-text">No internet connection</span>
        </div>
        
        <div class="cached-content">
            <h3>Available Offline:</h3>
            <ul class="cached-links">
                <li><a href="/">Homepage</a></li>
                <li><a href="/dashboard">Dashboard (cached)</a></li>
                <li><a href="/profile">Your Profile (cached)</a></li>
                <li><a href="/alumni/directory">Alumni Directory (cached)</a></li>
            </ul>
        </div>
    </div>

    <script>
        // Network status detection
        function updateNetworkStatus() {
            const indicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');
            
            if (navigator.onLine) {
                indicator.className = 'status-indicator status-online';
                statusText.textContent = 'Connection restored! You can refresh the page.';
                
                // Show refresh button more prominently
                const refreshBtn = document.querySelector('.btn-primary');
                refreshBtn.style.animation = 'pulse 1s infinite';
                refreshBtn.innerHTML = '🔄 Refresh Now - You\'re Back Online!';
            } else {
                indicator.className = 'status-indicator status-offline';
                statusText.textContent = 'No internet connection';
            }
        }
        
        // Listen for network status changes
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        
        // Initial status check
        updateNetworkStatus();
        
        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-refresh when connection is restored
        let wasOffline = !navigator.onLine;
        
        window.addEventListener('online', () => {
            if (wasOffline) {
                setTimeout(() => {
                    if (confirm('Your connection has been restored. Would you like to refresh the page?')) {
                        window.location.reload();
                    }
                }, 1000);
            }
            wasOffline = false;
        });
        
        window.addEventListener('offline', () => {
            wasOffline = true;
        });
    </script>
</body>
</html>