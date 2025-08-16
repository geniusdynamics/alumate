<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $alert['title'] }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .alert-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .alert-header {
            background-color: {{ $severityColor }};
            color: white;
            padding: 20px;
            text-align: center;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .alert-severity {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
            opacity: 0.9;
        }
        .alert-content {
            padding: 30px;
        }
        .alert-message {
            font-size: 16px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid {{ $severityColor }};
            border-radius: 4px;
        }
        .alert-details {
            margin-top: 30px;
        }
        .alert-details h3 {
            color: #495057;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
            text-transform: capitalize;
        }
        .detail-value {
            color: #495057;
            word-break: break-word;
        }
        .alert-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
        .timestamp {
            font-family: 'Courier New', monospace;
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .tags {
            margin-top: 15px;
        }
        .tag {
            display: inline-block;
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .alert-content {
                padding: 20px;
            }
            .details-grid {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            .detail-label {
                margin-bottom: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="alert-container">
        <div class="alert-header">
            <div class="alert-icon">{{ $severityIcon }}</div>
            <h1 class="alert-title">{{ $alert['title'] }}</h1>
            <div class="alert-severity">{{ $alert['severity'] }} Alert</div>
        </div>
        
        <div class="alert-content">
            <div class="alert-message">
                {{ $alert['message'] }}
            </div>
            
            <div class="alert-details">
                <h3>Alert Details</h3>
                <div class="details-grid">
                    @foreach($alert['details'] as $key => $value)
                        @if($key !== 'timestamp')
                            <div class="detail-label">{{ str_replace('_', ' ', $key) }}:</div>
                            <div class="detail-value">
                                @if(is_array($value))
                                    {{ json_encode($value, JSON_PRETTY_PRINT) }}
                                @else
                                    {{ $value }}
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
                
                @if(isset($alert['tags']) && !empty($alert['tags']))
                    <div class="tags">
                        <strong>Tags:</strong><br>
                        @foreach($alert['tags'] as $tag)
                            <span class="tag">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        
        <div class="alert-footer">
            <div>
                <strong>Environment:</strong> {{ $alert['details']['environment'] ?? 'Unknown' }}
            </div>
            <div>
                <strong>Timestamp:</strong> 
                <span class="timestamp">{{ $alert['details']['timestamp'] ?? now()->toISOString() }}</span>
            </div>
            <div style="margin-top: 10px;">
                This alert was generated by the Homepage Monitoring System
            </div>
        </div>
    </div>
</body>
</html>