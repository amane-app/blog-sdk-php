# テスト実行専用イメージ。
# pcov (カバレッジドライバ) と composer を同梱し、毎回の
# `pecl install` / composer ダウンロードを省略する。
FROM php:8.3-cli

# composer の dist 展開に必要な unzip / git、zip 拡張をインストール
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# カバレッジ計測用ドライバ
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# composer バイナリを公式イメージからコピー
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# composer のキャッシュ先 (compose で named volume をマウントする)
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

WORKDIR /app
