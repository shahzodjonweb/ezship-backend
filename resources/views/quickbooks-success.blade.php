<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickBooks Connected - EzShip</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-box strong {
            color: #2c3e50;
        }
        
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
        
        .code {
            background: #2c3e50;
            color: #27ae60;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        
        <h1>{{ $message }}</h1>
        
        <div class="info-box">
            <strong>Realm ID:</strong> {{ $realm_id }}
        </div>
        
        @if(isset($note))
            <div class="warning-box">
                <strong>Important:</strong> {{ $note }}
                <div class="code">QUICKBOOKS_REALM_ID={{ $realm_id }}</div>
            </div>
        @endif
        
        <p style="color: #7f8c8d; margin-top: 20px;">
            QuickBooks integration is now active. The system will automatically<br>
            refresh tokens as needed to maintain the connection.
        </p>
        
        <a href="/quickbooks/status" class="btn">Check Connection Status</a>
        
        <p style="color: #95a5a6; margin-top: 30px; font-size: 14px;">
            You can close this window or navigate to the admin panel.
        </p>
    </div>
</body>
</html>