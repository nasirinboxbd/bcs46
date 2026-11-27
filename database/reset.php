<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "এই command শুধু CLI থেকে চালানো যাবে।\n");
    exit(1);
}

// Check the MySQL PDO driver before prompting or attempting any database changes.
if (!extension_loaded('pdo_mysql')) {
    $iniFile = php_ini_loaded_file();
    fwrite(STDERR, "Reset failed: PHP CLI does not have the pdo_mysql extension enabled. No database changes were made.\n");
    fwrite(STDERR, "CLI PHP: " . PHP_BINARY . "\n");
    fwrite(STDERR, "php.ini: " . ($iniFile !== false ? $iniFile : 'not loaded') . "\n");
    fwrite(STDERR, "Run php --ini to find the CLI php.ini, enable extension=pdo_mysql there, then open a new terminal and verify with php -m.\n");
    exit(1);
}

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'] ?? [];
$name = (string)($db['name'] ?? '');
if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
    fwrite(STDERR, "Database name নিরাপদ নয় বা config-এ নেই।\n");
    exit(1);
}

$force = in_array('--force', $argv, true);
if (!$force) {
    fwrite(STDOUT, "সতর্কতা: {$name} database এবং সব table স্থায়ীভাবে মুছে যাবে।\n");
    fwrite(STDOUT, "চালিয়ে যেতে RESET লিখুন: ");
    $answer = trim((string)fgets(STDIN));
    if ($answer !== 'RESET') {
        fwrite(STDOUT, "Reset বাতিল করা হয়েছে।\n");
        exit(0);
    }
}

$host = (string)($db['host'] ?? '127.0.0.1');
$port = (string)($db['port'] ?? '3306');
$charset = (string)($db['charset'] ?? 'utf8mb4');
$dsn = "mysql:host={$host};port={$port};charset={$charset}";

try {
    $pdo = new PDO($dsn, (string)($db['user'] ?? ''), (string)($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $quotedName = '`' . $name . '`';
    $pdo->exec("DROP DATABASE IF EXISTS {$quotedName}");
    $pdo->exec("CREATE DATABASE {$quotedName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $databasePdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", (string)($db['user'] ?? ''), (string)($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) throw new RuntimeException('schema.sql পড়া যায়নি।');
    $schema = preg_replace('/^\s*(CREATE DATABASE[^;]+;|USE\s+[^;]+;)\s*/im', '', $schema) ?? $schema;
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $databasePdo->exec($statement);
    }

    $admin = $config['admin'] ?? [];
    if (!empty($admin['email']) && !empty($admin['password_hash'])) {
        $stmt = $databasePdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$admin['email'], $admin['password_hash']]);
        fwrite(STDOUT, "Admin account seed করা হয়েছে: {$admin['email']}\n");
    }
    fwrite(STDOUT, "Database reset সম্পন্ন হয়েছে: {$name}\n");
    fwrite(STDOUT, "Schema এবং default settings seed করা হয়েছে।\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Reset ব্যর্থ: {$error->getMessage()}\n");
    exit(1);
}
