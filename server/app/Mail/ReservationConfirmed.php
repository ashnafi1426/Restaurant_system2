<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\Hotel;
use Carbon\Carbon;
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

    public function envelope(): Envelope
    {
        $hotel = $this->getHotel();
        $hotelName = $hotel?->name ?? config('app.name', 'Hotel Reservation');
        $ref = $this->reservation->booking_reference ?? $this->reservation->id;

        return new Envelope(
            subject: "Reservation Confirmed (#{$ref}) - {$hotelName}",
        );
    }

    protected function getHotel(): ?Hotel
    {
        if ($this->reservation->hotel_id) {
            return Hotel::find($this->reservation->hotel_id);
        }
        return $this->reservation->room?->hotel ?? null;
    }

    public function content(): Content
    {
        $hotel = $this->getHotel();
        $hotelName = $hotel?->name ?? config('app.name', 'Hotel');

        $checkInRaw = $this->reservation->check_in_date;
        $checkOutRaw = $this->reservation->check_out_date;

        $checkIn = $checkInRaw instanceof \DateTimeInterface ? Carbon::instance($checkInRaw) : Carbon::parse($checkInRaw);
        $checkOut = $checkOutRaw instanceof \DateTimeInterface ? Carbon::instance($checkOutRaw) : Carbon::parse($checkOutRaw);
        $nights = max(1, (int) $checkOut->diffInDays($checkIn));

        return new Content(
            view: 'emails.reservation-confirmed',
            with: [
                'guest'            => $this->reservation->guest,
                'reservation'      => $this->reservation,
                'room'             => $this->reservation->room,
                'bookingReference' => $this->reservation->booking_reference ?? $this->reservation->id,
                'checkInDate'      => $checkIn->format('F j, Y'),
                'checkOutDate'     => $checkOut->format('F j, Y'),
                'nights'           => $nights,
                'roomNumber'       => $this->reservation->room?->room_number ?? 'TBD',
                'roomType'         => $this->reservation->room?->roomType?->name ?? 'Standard',
                'totalAmount'      => $this->reservation->total_amount ?? 0,
                'currency'         => $hotel?->currency ?? 'ETB',
                'hotelName'        => $hotelName,
                'hotelPhone'       => $hotel?->phone ?? env('HOTEL_PHONE', '+251-900-000-000'),
                'hotelEmail'       => $hotel?->email ?? env('HOTEL_EMAIL', 'info@hotel.com'),
                'hotelWebsite'     => env('APP_URL', 'https://hotel.com'),
                'specialRequests'  => $this->reservation->special_requests,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
