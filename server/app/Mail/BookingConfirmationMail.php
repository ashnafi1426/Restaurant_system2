<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmation - ' . ($this->reservation->booking_reference ?? 'Hotel Reservation'),
        );
    }

    public function content(): Content
    {
        $hotel = $this->reservation->room?->hotel ?? null;
        $nights = $this->reservation->total_nights ?? 1;

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'guest' => $this->reservation->guest,
                'reservation' => $this->reservation,
                'room' => $this->reservation->room,
                'hotel' => $hotel,
                'bookingReference' => $this->reservation->booking_reference,
                'checkInDate' => $this->reservation->check_in_date?->format('F j, Y'),
                'checkOutDate' => $this->reservation->check_out_date?->format('F j, Y'),
                'nights' => $nights,
                'roomNumber' => $this->reservation->room?->room_number ?? 'TBD',
                'roomType' => $this->reservation->room?->roomType?->name ?? 'Standard',
                'totalAmount' => $this->reservation->total_amount ?? 0,
                'currency' => 'ETB',
                'hotelName' => $hotel?->name ?? config('app.name'),
                'hotelPhone' => $hotel?->phone ?? env('HOTEL_PHONE', '+251-900-000-000'),
                'hotelEmail' => $hotel?->email ?? env('HOTEL_EMAIL', 'info@hotel.com'),
                'hotelAddress' => $hotel?->address ?? 'Hotel Address',
                'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
                'specialRequests' => $this->reservation->special_requests,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
