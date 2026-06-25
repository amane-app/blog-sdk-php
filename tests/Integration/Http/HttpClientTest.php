<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Integration\Http;

use Amane\BlogSdk\Exceptions\AmaneApiException;
use Amane\BlogSdk\Exceptions\AuthException;
use Amane\BlogSdk\Exceptions\RateLimitException;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    public function testGetReturnsDecodedBody(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => ['id' => '1']])]);
        $http = $mock->httpClient();

        $result = $http->get('/articles');

        $this->assertSame(['data' => ['id' => '1']], $result);

        $request = $mock->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/articles', $request->getUri()->getPath());
        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
    }

    public function testGetAppendsQueryParameters(): void
    {
        $mock = new MockHttp([MockHttp::json(200, [])]);
        $http = $mock->httpClient();

        $http->get('/articles', ['status' => 'available', 'page' => 2]);

        $query = $mock->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('status=available', $query);
        $this->assertStringContainsString('page=2', $query);
    }

    public function testGetWithoutQueryHasEmptyQueryString(): void
    {
        $mock = new MockHttp([MockHttp::json(200, [])]);
        $http = $mock->httpClient();

        $http->get('/usage');

        $this->assertSame('', $mock->lastRequest()->getUri()->getQuery());
    }

    public function testPostSendsJsonBody(): void
    {
        $mock = new MockHttp([MockHttp::json(201, ['ok' => true])]);
        $http = $mock->httpClient();

        $result = $http->post('/keywords', ['keywords' => ['seo'], 'priority' => 'high']);

        $this->assertSame(['ok' => true], $result);

        $request = $mock->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            ['keywords' => ['seo'], 'priority' => 'high'],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testPutSendsJsonBody(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['updated' => true])]);
        $http = $mock->httpClient();

        $result = $http->put('/articles/1/publication', ['url' => 'https://x.test']);

        $this->assertSame(['updated' => true], $result);
        $this->assertSame('PUT', $mock->lastRequest()->getMethod());
        $this->assertSame(
            ['url' => 'https://x.test'],
            json_decode((string) $mock->lastRequest()->getBody(), true)
        );
    }

    public function testDeleteReturnsDecodedBody(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['deleted' => true])]);
        $http = $mock->httpClient();

        $result = $http->delete('/keywords/1');

        $this->assertSame(['deleted' => true], $result);
        $this->assertSame('DELETE', $mock->lastRequest()->getMethod());
    }

    public function testEmptyResponseBodyDecodesToEmptyArray(): void
    {
        $mock = new MockHttp([MockHttp::empty(204)]);
        $http = $mock->httpClient();

        $this->assertSame([], $http->delete('/keywords/1'));
    }

    public function testLeadingSlashIsStrippedFromPath(): void
    {
        $mock = new MockHttp([MockHttp::json(200, [])]);
        $http = $mock->httpClient();

        $http->get('///articles');

        $this->assertSame('/api/v1/articles', $mock->lastRequest()->getUri()->getPath());
    }

    public function testUnauthorizedThrowsAuthException(): void
    {
        $mock = new MockHttp([MockHttp::json(401, ['detail' => 'invalid token'])]);
        $http = $mock->httpClient();

        try {
            $http->get('/articles');
            $this->fail('AuthException が投げられるべき');
        } catch (AuthException $e) {
            $this->assertSame(401, $e->statusCode);
            $this->assertSame('invalid token', $e->detail);
        }
    }

    public function testRateLimitUsesRetryAfterHeader(): void
    {
        $mock = new MockHttp([
            MockHttp::json(429, ['detail' => 'slow down'], ['Retry-After' => '120']),
        ]);
        $http = $mock->httpClient();

        try {
            $http->get('/articles');
            $this->fail('RateLimitException が投げられるべき');
        } catch (RateLimitException $e) {
            $this->assertSame(120, $e->retryAfter);
            $this->assertSame('slow down', $e->detail);
        }
    }

    public function testRateLimitFallsBackToSixtySeconds(): void
    {
        $mock = new MockHttp([MockHttp::json(429, ['detail' => 'slow down'])]);
        $http = $mock->httpClient();

        try {
            $http->get('/articles');
            $this->fail('RateLimitException が投げられるべき');
        } catch (RateLimitException $e) {
            $this->assertSame(60, $e->retryAfter);
        }
    }

    public function testGenericErrorThrowsAmaneApiExceptionWithProblemDetails(): void
    {
        $mock = new MockHttp([
            MockHttp::json(404, [
                'type'   => 'https://amane.app/errors/not-found',
                'title'  => 'Not Found',
                'detail' => '記事が存在しません',
            ]),
        ]);
        $http = $mock->httpClient();

        try {
            $http->get('/articles/missing');
            $this->fail('AmaneApiException が投げられるべき');
        } catch (AmaneApiException $e) {
            $this->assertSame('Not Found', $e->getMessage());
            $this->assertSame(404, $e->statusCode);
            $this->assertSame('https://amane.app/errors/not-found', $e->errorType);
            $this->assertSame('記事が存在しません', $e->detail);
        }
    }

    public function testServerErrorWithoutBodyUsesDefaults(): void
    {
        $mock = new MockHttp([MockHttp::empty(500)]);
        $http = $mock->httpClient();

        try {
            $http->get('/articles');
            $this->fail('AmaneApiException が投げられるべき');
        } catch (AmaneApiException $e) {
            $this->assertSame('Error', $e->getMessage());
            $this->assertSame(500, $e->statusCode);
            $this->assertSame('about:blank', $e->errorType);
            $this->assertSame('', $e->detail);
        }
    }
}
