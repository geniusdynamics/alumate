<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Receipt - {{ $receipt_number }}</title>
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
        .receipt-details {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
        }
        .important-notice {
            background-color: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ff9800;
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
            background-color: #2e7d32;
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
        <h1>Official Tax Receipt</h1>
        <h2>{{ $receipt_number }}</h2>
    </div>

    <p>Dear {{ $donor_name }},</p>

    <p>Thank you for your generous donations during {{ $tax_year }}. This official tax receipt summarizes all your charitable contributions for the tax year.</p>

    <div class="receipt-details">
        <h3>Receipt Summary</h3>
        <p><strong>Receipt Number:</strong> {{ $receipt_number }}</p>
        <p><strong>Tax Year:</strong> {{ $tax_year }}</p>
        <p><strong>Receipt Date:</strong> {{ $receipt->receipt_date->format('F j, Y') }}</p>
        <p><strong>Total Charitable Donations:</strong> <span class="total-amount">${{ $total_amount }}</span></p>
        <p><strong>Donor:</strong> {{ $donor_name }}</p>
        @if($receipt->donor_address)
            <p><strong>Address:</strong><br>
            {{ implode('<br>', array_filter($receipt->donor_address)) }}
            </p>
        @endif
    </div>

    <div class="important-notice">
        <h4>Important Tax Information</h4>
        <p>This receipt is for income tax purposes and represents the total amount of eligible donations you made during {{ $tax_year }}.</p>
        <p>Please retain this receipt for your tax records. The attached PDF contains the detailed breakdown of all donations included in this receipt.</p>
    </div>

    <h3>What's Included</h3>
    <p>This receipt includes {{ count($receipt->donations) }} donation(s) made to various campaigns throughout {{ $tax_year }}. The attached PDF provides a complete itemized list of all donations.</p>

    <p>
        <a href="{{ $receipt->getDownloadUrl() }}" class="button">Download PDF Receipt</a>
    </p>

    <h3>Organization Information</h3>
    <p>
        <strong>{{ config('app.organization_name', 'Alumni Platform') }}</strong><br>
        Tax ID: {{ config('app.organization_tax_id', 'XX-XXXXXXX') }}<br>
        Charity Registration: {{ config('app.charity_registration', 'REGXXXXXX') }}
    </p>

    <p>If you have any questions about this tax receipt or need additional documentation, please don't hesitate to contact us.</p>

    <p>Thank you for your continued support of our mission!</p>

    <p>Sincerely,<br>
    The {{ config('app.name') }} Team</p>

    <div class="footer">
        <p><strong>Contact Information:</strong><br>
        Email: {{ config('app.organization_email', 'info@alumni.org') }}<br>
        Phone: {{ config('app.organization_phone', '(555) 123-4567') }}</p>
        
        <p><em>This is an official tax receipt. Please keep this document for your tax records.</em></p>
    </div>
</body>
</html>