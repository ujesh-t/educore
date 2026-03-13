<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - {{ $appName }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9fafb;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0;
        }
        .success-icon {
            text-align: center;
            font-size: 64px;
            margin: 20px 0;
        }
        .content {
            background: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .payment-details {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .payment-details p {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        .payment-details strong {
            color: #1f2937;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Payment Successful!</h1>
        </div>
        
        <div class="success-icon">🎉</div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>Your payment has been received successfully!</p>
            
            <div class="payment-details">
                <p><strong>Invoice Number:</strong> <span>{{ $invoiceNumber }}</span></p>
                <p><strong>Amount Paid:</strong> <span>₹{{ number_format($amount, 2) }}</span></p>
                <p><strong>Payment Method:</strong> <span>{{ ucfirst($paymentMethod) }}</span></p>
                <p><strong>Transaction ID:</strong> <span>{{ $transactionId }}</span></p>
            </div>
            
            <p>Thank you for your payment! Your account has been updated.</p>
        </div>
        
        <div class="footer">
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
    </div>
</body>
</html>
