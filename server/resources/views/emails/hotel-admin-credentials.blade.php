<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isResend ? 'Password Reset' : 'Welcome to Hotel Administration' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 13px;
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .intro {
            font-size: 14px;
            color: #475569;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .credentials-card {
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        .credential-row:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }
        .label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .value {
            font-size: 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            color: #0f172a;
        }
        .password-value {
            font-size: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 800;
            color: #4f46e5;
            background: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px dashed #4f46e5;
        }
        .alert-box {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #92400e;
            line-height: 1.5;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 8px;
            letter-spacing: 0.025em;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 28px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $isResend ? 'Password Reset Notification' : 'Welcome to Hotel Administration' }}</h1>
            <p>{{ $hotelName }}</p>
        </div>

        <div class="content">
            <div class="greeting">Hello, {{ $userName }}!</div>
            <div class="intro">
                @if($isResend)
                    Your administrator login credentials have been reset by platform administration. Below is your new temporary password:
                @else
                    You have been provisioned as a <strong>Hotel Administrator</strong> for <strong>{{ $hotelName }}</strong>. Below are your temporary login credentials:
                @endif
            </div>

            <!-- Credentials Card -->
            <div class="credentials-card">
                <div class="credential-row">
                    <span class="label">Assigned Hotel</span>
                    <span class="value">{{ $hotelName }}</span>
                </div>
                <div class="credential-row">
                    <span class="label">Login Email</span>
                    <span class="value">{{ $email }}</span>
                </div>
                <div class="credential-row">
                    <span class="label">Temporary Password</span>
                    <span class="password-value">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <div class="alert-box">
                ⚠️ <strong>Important Security Note:</strong> For security reasons, you will be required to change this temporary password to your own permanent password immediately upon logging in.
            </div>

            <div class="btn-container">
                <a href="{{ $loginUrl }}" class="btn">Log In to Dashboard</a>
            </div>

            <p style="font-size: 12px; color: #64748b; margin-top: 20px;">
                Direct login link: <a href="{{ $loginUrl }}" style="color: #4f46e5;">{{ $loginUrl }}</a>
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            Please do not reply directly to this automated email.
        </div>
    </div>
</body>
</html>
