<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Hotel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HotelAdminPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $temporaryPassword;
    public ?Hotel $hotel;
    public bool $isResend;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $temporaryPassword, ?Hotel $hotel = null, bool $isResend = false)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
        $this->hotel = $hotel;
        $this->isResend = $isResend;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $hotelName = $this->hotel?->name ?? 'Hotel Management System';
        $subject = $this->isResend
            ? "Your Hotel Admin Password Has Been Reset - {$hotelName}"
            : "Your Administrator Login Credentials - {$hotelName}";

        return $this->subject($subject)
                    ->view('emails.hotel-admin-credentials')
                    ->with([
                        'userName' => "{$this->user->first_name} {$this->user->last_name}",
                        'email' => $this->user->email,
                        'temporaryPassword' => $this->temporaryPassword,
                        'hotelName' => $hotelName,
                        'loginUrl' => config('app.frontend_url', 'http://localhost:5173') . '/login',
                        'isResend' => $this->isResend,
                    ]);
    }
}
