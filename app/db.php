<?php

$mysqlHost = '127.0.0.1';
$mysqlDbname = 'ecoridepool';
$mysqlUsername = 'root';  // Change if needed
$mysqlPassword = '1707Richi';  // Change if needed

try {
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDbname;charset=utf8", $mysqlUsername, $mysqlPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo " MySQL Database Connected Successfully!";
} catch (PDOException $e) {
    die(" MySQL Connection failed: " . $e->getMessage());
}

// MongoDB Connection
require 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    echo " MongoDB Connected Successfully!";
} catch (Exception $e) {
    die("\n MongoDB Connection failed: " . $e->getMessage());
}
?>
