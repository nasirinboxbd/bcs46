<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $exception): void {
        error_log((string) $exception);
        if (!headers_sent()) {
            $errorStatus = 500;
            require dirname(__DIR__) . '/error.php';
        }
    });
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_config(): array
{
    static $config;
    return $config ??= require dirname(__DIR__) . '/config/config.php';
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = app_config()['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Keep first-run setup friendly; database/schema.sql remains available for managed deployments.
    $pdo->exec('CREATE TABLE IF NOT EXISTS site_settings (id TINYINT UNSIGNED NOT NULL PRIMARY KEY, bcs_number VARCHAR(20) NOT NULL DEFAULT \'45\', exam_year YEAR NOT NULL DEFAULT 2023, joining_date DATE NULL, notification_date DATE NULL, commission_date DATE NULL, show_cadre_id TINYINT(1) NOT NULL DEFAULT 1, developer_name VARCHAR(160) NOT NULL DEFAULT \'Md. Nasir Uddin\', developer_subtitle VARCHAR(200) NOT NULL DEFAULT \'ওয়েব অ্যাপ ডেভেলপার\', developer_workplace VARCHAR(255) NOT NULL DEFAULT \'Assistant Authorized Officer, RAJUK\', developer_bcs46 VARCHAR(255) NOT NULL DEFAULT \'BCS Roads and Highways Cadre — সুপারিশপ্রাপ্ত\', developer_bcs47 VARCHAR(255) NOT NULL DEFAULT \'BCS Administration Cadre — সুপারিশপ্রাপ্ত\', developer_whatsapp VARCHAR(255) NOT NULL DEFAULT \'https://wa.me/8801581051987\', developer_facebook VARCHAR(255) NOT NULL DEFAULT \'https://www.facebook.com/mdnasiruddin.ce.buet\', developer_image VARCHAR(255) NOT NULL DEFAULT \'public/assets/developer.jpeg\', updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
    foreach ([
        'developer_name' => "VARCHAR(160) NOT NULL DEFAULT 'Md. Nasir Uddin'",
        'developer_subtitle' => "VARCHAR(200) NOT NULL DEFAULT 'ওয়েব অ্যাপ ডেভেলপার'",
        'developer_workplace' => "VARCHAR(255) NOT NULL DEFAULT 'Assistant Authorized Officer, RAJUK'",
        'developer_bcs46' => "VARCHAR(255) NOT NULL DEFAULT 'BCS Roads and Highways Cadre — সুপারিশপ্রাপ্ত'",
        'developer_bcs47' => "VARCHAR(255) NOT NULL DEFAULT 'BCS Administration Cadre — সুপারিশপ্রাপ্ত'",
        'commission_no' => "VARCHAR(100) NOT NULL DEFAULT '৮০.০০.০০০০.২০২.৬৪.০১৬.২৫-১৭১'",
        'notification_no' => "VARCHAR(100) NOT NULL DEFAULT '০৫.০০.০০০০.১৪৭.১১.০১৬.২৫-৫১৮'",
        'developer_whatsapp' => "VARCHAR(255) NOT NULL DEFAULT 'https://wa.me/8801581051987'",
        'developer_facebook' => "VARCHAR(255) NOT NULL DEFAULT 'https://www.facebook.com/mdnasiruddin.ce.buet'",
        'developer_image' => "VARCHAR(255) NOT NULL DEFAULT 'public/assets/developer.jpeg'",
        'branding' => 'TEXT NULL',
    ] as $column => $definition) {
        try { $pdo->exec("ALTER TABLE site_settings ADD COLUMN {$column} {$definition}"); } catch (Throwable) { /* Existing installations already have the column. */ }
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_users (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec("INSERT IGNORE INTO site_settings (id, bcs_number, exam_year, notification_date, commission_date, show_cadre_id) VALUES (1, '45', 2023, '2026-10-14', '2025-12-14', 1)");
    $admin = app_config()['admin'] ?? [];
    if (!empty($admin['email']) && !empty($admin['password_hash'])) {
        $existingId = $pdo->query('SELECT id FROM admin_users ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($existingId) {
            $stmt = $pdo->prepare('UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?');
            $stmt->execute([$admin['email'], $admin['password_hash'], $existingId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$admin['email'], $admin['password_hash']]);
        }
    }
    return $pdo;
}

function default_settings(): array
{
    return [
        'bcs_number' => '45',
        'exam_year' => '2023',
        'joining_date' => '',
        'notification_date' => '2026-10-14',
        'commission_date' => '2025-12-14',
        'commission_no' => '৮০.০০.০০০০.২০২.৬৪.০১৬.২৫-১৭১',
        'notification_no' => '০৫.০০.০০০০.১৪৭.১১.০১৬.২৫-৫১৮',
        'show_cadre_id' => 1,
        'developer_name' => 'Md. Nasir Uddin',
        'developer_subtitle' => 'ওয়েব অ্যাপ ডেভেলপার',
        'developer_workplace' => 'Assistant Authorized Officer, RAJUK',
        'developer_bcs46' => 'BCS Roads and Highways Cadre — সুপারিশপ্রাপ্ত',
        'developer_bcs47' => 'BCS Administration Cadre — সুপারিশপ্রাপ্ত',
        'developer_whatsapp' => 'https://wa.me/8801581051987',
        'developer_facebook' => 'https://www.facebook.com/mdnasiruddin.ce.buet',
        'developer_image' => 'public/assets/developer.jpeg',
        'branding' => null,
    ];
}

function get_settings(): array
{
    $defaults = default_settings();
    try {
        $row = db()->query('SELECT * FROM site_settings WHERE id = 1')->fetch();
        return array_merge($defaults, $row ?: []);
    } catch (Throwable $e) {
        return $defaults;
    }
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function require_csrf(): void
{
    $expected = $_SESSION['csrf'] ?? '';
    $submitted = $_POST['csrf'] ?? null;
    if ($expected === '' || !is_string($submitted) || !hash_equals($expected, $submitted)) {
        $errorStatus = 403;
        $csrfRejected = true;
        $errorAdmin = true;
        require dirname(__DIR__) . '/error.php';
        exit;
    }
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: admin');
        exit;
    }
}

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
