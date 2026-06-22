<?php

declare(strict_types=1);

namespace Amane\BlogSdk;

use Amane\BlogSdk\Http\HttpClient;
use Amane\BlogSdk\Resources\ArticleResource;
use Amane\BlogSdk\Resources\KeywordResource;
use Amane\BlogSdk\Resources\TopicSuggestionResource;
use Amane\BlogSdk\Resources\UsageResource;

class AmaneClient
{
    private HttpClient $http;

    private ?ArticleResource $articlesResource = null;
    private ?TopicSuggestionResource $topicsResource = null;
    private ?KeywordResource $keywordsResource = null;
    private ?UsageResource $usageResource = null;

    public function __construct(string $baseUrl, string $token)
    {
        $this->http = new HttpClient($baseUrl, $token);
    }

    public static function make(string $baseUrl, string $token): self
    {
        return new self($baseUrl, $token);
    }

    public function articles(): ArticleResource
    {
        return $this->articlesResource ??= new ArticleResource($this->http);
    }

    public function topics(): TopicSuggestionResource
    {
        return $this->topicsResource ??= new TopicSuggestionResource($this->http);
    }

    public function keywords(): KeywordResource
    {
        return $this->keywordsResource ??= new KeywordResource($this->http);
    }

    public function usage(): UsageResource
    {
        return $this->usageResource ??= new UsageResource($this->http);
    }
}
