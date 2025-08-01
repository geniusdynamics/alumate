<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Receipt - {{ $receipt->getFormattedReceiptNumber() }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .organization-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
            margin-top: 15px;
        }
        .receipt-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .receipt-info-left, .receipt-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .receipt-info-right {
            text-align: right;
        }
        .donor-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .summary-box {
            background-color: #e8f5e8;
            padding: 20px;
            border: 2px solid #2e7d32;
            text-align: center;
            margin: 20px 0;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
        }
        .donations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .donations-table th,
        .donations-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .donations-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .donations-table .amount {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-left, .signature-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin-top: 30px;
            margin-bottom: 5px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="organization-name">{{ $organization['name'] }}</div>
        <div>
            {{ $organization['address'] }}<br>
            {{ $organization['city'] }}, {{ $organization['state'] }} {{ $organization['zip'] }}<br>
            Phone: {{ $organization['phone'] }} | Email: {{ $organization['email'] }}
        </div>
        <div class="receipt-title">OFFICIAL TAX RECEIPT</div>
    </div>

    <div class="receipt-info">
        <div class="receipt-info-left">
            <strong>Receipt Number:</strong> {{ $receipt->getFormattedReceiptNumber() }}<br>
            <strong>Tax Year:</strong> {{ $receipt->tax_year }}<br>
            <strong>Receipt Date:</strong> {{ $receipt->receipt_date->format('F j, Y') }}<br>
            <strong>Generated:</strong> {{ $generated_date }}
        </div>
        <div class="receipt-info-right">
            <strong>Tax ID:</strong> {{ $organization['tax_id'] }}<br>
            <strong>Charity Reg:</strong> {{ $organization['charity_registration'] }}
        </div>
    </div>

    <div class="donor-info">
        <h3>Donor Information</h3>
        <strong>{{ $receipt->donor_name }}</strong><br>
        {{ $receipt->donor_email }}<br>
        @if($receipt->donor_address)
            @foreach($receipt->donor_address as $line)
                {{ $line }}<br>
            @endforeach
        @endif
    </div>

    <div class="summary-box">
        <h3>Total Charitable Donations for {{ $receipt->tax_year }}</h3>
        <div class="total-amount">${{ number_format($receipt->total_amount, 2) }}</div>
        <div style="margin-top: 10px; font-style: italic;">
            ({{ $total_amount_words }})
        </div>
    </div>

    <h3>Donation Details</h3>
    <table class="donations-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Campaign</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $donation)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($donation['processed_at'])->format('M j, Y') }}</td>
                    <td>{{ $donation['campaign']['title'] ?? 'General Donation' }}</td>
                    <td class="amount">${{ number_format($donation['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8f9fa;">
                <td colspan="2">Total</td>
                <td class="amount">${{ number_format($receipt->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin: 30px 0; padding: 15px; border: 1px solid #ddd; background-color: #fff3e0;">
        <h4>Important Tax Information</h4>
        <p><strong>This receipt is for income tax purposes.</strong></p>
        <p>No goods or services were provided in exchange for this donation. The full amount is eligible for tax deduction as permitted by law.</p>
        <p>Please retain this receipt for your tax records.</p>
    </div>

    <div class="signature-section">
        <div class="signature-left">
            <div class="signature-line"></div>
            <div>Authorized Signature</div>
            <div style="margin-top: 10px; font-size: 10px;">
                {{ $organization['name'] }}<br>
                Authorized Representative
            </div>
        </div>
        <div class="signature-right">
            <div style="text-align: right;">
                <div class="signature-line" style="margin-left: auto;"></div>
                <div>Date</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>{{ $organization['name'] }}</strong> is a registered charitable organization.</p>
        <p>Tax ID: {{ $organization['tax_id'] }} | Charity Registration: {{ $organization['charity_registration'] }}</p>
        <p>For questions about this receipt, contact us at {{ $organization['email'] }} or {{ $organization['phone'] }}.</p>
        <p style="text-align: center; margin-top: 20px;">
            <em>This document was generated electronically and is valid without a signature.</em>
        </p>
    </div>
</body>
</html>