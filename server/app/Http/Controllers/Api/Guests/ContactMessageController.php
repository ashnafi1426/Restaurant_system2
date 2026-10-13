<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    /**
     * Submit a contact inquiry for a specific hotel.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hotel_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Find hotel by ID or slug
        $hotel = Hotel::where('id', $validated['hotel_id'])
            ->orWhere('slug', $validated['hotel_id'])
            ->first();

        if (!$hotel) {
            $hotel = Hotel::where('status', Hotel::STATUS_ACTIVE)->first();
        }

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found.',
            ], 404);
        }

        // Store inquiry in database
        $contactMessage = ContactMessage::create([
            'hotel_id' => $hotel->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'unread',
        ]);

        // Determine destination hotel email
        $recipientEmail = null;
        if (strtolower($validated['subject']) === 'reservation' && !empty($hotel->reservation_email)) {
            $recipientEmail = $hotel->reservation_email;
        } elseif (!empty($hotel->email)) {
            $recipientEmail = $hotel->email;
        } else {
            $recipientEmail = config('mail.from.address');
        }

        $emailSent = false;
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new ContactMessageReceivedMail($contactMessage, $hotel));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::error("[ContactMessage] Failed to send email to {$recipientEmail}: " . $e->getMessage(), [
                    'contact_message_id' => $contactMessage->id,
                    'hotel_id' => $hotel->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Thank you! Your message has been sent to {$hotel->name}. Our team will contact you shortly.",
            'data' => [
                'id' => $contactMessage->id,
                'hotel_name' => $hotel->name,
                'email_sent' => $emailSent,
            ],
        ], 201);
    }
}
