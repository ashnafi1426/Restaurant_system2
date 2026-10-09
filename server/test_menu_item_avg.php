<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;

$item = MenuItem::withoutGlobalScopes()
    ->withCount(['approvedReviews as review_count'])
    ->withAvg('approvedReviews as average_rating', 'rating')
    ->first();

if ($item) {
    echo "Item: {$item->name}\n";
    echo "Review count: {$item->review_count}\n";
    echo "Avg rating: {$item->average_rating}\n";
} else {
    echo "No item\n";
}
