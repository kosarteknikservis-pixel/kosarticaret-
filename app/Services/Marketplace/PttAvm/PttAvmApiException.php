<?php

namespace App\Services\Marketplace\PttAvm;

use RuntimeException;

class PttAvmApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message);
    }
}
