<?php

namespace App\Services\Waiter;

use App\Models\User;
use App\Models\Waiter;

class WaiterContextResolver
{
    public function resolveWaiterId(?User $user): ?int
    {
        if (!$user) {
            \Log::warning(' [RESOLVER] No user provided');
            return null;
        }
        
        \Log::debug(' [RESOLVER] Resolving waiter ID for user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'N/A',
            'relation_loaded' => $user->relationLoaded('waiter') ? 'yes' : 'no',
        ]);
        
        if ($user->relationLoaded('waiter') && $user->waiter) {
            $waiterId = (int) $user->waiter->id;
            \Log::info(' [RESOLVER] Waiter ID resolved from loaded relation', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }

        $user->loadMissing('waiter');

        if ($user->waiter) {
            $waiterId = (int) $user->waiter->id;
            \Log::info(' [RESOLVER] Waiter ID resolved from loadMissing', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }
        \Log::warning(' [RESOLVER] No waiter relation found, trying fallback lookup', [
            'user_id' => $user->id,
        ]);

        $fallbackWaiter = Waiter::whereHas('user', function ($query) use ($user) {
            $query->where('email', $user->email)
                ->orWhere('phone', $user->phone);
        })->first();

        if ($fallbackWaiter?->id) {
            $waiterId = (int) $fallbackWaiter->id;
            \Log::warning(' [RESOLVER] Waiter ID resolved from fallback lookup', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }

        $directWaiter = Waiter::where('user_id', $user->id)->first();
        if ($directWaiter?->id) {
            return (int) $directWaiter->id;
        }

        try {
            $hotelId = app(\App\Services\TenantContext::class)->getHotelId()
                ?? $user->hotel_id
                ?? \App\Models\HotelUser::where('user_id', $user->id)->value('hotel_id');

            $createdWaiter = Waiter::create([
                'hotel_id' => $hotelId,
                'user_id' => (string) $user->id,
                'section' => 'All Sections',
                'shift' => 'morning',
                'experience_level' => 'junior',
                'status' => 'active',
            ]);

            \Log::info(' [RESOLVER] Auto-linked Waiter profile for user', [
                'user_id' => $user->id,
                'waiter_id' => $createdWaiter->id,
                'hotel_id' => $hotelId,
                'primary_role' => $user->role,
            ]);

            return (int) $createdWaiter->id;
        } catch (\Throwable $e) {
            \Log::error(' [RESOLVER] Error auto-creating waiter profile: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }
        
        \Log::error(' [RESOLVER] Could not resolve waiter ID for user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'all_waiters_count' => Waiter::count(),
        ]);
        
        return null;
    }
}