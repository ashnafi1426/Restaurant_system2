<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestRequest;
use App\Http\Resources\GuestCollection;
use App\Http\Resources\GuestResource;
use App\Http\Resources\ReservationCollection;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * Display a listing of guests.
     */
    public function index(Request $request)
    {
        $query = Guest::query();

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        // Nationality Filter
        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        // Latest First
        $query->latest();

        $guests = $query->paginate(
            $request->get('per_page', 10)
        );

        return new GuestCollection($guests);
    }

    /**
     * Store a newly created guest.
     */
    public function store(GuestRequest $request)
    {
        $guest = Guest::create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Guest created successfully.',
            'data' => new GuestResource($guest)
        ], 201);
    }

    /**
     * Display the specified guest.
     */
    public function show(Guest $guest)
    {
        return response()->json([
            'data' => new GuestResource($guest)
        ]);
    }

    /**
     * Get all reservations for a specific guest
     */
    public function reservations(Guest $guest, Request $request)
    {
        $query = $guest->reservations();

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('booking_reference', 'LIKE', "%{$search}%");
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('check_in_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $reservations = $query
            ->with(['room', 'creator'])
            ->latest()
            ->paginate(
                $request->integer('per_page', 10)
            );

        return new ReservationCollection($reservations);
    }

    /**
     * Update the specified guest.
     */
    public function update(
        GuestRequest $request,
        Guest $guest
    ) {
        $guest->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Guest updated successfully.',
            'data' => new GuestResource($guest)
        ]);
    }

    /**
     * Remove the specified guest.
     */
    public function destroy(Guest $guest)
    {
        $guest->delete();

        return response()->json([
            'message' => 'Guest deleted successfully.'
        ]);
    }
}