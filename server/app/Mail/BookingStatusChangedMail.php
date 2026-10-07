<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $previousStatus,
        public string $statusReason = ''
    ) {}

    public function envelope(): Envelope
    {
        $statusMessage = match ($this->reservation->status) {
            'confirmed' => 'Booking Confirmed',
            'checked_in' => 'Check-In Successful',
            'checked_out' => 'Thank You for Your Stay',
            'cancelled' => 'Booking Cancelled',
            default => 'Booking Status Updated',
        };

        return new Envelope(
            subject: $statusMessage . ' - ' . $this->reservation->booking_reference,
        );
    }

    public function content(): Content
    {
        $hotel = $this->reservation->room?->hotel ?? null;
        $statusIcon = $this->getStatusIcon($this->reservation->status);

        return new Content(
            view: 'emails.booking-status-changed',
            with: [
                'guest' => $this->reservation->guest,
                'reservation' => $this->reservation,
                'room' => $this->reservation->room,
                'hotel' => $hotel,
                'bookingReference' => $this->reservation->booking_reference,
                'newStatus' => $this->reservation->status,
                'previousStatus' => $this->previousStatus,
                'statusIcon' => $statusIcon,
                'statusReason' => $this->statusReason,
                'checkInDate' => $this->reservation->check_in_date?->format('F j, Y'),
                'checkOutDate' => $this->reservation->check_out_date?->format('F j, Y'),
                'roomNumber' => $this->reservation->room?->room_number ?? 'TBD',
                'hotelName' => $hotel?->name ?? config('app.name'),
                'hotelPhone' => $hotel?->phone ?? env('HOTEL_PHONE', '+251-900-000-000'),
                'hotelEmail' => $hotel?->email ?? env('HOTEL_EMAIL', 'info@hotel.com'),
                'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
                'checkInInstructions' => $this->getCheckInInstructions($this->reservation->status),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'confirmed' => '',
            'checked_in' => '🚪',
            'checked_out' => '👋',
            'cancelled' => '❌',
            default => '',
        };
    }

    private function getCheckInInstructions(string $status): string
    {
        return match ($status) {
            'confirmed' => 'Your booking has been confirmed. Please arrive on your check-in date. Check-in time is typically from 2:00 PM.',
            'checked_in' => 'Welcome to our hotel! Enjoy your stay. Our staff is available 24/7 for assistance.',
            'checked_out' => 'Thank you for staying with us! We hope you enjoyed your visit. We look forward to seeing you again.',
            'cancelled' => 'Your booking has been cancelled. If you have any questions, please contact our support team.',
            default => 'For more information about your booking, please contact our support team.',
        };
    }
}
