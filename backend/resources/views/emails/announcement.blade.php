<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ $appName }}</title>
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
        .announcement-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        .announcement-body {
            color: #4b5563;
            line-height: 1.8;
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
            <h1>📢 {{ $appName }} Announcement</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <div class="announcement-title">{{ $title }}</div>
            
            <div class="announcement-body">
                {!! nl2br(e($content)) !!}
            </div>
        </div>
        
        <div class="footer">
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
    </div>
</body>
</html>
