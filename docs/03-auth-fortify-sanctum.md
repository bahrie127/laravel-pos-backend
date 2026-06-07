# Step 03 — Auth: Fortify (Web) + Sanctum (API)

## Tujuan

Mempunyai **autentikasi dua jalur**:
- Web admin: login/logout/forgot-password via Fortify.
- Mobile API: login → dapat Sanctum token → akses endpoint protected.

## Prasyarat

- Step 02 selesai (DB baseline + 2FA cols)

## Konteks

Fortify = headless auth (logic siap, view custom kita yang buat). Sanctum = token-based auth untuk API. Keduanya bisa hidup berdampingan: Fortify untuk dashboard admin, Sanctum untuk Flutter app.

## Prompt untuk AI

````
Project Laravel POS sudah punya DB baseline. Sekarang setup auth dual-mode:

A. SANCTUM (API)
1. Publish config & migration Sanctum:
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
2. `app/Models/User.php`:
   - Add `use Laravel\Sanctum\HasApiTokens;`
   - Add `use HasApiTokens;` di body class
   - Pastikan `$fillable` punya: name, email, password, phone, roles, avatar, is_active, last_login_at, last_login_ip
   - `$hidden`: password, remember_token, two_factor_secret, two_factor_recovery_codes
   - `$casts`: ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'datetime']

B. FORTIFY (Web)
3. Install Fortify scaffold:
   php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
   
4. Edit `config/fortify.php`:
   - `'home' => '/home'`
   - `'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
     ]`

5. Edit `app/Providers/FortifyServiceProvider.php` di `boot()`:
   - `Fortify::loginView(fn () => view('pages.auth.login'));`
   - `Fortify::requestPasswordResetLinkView(fn () => view('pages.auth.forgot-password'));`
   - `Fortify::resetPasswordView(fn ($request) => view('pages.auth.reset-password', ['request' => $request]));`
   - Rate limiter login:
     ```php
     RateLimiter::for('login', function (Request $request) {
         $throttleKey = Str::lower($request->input(Fortify::username())).'|'.$request->ip();
         return Limit::perMinute(5)->by($throttleKey);
     });
     RateLimiter::for('two-factor', fn (Request $request) =>
         Limit::perMinute(5)->by($request->session()->get('login.id'))
     );
     ```
   - Register actions:
     ```php
     Fortify::createUsersUsing(CreateNewUser::class);
     Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
     Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
     Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
     ```

6. Buat action classes di `app/Actions/Fortify/`:
   - `CreateNewUser` (validate name, email unique, password confirmed; create User dengan role 'kasir' default)
   - `UpdateUserProfileInformation` (validate name, email unique-ignore; update)
   - `UpdateUserPassword` (validate current_password, password confirmed; hash + update)
   - `ResetUserPassword` (validate password confirmed; force-fill)
   - `PasswordValidationRules` trait dengan rule: `['required', 'string', 'min:8', 'confirmed', Password::defaults()]`

7. Register provider di `bootstrap/providers.php` atau `config/app.php` (otomatis kalau publish service provider).

C. API CONTROLLER (untuk Sanctum)
8. Buat `app/Http/Controllers/Api/AuthController.php` dengan method:
   - `login(Request $request)`: validate email+password, cek user aktif, return token + UserResource
   - `logout(Request $request)`: revoke current token, return success
   - `me(Request $request)`: return UserResource untuk authenticated user
   - `deleteAccount(Request $request)`: anonymize + soft delete (Step 14 detail)

9. Buat `app/Http/Resources/UserResource.php`:
   ```php
   public function toArray($request): array {
       return [
           'id' => $this->id,
           'name' => $this->name,
           'email' => $this->email,
           'phone' => $this->phone,
           'roles' => $this->roles,
           'avatar_url' => $this->avatar_url,
       ];
   }
   ```

10. Buat helper `app/Http/Responses/ApiResponse.php`:
    ```php
    class ApiResponse {
        public static function success($data = null, $message = 'OK', $status = 200, $meta = []) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'meta' => $meta ?: null], $status);
        }
        public static function error($message, $status = 400, $errors = []) {
            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors ?: null], $status);
        }
    }
    ```

D. ROUTE
11. Edit `routes/api.php`:
    ```php
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('user', [AuthController::class, 'me']); // legacy compat
    });
    ```

12. Edit `routes/web.php`:
    ```php
    Route::get('/', function () {
        if (auth()->check()) return redirect()->route('home');
        return view('pages.auth.login');
    });
    Route::middleware(['auth', 'cache.headers:no_store;no_cache;must_revalidate;max_age=0'])->group(function () {
        Route::get('home', fn() => view('pages.dashboard'))->name('home');
    });
    ```

13. View auth placeholder (Step 04 yang final): buat `resources/views/pages/auth/login.blade.php` minimal:
    ```blade
    <!DOCTYPE html><html><body>
    <form method="POST" action="{{ route('login') }}">@csrf
        <input name="email" type="email" required>
        <input name="password" type="password" required>
        <button>Login</button>
    </form>
    </body></html>
    ```

14. Buat 1 user manual untuk test:
    php artisan tinker
    >>> User::create(['name' => 'Bahri', 'email' => 'bahri@fic11.com', 'password' => 'rahasia12345', 'roles' => 'owner'])

15. Test web login di browser → harus redirect ke /home setelah berhasil.
16. Test API: POST /api/login dengan body email+password → harus return token.

Tampilkan output `php artisan route:list --columns=method,uri,name` untuk verifikasi.
````

## Hasil yang Diharapkan

```
app/
├── Actions/Fortify/
│   ├── CreateNewUser.php
│   ├── PasswordValidationRules.php
│   ├── ResetUserPassword.php
│   ├── UpdateUserPassword.php
│   └── UpdateUserProfileInformation.php
├── Http/
│   ├── Controllers/Api/AuthController.php
│   ├── Resources/UserResource.php
│   └── Responses/ApiResponse.php
├── Models/User.php                  ← HasApiTokens trait added
└── Providers/FortifyServiceProvider.php  ← view + rate limiter + actions
config/fortify.php                    ← features + home
config/sanctum.php                    ← default
resources/views/pages/auth/login.blade.php  ← placeholder
routes/api.php                        ← login/logout/me
routes/web.php                        ← / + /home
```

## Cara Test

```bash
# 1. List route
php artisan route:list --columns=method,uri,name | grep -E "login|logout|me|home"

# 2. Test web login (browser)
php artisan serve
# Buka http://localhost:8000 → form login muncul
# Login dengan bahri@fic11.com / rahasia12345 → redirect ke /home

# 3. Test API login (curl atau Postman)
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"bahri@fic11.com","password":"rahasia12345"}'

# Expected: {"success":true,"message":"OK","data":{"user":{...},"token":"1|abc..."}}

# 4. Test API me (gunakan token dari step 3)
TOKEN="1|abc..." # ganti dengan token hasil login
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Expected: {"success":true,...,"data":{"id":1,"name":"Bahri",...}}

# 5. Test logout
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer $TOKEN"
# Token harus terhapus dari personal_access_tokens
```

## Penjelasan untuk Murid

Talking points:

1. **"Apa beda Fortify dan Sanctum?"**
   - Fortify: server-rendered (Blade), session-based, cookie. Untuk admin web.
   - Sanctum: API token, stateless, Bearer header. Untuk mobile/SPA.
   - Bisa hidup bersama: 1 user bisa login web (session) + punya banyak API token (mobile).

2. **"Kenapa view login custom, bukan default Fortify?"** — Fortify hanya logic. View kita design sendiri biar Bahasa Indonesia + brand POS. Step 04 nanti redesign jadi split-screen profesional.

3. **"Rate limiter login per minute 5 — kenapa segitu?"** — Anti brute-force. 5 percobaan per kombinasi email+IP per menit cukup ketat tapi tidak ganggu user lupa password.

4. **"Apa itu Action class di Fortify?"** — Single-responsibility class yang punya 1 method `__invoke()` atau `create()`. Lebih clean dari method panjang di controller. Easy to test.

5. **"Kenapa pakai `password => 'hashed'` cast?"** — Auto-hash saat assign. Sebelum Laravel 10 harus manual `Hash::make()`. Sekarang cukup `$user->password = $request->password` → otomatis hash.

6. **"ApiResponse helper kenapa perlu?"** — Konsistensi response shape. Tanpa helper, beda controller bisa return shape beda → FE susah. Dengan helper, semua endpoint return `{success, message, data, errors}`.

Pertanyaan reflektif:
- "Kalau Flutter app sudah login, lalu user logout dari web, token mobile harus ikut revoke?" (→ tergantung policy; kalau iya, di `logout` panggil `$user->tokens()->delete()`)
- "Bagaimana caranya menyetel token expire dalam 30 hari?" (→ `config/sanctum.php` `expiration => 60*24*30`)

---

**Next: [04-layout-sidebar.md](04-layout-sidebar.md)**
