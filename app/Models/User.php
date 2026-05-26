<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'roles',
        'avatar',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->avatar) {
                return asset('storage/avatars/' . $this->avatar);
            }

            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? '?')
                . '&background=3B82F6&color=fff&bold=true';
        });
    }

    public function role(): ?UserRole
    {
        return $this->roles ? UserRole::tryFrom($this->roles) : null;
    }

    public function isOwner(): bool
    {
        return $this->roles === UserRole::Owner->value;
    }

    public function isAdmin(): bool
    {
        return in_array($this->roles, [UserRole::Owner->value, UserRole::Admin->value], true);
    }

    public function isKasir(): bool
    {
        return $this->roles === UserRole::Kasir->value;
    }
}
