<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions.index', [
            'promotions' => Promotion::query()->orderByDesc('priority')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', ['promotion' => new Promotion]);
    }

    public function store(Request $request): RedirectResponse
    {
        Promotion::query()->create($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('success', 'Kampanya eklendi.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.form', ['promotion' => $promotion]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('success', 'Kampanya güncellendi.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', 'Kampanya silindi.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:percent,fixed,free_shipping,buy_x_get_y'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'buy_qty' => ['nullable', 'integer', 'min:1'],
            'free_qty' => ['nullable', 'integer', 'min:1'],
            'min_cart' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['active'] = $request->boolean('active', true);
        $data['auto_apply'] = $request->boolean('auto_apply', true);
        $data['priority'] = $data['priority'] ?? 0;

        return $data;
    }
}
