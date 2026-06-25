<?php

declare(strict_types=1);

/**
 * Clover カバレッジレポートを読み、閾値を下回っていたら exit 1 する。
 *
 * PHPUnit 9 には「カバレッジが N% 未満なら失敗」という機能が無いため、
 * CI のカバレッジゲートとしてこのスクリプトを使う。
 *
 * Usage:
 *   php bin/coverage-check.php build/logs/clover.xml 90
 */

$cloverFile = isset($argv[1]) ? $argv[1] : 'build/logs/clover.xml';
$threshold  = isset($argv[2]) ? (float) $argv[2] : 90.0;

if (!is_file($cloverFile)) {
    fwrite(STDERR, "Clover レポートが見つかりません: {$cloverFile}\n");
    exit(1);
}

$xml = simplexml_load_file($cloverFile);
if ($xml === false || !isset($xml->project->metrics)) {
    fwrite(STDERR, "Clover レポートを解析できません: {$cloverFile}\n");
    exit(1);
}

$metrics  = $xml->project->metrics;
$elements = (int) $metrics['elements'];
$covered  = (int) $metrics['coveredelements'];

if ($elements === 0) {
    fwrite(STDERR, "カバレッジ対象の要素が見つかりません。\n");
    exit(1);
}

$coverage = $covered / $elements * 100;

printf("Coverage: %.2f%% (%d / %d elements)\n", $coverage, $covered, $elements);

if ($coverage + 1e-9 < $threshold) {
    fwrite(STDERR, sprintf(
        "FAIL: カバレッジ %.2f%% は閾値 %.2f%% を下回っています。\n",
        $coverage,
        $threshold
    ));
    exit(1);
}

printf("PASS: カバレッジは閾値 %.2f%% を満たしています。\n", $threshold);
exit(0);
