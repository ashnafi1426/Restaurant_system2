<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * SmsNotificationService
 * ============================================================================
 * Handles SMS notifications for guest bookings
 * 
 * Features:
 * - Send confirmation SMS after booking
 * - Send check-in reminders 24 hours before
 * - Send cancellation notifications
 * - Track SMS delivery status
 * - Support for multiple SMS providers (Chapa SMS, etc.)
 * ============================================================================
 */
class SmsNotificationService
{
    /**
     * SMS Provider (can be configured in env)
     */
    private string $provider;

    /**
     * SMS API Key
     */
    private string $apiKey;

    /**
     * SMS API Endpoint
     */
    private string $apiEndpoint;

    /**
     * SMS Sender ID
     */
    private string $senderId;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'chapa');
        $this->apiKey = config('services.sms.api_key', '');
        $this->apiEndpoint = config('services.sms.endpoint', 'https://api.chapa.co/v1/sms');
        $this->senderId = config('services.sms.sender_id', 'HOTEL');
    }

    /**
     * ============================================================================
     * Send Booking Confirmation SMS
     * ============================================================================
     * Sends confirmation SMS to guest after successful booking
     * 
     * @param Reservation $reservation
     * @return array - {success: bool, message_id?: string, error?: string}
     */
    public function sendBookingConfirmation(Reservation $reservation): array
    {
        try {
            $guest = $reservation->guest;

            if (!$guest || !$guest->phone) {
                Log::warning('⚠️ [SMS] No phone number for guest', [
                    'guest_id' => $guest?->id,
                    'reservation_id' => $reservation->id,
                ]);
                return [
                    'success' => false,
                    'error' => 'No phone number available',
                ];
            }

            $message = $this->formatConfirmationMessage($reservation);

            return $this->sendSms(
                $guest->phone,
                $message,
                "booking_confirmation_{$reservation->id}"
            );

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Booking confirmation exception', [
                'message' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ============================================================================
     * Send Check-In Reminder SMS
     * ============================================================================
     * Sends reminder SMS 24 hours before check-in
     * 
     * @param Reservation $reservation
     * @return array - {success: bool, message_id?: string, error?: string}
     */
    public function sendCheckInReminder(Reservation $reservation): array
    {
        try {
            $guest = $reservation->guest;

            if (!$guest || !$guest->phone) {
                Log::warning('⚠️ [SMS] No phone number for guest', [
                    'guest_id' => $guest?->id,
                    'reservation_id' => $reservation->id,
                ]);
                return [
                    'success' => false,
                    'error' => 'No phone number available',
                ];
            }

            $message = $this->formatCheckInReminderMessage($reservation);

            return $this->sendSms(
                $guest->phone,
                $message,
                "checkin_reminder_{$reservation->id}"
            );

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Check-in reminder exception', [
                'message' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ============================================================================
     * Send Cancellation SMS
     * ============================================================================
     * Sends cancellation notification SMS to guest
     * 
     * @param Reservation $reservation
     * @param ?float $refundAmount
     * @return array - {success: bool, message_id?: string, error?: string}
     */
    public function sendCancellationNotification(Reservation $reservation, ?float $refundAmount = null): array
    {
        try {
            $guest = $reservation->guest;

            if (!$guest || !$guest->phone) {
                Log::warning('⚠️ [SMS] No phone number for guest', [
                    'guest_id' => $guest?->id,
                    'reservation_id' => $reservation->id,
                ]);
                return [
                    'success' => false,
                    'error' => 'No phone number available',
                ];
            }

            $message = $this->formatCancellationMessage($reservation, $refundAmount);

            return $this->sendSms(
                $guest->phone,
                $message,
                "cancellation_notification_{$reservation->id}"
            );

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Cancellation notification exception', [
                'message' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ============================================================================
     * Send SMS
     * ============================================================================
     * Generic SMS sending method supporting multiple providers
     * 
     * @param string $phoneNumber - Recipient phone number
     * @param string $message - SMS message content
     * @param string $messageId - Unique message identifier for tracking
     * @return array - {success: bool, message_id?: string, error?: string, provider_response?: array}
     */
    private function sendSms(string $phoneNumber, string $message, string $messageId): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::warning('⚠️ [SMS] SMS API key not configured', [
                    'provider' => $this->provider,
                ]);
                return [
                    'success' => false,
                    'error' => 'SMS service not configured',
                ];
            }

            // Sanitize phone number
            $phoneNumber = $this->sanitizePhoneNumber($phoneNumber);

            Log::info('📱 [SMS] Sending SMS', [
                'provider' => $this->provider,
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'message_id' => $messageId,
                'message_length' => strlen($message),
            ]);

            $response = $this->provider === 'chapa'
                ? $this->sendViaChapaApi($phoneNumber, $message, $messageId)
                : $this->sendViaDefaultProvider($phoneNumber, $message, $messageId);

            if ($response['success']) {
                Log::info('✅ [SMS] SMS sent successfully', [
                    'provider' => $this->provider,
                    'message_id' => $messageId,
                    'provider_message_id' => $response['provider_message_id'] ?? null,
                ]);
            } else {
                Log::error('❌ [SMS] Failed to send SMS', [
                    'provider' => $this->provider,
                    'message_id' => $messageId,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Send SMS exception', [
                'message' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS via Chapa API
     */
    private function sendViaChapaApi(string $phoneNumber, string $message, string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiEndpoint, [
                'phone' => $phoneNumber,
                'message' => $message,
                'sender_id' => $this->senderId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'provider_message_id' => $data['id'] ?? $messageId,
                    'provider_response' => $data,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to send SMS',
                    'provider_response' => $response->json(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Chapa API exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS via default HTTP provider
     */
    private function sendViaDefaultProvider(string $phoneNumber, string $message, string $messageId): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiEndpoint, [
                'api_key' => $this->apiKey,
                'phone' => $phoneNumber,
                'message' => $message,
                'sender_id' => $this->senderId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'provider_response' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send SMS via provider',
                    'provider_response' => $response->json(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ [SMS] Default provider exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format booking confirmation message
     */
    private function formatConfirmationMessage(Reservation $reservation): string
    {
        $hotel = $reservation->room?->hotel;
        $hotelName = $hotel?->name ?? 'Hotel';
        $checkInDate = $reservation->check_in_date?->format('M j');
        $bookingRef = $reservation->booking_reference;

        return "Hi {$reservation->guest->first_name}! Your booking at {$hotelName} is confirmed for {$checkInDate}. Booking Reference: {$bookingRef}. Thank you!";
    }

    /**
     * Format check-in reminder message
     */
    private function formatCheckInReminderMessage(Reservation $reservation): string
    {
        $hotel = $reservation->room?->hotel;
        $hotelName = $hotel?->name ?? 'Hotel';
        $checkInDate = $reservation->check_in_date?->format('M j');
        $checkInTime = '2:00 PM';

        return "Reminder: Your check-in at {$hotelName} is tomorrow ({$checkInDate}) at {$checkInTime}. We look forward to welcoming you!";
    }

    /**
     * Format cancellation message
     */
    private function formatCancellationMessage(Reservation $reservation, ?float $refundAmount = null): string
    {
        $hotel = $reservation->room?->hotel;
        $hotelName = $hotel?->name ?? 'Hotel';
        $bookingRef = $reservation->booking_reference;
        $refundInfo = $refundAmount ? " Refund of ETB {$refundAmount} will be processed in 5-7 days." : '';

        return "Your booking at {$hotelName} (Ref: {$bookingRef}) has been cancelled.{$refundInfo} For assistance, contact support.";
    }

    /**
     * Sanitize phone number - ensure proper format
     */
    private function sanitizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except leading +
        if (strpos($phone, '+') === 0) {
            $phone = '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
        } else {
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Add country code if not present (assuming Ethiopia +251)
            if (!str_starts_with($phone, '251') && !str_starts_with($phone, '0')) {
                $phone = '251' . $phone;
            } elseif (str_starts_with($phone, '0')) {
                $phone = '251' . substr($phone, 1);
            }

            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Mask phone number for logging
     */
    private function maskPhoneNumber(string $phone): string
    {
        // Keep first 5 and last 2 digits
        if (strlen($phone) > 7) {
            return substr($phone, 0, 5) . '***' . substr($phone, -2);
        }
        return '***';
    }
}
