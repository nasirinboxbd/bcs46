<?php
declare(strict_types=1);

function site_branding(array $settings): array
{
    $saved = json_decode((string) ($settings['branding'] ?? ''), true);
    $defaults = [
        'site_title' => 'বিসিএস যোগদানের কাগজপত্র',
        'site_description' => 'বিসিএসে যোগদানের প্রয়োজনীয় কাগজপত্র তৈরি করুন, লাইভ প্রিভিউ দেখুন এবং Word বা PDF ডাউনলোড করুন।',
        'site_logo' => 'public/assets/favicon.svg',
        'share_title' => '',
        'share_description' => '',
        'share_image' => 'public/assets/thumbnail.png',
        'share_image_alt' => 'বিসিএস যোগদানের কাগজপত্র তৈরির ওয়েব অ্যাপের প্রিভিউ',
        'canonical_url' => '',
        'keywords' => 'বিসিএস যোগদান, অঙ্গীকারনামা, যোগদানপত্র',
        'author' => 'BCS Joining Documents',
        'theme_color' => '#087a55',
    ];
    return is_array($saved)
        ? array_replace($defaults, array_intersect_key($saved, $defaults))
        : $defaults;
}

function site_base_url(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^(?:[a-z0-9.-]+|\[[a-f0-9:]+\])(?::[0-9]+)?$/iD', $host)) {
        $host = 'localhost';
    }
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    return ($secure ? 'https://' : 'http://') . $host . $directory . '/';
}

function site_asset_url(string $asset, string $base): string
{
    return preg_match('~^https?://~i', $asset) ? $asset : $base . $asset;
}
