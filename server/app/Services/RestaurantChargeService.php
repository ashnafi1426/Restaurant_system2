<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RestaurantCharge;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RestaurantChargeService
{

    public function createFromOrder(Order $order): RestaurantCharge
    {
        return DB::transaction(function () use ($order) {
            if ($order->restaurantCharge()->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'This order has already been billed.',
                ]);
            }
            if ($order->status !== 'served') {
                throw ValidationException::withMessages([
                    'status' => 'Only served orders can be billed.',
                ]);
            }
            return RestaurantCharge::create([

                'reservation_id' => $order->reservation_id,

                'order_id' => $order->id,

                'amount' => $order->total,

                'description' => sprintf(
                    'Restaurant Order #%s',
                    $order->order_number
                ),

                'status' => 'unpaid',

            ]);
        });
    }
    public function find(string $id): RestaurantCharge
    {
        return RestaurantCharge::with([
            'reservation',
            'order',
        ])->findOrFail($id);
    }

    public function all(): Collection
    {
        return RestaurantCharge::with([
            'reservation',
            'order',
        ])
        ->latest()
        ->get();
    }
    public function reservationCharges(string $reservationId): Collection
    {
        return RestaurantCharge::with('order')
            ->where('reservation_id', $reservationId)
            ->latest()
            ->get();
    }
    public function unpaidCharges(string $reservationId): Collection
    {
        return RestaurantCharge::with('order')
            ->where('reservation_id', $reservationId)
            ->where('status', 'unpaid')
            ->latest()
            ->get();
    }

    public function reservationBalance(string $reservationId): float
    {
        return (float) RestaurantCharge::where(
            'reservation_id',
            $reservationId
        )
        ->where('status', 'unpaid')
        ->sum('amount');
    }

    public function markPaid(
        RestaurantCharge $charge,
        ?string $reference = null
    ): RestaurantCharge {

        DB::transaction(function () use ($charge, $reference) {

            $charge->update([

                'status' => 'paid',

                'paid_at' => now(),

                'payment_reference' => $reference,

            ]);

        });

        return $charge->fresh([
            'reservation',
            'order',
        ]);
    }

    public function cancel(RestaurantCharge $charge): RestaurantCharge
    {
        DB::transaction(function () use ($charge) {

            $charge->update([

                'status' => 'cancelled',

            ]);

        });

        return $charge->fresh([
            'reservation',
            'order',
        ]);
    }

    public function statistics(): array
    {
        return [

            'total_charges' => RestaurantCharge::count(),

            'unpaid_charges' => RestaurantCharge::unpaid()->count(),

            'paid_charges' => RestaurantCharge::paid()->count(),

            'cancelled_charges' => RestaurantCharge::cancelled()->count(),

            'total_unpaid_amount' => RestaurantCharge::unpaid()->sum('amount'),

            'total_paid_amount' => RestaurantCharge::paid()->sum('amount'),

        ];
    }
}