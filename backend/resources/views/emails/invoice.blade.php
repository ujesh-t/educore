<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoiceNumber }} - {{ $appName }}</title>
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
            color: #4f46e5;
            margin: 0;
        }
        .invoice-box {
            background: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
            text-align: center;
            padding: 20px;
            background: #eef2ff;
            border-radius: 6px;
            margin: 20px 0;
        }
        .due-date {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
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
            <h1>📄 Invoice {{ $invoiceNumber }}</h1>
        </div>
        
        <div class="invoice-box">
            <p>Hello {{ $name }},</p>
            
            <p>Please find your invoice details below:</p>
            
            <div class="amount">₹{{ number_format($amount, 2) }}</div>
            
            <div class="due-date">
                <strong>⏰ Due Date:</strong> {{ $dueDate }}
            </div>
            
            <p>Please make the payment before the due date to avoid any late fees.</p>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!<br>The {{ $appName }} Team</p>
        </div>
    </div>
</body>
</html>
