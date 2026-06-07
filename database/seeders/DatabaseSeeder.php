<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Owner account — siap login untuk demo semua fitur (Pengguna, Laporan, Promo, dll)
        User::factory()->create([
            'name' => 'Code with Bahri',
            'email' => 'bahri@fic11.com',
            'password' => Hash::make('12345678'),
            'roles' => 'owner',
            'is_active' => true,
        ]);

        // Admin sample
        User::factory()->create([
            'name' => 'Admin Cafe',
            'email' => 'admin@fic11.com',
            'password' => Hash::make('12345678'),
            'roles' => 'admin',
            'is_active' => true,
        ]);

        // Kasir samples — default factory state = kasir
        User::factory(8)->create();

        // Categories — pakai Model::firstOrCreate supaya slug auto-generated via boot event
        $categories = [
            ['name' => 'Makanan', 'icon' => 'cube', 'color' => '#F59E0B', 'sort_order' => 0],
            ['name' => 'Minuman', 'icon' => 'coffee', 'color' => '#3B82F6', 'sort_order' => 1],
            ['name' => 'Snack', 'icon' => 'gift', 'color' => '#10B981', 'sort_order' => 2],
            ['name' => 'Dessert', 'icon' => 'cake', 'color' => '#EC4899', 'sort_order' => 3],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                $cat + ['is_active' => true]
            );
        }

        $this->call([
            ProductSeeder::class,
        ]);
    }
}
