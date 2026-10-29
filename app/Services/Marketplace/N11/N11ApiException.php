<?php

namespace App\Services\Marketplace\N11;

use RuntimeException;

class N11ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message, $code);
    }
}
