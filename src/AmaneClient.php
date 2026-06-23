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
    /** @var HttpClient */
    private $http;

    /** @var ArticleResource|null */
    private $articlesResource = null;

    /** @var TopicSuggestionResource|null */
    private $topicsResource = null;

    /** @var KeywordResource|null */
    private $keywordsResource = null;

    /** @var UsageResource|null */
    private $usageResource = null;

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
        if ($this->articlesResource === null) {
            $this->articlesResource = new ArticleResource($this->http);
        }
        return $this->articlesResource;
    }

    public function topics(): TopicSuggestionResource
    {
        if ($this->topicsResource === null) {
            $this->topicsResource = new TopicSuggestionResource($this->http);
        }
        return $this->topicsResource;
    }

    public function keywords(): KeywordResource
    {
        if ($this->keywordsResource === null) {
            $this->keywordsResource = new KeywordResource($this->http);
        }
        return $this->keywordsResource;
    }

    public function usage(): UsageResource
    {
        if ($this->usageResource === null) {
            $this->usageResource = new UsageResource($this->http);
        }
        return $this->usageResource;
    }
}
