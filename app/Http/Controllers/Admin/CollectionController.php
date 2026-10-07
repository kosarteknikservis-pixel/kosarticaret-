<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Services\CollectionMatcher;
use App\Services\CollectionPageData;
use App\Support\SlugHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('admin.collections.index', [
            'collections' => Collection::query()
                ->with('category')
                ->withCount('visibleProducts')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.collections.form', [
            'collection' => new Collection(['status' => Collection::STATUS_DRAFT, 'rules' => []]),
            'categories' => $this->categories(),
            'preview' => null,
        ]);
    }

    public function store(Request $request, CollectionMatcher $matcher): RedirectResponse
    {
        $collection = Collection::query()->create($this->validated($request));
        $matcher->syncAll();

        return redirect()
            ->route('admin.collections.edit', $collection)
            ->with('success', 'Etiket taslak olarak eklendi.');
    }

    public function edit(Collection $collection, CollectionMatcher $matcher): View
    {
        return view('admin.collections.form', [
            'collection' => $collection,
            'categories' => $this->categories(),
            'preview' => $matcher->preview($collection),
        ]);
    }

    public function update(Request $request, Collection $collection, CollectionMatcher $matcher): RedirectResponse
    {
        $data = $this->validated($request, $collection);
        $collection->update($data);
        $matcher->syncAll();
        $collection->refresh();

        if ($collection->status === Collection::STATUS_INDEX && $collection->visibleProducts()->count() < Collection::MIN_PRODUCTS) {
            $collection->update(['status' => Collection::STATUS_DRAFT]);

            return back()
                ->withErrors(['status' => 'Index için en az '.Collection::MIN_PRODUCTS.' uygun ürün gerekir. Durum taslakta bırakıldı.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.collections.edit', $collection)
            ->with('success', 'Etiket güncellendi.');
    }

    public function preview(Request $request, Collection $collection, CollectionPageData $pages): Response
    {
        return response()
            ->view('shop.collections.show', $pages->data($request, $collection, true))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function membership(Request $request, Collection $collection, CollectionMatcher $matcher): RedirectResponse
    {
        $data = $request->validate([
            'product_ref' => ['required', 'string', 'max:120'],
            'action' => ['required', 'in:include,exclude,clear'],
        ]);

        $ref = trim($data['product_ref']);
        $product = Product::query()
            ->where('sku', $ref)
            ->when(ctype_digit($ref), fn ($query) => $query->orWhere('id', (int) $ref))
            ->first();

        if ($product === null) {
            return back()->withErrors(['product_ref' => 'Ürün bulunamadı. Stok kodu veya ürün numarası yazın.']);
        }

        if ($data['action'] === 'clear') {
            $matcher->clearOverride($collection, $product);
        } else {
            $matcher->setOverride($collection, $product, $data['action']);
        }

        return back()->with('success', 'Üyelik güncellendi.');
    }

    /** @return \Illuminate\Support\Collection<int, Category> */
    private function categories()
    {
        return Category::query()->orderBy('name')->get();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Collection $collection = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'target_keyword' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:draft,noindex,index'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'motor_hp' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'phase' => ['nullable', 'in:monofaze,trifaze'],
        ]);

        $rules = [];
        if ($request->filled('motor_hp')) {
            $rules['motor_hp'] = (float) $data['motor_hp'];
        }
        if ($request->filled('phase')) {
            $rules['phase'] = $data['phase'];
        }
        if ($rules === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'motor_hp' => 'Güç veya faz kuralından biri gerekir.',
            ]);
        }

        $data['slug'] = SlugHelper::assign('collections', $data['slug'] ?? null, $data['name'], $collection?->id);
        $data['rules'] = $rules;
        $data['status'] = $data['status'] ?: Collection::STATUS_DRAFT;
        unset($data['motor_hp'], $data['phase']);

        return $data;
    }
}
