<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavigationController extends Controller
{
    public function index(): View
    {
        return view('admin.navigation.index', [
            'items' => NavigationItem::query()->orderBy('location')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.navigation.form', ['item' => new NavigationItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        NavigationItem::query()->create($this->validated($request));

        return redirect()->route('admin.navigation.index')->with('success', 'Menü linki eklendi.');
    }

    public function edit(NavigationItem $navigation): View
    {
        return view('admin.navigation.form', ['item' => $navigation]);
    }

    public function update(Request $request, NavigationItem $navigation): RedirectResponse
    {
        $navigation->update($this->validated($request));

        return redirect()->route('admin.navigation.index')->with('success', 'Menü linki güncellendi.');
    }

    public function destroy(NavigationItem $navigation): RedirectResponse
    {
        $navigation->delete();

        return redirect()->route('admin.navigation.index')->with('success', 'Menü linki silindi.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
            'location' => ['required', 'in:header,footer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['active'] = $request->boolean('active', true);
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
