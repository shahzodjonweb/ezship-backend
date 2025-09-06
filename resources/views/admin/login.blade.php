<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EzShip</title>
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

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .demo-credentials h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .demo-credentials p {
            color: #7f8c8d;
            font-size: 13px;
            margin: 5px 0;
        }

        .demo-credentials code {
            background: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>EzShip Admin</h1>
            <p>Please login to continue</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus 
                       value="{{ old('email') }}" placeholder="admin@example.com">
                @error('email')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
                @error('password')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; font-weight: normal;">
                    <input type="checkbox" name="remember" style="width: auto; margin-right: 10px;">
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="back-link">
            <a href="{{ url('/') }}">← Back to Website</a>
        </div>

        <!-- Demo Credentials (Remove in production) -->
        <div class="demo-credentials">
            <h4>Demo Admin Credentials</h4>
            <p>Email: <code>admin@ezship.com</code></p>
            <p>Password: <code>password</code></p>
            <p style="font-size: 11px; margin-top: 10px; color: #95a5a6;">
                Note: Create an admin user with is_admin=1 in the database
            </p>
        </div>
    </div>
</body>
</html>