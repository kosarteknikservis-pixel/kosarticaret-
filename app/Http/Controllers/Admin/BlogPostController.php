<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\BlogPost;

use App\Support\ImageVariant;

use App\Support\RichContent;

use App\Support\SlugHelper;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

use Illuminate\View\View;



class BlogPostController extends Controller

{

    public function index(): View

    {

        return view('admin.blog.index', [

            'posts' => BlogPost::query()->orderByDesc('published_at')->paginate(20),

        ]);

    }



    public function create(): View

    {

        return view('admin.blog.form', ['post' => new BlogPost]);

    }



    public function store(Request $request): RedirectResponse

    {

        $data = $this->validated($request);

        $data = $this->mergeImage($request, $data);

        BlogPost::query()->create($data);



        return redirect()->route('admin.blog.index')->with('success', 'Yazı eklendi.');

    }



    public function edit(BlogPost $blog): View

    {

        return view('admin.blog.form', ['post' => $blog]);

    }



    public function update(Request $request, BlogPost $blog): RedirectResponse

    {

        $data = $this->validated($request, $blog);

        $data = $this->mergeImage($request, $data, $blog);

        $blog->update($data);



        return redirect()->route('admin.blog.index')->with('success', 'Yazı güncellendi.');

    }



    public function destroy(BlogPost $blog): RedirectResponse

    {

        if ($blog->image && ! str_starts_with($blog->image, 'http')) {

            ImageVariant::delete($blog->image);

            Storage::disk('public')->delete($blog->image);

        }

        $blog->delete();



        return redirect()->route('admin.blog.index')->with('success', 'Yazı silindi.');

    }



    private function validated(Request $request, ?BlogPost $post = null): array

    {

        $data = $request->validate([

            'title' => ['required', 'string', 'max:255'],

            'slug' => ['nullable', 'string', 'max:255'],

            'excerpt' => ['nullable', 'string', 'max:500'],

            'content' => ['nullable', 'string'],

            'image_alt' => ['nullable', 'string', 'max:255'],

            'published_at' => ['nullable', 'date'],

            'tags' => ['nullable', 'string'],

            'meta_title' => ['nullable', 'string'],

            'meta_description' => ['nullable', 'string'],

            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

        ]);



        $data['slug'] = SlugHelper::assign('blog_posts', $data['slug'] ?? null, $data['title'], $post?->id);

        $data['published'] = $request->boolean('published', true);

        $data['tags'] = $data['tags']

            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))

            : [];

        $data['content'] = RichContent::normalize($data['content'] ?? null);

        $data['excerpt'] = RichContent::normalize($data['excerpt'] ?? null);

        unset($data['image_file']);



        return $data;

    }



    /**

     * @param  array<string, mixed>  $data

     * @return array<string, mixed>

     */

    private function mergeImage(Request $request, array $data, ?BlogPost $post = null): array

    {

        if ($request->hasFile('image_file')) {

            if ($post?->image && ! str_starts_with($post->image, 'http')) {

                ImageVariant::delete($post->image);

                Storage::disk('public')->delete($post->image);

            }

            $data['image'] = $request->file('image_file')->store('blog', 'public');

            ImageVariant::generate($data['image'], ImageVariant::presetsFor('blog'));

        }



        return $data;

    }

}


