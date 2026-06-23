# AMANE Blog Distribution — PHP SDK

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
    apiUrl: 'https://service.amane.app',
    apiToken: 'amb_xxxxxxxxxxxxx',  // AMANE 管理画面で発行
);
```

### 配信可能な記事一覧を取得

```php
$articles = $client->articles()->list();
foreach ($articles['data'] as $article) {
    echo $article['title'] . "\n";
}
```

### 記事詳細を取得 (= delivered ステータスに遷移)

```php
$article = $client->articles()->fetch('art_01HF3ABC...');
echo $article['content_html'];      // HTML 本文
echo $article['content_markdown'];  // Markdown 本文
```

### 公開報告 (= 顧客サイトで公開した時に呼ぶ)

```php
$client->publication()->report(
    articleId: 'art_01HF3ABC...',
    publishedUrl: 'https://customer.example.com/blog/article-slug',
    publishedAt: new DateTimeImmutable(),
);
```

### 効果計測結果取得 (= 公開後 14 日以降)

```php
$perf = $client->performance()->get('art_01HF3ABC...');
echo "verdict: {$perf['data']['verdict']}\n";
echo "position improvement: {$perf['data']['delta']['position_improvement']}\n";
```

## API トークンの発行

1. AMANE 管理画面 (https://service.amane.app) にログイン
2. Site 詳細 → 「📝 ブログ配信」タブ
3. API トークン発行 (= 表示は 1 度だけ、コピーしておく)

トークンの形式: `amb_` プレフィックス + 48 桁の hex

## 関連リンク

- [API 仕様 (OpenAPI 3.0)](https://service.amane.app/api/v1/docs)
- [JavaScript/TypeScript SDK](https://github.com/amane-app/blog-sdk-js)
- [WordPress プラグイン](https://github.com/amane-app/blog-distribution-wp)
- [プロダクトサイト](https://amane.app)

## ライセンス

MIT License — see [LICENSE](LICENSE)

Copyright (c) 2026 Transonic Software Corporation
