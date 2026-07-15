<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmed extends Mailable
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
            subject: 'Reservation Confirmed - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-confirmed',
            with: [
                'guest' => $this->reservation->guest,
                'reservation' => $this->reservation,
                'room' => $this->reservation->room,
                'checkInDate' => $this->reservation->check_in_date->format('F j, Y'),
                'checkOutDate' => $this->reservation->check_out_date->format('F j, Y'),
                'nights' => $this->reservation->check_out_date->diffInDays($this->reservation->check_in_date),
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
