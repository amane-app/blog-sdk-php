<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

class AuthException extends AmaneApiException
{
    public function __construct(string $detail = 'Unauthorized')
    {
        parent::__construct(
            message: 'Authentication failed: ' . $detail,
            statusCode: 401,
            errorType: 'https://amane.app/errors/unauthorized',
            detail: $detail,
        );
    }
}
