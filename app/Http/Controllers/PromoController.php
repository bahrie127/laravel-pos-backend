<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoRequest;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $query = Promo::query()->withCount('orders');

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $now = now();
            match ($request->status) {
                'live' => $query->live(),
                'inactive' => $query->where('active', false),
                'scheduled' => $query->where('active', true)
                    ->whereNotNull('starts_at')
                    ->where('starts_at', '>', $now),
                'expired' => $query->where('active', true)
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<', $now),
                default => null,
            };
        }

        $promos = $query->orderByDesc('active')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.promos.index', compact('promos'));
    }

    public function create()
    {
        $this->authorize('create', Promo::class);

        return view('pages.promos.create');
    }

    public function store(PromoRequest $request)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        $data['min_subtotal'] = $data['min_subtotal'] ?? 0;
        $data['code'] = $data['code'] ? strtoupper(trim($data['code'])) : null;

        Promo::create($data);

        return redirect()->route('promo.index')->with('success', 'Promo berhasil ditambahkan.');
    }

    public function edit(Promo $promo)
    {
        $this->authorize('update', $promo);

        return view('pages.promos.edit', compact('promo'));
    }

    public function update(PromoRequest $request, Promo $promo)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $data['min_subtotal'] = $data['min_subtotal'] ?? 0;
        $data['code'] = $data['code'] ? strtoupper(trim($data['code'])) : null;

        $promo->update($data);

        return redirect()->route('promo.index')->with('success', 'Promo berhasil diperbarui.');
    }

    public function toggle(Promo $promo)
    {
        $this->authorize('update', $promo);

        $promo->update(['active' => ! $promo->active]);

        return back()->with(
            'success',
            $promo->active ? "Promo '{$promo->name}' diaktifkan." : "Promo '{$promo->name}' dinonaktifkan."
        );
    }

    public function destroy(Promo $promo)
    {
        $this->authorize('delete', $promo);

        if ($promo->orders()->exists()) {
            return back()->with(
                'error',
                "Promo '{$promo->name}' tidak bisa dihapus karena sudah dipakai di transaksi. Nonaktifkan saja."
            );
        }

        $promo->delete();

        return redirect()->route('promo.index')->with('success', 'Promo berhasil dihapus.');
    }
}
