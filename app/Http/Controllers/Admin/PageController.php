<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page]);
    }

    public function store(Request $request): RedirectResponse
    {
        Page::query()->create($this->validated($request));

        return redirect()->route('admin.pages.index')->with('success', 'Sayfa eklendi.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.index')->with('success', 'Sayfa güncellendi.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Sayfa silindi.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['slug'] = SlugHelper::assign('pages', $data['slug'] ?? null, $data['title'], $page?->id);
        $data['published'] = $request->boolean('published', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['content'] = RichContent::normalize($data['content'] ?? null);

        return $data;
    }
}
