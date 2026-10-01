<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .credentials-box {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 15px 0;
        }
        .credential-label {
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 18px;
            color: #333;
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
        }
        .password-highlight {
            background: #fff3cd;
            border: 2px dashed #ff6b6b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            color: #ff6b6b;
            font-weight: bold;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .steps {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .step {
            margin: 15px 0;
            padding-left: 30px;
            position: relative;
        }
        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            background: #667eea;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}!</h1>
        <p>Your account has been created</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $userName }}</strong>,</p>

        <p>Your account has been created by an administrator. You can now access the {{ config('app.name') }} system with your role as <strong>{{ $role }}</strong>.</p>

        <div class="credentials-box">
            <h3 style="margin-top: 0; color: #667eea;">Your Login Credentials</h3>
            
            <div class="credential-item">
                <span class="credential-label">📧 Email:</span>
                <div class="credential-value">{{ $email }}</div>
            </div>

            <div class="credential-item">
                <span class="credential-label">🔑 Temporary Password:</span>
                <div class="credential-value">{{ $temporaryPassword }}</div>
            </div>
        </div>

        <div class="password-highlight">
            <strong> Important Security Notice:</strong>
            <p style="margin: 10px 0;">This is a <strong>temporary password</strong>. For security reasons, please change it immediately after your first login.</p>
            <p class="warning">Do not share this password with anyone!</p>
        </div>
        <div class="steps">
            <h3 style="margin-top: 0; color: #667eea;">Getting Started</h3>
            
            <div class="step">
                <div class="step-number">1</div>
                <strong>Login to your account</strong><br>
                Click the button below to access the login page
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <strong>Use your credentials</strong><br>
                Enter your email and the temporary password provided above
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <strong>Change your password</strong><br>
                Go to your Profile page → Security tab → Change Password
            </div>

            <div class="step">
                <div class="step-number">4</div>
                <strong>Complete your profile</strong><br>
                Update your personal information and preferences
            </div>
        </div>

        <center>
            <a href="{{ $loginUrl }}" class="btn">Login to Your Account</a>
        </center>

        <p style="margin-top: 30px;">If you have any questions or need assistance, please contact your system administrator.</p>

        <p>Best regards,<br>
        <strong>{{ config('app.name') }} Team</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
