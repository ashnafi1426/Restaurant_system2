<?php

namespace App\Services;

use App\Mail\ActivationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ActivationService
{
    /**
     * Generate and send activation email for a new user.
     *
     * @param User $user
     * @return array
     */
    public function generateActivationToken(User $user): array
    {
        try {
            // Generate UUID token
            $token = Str::uuid()->toString();
            
            // Set expiration (24 hours from now)
            $expiresAt = Carbon::now()->addHours(24);
            
            // Update user with activation token
            $user->update([
                'activation_token' => $token,
                'activation_token_expires_at' => $expiresAt,
                'activation_status' => 'pending',
                'password_hash' => null, // Ensure no password set
                'email_verified_at' => null
            ]);
            
            // Send activation email
            $this->sendActivationEmail($user, $token);
            
            // Log the action
            Log::info('Activation token generated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);
            
            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to generate activation token', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send activation email'
            ];
        }
    }

    /**
     * Validate activation token.
     *
     * @param string $token
     * @return array
     */
    public function validateToken(string $token): array
    {
        $user = User::where('activation_token', $token)->first();
        
        if (!$user) {
            return [
                'valid' => false,
                'message' => 'Invalid activation link',
                'error_type' => 'invalid_token'
            ];
        }
        
        // Check if already activated
        if ($user->activation_status === 'activated') {
            return [
                'valid' => false,
                'message' => 'Account already activated',
                'error_type' => 'already_activated',
                'user' => $user
            ];
        }
        
        // Check if token expired
        if (Carbon::now()->isAfter($user->activation_token_expires_at)) {
            $user->update(['activation_status' => 'expired']);
            
            return [
                'valid' => false,
                'message' => 'Activation link has expired',
                'error_type' => 'expired',
                'user' => $user
            ];
        }
        
        return [
            'valid' => true,
            'user' => $user
        ];
    }

    /**
     * Activate user account with password.
     *
     * @param string $token
     * @param string $password
     * @return array
     */
    public function activateAccount(string $token, string $password): array
    {
        // Validate token first
        $validation = $this->validateToken($token);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        $user = $validation['user'];
        
        try {
            // Set password and activate account
            $user->update([
                'password_hash' => Hash::make($password),
                'activation_token' => null,
                'activation_token_expires_at' => null,
                'activation_status' => 'activated',
                'email_verified_at' => Carbon::now(),
                'is_active' => true
            ]);
            
            // Log successful activation
            Log::info('Account activated successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);
            
            return [
                'success' => true,
                'message' => 'Account activated successfully',
                'user' => $user
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to activate account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to activate account. Please try again.'
            ];
        }
    }

    /**
     * Resend activation email.
     *
     * @param string $email
     * @return array
     */
    public function resendActivation(string $email): array
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Check if already activated
        if ($user->activation_status === 'activated') {
            return [
                'success' => false,
                'message' => 'Account is already activated'
            ];
        }
        
        // Generate new token
        return $this->generateActivationToken($user);
    }

    /**
     * Send activation email to user.
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    private function sendActivationEmail(User $user, string $token): void
    {
        try {
            Mail::to($user->email)->send(new ActivationMail($user, $token));
            
            Log::info('Activation email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send activation email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Check if user needs activation.
     *
     * @param User $user
     * @return bool
     */
    public function needsActivation(User $user): bool
    {
        return $user->activation_status === 'pending' || 
               $user->activation_status === 'expired';
    }
}
