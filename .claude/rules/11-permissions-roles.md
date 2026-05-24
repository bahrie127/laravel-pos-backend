# Permissions & Roles

## Current State

- Kolom `roles` di tabel `users` (string, dari migration `add_roles_phone_at_users`)
- Tidak ada Policy, Gate, atau middleware permission check
- Semua route web di-protect hanya dengan `auth`
- API endpoint hanya cek `auth:sanctum` — semua user authenticated bisa CRUD apapun

## Risk

- **Kasir bisa hapus user lain, hapus produk, lihat report omset** — tidak ada control granular.
- Data leak: kasir bisa lihat semua order (termasuk kasir lain).
- Tidak ada audit trail siapa melakukan apa.

## Target

### Phase 2 (Quick) — Enum role + Policy

3 role tetap:
- **owner**: full access, bisa kelola user
- **admin**: kelola produk, kategori, lihat report, tidak bisa hapus user
- **kasir**: hanya bisa create order, lihat order miliknya, lihat produk (read-only)

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Kasir = 'kasir';
    
    public function label(): string
    {
        return match($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Kasir => 'Kasir',
        };
    }
}

// app/Models/User.php
protected $casts = [
    'roles' => UserRole::class,
];

public function isOwner(): bool { return $this->roles === UserRole::Owner; }
public function isAdmin(): bool { return in_array($this->roles, [UserRole::Owner, UserRole::Admin]); }
public function isKasir(): bool { return $this->roles === UserRole::Kasir; }
```

### Policies

```php
// app/Policies/ProductPolicy.php
class ProductPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Product $product): bool { return true; }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Product $product): bool { return $user->isAdmin(); }
    public function delete(User $user, Product $product): bool { return $user->isAdmin(); }
}

// app/Policies/OrderPolicy.php
class OrderPolicy
{
    public function viewAny(User $user): bool { return true; }
    
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->kasir_id === $user->id;
    }
    
    public function create(User $user): bool { return true; }
    public function update(User $user, Order $order): bool { return false; }  // no update
    public function delete(User $user, Order $order): bool { return $user->isOwner(); }
    public function refund(User $user, Order $order): bool { return $user->isOwner(); }
}

// app/Policies/UserPolicy.php
class UserPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $u, User $t): bool { return $u->isAdmin() || $u->id === $t->id; }
    public function create(User $u): bool { return $u->isOwner(); }
    public function update(User $u, User $t): bool { return $u->isOwner() || $u->id === $t->id; }
    public function delete(User $u, User $t): bool { return $u->isOwner() && $u->id !== $t->id; }
}

// app/Policies/ReportPolicy.php
class ReportPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
}

// app/Policies/CategoryPolicy.php
class CategoryPolicy { /* admin only for create/update/delete */ }
```

Register di `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;

Gate::policy(Product::class, ProductPolicy::class);
Gate::policy(Order::class, OrderPolicy::class);
Gate::policy(User::class, UserPolicy::class);
Gate::policy(Category::class, CategoryPolicy::class);

// Reports = gate (no model)
Gate::define('view-reports', fn(User $user) => $user->isAdmin());
```

### Usage in controllers

```php
public function destroy(Product $product)
{
    $this->authorize('delete', $product);
    $product->delete();
    return back()->with('success', __('Produk dihapus.'));
}

// Atau resource controller auto-authorize:
public function __construct() { $this->authorizeResource(Product::class, 'product'); }
```

### Usage in Blade

```blade
@can('create', App\Models\Product::class)
    <a href="{{ route('product.create') }}" class="btn btn-primary">Tambah Produk</a>
@endcan

@can('delete', $product)
    <button class="btn-icon btn-icon-danger">Hapus</button>
@endcan
```

### Middleware untuk route group

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    // Semua role
    Route::get('home', [DashboardController::class, 'index'])->name('home');
    Route::resource('order', OrderController::class);
    
    // Admin only
    Route::middleware('role:admin,owner')->group(function () {
        Route::resource('product', ProductController::class);
        Route::resource('categories', CategoryController::class);
        Route::prefix('reports')->group(function () { /* ... */ });
    });
    
    // Owner only
    Route::middleware('role:owner')->group(function () {
        Route::resource('user', UserController::class);
    });
});
```

Buat middleware:

```php
// app/Http/Middleware/EnsureUserHasRole.php
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!$request->user() || !in_array($request->user()->roles->value, $roles)) {
            abort(403, __('Anda tidak memiliki akses ke halaman ini.'));
        }
        return $next($request);
    }
}

// app/Http/Kernel.php
protected $middlewareAliases = [
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
];
```

### Phase 4 (Robust) — Spatie laravel-permission

Untuk multi-role per user + permission granular:

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Seed permissions:

```php
// database/seeders/RolePermissionSeeder.php
$permissions = [
    'view-products', 'create-products', 'edit-products', 'delete-products',
    'view-categories', 'manage-categories',
    'view-orders', 'create-orders', 'refund-orders',
    'view-users', 'manage-users',
    'view-reports',
];

foreach ($permissions as $p) {
    Permission::firstOrCreate(['name' => $p]);
}

$owner = Role::firstOrCreate(['name' => 'owner']);
$owner->givePermissionTo(Permission::all());

$admin = Role::firstOrCreate(['name' => 'admin']);
$admin->givePermissionTo(['view-products', 'create-products', 'edit-products', 'delete-products', /* ... */]);

$kasir = Role::firstOrCreate(['name' => 'kasir']);
$kasir->givePermissionTo(['view-products', 'view-categories', 'create-orders', 'view-orders']);
```

Migrate kolom `roles` lama jadi role assignment:

```php
// Data migration
User::all()->each(fn($u) => $u->assignRole($u->getOriginal('roles')));
// Lalu drop column 'roles'
```

UI manage role: page baru `/settings/roles` (Phase 4).

### Audit log (Spatie activitylog)

```bash
composer require spatie/laravel-activitylog
php artisan migrate
```

Trait `LogsActivity` di model penting:

```php
// app/Models/Order.php
use LogsActivity;

protected static $logAttributes = ['total_price', 'status', 'payment_method'];
protected static $logName = 'order';

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly($this->logAttributes)
        ->logOnlyDirty();
}
```

Lihat log: page baru `/settings/activity-log`.

## Action Items

### Phase 2
- [ ] Buat enum `App\Enums\UserRole`
- [ ] Cast `roles` di User model ke enum
- [ ] Helper methods `isOwner()`, `isAdmin()`, `isKasir()` di User
- [ ] Buat 5 Policy: Product, Order, User, Category, Report
- [ ] Register policy di `AppServiceProvider::boot()`
- [ ] Buat middleware `role:`
- [ ] Apply policy di setiap web controller (`authorize()` atau `authorizeResource()`)
- [ ] Apply policy di setiap API controller juga
- [ ] Blade `@can` di tombol & menu sidebar
- [ ] 403 page custom

### Phase 4
- [ ] Migrate ke Spatie laravel-permission
- [ ] Buat seeder permission
- [ ] UI manage role & permission
- [ ] Install Spatie activitylog
- [ ] Apply LogsActivity trait di Order, Product, User, Category
- [ ] UI activity log viewer (filter by causer, subject, date)
