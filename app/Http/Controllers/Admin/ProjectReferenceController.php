<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectReference;
use App\Support\ImageVariant;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectReferenceController extends Controller
{
    public function index(): View
    {
        return view('admin.project-references.index', [
            'references' => ProjectReference::query()
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.project-references.form', ['reference' => new ProjectReference]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProjectReference::query()->create($this->validated($request));

        return redirect()->route('admin.project-references.index')->with('success', 'Referans eklendi.');
    }

    public function edit(ProjectReference $projectReference): View
    {
        return view('admin.project-references.form', ['reference' => $projectReference]);
    }

    public function update(Request $request, ProjectReference $projectReference): RedirectResponse
    {
        $projectReference->update($this->validated($request, $projectReference));

        return redirect()->route('admin.project-references.index')->with('success', 'Referans güncellendi.');
    }

    public function destroy(ProjectReference $projectReference): RedirectResponse
    {
        if ($projectReference->image && ! str_starts_with($projectReference->image, 'http')) {
            ImageVariant::delete($projectReference->image);
            Storage::disk('public')->delete($projectReference->image);
        }

        $projectReference->delete();

        return redirect()->route('admin.project-references.index')->with('success', 'Referans silindi.');
    }

    private function validated(Request $request, ?ProjectReference $reference = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:190'],
            'sector' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $data['slug'] = SlugHelper::assign('project_references', $data['slug'] ?? null, $data['title'], $reference?->id);
        $data['featured'] = $request->boolean('featured');
        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['body'] = RichContent::normalize($data['body'] ?? null);

        if ($request->boolean('remove_image') && $reference?->image) {
            if (! str_starts_with($reference->image, 'http')) {
                ImageVariant::delete($reference->image);
                Storage::disk('public')->delete($reference->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image_file')) {
            if ($reference?->image && ! str_starts_with($reference->image, 'http')) {
                ImageVariant::delete($reference->image);
                Storage::disk('public')->delete($reference->image);
            }
            $data['image'] = $request->file('image_file')->store('project-references', 'public');
            ImageVariant::generate($data['image'], ImageVariant::presetsFor('category'));
        }

        unset($data['image_file']);

        return $data;
    }
}
