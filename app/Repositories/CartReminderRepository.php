<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartReminder;

class CartReminderRepository
{
    public function find(int $reminderId): ?CartReminder
    {
        return CartReminder::find($reminderId);
    }

    public function updateOrCreate(array $conditions, array $data): CartReminder
    {
        return CartReminder::updateOrCreate($conditions, $data);
    }

    public function cancelPendingForCart(Cart $cart): int
    {
        return $cart->reminders()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    public function countPending(): int
    {
        return CartReminder::where('status', 'pending')->count();
    }

    public function countSentToday(): int
    {
        return CartReminder::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
    }

    public function refresh(CartReminder $reminder): CartReminder
    {
        return $reminder->refresh();
    }
}
