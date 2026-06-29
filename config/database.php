<?php
require_once dirname(__FILE__) . '/config.php';

// Database connection settings
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = ($host === 'localhost' || $host === 'localhost:8080' || $host === '127.0.0.1');

if ($isLocal) {
    // Local XAMPP
    $db_host = '127.0.0.1';
    $db_port = 3306;
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'c2c_marketplace';
} elseif (strpos($host, 'kesug.com') !== false) {
    // InfinityFree
  	$db_host = 'sql107.infinityfree.com';
    $db_port = 3306;
    $db_user = 'if0_42096921';
    $db_pass = 'UTjLUIyU5arT';
    $db_name = 'if0_42096921_c2c_marketplace';
} else {
    // Awardspace production
    $db_host = 'fdb1032.awardspace.net';
    $db_port = 3306;
    $db_user = '4765491_interlinked';
    $db_pass = 'Admin@1234';
    $db_name = '4765491_interlinked';
}

// connect to the database
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($con, 'utf8mb4');

// sanitize input data
function sanitize($con, $data) {
    return mysqli_real_escape_string($con, trim(strip_tags($data)));
}

// sorting helper
function sanitizeSort($value, array $allowed, $default = '') {
    if (isset($allowed[$value])) {
        return $allowed[$value];
    }
    return $default;
}

// generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}
