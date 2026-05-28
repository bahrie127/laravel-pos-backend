<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // V1 cafe POS: any signed-in user (incl. kasir) may add menu items.
        // Tighten to isAdmin() if a separate manager role is introduced.
        return $user->exists;
    }

    public function update(User $user, Product $product): bool
    {
        // Same rationale as create — kasir manages catalog inline (stock,
        // price, photo). Destructive ops stay admin-only below.
        return $user->exists;
    }

    public function delete(User $user, Product $product): bool
    {
        // Destructive — keep admin-only.
        return $user->isAdmin();
    }
}
