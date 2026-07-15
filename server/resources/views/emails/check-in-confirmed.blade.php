<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px; }
        .details-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .details-table td { padding: 12px; border: 1px solid #ddd; }
        .details-table tr:nth-child(even) td { background-color: #f5f5f5; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .checkmark { color: #4CAF50; font-weight: bold; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Welcome to {{ $hotelName }}!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $guest->first_name }} {{ $guest->last_name }},</p>

            <p>We're delighted to welcome you to <strong>{{ $hotelName }}</strong>! Your check-in has been confirmed and your room is ready for you.</p>

            <h2>Room Details</h2>
            <table class="details-table">
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Room Number</td>
                    <td>{{ $roomNumber }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Room Type</td>
                    <td>{{ $roomType }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f5f5f5;">Check-Out Date</td>
                    <td>{{ $checkOutDate }}</td>
                </tr>
            </table>

            <h2>What's Included</h2>
            <ul>
                <li><span class="checkmark">✓</span> Wi-Fi Access</li>
                <li><span class="checkmark">✓</span> 24/7 Room Service</li>
                <li><span class="checkmark">✓</span> Housekeeping Services</li>
                <li><span class="checkmark">✓</span> Fitness Center Access</li>
                <li><span class="checkmark">✓</span> Restaurant & Bar Access</li>
                <li><span class="checkmark">✓</span> Concierge Services</li>
            </ul>

            <h2>Hotel Services & Facilities</h2>
            <ul>
                <li><strong>Front Desk:</strong> Available 24/7 for any assistance</li>
                <li><strong>Room Service:</strong> Order food and beverages to your room anytime</li>
                <li><strong>Housekeeping:</strong> Daily room cleaning and laundry services available</li>
                <li><strong>Fitness Center:</strong> Access to our fully equipped gym</li>
                <li><strong>Restaurant:</strong> On-site dining with premium cuisine</li>
                <li><strong>Spa Services:</strong> Relax with our wellness offerings</li>
                <li><strong>Business Center:</strong> Conference and meeting facilities available</li>
            </ul>

            <h2>Important Information</h2>
            <ul>
                <li><strong>Breakfast Service:</strong> Served from 6:30 AM - 10:00 AM in the main dining room</li>
                <li><strong>Check-Out Time:</strong> 11:00 AM (Late check-out may be available for an additional fee)</li>
                <li><strong>Lost & Found:</strong> Contact front desk with any lost items</li>
                <li><strong>Emergency:</strong> Dial 0 from your room for immediate assistance</li>
            </ul>

            <h2>Contact Information</h2>
            <p>If you need anything during your stay, please don't hesitate to contact us:</p>
            <ul>
                <li><strong>Phone:</strong> {{ $hotelPhone }}</li>
                <li><strong>Email:</strong> {{ $hotelEmail }}</li>
                <li><strong>Website:</strong> {{ $hotelWebsite }}</li>
                <li><strong>Front Desk:</strong> Press 0 from your room</li>
            </ul>

            <p>Thank you for choosing {{ $hotelName }}. We wish you a wonderful stay!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
