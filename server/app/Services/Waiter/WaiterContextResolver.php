<?php

namespace App\Services\Waiter;

use App\Models\User;
use App\Models\Waiter;

class WaiterContextResolver
{
    public function resolveWaiterId(?User $user): ?int
    {
        if (!$user) {
            \Log::warning('🔴 [RESOLVER] No user provided');
            return null;
        }
        
        \Log::debug('🔵 [RESOLVER] Resolving waiter ID for user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'N/A',
            'relation_loaded' => $user->relationLoaded('waiter') ? 'yes' : 'no',
        ]);
        
        // Try loaded relationship first
        if ($user->relationLoaded('waiter') && $user->waiter) {
            $waiterId = (int) $user->waiter->id;
            \Log::info('✅ [RESOLVER] Waiter ID resolved from loaded relation', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }

        // Try loading missing relationship
        $user->loadMissing('waiter');

        if ($user->waiter) {
            $waiterId = (int) $user->waiter->id;
            \Log::info('✅ [RESOLVER] Waiter ID resolved from loadMissing', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }
        
        \Log::warning('⚠️ [RESOLVER] No waiter relation found, trying fallback lookup', [
            'user_id' => $user->id,
        ]);

        // Fallback: try finding by email or phone
        $fallbackWaiter = Waiter::whereHas('user', function ($query) use ($user) {
            $query->where('email', $user->email)
                ->orWhere('phone', $user->phone);
        })->first();

        if ($fallbackWaiter?->id) {
            $waiterId = (int) $fallbackWaiter->id;
            \Log::warning('⚠️ [RESOLVER] Waiter ID resolved from fallback lookup', [
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
            ]);
            return $waiterId;
        }
        
        \Log::error('❌ [RESOLVER] Could not resolve waiter ID for user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'all_waiters_count' => Waiter::count(),
        ]);
        
        return null;
    }
}