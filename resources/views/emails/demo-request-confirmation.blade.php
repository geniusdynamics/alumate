<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo Request Received</title>
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
        .highlight-box {
            background: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .contact-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📅 Demo Request Received</h1>
        <p>We'll be in touch within 24 hours</p>
    </div>

    <div class="content">
        <p>Hi {{ $contactName }},</p>

        <p>Thank you for requesting a demo of our alumni engagement platform for {{ $institutionName }}. We're excited to show you how our solution can transform your alumni relations!</p>

        <div class="highlight-box">
            <h3>✅ Your Request Details</h3>
            <ul>
                <li><strong>Institution:</strong> {{ $institutionName }}</li>
                <li><strong>Request ID:</strong> {{ $requestId }}</li>
                <li><strong>Preferred Time:</strong> {{ $preferredTime ?: 'Flexible' }}</li>
                @if(!empty($interests))
                <li><strong>Areas of Interest:</strong> {{ implode(', ', $interests) }}</li>
                @endif
            </ul>
        </div>

        <h3>What Happens Next?</h3>
        <ol>
            <li><strong>Personal Outreach (Within 24 hours)</strong><br>
                A member of our sales team will contact you to schedule your personalized demo</li>
            <li><strong>Demo Preparation</strong><br>
                We'll customize the demo based on your specific needs and interests</li>
            <li><strong>Live Demo Session (30-45 minutes)</strong><br>
                See the platform in action with your institution's branding and use cases</li>
            <li><strong>Q&A and Next Steps</strong><br>
                Get all your questions answered and discuss implementation options</li>
        </ol>

        <div class="info-box">
            <h4>What You'll See in Your Demo:</h4>
            <ul>
                <li>🎨 <strong>Custom Branded Mobile App</strong> - See how your app would look</li>
                <li>📊 <strong>Admin Dashboard</strong> - Comprehensive management and analytics tools</li>
                <li>🤝 <strong>Alumni Engagement Features</strong> - Directory, messaging, events, and more</li>
                <li>📈 <strong>Analytics & Reporting</strong> - Track engagement and measure ROI</li>
                <li>🔗 <strong>Integration Capabilities</strong> - Connect with your existing systems</li>
                <li>💼 <strong>Implementation Process</strong> - Timeline and support options</li>
            </ul>
        </div>

        <div class="contact-info">
            <h4>Questions Before Your Demo?</h4>
            <p>Feel free to reach out to our sales team:</p>
            <ul>
                <li>📧 <strong>Email:</strong> <a href="mailto:{{ $salesEmail }}">{{ $salesEmail }}</a></li>
                <li>📞 <strong>Phone:</strong> {{ $salesPhone }}</li>
            </ul>
        </div>

        <p>We're looking forward to showing you how our platform can help {{ $institutionName }} build stronger alumni connections and drive better engagement outcomes.</p>

        <p>Best regards,<br>
        The Alumni Platform Sales Team</p>
    </div>

    <div class="footer">
        <p>This email confirms your demo request. We'll contact you within 24 hours to schedule your personalized demonstration.</p>
        <p>© {{ date('Y') }} Alumni Platform. All rights reserved.</p>
    </div>
</body>
</html>