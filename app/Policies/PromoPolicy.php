<?php

namespace App\Policies;

use App\Models\Promo;
use App\Models\User;

class PromoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Promo $promo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Promo $promo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Promo $promo): bool
    {
        return $user->isAdmin();
    }
}
