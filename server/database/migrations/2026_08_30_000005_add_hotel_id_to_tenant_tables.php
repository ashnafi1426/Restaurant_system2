<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * List of all tables directly owned by a hotel tenant.
     */
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
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (!Schema::hasColumn($tableName, 'hotel_id')) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->uuid('hotel_id')->nullable()->index();
                        });
                    } catch (\Throwable $e) {}

                    try {
                        Schema::table($tableName, function (Blueprint $table) {
                            $table->foreign('hotel_id')
                                ->references('id')
                                ->on('hotels')
                                ->cascadeOnDelete();
                        });
                    } catch (\Throwable $e) {}
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'hotel_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['hotel_id']);
                    $table->dropIndex(['hotel_id']);
                    $table->dropColumn('hotel_id');
                });
            }
        }
    }
};
