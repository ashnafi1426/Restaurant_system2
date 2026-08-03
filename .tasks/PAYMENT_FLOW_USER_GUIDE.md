# 💳 Payment Flow - Complete User Guide

## Overview
This guide shows the complete payment flow for hotel reservations without requiring login.

---

## 📱 Step 1: Browse Rooms & Select Room

**Screen**: `/`
- Browse available hotel rooms
- Click on room to view details
- Click "Book Now" to open booking modal

---

## 📋 Step 2: Fill Booking Form

**Screen**: Booking Modal (No Auth Required ✅)

**Form Fields**:
- ✅ Check-in Date (required)
- ✅ Check-out Date (required)
- ✅ Number of Guests (required)
- ✅ Full Name (required) *
- ✅ Email Address (required) *
- ✅ Phone Number (required) *
- Optional: Room Preference
- Optional: Special Requests
- Optional: Add-ons (Breakfast, Dinner, Spa)

**Room Details Shown**:
- Room image
- Room number & type
- Price per night in ETB
- Available amenities
- Room rating
- Stay duration & prices
- Grand total with 15% tax

*A guest account is auto-created with this information

---

## 💳 Step 3: Click "💳 Proceed to Payment"

**Action**: Green button at bottom of form

**What Happens**:
1. ✅ Validates required fields
2. ✅ Guest account auto-created/retrieved
3. ✅ Opens BLUE payment confirmation modal

**Payment Confirmation Modal Shows**:
```
╔══════════════════════════════════════╗
║  Payment Confirmation                ║
║  Review your booking details before   ║
║  payment                              ║
╠══════════════════════════════════════╣
║ Booking Summary                      ║
├──────────────────────────────────────┤
║ Room: 303 - Standard                 ║
║ Check-in: 2026-08-03                 ║
║ Check-out: 2026-08-04                ║
║ Nights: 1                            ║
║ Guests: 2                            ║
├──────────────────────────────────────┤
║ Price Breakdown                      ║
├──────────────────────────────────────┤
║ 1 nights × 50 ETB        = 50.00 ETB ║
║ Tax (15%)                = 7.50 ETB  ║
├──────────────────────────────────────┤
║ Total Amount:            57.50 ETB   ║
├──────────────────────────────────────┤
║ ✓ Your payment is secure and        ║
║   processed through Chapa payment    ║
║   gateway                            ║
╠══════════════════════════════════════╣
║ [Cancel]      [💳 Pay Now]          ║
╚══════════════════════════════════════╝
```

---

## 💳 Step 4: Click "💳 Pay Now" Button

**Action**: BLUE "Pay Now" button in modal

**What Happens**:
1. ✅ Modal closes
2. ✅ Booking form closes
3. ✅ Redirects to **Chapa Payment Gateway**
4. ✅ Booking data saved in browser (sessionStorage)

---

## 🔒 Step 5: Complete Payment at Chapa

**Screen**: Chapa Checkout Page (https://chapa.co)

**Payment Options**:
- 💳 Credit/Debit Card
- 📱 Mobile Money
- 🏦 Bank Transfer
- Other payment methods

**Required Info**:
- Name (pre-filled)
- Email (pre-filled)
- Phone (pre-filled)
- Payment method selection
- Payment credentials

**Amount Displayed**: 57.50 ETB

---

## ✅ Step 6: Payment Verification

**Behind Scenes**:
1. Chapa processes payment
2. Backend verifies with Chapa API
3. Payment status confirmed
4. Reservation automatically created

**Duration**: Usually 2-5 seconds

---

## 🎉 Step 7: Success Page

**Screen**: `/payment/success`

**Displays**:
```
╔═══════════════════════════════════════╗
║                                       ║
║  ✓ Payment Successful!                ║
║                                       ║
║  Your booking is confirmed           ║
║                                       ║
║  Booking Reference: BKG-202608031234 ║
║                                       ║
║ ─────────────────────────────────────┤
║                                       ║
║  Guest Information:                   ║
║  Name: Ashenafi Sileshi              ║
║  Email: ashenafi@example.com         ║
║  Phone: +251912345678                ║
║                                       ║
║  Reservation Details:                 ║
║  Room: 303 - Standard                 ║
║  Check-in: Aug 3, 2026                ║
║  Check-out: Aug 4, 2026               ║
║  Guests: 2                            ║
║  Nights: 1                            ║
║                                       ║
║  Payment Information:                 ║
║  Amount Paid: 57.50 ETB               ║
║  Payment Method: Card                 ║
║  Transaction ID: TX-202608031234ABC  ║
║                                       ║
║ ─────────────────────────────────────┤
║                                       ║
║  Next Steps:                          ║
║  1. Check your email for confirmation ║
║  2. Follow check-in instructions      ║
║  3. You'll receive room details       ║
║                                       ║
║  [📥 Download Receipt] [🏠 Return Home]║
║                                       ║
╚═══════════════════════════════════════╝
```

**Information Provided**:
- ✅ Booking confirmation
- ✅ Booking reference number
- ✅ Guest details
- ✅ Room information
- ✅ Check-in/out dates
- ✅ Payment confirmation
- ✅ Next steps
- ✅ Download receipt option

---

## ❌ Step 8: If Payment Fails

**Screen**: `/payment/failed`

**Shows**:
- ❌ Payment Failed message
- Common failure reasons:
  - Insufficient funds
  - Card declined
  - Network error
  - Timeout
- Troubleshooting steps
- **[🔄 Try Payment Again]** button
- **[🏠 Return Home]** button

**Action**: User can:
1. Try different payment method
2. Return home and restart booking
3. Contact support

---

## 🔄 Payment Flow Diagram

```
┌─────────────────────┐
│  Browse Rooms       │
│  Select Room        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Booking Modal      │
│  (No Login Needed)  │
│  Fill Form:         │
│  - Dates            │
│  - Guest Info       │
│  - Special Requests │
└──────────┬──────────┘
           │
           ▼
    [💳 Proceed to Payment]
           │
           ▼
┌─────────────────────┐
│ Payment Confirmation│
│ Modal (BLUE)        │
│ Show Summary & Price│
│ with 15% Tax        │
│ ETB Currency        │
└──────────┬──────────┘
           │
           ▼
    [💳 Pay Now]
           │
           ▼
┌─────────────────────┐
│  Chapa Gateway      │
│  Complete Payment   │
│  Verify Transaction │
└──────────┬──────────┘
           │
    ┌──────┴──────┐
    │             │
    ▼             ▼
SUCCESS       FAILURE
    │             │
    ▼             ▼
┌────────┐   ┌─────────┐
│Success │   │ Failed  │
│Page    │   │ Page    │
│✓ Booking
│ Created │   │ Retry   │
└────────┘   │ Option  │
             └─────────┘
```

---

## 💡 Key Features

✅ **No Login Required**
- Guest account auto-created with booking
- Email can be new or existing

✅ **Secure Payment**
- Payment processed through Chapa (certified payment gateway)
- PCI-DSS compliant
- SSL encrypted

✅ **Transparent Pricing**
- Room price clearly shown (per night in ETB)
- Tax calculation visible (15% of subtotal)
- Total amount shown before payment

✅ **Automatic Booking Creation**
- Only created after payment verification
- Prevents double-booking
- Ensures valid payment

✅ **Confirmation Flow**
- Booking reference provided
- Confirmation email sent
- Check-in instructions provided

---

## 🆘 Troubleshooting

### Issue: "Cannot assign null to property `$secureKey`"
**Solution**: Chapa API keys not loaded
- Backend: `php artisan config:clear && php artisan cache:clear`
- ✅ Fixed

### Issue: "Route [login] not defined"
**Solution**: Payment endpoint required auth
- Moved routes to public section
- ✅ Fixed

### Issue: "Payment gateway redirect failed"
**Solution**: Check Chapa credentials in .env
- Verify `CHAPA_SECRET_KEY` and `CHAPA_PUBLIC_KEY`
- Ensure network connectivity

### Issue: "Guest not found after creation"
**Solution**: API endpoint issue
- Check `/api/guests` is accessible
- Verify response format matches expectations

---

## 📞 Support

For issues or questions:
1. Check browser console (F12) for error messages
2. Check server logs: `storage/logs/laravel.log`
3. Verify Chapa configuration in `.env`
4. Contact technical support with error details

---

## ✨ System Status

🟢 **Payment Flow Ready**
- ✅ Routes configured
- ✅ Guest creation working
- ✅ Chapa integration active
- ✅ Payment verification functional
- ✅ Booking creation automatic

**Test Mode**: Chapa TEST environment (no real charges)

---

## 🎯 Next Test

1. Go to `/` (Rooms page)
2. Select a room
3. Click "Book Now"
4. Fill booking form (all fields)
5. Click "💳 Proceed to Payment"
6. Review modal & click "💳 Pay Now"
7. Complete test payment on Chapa
8. See success page with booking confirmation

🚀 **Ready to Go!**
