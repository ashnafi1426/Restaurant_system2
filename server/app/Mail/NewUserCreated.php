<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserCreated extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $temporaryPassword;
    public string $loginUrl;

    public function __construct(User $user, string $temporaryPassword)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
        $this->loginUrl = config('app.frontend_url') . '/login';
    }

    public function build()
    {
        return $this->subject('Your Account Has Been Created - ' . config('app.name'))
                    ->view('emails.new-user-created')
                    ->with([
                        'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                        'email' => $this->user->email,
                        'temporaryPassword' => $this->temporaryPassword,
                        'role' => ucfirst($this->user->role),
                        'loginUrl' => $this->loginUrl,
                    ]);
    }
}
