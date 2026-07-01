<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\Seo\UrlIndexingNotifier;

class BlogPostObserver
{
    public function __construct(private UrlIndexingNotifier $indexing) {}

    public function saved(BlogPost $post): void
    {
        if (! $post->published) {
            return;
        }

        if ($post->published_at !== null && $post->published_at->isFuture()) {
            return;
        }

        $this->indexing->submit([
            route('blog.show', $post, absolute: true),
        ]);
    }
}
