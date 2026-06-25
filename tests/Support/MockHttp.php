<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Support;

use Amane\BlogSdk\AmaneClient;
use Amane\BlogSdk\Http\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

/**
 * テスト用の Guzzle トランスポート差し替えヘルパー。
 *
 * 本番コードには手を入れず、reflection で HttpClient 内部の Guzzle
 * クライアントを MockHandler ベースのものに置き換える。送信された
 * リクエストは history ミドルウェアで記録するので、メソッド / URI /
 * body / ヘッダを後から検証できる。
 */
final class MockHttp
{
    /** @var array<int, array{request: RequestInterface, response: mixed}> */
    public $transactions = [];

    /** @var Client */
    private $guzzle;

    /** @var string */
    private $baseUrl;

    /**
     * @param array<int, Response|\Throwable> $responses 返却順に積むレスポンス
     */
    public function __construct(array $responses, string $baseUrl = 'https://service.amane.app')
    {
        $this->baseUrl = $baseUrl;

        $mock  = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->transactions));

        $this->guzzle = new Client([
            'handler'     => $stack,
            'base_uri'    => HttpClient::normalizeBaseUrl($baseUrl),
            'headers'     => [
                'Authorization' => 'Bearer test-token',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * 与えられた応答列を返す HttpClient を生成する。
     */
    public function httpClient(): HttpClient
    {
        $http = new HttpClient($this->baseUrl, 'test-token');
        $this->inject($http);

        return $http;
    }

    /**
     * 与えられた応答列を返す AmaneClient を生成する。
     */
    public function client(): AmaneClient
    {
        $client = new AmaneClient($this->baseUrl, 'test-token');

        $prop = (new ReflectionClass($client))->getProperty('http');
        $prop->setAccessible(true);
        /** @var HttpClient $http */
        $http = $prop->getValue($client);
        $this->inject($http);

        return $client;
    }

    /**
     * 既存の HttpClient へモックトランスポートを差し込む。
     */
    public function inject(HttpClient $http): void
    {
        $prop = (new ReflectionClass($http))->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($http, $this->guzzle);
    }

    /**
     * @return RequestInterface[]
     */
    public function requests(): array
    {
        $out = [];
        foreach ($this->transactions as $t) {
            $out[] = $t['request'];
        }

        return $out;
    }

    public function lastRequest(): ?RequestInterface
    {
        if ($this->transactions === []) {
            return null;
        }
        $last = end($this->transactions);

        return $last['request'];
    }

    /**
     * JSON レスポンスを組み立てるショートカット。
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public static function json(int $status, array $data = [], array $headers = []): Response
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        return new Response($status, $headers, json_encode($data));
    }

    /**
     * 本文を持たないレスポンス。
     *
     * @param array<string, string> $headers
     */
    public static function empty(int $status = 204, array $headers = []): Response
    {
        return new Response($status, $headers, '');
    }
}
