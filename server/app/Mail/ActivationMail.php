<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $token;
    public string $activationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
        
        // Build activation URL
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $this->activationUrl = "{$frontendUrl}/activate/{$token}";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Activate Your Account',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.activation',
            with: [
                'user' => $this->user,
                'firstName' => $this->user->first_name,
                'lastName' => $this->user->last_name,
                'email' => $this->user->email,
                'role' => ucfirst($this->user->role),
                'activationUrl' => $this->activationUrl,
                'expiresIn' => '24 hours',
                'hotelName' => env('HOTEL_NAME', config('app.name')),
                'hotelPhone' => env('HOTEL_PHONE', '+251-800-000-0000'),
                'hotelEmail' => env('HOTEL_EMAIL', 'support@executivehorizon.com'),
                'hotelWebsite' => env('APP_URL', 'https://executivehorizon.com'),
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
