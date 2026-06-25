<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Http;

use Amane\BlogSdk\Exceptions\AmaneApiException;
use Amane\BlogSdk\Exceptions\AuthException;
use Amane\BlogSdk\Exceptions\RateLimitException;
use GuzzleHttp\Client;

class HttpClient
{
    /** @var Client */
    private $client;

    public function __construct(string $baseUrl, string $token)
    {
        $this->client = new Client([
            'base_uri' => self::normalizeBaseUrl($baseUrl),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * baseUrl から base_uri を組み立てる。
     *
     * AMANE の SaaS API は `/api/v1/` 配下にあるので、SDK 利用者が
     *   - `https://service.amane.app`        (= プレフィックス無し)
     *   - `https://service.amane.app/api/v1` (= プレフィックス有り)
     * のどちらを渡しても動くように吸収する。`/api/v2` 等の将来の API
     * バージョンが含まれていればそのまま尊重 (= 将来互換)。
     *
     * @internal
     */
    public static function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        if (!preg_match('#/api/v\d+$#', $baseUrl)) {
            $baseUrl .= '/api/v1';
        }
        return $baseUrl . '/';
    }

    public function get(string $path, array $query = []): array
    {
        $options = $query ? ['query' => $query] : [];
        $response = $this->client->get(ltrim($path, '/'), $options);
        return $this->parse($response);
    }

    public function post(string $path, array $body = []): array
    {
        $response = $this->client->post(ltrim($path, '/'), ['json' => $body]);
        return $this->parse($response);
    }

    public function put(string $path, array $body = []): array
    {
        $response = $this->client->put(ltrim($path, '/'), ['json' => $body]);
        return $this->parse($response);
    }

    public function delete(string $path): array
    {
        $response = $this->client->delete(ltrim($path, '/'));
        return $this->parse($response);
    }

    private function parse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();
        $data   = $body !== '' ? (array) json_decode($body, true) : [];

        if ($status >= 200 && $status < 300) {
            return $data;
        }

        // null coalescing operator (??) は PHP 7.0+ で利用可
        $type   = (string) ($data['type']   ?? 'about:blank');
        $title  = (string) ($data['title']  ?? 'Error');
        $detail = (string) ($data['detail'] ?? $body);

        if ($status === 401) {
            throw new AuthException($detail);
        }

        if ($status === 429) {
            $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: 60);
            throw new RateLimitException($retryAfter, $detail);
        }

        throw new AmaneApiException($title, $status, $type, $detail);
    }
}
