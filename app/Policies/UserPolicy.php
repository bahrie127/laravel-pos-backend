<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $current, User $target): bool
    {
        return $current->isAdmin() || $current->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $current, User $target): bool
    {
        return $current->isOwner() || $current->id === $target->id;
    }

    public function delete(User $current, User $target): bool
    {
        return $current->isOwner() && $current->id !== $target->id;
    }
}
