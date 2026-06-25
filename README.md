# AMANE Blog Distribution — PHP SDK

[![CI](https://github.com/amane-app/blog-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/amane-app/blog-sdk-php/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/amane/blog-sdk.svg)](https://packagist.org/packages/amane/blog-sdk)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

公式 PHP SDK for the [AMANE Blog Distribution API](https://amane.app).

AMANE が AI で自動生成した SEO 記事を、顧客の corporate サイトや blog サイトに pull して公開できます。

## 必要要件

- PHP **7.3 以上** (= 7.3 / 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4 で動作)
- Composer
- Guzzle 7.x (= 自動でインストールされます)

## インストール

```bash
composer require amane/blog-sdk
```

## 使い方

### クライアント初期化

```php
use Amane\BlogSdk\AmaneClient;

$client = new AmaneClient(
    'https://service.amane.app',          // baseUrl (= /api/v1 は SDK が自動付与)
    'amb_xxxxxxxxxxxxx'                   // API token (AMANE 管理画面で発行)
);
```

> **baseUrl について**: `https://service.amane.app` でも `https://service.amane.app/api/v1`
> でもどちらでも動きます。SDK 内部で `/api/v1/` プレフィックスを自動付与するため、
> 末尾スラッシュの有無も気にする必要はありません。

### 配信可能な記事一覧を取得

```php
$response = $client->articles()->list();
foreach ($response->data as $article) {
    echo $article['title'] . "\n";
}
```

### 記事詳細を取得 (= 呼ぶと delivered ステータスに自動遷移)

```php
$response = $client->articles()->get('01HF3ABC...');
$article = $response->data;
echo $article['content_html'];      // HTML 本文
echo $article['content_markdown'];  // Markdown 本文
```

### 公開報告 (= 顧客サイトで公開した時に呼ぶ)

```php
$client->articles()->reportPublication(
    '01HF3ABC...',
    'https://customer.example.com/blog/article-slug',
    date('c'),                                          // published_at (省略可)
    'https://customer.example.com/blog/article-slug'    // canonical_url (省略可)
);
```

### 公開先 URL の更新 (= 公開後に URL が変わった場合)

```php
$client->articles()->updatePublication('01HF3ABC...', [
    'url' => 'https://customer.example.com/new-slug',
]);
```

### 非公開化 (= 取り下げ)

```php
$client->articles()->markUnpublished('01HF3ABC...');
```

### 効果計測結果取得 (= 公開後 14 日以降)

```php
$response = $client->articles()->performance('01HF3ABC...');
$perf = $response->data;
echo "verdict: {$perf['verdict']}\n";
echo "position improvement: {$perf['delta']['position_improvement']}\n";
```

### キーワード管理

```php
// 一覧
$response = $client->keywords()->list();

// 追加 (= keywords は配列で複数渡せる。priority は省略時 'medium')
$client->keywords()->add(['名古屋 システム開発'], 'high');

// 削除
$client->keywords()->remove('kw_xxxxxxxx');
```

### トピック提案

```php
$response = $client->topics()->list();
foreach ($response->data as $topic) {
    echo $topic['title'] . "\n";
}
```

### 使用量取得

```php
$response = $client->usage()->get();
echo "今月の配信数: {$response->data['delivered_count']}\n";
echo "残数: {$response->data['remaining']}\n";
```

## エラーハンドリング

```php
use Amane\BlogSdk\Exceptions\AuthException;
use Amane\BlogSdk\Exceptions\RateLimitException;
use Amane\BlogSdk\Exceptions\AmaneApiException;

try {
    $response = $client->articles()->list();
} catch (AuthException $e) {
    // 401: トークン無効・失効 → 再発行を案内
    error_log('AMANE 認証失敗: ' . $e->getMessage());
} catch (RateLimitException $e) {
    // 429: レート制限超過 → Retry-After 秒数尊重
    sleep($e->retryAfter);
} catch (AmaneApiException $e) {
    // その他の API エラー (4xx/5xx)
    error_log('AMANE API エラー: ' . $e->getMessage());
}
```

## API トークンの発行

1. AMANE 管理画面 (https://service.amane.app) にログイン
2. Site 詳細 → 「📝 ブログ配信」タブ
3. 「🔑 API トークン管理」→ 新規発行 (= 表示は 1 度だけ、コピーしておく)

トークンの形式: `amb_` プレフィックス + 48 桁の hex

## .env 設定の注意点

`.env` を Windows エディタで編集すると CRLF (`\r\n`) で保存される場合があり、
`AMANE_API_TOKEN` の値に `\r` が混入して Authorization ヘッダが壊れ、nginx が 400 を返すことがあります。

**対策**:
- `.env` を保存する前にエディタの改行コードを LF に設定
- アプリケーション側で `trim($_ENV['AMANE_API_TOKEN'])` を入れて防御

## 開発・テスト

テストは PHPUnit で 3 つのスイート (Unit / Integration / E2E) に分かれています。

### Docker で実行 (= ローカルに PHP 不要・推奨)

pcov と composer を同梱したテスト用イメージを [docker-compose.yml](docker-compose.yml) /
[docker/Dockerfile](docker/Dockerfile) で用意しています。

```bash
docker compose build test                              # 初回のみ (イメージ作成)
docker compose run --rm test composer install          # 初回 / 依存更新時

docker compose run --rm test composer run test:unit         # 単体テスト
docker compose run --rm test composer run test:integration  # 結合テスト
docker compose run --rm test composer run test:e2e          # E2E テスト
docker compose run --rm test composer run test              # 全スイートまとめて

docker compose run --rm test composer run test:coverage     # カバレッジ計測
docker compose run --rm test composer run coverage:check    # 90% 未満なら exit 1
```

`vendor/` はホストにマウントされるため `composer install` は基本 1 回でよく、
カバレッジ HTML は `build/coverage/index.html` に出力されます。

### ローカルに PHP がある場合

```bash
composer install
composer run test            # 全スイート
composer run test:coverage   # カバレッジ計測 (要 pcov / xdebug)
composer run coverage:check  # カバレッジが 90% 未満なら exit 1
```

各スイートの内容:

- **Unit**: 純粋ロジック (`normalizeBaseUrl` / 認証ヘッダ構築) ・例外・リソースの遅延生成
- **Integration**: `HttpClient` の各 verb とエラーマッピング、各 Resource のメソッドを Guzzle モック越しに検証
- **E2E**: `AmaneClient` から Guzzle まで貫通する一連の操作 (記事取得 → 公開報告 → 更新 → 取り下げ 等)

GitHub Actions ([.github/workflows/ci.yml](.github/workflows/ci.yml)) で、`main` への push /
pull request 時に PHP 7.3〜8.4 のマトリクスで全スイートを実行し、カバレッジ 90% 以上を強制します。

> **E2E の実 API スモークテスト**: `AMANE_E2E_BASE_URL` と `AMANE_E2E_TOKEN` を環境変数に
> 設定すると、実 API への読み取り専用スモークテストも実行されます (未設定時はスキップ)。

## 関連リンク

- [API 仕様 (OpenAPI 3.0)](https://service.amane.app/api/v1/docs)
- [JavaScript/TypeScript SDK](https://github.com/amane-app/blog-sdk-js)
- [WordPress プラグイン](https://github.com/amane-app/blog-distribution-wp)
- [プロダクトサイト](https://amane.app)

## 変更履歴

### v0.1.2 (2026-06-25)
- **fix**: `HttpClient::normalizeBaseUrl()` を追加。baseUrl に `/api/v1` プレフィックスが無くても
  SDK 内部で自動付与するように改善。これまで `https://service.amane.app` を渡すと SaaS の
  SPA index.html を 200 で受けて空レスポンスになる事故が起きていたため。後方互換あり
  (= 既に `/api/v1` を含めて渡しているコードはそのまま動く)
- **docs**: README のサンプルを PHP 7.3 互換構文に統一。実在しないメソッド名 (`fetch` /
  `publication()->report()` 等) を正しいメソッド名に修正

### v0.1.1 (2026-06-23)
- **feat**: PHP 7.3 互換性を追加 (旧 8.1 要求から拡大)

### v0.1.0 (2026-06-22)
- **feat**: initial release

## ライセンス

MIT License — see [LICENSE](LICENSE)

Copyright (c) 2026 Transonic Software Corporation
