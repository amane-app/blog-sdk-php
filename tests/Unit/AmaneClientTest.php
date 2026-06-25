<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Unit;

use Amane\BlogSdk\AmaneClient;
use Amane\BlogSdk\Resources\ArticleResource;
use Amane\BlogSdk\Resources\KeywordResource;
use Amane\BlogSdk\Resources\TopicSuggestionResource;
use Amane\BlogSdk\Resources\UsageResource;
use PHPUnit\Framework\TestCase;

final class AmaneClientTest extends TestCase
{
    public function testConstructorAcceptsBaseUrlAndToken(): void
    {
        $client = new AmaneClient('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(AmaneClient::class, $client);
    }

    public function testMakeReturnsInstance(): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(AmaneClient::class, $client);
    }

    public function testArticlesReturnsArticleResource(): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(ArticleResource::class, $client->articles());
    }

    public function testTopicsReturnsTopicSuggestionResource(): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(TopicSuggestionResource::class, $client->topics());
    }

    public function testKeywordsReturnsKeywordResource(): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(KeywordResource::class, $client->keywords());
    }

    public function testUsageReturnsUsageResource(): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');
        $this->assertInstanceOf(UsageResource::class, $client->usage());
    }

    /**
     * 各リソースは遅延生成され、二度目以降は同一インスタンスを返す。
     *
     * @dataProvider resourceAccessorProvider
     */
    public function testResourcesAreLazilyCached(string $accessor): void
    {
        $client = AmaneClient::make('https://service.amane.app', 'amb_token');

        $first  = $client->{$accessor}();
        $second = $client->{$accessor}();

        $this->assertSame($first, $second, "{$accessor}() は同じインスタンスを返すべき");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function resourceAccessorProvider(): array
    {
        return [
            'articles' => ['articles'],
            'topics'   => ['topics'],
            'keywords' => ['keywords'],
            'usage'    => ['usage'],
        ];
    }
}
