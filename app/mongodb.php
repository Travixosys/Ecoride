<?php
use MongoDB\Client as MongoDBClient;
require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->ecoridepool->user_preferences;

echo "✅ MongoDB Connected Successfully!";
?>
