<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/branding.php';

$settings = get_settings();
$branding = site_branding($settings);
$configDeveloper = app_config()['developer'] ?? [];
$developer = [
    'name' => $settings['developer_name'] ?: ($configDeveloper['name'] ?? 'Md. Nasir Uddin'),
    'subtitle' => $settings['developer_subtitle'] ?: ($configDeveloper['subtitle'] ?? 'ওয়েব অ্যাপ ডেভেলপার'),
    'workplace' => $settings['developer_workplace'] ?: ($configDeveloper['workplace'] ?? ''),
    'bcs46' => $settings['developer_bcs46'] ?: ($configDeveloper['bcs46'] ?? ''),
    'bcs47' => $settings['developer_bcs47'] ?: ($configDeveloper['bcs47'] ?? ''),
    'facebook_url' => $settings['developer_facebook'] ?: ($configDeveloper['facebook_url'] ?? ''),
    'image' => $settings['developer_image'] ?: ($configDeveloper['image'] ?? 'public/assets/developer.jpeg'),
];
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
if (($_GET['panel'] ?? '') === 'declaration') {
    $html = file_get_contents(__DIR__ . '/resources/document-generator.html');
    $html = str_replace('<!-- site-branding -->', '<title>অঙ্গীকারনামা | ' . h($branding['site_title']) . '</title><meta name="robots" content="noindex,follow">', $html ?: '');
    $boot = '<script>window.BCS_SETTINGS = ' . json_encode($settings, $jsonFlags) . ';</script>';
    echo str_replace('</head>', $boot . '</head>', $html);
    exit;
}

$baseUrl = site_base_url();
$canonical = $branding['canonical_url'] ?: $baseUrl;
$shareTitle = $branding['share_title'] ?: $branding['site_title'];
$shareDescription = $branding['share_description'] ?: $branding['site_description'];
$shareImage = site_asset_url($branding['share_image'], $baseUrl);
$tabs = ['declaration' => 'অঙ্গীকারনামা', 'joining' => 'যোগদানপত্র'];
if (admin_logged_in()) {
    $tabs['admin'] = 'এডমিন প্যানেল';
}
$sources = ['declaration' => '?panel=declaration', 'joining' => 'joining-letter?panel=1', 'admin' => 'admin?panel=1'];
?><!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($branding['site_title']) ?></title>
  <meta name="description" content="<?= h($branding['site_description']) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <meta name="keywords" content="<?= h($branding['keywords']) ?>">
  <meta name="author" content="<?= h($branding['author']) ?>">
  <meta name="theme-color" content="<?= h($branding['theme_color']) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <link rel="icon" href="<?= h($branding['site_logo']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="bn_BD">
  <meta property="og:site_name" content="<?= h($branding['site_title']) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:title" content="<?= h($shareTitle) ?>">
  <meta property="og:description" content="<?= h($shareDescription) ?>">
  <meta property="og:image" content="<?= h($shareImage) ?>">
  <meta property="og:image:alt" content="<?= h($branding['share_image_alt']) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($shareTitle) ?>">
  <meta name="twitter:description" content="<?= h($shareDescription) ?>">
  <meta name="twitter:image" content="<?= h($shareImage) ?>">
  <meta name="twitter:image:alt" content="<?= h($branding['share_image_alt']) ?>">
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'WebApplication',
      'name' => $branding['site_title'],
      'description' => $branding['site_description'],
      'url' => $canonical,
      'applicationCategory' => 'BusinessApplication',
      'operatingSystem' => 'Any',
      'inLanguage' => 'bn-BD',
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <link rel="stylesheet" href="public/assets/navigation.css">
  <link rel="stylesheet" href="public/assets/workspace.css">
  <script src="public/assets/navigation.js" defer></script>
</head>
<body class="has-app-sidebar document-shell">
  <button type="button" class="app-sidebar-toggle" aria-label="সাইডবার খুলুন" aria-controls="workspace-sidebar" aria-expanded="false">☰</button>
  <div class="app-sidebar__overlay"></div>
  <aside class="app-sidebar" id="workspace-sidebar" aria-label="সাইডবার">
    <div class="app-sidebar__brand">
      <img class="app-sidebar__mark" src="<?= h($branding['site_logo']) ?>" alt="" width="36" height="36">
      <span><?= h($branding['site_title']) ?></span>
      <button type="button" class="app-sidebar__close" aria-label="সাইডবার বন্ধ করুন">×</button>
    </div>
    <div class="workspace-tabs" role="tablist" aria-orientation="vertical" aria-label="যোগদানের কাগজপত্র">
      <?php foreach ($tabs as $id => $label): ?>
        <button type="button" role="tab" id="tab-<?= $id ?>" aria-controls="panel-<?= $id ?>" aria-selected="<?= $id === 'declaration' ? 'true' : 'false' ?>" tabindex="<?= $id === 'declaration' ? '0' : '-1' ?>"><?= h($label) ?></button>
      <?php endforeach; ?>
    </div>
    <button type="button" id="openDeveloper" aria-haspopup="dialog" aria-controls="developerModal">👤 ডেভেলপার পরিচিতি</button>
    <div class="app-sidebar__footer">
      <div id="downloadStats" aria-live="polite"><strong id="downloadTotal">—</strong> মোট ডাউনলোড</div>
      <p>আপনার তথ্য এই ব্রাউজারেই সংরক্ষিত থাকে।</p>
    </div>
  </aside>
  <main class="workspace-shell">
    <?php foreach ($tabs as $id => $label): ?>
      <section role="tabpanel" id="panel-<?= $id ?>" aria-labelledby="tab-<?= $id ?>"<?= $id === 'declaration' ? '' : ' hidden' ?>>
        <iframe title="<?= h($label) ?>" data-src="<?= h($sources[$id]) ?>" allowfullscreen></iframe>
      </section>
    <?php endforeach; ?>
  </main>
  <?php if (!admin_logged_in()): ?>
    <details class="seo-content">
      <summary>বিসিএস যোগদানপত্র ও অঙ্গীকারনামা জেনারেটর সম্পর্কে</summary>
      <div class="seo-content__inner">
        <h1 id="seo-heading">বিসিএস যোগদানপত্র ও অঙ্গীকারনামা তৈরি করুন</h1>
        <p>বিসিএসে যোগদানের জন্য প্রয়োজনীয় অঙ্গীকারনামা ও যোগদানপত্রের খসড়া এক জায়গায় তৈরি করুন। ফর্মে তথ্য দিলে প্রিভিউ দেখুন, তারপর Word বা PDF ফাইল ডাউনলোড করুন।</p>
        <h2>যে কাগজপত্র তৈরি করা যাবে</h2>
        <ul>
          <li><strong>অঙ্গীকারনামা:</strong> ব্যক্তিগত তথ্য, স্থায়ী ঠিকানা, ক্যাডার ও নিয়োগের তথ্য দিয়ে অঙ্গীকারনামার খসড়া তৈরি করুন।</li>
          <li><strong>যোগদানপত্র:</strong> প্রাপক, পদ, ক্যাডার ও যোগদানের তারিখসহ যোগদানপত্র তৈরি করে প্রিভিউ দেখুন।</li>
        </ul>
        <h2>কীভাবে ব্যবহার করবেন</h2>
        <p>সাইডবার থেকে অঙ্গীকারনামা বা যোগদানপত্র বেছে নিয়ে ফর্ম পূরণ করুন। প্রিভিউ মিলিয়ে Word অথবা PDF ডাউনলোড করুন। একই ব্রাউজারে ফর্মের কিছু সাধারণ তথ্য সংরক্ষিত থাকতে পারে, যাতে দুই নথিতে আবার লিখতে না হয়।</p>
        <p class="seo-content__note">এখানে তৈরি নথি খসড়া হিসেবে ব্যবহার করুন। জমা দেওয়ার আগে সংশ্লিষ্ট কর্তৃপক্ষের নির্দেশনা ও আপনার তথ্য যাচাই করে নিন।</p>
      </div>
    </details>
  <?php endif; ?>
  <script>window.BCS_DEVELOPER = <?= json_encode($developer, $jsonFlags) ?>;</script>
  <?php readfile(__DIR__ . '/resources/workspace-widgets.html'); ?>
</body>
</html>
