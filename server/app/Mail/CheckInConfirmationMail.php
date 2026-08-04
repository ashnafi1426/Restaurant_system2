<?php

namespace App\Mail;

use App\Models\CheckIn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CheckInConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $checkIn;

    /**
     * Create a new message instance.
     */
    public function __construct(CheckIn $checkIn)
    {
        $this->checkIn = $checkIn;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $guestName = $this->checkIn->guest->first_name . ' ' . $this->checkIn->guest->last_name;
        $roomNumber = $this->checkIn->room->room_number;
        $roomType = $this->checkIn->room->roomType->name ?? 'Room';
        $checkInDate = $this->checkIn->checked_in_at->format('F d, Y');
        $checkOutDate = $this->checkIn->expected_check_out_at->format('F d, Y');

        return $this->subject('Welcome! Your Check-in is Confirmed')
                    ->view('emails.check-in-confirmation')
                    ->with([
                        'guestName' => $guestName,
                        'roomNumber' => $roomNumber,
                        'roomType' => $roomType,
                        'checkInDate' => $checkInDate,
                        'checkOutDate' => $checkOutDate,
                        'bookingReference' => $this->checkIn->reservation->booking_reference,
                    ]);
    }
}
