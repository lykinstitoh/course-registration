<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $payment->reference }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e3a8a; }
        .header p { margin: 5px 0; color: #666; }
        .details-table { width: 100%; margin-bottom: 30px; }
        .details-table td { padding: 5px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .receipt-table th { background-color: #f8fafc; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 10px; }
        .badge { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>OCRS University</h1>
        <p>Official Payment Receipt</p>
    </div>

    <table class="details-table">
        <tr>
            <td><strong>Receipt No:</strong> {{ $payment->reference }}</td>
            <td style="text-align:right;"><strong>Date:</strong> {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->updated_at->format('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Student Name:</strong> {{ $profile->user->name }}</td>
            <td style="text-align:right;"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
        </tr>
        <tr>
            <td><strong>Admission No:</strong> {{ $profile->admission_number ?? 'N/A' }}</td>
            <td style="text-align:right;">
                @if($payment->mpesa_receipt)
                    <strong>M-Pesa Ref:</strong> {{ $payment->mpesa_receipt }}
                @elseif($payment->bank_reference)
                    <strong>Bank Ref:</strong> {{ $payment->bank_reference }}
                @endif
            </td>
        </tr>
    </table>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right;">Amount ({{ $payment->currency }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->feeStructure->description ?? 'Fee Payment' }}</td>
                <td style="text-align:right;">{{ number_format($payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align:right; font-weight:bold;">Total Paid:</td>
                <td style="text-align:right; font-weight:bold;">{{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 30px;">
        <span class="badge">PAID IN FULL</span>
    </div>

    <div class="footer">
        <p>This is a computer generated receipt and does not require a signature.</p>
        <p>&copy; {{ date('Y') }} OCRS University. All rights reserved.</p>
    </div>
</body>
</html>
