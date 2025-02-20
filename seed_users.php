<?php
use PDO;

// ✅ MySQL Connection
$pdo = new PDO("mysql:host=127.0.0.1;dbname=ecoridepool;charset=utf8", "root", "1707Richi", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// ✅ Predefined Users
$users = [
    ['Admin One', 'admin1@ecoride.com', 'adminsecure', 'admin', '1111111111', NULL, NULL],
    ['Admin Two', 'admin2@ecoride.com', 'adminsecure', 'admin', '2222222222', NULL, NULL],
    ['Driver One', 'driver1@ecoride.com', 'driverpass', 'driver', '3333333333', 'DRV1001', 4.8],
    ['Driver Two', 'driver2@ecoride.com', 'driverpass', 'driver', '4444444444', 'DRV1002', 4.5],
    ['Driver Three', 'driver3@ecoride.com', 'driverpass', 'driver', '5555555555', 'DRV1003', 4.6],
    ['Driver Four', 'driver4@ecoride.com', 'driverpass', 'driver', '6666666666', NULL, NULL], // No vehicle
    ['User One', 'user1@ecoride.com', 'password123', 'user', '7777777777', NULL, NULL],
    ['User Two', 'user2@ecoride.com', 'password123', 'user', '8888888888', NULL, NULL],
    ['User Three', 'user3@ecoride.com', 'password123', 'user', '9999999999', NULL, NULL],
    ['User Four', 'user4@ecoride.com', 'password123', 'user', '1212121212', NULL, NULL],
    ['User Five', 'user5@ecoride.com', 'password123', 'user', '1313131313', NULL, NULL],
    ['User Six', 'user6@ecoride.com', 'password123', 'user', '1414141414', NULL, NULL],
    ['User Seven', 'user7@ecoride.com', 'password123', 'user', '1515151515', NULL, NULL],
    ['User Eight', 'user8@ecoride.com', 'password123', 'user', '1616161616', NULL, NULL]
];

// ✅ Insert Users
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone_number, license_number, driver_rating) VALUES (:name, :email, :password, :role, :phone, :license, :rating)");

foreach ($users as $user) {
    $stmt->execute([
        'name' => $user[0],
        'email' => $user[1],
        'password' => password_hash($user[2], PASSWORD_BCRYPT), // ✅ Auto Hashing
        'role' => $user[3],
        'phone' => $user[4],
        'license' => $user[5],
        'rating' => $user[6]
    ]);
}

echo "✅ Users Inserted Successfully!\n";
?>
