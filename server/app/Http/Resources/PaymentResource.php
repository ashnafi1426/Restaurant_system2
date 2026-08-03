<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ============================================================================
 * PaymentResource
 * ============================================================================
 * Transforms Payment model into JSON response format
 * 
 * Returns:
 * - Payment metadata and status
 * - Transaction details
 * - Customer information
 * - Timestamps for audit trail
 * 
 * Used in API responses for consistency and security (hides sensitive data)
 * ============================================================================
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform Payment model into array
     * 
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiers
            'id'                       => $this->id,
            'tx_ref'                   => $this->tx_ref,
            'chapa_transaction_id'     => $this->chapa_transaction_id,

            // Customer Information
            'customer' => [
                'name'       => $this->customer_name,
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
                'email'      => $this->email,
                'phone'      => $this->phone,
            ],

            // Amount and Currency
            'amount'              => (float) $this->amount,
            'currency'            => $this->currency,
            'formatted_amount'    => $this->formatted_amount,

            // Payment Details
            'payment_provider'    => $this->payment_provider,
            'payment_method'      => $this->payment_method,
            'status'              => $this->status,
            'checkout_url'        => $this->checkout_url,

            // Timestamps
            'paid_at'             => $this->paid_at?->toIso8601String(),
            'verified_at'         => $this->verified_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),

            // Status Helpers
            'is_pending'          => $this->isPending(),
            'is_initialized'      => $this->isInitialized(),
            'is_processing'       => $this->isProcessing(),
            'is_paid'             => $this->isPaid(),
            'is_verified'         => $this->isVerified(),
            'is_failed'           => $this->isFailed(),
            'is_cancelled'        => $this->isCancelled(),
            'is_expired'          => $this->isExpired(),
            'is_refunded'         => $this->isRefunded(),

            // Metadata (if present)
            'metadata'            => $this->metadata ?? null,
        ];
    }
}
