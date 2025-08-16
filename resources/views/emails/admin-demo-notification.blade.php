<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Demo Request</title>
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
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e1e5e9;
            border-top: none;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .priority-high {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .priority-medium {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .highlight {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 New Demo Request</h1>
        <p>{{ $demoData['institution_name'] }} - 
        <span class="priority-{{ $demoData['priority'] }}">{{ strtoupper($demoData['priority']) }} PRIORITY</span></p>
    </div>

    <div class="content">
        <h3>Institution & Contact Details</h3>
        <table class="data-table">
            <tr>
                <th>Request ID</th>
                <td>{{ $demoData['request_id'] }}</td>
            </tr>
            <tr>
                <th>Institution</th>
                <td><strong>{{ $demoData['institution_name'] }}</strong></td>
            </tr>
            <tr>
                <th>Contact Name</th>
                <td>{{ $demoData['contact_name'] }}</td>
            </tr>
            <tr>
                <th>Title</th>
                <td>{{ $demoData['title'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td><a href="mailto:{{ $demoData['email'] }}">{{ $demoData['email'] }}</a></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $demoData['phone'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Alumni Count</th>
                <td>{{ $demoData['alumni_count'] ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Current Solution</th>
                <td>{{ $demoData['current_solution'] ?: 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Preferred Time</th>
                <td>{{ $demoData['preferred_time'] ?: 'Flexible' }}</td>
            </tr>
            <tr>
                <th>Priority</th>
                <td><span class="priority-{{ $demoData['priority'] }}">{{ strtoupper($demoData['priority']) }}</span></td>
            </tr>
            <tr>
                <th>Source</th>
                <td>{{ $demoData['source'] }}</td>
            </tr>
            <tr>
                <th>Timestamp</th>
                <td>{{ $demoData['timestamp']->format('M j, Y g:i A') }}</td>
            </tr>
        </table>

        @if(!empty($demoData['interests']))
        <h3>Areas of Interest</h3>
        <ul>
            @foreach($demoData['interests'] as $interest)
            <li>{{ ucwords(str_replace('_', ' ', $interest)) }}</li>
            @endforeach
        </ul>
        @endif

        @if($demoData['message'])
        <h3>Additional Information</h3>
        <div class="highlight">
            <p>{{ $demoData['message'] }}</p>
        </div>
        @endif

        <h3>Recommended Next Steps</h3>
        <ol>
            <li><strong>Contact within 2 hours</strong> (high priority leads)</li>
            <li>Prepare customized demo based on interests</li>
            <li>Research institution background</li>
            <li>Schedule 30-45 minute demo session</li>
            <li>Follow up with proposal if interested</li>
        </ol>

        <div class="highlight">
            <h4>Quick Contact Info:</h4>
            <p><strong>{{ $demoData['contact_name'] }}</strong><br>
            {{ $demoData['title'] ? $demoData['title'] . ', ' : '' }}{{ $demoData['institution_name'] }}<br>
            📧 <a href="mailto:{{ $demoData['email'] }}">{{ $demoData['email'] }}</a>
            @if($demoData['phone'])
            <br>📞 {{ $demoData['phone'] }}
            @endif
            </p>
        </div>
    </div>
</body>
</html>