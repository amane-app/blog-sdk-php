<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Resources;

use Amane\BlogSdk\Http\HttpClient;

class KeywordResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(): object
    {
        return (object) $this->http->get('/keywords');
    }

    public function add(array $keywords, string $priority = 'medium'): object
    {
        return (object) $this->http->post('/keywords', [
            'keywords' => $keywords,
            'priority' => $priority,
        ]);
    }

    public function remove(string $id): object
    {
        return (object) $this->http->delete('/keywords/' . $id);
    }
}
