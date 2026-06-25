<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Integration\Resources;

use Amane\BlogSdk\Resources\KeywordResource;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class KeywordResourceTest extends TestCase
{
    public function testList(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => [['keyword' => 'seo']]])]);
        $resource = new KeywordResource($mock->httpClient());

        $response = $resource->list();

        $this->assertSame([['keyword' => 'seo']], $response->data);
        $request = $mock->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/keywords', $request->getUri()->getPath());
    }

    public function testAddWithDefaultPriority(): void
    {
        $mock = new MockHttp([MockHttp::json(201, ['ok' => true])]);
        $resource = new KeywordResource($mock->httpClient());

        $resource->add(['seo対策', 'コンテンツ']);

        $request = $mock->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/keywords', $request->getUri()->getPath());
        $this->assertSame(
            ['keywords' => ['seo対策', 'コンテンツ'], 'priority' => 'medium'],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testAddWithExplicitPriority(): void
    {
        $mock = new MockHttp([MockHttp::json(201, ['ok' => true])]);
        $resource = new KeywordResource($mock->httpClient());

        $resource->add(['seo'], 'high');

        $body = json_decode((string) $mock->lastRequest()->getBody(), true);
        $this->assertSame('high', $body['priority']);
    }

    public function testRemove(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['deleted' => true])]);
        $resource = new KeywordResource($mock->httpClient());

        $response = $resource->remove('kw_1');

        $this->assertTrue($response->deleted);
        $request = $mock->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/keywords/kw_1', $request->getUri()->getPath());
    }
}
