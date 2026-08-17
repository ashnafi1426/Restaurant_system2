<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting database schema update to alter all ENUM columns to VARCHAR...\n";

$statements = [
    "ALTER TABLE users MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'guest'",
    "ALTER TABLE users MODIFY COLUMN activation_status VARCHAR(50) NOT NULL DEFAULT 'activated'",
    "ALTER TABLE check_outs MODIFY COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'",
    "ALTER TABLE check_outs MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE restaurant_charges MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'unpaid'",
    "ALTER TABLE notifications MODIFY COLUMN type VARCHAR(100) NOT NULL DEFAULT 'general'",
    "ALTER TABLE orders MODIFY COLUMN source VARCHAR(50) NOT NULL DEFAULT 'receptionist'",
    "ALTER TABLE orders MODIFY COLUMN order_type VARCHAR(50) NOT NULL DEFAULT 'room_service'",
    "ALTER TABLE menu_items MODIFY COLUMN category VARCHAR(100) NULL",
    "ALTER TABLE housekeeping_tasks MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE housekeeping_tasks MODIFY COLUMN priority VARCHAR(50) NOT NULL DEFAULT 'medium'",
    "ALTER TABLE laundry_requests MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE room_service_deliveries MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE waiters MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'active'",
    "ALTER TABLE waiters MODIFY COLUMN shift VARCHAR(50) NULL DEFAULT 'morning'",
    "ALTER TABLE waiters MODIFY COLUMN experience_level VARCHAR(50) NULL DEFAULT 'junior'",
    "ALTER TABLE waiters MODIFY COLUMN employment_type VARCHAR(50) NULL DEFAULT 'full_time'",
    "ALTER TABLE waiters MODIFY COLUMN availability VARCHAR(50) NULL DEFAULT 'offline'",
    "ALTER TABLE hotel_shifts MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'active'",
    "ALTER TABLE waiter_floor_assignments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'assigned'",
    "ALTER TABLE waiter_floor_assignments MODIFY COLUMN priority VARCHAR(50) NOT NULL DEFAULT 'primary'",
    "ALTER TABLE delivery_tasks MODIFY COLUMN assignment_type VARCHAR(50) NOT NULL DEFAULT 'automatic'",
    "ALTER TABLE delivery_tasks MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting_assignment'",
    "ALTER TABLE payments MODIFY COLUMN payment_provider VARCHAR(50) NOT NULL DEFAULT 'chapa'",
    "ALTER TABLE payments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE invoices MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE transactions MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'payment'",
    "ALTER TABLE transactions MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'",
    "ALTER TABLE receipts MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'issued'",
    "ALTER TABLE refunds MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'requested'",
    "ALTER TABLE refunds MODIFY COLUMN refund_method VARCHAR(50) NOT NULL DEFAULT 'original_payment'",
    "ALTER TABLE restaurant_tables MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'available'",
    "ALTER TABLE waiter_table_assignments MODIFY COLUMN priority VARCHAR(50) NOT NULL DEFAULT 'primary'",
    "ALTER TABLE waiter_table_assignments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'active'",
];

$successCount = 0;
$skippedCount = 0;

foreach ($statements as $sql) {
    try {
        DB::statement($sql);
        echo "SUCCESS: {$sql}\n";
        $successCount++;
    } catch (\Exception $e) {
        echo "SKIPPED/NOTICE: " . $e->getMessage() . "\n";
        $skippedCount++;
    }
}

// Clear all Laravel authorization and schema caches
try {
    \Illuminate\Support\Facades\Cache::flush();
    echo "Cache flushed successfully.\n";
} catch (\Exception $e) {
    echo "Cache flush note: " . $e->getMessage() . "\n";
}

echo "Database update completed! {$successCount} statements executed successfully, {$skippedCount} skipped.\n";
