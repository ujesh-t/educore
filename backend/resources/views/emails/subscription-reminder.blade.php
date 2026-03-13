<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Reminder - {{ $appName }}</title>
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
            color: #f59e0b;
            margin: 0;
        }
        .content {
            background: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .reminder-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
        }
        .plan-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            text-align: center;
            padding: 15px;
            background: #eef2ff;
            border-radius: 6px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            margin-top: 20px;
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
            <h1>⏰ Subscription Expiry Reminder</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>This is a friendly reminder that your subscription will expire soon.</p>
            
            <div class="plan-name">{{ $planName }}</div>
            
            <div class="reminder-box">
                <strong>📅 Expiry Date:</strong> {{ $expiryDate }}<br>
                <strong>⏳ Days Remaining:</strong> {{ $daysUntilExpiry }} days
            </div>
            
            <p>Please renew your subscription to continue enjoying our services without interruption.</p>
            
            <a href="{{ config('app.url') }}/subscriptions" class="button">Renew Now</a>
        </div>
        
        <div class="footer">
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
    </div>
</body>
</html>
