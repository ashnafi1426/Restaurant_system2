<?php

namespace App\Observers;

use App\Models\Guest;
use App\Models\MenuItemReview;

class GuestObserver
{
    /**
     * Handle the Guest "deleting" event.
     * 
     * When a guest is deleted, anonymize their approved reviews by setting
     * guest_id to null while preserving the guest name in the display.
     * The MenuItemReview model's getAnonymizedGuestNameAttribute() will
     * return "Anonymous Guest" when guest_id is null.
     *
     * @param  \App\Models\Guest  $guest
     * @return void
     */
    public function deleting(Guest $guest): void
    {
        // Anonymize approved reviews by setting guest_id to null
        // The guest name is preserved through the model's accessor which returns
        // "Anonymous Guest" when the guest relationship is null
        MenuItemReview::where('guest_id', $guest->id)
            ->where('status', MenuItemReview::STATUS_APPROVED)
            ->update(['guest_id' => null]);
    }
}
