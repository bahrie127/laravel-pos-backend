<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PromoStoreRequest;
use App\Http\Resources\PromoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * List all promos (filterable). Cashier app pulls this at bootstrap
     * for offline-first lookups.
     */
    public function index(Request $request)
    {
        $query = Promo::query();
        if ($request->boolean('active_only')) {
            $query->active();
        }
        if ($request->boolean('live_only')) {
            $query->live();
        }
        $promos = $query->orderBy('active', 'desc')
            ->orderBy('starts_at', 'desc')
            ->get();

        return ApiResponse::success(
            PromoResource::collection($promos),
            'List promo berhasil dimuat.'
        );
    }

    public function show(Promo $promo)
    {
        return ApiResponse::success(new PromoResource($promo), 'Detail promo.');
    }

    public function store(PromoStoreRequest $request)
    {
        // Hanya admin/owner yang boleh CRUD promo. Kasir READ-ONLY.
        if (! $request->user()->can('create', Promo::class)) {
            return ApiResponse::error('Anda tidak memiliki izin membuat promo.', 403);
        }

        $promo = Promo::create($request->validated());
        return ApiResponse::success(
            new PromoResource($promo),
            'Promo berhasil dibuat.',
            201
        );
    }

    public function update(PromoStoreRequest $request, Promo $promo)
    {
        if (! $request->user()->can('update', $promo)) {
            return ApiResponse::error('Anda tidak memiliki izin mengubah promo.', 403);
        }

        $promo->update($request->validated());
        return ApiResponse::success(
            new PromoResource($promo->fresh()),
            'Promo diperbarui.'
        );
    }

    public function toggle(Request $request, Promo $promo)
    {
        if (! $request->user()->can('update', $promo)) {
            return ApiResponse::error('Anda tidak memiliki izin mengubah promo.', 403);
        }

        $promo->update(['active' => ! $promo->active]);
        return ApiResponse::success(
            new PromoResource($promo->fresh()),
            $promo->active ? 'Promo diaktifkan.' : 'Promo dinonaktifkan.'
        );
    }

    public function destroy(Request $request, Promo $promo)
    {
        if (! $request->user()->can('delete', $promo)) {
            return ApiResponse::error('Anda tidak memiliki izin menghapus promo.', 403);
        }

        if ($promo->orders()->exists()) {
            return ApiResponse::error('Promo tidak bisa dihapus karena sudah dipakai pada pesanan.', 422);
        }

        $promo->delete();
        return ApiResponse::success(null, 'Promo dihapus.');
    }

    /**
     * Validate a voucher code against the current cart subtotal.
     * Returns the promo + computed discount on success, 404 if no match,
     * 422 if inactive/expired/under minimum.
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $promo = Promo::byCode($validated['code'])->first();
        if (! $promo) {
            return ApiResponse::error('Kode voucher tidak ditemukan.', 404);
        }
        if (! $promo->isLive()) {
            return ApiResponse::error('Voucher sudah tidak berlaku.', 422);
        }
        if ($validated['subtotal'] < $promo->min_subtotal) {
            return ApiResponse::error(
                'Minimum belanja Rp' . number_format($promo->min_subtotal, 0, ',', '.'),
                422
            );
        }

        $discount = $promo->computeDiscount($validated['subtotal']);

        return ApiResponse::success([
            'promo' => new PromoResource($promo),
            'discount_amount' => $discount,
        ], 'Voucher diterima.');
    }
}
