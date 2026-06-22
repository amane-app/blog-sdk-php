<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Resources;

use Amane\BlogSdk\Http\HttpClient;

class TopicSuggestionResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(array $params = []): object
    {
        return (object) $this->http->get('/topic-suggestions', $params);
    }

    public function approve(string $id): object
    {
        return (object) $this->http->post('/topic-suggestions/' . $id . '/approve');
    }

    public function reject(string $id, string $reason = ''): object
    {
        return (object) $this->http->post('/topic-suggestions/' . $id . '/reject', [
            'reason' => $reason,
        ]);
    }
}
