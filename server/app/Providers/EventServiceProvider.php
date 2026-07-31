<?php

namespace App\Providers;

use App\Events\OrderReadyEvent;
use App\Events\WaiterAssignedEvent;
use App\Events\DeliveryReassignedEvent;
use App\Listeners\AssignWaiterListener;
use App\Listeners\NotifyWaiterListener;
use App\Listeners\UpdateWorkloadListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        /**
         * Order Ready Event - Triggered when kitchen marks order as ready
         * Fires multiple listeners for assignment and notifications
         */
        OrderReadyEvent::class => [
            AssignWaiterListener::class,
        ],

        /**
         * Waiter Assigned Event - Triggered when delivery is assigned to waiter
         * Can be automatic (from OrderReadyEvent) or manual (from manager)
         */
        WaiterAssignedEvent::class => [
            NotifyWaiterListener::class,
            UpdateWorkloadListener::class,
        ],

        /**
         * Delivery Reassigned Event - Triggered when delivery is reassigned
         * Usually from manager action to move delivery to different waiter
         */
        DeliveryReassignedEvent::class => [
            NotifyWaiterListener::class,
            UpdateWorkloadListener::class,
        ],

        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        // Add model observers here as needed
        // E.g., \App\Models\Order::class => [\App\Observers\OrderObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
