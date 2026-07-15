<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px; }
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table td { padding: 12px; border: 1px solid #ddd; }
        .details-table tr:nth-child(even) { background-color: #f5f5f5; }
        .details-table tr:nth-child(even) td { background-color: #f5f5f5; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Reservation Confirmed!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $guest->first_name }} {{ $guest->last_name }},</p>

            <p>Your reservation has been confirmed. We're excited to welcome you to <strong>{{ $hotelName }}</strong>!</p>

            <h2>Reservation Details</h2>
            <table class="details-table">
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Reservation Number</td>
                    <td>{{ $reservation->id }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Check-In Date</td>
                    <td>{{ $checkInDate }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Check-Out Date</td>
                    <td>{{ $checkOutDate }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Number of Nights</td>
                    <td>{{ $nights }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Room Number</td>
                    <td>{{ $roomNumber }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Room Type</td>
                    <td>{{ $roomType }}</td>
                </tr>
            </table>

            <h2>What to Expect</h2>
            <ul>
                <li><strong>Check-in:</strong> Available from 3:00 PM on {{ $checkInDate }}</li>
                <li><strong>Check-out:</strong> Until 11:00 AM on {{ $checkOutDate }}</li>
                <li><strong>Early Check-in:</strong> Contact the front desk to request early check-in</li>
                <li><strong>Late Check-out:</strong> Additional charges may apply</li>
            </ul>

            <h2>Contact Information</h2>
            <p>If you have any questions or need assistance, please don't hesitate to contact us:</p>
            <ul>
                <li><strong>Phone:</strong> {{ $hotelPhone }}</li>
                <li><strong>Email:</strong> {{ $hotelEmail }}</li>
                <li><strong>Website:</strong> {{ $hotelWebsite }}</li>
                <li><strong>Front Desk:</strong> Available 24/7</li>
            </ul> 

            <h2>Important Information</h2>
            <ul>
                <li>Please keep your reservation number handy for check-in</li>
                <li>Bring a valid ID and payment method</li>
                <li>Children under 12 years stay free with adults</li>
                <li>Cancellation Policy: Free cancellation up to 48 hours before check-in</li>
            </ul>

            <p>We look forward to providing you with an exceptional experience!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
