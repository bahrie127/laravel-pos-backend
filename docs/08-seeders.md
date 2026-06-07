# Step 08 — Seeders & Factories

## Tujuan

Mempunyai **data dummy lengkap** untuk development: 1 user owner siap login, beberapa kasir/admin, 3 kategori, 30 produk, dan opsional order historis untuk demo dashboard.

## Prasyarat

- Step 07 selesai (schema POS siap)

## Konteks

Setiap kali tim atau peserta workshop clone repo, mereka butuh data siap pakai untuk eksplorasi UI. Seeder + factory = `php artisan migrate:fresh --seed` → siap demo.

## Prompt untuk AI

````
Project Laravel POS sudah punya model lengkap. Sekarang buat seeder & factory.

A. FACTORIES

1. `database/factories/UserFactory.php` (update default Laravel):
```php
public function definition(): array {
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => Hash::make('rahasia12345'),
        'phone' => fake()->phoneNumber(),
        'roles' => 'kasir',
        'is_active' => true,
    ];
}
public function owner() { return $this->state(fn() => ['roles' => 'owner']); }
public function admin() { return $this->state(fn() => ['roles' => 'admin']); }
public function inactive() { return $this->state(fn() => ['is_active' => false]); }
```

2. `database/factories/CategoryFactory.php`:
```php
public function definition(): array {
    $name = fake()->randomElement(['Makanan', 'Minuman', 'Snack', 'Dessert', 'Kopi', 'Roti']);
    return [
        'name' => $name,
        'description' => fake()->sentence(),
        'icon' => fake()->randomElement(['tag','cube','gift','cake','coffee','sparkles']),
        'color' => fake()->randomElement(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899']),
        'is_active' => true,
        'sort_order' => 0,
    ];
}
```

3. `database/factories/ProductFactory.php`:
```php
public function definition(): array {
    $name = fake()->randomElement(['Nasi Goreng','Mie Ayam','Bakso','Es Teh','Kopi Susu','Roti Bakar','Pisang Goreng','Ayam Geprek','Soto Ayam','Sate Ayam']);
    return [
        'name' => $name.' '.fake()->word(),
        'description' => fake()->paragraph(2),
        'price' => fake()->numberBetween(5, 50) * 1000,
        'stock' => fake()->numberBetween(0, 100),
        'category' => 'food', // backward-compat
        'category_id' => Category::inRandomOrder()->first()?->id ?? 1,
        'is_best_seller' => fake()->boolean(20),
    ];
}
```

4. `database/factories/OrderFactory.php`:
```php
public function definition(): array {
    $items = fake()->numberBetween(1, 5);
    $total = fake()->numberBetween(10000, 200000);
    return [
        'transaction_time' => fake()->dateTimeBetween('-30 days', 'now'),
        'total_price' => $total,
        'subtotal' => $total,
        'total_item' => $items,
        'kasir_id' => User::where('roles','kasir')->inRandomOrder()->first()?->id ?? 1,
        'payment_method' => fake()->randomElement(['cash','qris','transfer']),
        'status' => 'paid',
        'amount_paid' => $total,
        'change_amount' => 0,
    ];
}
```

5. `database/factories/OrderItemFactory.php`:
```php
public function definition(): array {
    $product = Product::inRandomOrder()->first();
    $qty = fake()->numberBetween(1, 3);
    return [
        'order_id' => Order::factory(),
        'product_id' => $product?->id ?? 1,
        'quantity' => $qty,
        'total_price' => ($product?->price ?? 10000) * $qty,
    ];
}
```

B. SEEDERS

6. `database/seeders/DatabaseSeeder.php`:
```php
public function run(): void {
    // 1 Owner siap login
    User::factory()->owner()->create([
        'name' => 'Code with Bahri',
        'email' => 'bahri@fic11.com',
        'password' => Hash::make('12345678'),
    ]);

    // 2 Admin + 5 Kasir
    User::factory()->admin()->count(2)->create();
    User::factory()->count(5)->create(); // default 'kasir'

    // Categories
    $this->call(CategorySeeder::class);
    $this->call(ProductSeeder::class);

    // Order historis (opsional, untuk demo dashboard)
    if (app()->environment('local','testing')) {
        $this->call(OrderSeeder::class);
    }
}
```

7. `database/seeders/CategorySeeder.php`:
```php
public function run(): void {
    $cats = [
        ['name' => 'Makanan', 'icon' => 'cube',   'color' => '#F59E0B'],
        ['name' => 'Minuman', 'icon' => 'coffee', 'color' => '#3B82F6'],
        ['name' => 'Snack',   'icon' => 'gift',   'color' => '#10B981'],
        ['name' => 'Dessert', 'icon' => 'cake',   'color' => '#EC4899'],
    ];
    foreach ($cats as $i => $c) {
        Category::firstOrCreate(['name' => $c['name']], $c + ['sort_order' => $i, 'is_active' => true]);
    }
}
```

8. `database/seeders/ProductSeeder.php`:
```php
public function run(): void {
    Product::factory()->count(30)->create();
}
```

9. `database/seeders/OrderSeeder.php`:
```php
public function run(): void {
    // 50 order dengan items
    Order::factory()->count(50)->create()->each(function ($order) {
        $itemCount = rand(1, 4);
        $total = 0;
        for ($i = 0; $i < $itemCount; $i++) {
            $product = Product::inRandomOrder()->first();
            $qty = rand(1, 3);
            $sub = $product->price * $qty;
            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'total_price' => $sub,
            ]);
            $total += $sub;
        }
        $order->update([
            'total_price' => $total,
            'subtotal' => $total,
            'amount_paid' => $total,
            'total_item' => $itemCount,
        ]);
    });
}
```

C. EKSEKUSI

10. Jalankan:
```
php artisan migrate:fresh --seed
```

11. Verifikasi:
- `User::count()` = 8 (1 owner + 2 admin + 5 kasir)
- `Category::count()` = 4
- `Product::count()` = 30
- `Order::count()` = 50
- Login dengan `bahri@fic11.com` / `12345678` → masuk ke dashboard

Tampilkan output `migrate:fresh --seed` setelah selesai.
````

## Hasil yang Diharapkan

```
database/
├── factories/
│   ├── UserFactory.php       ← updated
│   ├── CategoryFactory.php   ← baru
│   ├── ProductFactory.php    ← baru
│   ├── OrderFactory.php      ← baru
│   └── OrderItemFactory.php  ← baru
└── seeders/
    ├── DatabaseSeeder.php    ← updated
    ├── CategorySeeder.php    ← baru
    ├── ProductSeeder.php     ← baru
    └── OrderSeeder.php       ← baru
```

## Cara Test

```bash
# 1. Fresh seed
php artisan migrate:fresh --seed

# 2. Hitung row
php artisan tinker
>>> User::count(); Category::count(); Product::count(); Order::count(); OrderItem::count();

# 3. Test query (untuk persiapan dashboard di Step 10)
>>> Order::whereDate('transaction_time', today())->count()
>>> Product::where('stock', '<', 5)->count()
>>> OrderItem::groupBy('product_id')->selectRaw('product_id, SUM(quantity) as q')->orderByDesc('q')->limit(5)->get()

# 4. Login dengan owner
# Buka /login, email: bahri@fic11.com, password: 12345678 → ke /home
```

## Penjelasan untuk Murid

Talking points:

1. **"Factory vs Seeder?"**
   - Factory: blueprint untuk satu record (dengan fake data).
   - Seeder: orchestrator yang panggil factory + manual insert untuk skenario tertentu.

2. **"Kenapa `firstOrCreate`?"** — Idempotent. Bisa di-rerun tanpa duplikat. Default key untuk match = arg pertama.

3. **"`state()` di factory?"** — Override default value untuk skenario khusus. `User::factory()->admin()->count(2)` = 2 admin.

4. **"`app()->environment('local','testing')`?"** — Order historis hanya di-seed di dev/test, jangan di production (yang menyetel APP_ENV=production). Aman.

5. **"`Order::factory()->count(50)->create()->each(...)`?"** — Buat 50 order, lalu untuk tiap order, generate items. Pattern parent-child.

6. **"Kenapa tidak pakai `OrderItem::factory()` di seeder?"** — Order factory akan trigger `creating` event yang generate `order_number`. Tapi items butuh total dari sum items → chicken-egg. Solusi: buat order kosong dulu → loop items → update total order.

Pertanyaan reflektif:
- "Cara seed 1000 order untuk benchmark performance?" (→ `Order::factory()->count(1000)->create()` + di-`chunk` supaya hemat memory)
- "Bisakah seeder hanya dijalankan untuk class tertentu?" (→ `php artisan db:seed --class=ProductSeeder`)

---

**Next: [09-role-policy.md](09-role-policy.md)**
