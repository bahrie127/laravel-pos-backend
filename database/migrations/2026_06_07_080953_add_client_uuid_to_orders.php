<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `client_uuid` UUID UNIQUE di orders untuk idempotency.
 *
 * Skenario: Flutter app buat order offline → POST ke /api/orders → network blip
 * → FE retry → BE process 2x → DUPLIKAT order. Solusi: FE generate UUID per
 * order saat dibuat lokal, kirim di payload. BE cek `client_uuid` ada → return
 * existing order (idempotent), tidak buat baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }
};
