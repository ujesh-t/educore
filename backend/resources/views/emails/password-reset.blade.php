<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - {{ $appName }}</title>
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
        .button {
            display: inline-block;
            background: #f59e0b;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
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
            <h1>🔐 Password Reset Request</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>You have requested to reset your password for your {{ $appName }} account.</p>
            
            <p>Click the button below to reset your password:</p>
            
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> This link will expire in 60 minutes. 
                If you did not request a password reset, please ignore this email.
            </div>
        </div>
        
        <div class="footer">
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
    </div>
</body>
</html>
