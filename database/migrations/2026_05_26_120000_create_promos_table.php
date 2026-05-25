<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promo/voucher catalog. Three types:
     *   - 'percent' → value 0-100, applied as percentage of subtotal
     *   - 'rupiah'  → value in rupiah, flat discount
     *   - 'b1g1'    → buy-one-get-one (cheapest item free); value ignored
     *
     * `code` is the voucher code (NULL = auto-applies when window is active).
     * `applies_to` is a JSON blob (e.g. {"category":"kopi"} or
     *  {"product_ids":[1,2]}); NULL = all products.
     */
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['percent', 'rupiah', 'b1g1']);
            $table->integer('value')->default(0);
            $table->string('code', 50)->nullable()->unique();
            $table->json('applies_to')->nullable();
            $table->integer('min_subtotal')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'starts_at', 'ends_at'], 'promos_window_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promo_id')
                ->nullable()
                ->after('cash_session_id')
                ->constrained('promos')
                ->nullOnDelete();
            $table->integer('discount_amount')->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropColumn(['promo_id', 'discount_amount']);
        });
        Schema::dropIfExists('promos');
    }
};
