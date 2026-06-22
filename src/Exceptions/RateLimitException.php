<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

class RateLimitException extends AmaneApiException
{
    public function __construct(
        public readonly int $retryAfter,
        string $detail = 'Too Many Requests',
    ) {
        parent::__construct(
            message: 'Rate limit exceeded. Retry after ' . $retryAfter . ' seconds.',
            statusCode: 429,
            errorType: 'https://amane.app/errors/rate-limit',
            detail: $detail,
        );
    }
}
