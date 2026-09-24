<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancellationPolicy;
use App\Models\Reservation;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CancellationPolicyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

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

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Get policies exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred retrieving policies',
            ], 500);
        }
    }

    public function show(string $policyId): JsonResponse
    {
        try {
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $policy = CancellationPolicy::where('hotel_id', $hotelId)
                ->where('id', $policyId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $policy,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Get policy exception', [
                'message' => $e->getMessage(),
                'policy_id' => $policyId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
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

            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $policy = CancellationPolicy::create([
                'hotel_id' => $hotelId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'cancellation_deadline_days' => $validated['cancellation_deadline_days'],
                'refund_percentage' => $validated['refund_percentage'],
                'minimum_stay_nights' => $validated['minimum_stay_nights'] ?? 1,
                'applies_to' => $validated['applies_to'] ?? 'all',
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            Log::info('✅ [POLICY] Cancellation policy created', [
                'policy_id' => $policy->id,
                'type' => $policy->type,
                'hotel_id' => $hotelId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Policy created successfully',
                'data' => $policy,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Create policy exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred creating policy',
            ], 500);
        }
    }

    public function update(string $policyId, Request $request): JsonResponse
    {
        try {
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $policy = CancellationPolicy::where('hotel_id', $hotelId)
                ->where('id', $policyId)
                ->firstOrFail();

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

            $policy->update(array_filter($validated));

            Log::info('✅ [POLICY] Cancellation policy updated', [
                'policy_id' => $policy->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Policy updated successfully',
                'data' => $policy->fresh(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Update policy exception', [
                'message' => $e->getMessage(),
                'policy_id' => $policyId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred updating policy',
            ], 500);
        }
    }

    public function destroy(string $policyId): JsonResponse
    {
        try {
            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $policy = CancellationPolicy::where('hotel_id', $hotelId)
                ->where('id', $policyId)
                ->firstOrFail();

            $inUse = Reservation::where('cancellation_policy_id', $policyId)->exists();

            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete policy that is in use by reservations',
                ], 422);
            }

            $policy->delete();

            Log::info('✅ [POLICY] Cancellation policy deleted', [
                'policy_id' => $policyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Delete policy exception', [
                'message' => $e->getMessage(),
                'policy_id' => $policyId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred deleting policy',
            ], 500);
        }
    }

    public function calculateRefund(string $policyId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'total_amount' => 'required|numeric|min:0',
                'check_in_date' => 'required|date',
                'cancellation_date' => 'required|date|before_or_equal:check_in_date',
            ]);

            $hotelId = TenantContext::id() ?? auth()->user()?->hotel_id;

            $policy = CancellationPolicy::where('hotel_id', $hotelId)
                ->where('id', $policyId)
                ->firstOrFail();

            $checkInDate = new \DateTime($validated['check_in_date']);
            $cancellationDate = new \DateTime($validated['cancellation_date']);

            $refund = $policy->calculateRefund(
                (float) $validated['total_amount'],
                $checkInDate,
                $cancellationDate
            );

            Log::info('📊 [POLICY] Refund calculated', [
                'policy_id' => $policyId,
                'refund_amount' => $refund['refund_amount'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $refund,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ [POLICY] Calculate refund exception', [
                'message' => $e->getMessage(),
                'policy_id' => $policyId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred calculating refund',
            ], 500);
        }
    }
}
