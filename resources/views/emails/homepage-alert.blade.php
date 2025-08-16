<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Homepage Alert - {{ ucfirst($alert['type']) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-critical {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
        }
        .alert-warning {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
        }
        .alert-info {
            border-left: 4px solid #17a2b8;
            background: #d1ecf1;
        }
        .alert-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .alert-details h3 {
            margin-top: 0;
            color: #495057;
        }
        .detail-item {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
            display: inline-block;
            width: 120px;
        }
        .detail-value {
            color: #495057;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        .severity-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .severity-critical {
            background: #dc3545;
            color: white;
        }
        .severity-warning {
            background: #ffc107;
            color: #212529;
        }
        .severity-info {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header alert-{{ $alert['severity'] }}">
        <h1>🚨 Homepage Alert</h1>
        <p>
            <strong>{{ ucfirst($alert['type']) }}</strong>
            <span class="severity-badge severity-{{ $alert['severity'] }}">{{ $alert['severity'] }}</span>
        </p>
    </div>

    <div class="alert-details">
        <h3>Alert Details</h3>
        
        @foreach($alert as $key => $value)
            @if(!in_array($key, ['type', 'severity']))
                <div class="detail-item">
                    <span class="detail-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                    <span class="detail-value">
                        @if(is_array($value))
                            {{ json_encode($value, JSON_PRETTY_PRINT) }}
                        @else
                            {{ $value }}
                        @endif
                    </span>
                </div>
            @endif
        @endforeach
    </div>

    @if($alert['type'] === 'uptime')
        <div class="alert-details">
            <h3>Recommended Actions</h3>
            <ul>
                <li>Check server status and logs</li>
                <li>Verify network connectivity</li>
                <li>Review recent deployments</li>
                <li>Check database and cache services</li>
            </ul>
        </div>
    @elseif($alert['type'] === 'performance')
        <div class="alert-details">
            <h3>Recommended Actions</h3>
            <ul>
                <li>Review server resource usage</li>
                <li>Check database query performance</li>
                <li>Analyze recent traffic patterns</li>
                <li>Consider scaling resources if needed</li>
            </ul>
        </div>
    @elseif($alert['type'] === 'error')
        <div class="alert-details">
            <h3>Recommended Actions</h3>
            <ul>
                <li>Review error logs for details</li>
                <li>Check recent code deployments</li>
                <li>Verify third-party service status</li>
                <li>Monitor error frequency and patterns</li>
            </ul>
        </div>
    @endif

    <div class="alert-details">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="{{ config('app.url') }}/monitoring/dashboard">Monitoring Dashboard</a></li>
            <li><a href="{{ config('app.url') }}/health-check/homepage">Health Check</a></li>
            <li><a href="{{ config('app.url') }}/monitoring/logs">Error Logs</a></li>
        </ul>
    </div>

    <div class="footer">
        <p>
            This alert was generated automatically by the Alumni Platform monitoring system.<br>
            Environment: <strong>{{ $alert['environment'] ?? 'Unknown' }}</strong><br>
            Timestamp: <strong>{{ $alert['timestamp'] ?? now()->toISOString() }}</strong>
        </p>
        <p>
            <small>
                To modify alert settings or unsubscribe from these notifications, 
                please contact your system administrator.
            </small>
        </p>
    </div>
</body>
</html>