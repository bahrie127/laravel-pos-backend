<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Kasir => 'Kasir',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Owner => 'badge-soft-danger',
            self::Admin => 'badge-soft-primary',
            self::Kasir => 'badge-soft-info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }

        return $opts;
    }
}
