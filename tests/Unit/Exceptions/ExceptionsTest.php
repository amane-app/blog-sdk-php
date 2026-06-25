<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Unit\Exceptions;

use Amane\BlogSdk\Exceptions\AmaneApiException;
use Amane\BlogSdk\Exceptions\AuthException;
use Amane\BlogSdk\Exceptions\RateLimitException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionsTest extends TestCase
{
    public function testAmaneApiExceptionExposesAllFields(): void
    {
        $e = new AmaneApiException('Not Found', 404, 'https://amane.app/errors/not-found', '記事が見つかりません');

        $this->assertInstanceOf(RuntimeException::class, $e);
        $this->assertSame('Not Found', $e->getMessage());
        $this->assertSame(404, $e->statusCode);
        $this->assertSame('https://amane.app/errors/not-found', $e->errorType);
        $this->assertSame('記事が見つかりません', $e->detail);
    }

    public function testAuthExceptionDefaults(): void
    {
        $e = new AuthException();

        $this->assertInstanceOf(AmaneApiException::class, $e);
        $this->assertSame(401, $e->statusCode);
        $this->assertSame('https://amane.app/errors/unauthorized', $e->errorType);
        $this->assertSame('Unauthorized', $e->detail);
        $this->assertStringContainsString('Unauthorized', $e->getMessage());
    }

    public function testAuthExceptionWithCustomDetail(): void
    {
        $e = new AuthException('トークンが無効です');

        $this->assertSame('トークンが無効です', $e->detail);
        $this->assertStringContainsString('トークンが無効です', $e->getMessage());
    }

    public function testRateLimitExceptionDefaults(): void
    {
        $e = new RateLimitException(30);

        $this->assertInstanceOf(AmaneApiException::class, $e);
        $this->assertSame(429, $e->statusCode);
        $this->assertSame('https://amane.app/errors/rate-limit', $e->errorType);
        $this->assertSame(30, $e->retryAfter);
        $this->assertSame('Too Many Requests', $e->detail);
        $this->assertStringContainsString('30', $e->getMessage());
    }

    public function testRateLimitExceptionWithCustomDetail(): void
    {
        $e = new RateLimitException(90, 'リクエストが多すぎます');

        $this->assertSame(90, $e->retryAfter);
        $this->assertSame('リクエストが多すぎます', $e->detail);
        $this->assertStringContainsString('90', $e->getMessage());
    }
}
