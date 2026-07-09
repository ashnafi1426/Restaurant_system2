<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->boot();

use App\Models\MenuItem;

$count = MenuItem::count();
$breakfast = MenuItem::where('category', 'breakfast')->count();
$lunch = MenuItem::where('category', 'lunch')->count();
$dinner = MenuItem::where('category', 'dinner')->count();
$drinks = MenuItem::where('category', 'drinks')->count();
$dessert = MenuItem::where('category', 'dessert')->count();

echo "Total: $count\n";
echo "Breakfast: $breakfast\n";
echo "Lunch: $lunch\n";
echo "Dinner: $dinner\n";
echo "Drinks: $drinks\n";
echo "Dessert: $dessert\n";

if ($count === 0) {
    echo "SUCCESS: All mock data removed!\n";
} else {
    echo "REMAINING: $count items still in database\n";
}
