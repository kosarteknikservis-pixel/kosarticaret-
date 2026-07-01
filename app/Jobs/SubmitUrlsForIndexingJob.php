<?php

namespace App\Jobs;

use App\Services\Seo\UrlIndexingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitUrlsForIndexingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    /** @param  list<string>  $urls */
    public function __construct(public array $urls) {}

    public function handle(UrlIndexingNotifier $notifier): void
    {
        $notifier->submitNow($this->urls);
    }
}
