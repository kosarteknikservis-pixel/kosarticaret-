<?php

namespace App\Services\Marketplace\Idefix;

use RuntimeException;

class IdefixApiException extends RuntimeException
{
    /** @param  array<string, mixed>|null  $response */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message, $status);
    }
}
