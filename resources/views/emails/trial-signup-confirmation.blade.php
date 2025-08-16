<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Your Free Trial</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e1e5e9;
            border-top: none;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 1px solid #e1e5e9;
            border-top: none;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .highlight-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to Your Free Trial!</h1>
        <p>You're all set to explore our {{ $planName }} plan</p>
    </div>

    <div class="content">
        <p>Hi {{ $name }},</p>

        <p>Thank you for starting your 14-day free trial! We're excited to help you connect with your alumni network and advance your career.</p>

        <div class="highlight-box">
            <h3>Your Trial Details</h3>
            <ul>
                <li><strong>Plan:</strong> {{ $planName }}</li>
                <li><strong>Trial ID:</strong> {{ $trialId }}</li>
                <li><strong>Trial Ends:</strong> {{ $trialEndDate->format('F j, Y') }}</li>
                <li><strong>No Credit Card Required</strong></li>
            </ul>
        </div>

        <h3>What's Included in Your Trial:</h3>
        <ul class="feature-list">
            <li>Full alumni directory access</li>
            <li>Unlimited messaging with alumni</li>
            <li>Event creation and management</li>
            <li>AI-powered mentorship matching</li>
            <li>Advanced networking tools</li>
            <li>Priority customer support</li>
        </ul>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">Start Exploring Now</a>
        </div>

        <h3>Getting Started</h3>
        <ol>
            <li><strong>Complete your profile</strong> - Add your professional details and career goals</li>
            <li><strong>Explore the directory</strong> - Find alumni in your industry or location</li>
            <li><strong>Start connecting</strong> - Send messages and connection requests</li>
            <li><strong>Join events</strong> - Participate in networking events and webinars</li>
        </ol>

        <div class="highlight-box">
            <h4>Need Help?</h4>
            <p>Our support team is here to help you make the most of your trial:</p>
            <ul>
                <li>📧 Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                <li>💬 Live chat available in your dashboard</li>
                <li>📚 Check out our <a href="#">Getting Started Guide</a></li>
            </ul>
        </div>

        <p>We're here to support you every step of the way. Don't hesitate to reach out if you have any questions!</p>

        <p>Best regards,<br>
        The Alumni Platform Team</p>
    </div>

    <div class="footer">
        <p>This email was sent to confirm your free trial signup. Your trial will automatically expire after 14 days with no charges.</p>
        <p>© {{ date('Y') }} Alumni Platform. All rights reserved.</p>
    </div>
</body>
</html>