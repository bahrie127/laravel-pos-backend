<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standard-POS cash drawer / shift tracking for coffeeshop:
     *   - one open session per cashier at a time (enforced in controller)
     *   - opening_float captured at open
     *   - physical_count + variance captured at close
     *   - cash_in / cash_out support petty-cash flows during the shift
     *   - orders.cash_session_id attributes revenue to a session
     */
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('shift_label', 20); // 'Pagi' | 'Siang' | 'Malam'
            $table->integer('opening_float');
            $table->text('opening_note')->nullable();
            $table->timestamp('opened_at');

            $table->integer('cash_in')->default(0);
            $table->integer('cash_out')->default(0);

            $table->integer('physical_count')->nullable();
            $table->integer('expected_cash')->nullable();
            $table->integer('variance')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'closed_at'], 'cash_sessions_user_open_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cash_session_id')
                ->nullable()
                ->after('kasir_id')
                ->constrained('cash_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn('cash_session_id');
        });

        Schema::dropIfExists('cash_sessions');
    }
};
