<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;

echo "\n========================================\n";
echo "Menu Item Image URL Check\n";
echo "========================================\n\n";

$items = MenuItem::whereNotNull('image')->take(5)->get();

if ($items->isEmpty()) {
    echo "❌ No items with images found\n";
} else {
    echo "✅ Found " . count($items) . " items with images\n\n";
    
    foreach ($items as $item) {
        echo "Item ID: {$item->id}\n";
        echo "  Name: {$item->name}\n";
        echo "  Image (from DB): {$item->image}\n";
        echo "  Image URL (accessor): {$item->image_url}\n";
        echo "  Image URL works: " . (filter_var($item->image_url, FILTER_VALIDATE_URL) ? '✅' : '❌') . "\n";
        echo "\n";
    }
}

echo "========================================\n";
echo "Check AppURL in config:\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "Asset URL for 'storage/test.jpg': " . asset('storage/test.jpg') . "\n";
echo "========================================\n";
?>
