<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

class RateLimitException extends AmaneApiException
{
    /**
     * Retry-After ヘッダの秒数 (= 何秒待ってから再試行するべきか)。
     * v0.1 系で readonly だった public property の後方互換性のため、PHP 7.3 でも
     * 直接アクセスできる public 公開を維持する。
     *
     * @var int
     */
    public $retryAfter;

    public function __construct(int $retryAfter, string $detail = 'Too Many Requests')
    {
        parent::__construct(
            'Rate limit exceeded. Retry after ' . $retryAfter . ' seconds.',
            429,
            'https://amane.app/errors/rate-limit',
            $detail
        );
        $this->retryAfter = $retryAfter;
    }
}
