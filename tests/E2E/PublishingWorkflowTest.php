<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\E2E;

use Amane\BlogSdk\AmaneClient;
use Amane\BlogSdk\Exceptions\AuthException;
use Amane\BlogSdk\Exceptions\RateLimitException;
use Amane\BlogSdk\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

/**
 * AmaneClient ファサードから HttpClient / Guzzle まで貫通させる E2E テスト。
 *
 * 実 API への依存を避けるため、AMANE API が返す形のレスポンスを順に
 * モックサーバ (Guzzle MockHandler) で再現し、SDK 利用者が実際に行う
 * 一連の操作 (記事取得 → 公開報告 → 公開更新 → 公開取り下げ →
 * パフォーマンス確認 → キーワード / トピック / 使用量) を通しで検証する。
 */
final class PublishingWorkflowTest extends TestCase
{
    public function testFullPublishingLifecycle(): void
    {
        $mock = new MockHttp([
            // 1. 配信可能な記事一覧
            MockHttp::json(200, ['data' => [['id' => '01HF', 'title' => 'SEO 記事']]]),
            // 2. 記事詳細 (= delivered へ遷移)
            MockHttp::json(200, ['data' => [
                'id'               => '01HF',
                'content_html'     => '<h1>記事</h1>',
                'content_markdown' => '# 記事',
            ]]),
            // 3. 公開報告
            MockHttp::json(201, ['data' => ['status' => 'published']]),
            // 4. 公開情報の更新
            MockHttp::json(200, ['data' => ['status' => 'published', 'url' => 'https://blog.test/v2']]),
            // 5. パフォーマンス
            MockHttp::json(200, ['data' => ['clicks' => 42, 'impressions' => 1000]]),
            // 6. 公開取り下げ
            MockHttp::json(200, ['data' => ['status' => 'unpublished']]),
        ]);

        $client = $mock->client();

        $list = $client->articles()->list(['status' => 'available']);
        $this->assertSame('01HF', $list->data[0]['id']);

        $article = $client->articles()->get('01HF');
        $this->assertSame('<h1>記事</h1>', $article->data['content_html']);

        $published = $client->articles()->reportPublication('01HF', 'https://blog.test/post', '2026-06-25T09:00:00Z');
        $this->assertSame('published', $published->data['status']);

        $updated = $client->articles()->updatePublication('01HF', ['url' => 'https://blog.test/v2']);
        $this->assertSame('https://blog.test/v2', $updated->data['url']);

        $performance = $client->articles()->performance('01HF');
        $this->assertSame(42, $performance->data['clicks']);

        $unpublished = $client->articles()->markUnpublished('01HF');
        $this->assertSame('unpublished', $unpublished->data['status']);

        // 全リクエストが正しいメソッド / パスで送信されたことを検証
        $requests = $mock->requests();
        $this->assertCount(6, $requests);

        $this->assertSame('GET', $requests[0]->getMethod());
        $this->assertSame('/api/v1/articles', $requests[0]->getUri()->getPath());

        $this->assertSame('GET', $requests[1]->getMethod());
        $this->assertSame('/api/v1/articles/01HF', $requests[1]->getUri()->getPath());

        $this->assertSame('POST', $requests[2]->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $requests[2]->getUri()->getPath());

        $this->assertSame('PUT', $requests[3]->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $requests[3]->getUri()->getPath());

        $this->assertSame('GET', $requests[4]->getMethod());
        $this->assertSame('/api/v1/articles/01HF/performance', $requests[4]->getUri()->getPath());

        $this->assertSame('DELETE', $requests[5]->getMethod());
        $this->assertSame('/api/v1/articles/01HF/publication', $requests[5]->getUri()->getPath());

        // 認証ヘッダが全リクエストに付与されていること
        foreach ($requests as $request) {
            $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
        }
    }

    public function testKeywordAndTopicAndUsageWorkflow(): void
    {
        $mock = new MockHttp([
            MockHttp::json(200, ['data' => []]),
            MockHttp::json(201, ['data' => ['added' => 2]]),
            MockHttp::json(200, ['data' => [['id' => 'ts_1', 'title' => 'topic']]]),
            MockHttp::json(200, ['data' => ['status' => 'approved']]),
            MockHttp::json(200, ['data' => ['status' => 'rejected']]),
            MockHttp::json(200, ['data' => ['articles_used' => 3, 'limit' => 50]]),
        ]);

        $client = $mock->client();

        $client->keywords()->list();
        $client->keywords()->add(['seo', 'php'], 'high');
        $client->topics()->list(['status' => 'pending']);
        $client->topics()->approve('ts_1');
        $client->topics()->reject('ts_2', '重複');
        $usage = $client->usage()->get();

        $this->assertSame(3, $usage->data['articles_used']);

        $requests = $mock->requests();
        $this->assertSame('/api/v1/keywords', $requests[0]->getUri()->getPath());
        $this->assertSame('/api/v1/keywords', $requests[1]->getUri()->getPath());
        $this->assertSame('/api/v1/topic-suggestions', $requests[2]->getUri()->getPath());
        $this->assertSame('/api/v1/topic-suggestions/ts_1/approve', $requests[3]->getUri()->getPath());
        $this->assertSame('/api/v1/topic-suggestions/ts_2/reject', $requests[4]->getUri()->getPath());
        $this->assertSame('/api/v1/usage', $requests[5]->getUri()->getPath());
    }

    public function testAuthFailurePropagatesThroughFacade(): void
    {
        $mock = new MockHttp([MockHttp::json(401, ['detail' => 'token expired'])]);
        $client = $mock->client();

        $this->expectException(AuthException::class);
        $client->articles()->list();
    }

    public function testRateLimitPropagatesThroughFacade(): void
    {
        $mock = new MockHttp([
            MockHttp::json(429, ['detail' => 'too many'], ['Retry-After' => '15']),
        ]);
        $client = $mock->client();

        try {
            $client->usage()->get();
            $this->fail('RateLimitException が投げられるべき');
        } catch (RateLimitException $e) {
            $this->assertSame(15, $e->retryAfter);
        }
    }

    /**
     * 環境変数が設定されている場合のみ、実 API に対する読み取り専用の
     * スモークテストを実行する。CI のデフォルト実行ではスキップされる。
     */
    public function testLiveUsageSmoke(): void
    {
        $baseUrl = getenv('AMANE_E2E_BASE_URL');
        $token   = getenv('AMANE_E2E_TOKEN');

        if ($baseUrl === false || $baseUrl === '' || $token === false || $token === '') {
            $this->markTestSkipped('AMANE_E2E_BASE_URL / AMANE_E2E_TOKEN 未設定のためスキップ');
        }

        $client = AmaneClient::make($baseUrl, $token);
        $usage  = $client->usage()->get();

        $this->assertIsObject($usage);
        $this->assertTrue(property_exists($usage, 'data'));
    }
}
