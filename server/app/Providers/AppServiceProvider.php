<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Multi-Tenant Context
        $this->app->singleton(\App\Services\TenantContext::class, function () {
            return new \App\Services\TenantContext();
        });

        // Register Waiter Services
        $this->app->singleton(\App\Services\Waiter\FloorResolverService::class, 
            fn () => new \App\Services\Waiter\FloorResolverService()
        );

        $this->app->singleton(\App\Services\Waiter\ShiftResolverService::class,
            fn () => new \App\Services\Waiter\ShiftResolverService()
        );

        $this->app->singleton(\App\Services\Waiter\WaiterAvailabilityService::class,
            fn () => new \App\Services\Waiter\WaiterAvailabilityService()
        );

        $this->app->singleton(\App\Services\Waiter\WaiterSelectionEngine::class,
            fn () => new \App\Services\Waiter\WaiterSelectionEngine()
        );

        $this->app->singleton(\App\Services\Waiter\AssignmentStrategy::class, function ($app) {
            return new \App\Services\Waiter\AssignmentStrategy(
                $app->make(\App\Services\Waiter\WaiterAvailabilityService::class)
            );
        });

        $this->app->singleton(\App\Services\Waiter\DeliveryWorkloadService::class,
            fn () => new \App\Services\Waiter\DeliveryWorkloadService()
        );

        $this->app->singleton(\App\Services\Waiter\DeliveryNotificationService::class,
            fn () => new \App\Services\Waiter\DeliveryNotificationService()
        );

        $this->app->singleton(\App\Services\Waiter\AutomaticWaiterAssignmentService::class, function ($app) {
            return new \App\Services\Waiter\AutomaticWaiterAssignmentService(
                $app->make(\App\Services\Waiter\FloorResolverService::class),
                $app->make(\App\Services\Waiter\ShiftResolverService::class),
                $app->make(\App\Services\Waiter\WaiterSelectionEngine::class),
                $app->make(\App\Services\Waiter\DeliveryWorkloadService::class),
                $app->make(\App\Services\Waiter\DeliveryNotificationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure payments table allows nullable guest_id and invoice_id
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `payments` MODIFY `guest_id` CHAR(36) NULL");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `payments` MODIFY `invoice_id` CHAR(36) NULL");
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('waiter_table_assignments')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `waiter_table_assignments` MODIFY `shift_id` CHAR(36) NULL");
            }

            // Ensure floors table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('floors')) {
                \Illuminate\Support\Facades\Schema::create('floors', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('hotel_id')->nullable();
                    $table->string('name');
                    $table->integer('floor_number');
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();

                    $table->index(['hotel_id', 'is_active']);
                    $table->index(['hotel_id', 'floor_number']);
                });

                if (\Illuminate\Support\Facades\Schema::hasTable('hotel_floors')) {
                    $defaultHotelId = \Illuminate\Support\Facades\DB::table('hotels')->value('id');
                    $existing = \Illuminate\Support\Facades\DB::table('hotel_floors')->get();
                    foreach ($existing as $hf) {
                        \Illuminate\Support\Facades\DB::table('floors')->insert([
                            'id' => $hf->id,
                            'hotel_id' => $hf->hotel_id ?? $defaultHotelId,
                            'name' => $hf->name,
                            'floor_number' => $hf->floor_number,
                            'is_active' => $hf->is_active ?? true,
                            'created_at' => $hf->created_at ?? now(),
                            'updated_at' => $hf->updated_at ?? now(),
                        ]);
                    }
                }
            }

            // Ensure rooms table has floor_id
            if (\Illuminate\Support\Facades\Schema::hasTable('rooms')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'floor_id')) {
                    \Illuminate\Support\Facades\Schema::table('rooms', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->uuid('floor_id')->nullable()->after('room_type_id');
                    });
                }
                $roomsWithoutFloor = \Illuminate\Support\Facades\DB::table('rooms')->whereNull('floor_id')->get();
                foreach ($roomsWithoutFloor as $rm) {
                    if (!empty($rm->floor)) {
                        $flr = \Illuminate\Support\Facades\DB::table('floors')
                            ->where('floor_number', $rm->floor)
                            ->when(!empty($rm->hotel_id), fn($q) => $q->where('hotel_id', $rm->hotel_id))
                            ->first();
                        if ($flr) {
                            \Illuminate\Support\Facades\DB::table('rooms')->where('id', $rm->id)->update(['floor_id' => $flr->id]);
                        }
                    }
                }
            }

            // Ensure waiter_floor_assignments columns
            if (!\Illuminate\Support\Facades\Schema::hasTable('waiter_floor_assignments')) {
                \Illuminate\Support\Facades\Schema::create('waiter_floor_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('hotel_id')->nullable();
                    $table->uuid('floor_id');
                    $table->unsignedBigInteger('waiter_id');
                    $table->boolean('is_active')->default(true);
                    $table->timestamp('assigned_at')->nullable()->useCurrent();
                    $table->timestamps();

                    $table->index(['hotel_id', 'is_active']);
                    $table->index(['floor_id', 'is_active']);
                    $table->index(['waiter_id', 'is_active']);
                });
            } else {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `waiter_floor_assignments` MODIFY `shift_id` CHAR(36) NULL");
                if (!\Illuminate\Support\Facades\Schema::hasColumn('waiter_floor_assignments', 'hotel_id')) {
                    \Illuminate\Support\Facades\Schema::table('waiter_floor_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->uuid('hotel_id')->nullable()->after('id');
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('waiter_floor_assignments', 'is_active')) {
                    \Illuminate\Support\Facades\Schema::table('waiter_floor_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->boolean('is_active')->default(true)->after('waiter_id');
                    });
                    if (\Illuminate\Support\Facades\Schema::hasColumn('waiter_floor_assignments', 'status')) {
                        \Illuminate\Support\Facades\DB::table('waiter_floor_assignments')
                            ->where('status', 'active')
                            ->update(['is_active' => true]);
                        \Illuminate\Support\Facades\DB::table('waiter_floor_assignments')
                            ->where('status', '!=', 'active')
                            ->update(['is_active' => false]);
                    }
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('waiter_floor_assignments', 'assigned_at')) {
                    \Illuminate\Support\Facades\Schema::table('waiter_floor_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->timestamp('assigned_at')->nullable()->after('is_active');
                    });
                    \Illuminate\Support\Facades\DB::statement("UPDATE `waiter_floor_assignments` SET `assigned_at` = `created_at` WHERE `assigned_at` IS NULL");
                }
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `waiter_floor_assignments` DROP INDEX `wfa_floor_shift_date_priority`");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `waiter_floor_assignments` DROP INDEX `wfa_waiter_floor_shift_date_unique`");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("
                        UPDATE `waiter_floor_assignments` wfa
                        JOIN `waiters` w ON wfa.waiter_id = w.id
                        SET wfa.hotel_id = w.hotel_id
                        WHERE wfa.hotel_id IS NULL AND w.hotel_id IS NOT NULL
                    ");
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // Silently ignore if already set
        }

        // Include translation helpers
        require_once app_path('Translations/helpers.php');

        // Register view namespace for email templates
        view()->addNamespace('mail', resource_path('views/emails'));
    }
}
