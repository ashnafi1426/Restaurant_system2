@component('mail::message')
# Reservation Confirmed! 🎉

Dear {{ $guest->first_name }} {{ $guest->last_name }},

Your reservation has been confirmed. We're excited to welcome you to **{{ $hotelName }}**!

## Reservation Details

<table style="width: 100%; margin-top: 20px; margin-bottom: 20px;">
    <tr style="background-color: #f5f5f5;">
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Reservation Number</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $reservation->id }}</td>
    </tr>
    <tr>
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Check-In Date</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $checkInDate }}</td>
    </tr>
    <tr style="background-color: #f5f5f5;">
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Check-Out Date</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $checkOutDate }}</td>
    </tr>
    <tr>
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Number of Nights</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $nights }}</td>
    </tr>
    <tr style="background-color: #f5f5f5;">
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Room Number</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $roomNumber }}</td>
    </tr>
    <tr>
        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">Room Type</td>
        <td style="padding: 12px; border: 1px solid #ddd;">{{ $roomType }}</td>
    </tr>
</table>

## What to Expect

- **Check-in**: Available from 3:00 PM on {{ $checkInDate }}
- **Check-out**: Until 11:00 AM on {{ $checkOutDate }}
- **Early Check-in**: Contact the front desk to request early check-in
- **Late Check-out**: Additional charges may apply

## Guest Information

| Details | Information |
|---------|-------------|
| Guest Name | {{ $guest->first_name }} {{ $guest->last_name }} |
| Email | {{ $guest->email }} |
| Phone | {{ $guest->phone ?? 'Not provided' }} |

## Contact Information

If you have any questions or need assistance, please don't hesitate to contact us:

- **Phone**: {{ $hotelPhone }}
- **Email**: {{ $hotelEmail }}
- **Website**: {{ $hotelWebsite }}
- **Front Desk**: Available 24/7

## Important Information

- Please keep your reservation number handy for check-in
- Bring a valid ID and payment method
- Children under 12 years stay free with adults
- Cancellation Policy: Free cancellation up to 48 hours before check-in

We look forward to providing you with an exceptional experience!

---

@component('mail::footer')
© {{ date('Y') }} {{ $hotelName }}. All rights reserved.
@endcomponent
@endcomponent
