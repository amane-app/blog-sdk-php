<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Resources;

use Amane\BlogSdk\Http\HttpClient;

class ArticleResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    public function list(array $params = []): object
    {
        return (object) $this->http->get('/articles', $params);
    }

    public function get(string $id): object
    {
        return (object) $this->http->get('/articles/' . $id);
    }

    /**
     * 記事の公開を報告する。
     *
     * API は body に `published_url` (必須) / `published_at` (必須) を期待する。
     * 顧客 CMS で編集した実タイトル等があれば actualTitle / actualMetaDescription /
     * deviationNotes も送れる (= 配信ダッシュボードに実態を反映するため)。
     */
    public function reportPublication(
        string $id,
        string $url,
        ?string $publishedAt = null,
        ?string $canonicalUrl = null,
        ?string $actualTitle = null,
        ?string $actualMetaDescription = null,
        ?string $deviationNotes = null
    ): object {
        $body = ['published_url' => $url];

        if ($publishedAt !== null) {
            $body['published_at'] = $publishedAt;
        }

        if ($canonicalUrl !== null) {
            $body['canonical_url'] = $canonicalUrl;
        }

        if ($actualTitle !== null) {
            $body['actual_title'] = $actualTitle;
        }

        if ($actualMetaDescription !== null) {
            $body['actual_meta_description'] = $actualMetaDescription;
        }

        if ($deviationNotes !== null) {
            $body['deviation_notes'] = $deviationNotes;
        }

        return (object) $this->http->post('/articles/' . $id . '/publication', $body);
    }

    public function updatePublication(string $id, array $data): object
    {
        return (object) $this->http->put('/articles/' . $id . '/publication', $data);
    }

    public function markUnpublished(string $id): object
    {
        return (object) $this->http->delete('/articles/' . $id . '/publication');
    }

    public function performance(string $id): object
    {
        return (object) $this->http->get('/articles/' . $id . '/performance');
    }
}
