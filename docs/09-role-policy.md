# Step 09 — UserRole Enum + Policies + Middleware Role

## Tujuan

Mempunyai **role-based access control** lengkap:
- Enum `UserRole` (Owner, Admin, Kasir) dengan label & badge class.
- 6 Policy (Product, Category, Order, User, CashSession, Promo).
- Gate `view-reports`.
- Middleware `role:` untuk route protection.

## Prasyarat

- Step 08 selesai

## Konteks

Tanpa policy, semua user yang login bisa hapus order, edit user lain, akses report. Itu security hole. Policy = "siapa boleh ngapain pada model X". Middleware role = "siapa boleh akses route Y".

## Prompt untuk AI

````
Project Laravel POS sudah punya model + seeder. Sekarang implement role & policy.

A. ENUM

1. `app/Enums/UserRole.php`:
```php
namespace App\Enums;

enum UserRole: string {
    case Owner = 'owner';
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string {
        return match($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Kasir => 'Kasir',
        };
    }

    public function badgeClass(): string {
        return match($this) {
            self::Owner => 'danger',
            self::Admin => 'primary',
            self::Kasir => 'info',
        };
    }

    public static function options(): array {
        return [
            self::Owner->value => self::Owner->label(),
            self::Admin->value => self::Admin->label(),
            self::Kasir->value => self::Kasir->label(),
        ];
    }
}
```

B. POLICIES

2. `app/Policies/ProductPolicy.php`:
```php
class ProductPolicy {
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Product $p): bool { return true; }
    public function create(User $user): bool { return true; } // kasir boleh ambil di catalog inline
    public function update(User $user, Product $p): bool { return true; }
    public function delete(User $user, Product $p): bool { return $user->isAdmin(); }
}
```

3. `app/Policies/CategoryPolicy.php`:
```php
class CategoryPolicy {
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Category $c): bool { return true; }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Category $c): bool { return $user->isAdmin(); }
    public function delete(User $user, Category $c): bool { return $user->isAdmin(); }
}
```

4. `app/Policies/OrderPolicy.php`:
```php
class OrderPolicy {
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Order $o): bool {
        return $user->isAdmin() || $o->kasir_id === $user->id;
    }
    public function create(User $user): bool { return true; }
    public function update(User $user, Order $o): bool { return false; } // immutable
    public function delete(User $user, Order $o): bool { return $user->isOwner(); }
    public function refund(User $user, Order $o): bool {
        return $user->isOwner() && $o->status === Order::STATUS_PAID;
    }
}
```

5. `app/Policies/UserPolicy.php`:
```php
class UserPolicy {
    public function viewAny(User $u): bool { return $u->isAdmin(); }
    public function view(User $u, User $t): bool { return $u->isAdmin() || $u->id === $t->id; }
    public function create(User $u): bool { return $u->isOwner(); }
    public function update(User $u, User $t): bool { return $u->isOwner() || $u->id === $t->id; }
    public function delete(User $u, User $t): bool { return $u->isOwner() && $u->id !== $t->id; }
}
```

6. `app/Policies/CashSessionPolicy.php`:
```php
class CashSessionPolicy {
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, CashSession $s): bool {
        return $u->isAdmin() || $s->user_id === $u->id;
    }
    public function forceClose(User $u, CashSession $s): bool {
        return $u->isAdmin() && is_null($s->closed_at);
    }
}
```

7. `app/Policies/PromoPolicy.php`:
```php
class PromoPolicy {
    public function viewAny(User $u): bool { return true; }
    public function view(User $u, Promo $p): bool { return true; }
    public function create(User $u): bool { return $u->isAdmin(); }
    public function update(User $u, Promo $p): bool { return $u->isAdmin(); }
    public function delete(User $u, Promo $p): bool { return $u->isAdmin(); }
}
```

C. REGISTRASI

8. `app/Providers/AuthServiceProvider.php`:
```php
public function boot(): void {
    Gate::policy(Product::class, ProductPolicy::class);
    Gate::policy(Category::class, CategoryPolicy::class);
    Gate::policy(Order::class, OrderPolicy::class);
    Gate::policy(User::class, UserPolicy::class);
    Gate::policy(CashSession::class, CashSessionPolicy::class);
    Gate::policy(Promo::class, PromoPolicy::class);

    Gate::define('view-reports', fn(User $user) => $user->isAdmin());
}
```

Register provider di `bootstrap/providers.php` kalau Laravel 11+ atau `config/app.php` kalau Laravel 10.

D. MIDDLEWARE

9. `app/Http/Middleware/EnsureUserHasRole.php`:
```php
class EnsureUserHasRole {
    public function handle(Request $request, Closure $next, string ...$roles): Response {
        $user = $request->user();
        if (! $user) return redirect()->route('login');
        if (! in_array($user->roles, $roles)) abort(403, __('Anda tidak memiliki akses ke halaman ini.'));
        return $next($request);
    }
}
```

10. Register alias `role` di `bootstrap/app.php` (Laravel 11+) atau `app/Http/Kernel.php`:
```php
// Laravel 11+ bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})

// Laravel 10
protected $middlewareAliases = [
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
];
```

E. APLIKASI

11. Update sidebar pakai `@can`:
```blade
@can('viewAny', App\Models\User::class)
<li>... menu Pengguna ...</li>
@endcan

@can('view-reports')
<li>... menu Laporan ...</li>
@endcan
```

12. Test policy via tinker:
```
>>> $owner = User::where('email','bahri@fic11.com')->first()
>>> $kasir = User::where('roles','kasir')->first()
>>> Gate::forUser($owner)->allows('view-reports')
=> true
>>> Gate::forUser($kasir)->allows('view-reports')
=> false
>>> Gate::forUser($kasir)->allows('delete', Product::first())
=> false
```

13. Update controller pattern. Contoh `CategoryController`:
```php
public function __construct() {
    $this->authorizeResource(Category::class, 'category');
}
```
Auto-call `viewAny`, `view`, `create`, `update`, `delete` policy method untuk tiap action.

Tampilkan output `php artisan route:list --columns=method,uri,middleware | grep role` setelah selesai.
````

## Hasil yang Diharapkan

```
app/
├── Enums/UserRole.php
├── Http/Middleware/EnsureUserHasRole.php
├── Policies/
│   ├── ProductPolicy.php
│   ├── CategoryPolicy.php
│   ├── OrderPolicy.php
│   ├── UserPolicy.php
│   ├── CashSessionPolicy.php
│   └── PromoPolicy.php
└── Providers/AuthServiceProvider.php  ← register policies + gate
```

## Cara Test

```bash
# Test access matrix:
# Login sebagai owner (bahri@fic11.com) → sidebar muncul menu Pengguna + Laporan
# Login sebagai kasir (email random dari seed, password rahasia12345) → menu Pengguna + Laporan HILANG

# Test 403:
# Sebagai kasir, paksa akses URL /user → response 403 + pesan "Anda tidak memiliki akses..."
# Sebagai kasir, lihat order milik kasir lain di /order/{id} → 403

# Test gate via tinker:
php artisan tinker
>>> $u = User::where('roles','kasir')->first()
>>> Gate::forUser($u)->allows('view-reports')
=> false
>>> Gate::forUser($u)->allows('create', App\Models\Order::class)
=> true
```

## Penjelasan untuk Murid

Talking points:

1. **"Policy vs Middleware?"**
   - Middleware: gate per ROUTE (mass protection).
   - Policy: gate per MODEL INSTANCE (granular: "boleh akses order INI tapi tidak order ITU").

2. **"`authorizeResource()` di controller?"** — Bind semua resource action ke policy method dengan naming convention. Hemat 7 baris `$this->authorize(...)` per controller.

3. **"Kenapa Order::update Policy return false?"** — Order is immutable. Untuk refund, pakai method khusus `refund()`. Tidak boleh edit total/items secara langsung.

4. **"OrderPolicy::view check `kasir_id === $user->id`?"** — Kasir cuma boleh lihat order miliknya. Admin lihat semua. Filter ini juga harus diterapkan di Controller `index()` query.

5. **"Gate::define vs Policy?"** — Gate: stateless rule (`view-reports`). Policy: terikat ke model class. View-reports tidak terikat model → Gate cocok.

6. **"`@can` di Blade gagal silent?"** — Default ya, dia just hide tombol. Untuk hard-block, kombinasi dengan `$this->authorize()` di controller (yang throw 403).

7. **"`Gate::forUser($u)->allows(...)` — kapan dipakai?"** — Testing & batch operation. Normalnya `Gate::allows()` pakai user logged-in.

Pertanyaan reflektif:
- "Kalau client butuh role baru `Manager` yang bisa edit produk tapi tidak hapus user, langkah apa?" (→ tambah case di enum, update setiap policy untuk match)
- "Kalau policy logic kompleks (cek waktu, status, multi-relasi), refactor ke action class?" (→ ya, pattern Spec / Policy class jadi facade panggil action)

---

**Next: [10-dashboard.md](10-dashboard.md)**
