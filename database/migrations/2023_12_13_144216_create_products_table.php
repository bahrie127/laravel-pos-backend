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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            //description
            $table->text('description')->nullable();
            //price
            $table->integer('price')->default(0);
            //stock
            $table->integer('stock')->default(0);
            //category enum (food, drink, snack)
            $table->enum('category', ['food', 'drink', 'snack']);
            //image
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
