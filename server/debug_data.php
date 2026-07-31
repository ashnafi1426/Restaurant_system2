<?php
echo "=== CHECKING DELIVERY_TASKS TABLE ===\n";
$count = \App\Models\DeliveryTask::count();
echo "Total delivery_tasks records: $count\n";

echo "\n=== CHECKING WAITERS TABLE ===\n";
$waiterCount = \App\Models\Waiter::count();
echo "Total waiters: $waiterCount\n";

if ($waiterCount > 0) {
    echo "\nWaiter details:\n";
    $waiters = \App\Models\Waiter::with('user')->limit(3)->get();
    foreach ($waiters as $w) {
        echo "  - ID: {$w->id}, User: {$w->user?->name}, Status: {$w->status}\n";
    }
}

echo "\n=== CHECKING USERS WITH WAITER ROLE ===\n";
$waiterUsers = \App\Models\User::where('role', 'waiter')->count();
echo "Users with waiter role: $waiterUsers\n";

if ($count > 0) {
    echo "\n=== SAMPLE DELIVERY_TASKS ===\n";
    $tasks = \App\Models\DeliveryTask::limit(3)->get();
    foreach ($tasks as $t) {
        echo "  - Task ID: {$t->id}, Waiter ID: {$t->waiter_id}, Status: {$t->status}, Assigned: {$t->assigned_at}\n";
    }
}

echo "\n=== CHECKING USER-WAITER RELATIONSHIP ===\n";
$user = \App\Models\User::where('role', 'waiter')->first();
if ($user) {
    echo "User: {$user->id} - {$user->name}\n";
    echo "Has waiter relation: " . ($user->waiter ? 'YES' : 'NO') . "\n";
    if ($user->waiter) {
        echo "Waiter ID: {$user->waiter->id}\n";
        echo "Deliveries for this waiter: " . $user->waiter->deliveryTasks()->count() . "\n";
    }
}
?>
