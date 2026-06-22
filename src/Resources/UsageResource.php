<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Resources;

use Amane\BlogSdk\Http\HttpClient;

class UsageResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function get(): object
    {
        return (object) $this->http->get('/usage');
    }
}
