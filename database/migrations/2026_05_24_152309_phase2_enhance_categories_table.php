<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('description', 500)->nullable()->after('slug');
            $table->string('icon', 50)->default('tag')->after('description');
            $table->string('color', 7)->default('#3B82F6')->after('icon');
            $table->unsignedInteger('sort_order')->default(0)->after('color');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        // Backfill slug untuk row yang sudah ada
        $rows = DB::table('categories')->whereNull('slug')->get();
        foreach ($rows as $row) {
            $base = Str::slug($row->name) ?: 'kategori';
            $slug = $base;
            $i = 1;
            while (DB::table('categories')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $base . '-' . ++$i;
            }
            DB::table('categories')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'description', 'icon', 'color', 'sort_order', 'is_active']);
        });
    }
};
