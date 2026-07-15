<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Gmail Service - Sends emails via Gmail SMTP using Laravel Mail
 */
class GmailService
{
    /**
     * Send email to recipient
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlBody)
    {
        try {
            Mail::html($htmlBody, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName)
                        ->subject($subject)
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            
            Log::info('📧 [GMAIL] Email sent successfully', [
                'to' => $toEmail,
                'subject' => $subject,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('📧 [GMAIL] Failed to send email: ' . $e->getMessage(), [
                'to' => $toEmail,
                'subject' => $subject,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Send reservation confirmation email
     */
    public function sendReservationConfirmation($guest, $reservation)
    {
        $htmlBody = $this->getReservationConfirmationHtml($guest, $reservation);
        
        return $this->sendEmail(
            $guest->email,
            $guest->first_name . ' ' . $guest->last_name,
            'Reservation Confirmed - ' . config('app.name'),
            $htmlBody
        );
    }
    
    /**
     * Send check-in confirmation email
     */
    public function sendCheckInConfirmation($guest, $reservation)
    {
        $htmlBody = $this->getCheckInHtml($guest, $reservation);
        
        return $this->sendEmail(
            $guest->email,
            $guest->first_name . ' ' . $guest->last_name,
            'Welcome to ' . config('app.name'),
            $htmlBody
        );
    }
    
    /**
     * Send check-out notification email
     */
    public function sendCheckOutNotification($guest, $reservation)
    {
        $htmlBody = $this->getCheckOutHtml($guest, $reservation);
        
        return $this->sendEmail(
            $guest->email,
            $guest->first_name . ' ' . $guest->last_name,
            'Thank You for Your Stay - ' . config('app.name'),
            $htmlBody
        );
    }
    
    /**
     * Get HTML for reservation confirmation
     */
    protected function getReservationConfirmationHtml($guest, $reservation)
    {
        $checkIn = $reservation->check_in_date->format('F j, Y');
        $checkOut = $reservation->check_out_date->format('F j, Y');
        $nights = $reservation->check_out_date->diffInDays($reservation->check_in_date);
        $room = $reservation->room;
        $roomType = $room->room_type->name ?? 'Standard';
        
        return view('emails.reservation-confirmed', [
            'guest' => $guest,
            'reservation' => $reservation,
            'room' => $room,
            'checkInDate' => $checkIn,
            'checkOutDate' => $checkOut,
            'nights' => $nights,
            'roomNumber' => $room->room_number,
            'roomType' => $roomType,
            'hotelName' => config('app.name'),
            'hotelPhone' => env('HOTEL_PHONE', '+1-800-000-0000'),
            'hotelEmail' => env('HOTEL_EMAIL', 'info@hotel.com'),
            'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
        ])->render();
    }
    
    /**
     * Get HTML for check-in
     */
    protected function getCheckInHtml($guest, $reservation)
    {
        $room = $reservation->room;
        $checkOut = $reservation->check_out_date->format('F j, Y');
        $roomType = $room->room_type->name ?? 'Standard';
        
        return view('emails.check-in-confirmed', [
            'guest' => $guest,
            'reservation' => $reservation,
            'room' => $room,
            'checkOutDate' => $checkOut,
            'roomNumber' => $room->room_number,
            'roomType' => $roomType,
            'hotelName' => config('app.name'),
            'hotelPhone' => env('HOTEL_PHONE', '+1-800-000-0000'),
            'hotelEmail' => env('HOTEL_EMAIL', 'info@hotel.com'),
            'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
        ])->render();
    }
    
    /**
     * Get HTML for check-out
     */
    protected function getCheckOutHtml($guest, $reservation)
    {
        $checkIn = $reservation->check_in_date->format('F j, Y');
        $checkOut = $reservation->check_out_date->format('F j, Y');
        $nights = $reservation->check_out_date->diffInDays($reservation->check_in_date);
        
        return view('emails.check-out-notification', [
            'guest' => $guest,
            'reservation' => $reservation,
            'room' => $reservation->room,
            'checkInDate' => $checkIn,
            'checkOutDate' => $checkOut,
            'nights' => $nights,
            'hotelName' => config('app.name'),
            'hotelPhone' => env('HOTEL_PHONE', '+1-800-000-0000'),
            'hotelEmail' => env('HOTEL_EMAIL', 'info@hotel.com'),
            'hotelWebsite' => env('APP_URL', 'https://hotel.com'),
        ])->render();
    }
}
