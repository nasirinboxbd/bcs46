<?php
declare(strict_types=1);

$status = $errorStatus ?? (int) ($_SERVER['REDIRECT_STATUS'] ?? 404);
if ($status < 400 || $status > 599) {
    $status = 404;
}

[$title, $description] = match ($status) {
    400 => ['অনুরোধটি সঠিক নয়', 'ঠিকানাটি যাচাই করে আবার চেষ্টা করুন।'],
    401 => ['লগইন প্রয়োজন', 'এই পেজে প্রবেশের জন্য অনুমোদিত অ্যাকাউন্টে লগইন করুন।'],
    403 => ($csrfRejected ?? false)
        ? ['ফরমের নিরাপত্তা যাচাই ব্যর্থ হয়েছে', 'এডমিন পেজটি আবার খুলে ফরম জমা দিন।']
        : ['প্রবেশের অনুমতি নেই', 'এই পেজটি দেখতে প্রয়োজনীয় অনুমতি থাকতে হবে।'],
    404 => ['পেজটি পাওয়া যায়নি', 'ঠিকানাটি ভুল হতে পারে অথবা পেজটি অন্য জায়গায় সরানো হয়েছে।'],
    405, 501 => ['এই অনুরোধটি সমর্থিত নয়', 'মূল পেজ থেকে প্রয়োজনীয় কাজটি আবার শুরু করুন।'],
    408, 504 => ['অনুরোধের সময় শেষ হয়েছে', 'কিছুক্ষণ পর মূল পেজ থেকে আবার চেষ্টা করুন।'],
    409, 412, 428 => ['অনুরোধটি সম্পন্ন করা যায়নি', 'সর্বশেষ তথ্য পেতে মূল পেজ থেকে কাজটি আবার শুরু করুন।'],
    410 => ['পেজটি সরিয়ে নেওয়া হয়েছে', 'হোম পেজে বর্তমান কাগজপত্র ও ফরম পাওয়া যাবে।'],
    413 => ['ফাইল বা অনুরোধটি খুব বড়', 'ফাইলের আকার কমিয়ে আবার চেষ্টা করুন।'],
    414, 431 => ['অনুরোধটি খুব দীর্ঘ', 'মূল পেজ থেকে নতুন করে শুরু করুন।'],
    415 => ['ফাইলের ধরন সমর্থিত নয়', 'ফরমে উল্লেখ করা ফাইলের ধরন ব্যবহার করুন।'],
    422 => ['তথ্য যাচাই করা যায়নি', 'ফরমের তথ্য যাচাই করে আবার জমা দিন।'],
    429 => ['অল্প সময়ে অনেক অনুরোধ এসেছে', 'কিছুক্ষণ অপেক্ষা করে আবার চেষ্টা করুন।'],
    451 => ['পেজটি দেখানো যাচ্ছে না', 'আইনগত কারণে এই পেজে প্রবেশ সীমিত।'],
    502, 503 => ['সেবাটি সাময়িকভাবে বন্ধ', 'কিছুক্ষণ পর আবার চেষ্টা করুন।'],
    default => $status >= 500
        ? ['সার্ভারে সমস্যা হয়েছে', 'কাজটি এখন সম্পন্ন করা যাচ্ছে না। কিছুক্ষণ পর আবার চেষ্টা করুন।']
        : ['অনুরোধটি সম্পন্ন করা যায়নি', 'মূল পেজ থেকে প্রয়োজনীয় কাজটি আবার শুরু করুন।'],
};

http_response_code($status);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/error.php')), '/') . '/';
$home = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
$showAdmin = $errorAdmin ?? false;
?><!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $status ?> · <?= $title ?></title>
  <style><?php readfile(__DIR__ . '/resources/error-page.css'); ?></style>
</head>
<body>
  <main class="error-card">
    <div class="error-art" aria-hidden="true">
      <span class="orbit orbit-outer"></span>
      <span class="orbit orbit-inner"></span>
      <div class="paper"><span></span><span></span><span></span></div>
      <span class="spark spark-top"></span><span class="spark spark-bottom"></span>
    </div>
    <div class="error-copy">
      <p class="status" lang="en">HTTP <?= $status ?></p>
      <h1><?= $title ?></h1>
      <p class="description"><?= $description ?></p>
      <div class="actions">
        <a class="primary" href="<?= $home ?>" target="_top"><span aria-hidden="true">←</span> হোমে ফিরুন</a>
        <?php if ($showAdmin): ?><a href="<?= $home ?>admin" target="_top">এডমিন পেজ খুলুন</a><?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
