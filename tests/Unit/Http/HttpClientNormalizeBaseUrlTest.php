<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Tests\Unit\Http;

use Amane\BlogSdk\Http\HttpClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class HttpClientNormalizeBaseUrlTest extends TestCase
{
    /**
     * @dataProvider baseUrlProvider
     */
    public function testNormalizeBaseUrl(string $input, string $expected): void
    {
        $this->assertSame($expected, HttpClient::normalizeBaseUrl($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function baseUrlProvider(): array
    {
        return [
            'プレフィックス無し'          => ['https://service.amane.app', 'https://service.amane.app/api/v1/'],
            '末尾スラッシュ付き'          => ['https://service.amane.app/', 'https://service.amane.app/api/v1/'],
            'v1 明示'                    => ['https://service.amane.app/api/v1', 'https://service.amane.app/api/v1/'],
            'v1 明示 + 末尾スラッシュ'    => ['https://service.amane.app/api/v1/', 'https://service.amane.app/api/v1/'],
            'v2 は尊重する'              => ['https://service.amane.app/api/v2', 'https://service.amane.app/api/v2/'],
            '二桁バージョンも尊重'        => ['https://service.amane.app/api/v10', 'https://service.amane.app/api/v10/'],
            'サブパス込みは v1 付与'      => ['https://example.test/amane', 'https://example.test/amane/api/v1/'],
        ];
    }

    public function testConstructorBuildsAuthorizationHeaderFromToken(): void
    {
        $http = new HttpClient('https://service.amane.app', 'amb_secret_token');

        $clientProp = (new ReflectionClass($http))->getProperty('client');
        $clientProp->setAccessible(true);
        /** @var Client $guzzle */
        $guzzle = $clientProp->getValue($http);

        $headers = $guzzle->getConfig('headers');
        $this->assertSame('Bearer amb_secret_token', $headers['Authorization']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('application/json', $headers['Content-Type']);

        $this->assertSame(
            'https://service.amane.app/api/v1/',
            (string) $guzzle->getConfig('base_uri')
        );
    }
}
