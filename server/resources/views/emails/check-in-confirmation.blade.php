<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Confirmation</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .welcome-message {
            background-color: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .detail-box {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #667eea;
            font-weight: 600;
        }
        .info-section {
            background-color: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #f57c00;
        }
        .footer {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
            font-weight: bold;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🎉</div>
            <h1>Welcome to Executive Horizon!</h1>
            <p>Your check-in is confirmed</p>
        </div>

        <div class="content">
            <div class="welcome-message">
                <h2 style="margin-top: 0; color: #667eea;">Hello, {{ $guestName }}!</h2>
                <p style="margin-bottom: 0;">
                    We're delighted to welcome you to <strong>Executive Horizon Hospitality Suite</strong>. 
                    Your check-in has been successfully processed, and your room is ready!
                </p>
            </div>

            <h3 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                📋 Check-in Details
            </h3>

            <div class="detail-box">
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span class="detail-value">{{ $bookingReference }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Number:</span>
                    <span class="detail-value">{{ $roomNumber }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value">{{ $roomType }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">{{ $checkInDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">{{ $checkOutDate }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>🏨 Important Information</h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Your room key has been prepared at the reception desk</li>
                    <li>Check-out time is 12:00 PM</li>
                    <li>Breakfast is served from 7:00 AM to 10:00 AM</li>
                    <li>Wi-Fi password: Available at reception</li>
                    <li>For room service, dial 0 from your room phone</li>
                </ul>
            </div>

            <div style="background-color: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #2e7d32;">✨ During Your Stay</h3>
                <p style="margin-bottom: 0;">
                    Feel free to contact our 24/7 reception desk for any assistance. 
                    We're here to make your stay comfortable and memorable!
                </p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 18px; color: #667eea; font-weight: bold;">
                    We wish you a pleasant stay!
                </p>
                <p style="color: #777; margin-top: 5px;">
                    - The Executive Horizon Team
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>Executive Horizon Hospitality Suite</strong></p>
            <p>Thank you for choosing us for your stay.</p>
            <p style="margin-top: 15px; font-size: 11px;">
                This is an automated email. Please do not reply to this message.
                <br>
                For inquiries, please contact our reception desk.
            </p>
        </div>
    </div>
</body>
</html>
