<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class ClaimGuestOrdersOnVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->isAdmin()) {
            return;
        }

        Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)])
            ->update(['user_id' => $user->id]);
    }
}
