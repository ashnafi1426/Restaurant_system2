<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container { 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 40px 20px; 
            text-align: center; 
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content { 
            padding: 40px 30px; 
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn { 
            display: inline-block; 
            padding: 16px 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important; 
            text-decoration: none; 
            border-radius: 50px; 
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .security-notice {
            background-color: #ffebee;
            border: 1px solid #ef5350;
            border-radius: 6px;
            padding: 15px;
            margin: 25px 0;
        }
        .security-notice strong {
            color: #c62828;
            display: block;
            margin-bottom: 8px;
        }
        .security-notice ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #c62828;
        }
        .security-notice li {
            margin: 5px 0;
            font-size: 14px;
        }
        .password-requirements {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .password-requirements h3 {
            margin: 0 0 10px 0;
            color: #1976D2;
            font-size: 16px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin: 6px 0;
            color: #1976D2;
            font-size: 14px;
        }
        .expiry-warning {
            text-align: center;
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin: 25px 0;
            font-weight: 600;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .contact-info h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        .contact-info p {
            margin: 8px 0;
            font-size: 14px;
            color: #666;
        }
        .contact-info a {
            color: #667eea;
            text-decoration: none;
        }
        .footer { 
            text-align: center; 
            font-size: 12px; 
            color: #999; 
            padding: 30px 20px; 
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
        }
        .link-text {
            word-break: break-all;
            font-size: 12px;
            color: #666;
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
            <p>{{ $hotelName }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                <strong>Hello {{ $firstName }} {{ $lastName }},</strong>
            </div>

            <p>You are receiving this email because we received a password reset request for your account.</p>

            <div class="info-box">
                <strong>📧 Account Email:</strong>
                {{ $email }}
            </div>

            <h2 style="color: #333; font-size: 20px; margin-top: 30px;">Reset Your Password</h2>
            
            <p>Click the button below to reset your password. This link will expire in {{ $expiresIn }}.</p>

            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="btn">
                    Reset My Password
                </a>
            </div>

            <div class="link-text">
                <strong>Can't click the button?</strong> Copy and paste this link into your browser:<br>
                {{ $resetUrl }}
            </div>

            <div class="password-requirements">
                <h3>🔒 New Password Requirements</h3>
                <ul>
                    <li>Minimum 8 characters</li>
                    <li>At least one uppercase letter (A-Z)</li>
                    <li>At least one lowercase letter (a-z)</li>
                    <li>At least one number (0-9)</li>
                    <li>At least one special character (!@#$%^&*)</li>
                </ul>
            </div>

            <div class="expiry-warning">
                ⏰ This password reset link expires in {{ $expiresIn }}
            </div>

            <div class="security-notice">
                <strong>🛡️ Security Notice:</strong>
                <ul>
                    <li>If you didn't request a password reset, please ignore this email</li>
                    <li>Your password won't change until you click the link above and create a new one</li>
                    <li>This link can only be used once</li>
                    <li>Never share this link with anyone</li>
                    <li>Our team will never ask for your password</li>
                </ul>
            </div>

            <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 6px;">
                <p style="margin: 0; font-size: 14px; color: #666;">
                    <strong>Didn't request this?</strong> If you didn't request a password reset, you can safely ignore this email. Your account is secure.
                </p>
            </div>

            <div class="contact-info">
                <h3>📞 Need Help?</h3>
                <p>If you're having trouble resetting your password, please contact us:</p>
                <p><strong>Phone:</strong> {{ $hotelPhone }}</p>
                <p><strong>Email:</strong> <a href="mailto:{{ $hotelEmail }}">{{ $hotelEmail }}</a></p>
                <p><strong>Website:</strong> <a href="{{ $hotelWebsite }}">{{ $hotelWebsite }}</a></p>
                <p><strong>Support Hours:</strong> 24/7 Available</p>
            </div>

            <p style="margin-top: 30px; color: #666;">
                <strong>Best regards,</strong><br>
                The {{ $hotelName }} Security Team
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</p>
            <p style="margin-top: 10px;">This is an automated security message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
