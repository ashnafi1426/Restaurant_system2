<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckInConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Check-In Confirmed - Welcome to ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.check-in-confirmed',
            with: [
                'guest' => $this->reservation->guest,
                'reservation' => $this->reservation,
                'room' => $this->reservation->room,
                'checkOutDate' => $this->reservation->check_out_date->format('F j, Y'),
                'roomNumber' => $this->reservation->room->room_number,
                'roomType' => $this->reservation->room->roomType?->name ?? 'Standard',
                'hotelName' => config('app.name'),
                'hotelPhone' => env('HOTEL_PHONE', '+1-800-000-0000'),
                'hotelEmail' => env('HOTEL_EMAIL', 'info@hotel.com'),
                'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
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
