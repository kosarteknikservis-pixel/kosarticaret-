<?php

namespace App\Services\Marketplace\Trendyol;

use RuntimeException;

class TrendyolApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message);
    }
}
