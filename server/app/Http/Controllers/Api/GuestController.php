<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestRequest;
use App\Http\Resources\GuestCollection;
use App\Http\Resources\GuestResource;
use App\Http\Resources\ReservationCollection;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $query = Guest::query();
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

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }
        $query->latest();
        $guests = $query->paginate(
            $request->get('per_page', 10)
        );

        return new GuestCollection($guests);
    }

    public function store(GuestRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $existingGuest = null;

            if (!empty($validated['email'])) {
                $existingGuest = Guest::where('email', strtolower(trim($validated['email'])))->first();
            }

            if (!$existingGuest && !empty($validated['passport_number'])) {
                $existingGuest = Guest::where('passport_number', trim($validated['passport_number']))->first();
            }

            if ($existingGuest) {
                $existingGuest->update(array_filter($validated, fn($val) => !is_null($val) && $val !== ''));
                return response()->json([
                    'message' => 'Existing guest record found and updated successfully.',
                    'data' => new GuestResource($existingGuest)
                ], 200);
            }

            $guest = Guest::create($validated);

            return response()->json([
                'message' => 'Guest created successfully.',
                'data' => new GuestResource($guest)
            ], 201);
        });
    }

    public function show(Guest $guest)
    {
        return response()->json([
            'data' => new GuestResource($guest)
        ]);
    }

    public function reservations(Guest $guest, Request $request)
    {
        $query = $guest->reservations();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('booking_reference', 'LIKE', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

    public function destroy(Guest $guest)
    {
        $guest->delete();

        return response()->json([
            'message' => 'Guest deleted successfully.'
        ]);
    }
}