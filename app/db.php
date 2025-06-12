<?php


// ✅ MYSQL (AlwaysData or Heroku ENV)
$mysqlHost = getenv('DB_HOST') ?: 'mysql-ecoridepool.alwaysdata.net';
$mysqlDbname = getenv('DB_NAME') ?: 'ecoridepool_eco';
$mysqlUsername = getenv('DB_USER') ?: '418123';
$mysqlPassword = getenv('DB_PASS') ?: 'dataSQL45';

try {
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDbname;charset=utf8mb4", $mysqlUsername, $mysqlPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL connected<br>";
} catch (PDOException $e) {
    die("❌ MySQL error: " . $e->getMessage());
}

// ✅ MONGODB ATLAS
require_once __DIR__ . '/../vendor/autoload.php';

$mongoUri = getenv('MONGO_URI') ?: 'mongodb+srv://uwaugboaja:6bEKOKuDTtsk2CLf@cluster0.nwx7mtr.mongodb.net/ecoridepool?retryWrites=true&w=majority';

try {
    $mongoClient = new MongoDB\Client($mongoUri);
    $mongoDB = $mongoClient->ecoridepool;
    $userPreferences = $mongoDB->user_preferences;
    echo "✅ MongoDB connected to ecoridepool.user_preferences<br>";
} catch (Exception $e) {
    die("❌ MongoDB error: " . $e->getMessage());
}


// $mysqlHost = '127.0.0.1';
// $mysqlDbname = 'ecoridepool';
// $mysqlUsername = 'root';  // Change if needed
// $mysqlPassword = '1707Richi';  // Change if needed

// try {
//     $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDbname;charset=utf8", $mysqlUsername, $mysqlPassword);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     echo " MySQL Database Connected Successfully!";
// } catch (PDOException $e) {
//     die(" MySQL Connection failed: " . $e->getMessage());
// }

// // MongoDB Connection
// require 'vendor/autoload.php';

// try {
//     $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
//     echo " MongoDB Connected Successfully!";
// } catch (Exception $e) {
//     die("\n MongoDB Connection failed: " . $e->getMessage());
// }
?>
