<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (($_GET['panel'] ?? '') !== '1') {
    header('Location: ./#joining');
    exit;
}

$settings = get_settings();
$html = file_get_contents(__DIR__ . '/resources/joining-letter.html');
$boot = '<script>window.BCS_SETTINGS = ' . json_encode(
    $settings,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
) . ';</script>';

echo str_replace('</head>', $boot . '</head>', $html ?: '');
