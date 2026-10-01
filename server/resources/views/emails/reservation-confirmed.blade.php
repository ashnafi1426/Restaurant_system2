<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0 0 8px 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { margin: 0; font-size: 14px; opacity: 0.9; }
        .badge { display: inline-block; background-color: #10b981; color: white; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-top: 10px; }
        .content { padding: 32px 24px; }
        .booking-ref-box { background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 16px; text-align: center; margin-bottom: 24px; }
        .booking-ref-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-bottom: 4px; }
        .booking-ref-value { font-size: 22px; font-family: monospace; font-weight: 800; color: #0f172a; }
        .details-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 20px 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .details-table td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .details-table tr:last-child td { border-bottom: none; }
        .details-label { color: #64748b; font-weight: 600; width: 40%; background-color: #f8fafc; }
        .details-value { color: #0f172a; font-weight: 700; }
        .price-highlight { font-size: 18px; color: #2563eb; }
        .section-title { font-size: 15px; font-weight: 700; color: #0f172a; margin: 24px 0 12px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
        .info-list { padding-left: 20px; margin: 8px 0; font-size: 13px; color: #475569; }
        .info-list li { margin-bottom: 6px; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 24px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Reservation Confirmed!</h1>
            <p>Welcome to <strong>{{ $hotelName }}</strong></p>
            <div class="badge">Confirmed & Guaranteed</div>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $guest->first_name ?? 'Valued' }} {{ $guest->last_name ?? 'Guest' }}</strong>,</p>
            <p>Thank you for booking with us! Your reservation is confirmed and guaranteed. Below are your booking details:</p>

            <div class="booking-ref-box">
                <div class="booking-ref-title">Booking Reference Number</div>
                <div class="booking-ref-value">{{ $bookingReference }}</div>
            </div>

            <div class="section-title">Stay & Accommodation Summary</div>
            <table class="details-table">
                <tr>
                    <td class="details-label">Assigned Room</td>
                    <td class="details-value">Room {{ $roomNumber }} ({{ $roomType }})</td>
                </tr>
                <tr>
                    <td class="details-label">Check-In Date</td>
                    <td class="details-value">{{ $checkInDate }}</td>
                </tr>
                <tr>
                    <td class="details-label">Check-Out Date</td>
                    <td class="details-value">{{ $checkOutDate }}</td>
                </tr>
                <tr>
                    <td class="details-label">Length of Stay</td>
                    <td class="details-value">{{ $nights }} Night{{ $nights > 1 ? 's' : '' }}</td>
                </tr>
                <tr>
                    <td class="details-label">Total Amount</td>
                    <td class="details-value price-highlight">{{ number_format((float)$totalAmount, 2) }} {{ $currency ?? 'ETB' }}</td>
                </tr>
                @if(!empty($specialRequests))
                <tr>
                    <td class="details-label">Special Requests</td>
                    <td class="details-value">{{ $specialRequests }}</td>
                </tr>
                @endif
            </table>

            <div class="section-title">What to Expect</div>
            <ul class="info-list">
                <li><strong>Check-in:</strong> Starts at 2:00 PM on {{ $checkInDate }}.</li>
                <li><strong>Check-out:</strong> Up to 11:00 AM on {{ $checkOutDate }}.</li>
                <li><strong>Identification:</strong> Please present a government-issued ID upon arrival.</li>
            </ul>

            <div class="section-title">Hotel Contact Information</div>
            <ul class="info-list">
                <li><strong>Phone:</strong> {{ $hotelPhone }}</li>
                <li><strong>Email:</strong> {{ $hotelEmail }}</li>
                <li><strong>Front Desk:</strong> 24/7 Assistance available</li>
            </ul>

            <p style="margin-top: 24px; font-size: 13px; color: #64748b;">
                If you have any questions or need to make adjustments to your reservation, please contact our front desk with your booking reference.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</p>
            <p>This is an automated confirmation email for your hotel reservation.</p>
        </div>
    </div>
</body>
</html>
