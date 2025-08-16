<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Trial Signup</title>
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
            background: #28a745;
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
        .highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 New Trial Signup</h1>
        <p>{{ $trialData['name'] }} started a {{ $trialData['plan_id'] }} trial</p>
    </div>

    <div class="content">
        <h3>Trial Details</h3>
        <table class="data-table">
            <tr>
                <th>Trial ID</th>
                <td>{{ $trialData['trial_id'] }}</td>
            </tr>
            <tr>
                <th>Name</th>
                <td>{{ $trialData['name'] }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td><a href="mailto:{{ $trialData['email'] }}">{{ $trialData['email'] }}</a></td>
            </tr>
            <tr>
                <th>Plan</th>
                <td>{{ ucfirst($trialData['plan_id']) }}</td>
            </tr>
            <tr>
                <th>Institution</th>
                <td>{{ $trialData['institution'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Graduation Year</th>
                <td>{{ $trialData['graduation_year'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Current Role</th>
                <td>{{ $trialData['current_role'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Industry</th>
                <td>{{ $trialData['industry'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Referral Source</th>
                <td>{{ $trialData['referral_source'] ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <th>Source</th>
                <td>{{ $trialData['source'] }}</td>
            </tr>
            <tr>
                <th>Trial Start</th>
                <td>{{ $trialData['trial_start_date']->format('M j, Y g:i A') }}</td>
            </tr>
            <tr>
                <th>Trial End</th>
                <td>{{ $trialData['trial_end_date']->format('M j, Y g:i A') }}</td>
            </tr>
            <tr>
                <th>IP Address</th>
                <td>{{ $trialData['ip_address'] }}</td>
            </tr>
        </table>

        @if($trialData['industry'] || $trialData['current_role'])
        <div class="highlight">
            <h4>Lead Quality Indicators:</h4>
            <ul>
                @if($trialData['industry'])
                <li><strong>Industry:</strong> {{ $trialData['industry'] }}</li>
                @endif
                @if($trialData['current_role'])
                <li><strong>Role:</strong> {{ $trialData['current_role'] }}</li>
                @endif
                @if($trialData['institution'])
                <li><strong>Institution:</strong> {{ $trialData['institution'] }}</li>
                @endif
            </ul>
        </div>
        @endif

        <h3>Recommended Actions:</h3>
        <ul>
            <li>Send welcome email sequence</li>
            <li>Monitor trial engagement</li>
            <li>Schedule follow-up on day 7</li>
            <li>Prepare conversion offer for day 12</li>
        </ul>
    </div>
</body>
</html>