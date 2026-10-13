<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Inquiry - {{ $hotel->name }}</title>
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
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header p {
            margin: 6px 0 0;
            opacity: 0.8;
            font-size: 13px;
        }
        .content {
            padding: 32px 28px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 12px;
        }
        .info-grid {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
        }
        .info-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
        .message-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 18px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #334155;
            white-space: pre-line;
            line-height: 1.7;
        }
        .action-container {
            text-align: center;
            margin: 28px 0 16px;
        }
        .reply-button {
            display: inline-block;
            background: #f59e0b;
            color: #0f172a;
            font-weight: 800;
            font-size: 13px;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Guest Inquiry</span>
            <h1>{{ $hotel->name }}</h1>
            <p>New message received through the public contact portal</p>
        </div>

        <div class="content">
            <div class="section-title">Inquiry Details</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value">{{ $contactMessage->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email Address:</span>
                    <span class="info-value"><a href="mailto:{{ $contactMessage->email }}" style="color: #2563eb; text-decoration: none;">{{ $contactMessage->email }}</a></span>
                </div>
                @if($contactMessage->phone)
                <div class="info-row">
                    <span class="info-label">Phone Number:</span>
                    <span class="info-value">{{ $contactMessage->phone }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Inquiry Subject:</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $contactMessage->subject)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ now()->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <div class="section-title">Message Content</div>
            <div class="message-box">
                {{ $contactMessage->message }}
            </div>

            <div class="action-container">
                <a href="mailto:{{ $contactMessage->email }}?subject={{ urlencode('Re: ' . $contactMessage->subject . ' - ' . $hotel->name) }}" class="reply-button">
                    Reply to {{ $contactMessage->name }}
                </a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 6px;">{{ $hotel->name }} &bull; {{ $hotel->address ?? $hotel->city }}</p>
            <p style="margin: 0;">This notification was generated automatically by the Hotel SaaS Management System.</p>
        </div>
    </div>
</body>
</html>
