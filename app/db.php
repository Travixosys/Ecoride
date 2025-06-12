<?php

use MongoDB\Client as MongoClient;

return (function () {
    // MySQL
    $mysqlHost     = getenv('DB_HOST')   ?: 'mysql-ecoridepool.alwaysdata.net';
    $mysqlDbname   = getenv('DB_NAME')   ?: 'ecoridepool_eco';
    $mysqlUsername = getenv('DB_USER')   ?: '418123';
    $mysqlPassword = getenv('DB_PASS')   ?: 'dataSQL45';

    $pdo = new PDO(
        "mysql:host={$mysqlHost};dbname={$mysqlDbname};charset=utf8mb4",
        $mysqlUsername,
        $mysqlPassword,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // MongoDB Atlas
    $mongoUri = getenv('MONGO_URI');
    if (! $mongoUri) {
        throw new RuntimeException('MONGO_URI environment variable not set');
    }

    // ** Add TLS options here **
    $mongoOptions = [
        // enforce TLS
        'tls' => true,
        // temporarily allow invalid/self-signed certs
        'tlsAllowInvalidCertificates' => true,
    ];

    $mongoClient = new MongoClient($mongoUri, $mongoOptions);
    $preferencesCollection = $mongoClient
        ->selectDatabase('ecoridepool')
        ->selectCollection('user_preferences');

    return [
        'pdo'              => $pdo,
        'prefs_collection' => $preferencesCollection,
    ];
})();


