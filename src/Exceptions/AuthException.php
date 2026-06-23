<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

class AuthException extends AmaneApiException
{
    public function __construct(string $detail = 'Unauthorized')
    {
        parent::__construct(
            'Authentication failed: ' . $detail,
            401,
            'https://amane.app/errors/unauthorized',
            $detail
        );
    }
}
