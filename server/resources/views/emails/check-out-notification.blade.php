<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #FF9800; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px; }
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table td { padding: 12px; border: 1px solid #ddd; }
        .details-table tr:nth-child(even) td { background-color: #f5f5f5; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .checkmark { color: #4CAF50; font-weight: bold; }
        ul { line-height: 1.8; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #FF9800; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Stay!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $guest->first_name }} {{ $guest->last_name }},</p>

            <p>We hope you had a wonderful experience at <strong>{{ $hotelName }}</strong>! Thank you for staying with us and we hope to see you again soon.</p>

            <h2>Your Stay Summary</h2>
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
                    <td style="font-weight: bold; background-color: #f5f5f5;">Total Nights</td>
                    <td>{{ $nights }}</td>
                </tr>
            </table>

            <h2>Guest Feedback</h2>
            <p>Your feedback is important to us. We'd love to hear about your experience at {{ $hotelWebsite }}/feedback</p>

            <h2>Items Left Behind</h2>
            <p>If you believe you left anything in your room, please contact our Lost & Found department:</p>
            <ul>
                <li><strong>Phone:</strong> {{ $hotelPhone }}</li>
                <li><strong>Email:</strong> {{ $hotelEmail }}</li>
                <li><strong>Website:</strong> {{ $hotelWebsite }}</li>
            </ul>
            <p><em>We keep lost items for 30 days before donating them.</em></p>

            <h2>Special Offers for Your Next Visit</h2>
            <p>We appreciate your business! Use code <strong>WELCOME-BACK</strong> for 15% off your next reservation.</p>
            <p><em>Valid until: 12 months from your checkout date</em></p>

            <h2>Why Stay With Us Again?</h2>
            <ul>
                <li><span class="checkmark">✓</span> Exceptional Customer Service</li>
                <li><span class="checkmark">✓</span> Prime Location</li>
                <li><span class="checkmark">✓</span> Modern Amenities</li>
                <li><span class="checkmark">✓</span> Competitive Rates</li>
                <li><span class="checkmark">✓</span> Loyalty Rewards Program</li>
            </ul>

            <h2>Contact Information</h2>
            <p>For any follow-up inquiries or to book your next stay:</p>
            <ul>
                <li><strong>Phone:</strong> {{ $hotelPhone }}</li>
                <li><strong>Email:</strong> {{ $hotelEmail }}</li>
                <li><strong>Website:</strong> {{ $hotelWebsite }}</li>
                <li><strong>Front Desk:</strong> Available 24/7</li>
            </ul>

            <p>We look forward to welcoming you back to {{ $hotelName }}!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
