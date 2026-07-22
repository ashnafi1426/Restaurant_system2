<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class UserController extends Controller
{
    public function index(Request $request)
  {
    $query = User::query();
    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where(
          'first_name',
          'like',
          "%{$search}%"
        )
          ->orWhere(
            'last_name',
            'like',
            "%{$search}%"
          )

          ->orWhere(
            'email',
            'like',
            "%{$search}%"
          );
      });
    }
    if ($request->filled('role')) {
      $query->where(
        'role',
        $request->role
      );
    }
    if ($request->filled('is_active')) {

      $query->where(
        'is_active',
        $request->boolean('is_active')
      );
    }
    $query->latest();
    $users = $query->paginate(
      $request->get('per_page', 10)
    );
    return UserResource::collection($users);
  }
  public function store(StoreUserRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = User::create([
        'first_name' =>
        $request->first_name,
        'last_name' =>
        $request->last_name,
        'email' =>
        $request->email,

        'phone' =>
        $request->phone,
        'password_hash' =>
        Hash::make(
          $request->password
        ),
        'role' =>
        $request->role,
        'is_active' =>
        $request->is_active,
      ]);
      DB::commit();
      return response()->json([
        'success' => true,

        'message' =>
        'User created successfully.',

        'data' =>
        new UserResource($user)

      ], 201);
    }
     catch (\Exception $exception) {

      DB::rollBack();

      return response()->json([

        'success' => false,

        'message' =>
        'Unable to create user.',

        'error' =>
        $exception->getMessage()

      ], 500);
    }
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

        // Hash the password if provided
        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->password);
        }

        $user->save();

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
