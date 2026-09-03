<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Hotel;

return new class extends Migration
{
    protected array $tenantTables = [
        'hotel_settings',
        'room_types',
        'rooms',
        'hotel_floors',
        'hotel_shifts',
        'guests',
        'reservations',
        'check_ins',
        'check_outs',
        'categories',
        'menu_items',
        'orders',
        'restaurant_tables',
        'payments',
        'invoices',
        'waiters',
        'waiter_assignments',
        'waiter_floor_assignments',
        'waiter_table_assignments',
        'delivery_tasks',
        'delivery_logs',
        'waiter_performance',
        'complaint_tickets',
        'inventory_management',
        'performance_metrics',
        'housekeeping_tasks',
        'laundry_requests',
        'room_service_deliveries',
        'restaurant_charges',
        'walk_in_payments',
        'notifications',
        'manager_notifications',
        'waiter_notifications',
        'review_notifications',
        'menu_item_reviews',
        'manager_dashboard_settings',
        'manager_announcements',
        'manager_activity_logs',
        'manager_reports',
        'audit_logs',
        'reservation_audit_logs',
        'manager_audit_logs',
    ];

    public function up(): void
    {
        $hotel = Hotel::where('slug', 'executive-horizon')->first();

        if (!$hotel) {
            $hotel = Hotel::first();
        }

        if (!$hotel) {
            return;
        }

        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'hotel_id')) {
                DB::table($tableName)
                    ->whereNull('hotel_id')
                    ->update(['hotel_id' => $hotel->id]);
            }
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback to prevent data loss
    }
};
