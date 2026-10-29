<?php

namespace App\Services\Marketplace\Hepsiburada;

use RuntimeException;

class HepsiburadaApiException extends RuntimeException
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
