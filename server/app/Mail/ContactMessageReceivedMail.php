<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\Hotel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactMessage $contactMessage;
    public Hotel $hotel;

    public function __construct(ContactMessage $contactMessage, Hotel $hotel)
    {
        $this->contactMessage = $contactMessage;
        $this->hotel = $hotel;
    }

    public function build()
    {
        $subject = "New Contact Inquiry from {$this->contactMessage->name} - {$this->hotel->name}";

        return $this->subject($subject)
            ->replyTo($this->contactMessage->email, $this->contactMessage->name)
            ->view('emails.contact-inquiry')
            ->with([
                'contactMessage' => $this->contactMessage,
                'hotel' => $this->hotel,
            ]);
    }
}
