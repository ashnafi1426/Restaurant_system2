<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'bamlaksle@gmail.com')->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "User ID: {$user->id}\n";
echo "Role: {$user->role}\n";
echo "Has Receptionist: " . ($user->receptionist ? 'YES' : 'NO') . "\n";

if ($user->receptionist) {
    echo "Receptionist ID: {$user->receptionist->id}\n";
    echo "Employee Code: {$user->receptionist->employee_code}\n";
} else {
    echo "\nERROR: User has no receptionist record!\n";
    echo "Creating receptionist record...\n";
    
    $receptionist = \App\Models\Receptionist::create([
        'id' => $user->id,  // MUST match user ID for foreign key
        'user_id' => $user->id,
        'employee_code' => 'REC' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        'shift' => 'day',
        'status' => 'active',
    ]);
    
    echo "Receptionist created with ID: {$receptionist->id}\n";
}
