<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Integration\Resources;

use Amane\BlogSdk\Resources\UsageResource;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class UsageResourceTest extends TestCase
{
    public function testGet(): void
    {
        $mock = new MockHttp([
            MockHttp::json(200, ['data' => ['articles_used' => 12, 'limit' => 50]]),
        ]);
        $resource = new UsageResource($mock->httpClient());

        $response = $resource->get();

        $this->assertSame(['articles_used' => 12, 'limit' => 50], $response->data);
        $request = $mock->lastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/usage', $request->getUri()->getPath());
    }
}
