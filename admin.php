<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/branding.php';

$in_panel = ($_GET['panel'] ?? '') === '1';
if (!$in_panel && admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ./#admin');
    exit;
}
if ($in_panel && !admin_logged_in()) {
    $errorStatus = 403;
    $errorAdmin = true;
    require __DIR__ . '/error.php';
    exit;
}

$error = '';
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$settings = default_settings();
$has_admin = false;
$uploadedImages = [];
$brandingFields = [
    'site_title' => ['সাইটের টাইটেল', 160],
    'site_description' => ['সাইটের বিবরণ', 320],
    'site_logo' => ['সাইটের লোগোর URL বা পাথ', 2048],
    'share_title' => ['শেয়ার কার্ডের টাইটেল', 160],
    'share_description' => ['শেয়ার কার্ডের বিবরণ', 320],
    'share_image' => ['শেয়ার কার্ডের ছবির URL বা পাথ', 2048],
    'share_image_alt' => ['শেয়ার ছবির বিকল্প বর্ণনা', 250],
    'canonical_url' => ['সাইটের মূল (canonical) URL', 2048],
    'keywords' => ['মেটা কীওয়ার্ড', 250],
    'author' => ['মেটা লেখকের নাম', 160],
    'theme_color' => ['ব্রাউজারের থিম রং', 7],
];

try {
    $has_admin = (int)db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    $settings = get_settings();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'setup' && !$has_admin) {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            if (mb_strlen($username) < 3 || strlen($password) < 8) {
                throw new RuntimeException('ইউজারনেম কমপক্ষে ৩ অক্ষর এবং পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।');
            }
            $stmt = db()->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['admin_id'] = (int)db()->lastInsertId();
            $_SESSION['admin_name'] = $username;
            header('Location: ./#admin');
            exit;
        }

        if ($action === 'login' && !$has_admin) {
            throw new RuntimeException('প্রথমে admin account তৈরি করুন।');
        }

        if ($action === 'login') {
            $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1');
            $stmt->execute([trim((string)($_POST['username'] ?? ''))]);
            $user = $stmt->fetch();
            if (!$user || !password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
                throw new RuntimeException('ইউজারনেম অথবা পাসওয়ার্ড সঠিক নয়।');
            }
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['admin_name'] = $user['username'];
            header('Location: ./#admin');
            exit;
        }

        if ($action === 'save' && admin_logged_in()) {
            $bcs = trim((string)($_POST['bcs_number'] ?? ''));
            $year = (int)($_POST['exam_year'] ?? 0);
            if ($bcs === '' || $year < 1900 || $year > 2200) {
                throw new RuntimeException('বিসিএস নম্বর ও পরীক্ষার সাল সঠিকভাবে দিন।');
            }
            $commissionNo = trim((string)($_POST['commission_no'] ?? ''));
            $notificationNo = trim((string)($_POST['notification_no'] ?? ''));
            if ($commissionNo === '' || $notificationNo === '' || mb_strlen($commissionNo) > 100 || mb_strlen($notificationNo) > 100) {
                throw new RuntimeException('কমিশনের চিঠি ও প্রজ্ঞাপনের স্মারক নম্বর ১–১০০ অক্ষরের মধ্যে দিন।');
            }
            $date = static function (string $key): ?string {
                $value = trim((string)($_POST[$key] ?? ''));
                if ($value === '') return null;
                $d = DateTime::createFromFormat('Y-m-d', $value);
                return $d && $d->format('Y-m-d') === $value ? $value : throw new RuntimeException('তারিখের ঘরে সঠিক তারিখ দিন।');
            };
            $notificationDate = $date('notification_date');
            $commissionDate = $date('commission_date');
            if ($notificationDate === null || $commissionDate === null) {
                throw new RuntimeException('প্রজ্ঞাপন ও কমিশনের তারিখ পূরণ করুন।');
            }
            $url = static function (string $key): string {
                $value = trim((string)($_POST[$key] ?? ''));
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new RuntimeException('Developer link-এর URL সঠিক নয়।');
                }
                return $value;
            };
            $uploadImage = static function (string $field, string $prefix, string $fallback) use (&$uploadedImages): string {
                $file = $_FILES[$field] ?? [];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    return $fallback;
                }
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
                    throw new RuntimeException('আপলোড করা ছবি ২ MB-এর মধ্যে হতে হবে।');
                }
                $imageInfo = @getimagesize($file['tmp_name']);
                $extension = match ($imageInfo['mime'] ?? '') {
                    'image/jpeg' => 'jpeg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => throw new RuntimeException('শুধু JPG, PNG অথবা WebP image ব্যবহার করুন।'),
                };
                $filename = $prefix . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
                $destination = __DIR__ . '/public/assets/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException('ছবি আপলোড করা যায়নি।');
                }
                $uploadedImages[] = $destination;
                return 'public/assets/' . $filename;
            };
            $defaults = site_branding([]);
            $branding = site_branding($settings);
            foreach ($brandingFields as $key => [$label, $maxLength]) {
                $value = trim((string) ($_POST[$key] ?? $branding[$key]));
                if (mb_strlen($value) > $maxLength) {
                    throw new RuntimeException($label . ' অনুমোদিত দৈর্ঘ্যের চেয়ে বড়।');
                }
                $branding[$key] = $value === '' ? $defaults[$key] : $value;
            }
            $isWebUrl = static fn(string $value): bool =>
                preg_match('~^https?://~i', $value) === 1 && filter_var($value, FILTER_VALIDATE_URL) !== false;
            if ($branding['canonical_url'] !== '' && !$isWebUrl($branding['canonical_url'])) {
                throw new RuntimeException('Canonical URL-এ সম্পূর্ণ HTTP বা HTTPS ঠিকানা দিন।');
            }
            if (!preg_match('/^#[0-9a-f]{6}$/iD', $branding['theme_color'])) {
                throw new RuntimeException('থিম রংটি #087a55 ফরম্যাটে দিন।');
            }
            foreach (['site_logo' => 'site-logo', 'share_image' => 'share-image'] as $key => $prefix) {
                $branding[$key] = $uploadImage($key . '_file', $prefix, $branding[$key]);
                $asset = $branding[$key];
                if (!$isWebUrl($asset) && !(
                    preg_match('~^public/assets/[a-z0-9][a-z0-9._-]*\.(?:png|jpe?g|webp|svg|ico)$~iD', $asset)
                    && is_file(__DIR__ . '/' . $asset)
                )) {
                    throw new RuntimeException($brandingFields[$key][0] . ' সঠিক নয়।');
                }
            }
            $developerImage = $uploadImage('developer_image_file', 'developer',
                trim((string) ($_POST['developer_image'] ?? '')) ?: 'public/assets/developer.jpeg');
            $stmt = db()->prepare('UPDATE site_settings SET bcs_number = ?, exam_year = ?, joining_date = ?, notification_date = ?, commission_date = ?, commission_no = ?, notification_no = ?, show_cadre_id = ?, developer_name = ?, developer_subtitle = ?, developer_workplace = ?, developer_bcs46 = ?, developer_bcs47 = ?, developer_whatsapp = ?, developer_facebook = ?, developer_image = ?, branding = ? WHERE id = 1');
            $stmt->execute([
                $bcs, $year, $date('joining_date'), $notificationDate, $commissionDate,
                $commissionNo, $notificationNo,
                !empty($_POST['show_cadre_id']) ? 1 : 0,
                trim((string) ($_POST['developer_name'] ?? '')), trim((string) ($_POST['developer_subtitle'] ?? '')),
                trim((string) ($_POST['developer_workplace'] ?? '')), trim((string) ($_POST['developer_bcs46'] ?? '')),
                trim((string) ($_POST['developer_bcs47'] ?? '')), $url('developer_whatsapp'), $url('developer_facebook'),
                $developerImage, json_encode($branding, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
            $_SESSION['flash'] = 'সকলের জন্য প্রযোজ্য settings সফলভাবে সংরক্ষণ করা হয়েছে।';
            header('Location: admin?panel=1');
            exit;
        }
    }
} catch (Throwable $e) {
    foreach ($uploadedImages as $uploadedImage) {
        unlink($uploadedImage);
    }
    $error = $e->getMessage();
}

$logged_in = admin_logged_in();
$branding = site_branding($settings);
?><!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin panel | BCS joining documents</title>
  <link rel="stylesheet" href="public/assets/navigation.css">
  <style>
    @font-face{font-family:Nikosh;src:url("public/assets/nikosh.ttf") format("truetype");font-display:swap}
    :root{--ink:#14231c;--muted:#6b7b72;--line:#dce8e1;--surface:#fff;--bg:#f3f8f5;--brand:#087a55;--brand-dark:#064e3b;--soft:#e7f5ee}
    *{box-sizing:border-box} body{margin:0;background:radial-gradient(circle at 5% 0,#dff5e9,transparent 28rem),var(--bg);color:var(--ink);font:16px/1.6 Nikosh,"Noto Sans Bengali",system-ui,sans-serif}
    .shell{width:min(100% - 32px,1040px);margin:0 auto}.top{padding:28px 0 50px;background:linear-gradient(120deg,var(--brand-dark),var(--brand));color:#fff}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px}.brand{font-size:20px;font-weight:800}.eyebrow{opacity:.78;font-size:13px}.top h1{font-size:clamp(28px,5vw,44px);line-height:1.2;margin:40px 0 8px}.top p{margin:0;opacity:.85;max-width:650px}.link{color:#fff;text-decoration:none;border:1px solid #ffffff55;border-radius:999px;padding:8px 14px}.wrap{margin:-28px auto 60px}.panel{background:var(--surface);border:1px solid var(--line);border-radius:22px;padding:clamp(20px,4vw,34px);box-shadow:0 18px 50px #164d3420}.panel h2{margin:0 0 5px;font-size:25px}.sub{margin:0 0 22px;color:var(--muted)}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:start;gap:18px 22px}.field{display:grid;align-content:start;gap:6px}.field.full{grid-column:1/-1}label{font-weight:700;font-size:15px}input{width:100%;min-height:46px;border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:#fbfdfc;color:var(--ink);font:inherit}input:focus{outline:3px solid #0f9d6e33;border-color:var(--brand)}.help{font-size:13px;color:var(--muted)}.toggle{display:flex;align-items:center;gap:12px;border:1px solid var(--line);border-radius:14px;padding:14px;background:#fbfdfc}.toggle input{width:20px;min-height:20px;accent-color:var(--brand)}button{border:0;border-radius:12px;padding:12px 18px;background:var(--brand);color:#fff;font:inherit;font-weight:700;cursor:pointer}button:hover{background:#0f9d6e}.actions{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:28px;padding-top:22px;border-top:1px solid var(--line)}.alert{border-radius:12px;padding:12px 14px;margin-bottom:18px}.error{background:#fff0ef;color:#a52b27}.success{background:var(--soft);color:var(--brand-dark)}.login{max-width:460px;margin:0 auto}.empty{color:var(--muted)}@media(max-width:680px){.shell{width:min(100% - 20px,1040px)}.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.top{padding-top:18px}.top h1{margin-top:32px}.actions{align-items:stretch;flex-direction:column}.actions button{width:100%}}
    .developer-editor h3{margin:10px 0 0;font-size:20px}.developer-preview{display:flex;align-items:center;gap:18px;padding:18px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(135deg,#edf9f2,#fff)}.developer-preview img{width:92px;height:92px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:0 8px 20px #164d3425}.developer-preview h3{margin:0;font-size:22px}.developer-preview p{margin:2px 0;color:var(--muted)}.developer-preview a{display:inline-block;margin-top:8px;color:#1877f2;font-weight:700;text-decoration:none}@media(max-width:680px){.developer-preview{align-items:flex-start;flex-direction:column}}
    .image-upload{display:grid;grid-template-columns:112px minmax(0,1fr);align-items:center;gap:18px;padding:16px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(135deg,#f5fbf7,#fff)}.image-upload img{width:112px;height:100px;object-fit:contain;border:1px solid var(--line);border-radius:14px;background:#fff;padding:8px}.image-upload img.avatar{width:100px;height:100px;object-fit:cover;border-radius:50%}.upload-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;padding:9px 14px;border:1px solid #b9dec9;border-radius:10px;background:#fff;color:var(--brand-dark);font-weight:700;cursor:pointer}.upload-button:hover{background:var(--soft)}.image-upload input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.upload-name{display:block;min-height:20px;margin-top:6px;font-size:13px;color:var(--muted)}
  </style>
</head>
<body>
  <header class="top"><div class="shell"><div class="topbar"><div><div class="eyebrow">BCS joining documents</div><div class="brand">Admin control center</div></div><?php if ($logged_in): ?><a class="link" href="logout" target="_top">লগআউট</a><?php else: ?><a class="link" href="./">হোম</a><?php endif; ?></div><h1><?php echo $logged_in ? 'সাইটের shared তথ্য নিয়ন্ত্রণ করুন' : 'Admin panel'; ?></h1><p>এখানে পরিবর্তন করা তথ্য সকল ব্যবহারকারীর ফর্ম ও generated document-এ প্রযোজ্য হবে।</p></div></header>
  <main class="shell wrap">
    <section class="panel">
      <?php if ($error): ?><div class="alert error"><?php echo h($error); ?></div><?php endif; ?>
      <?php if ($flash): ?><div class="alert success"><?php echo h($flash); ?></div><?php endif; ?>
      <?php if (!$has_admin && !$logged_in): ?>
        <div class="login"><h2>প্রথম admin account তৈরি করুন</h2><p class="sub">এই account দিয়েই পরবর্তীতে shared settings পরিবর্তন করা যাবে।</p><form method="post"><input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"><input type="hidden" name="action" value="setup"><div class="field"><label for="username">Admin email</label><input id="username" type="email" name="username" required minlength="3" autocomplete="username"></div><div class="field" style="margin-top:16px"><label for="password">পাসওয়ার্ড</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"><span class="help">কমপক্ষে ৮ অক্ষর</span></div><button style="margin-top:22px" type="submit">Admin account তৈরি করুন</button></form></div>
      <?php elseif (!$logged_in): ?>
        <div class="login"><h2>Admin login</h2><p class="sub">সাইটের shared settings ও Developer পরিচিতি পরিবর্তন করতে লগইন করুন।</p><form method="post"><input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"><input type="hidden" name="action" value="login"><div class="field"><label for="username">Admin email</label><input id="username" type="email" name="username" required autocomplete="username"></div><div class="field" style="margin-top:16px"><label for="password">পাসওয়ার্ড</label><input id="password" type="password" name="password" required autocomplete="current-password"></div><button style="margin-top:22px" type="submit">লগইন</button></form><p class="help" style="margin-top:18px">লগইন করার পর নিচে Developer পরিচিতি section ও live preview দেখা যাবে।</p></div>
      <?php else: ?>
        <h2>Website defaults</h2><p class="sub">এই settings সব visitor-এর জন্য একই থাকবে এবং document generation-এর সময় ব্যবহার হবে।</p>
        <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"><input type="hidden" name="action" value="save"><div class="grid">
          <div class="field"><label for="bcs_number">কততম বিসিএস</label><input id="bcs_number" name="bcs_number" value="<?php echo h($settings['bcs_number']); ?>" required><span class="help">যেমন: ৪৫ বা 45</span></div>
          <div class="field"><label for="exam_year">বিসিএস পরীক্ষার সাল</label><input id="exam_year" name="exam_year" type="number" min="1900" max="2200" value="<?php echo h($settings['exam_year']); ?>" required></div>
          <div class="field"><label for="joining_date">যোগদানের তারিখ</label><input id="joining_date" name="joining_date" type="date" value="<?php echo h($settings['joining_date']); ?>"><span class="help">প্রয়োজনে ফাঁকা রাখতে পারবেন</span></div>
          <div class="field full"><h3>বাংলাদেশ সরকারি কর্মকমিশনের বিজ্ঞপ্তি</h3><p class="help">তারিখ ও বিজ্ঞপ্তি নম্বর পরিবর্তন করলে যোগদানপত্রে আপডেট হবে।</p></div>
          <div class="field"><label for="commission_date">বিজ্ঞপ্তির তারিখ</label><input id="commission_date" name="commission_date" type="date" value="<?php echo h($settings['commission_date']); ?>" required></div>
          <div class="field"><label for="commission_no">বিজ্ঞপ্তি নম্বর</label><input id="commission_no" name="commission_no" maxlength="100" value="<?php echo h($settings['commission_no']); ?>" required><span class="help">উদাহরণ: ৮০.০০.০০০০.২০২.৬৪.০১৬.২৫-১৭১</span></div>
          <div class="field full"><h3>জনপ্রশাসন মন্ত্রণালয়ের নবনিয়োগ শাখার প্রজ্ঞাপন</h3></div>
          <div class="field"><label for="notification_date">প্রজ্ঞাপনের তারিখ</label><input id="notification_date" name="notification_date" type="date" value="<?php echo h($settings['notification_date']); ?>" required></div>
          <div class="field"><label for="notification_no">প্রজ্ঞাপন নম্বর</label><input id="notification_no" name="notification_no" maxlength="100" value="<?php echo h($settings['notification_no']); ?>" required><span class="help">উদাহরণ: ০৫.০০.০০০০.১৪৭.১১.০১৬.২৫-৫১৮</span></div>
          <div class="field full"><label class="toggle" for="show_cadre_id"><input id="show_cadre_id" name="show_cadre_id" type="checkbox" value="1" <?php echo !empty($settings['show_cadre_id']) ? 'checked' : ''; ?>><span><strong>ক্যাডার পরিচিতি নম্বর দেখান</strong><br><span class="help">বন্ধ করলে অঙ্গীকারনামার ফরম, প্রিভিউ, Word ও PDF থেকে নম্বরের ঘর ও লেখা বাদ যাবে।</span></span></label></div>
          <div class="field full developer-editor"><h3>সাইটের পরিচিতি ও শেয়ার কার্ড</h3><p class="help">শেয়ার টাইটেল বা বিবরণ ফাঁকা রাখলে সাইটের টাইটেল বা বিবরণ ব্যবহার হবে।</p></div>
          <?php foreach ($brandingFields as $key => [$label, $maxLength]): ?>
            <?php if ($key === 'site_logo' || $key === 'share_image'): ?>
              <div class="field"><label><?= h($label) ?></label><input id="<?= h($key) ?>" name="<?= h($key) ?>" type="hidden" value="<?= h($branding[$key]) ?>"><div class="image-upload"><img id="<?= h($key) ?>_preview" src="<?= h($branding[$key]) ?>" alt="<?= h($label) ?> preview"><div><strong><?= $key === 'site_logo' ? 'সাইটের লোগো' : 'শেয়ার কার্ডের ছবি' ?></strong><span class="help" style="display:block">JPG, PNG বা WebP · সর্বোচ্চ ২ MB</span><label class="upload-button" for="<?= h($key) ?>_file">ছবি বেছে নিন</label><input id="<?= h($key) ?>_file" name="<?= h($key) ?>_file" type="file" accept="image/jpeg,image/png,image/webp"><span class="upload-name" id="<?= h($key) ?>_name">বর্তমান ছবি</span></div></div></div>
            <?php else: ?>
              <div class="field">
                <label for="<?= h($key) ?>"><?= h($label) ?></label>
                <input id="<?= h($key) ?>" name="<?= h($key) ?>" type="<?= $key === 'canonical_url' ? 'url' : 'text' ?>" maxlength="<?= $maxLength ?>" value="<?= h($branding[$key]) ?>">
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
          <div class="field full developer-editor"><h3>Developer পরিচিতি</h3><p class="help">এই তথ্য sidebar-এর Developer পরিচিতি modal-এ দেখা যাবে। ডান পাশে live preview দেখুন।</p></div>
          <div class="field"><label for="developer_name">নাম</label><input id="developer_name" name="developer_name" value="<?php echo h($settings['developer_name']); ?>" required></div>
          <div class="field"><label for="developer_subtitle">পরিচয় / subtitle</label><input id="developer_subtitle" name="developer_subtitle" value="<?php echo h($settings['developer_subtitle']); ?>" required></div>
          <div class="field"><label for="developer_workplace">বর্তমান কর্মস্থল</label><input id="developer_workplace" name="developer_workplace" value="<?php echo h($settings['developer_workplace']); ?>" required></div>
          <div class="field"><label for="developer_bcs46">৪৬তম বিসিএস পরিচিতি</label><input id="developer_bcs46" name="developer_bcs46" value="<?php echo h($settings['developer_bcs46']); ?>" required></div>
          <div class="field"><label for="developer_bcs47">৪৭তম বিসিএস পরিচিতি</label><input id="developer_bcs47" name="developer_bcs47" value="<?php echo h($settings['developer_bcs47']); ?>" required></div>
          <div class="field"><label for="developer_whatsapp">WhatsApp link</label><input id="developer_whatsapp" name="developer_whatsapp" type="url" value="<?php echo h($settings['developer_whatsapp']); ?>" required></div>
          <div class="field"><label for="developer_facebook">Facebook profile/page link</label><input id="developer_facebook" name="developer_facebook" type="url" value="<?php echo h($settings['developer_facebook']); ?>" required></div>
          <div class="field full"><label>ডেভেলপার প্রোফাইল ছবি</label><input id="developer_image" name="developer_image" type="hidden" value="<?php echo h($settings['developer_image']); ?>"><div class="image-upload"><img class="avatar" id="developerUploadPreview" src="<?php echo h($settings['developer_image']); ?>" alt="ডেভেলপার প্রোফাইল ছবির প্রিভিউ"><div><strong>প্রোফাইল ছবি</strong><span class="help" style="display:block">JPG, PNG বা WebP · সর্বোচ্চ ২ MB</span><label class="upload-button" for="developer_image_file">ছবি বেছে নিন</label><input id="developer_image_file" name="developer_image_file" type="file" accept="image/jpeg,image/png,image/webp"><span class="upload-name" id="developer_image_name">বর্তমান ছবি</span></div></div></div>
          <div class="field full"><div class="developer-preview" id="developerPreview"><img id="developerPreviewImage" src="<?php echo h($settings['developer_image']); ?>" alt="Developer preview"><div><h3 id="developerPreviewName"><?php echo h($settings['developer_name']); ?></h3><p id="developerPreviewSubtitle"><?php echo h($settings['developer_subtitle']); ?></p><p id="developerPreviewWorkplace"><?php echo h($settings['developer_workplace']); ?></p><p id="developerPreviewBcs46"><?php echo h($settings['developer_bcs46']); ?></p><p id="developerPreviewBcs47"><?php echo h($settings['developer_bcs47']); ?></p><a id="developerPreviewFacebook" href="<?php echo h($settings['developer_facebook']); ?>" target="_blank" rel="noopener">Facebook profile খুলুন</a></div></div></div>
        </div><div class="actions"><span class="help">Logged in as <?php echo h($_SESSION['admin_name'] ?? 'admin'); ?></span><button type="submit">Settings সংরক্ষণ করুন</button></div></form>
      <?php endif; ?>
    </section>
  </main>
  <?php if ($logged_in && $flash): ?>
    <script>
      if ("BroadcastChannel" in window) {
        const settingsChannel = new BroadcastChannel("bcs-site-settings");
        settingsChannel.postMessage(<?= json_encode([
            'showCadreId' => (int) $settings['show_cadre_id'] === 1,
            'examYear' => (string) $settings['exam_year'],
            'joiningDate' => (string) ($settings['joining_date'] ?? ''),
            'commissionDate' => $settings['commission_date'],
            'notificationDate' => $settings['notification_date'],
            'commissionNo' => $settings['commission_no'],
            'notificationNo' => $settings['notification_no'],
            'siteTitle' => $branding['site_title'],
            'siteLogo' => $branding['site_logo'],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
        settingsChannel.close();
      }
    </script>
  <?php endif; ?>
  <script>
    (function () {
      const fields = ['developer_name', 'developer_subtitle', 'developer_workplace', 'developer_bcs46', 'developer_bcs47', 'developer_facebook', 'developer_image'];
      const sync = () => {
        const value = (id) => document.getElementById(id)?.value || '';
        const text = (id, content) => { const el = document.getElementById(id); if (el) el.textContent = content; };
        text('developerPreviewName', value('developer_name'));
        text('developerPreviewSubtitle', value('developer_subtitle'));
        text('developerPreviewWorkplace', value('developer_workplace'));
        text('developerPreviewBcs46', value('developer_bcs46'));
        text('developerPreviewBcs47', value('developer_bcs47'));
        const image = document.getElementById('developerPreviewImage');
        if (image && value('developer_image')) image.src = value('developer_image');
        const facebook = document.getElementById('developerPreviewFacebook');
        if (facebook) facebook.href = value('developer_facebook');
      };
      fields.forEach((id) => document.getElementById(id)?.addEventListener('input', sync));
      document.getElementById('developer_image_file')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        const previewUrl = URL.createObjectURL(file);
        ['developerPreviewImage', 'developerUploadPreview'].forEach((id) => {
          const image = document.getElementById(id);
          if (image) image.src = previewUrl;
        });
        const name = document.getElementById('developer_image_name');
        if (name) name.textContent = file.name;
      });
      ['site_logo', 'share_image'].forEach((key) => {
        document.getElementById(`${key}_file`)?.addEventListener('change', (event) => {
          const file = event.target.files?.[0];
          if (!file) return;
          const preview = document.getElementById(`${key}_preview`);
          if (preview) preview.src = URL.createObjectURL(file);
          const name = document.getElementById(`${key}_name`);
          if (name) name.textContent = file.name;
        });
      });
      sync();
    })();
  </script>
</body>
</html>
