<?php

declare(strict_types=1);

namespace Amane\BlogSdk\Exceptions;

use RuntimeException;

class AmaneApiException extends RuntimeException
{
    /**
     * HTTP ステータスコード (例: 400, 404, 500)。
     * v0.1 系で readonly だった public property の後方互換性のため、PHP 7.3 でも
     * 直接アクセスできる public 公開を維持する。書き換えはしないこと。
     *
     * @var int
     */
    public $statusCode;

    /**
     * RFC 7807 type URI (例: https://amane.app/errors/unauthorized)。
     *
     * @var string
     */
    public $errorType;

    /**
     * 詳細メッセージ (= RFC 7807 detail フィールド)。
     *
     * @var string
     */
    public $detail;

    public function __construct(string $message, int $statusCode, string $errorType, string $detail)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorType = $errorType;
        $this->detail = $detail;
    }
}
