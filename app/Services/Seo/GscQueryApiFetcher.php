<?php

namespace App\Services\Seo;

class GscQueryApiFetcher
{
    public function __construct(private GscSearchAnalyticsClient $client) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(int $days): array
    {
        return $this->client->fetchQueries($days);
    }
}
