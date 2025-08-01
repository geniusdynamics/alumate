<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank You for Your Donation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .donation-details {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
        }
        .campaign-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #666;
        }
        .button {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You for Your Generous Donation!</h1>
    </div>

    <p>Dear {{ $donor_name }},</p>

    <p>We are deeply grateful for your generous donation to support <strong>{{ $campaign->title }}</strong>. Your contribution makes a real difference in our mission.</p>

    <div class="donation-details">
        <h3>Donation Details</h3>
        <p><strong>Amount:</strong> <span class="amount">${{ $amount }}</span></p>
        <p><strong>Date:</strong> {{ $donation->processed_at->format('F j, Y') }}</p>
        <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $donation->payment_method)) }}</p>
        @if($is_recurring)
            <p><strong>Recurring:</strong> Yes ({{ ucfirst($donation->recurring_frequency) }})</p>
        @endif
        @if($donation->message)
            <p><strong>Your Message:</strong> "{{ $donation->message }}"</p>
        @endif
    </div>

    <div class="campaign-info">
        <h3>About {{ $campaign->title }}</h3>
        <p>{{ $campaign->description }}</p>
        
        @if($campaign->goal_amount > 0)
            <p><strong>Campaign Progress:</strong> ${{ number_format($campaign->raised_amount, 2) }} raised of ${{ number_format($campaign->goal_amount, 2) }} goal ({{ round($campaign->progress_percentage) }}%)</p>
        @endif
    </div>

    @if($campaign->thank_you_message)
        <div style="font-style: italic; padding: 15px; background-color: #fff3e0; border-radius: 8px; margin: 20px 0;">
            <p>"{{ $campaign->thank_you_message }}"</p>
        </div>
    @endif

    <p>
        <a href="{{ route('campaigns.show', $campaign) }}" class="button">View Campaign Progress</a>
    </p>

    @if($is_recurring)
        <p>As a recurring donor, you can manage your donation preferences at any time by visiting your donor dashboard.</p>
        <p>
            <a href="{{ route('donor.dashboard') }}" class="button">Manage Donations</a>
        </p>
    @endif

    <p>Your donation is tax-deductible to the full extent allowed by law. You will receive a tax receipt for your records.</p>

    <p>Thank you again for your support. Together, we can make a lasting impact!</p>

    <p>With gratitude,<br>
    The {{ config('app.name') }} Team</p>

    <div class="footer">
        <p>This is an automated message. If you have any questions about your donation, please contact us at {{ config('app.organization_email', 'support@example.com') }}.</p>
        
        @if($is_recurring)
            <p>To cancel your recurring donation, please visit your donor dashboard or contact us directly.</p>
        @endif
    </div>
</body>
</html>