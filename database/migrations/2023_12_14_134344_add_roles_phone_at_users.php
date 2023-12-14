<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //phone
            $table->string('phone')->nullable()->after('email');
            //roles enum (admin, staf, user)
            $table->enum('roles', ['admin', 'staff', 'user'])->default('user')->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //phone
            $table->dropColumn('phone');
            //roles enum (admin, staf, user)
            $table->dropColumn('roles');
        });
    }
};
