<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Alert - {{ $search->name }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .search-info {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .search-query {
            font-size: 16px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        .search-stats {
            font-size: 14px;
            color: #6b7280;
        }
        .results-section {
            margin-bottom: 25px;
        }
        .results-header {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .result-item {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 12px;
            background-color: #fafafa;
        }
        .result-type {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .result-title {
            font-size: 16px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .result-snippet {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 16px;
        }
        .cta-button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .unsubscribe {
            margin-top: 15px;
        }
        .unsubscribe a {
            color: #6b7280;
            text-decoration: underline;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 1px 3px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Alumni Platform</div>
            <div class="alert-title">🔍 New Search Results Available</div>
        </div>

        <div class="search-info">
            <div class="search-query">
                <strong>Search:</strong> "{{ $search->query }}"
            </div>
            <div class="search-stats">
                <strong>{{ $new_results_count }}</strong> new results • 
                <strong>{{ $total_results_count }}</strong> total results
            </div>
        </div>

        <div class="results-section">
            <div class="results-header">Latest Results</div>
            
            @foreach(array_slice($results, 0, 5) as $result)
                <div class="result-item">
                    <div class="result-type">
                        @switch($result['type'])
                            @case('user')
                                👤 Alumni
                                @break
                            @case('post')
                                📝 Post
                                @break
                            @case('job')
                                💼 Job
                                @break
                            @case('event')
                                📅 Event
                                @break
                            @default
                                📄 {{ ucfirst($result['type']) }}
                        @endswitch
                    </div>
                    
                    <div class="result-title">
                        @if($result['type'] === 'user')
                            {{ $result['source']['name'] ?? 'Alumni Profile' }}
                        @elseif($result['type'] === 'post')
                            Post by {{ $result['source']['user_name'] ?? 'Alumni' }}
                        @elseif($result['type'] === 'job')
                            {{ $result['source']['title'] ?? 'Job Opportunity' }}
                        @elseif($result['type'] === 'event')
                            {{ $result['source']['title'] ?? 'Event' }}
                        @else
                            {{ $result['source']['title'] ?? $result['source']['name'] ?? 'Result' }}
                        @endif
                    </div>
                    
                    <div class="result-snippet">
                        @if($result['type'] === 'user')
                            @if(isset($result['source']['current_position']))
                                {{ $result['source']['current_position'] }}
                                @if(isset($result['source']['current_company']))
                                    at {{ $result['source']['current_company'] }}
                                @endif
                            @endif
                            @if(isset($result['source']['location']))
                                • {{ $result['source']['location'] }}
                            @endif
                        @elseif($result['type'] === 'job')
                            @if(isset($result['source']['company']))
                                {{ $result['source']['company'] }}
                            @endif
                            @if(isset($result['source']['location']))
                                • {{ $result['source']['location'] }}
                            @endif
                        @elseif($result['type'] === 'event')
                            @if(isset($result['source']['event_date']))
                                {{ date('M j, Y', strtotime($result['source']['event_date'])) }}
                            @endif
                            @if(isset($result['source']['location']))
                                • {{ $result['source']['location'] }}
                            @endif
                        @else
                            @if(isset($result['source']['description']))
                                {{ Str::limit($result['source']['description'], 100) }}
                            @elseif(isset($result['source']['content']))
                                {{ Str::limit($result['source']['content'], 100) }}
                            @elseif(isset($result['source']['bio']))
                                {{ Str::limit($result['source']['bio'], 100) }}
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            @if(count($results) > 5)
                <div style="text-align: center; margin-top: 15px; color: #6b7280; font-size: 14px;">
                    ... and {{ count($results) - 5 }} more results
                </div>
            @endif
        </div>

        <div class="cta-section">
            <a href="{{ $search_url }}" class="cta-button">
                View All Results
            </a>
        </div>

        <div class="footer">
            <p>
                You're receiving this email because you have alerts enabled for the saved search 
                "<strong>{{ $search->name }}</strong>" with {{ $search->formatted_alert_frequency }} frequency.
            </p>
            
            <div class="unsubscribe">
                <a href="{{ config('app.url') }}/search/saved">Manage your saved searches</a> • 
                <a href="{{ config('app.url') }}/settings/notifications">Update notification preferences</a>
            </div>
        </div>
    </div>
</body>
</html>