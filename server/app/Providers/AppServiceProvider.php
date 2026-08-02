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
                $app->make(\App\Services\Waiter\AssignmentStrategy::class),
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
        // Register view namespace for email templates
        view()->addNamespace('mail', resource_path('views/emails'));
    }
}
