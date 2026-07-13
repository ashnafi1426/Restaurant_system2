@component('mail::message')
# Thank You for Your Stay! 🙏

Dear {{ $guest->first_name }} {{ $guest->last_name }},

We hope you had a wonderful experience at **{{ $hotelName }}**! Thank you for staying with us and we hope to see you again soon.

## Your Stay Summary

| Information | Details |
|------------|---------|
| **Reservation Number** | {{ $reservation->id }} |
| **Check-In Date** | {{ $checkInDate }} |
| **Check-Out Date** | {{ $checkOutDate }} |
| **Total Nights** | {{ $nights }} |

## Guest Feedback

Your feedback is important to us. We'd love to hear about your experience:

@component('mail::button', ['url' => $hotelWebsite . '/feedback'])
Share Your Feedback
@endcomponent

## Items Left Behind

If you believe you left anything in your room, please contact our Lost & Found department:

- **Phone**: {{ $hotelPhone }}
- **Email**: {{ $hotelEmail }}
- **Website**: {{ $hotelWebsite }}

We keep lost items for 30 days before donating them.

## Special Offers for Your Next Visit

We appreciate your business! Use code **WELCOME-BACK** for 15% off your next reservation.

Valid until: 12 months from your checkout date

## Why Stay With Us Again?

✓ Exceptional Customer Service
✓ Prime Location
✓ Modern Amenities
✓ Competitive Rates
✓ Loyalty Rewards Program

## Stay Connected

Follow us on social media for exclusive deals and updates:
- Facebook: @{{ str_replace(' ', '', $hotelName) }}
- Instagram: @{{ str_replace(' ', '', $hotelName) }}
- Twitter: @{{ str_replace(' ', '', $hotelName) }}

## Contact Information

For any follow-up inquiries or to book your next stay:

- **Phone**: {{ $hotelPhone }}
- **Email**: {{ $hotelEmail }}
- **Website**: {{ $hotelWebsite }}
- **Front Desk**: Available 24/7

---

We look forward to welcoming you back to {{ $hotelName }}!

@component('mail::footer')
© {{ date('Y') }} {{ $hotelName }}. All rights reserved.
@endcomponent
@endcomponent
