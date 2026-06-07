<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah FK orders.kasir_id dari cascadeOnDelete → nullOnDelete.
 *
 * Kenapa: kalau user kasir dihapus permanen (force delete), order
 * milik kasir tersebut JANGAN ikut hilang. History keuangan harus utuh.
 * Soft delete user sudah aman, tapi force delete akan trigger cascade.
 *
 * Setelah migration ini, hapus user → orders.kasir_id jadi NULL (yatim),
 * tapi data order tetap ada untuk audit dan rekap revenue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop FK lama (cascadeOnDelete) lalu re-create dengan nullOnDelete.
            $table->dropForeign(['kasir_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('kasir_id')->nullable()->change();
            $table->foreign('kasir_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['kasir_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('kasir_id')->nullable(false)->change();
            $table->foreign('kasir_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
