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

    // Decide which Mongo URI to use
    $atlasUri = getenv('MONGO_URI');

    if ($atlasUri) {
        // Production on Heroku: Atlas + CA bundle
        $caFile = __DIR__ . '/certs/cacert.pem';
        if (! is_file($caFile)) {
            throw new \RuntimeException("CA bundle not found at {$caFile}");
        }
        $mongoOptions = [
            // driver will use TLS automatically on mongodb+srv
            'tlsCAFile' => $caFile,
            // (no tlsAllowInvalidCertificates here — we want real validation)
        ];
        $mongoClient = new MongoClient($atlasUri, $mongoOptions);
    } else {
        // Local dev fallback
        $mongoClient = new MongoClient('mongodb://localhost:27017');
    }

    $preferencesCollection = $mongoClient
        ->selectDatabase('ecoridepool')
        ->selectCollection('user_preferences');

    return [
        'pdo'              => $pdo,
        'prefs_collection' => $preferencesCollection,
    ];
})();


