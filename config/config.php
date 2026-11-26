<?php
// Local defaults are suitable for XAMPP/WAMP. Use environment variables in production.
$config = require __DIR__ . '/config.example.php';
$config['admin'] = [
    'email' => 'nasir.buet.ce@gmail.com',
    'password_hash' => '$2y$10$P2Ko3Bp7WN1dymGXWygbS.fp3y5dRX8bHDez37iNZB3BAs29Vkm3q',
    'db' => [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'bcs_joining',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
];
$config['developer'] = [
    'image' => 'public/assets/developer.jpeg',
    'facebook_url' => getenv('BCS_DEVELOPER_FACEBOOK') ?: '',
];
return $config;

