@component('mail::message')
# Welcome to {{ $hotelName }}! 🎉

Dear {{ $guest->first_name }} {{ $guest->last_name }},

We're delighted to welcome you to **{{ $hotelName }}**! Your check-in has been confirmed and your room is ready for you.

## Room Details

| Information | Details |
|------------|---------|
| **Room Number** | {{ $roomNumber }} |
| **Room Type** | {{ $roomType }} |
| **Check-Out Date** | {{ $checkOutDate }} |

## What's Included

✓ Wi-Fi Access
✓ 24/7 Room Service
✓ Housekeeping Services
✓ Fitness Center Access
✓ Restaurant & Bar Access
✓ Concierge Services

## Hotel Services & Facilities

- **Front Desk**: Available 24/7 for any assistance
- **Room Service**: Order food and beverages to your room anytime
- **Housekeeping**: Daily room cleaning and laundry services available
- **Fitness Center**: Access to our fully equipped gym
- **Restaurant**: On-site dining with premium cuisine
- **Spa Services**: Relax with our wellness offerings
- **Business Center**: Conference and meeting facilities available

## Important Information

- **Breakfast Service**: Served from 6:30 AM - 10:00 AM in the main dining room
- **Check-Out Time**: 11:00 AM (Late check-out may be available for an additional fee)
- **Lost & Found**: Contact front desk with any lost items
- **Emergency**: Dial 0 from your room for immediate assistance

## Guest Contact Information

If you need anything during your stay, please don't hesitate to contact us:

- **Phone**: {{ $hotelPhone }}
- **Email**: {{ $hotelEmail }}
- **Website**: {{ $hotelWebsite }}
- **Front Desk Extension**: Press 0

## Local Attractions

Explore the wonderful attractions near our hotel:
- City Center: 2 km away
- Beach: 5 km away
- Shopping Mall: 3 km away
- Cultural District: 4 km away

Our concierge team can provide directions and recommendations for restaurants, entertainment, and activities.

---

Thank you for choosing {{ $hotelName }}. We wish you a wonderful stay!

@component('mail::footer')
© {{ date('Y') }} {{ $hotelName }}. All rights reserved.
@endcomponent
@endcomponent
