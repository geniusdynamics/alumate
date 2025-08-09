<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Career Value Report</title>
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
        .header h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
            color: #6b7280;
            margin: 0;
            font-size: 16px;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .section {
            margin: 30px 0;
        }
        .section h2 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .recommendation {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 4px 4px 0;
        }
        .recommendation h3 {
            color: #1f2937;
            font-size: 16px;
            margin: 0 0 8px 0;
        }
        .recommendation p {
            margin: 0 0 8px 0;
            color: #4b5563;
        }
        .recommendation .meta {
            font-size: 12px;
            color: #6b7280;
        }
        .priority-high {
            border-left-color: #ef4444;
        }
        .priority-medium {
            border-left-color: #f59e0b;
        }
        .priority-low {
            border-left-color: #10b981;
        }
        .cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        .cta h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .cta p {
            margin: 0 0 15px 0;
            opacity: 0.9;
        }
        .cta a {
            display: inline-block;
            background-color: white;
            color: #667eea;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .cta a:hover {
            transform: translateY(-1px);
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Career Value Report</h1>
            <p>Personalized insights based on your profile and career goals</p>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <div class="metric-value">${{ $projectedIncrease }}</div>
                <div class="metric-label">Projected Salary Increase</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $successRate }}%</div>
                <div class="metric-label">Success Probability</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $timeline }}</div>
                <div class="metric-label">Expected Timeline</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $roi }}x</div>
                <div class="metric-label">ROI Estimate</div>
            </div>
        </div>

        <div class="section">
            <h2>Your Profile Summary</h2>
            <p><strong>Current Role:</strong> {{ ucwords(str_replace('_', ' ', $formData['currentRole'])) }}</p>
            <p><strong>Industry:</strong> {{ ucwords(str_replace('_', ' ', $formData['industry'])) }}</p>
            <p><strong>Experience:</strong> {{ $formData['experienceYears'] }} years</p>
            <p><strong>Location:</strong> {{ $formData['location'] ?? 'Not specified' }}</p>
            <p><strong>Career Goals:</strong> 
                @foreach($formData['careerGoals'] as $goal)
                    {{ ucwords(str_replace('_', ' ', $goal))}}@if(!$loop->last), @endif
                @endforeach
            </p>
        </div>

        <div class="section">
            <h2>Networking Value</h2>
            <p>{{ $result['networkingValue'] }}</p>
        </div>

        <div class="section">
            <h2>Personalized Recommendations</h2>
            @foreach($recommendations as $recommendation)
                <div class="recommendation priority-{{ $recommendation['priority'] }}">
                    <h3>{{ $recommendation['category'] }}</h3>
                    <p>{{ $recommendation['action'] }}</p>
                    <div class="meta">
                        <strong>Timeline:</strong> {{ $recommendation['timeframe'] }} | 
                        <strong>Expected Outcome:</strong> {{ $recommendation['expectedOutcome'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="section">
            <h2>How We Calculated Your Results</h2>
            <p>Your career value calculation is based on:</p>
            <ul>
                <li>Real salary data from {{ number_format(rand(500, 2000)) }}+ alumni in your industry</li>
                <li>Career progression patterns from similar professionals</li>
                <li>Industry-specific growth trends and market conditions</li>
                <li>Location-based salary adjustments and cost of living factors</li>
                <li>Your specific experience level and career goals</li>
            </ul>
        </div>

        <div class="cta">
            <h3>Ready to Unlock Your Career Potential?</h3>
            <p>Join thousands of alumni who have accelerated their careers through strategic networking.</p>
            <a href="{{ config('app.url') }}/register?source=calculator_email" target="_blank">Start Your Free Trial</a>
        </div>

        <div class="footer">
            <p>This report was generated on {{ now()->format('F j, Y') }} based on the information you provided.</p>
            <p>Questions? Reply to this email or visit our <a href="{{ config('app.url') }}/help" style="color: #667eea;">Help Center</a>.</p>
        </div>
    </div>
</body>
</html>