<?php
// app/db.php

use MongoDB\Client as MongoClient;

// ────────────────────────────────────────────────────────────
// 1) MySQL (PDO) — always from Heroku/AlwaysData ENV
// ────────────────────────────────────────────────────────────
$mysqlHost   = getenv('DB_HOST');
$mysqlDbname = getenv('DB_NAME');
$mysqlUser   = getenv('DB_USER');
$mysqlPass   = getenv('DB_PASS');

// Validate
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $v) {
    if (! getenv($v)) {
        throw new RuntimeException("$v environment variable is required");
    }
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $mysqlHost,
    $mysqlDbname
);

$pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// ────────────────────────────────────────────────────────────
// 2) MongoDB Atlas — only from MONGO_URI ENV
// ────────────────────────────────────────────────────────────


$mongoUri = trim((string) getenv('MONGO_URI'));
if ($mongoUri === '') {
    throw new RuntimeException('MONGO_URI environment variable is required');
}

// Environment-aware TLS settings
// In production, enforce proper TLS verification
// Set MONGO_TLS_INSECURE=true only for local development with self-signed certs
$isProduction = getenv('APP_ENV') === 'production';
$tlsInsecure = !$isProduction && getenv('MONGO_TLS_INSECURE') === 'true';

$driverOptions = [];
if ($tlsInsecure) {
    $driverOptions['tlsInsecure'] = true;
}

$mongoClient = new MongoClient($mongoUri, [], $driverOptions);

$prefsCollection = $mongoClient
    ->selectDatabase('ecoridepool')
    ->selectCollection('user_preferences');

return [
    'pdo'              => $pdo,
    'prefs_collection' => $prefsCollection,
];