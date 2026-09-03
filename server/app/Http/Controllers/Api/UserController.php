<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivationService;
use App\Mail\NewUserCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
class UserController extends Controller
{
    protected ActivationService $activationService;

    public function __construct(ActivationService $activationService)
    {
        $this->activationService = $activationService;
    }

    public function index(Request $request)
  {
    $query = User::query();

    // Hotel tenant scoping: strictly fetch users belonging to the current active hotel
    $hotelId = $request->header('X-Hotel-ID')
        ?: app(\App\Services\TenantContext::class)->getHotelId()
        ?: $request->query('hotel_id');

    $isAllHotels = $request->boolean('all_hotels') && $request->user()?->isPlatformAdmin();

    if (!$isAllHotels) {
        if (!$hotelId && $request->user()) {
            $hotelId = $request->user()->hotelMemberships()->first()?->hotel_id;
        }

        if ($hotelId) {
            $query->whereHas('hotelMemberships', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            });
        }
    }

    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('first_name', 'like', "%{$search}%")
          ->orWhere('last_name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('role', 'like', "%{$search}%");
      });
    }
    if ($request->filled('role')) {
      $query->where('role', $request->role);
    }
    if ($request->filled('is_active')) {
      $query->where('is_active', $request->boolean('is_active'));
    }
    $query->latest();
    $perPage = (int) $request->get('per_page', 500);
    $users = $query->paginate($perPage);
    return UserResource::collection($users);
  }
  public function store(StoreUserRequest $request)
  {
    // Ensure database column accepts dynamic role names (VARCHAR instead of ENUM)
    try {
      DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'guest'");
    } catch (\Exception $e) {}

    DB::beginTransaction();
    try {
      // Generate a random password (12 characters with mix of letters, numbers, and symbols)
      $temporaryPassword = $this->generateSecurePassword();

      // Create user with auto-generated password
      $user = User::create([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password_hash' => Hash::make($temporaryPassword),
        'role' => $request->role,
        'is_active' => $request->is_active,
        'activation_status' => 'activated', // User can login immediately
        'email_verified_at' => now() // Mark email as verified
      ]);

      // Attach newly created user to active hotel
      $currentHotelId = app(\App\Services\TenantContext::class)->getHotelId()
          ?: $request->header('X-Hotel-ID')
          ?: auth()->user()?->hotelMemberships()->first()?->hotel_id;

      $targetRoleModel = null;
      if (!empty($user->role) && $currentHotelId) {
        $targetRoleStr = strtolower(trim($user->role));
        $targetRoleModel = \App\Models\Role::withoutTenant()
          ->where('hotel_id', $currentHotelId)
          ->where(function ($q) use ($targetRoleStr) {
            $q->whereRaw('LOWER(slug) = ?', [$targetRoleStr])
              ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr]);
          })
          ->first();
      }

      if ($currentHotelId) {
        \App\Models\HotelUser::firstOrCreate([
          'hotel_id' => $currentHotelId,
          'user_id' => $user->id,
        ], [
          'id' => (string) \Illuminate\Support\Str::uuid(),
          'role' => $user->role ?: 'staff',
          'role_id' => $targetRoleModel?->id,
          'is_active' => true,
        ]);

        if ($targetRoleModel) {
          \Illuminate\Support\Facades\DB::table('user_roles')->updateOrInsert(
            [
              'hotel_id' => $currentHotelId,
              'user_id' => $user->id,
              'role_id' => $targetRoleModel->id,
            ],
            [
              'is_primary' => true,
              'created_at' => now(),
              'updated_at' => now(),
            ]
          );
        }
      }

      // Send email with temporary password
      try {
        Mail::to($user->email)->send(new NewUserCreated($user, $temporaryPassword));
      } catch (\Exception $mailException) {
        Log::error('Failed to send new user email', [
          'user_id' => $user->id,
          'email' => $user->email,
          'error' => $mailException->getMessage()
        ]);
        
        // Don't rollback - user is created, just email failed
        DB::commit();
        
        return response()->json([
          'success' => true,
          'message' => 'User created successfully but failed to send email. Please provide password manually: ' . $temporaryPassword,
          'data' => new UserResource($user),
          'temporary_password' => $temporaryPassword, // Return it if email fails
          'email_sent' => false
        ], 201);
      }

      DB::commit();

      Log::info('User created with auto-generated password', [
        'user_id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
        'created_by' => auth('sanctum')->id()
      ]);

      return response()->json([
        'success' => true,
        'message' => 'User created successfully. Login credentials sent to ' . $user->email,
        'data' => new UserResource($user),
        'email_sent' => true
      ], 201);
    }
     catch (\Exception $exception) {

      DB::rollBack();

      Log::error('Failed to create user', [
        'error' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString()
      ]);

      return response()->json([

        'success' => false,

        'message' =>
        'Unable to create user.',

        'error' =>
        $exception->getMessage()

      ], 500);
    }
  }

  /**
   * Generate a secure random password
   */
  private function generateSecurePassword(int $length = 12): string
  {
    $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Excluding I, O
    $lowercase = 'abcdefghjkmnpqrstuvwxyz'; // Excluding i, l, o
    $numbers = '23456789'; // Excluding 0, 1
    $symbols = '!@#$%&*';
    
    // Ensure at least one character from each group
    $password = 
      $uppercase[random_int(0, strlen($uppercase) - 1)] .
      $lowercase[random_int(0, strlen($lowercase) - 1)] .
      $numbers[random_int(0, strlen($numbers) - 1)] .
      $symbols[random_int(0, strlen($symbols) - 1)];
    
    // Fill the rest with random characters from all groups
    $allChars = $uppercase . $lowercase . $numbers . $symbols;
    for ($i = 4; $i < $length; $i++) {
      $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Shuffle the password to randomize character positions
    return str_shuffle($password);
  }
  public function show(User $user)
  {
    return response()->json([
      'success' => true,
      'message' => 'User retrieved successfully.',
      'data' => new UserResource($user)
    ], 200);
  }
  public function update(UpdateUserRequest $request, User $user)
   {
    DB::beginTransaction();

    try {

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role = $request->role;
        $user->is_active = $request->is_active;

        // Hash the password if provided and not empty
        if ($request->filled('password') && !empty($request->password)) {
            $user->password_hash = Hash::make($request->password);
        }

        $user->save();

        // Sync role pivot table if role model exists
        if (!empty($user->role)) {
            $targetRoleStr = strtolower($user->role);
            $roleModel = \App\Models\Role::whereRaw('LOWER(slug) = ?', [$targetRoleStr])
                ->orWhereRaw('LOWER(name) = ?', [$targetRoleStr])
                ->first();
            if ($roleModel) {
                try {
                    $user->roles()->sync([
                        $roleModel->id => ['is_primary' => true]
                    ]);
                } catch (\Exception $e) {}
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($user),
        ], 200);

    } catch (\Exception $exception) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Unable to update user.',
            'error' => $exception->getMessage(),
        ], 500);
    }
  }

public function destroy(User $user)
{ 
    if (auth('sanctum')->id()=== $user->id) {
        return $this->errorResponse(
            'You cannot delete your own account.',
            null,
            403
        );
    }
    DB::beginTransaction();

    try {
        $user->delete();

        DB::commit();

        return $this->successResponse(
            'User deleted successfully.'
        );
    } catch (\Throwable $exception) {

        DB::rollBack();

        // Log the exception for debugging

        Log::error('Failed to delete user.', [
            'user_id' => $user->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return $this->errorResponse(
            'Unable to delete user.',
            null,
            500
        );
    }
}
  public function toggleStatus(User $user)
      {

    DB::beginTransaction();

    try {
   $user->is_active = ! $user->is_active;

      $user->save();

      DB::commit();

      return response()->json([

        'success' => true,

        'message' => $user->is_active
          ? 'User activated successfully.'
          : 'User deactivated successfully.',

        'data' => new UserResource($user)

      ], 200);
    } catch (\Exception $exception) {

      DB::rollBack();

      return response()->json([
        'success' => false,
        'message' => 'Unable to update user status.',
        'error' => $exception->getMessage()
      ], 500);
    }
  }
 protected function successResponse(
    string $message,
    mixed $data = null,
    int $status = 200
  ) {

    return response()->json([

      'success' => true,

      'message' => $message,

      'data' => $data

    ], $status);
  }
   protected function errorResponse(
    string $message,
    mixed $error = null,
    int $status = 500
  ) {

    return response()->json([

      'success' => false,

      'message' => $message,

      'error' => $error

    ], $status);
  }
}
