<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Code with Bahri',
            'email' => 'bahri@fic11.com',
            'password' => Hash::make('12345678'),
        ]);

        DB::table('categories')->insert([
            ['name' => 'Makanan'],
            ['name' => 'Minuman'],
            ['name' => 'snack'],
        ]);
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
