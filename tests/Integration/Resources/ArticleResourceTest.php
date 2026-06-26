<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Integration\Resources;

use Amane\BlogSdk\Resources\ArticleResource;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class ArticleResourceTest extends TestCase
{
    public function testListReturnsObjectAndSendsQuery(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => [['title' => 'A']]])]);
        $resource = new ArticleResource($mock->httpClient());

        $response = $resource->list(['status' => 'available']);

        $this->assertIsObject($response);
        $this->assertSame([['title' => 'A']], $response->data);

        $request = $mock->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/articles', $request->getUri()->getPath());
        $this->assertStringContainsString('status=available', $request->getUri()->getQuery());
    }

    public function testGetFetchesSingleArticle(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => ['id' => '01HF']])]);
        $resource = new ArticleResource($mock->httpClient());

        $response = $resource->get('01HF');

        $this->assertSame(['id' => '01HF'], $response->data);
        $this->assertSame('/api/v1/articles/01HF', $mock->lastRequest()->getUri()->getPath());
    }

    public function testReportPublicationWithRequiredFieldsOnly(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['ok' => true])]);
        $resource = new ArticleResource($mock->httpClient());

        $resource->reportPublication('01HF', 'https://blog.test/post');

        $request = $mock->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $request->getUri()->getPath());
        $this->assertSame(
            ['published_url' => 'https://blog.test/post'],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testReportPublicationWithOptionalFields(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['ok' => true])]);
        $resource = new ArticleResource($mock->httpClient());

        $resource->reportPublication(
            '01HF',
            'https://blog.test/post',
            '2026-06-25T10:00:00Z',
            'https://blog.test/canonical',
            'CMS で直したタイトル',
            'CMS で直した meta',
            '見出しを 1 つ追加'
        );

        $body = json_decode((string) $mock->lastRequest()->getBody(), true);
        $this->assertSame('https://blog.test/post', $body['published_url']);
        $this->assertSame('2026-06-25T10:00:00Z', $body['published_at']);
        $this->assertSame('https://blog.test/canonical', $body['canonical_url']);
        $this->assertSame('CMS で直したタイトル', $body['actual_title']);
        $this->assertSame('CMS で直した meta', $body['actual_meta_description']);
        $this->assertSame('見出しを 1 つ追加', $body['deviation_notes']);
    }

    public function testUpdatePublication(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['updated' => true])]);
        $resource = new ArticleResource($mock->httpClient());

        $response = $resource->updatePublication('01HF', ['url' => 'https://blog.test/new']);

        $this->assertTrue($response->updated);
        $request = $mock->lastRequest();
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $request->getUri()->getPath());
        $this->assertSame(
            ['url' => 'https://blog.test/new'],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testMarkUnpublished(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['unpublished' => true])]);
        $resource = new ArticleResource($mock->httpClient());

        $response = $resource->markUnpublished('01HF');

        $this->assertTrue($response->unpublished);
        $request = $mock->lastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $request->getUri()->getPath());
    }

    public function testPerformance(): void
    {
        $mock = new MockHttp([MockHttp::json(200, ['data' => ['clicks' => 10]])]);
        $resource = new ArticleResource($mock->httpClient());

        $response = $resource->performance('01HF');

        $this->assertSame(['clicks' => 10], $response->data);
        $this->assertSame('/api/v1/articles/01HF/performance', $mock->lastRequest()->getUri()->getPath());
    }
}
