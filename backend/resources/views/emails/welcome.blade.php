<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
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
        .content {
            background: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .credentials {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .credentials p {
            margin: 5px 0;
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
            <h1>🎉 Welcome to {{ $appName }}!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>We're excited to have you on board! Your account has been created successfully.</p>
            
            @if(!empty($credentials['email']) && !empty($credentials['password']))
            <div class="credentials">
                <strong>Your Login Credentials:</strong>
                <p><strong>Email:</strong> {{ $credentials['email'] }}</p>
                <p><strong>Password:</strong> {{ $credentials['password'] }}</p>
            </div>
            @endif
            
            <p>Get started by logging in to your account and exploring all the features we have to offer.</p>
            
            <a href="{{ config('app.url') }}/login" class="button">Login Now</a>
        </div>
        
        <div class="footer">
            <p>Best regards,<br>The {{ $appName }} Team</p>
            <p>If you have any questions, feel free to contact our support team.</p>
        </div>
    </div>
</body>
</html>
