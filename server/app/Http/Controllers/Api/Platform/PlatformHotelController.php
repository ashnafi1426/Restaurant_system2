<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreHotelRequest;
use App\Http\Requests\Platform\UpdateHotelRequest;
use App\Http\Requests\Platform\UpdateHotelStatusRequest;
use App\Http\Requests\Platform\AssignHotelAdminRequest;
use App\Http\Requests\Platform\StoreHotelAdminRequest;
use App\Http\Requests\Platform\DeleteHotelRequest;
use App\Http\Requests\Platform\UpdatePlatformSettingsRequest;
use App\Http\Resources\Platform\HotelResource;
use App\Http\Resources\Platform\HotelCollection;
use App\Http\Resources\Platform\HotelAdminResource;
use App\Http\Resources\Platform\PlatformStatisticsResource;
use App\Http\Resources\Platform\UserResource;
use App\Services\Platform\PlatformHotelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Hotel;

class PlatformHotelController extends Controller
{
    protected PlatformHotelService $platformHotelService;

    public function __construct(PlatformHotelService $platformHotelService)
    {
        $this->platformHotelService = $platformHotelService;
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->platformHotelService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => new PlatformStatisticsResource($statistics),
        ]);
    }
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'city' => $request->input('city'),
        ];
        $hotels = $this->platformHotelService->getHotels($filters, $request->integer('per_page', 15));

        $data = [
            'current_page' => $hotels->currentPage(),
            'data' => $hotels->items(),
            'first_page_url' => $hotels->url(1),
            'from' => $hotels->firstItem(),
            'last_page' => $hotels->lastPage(),
            'last_page_url' => $hotels->url($hotels->lastPage()),
            'links' => $hotels->linkCollection()->toArray(),
            'next_page_url' => $hotels->nextPageUrl(),
            'path' => $hotels->path(),
            'per_page' => $hotels->perPage(),
            'prev_page_url' => $hotels->previousPageUrl(),
            'to' => $hotels->lastItem(),
            'total' => $hotels->total(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    public function store(StoreHotelRequest $request): JsonResponse
    {
        $result = $this->platformHotelService->createHotel($request->validated());

        $response = [
            'success' => true,
            'message' => 'Hotel onboarded successfully',
            'data' => new HotelResource($result['hotel']),
        ];

        if ($result['admin_user']) {
            $response['admin'] = [
                'id' => $result['admin_user']->id,
                'email' => $result['admin_user']->email,
                'name' => $result['admin_user']->first_name . ' ' . $result['admin_user']->last_name,
                'temporary_password' => $result['generated_password'],
            ];
        }
        return response()->json($response, 201);
    }
    public function show(string $id): JsonResponse
    {
        $hotelData = $this->platformHotelService->getHotelDetails($id);
        return response()->json([
            'success' => true,
            'data' => new HotelResource((object) $hotelData),
        ]);
    }

    public function update(UpdateHotelRequest $request, string $id): JsonResponse
    {
        $hotel = $this->platformHotelService->updateHotel($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Hotel updated successfully',
            'data' => new HotelResource($hotel),
        ]);
    }

    public function updateStatus(UpdateHotelStatusRequest $request, string $id): JsonResponse
    {
        $result = $this->platformHotelService->updateHotelStatus($id, $request->validated()['status']);

        return response()->json([
            'success' => true,
            'message' => "Hotel status changed from {$result['old_status']} to {$result['new_status']}",
            'data' => new HotelResource($result['hotel']),
        ]);
    }

    public function archive(string $id): JsonResponse
    {
        $hotel = $this->platformHotelService->archiveHotel($id);

        return response()->json([
            'success' => true,
            'message' => "Hotel {$hotel->name} has been archived. All data is preserved.",
            'data' => new HotelResource($hotel),
        ]);
    }

    public function destroy(DeleteHotelRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->platformHotelService->deleteHotel($id, $request->validated()['confirm_name']);

            return response()->json([
                'success' => true,
                'message' => "Hotel {$result['hotel_name']} has been deleted permanently.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    public function allAdmins(Request $request): JsonResponse
    {
        $filters = [
            'hotel_id' => $request->input('hotel_id'),
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ];

        $admins = $this->platformHotelService->getAllAdmins($filters, $request->integer('per_page', 15));

        $data = [
            'current_page' => $admins->currentPage(),
            'data' => $admins->items(),
            'first_page_url' => $admins->url(1),
            'from' => $admins->firstItem(),
            'last_page' => $admins->lastPage(),
            'last_page_url' => $admins->url($admins->lastPage()),
            'links' => $admins->linkCollection()->toArray(),
            'next_page_url' => $admins->nextPageUrl(),
            'path' => $admins->path(),
            'per_page' => $admins->perPage(),
            'prev_page_url' => $admins->previousPageUrl(),
            'to' => $admins->lastItem(),
            'total' => $admins->total(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getAdmins(string $id): JsonResponse
    {
        $admins = $this->platformHotelService->getHotelAdmins($id);
        $hotel = Hotel::findOrFail($id);

        return response()->json([
            'success' => true,
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
            ],
            'data' => $admins,
        ]);
    }

    public function assignAdmin(AssignHotelAdminRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->platformHotelService->assignAdminToHotel($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => "User {$result['user_email']} is now a Hotel Administrator for {$result['hotel_name']}.",
                'data' => $result,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeAdmin(string $id, string $userId): JsonResponse
    {
        try {
            $this->platformHotelService->removeAdminFromHotel($id, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Admin privileges removed for this hotel successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    public function createHotelAdmin(StoreHotelAdminRequest $request): JsonResponse
    {
        $result = $this->platformHotelService->createHotelAdmin($request->validated());

        return response()->json([
            'success' => true,
            'message' => $result['email_sent']
                ? "Hotel Admin created and login credentials securely sent to {$result['user']->email}."
                : "Hotel Admin {$result['user']->first_name} {$result['user']->last_name} created successfully.",
            'data' => [
                'user' => new UserResource($result['user']),
                'hotel' => new HotelResource($result['hotel']),
                'membership_id' => $result['membership_id'],
                'email_sent' => $result['email_sent'],
            ],
        ], 201);
    }

    public function resetAdminPassword(Request $request, string $userId): JsonResponse
    {
        $result = $this->platformHotelService->resetAdminPassword($userId);

        return response()->json([
            'success' => true,
            'message' => $result['email_sent']
                ? "A new system-generated temporary password has been securely emailed to {$result['user']->email}."
                : "New temporary password generated and logged for {$result['user']->email}.",
            'email_sent' => $result['email_sent'],
        ]);
    }

    public function resendAdminPassword(Request $request, string $userId): JsonResponse
    {
        return $this->resetAdminPassword($request, $userId);
    }

    public function toggleAdminStatus(string $membershipId): JsonResponse
    {
        $membership = $this->platformHotelService->toggleAdminStatus($membershipId);

        return response()->json([
            'success' => true,
            'message' => "Hotel admin status updated to " . ($membership->is_active ? 'Active' : 'Inactive') . ".",
            'data' => new HotelAdminResource($membership),
        ]);
    }
    public function enterViewMode(Request $request, string $id): JsonResponse
    {
        $requestData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $hotel = $this->platformHotelService->enterViewMode($id, $requestData);

        return response()->json([
            'success' => true,
            'message' => "Entered Super Admin View Mode for hotel {$hotel->name}.",
            'data' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'logo' => $hotel->logo,
                'currency' => $hotel->currency,
                'role' => 'admin',
                'is_viewing_as_platform_admin' => true,
            ],
        ]);
    }

    public function exitViewMode(Request $request): JsonResponse
    {
        $this->platformHotelService->exitViewMode();

        return response()->json([
            'success' => true,
            'message' => 'Exited Super Admin View Mode.',
        ]);
    }

    public function allUsers(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'role' => $request->input('role'),
            'hotel_id' => $request->input('hotel_id'),
        ];

        $users = $this->platformHotelService->getAllUsers($filters, $request->integer('per_page', 20));

        $data = [
            'current_page' => $users->currentPage(),
            'data' => $users->items(),
            'first_page_url' => $users->url(1),
            'from' => $users->firstItem(),
            'last_page' => $users->lastPage(),
            'last_page_url' => $users->url($users->lastPage()),
            'links' => $users->linkCollection()->toArray(),
            'next_page_url' => $users->nextPageUrl(),
            'path' => $users->path(),
            'per_page' => $users->perPage(),
            'prev_page_url' => $users->previousPageUrl(),
            'to' => $users->lastItem(),
            'total' => $users->total(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $filters = [
            'hotel_id' => $request->input('hotel_id'),
            'action' => $request->input('action'),
        ];

        $logs = $this->platformHotelService->getAuditLogs($filters, $request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function getSettings(): JsonResponse
    {
        $settings = $this->platformHotelService->getSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function updateSettings(UpdatePlatformSettingsRequest $request): JsonResponse
    {
        $settings = $this->platformHotelService->updateSettings($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Platform settings updated successfully.',
            'data' => $settings,
        ]);
    }
}

