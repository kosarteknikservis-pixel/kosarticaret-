<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugHelper
{
    /** @var array<string, string> */
    private const TABLES = [
        'products' => 'products',
        'categories' => 'categories',
        'brands' => 'brands',
        'blog_posts' => 'blog_posts',
        'pages' => 'pages',
    ];

    public static function table(string $entity): string
    {
        return self::TABLES[$entity] ?? $entity;
    }

    /**
     * Kayıt için benzersiz slug üretir. Boş slug ise kaynak metinden türetir.
     */
    public static function assign(string $entity, ?string $slug, string $source, ?int $excludeId = null): string
    {
        $base = filled(trim((string) $slug))
            ? Str::slug($slug)
            : Str::slug($source);

        if ($base === '') {
            $base = 'item';
        }

        return self::unique($entity, $base, $excludeId);
    }

    public static function unique(string $entity, string $baseSlug, ?int $excludeId = null): string
    {
        $table = self::table($entity);
        $slug = $baseSlug;
        $suffix = 2;

        while (self::exists($table, $slug, $excludeId)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function exists(string $table, string $slug, ?int $excludeId): bool
    {
        $query = DB::table($table)->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
