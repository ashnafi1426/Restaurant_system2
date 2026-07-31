<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('hotel_shifts')->count();
echo "Total Shifts: " . $count . "\n";

$active = DB::table('hotel_shifts')->where('status', 'active')->count();
echo "Active Shifts: " . $active . "\n";

$shifts = DB::table('hotel_shifts')->where('status', 'active')->get();
foreach ($shifts as $shift) {
    echo "- " . $shift->name . " (" . $shift->start_time . "-" . $shift->end_time . ")\n";
}
?>
