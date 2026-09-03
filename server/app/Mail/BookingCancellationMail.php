<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ============================================================================
 * BookingCancellationMail
 * ============================================================================
 * Sends cancellation notification to guest with refund information
 * 
 * Features:
 * - Cancellation confirmation with booking reference
 * - Refund amount and timeline
 * - Cancellation reason
 * - Rebook incentive or discount code
 * - Contact support option
 * - Queued for async sending
 * ============================================================================
 */
class BookingCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public ?float $refundAmount = null,
        public string $cancellationReason = '',
        public ?string $discountCode = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Cancellation Confirmation - ' . $this->reservation->booking_reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $hotel = $this->reservation->room?->hotel ?? null;
        $refundAmount = $this->refundAmount ?? $this->reservation->total_amount;

        return new Content(
            view: 'emails.booking-cancellation',
            with: [
                'guest' => $this->reservation->guest,
                'reservation' => $this->reservation,
                'room' => $this->reservation->room,
                'hotel' => $hotel,
                'bookingReference' => $this->reservation->booking_reference,
                'originalCheckIn' => $this->reservation->check_in_date?->format('F j, Y'),
                'originalCheckOut' => $this->reservation->check_out_date?->format('F j, Y'),
                'roomNumber' => $this->reservation->room?->room_number ?? 'TBD',
                'originalAmount' => $this->reservation->total_amount,
                'refundAmount' => $refundAmount,
                'currency' => 'ETB',
                'refundTimeline' => '5-7 business days',
                'cancellationReason' => $this->cancellationReason,
                'cancellationDate' => now()->format('F j, Y'),
                'hotelName' => $hotel?->name ?? config('app.name'),
                'hotelPhone' => $hotel?->phone ?? env('HOTEL_PHONE', '+251-900-000-000'),
                'hotelEmail' => $hotel?->email ?? env('HOTEL_EMAIL', 'info@hotel.com'),
                'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
                'discountCode' => $this->discountCode,
                'discountPercentage' => 10, // 10% rebooking discount
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
