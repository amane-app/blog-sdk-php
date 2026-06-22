<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

use RuntimeException;

class AmaneApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $errorType,
        public readonly string $detail,
    ) {
        parent::__construct($message);
    }
}
