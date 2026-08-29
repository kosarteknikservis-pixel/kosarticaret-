<?php

namespace App\Support;

use App\Models\BlogPost;

final class BlogAuthor
{
    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        $author = config("blog_authors.authors.{$slug}");

        return is_array($author) ? self::normalize($slug, $author) : null;
    }

    /** @return array<string, mixed> */
    public static function forPost(BlogPost $post): array
    {
        $slug = (string) config('blog_authors.default', 'kosar-teknik-ekibi');

        return self::find($slug) ?? self::fallback($slug);
    }

    public static function url(string $slug): string
    {
        return route('authors.show', ['author' => $slug]);
    }

    /** @param  array<string, mixed>  $author */
    private static function normalize(string $slug, array $author): array
    {
        return [
            'slug' => $slug,
            'name' => (string) ($author['name'] ?? ''),
            'title' => trim((string) ($author['title'] ?? '')),
            'bio' => trim((string) ($author['bio'] ?? '')),
            'expertise' => array_values(array_filter((array) ($author['expertise'] ?? []))),
            'linkedin' => filled($author['linkedin'] ?? null) ? (string) $author['linkedin'] : null,
            'url' => self::url($slug),
        ];
    }

    /** @return array<string, mixed> */
    private static function fallback(string $slug): array
    {
        return [
            'slug' => $slug,
            'name' => SiteName::get(),
            'title' => '',
            'bio' => '',
            'expertise' => [],
            'linkedin' => null,
            'url' => self::url($slug),
        ];
    }
}
