# Step 07 — Model & Migration POS (Phase 1 + Phase 2)

## Tujuan

Mempunyai **schema database POS lengkap** + Model Eloquent dengan relasi, cast, accessor, dan boot event. Mencakup: `products`, `categories`, `orders`, `order_items`, `cash_sessions`, `promos`, plus tambahan kolom user (avatar, is_active, last_login_*, soft-deletes).

## Prasyarat

- Step 06 selesai
- DB sudah migrate baseline (Step 02)

## Konteks

Step ini paling padat schema-wise. Dibagi jadi 2 batch:
- **Batch 1 (Phase 1 lama)**: products, categories, orders, order_items.
- **Batch 2 (Phase 2 enhance)**: kolom tambahan + tabel `cash_sessions`, `promos`, soft delete users, refund cols.

Setiap migration di-eksekusi `migrate` (bukan `fresh`) supaya bisa simulasi production: schema bertahap.

## Prompt untuk AI

````
Project Laravel POS sudah punya auth + UI dasar. Sekarang buat semua schema POS dengan migration + Eloquent model.

A. BATCH 1 — Migrasi Awal (Phase 1)

1. `2023_12_13_144216_create_products_table.php`:
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('price')->default(0);
    $table->integer('stock')->default(0);
    $table->enum('category', ['food','drink','snack'])->default('food'); // backward-compat
    $table->string('image')->nullable();
    $table->timestamps();
});
```

2. `2023_12_14_134344_add_roles_phone_at_users.php`:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable()->after('email');
    $table->enum('roles', ['admin','staff','user'])->default('user')->after('phone');
});
```

3. `2023_12_27_135124_add_favorite_at_products.php`:
```php
Schema::table('products', function (Blueprint $table) {
    $table->boolean('is_best_seller')->default(false);
});
```

4. `2024_01_03_145442_create_orders_table.php`:
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->timestamp('transaction_time');
    $table->integer('total_price');
    $table->integer('total_item');
    $table->foreignId('kasir_id')->constrained('users')->cascadeOnDelete();
    $table->string('payment_method');
    $table->timestamps();
});
```

5. `2024_01_03_145447_create_order_items_table.php`:
```php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->integer('quantity');
    $table->integer('total_price');
    $table->timestamps();
});
```

6. `2024_09_08_025520_create_categories_table.php`:
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

7. `2024_09_08_030550_alter_category_products.php` — ubah `products.category` jadi VARCHAR + tambah `category_id`:
```php
Schema::table('products', function (Blueprint $table) {
    $table->string('category')->default('food')->change(); // dari ENUM ke VARCHAR
    $table->unsignedBigInteger('category_id')->nullable()->after('category');
    // optional: $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
});
```

B. BATCH 2 — Migrasi Enhance (Phase 2)

8. `2026_05_24_152309_phase2_add_avatar_status_login_to_users.php`:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable()->after('phone');
    $table->boolean('is_active')->default(true)->after('avatar');
    $table->timestamp('last_login_at')->nullable()->after('is_active');
    $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
});
```

9. `2026_05_24_152309_phase2_enhance_categories_table.php`:
```php
Schema::table('categories', function (Blueprint $table) {
    $table->string('slug')->nullable()->unique()->after('name');
    $table->string('description', 500)->nullable()->after('slug');
    $table->string('icon', 50)->default('tag')->after('description');
    $table->string('color', 7)->default('#3B82F6')->after('icon');
    $table->unsignedInteger('sort_order')->default(0)->after('color');
    $table->boolean('is_active')->default(true)->after('sort_order');
});
// Backfill slug dari name (Str::slug + dedup)
\App\Models\Category::all()->each(function ($c) {
    if (empty($c->slug)) {
        $base = \Str::slug($c->name);
        $slug = $base; $i = 1;
        while (\App\Models\Category::where('slug', $slug)->where('id','!=',$c->id)->exists()) {
            $slug = $base.'-'.$i++;
        }
        $c->update(['slug' => $slug]);
    }
});
```

10. `2026_05_24_152309_phase2_enhance_orders_table.php`:
```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('order_number', 32)->nullable()->unique()->after('id');
    $table->enum('status', ['pending','paid','cancelled','refunded'])->default('paid')->after('payment_method');
    $table->decimal('subtotal', 12, 2)->default(0)->after('status');
    $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
    $table->decimal('tax', 12, 2)->default(0)->after('discount');
    $table->decimal('amount_paid', 12, 2)->default(0)->after('tax');
    $table->decimal('change_amount', 12, 2)->default(0)->after('amount_paid');
    $table->string('customer_name', 100)->nullable()->after('change_amount');
    $table->text('notes')->nullable()->after('customer_name');
});
// Backfill order_number untuk row lama
\App\Models\Order::whereNull('order_number')->each(function ($o, $i) {
    $o->update([
        'order_number' => 'INV-'.now()->format('Ymd').'-'.str_pad($i+1, 4, '0', STR_PAD_LEFT),
        'subtotal' => $o->total_price,
    ]);
});
```

11. `2026_05_24_152741_phase2_alter_users_roles_to_string.php` — ubah `users.roles` dari ENUM ke VARCHAR + data migration:
```php
// MySQL raw karena ALTER enum tricky
DB::statement("ALTER TABLE users MODIFY roles VARCHAR(20) NOT NULL DEFAULT 'kasir'");
DB::table('users')->whereIn('roles', ['user','staff'])->update(['roles' => 'kasir']);
// 'admin' tetap 'admin'
```

12. `2026_05_25_120000_create_cash_sessions_table.php`:
```php
Schema::create('cash_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('shift_label', 20); // Pagi/Siang/Malam
    $table->integer('opening_float')->default(0);
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
// Tambah cash_session_id di orders:
Schema::table('orders', function (Blueprint $table) {
    $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete()->after('kasir_id');
});
```

13. `2026_05_26_120000_create_promos_table.php`:
```php
Schema::create('promos', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->enum('type', ['percent','rupiah','b1g1']);
    $table->integer('value')->default(0);
    $table->string('code', 50)->nullable()->unique();
    $table->json('applies_to')->nullable();
    $table->integer('min_subtotal')->default(0);
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
    $table->index(['active','starts_at','ends_at'], 'promos_window_idx');
});
// Tambah promo_id + discount_amount di orders:
Schema::table('orders', function (Blueprint $table) {
    $table->foreignId('promo_id')->nullable()->constrained()->nullOnDelete()->after('cash_session_id');
    $table->integer('discount_amount')->default(0)->after('discount');
});
```

14. `2026_05_27_120000_add_soft_deletes_to_users.php`:
```php
Schema::table('users', fn ($t) => $t->softDeletes());
```

15. `2026_05_28_120000_add_refund_columns_to_orders.php`:
```php
Schema::table('orders', function (Blueprint $table) {
    $table->timestamp('refunded_at')->nullable()->after('notes');
    $table->string('refund_reason', 64)->nullable()->after('refunded_at');
    $table->text('refund_note')->nullable()->after('refund_reason');
    $table->decimal('refund_amount', 12, 2)->default(0)->after('refund_note');
    $table->foreignId('refunded_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('refund_amount');
});
```

C. MODELS

16. `app/Models/Product.php`:
```php
class Product extends Model {
    use HasFactory;
    protected $fillable = ['name','description','price','stock','category','category_id','image','is_best_seller'];
    protected $casts = ['price'=>'integer','stock'=>'integer','is_best_seller'=>'boolean'];
    public function category() { return $this->belongsTo(Category::class, 'category_id'); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
    public function getImageUrlAttribute() {
        return $this->image ? asset('storage/products/'.$this->image) : null;
    }
}
```

17. `app/Models/Category.php`:
```php
class Category extends Model {
    use HasFactory;
    protected $fillable = ['name','slug','description','icon','color','sort_order','is_active'];
    protected $casts = ['is_active'=>'boolean','sort_order'=>'integer'];
    public function products() { return $this->hasMany(Product::class); }
    protected static function booted() {
        static::saving(function ($cat) {
            if (empty($cat->slug)) {
                $base = Str::slug($cat->name); $slug = $base; $i = 1;
                while (static::where('slug', $slug)->where('id','!=',$cat->id)->exists()) $slug = $base.'-'.$i++;
                $cat->slug = $slug;
            }
        });
    }
}
```

18. `app/Models/Order.php`:
```php
class Order extends Model {
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number','transaction_time','total_price','total_item','kasir_id','cash_session_id','promo_id',
        'payment_method','status','subtotal','discount','discount_amount','tax','amount_paid','change_amount',
        'customer_name','notes','refunded_at','refund_reason','refund_note','refund_amount','refunded_by_user_id',
    ];
    protected $casts = [
        'transaction_time' => 'datetime',
        'refunded_at' => 'datetime',
        'total_price' => 'decimal:2','subtotal'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2',
        'amount_paid'=>'decimal:2','change_amount'=>'decimal:2','refund_amount'=>'decimal:2',
    ];

    public function kasir() { return $this->belongsTo(User::class, 'kasir_id'); }
    public function cashSession() { return $this->belongsTo(CashSession::class); }
    public function promo() { return $this->belongsTo(Promo::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }

    protected static function booted() {
        static::creating(function ($order) {
            if (empty($order->order_number)) $order->order_number = self::generateOrderNumber();
            if (empty($order->status)) $order->status = self::STATUS_PAID;
        });
    }

    public static function generateOrderNumber(): string {
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'INV-'.now()->format('Ymd').'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string {
        return match($this->status) {
            self::STATUS_PAID => 'Lunas',
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_REFUNDED => 'Refund',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string {
        return match($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_REFUNDED => 'info',
            default => 'secondary',
        };
    }
}
```

19. `app/Models/OrderItem.php`:
```php
class OrderItem extends Model {
    use HasFactory;
    protected $fillable = ['order_id','product_id','quantity','total_price'];
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
```

20. `app/Models/CashSession.php`:
```php
class CashSession extends Model {
    use HasFactory;

    const SHIFT_PAGI = 'Pagi';
    const SHIFT_SIANG = 'Siang';
    const SHIFT_MALAM = 'Malam';

    protected $fillable = [
        'user_id','shift_label','opening_float','opening_note','opened_at',
        'cash_in','cash_out','physical_count','expected_cash','variance','closing_note','closed_at',
    ];
    protected $casts = [
        'opened_at'=>'datetime','closed_at'=>'datetime',
        'opening_float'=>'integer','cash_in'=>'integer','cash_out'=>'integer',
        'physical_count'=>'integer','expected_cash'=>'integer','variance'=>'integer',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }

    public function getIsOpenAttribute() { return is_null($this->closed_at); }
    public function getIsBalancedAttribute() { return $this->variance === 0; }

    public function cashRevenue(): int {
        return (int) $this->orders()
            ->where('payment_method', 'cash')
            ->where('status', Order::STATUS_PAID)
            ->sum('amount_paid');
    }

    public function revenueByMethod(): array {
        return $this->orders()
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('payment_method, SUM(total_price) as total')
            ->groupBy('payment_method')
            ->pluck('total','payment_method')->toArray();
    }

    public function scopeOpen($q) { return $q->whereNull('closed_at'); }
    public function scopeForUser($q, $userId) { return $q->where('user_id', $userId); }
    public static function currentFor(int $userId) {
        return static::open()->forUser($userId)->latest('opened_at')->first();
    }
}
```

21. `app/Models/Promo.php`:
```php
class Promo extends Model {
    use HasFactory;

    const TYPE_PERCENT = 'percent';
    const TYPE_RUPIAH = 'rupiah';
    const TYPE_B1G1 = 'b1g1';

    const TYPES = [self::TYPE_PERCENT, self::TYPE_RUPIAH, self::TYPE_B1G1];

    protected $fillable = ['name','type','value','code','applies_to','min_subtotal','starts_at','ends_at','active'];
    protected $casts = [
        'value' => 'integer','min_subtotal'=>'integer',
        'applies_to' => 'array',
        'starts_at'=>'datetime','ends_at'=>'datetime',
        'active'=>'boolean',
    ];

    public function orders() { return $this->hasMany(Order::class); }

    public function computeDiscount(int $subtotal, array $items = []): int {
        if (! $this->isLive() || $subtotal < $this->min_subtotal) return 0;
        return match($this->type) {
            self::TYPE_PERCENT => (int) floor($subtotal * $this->value / 100),
            self::TYPE_RUPIAH => min($this->value, $subtotal),
            self::TYPE_B1G1 => $this->computeB1G1Discount($items),
            default => 0,
        };
    }

    private function computeB1G1Discount(array $items): int {
        // Sederhana: untuk tiap produk yang qty >= 2, diskon = harga produk * floor(qty/2)
        $total = 0;
        foreach ($items as $it) {
            if (($it['quantity'] ?? 0) >= 2) {
                $total += ($it['price'] ?? 0) * intdiv($it['quantity'], 2);
            }
        }
        return $total;
    }

    public function isLive(?\DateTimeInterface $now = null): bool {
        $now = $now ?? now();
        if (! $this->active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }

    public function typeLabel(): string {
        return match($this->type) {
            self::TYPE_PERCENT => 'Persen',
            self::TYPE_RUPIAH => 'Rupiah',
            self::TYPE_B1G1 => 'Beli 1 Gratis 1',
        };
    }

    public function status(?\DateTimeInterface $now = null): string {
        $now = $now ?? now();
        if (! $this->active) return 'inactive';
        if ($this->starts_at && $now->lt($this->starts_at)) return 'scheduled';
        if ($this->ends_at && $now->gt($this->ends_at)) return 'expired';
        return 'live';
    }

    public function scopeActive($q) { return $q->where('active', true); }
    public function scopeLive($q, ?\DateTimeInterface $now = null) {
        $now = $now ?? now();
        return $q->where('active', true)
            ->where(fn($w) => $w->whereNull('starts_at')->orWhere('starts_at','<=',$now))
            ->where(fn($w) => $w->whereNull('ends_at')->orWhere('ends_at','>=',$now));
    }
    public function scopeByCode($q, string $code) {
        return $q->whereRaw('UPPER(code) = ?', [strtoupper($code)]);
    }
}
```

22. Update `app/Models/User.php`:
```php
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = ['name','email','password','phone','roles','avatar','is_active','last_login_at','last_login_ip'];
    protected $hidden = ['password','remember_token','two_factor_secret','two_factor_recovery_codes'];
    protected $appends = ['avatar_url'];
    protected $casts = [
        'email_verified_at'=>'datetime','password'=>'hashed',
        'is_active'=>'boolean','last_login_at'=>'datetime',
    ];

    public function orders() { return $this->hasMany(Order::class, 'kasir_id'); }
    public function cashSessions() { return $this->hasMany(CashSession::class); }

    public function getAvatarUrlAttribute(): string {
        return $this->avatar
            ? asset('storage/avatars/'.$this->avatar)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=3B82F6&color=fff';
    }

    public function role(): ?UserRole {
        return UserRole::tryFrom($this->roles);
    }
    public function isOwner(): bool { return $this->roles === UserRole::Owner->value; }
    public function isAdmin(): bool { return in_array($this->roles, [UserRole::Owner->value, UserRole::Admin->value]); }
    public function isKasir(): bool { return $this->roles === UserRole::Kasir->value; }
}
```

D. STORAGE LINK
23. Jalankan:
```
php artisan storage:link
mkdir -p storage/app/public/products
mkdir -p storage/app/public/avatars
```

E. EKSEKUSI
24. Jalankan `php artisan migrate` (NOT fresh — supaya bisa simulasi production-style step-wise). Kalau ada error karena Phase 1 belum jalan, jalankan `migrate:fresh` saja sekali (oke karena belum production).

25. Verifikasi semua tabel:
```
php artisan db:show --counts
```

Tampilkan output `db:show --counts` setelah selesai. List semua tabel + 0 row di tabel POS yang baru.
````

## Hasil yang Diharapkan

```
database/migrations/
├── (5 baseline dari Step 02)
├── 2023_12_13_..._create_products_table.php
├── 2023_12_14_..._add_roles_phone_at_users.php
├── 2023_12_27_..._add_favorite_at_products.php
├── 2024_01_03_..._create_orders_table.php
├── 2024_01_03_..._create_order_items_table.php
├── 2024_09_08_..._create_categories_table.php
├── 2024_09_08_..._alter_category_products.php
├── 2026_05_24_..._phase2_add_avatar_status_login_to_users.php
├── 2026_05_24_..._phase2_enhance_categories_table.php
├── 2026_05_24_..._phase2_enhance_orders_table.php
├── 2026_05_24_..._phase2_alter_users_roles_to_string.php
├── 2026_05_25_..._create_cash_sessions_table.php
├── 2026_05_26_..._create_promos_table.php
├── 2026_05_27_..._add_soft_deletes_to_users.php
└── 2026_05_28_..._add_refund_columns_to_orders.php

app/Models/
├── User.php       ← updated (SoftDeletes, role methods, avatar accessor)
├── Product.php
├── Category.php
├── Order.php
├── OrderItem.php
├── CashSession.php
└── Promo.php

storage/app/public/{products, avatars}/  ← folder upload
```

Tabel database setelah migrate:
- users, password_reset_tokens, failed_jobs, personal_access_tokens, migrations
- products, categories, orders, order_items, cash_sessions, promos

## Cara Test

```bash
# 1. Migrate
php artisan migrate:fresh

# 2. Cek schema
php artisan db:show --counts

# 3. Test create model via tinker
php artisan tinker
>>> $c = Category::create(['name' => 'Makanan'])
>>> $c->slug
=> "makanan"
>>> Product::create(['name' => 'Nasi Goreng', 'price' => 15000, 'stock' => 50, 'category_id' => $c->id])
>>> Order::create(['transaction_time' => now(), 'total_price' => 15000, 'total_item' => 1, 'kasir_id' => User::first()->id, 'payment_method' => 'cash'])
>>> Order::first()->order_number
=> "INV-20260607-0001"
>>> User::first()->role()
=> UserRole::Owner
>>> User::first()->avatar_url
=> "https://ui-avatars.com/api/..."

# 4. Storage link
ls -la public/storage
# Harus symlink ke storage/app/public

# 5. Verifikasi soft delete users
>>> $u = User::factory()->create(); $u->delete(); User::count(); User::withTrashed()->count();
```

## Penjelasan untuk Murid

Talking points:

1. **"Kenapa `decimal(12,2)` untuk uang?"** — Precision exact. Float bisa lossy (0.1 + 0.2 = 0.30000000000000004). Decimal SQL menyimpan persis. 12,2 = max 9999999999.99 (Rp 9.9 miliar).

2. **"Kenapa `cascadeOnDelete()` di order_items.order_id?"** — Kalau order dihapus, items ikut hapus. Tidak ada orphan record.

3. **"Tapi kenapa `nullOnDelete()` di orders.cash_session_id?"** — Order tetap ada walau cash_session dihapus. History transaksi harus utuh.

4. **"`booted()` event di Model?"** — Hook event lifecycle: `creating`, `created`, `updating`, `saving`, `deleted`. Auto-generate `order_number` di sini supaya selalu ter-set tanpa lupa di controller.

5. **"Kenapa pakai `enum` SQL untuk status, bukan enum PHP?"** — Karena perlu CHECK constraint di DB. Tapi enum PHP (UserRole) lebih type-safe di code. Kita pakai keduanya: DB enum + PHP enum, cast di Model.

6. **"SoftDeletes di User — kapan dipakai?"** — User self-delete akun. Data tidak benar-benar hilang (untuk audit), tapi tidak muncul di query default. Plus FK integrity ke orders tetap valid.

7. **"`appends => ['avatar_url']`?"** — Otomatis include accessor di JSON serialization. Berguna untuk API response.

8. **"Relasi `belongsTo(User::class, 'kasir_id')`?"** — Custom FK karena nama kolom bukan `user_id`. Eloquent assume `{model_name_lowercase}_id`.

Pertanyaan reflektif:
- "Kalau seller minta tambah field `barcode` ke product, langkah apa?" (→ buat migration `add_barcode_to_products`, add ke `$fillable`, tambah unique index)
- "Kalau ada bug yang require produk yang sudah di-delete masih bisa diakses dari order detail, gimana?" (→ tambah SoftDeletes ke Product, ubah `with('product')` jadi `with(['product' => fn($q) => $q->withTrashed()])`)

---

**Next: [08-seeders.md](08-seeders.md)**
