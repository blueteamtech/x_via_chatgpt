<?php

namespace App\Exceptions;

use RuntimeException;

class XApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $status);
    }
}
