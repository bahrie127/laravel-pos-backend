<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pakai raw SQL karena Laravel schema builder kurang ergonomis untuk change ENUM
        DB::statement("ALTER TABLE users MODIFY roles VARCHAR(20) NOT NULL DEFAULT 'kasir'");

        // Map nilai lama 'user'/'staff' → 'kasir', 'admin' tetap admin
        DB::table('users')->whereIn('roles', ['user', 'staff'])->update(['roles' => 'kasir']);
    }

    public function down(): void
    {
        DB::table('users')->whereNotIn('roles', ['admin', 'staff', 'user'])->update(['roles' => 'user']);
        DB::statement("ALTER TABLE users MODIFY roles ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user'");
    }
};
