<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancellationPolicy;
use App\Models\Reservation;
use App\Services\TenantContext;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CancellationPolicyController extends Controller
{
    /**
     * Display a listing of cancellation policies for the current hotel.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->resolveHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hotel context not found',
                ], 400);
            }

            $query = CancellationPolicy::where('hotel_id', $hotelId);

            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            $policies = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $policies,
            ]);
        } catch (Throwable $e) {
            Log::error('Get cancellation policies exception', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred retrieving policies',
            ], 500);
        }
    }

    /**
     * Display the specified cancellation policy.
     */
    public function show(string $policyId): JsonResponse
    {
        try {
            $policy = $this->findPolicy($policyId);

            return response()->json([
                'success' => true,
                'data' => $policy,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Get policy exception', [
                'policy_id' => $policyId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Store a newly created cancellation policy.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->resolveHotelId();
            if (!$hotelId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hotel context not found',
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:flexible,moderate,strict,non_refundable',
                'description' => 'nullable|string',
                'cancellation_deadline_days' => 'required|integer|min:0',
                'refund_percentage' => 'required|numeric|min:0|max:100',
                'minimum_stay_nights' => 'nullable|integer|min:1',
                'applies_to' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            $policy = CancellationPolicy::create([
                'hotel_id' => $hotelId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'cancellation_deadline_days' => $validated['cancellation_deadline_days'],
                'refund_percentage' => $validated['refund_percentage'],
                'minimum_stay_nights' => $validated['minimum_stay_nights'] ?? 1,
                'applies_to' => $validated['applies_to'] ?? 'all',
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Policy created successfully',
                'data' => $policy,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Create policy exception', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred creating policy',
            ], 500);
        }
    }

    /**
     * Update the specified cancellation policy.
     */
    public function update(string $policyId, Request $request): JsonResponse
    {
        try {
            $policy = $this->findPolicy($policyId);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'type' => 'nullable|in:flexible,moderate,strict,non_refundable',
                'description' => 'nullable|string',
                'cancellation_deadline_days' => 'nullable|integer|min:0',
                'refund_percentage' => 'nullable|numeric|min:0|max:100',
                'minimum_stay_nights' => 'nullable|integer|min:1',
                'applies_to' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            // Filter null values only so boolean false (e.g. is_active: false) is preserved
            $policy->update(array_filter($validated, fn ($val) => $val !== null));

            return response()->json([
                'success' => true,
                'message' => 'Policy updated successfully',
                'data' => $policy->fresh(),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Update policy exception', [
                'policy_id' => $policyId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred updating policy',
            ], 500);
        }
    }

    /**
     * Remove the specified cancellation policy.
     */
    public function destroy(string $policyId): JsonResponse
    {
        try {
            $policy = $this->findPolicy($policyId);

            $inUse = Reservation::where('cancellation_policy_id', $policyId)->exists();
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete policy that is in use by reservations',
                ], 422);
            }

            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        } catch (Throwable $e) {
            Log::error('Delete policy exception', [
                'policy_id' => $policyId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred deleting policy',
            ], 500);
        }
    }

    /**
     * Calculate refund amount based on policy rules.
     */
    public function calculateRefund(string $policyId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'total_amount' => 'required|numeric|min:0',
                'check_in_date' => 'required|date',
                'cancellation_date' => 'required|date|before_or_equal:check_in_date',
            ]);

            $policy = $this->findPolicy($policyId);

            $checkInDate = new DateTime($validated['check_in_date']);
            $cancellationDate = new DateTime($validated['cancellation_date']);

            $refund = $policy->calculateRefund(
                (float) $validated['total_amount'],
                $checkInDate,
                $cancellationDate
            );

            return response()->json([
                'success' => true,
                'data' => $refund,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Calculate refund exception', [
                'policy_id' => $policyId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating refund',
            ], 500);
        }
    }

    /**
     * Resolve hotel ID for the current request.
     */
    private function resolveHotelId(): ?string
    {
        return TenantContext::id() ?? auth()->user()?->hotel_id;
    }

    /**
     * Find a cancellation policy scoped to the current hotel.
     */
    private function findPolicy(string $policyId): CancellationPolicy
    {
        $hotelId = $this->resolveHotelId();

        $query = CancellationPolicy::where('id', $policyId);
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->firstOrFail();
    }
}
