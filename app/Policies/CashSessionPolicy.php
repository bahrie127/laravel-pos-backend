<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // kasir lihat shift sendiri, admin lihat semua (di-filter di controller)
    }

    public function view(User $user, CashSession $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    /**
     * Only admin/owner can force-close another user's shift.
     */
    public function forceClose(User $user, CashSession $session): bool
    {
        return $user->isAdmin() && $session->is_open;
    }
}
