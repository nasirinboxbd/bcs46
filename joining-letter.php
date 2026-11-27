<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/branding.php';

if (($_GET['panel'] ?? '') !== '1') {
    header('Location: ./#joining');
    exit;
}

$settings = get_settings();
$branding = site_branding($settings);
$html = file_get_contents(__DIR__ . '/resources/joining-letter.html');
$html = str_replace('<html lang="bn">', '<html lang="bn" data-theme="' . h($branding['active_theme']) . '" data-color-mode="' . h($branding['color_mode']) . '">', $html ?: '');
$html = str_replace('</head>', '<link rel="stylesheet" href="public/assets/site-themes.css"><script src="public/assets/site-theme-sync.js" defer></script></head>', $html);
$boot = '<script>window.BCS_SETTINGS = ' . json_encode(
    $settings,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
) . ';</script>';

echo str_replace('</head>', $boot . '</head>', $html ?: '');
