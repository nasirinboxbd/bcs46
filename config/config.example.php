<?php
// Copy this file to config/config.php and update the MySQL values for your server.
return [
    'db' => [
        'host' => getenv('BCS_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('BCS_DB_PORT') ?: '3306',
        'name' => getenv('BCS_DB_NAME') ?: 'bcs_joining',
        'user' => getenv('BCS_DB_USER') ?: 'root',
        'pass' => getenv('BCS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    // Optional: set these through environment variables for automatic admin bootstrap.
    'admin' => [
        'email' => getenv('BCS_ADMIN_EMAIL') ?: '',
        'password_hash' => getenv('BCS_ADMIN_PASSWORD_HASH') ?: '',
    ],
    'developer' => [
        'image' => getenv('BCS_DEVELOPER_IMAGE') ?: 'public/assets/developer.jpeg',
        'facebook_url' => getenv('BCS_DEVELOPER_FACEBOOK') ?: '',
    ],
];
