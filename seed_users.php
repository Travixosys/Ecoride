<?php

use PDO;

/* 1 — Connect to the database */

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=ecoridepool;charset=utf8mb4', // Change to your local DB settings
    '',
    '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

/* 2 — Seed data
      [name, email, clearPwd, role, phone, license_number, driver_rating] */
$users = [
    ['Admin One',    'admin1@ecoride.com',  'adminsecure', 'admin',    '1111111111', null,      null],
    ['Admin Two',    'admin2@ecoride.com',  'adminsecure', 'admin',    '2222222222', null,      null],

    ['Driver One',   'driver1@ecoride.com', 'driverpass',  'driver',   '3333333333', 'DRV1001', 4.8],
    ['Driver Two',   'driver2@ecoride.com', 'driverpass',  'driver',   '4444444444', 'DRV1002', 4.5],
    ['Driver Three', 'driver3@ecoride.com', 'driverpass',  'driver',   '5555555555', 'DRV1003', 4.0],
    ['Driver Four',  'driver4@ecoride.com', 'driverpass',  'driver',   '6666666666', null,      null],

    ['User One',     'user1@ecoride.com',   'password123', 'user',     '7777777777', null,      null],
    ['User Two',     'user2@ecoride.com',   'password123', 'user',     '8888888888', null,      null],
    ['User Three',   'user3@ecoride.com',   'password123', 'user',     '9999999999', null,      null],
    ['User Four',    'user4@ecoride.com',   'password123', 'user',     '1212121212', null,      null],
    ['User Five',    'user5@ecoride.com',   'password123', 'user',     '1313131313', null,      null],
    ['User Six',     'user6@ecoride.com',   'password123', 'user',     '1414141414', null,      null],
    ['User Seven',   'user7@ecoride.com',   'password123', 'user',     '1515151515', null,      null],
    ['User Eight',   'user8@ecoride.com',   'password123', 'user',     '1616161616', null,      null],

    ['Employee One', 'employee1@ecoride.com', 'employee123', 'employee', '1717171717', null,      null],
    ['Employee Two', 'employee2@ecoride.com', 'employee123', 'employee', '1818181818', null,      null],
];

/* 3 — Prepared INSERT that matches every column except id/created_at */
$sql = <<<SQL
INSERT INTO users
    (name, email, password, role, phone_number,
     license_number, suspended, driver_rating, credits)
VALUES
    (:name, :email, :password, :role, :phone,
     :license, :suspended, :rating, :credits)
SQL;

$stmt = $pdo->prepare($sql);

/* 4 — Execute for each user */
foreach ($users as $u) {
    $stmt->execute([
        'name'       => $u[0],
        'email'      => $u[1],
        'password'   => password_hash($u[2], PASSWORD_BCRYPT),
        'role'       => $u[3],
        'phone'      => $u[4],
        'license'    => $u[5],
        'suspended'  => 0,    // active by default
        'rating'     => $u[6],
        'credits'    => 20,   // starting balance
    ]);
}

echo "Users inserted successfully!\n";
