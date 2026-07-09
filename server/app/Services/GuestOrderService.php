<?php

namespace App\Services\Restaurant;

use App\Models\MenuCategory;
use App\Models\Room;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class GuestOrderService
{
    /**
     * Display the restaurant menu for the scanned QR code.
     *
     * @throws Exception
     */
    public function menu(string $token): array
    {
        /*
        |--------------------------------------------------------------------------
        | Find Room by QR Token
        |--------------------------------------------------------------------------
        */

        $room = Room::with([
            'activeReservation.guest',
        ])
        ->where('qr_token', $token)
        ->first();

        if (! $room) {
            throw new ModelNotFoundException(
                'Invalid QR code.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Active Reservation
        |--------------------------------------------------------------------------
        */

        $reservation = $room->activeReservation;

        if (! $reservation) {
            throw new Exception(
                'This room currently has no active reservation.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Check-In Status
        |--------------------------------------------------------------------------
        */

        if ($reservation->status !== 'checked_in') {
            throw new Exception(
                'Only checked-in guests can place restaurant orders.'
            );
        }

        $categories = MenuCategory::with([
            'menuItems' => function ($query) {
                $query->where('is_available', true)
                      ->where('is_active', true)
                      ->orderBy('name');
            },
        ])
        ->where('is_active', true)
        ->orderBy('display_order')
        ->get();

        return [

            'room' => [
                'id' => $room->id,
                'room_number' => $room->room_number,
            ],

            'guest' => [
                'id' => $reservation->guest->id,
                'name' => $reservation->guest->full_name,
            ],

            'reservation' => [
                'id' => $reservation->id,
            ],

            'categories' => $categories,

        ];
    }
    public function create(array $data): Order
{
    /*
    |--------------------------------------------------------------------------
    | Find Room
    |--------------------------------------------------------------------------
    */

    $room = $this->findRoomByToken(
        $data['qr_token']
    );

    /*
    |--------------------------------------------------------------------------
    | Find Active Reservation
    |--------------------------------------------------------------------------
    */

    $reservation = $this->findActiveReservation(
        $room
    );

    /*
    |--------------------------------------------------------------------------
    | Build Order Payload
    |--------------------------------------------------------------------------
    */

    $orderData = [

        'reservation_id' => $reservation->id,

        'guest_id' => $reservation->guest_id,

        'room_id' => $room->id,

        'payment_type' => Order::PAYMENT_ROOM_CHARGE,

        'notes' => $data['notes'] ?? null,

        'items' => $data['items'],

    ];

    return $this->orderService->create(
        $orderData
    );
}
/**
 * Display a guest order.
 *
 * @throws \Exception
 */
public function show(
    string $qrToken,
    string $orderId
): Order {

    /*
    |--------------------------------------------------------------------------
    | Validate QR Token
    |--------------------------------------------------------------------------
    */

    $data = $this->validateQrToken(
        $qrToken
    );

    /*
    |--------------------------------------------------------------------------
    | Find Order
    |--------------------------------------------------------------------------
    */

    $order = Order::with([
            'guest',
            'room',
            'reservation',
            'items.menuItem',
        ])
        ->where('id', $orderId)
        ->where(
            'reservation_id',
            $data['reservation']->id
        )
        ->first();

    if (! $order) {
        throw new Exception(
            'Order not found.'
        );
    }

    return $order;
}
/**
 * Display the current reservation's order history.
 *
 * @throws \Exception
 */
public function history(
    string $qrToken,
    int $perPage = 10
) {

    /*
    |--------------------------------------------------------------------------
    | Validate QR Token
    |--------------------------------------------------------------------------
    */

    $data = $this->validateQrToken(
        $qrToken
    );

    /*
    |--------------------------------------------------------------------------
    | Retrieve Orders
    |--------------------------------------------------------------------------
    */

    return Order::with([
            'guest',
            'room',
            'reservation',
            'items.menuItem',
        ])
        ->where(
            'reservation_id',
            $data['reservation']->id
        )
        ->latest('order_time')
        ->paginate($perPage);
}

}