<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Resources;

use Amane\BlogSdk\Http\HttpClient;

class ArticleResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(array $params = []): object
    {
        return (object) $this->http->get('/articles', $params);
    }

    public function get(string $id): object
    {
        return (object) $this->http->get('/articles/' . $id);
    }

    public function reportPublication(
        string $id,
        string $url,
        ?string $publishedAt = null,
        ?string $canonicalUrl = null,
    ): object {
        $body = ['url' => $url];

        if ($publishedAt !== null) {
            $body['published_at'] = $publishedAt;
        }

        if ($canonicalUrl !== null) {
            $body['canonical_url'] = $canonicalUrl;
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
