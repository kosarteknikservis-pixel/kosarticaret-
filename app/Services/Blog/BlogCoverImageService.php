<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Support\BlogCoverImageGenerator;
use App\Support\ImageVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogCoverImageService
{
    public function __construct(
        private BlogCoverImageGenerator $generator,
    ) {}

    public function assign(BlogPost $post, bool $force = false): bool
    {
        if (filled($post->image) && ! $force) {
            return false;
        }

        $path = $this->generator->generate($post);
        if ($path === null) {
            return false;
        }

        $post->update([
            'image' => $path,
            'image_alt' => $this->resolveAltText($post),
        ]);

        return true;
    }

    public function remove(BlogPost $post): bool
    {
        if (blank($post->image)) {
            return false;
        }

        $path = (string) $post->image;
        if (! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            ImageVariant::delete($path);
            Storage::disk('public')->delete($path);
        }

        $post->update(['image' => null]);

        return true;
    }

    public function previewTitle(BlogPost $post): string
    {
        return trim((string) $post->title);
    }

    private function resolveAltText(BlogPost $post): string
    {
        if (filled($post->image_alt)) {
            return (string) $post->image_alt;
        }

        return Str::limit(trim((string) $post->title).' — Koşar blog kapak görseli', 255, '');
    }

    /** @return Collection<int, BlogPost> */
    public function postsNeedingCover(): Collection
    {
        return BlogPost::query()
            ->published()
            ->where(function ($q): void {
                $q->whereNull('image')->orWhere('image', '');
            })
            ->orderBy('published_at')
            ->get();
    }
}
