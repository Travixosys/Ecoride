<?php
// app/db.php

use MongoDB\Client as MongoClient;

// ────────────────────────────────────────────────────────────
// 1) MySQL/TiDB (PDO) — from environment variables
// ────────────────────────────────────────────────────────────
$mysqlHost   = getenv('DB_HOST');
$mysqlDbname = getenv('DB_NAME');
$mysqlUser   = getenv('DB_USER');
$mysqlPass   = getenv('DB_PASS');
$mysqlPort   = getenv('DB_PORT') ?: '4000'; // TiDB uses 4000, MySQL uses 3306

// Validate
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $v) {
    if (! getenv($v)) {
        throw new RuntimeException("$v environment variable is required");
    }
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $mysqlHost,
    $mysqlPort,
    $mysqlDbname
);

// PDO options - TiDB Cloud requires SSL
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Auto-detect TiDB Cloud (tidbcloud in hostname or port 4000) OR explicit DB_SSL=true
$isTiDBCloud = strpos($mysqlHost, 'tidbcloud') !== false || $mysqlPort === '4000';
if ($isTiDBCloud || getenv('DB_SSL') === 'true') {
    // TiDB Cloud requires SSL - enable encrypted connection
    // Set SSL_CA to empty string to use SSL without specific CA verification
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '';
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

$pdo = new PDO($dsn, $mysqlUser, $mysqlPass, $pdoOptions);

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