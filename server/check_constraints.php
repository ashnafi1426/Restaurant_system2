<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check the actual constraint
$constraints = DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'waiter_floor_assignments' 
    AND CONSTRAINT_NAME LIKE '%wfa%'
    ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION
");

echo "Constraints on waiter_floor_assignments:\n";
foreach ($constraints as $constraint) {
    echo "- " . $constraint->CONSTRAINT_NAME . ": " . $constraint->COLUMN_NAME . "\n";
}
?>
