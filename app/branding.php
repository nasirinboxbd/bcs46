<?php
declare(strict_types=1);

function site_branding(array $settings): array
{
    $saved = json_decode((string) ($settings['branding'] ?? ''), true);
    $defaults = [
        'site_title' => 'বিসিএস যোগদানপত্র ও অঙ্গীকারনামা জেনারেটর',
        'site_description' => 'বিসিএস যোগদানপত্র ও অঙ্গীকারনামা তৈরি করুন। তথ্য পূরণ করে প্রিভিউ দেখুন এবং Word বা PDF হিসেবে ডাউনলোড করুন।',
        'site_logo' => 'public/assets/favicon.svg',
        'share_title' => 'বিসিএস যোগদানপত্র ও অঙ্গীকারনামা তৈরি করুন',
        'share_description' => 'প্রয়োজনীয় তথ্য দিয়ে নথির খসড়া তৈরি করুন, প্রিভিউ দেখুন এবং Word বা PDF হিসেবে ডাউনলোড করুন।',
        'share_image' => 'public/assets/thumbnail.png',
        'share_image_alt' => 'বিসিএস যোগদানের কাগজপত্র তৈরির ওয়েব অ্যাপের প্রিভিউ',
        'canonical_url' => '',
        'keywords' => 'বিসিএস যোগদানপত্র, বিসিএস অঙ্গীকারনামা, BCS joining letter, BCS declaration, যোগদানের কাগজপত্র',
        'author' => 'BCS Joining Documents',
        'theme_color' => '#087a55',
        'enabled_themes' => ['emerald', 'ocean', 'violet', 'sunset'],
        'active_theme' => 'emerald',
        'color_mode' => 'light',
    ];
    $branding = is_array($saved)
        ? array_replace($defaults, array_intersect_key($saved, $defaults))
        : $defaults;
    foreach (['share_title', 'share_description'] as $key) {
        if (trim((string) $branding[$key]) === '') {
            $branding[$key] = $defaults[$key];
        }
    }
    $themeIds = array_keys(site_theme_catalog());
    $enabled = is_array($branding['enabled_themes'])
        ? array_values(array_unique(array_intersect($themeIds, $branding['enabled_themes'])))
        : [];
    if (!$enabled) {
        $enabled = $defaults['enabled_themes'];
    }
    $branding['enabled_themes'] = $enabled;
    if (!in_array($branding['active_theme'], $enabled, true)) {
        $branding['active_theme'] = $enabled[0];
    }
    if (!in_array($branding['color_mode'], ['light', 'dark'], true)) {
        $branding['color_mode'] = 'light';
    }
    return $branding;
}

function site_theme_catalog(): array
{
    return [
        'emerald' => 'এমারেল্ড',
        'ocean' => 'ওশান',
        'violet' => 'ভায়োলেট',
        'sunset' => 'সানসেট',
    ];
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
