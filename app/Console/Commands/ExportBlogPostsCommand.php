<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExportBlogPostsCommand extends Command
{
    protected $signature = 'blog:export
                            {--path=database/blog-export.json : Oluşturulacak JSON dosyası}
                            {--slug=* : Sadece belirtilen slug(lar)}
                            {--published-only : Sadece yayındaki blog yazıları}';

    protected $description = 'Blog yazılarını canlıya taşımak için JSON olarak dışa aktarır';

    public function handle(): int
    {
        $path = base_path($this->option('path'));
        $slugs = array_filter((array) $this->option('slug'));

        $query = BlogPost::query()->orderBy('id');

        if ($slugs !== []) {
            $query->whereIn('slug', $slugs);
        }

        if ($this->option('published-only')) {
            $query->where('published', true);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->warn('Dışa aktarılacak blog yazısı bulunamadı.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($path));

        $payload = [
            'type' => 'kosar-blog-export',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'count' => $posts->count(),
            'posts' => $posts->map(fn (BlogPost $post) => $this->serializePost($post))->values(),
        ];

        File::put($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $this->info("Blog export hazır: {$path}");
        $this->line("Yazı sayısı: {$posts->count()}");

        return self::SUCCESS;
    }

    private function serializePost(BlogPost $post): array
    {
        return [
            'slug' => $post->getRawOriginal('slug'),
            'title' => $post->getRawOriginal('title'),
            'excerpt' => $post->getRawOriginal('excerpt'),
            'content' => $post->getRawOriginal('content'),
            'image' => $post->getRawOriginal('image'),
            'image_alt' => $post->getRawOriginal('image_alt'),
            'published_at' => optional($post->published_at)->toDateTimeString(),
            'published' => (bool) $post->published,
            'meta_title' => $post->getRawOriginal('meta_title'),
            'meta_description' => $post->getRawOriginal('meta_description'),
            'tags' => $post->tags ?? [],
            'translations' => $post->translations ?? [],
            'created_at' => optional($post->created_at)->toDateTimeString(),
            'updated_at' => optional($post->updated_at)->toDateTimeString(),
            'image_file' => $this->serializeImage($post->getRawOriginal('image')),
        ];
    }

    private function serializeImage(?string $image): ?array
    {
        if (! $image || str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return null;
        }

        if (! Storage::disk('public')->exists($image)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($image);

        return [
            'path' => $image,
            'mime' => File::mimeType($absolutePath),
            'base64' => base64_encode(File::get($absolutePath)),
        ];
    }
}
