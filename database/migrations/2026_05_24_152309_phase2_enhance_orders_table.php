<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 32)->nullable()->after('id');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])
                ->default('paid')
                ->after('payment_method');
            $table->decimal('subtotal', 12, 2)->default(0)->after('status');
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('tax');
            $table->decimal('change_amount', 12, 2)->default(0)->after('amount_paid');
            $table->string('customer_name', 100)->nullable()->after('change_amount');
            $table->text('notes')->nullable()->after('customer_name');
        });

        // Backfill order_number untuk pesanan lama: INV-YYYYMMDD-{padded-id}
        $rows = DB::table('orders')->whereNull('order_number')->get();
        foreach ($rows as $row) {
            $datePart = $row->transaction_time
                ? date('Ymd', strtotime($row->transaction_time))
                : date('Ymd', strtotime($row->created_at ?? 'now'));
            $orderNumber = 'INV-' . $datePart . '-' . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
            DB::table('orders')->where('id', $row->id)->update([
                'order_number' => $orderNumber,
                'subtotal' => $row->total_price,
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 32)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn([
                'order_number', 'status', 'subtotal', 'discount',
                'tax', 'amount_paid', 'change_amount', 'customer_name', 'notes',
            ]);
        });
    }
};
