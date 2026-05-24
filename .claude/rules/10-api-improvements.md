# API Improvements

## Current State

Routes di `routes/api.php`:
- `POST /api/login`, `POST /api/logout`
- `GET /api/user`
- `apiResource /api/products`
- `apiResource /api/orders` + `/api/orders/kasir/{id}`
- `GET /api/list-categories`
- `GET /api/reports/{summary,product-sales,close-cashier}`

Controllers di `app/Http/Controllers/Api/`. Method seperti `index`, `store`, dll. masih ada yang **kosong** (placeholder dari `apiResource` scaffold — contoh `AuthController::index/store/show/update/destroy` semua `//`).

### Issues

1. **Response shape inconsistent** — `AuthController::login` return `['user' => ..., 'token' => ...]`, tapi controller lain mungkin return raw model. Tidak ada base structure (`data`, `meta`, `errors`).

2. **Error handling generic** — pakai `response([...], 404)` manual. Tidak ada exception handler yang map ke shape standar.

3. **No API resources** — return raw model = expose semua attribute (termasuk `email_verified_at`, `created_at` raw, dll.). Harusnya pakai `JsonResource` class.

4. **No validation FormRequest** — semua validasi inline `$request->validate(...)`.

5. **No pagination meta** — `index` endpoint kemungkinan return collection mentah, tanpa `links`/`meta`.

6. **No rate limiting per endpoint** — hanya pakai global throttle.

7. **No API docs** — tidak ada Scribe/Swagger/Postman collection.

8. **No versioning** — endpoint langsung `/api/...`, susah breaking change tanpa pecahkan FE.

## Target Improvements

### 1. Versioning + base response

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('categories', CategoryController::class)->only(['index']);
        
        Route::prefix('reports')->group(function () {
            Route::get('summary', [ReportController::class, 'summary']);
            Route::get('product-sales', [ReportController::class, 'productSales']);
            Route::get('close-cashier', [ReportController::class, 'closeCashier']);
        });
    });
});

// Backward-compat: keep old routes mapping to v1 controllers (deprecated)
// Atau force FE update — discussion dengan owner Flutter app.
```

### 2. Standardize response

Buat `App\Http\Responses\ApiResponse`:

```php
class ApiResponse
{
    public static function success($data = null, string $message = 'OK', int $status = 200, array $meta = [])
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta ?: null,
        ], $status);
    }
    
    public static function error(string $message, int $status = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null,
        ], $status);
    }
}
```

Usage:

```php
public function login(LoginRequest $request)
{
    $user = User::where('email', $request->email)->first();
    
    if (!$user || !Hash::check($request->password, $user->password)) {
        return ApiResponse::error(__('Email atau password salah'), 401);
    }
    
    $token = $user->createToken('mobile')->plainTextToken;
    
    return ApiResponse::success([
        'user' => new UserResource($user),
        'token' => $token,
        'token_type' => 'Bearer',
    ], 'Login berhasil');
}
```

### 3. JsonResource for every model

```php
// app/Http/Resources/UserResource.php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->roles,
            'avatar_url' => $this->avatar_url,
        ];
    }
}

// app/Http/Resources/ProductResource.php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (int) $this->price,
            'stock' => $this->stock,
            'is_best_seller' => (bool) $this->is_best_seller,
            'image_url' => $this->image ? asset('storage/products/' . $this->image) : null,
            'category' => new CategoryResource($this->whenLoaded('category_rel')),
        ];
    }
}

// app/Http/Resources/OrderResource.php
class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'transaction_time' => $this->transaction_time->toIso8601String(),
            'total_price' => (int) $this->total_price,
            'total_item' => $this->total_item,
            'subtotal' => (int) $this->subtotal,
            'discount' => (int) $this->discount,
            'tax' => (int) $this->tax,
            'amount_paid' => (int) $this->amount_paid,
            'change_amount' => (int) $this->change_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'notes' => $this->notes,
            'kasir' => new UserResource($this->whenLoaded('kasir')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
```

### 4. FormRequest classes

```php
// app/Http/Requests/Api/LoginRequest.php
class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'email.required' => __('Email wajib diisi'),
            'email.email' => __('Format email tidak valid'),
            'password.required' => __('Password wajib diisi'),
        ];
    }
}

// app/Http/Requests/Api/ProductStoreRequest.php
class ProductStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_best_seller' => ['boolean'],
        ];
    }
}

// app/Http/Requests/Api/OrderStoreRequest.php
class OrderStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'amount_paid' => ['required_if:payment_method,cash', 'integer', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

### 5. Global exception handler

Pindah dari `App\Exceptions\Handler` ke konsisten error shape untuk API:

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->renderable(function (ValidationException $e, $request) {
        if ($request->is('api/*')) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        }
    });
    
    $this->renderable(function (AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return ApiResponse::error('Unauthenticated', 401);
        }
    });
    
    $this->renderable(function (ModelNotFoundException $e, $request) {
        if ($request->is('api/*')) {
            return ApiResponse::error('Resource not found', 404);
        }
    });
    
    $this->renderable(function (ThrottleRequestsException $e, $request) {
        if ($request->is('api/*')) {
            return ApiResponse::error('Too many requests', 429);
        }
    });
}
```

### 6. Pagination response

Resource collection auto-build `links` & `meta`:

```php
// Controller
public function index(Request $request)
{
    $products = Product::query()
        ->when($request->q, fn($q, $term) => $q->where('name', 'like', "%$term%"))
        ->when($request->category_id, fn($q, $id) => $q->where('category_id', $id))
        ->with('category_rel')
        ->paginate($request->per_page ?? 15);
    
    return ProductResource::collection($products);
}

// Auto produces:
// { "data": [...], "links": {...}, "meta": {...} }
```

### 7. Rate limit per endpoint

```php
// app/Providers/RouteServiceProvider.php atau bootstrap/app.php
RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
RateLimiter::for('orders-write', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id));
```

Apply: `Route::post('orders', ...)->middleware('throttle:orders-write');`

### 8. API documentation

**Pilihan A: Scribe** (auto-generate dari docblock + FormRequest):

```bash
composer require --dev knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

Output di `public/docs/`. URL: `/docs`.

**Pilihan B: L5-Swagger** — Swagger UI yang lebih familiar tapi butuh anotasi manual.

**Rekomendasi: Scribe** (lebih sedikit ngoding).

### 9. Logging & monitoring

- Log semua request API ke `storage/logs/api.log` (channel terpisah)
- Track slow query (>1s) via Telescope (dev) atau custom listener (prod)
- API key untuk endpoint internal (kalau ada microservice lain konsumsi)

## Action Items

- [ ] Prefix `v1` untuk semua API endpoint (atau strategi backward-compat lain — diskusi dengan Flutter dev)
- [ ] Buat `App\Http\Responses\ApiResponse` helper
- [ ] Buat JsonResource untuk: User, Product, Category, Order, OrderItem
- [ ] Buat FormRequest untuk: Login, ProductStore/Update, OrderStore, CategoryStore/Update
- [ ] Hapus method placeholder kosong di `AuthController` (index/store/show/update/destroy)
- [ ] Update `Handler::register()` untuk error shape API yang konsisten
- [ ] Update semua controller API supaya return resource + ApiResponse
- [ ] Tambah rate limiter `login`, `orders-write`
- [ ] Install Scribe + generate docs
- [ ] Tambah Postman/Insomnia collection di `docs/`
- [ ] Sync dengan Flutter app (FE path: `/Users/bahri/development/fic11/flutter_pos_app`) untuk update model class & endpoint constants
