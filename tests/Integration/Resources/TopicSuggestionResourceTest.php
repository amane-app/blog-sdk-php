<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Integration\Resources;

use Amane\BlogSdk\Resources\TopicSuggestionResource;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class TopicSuggestionResourceTest extends TestCase
{
    public function testListWithParams(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => [['title' => 'topic']]])]);
        $resource = new TopicSuggestionResource($mock->httpClient());

        $response = $resource->list(['status' => 'pending']);

        $this->assertSame([['title' => 'topic']], $response->data);
        $request = $mock->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/topic-suggestions', $request->getUri()->getPath());
        $this->assertStringContainsString('status=pending', $request->getUri()->getQuery());
    }

    public function testApprove(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['approved' => true])]);
        $resource = new TopicSuggestionResource($mock->httpClient());

        $response = $resource->approve('ts_1');

        $this->assertTrue($response->approved);
        $request = $mock->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/topic-suggestions/ts_1/approve', $request->getUri()->getPath());
    }

    public function testRejectWithDefaultReason(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['rejected' => true])]);
        $resource = new TopicSuggestionResource($mock->httpClient());

        $resource->reject('ts_1');

        $request = $mock->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/topic-suggestions/ts_1/reject', $request->getUri()->getPath());
        $this->assertSame(['reason' => ''], json_decode((string) $request->getBody(), true));
    }

    public function testRejectWithReason(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['rejected' => true])]);
        $resource = new TopicSuggestionResource($mock->httpClient());

        $resource->reject('ts_1', '内容が重複しているため');

        $body = json_decode((string) $mock->lastRequest()->getBody(), true);
        $this->assertSame('内容が重複しているため', $body['reason']);
    }
}
