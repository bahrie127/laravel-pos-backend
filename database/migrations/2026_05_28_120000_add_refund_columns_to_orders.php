<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('notes');
            $table->string('refund_reason', 64)->nullable()->after('refunded_at');
            $table->text('refund_note')->nullable()->after('refund_reason');
            $table->decimal('refund_amount', 12, 2)->default(0)->after('refund_note');
            $table->foreignId('refunded_by_user_id')
                ->nullable()
                ->after('refund_amount')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by_user_id');
            $table->dropColumn([
                'refunded_at',
                'refund_reason',
                'refund_note',
                'refund_amount',
            ]);
        });
    }
};
