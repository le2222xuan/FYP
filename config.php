<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "testing";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

if (!defined('PAYMENT_DEPOSIT_PERCENT')) {
    define('PAYMENT_DEPOSIT_PERCENT', 30);
}
if (!defined('PAYMENT_MIDTERM_PERCENT')) {
    define('PAYMENT_MIDTERM_PERCENT', 40);
}
if (!defined('PAYMENT_FINAL_PERCENT')) {
    define('PAYMENT_FINAL_PERCENT', 30);
}


require_once __DIR__ . '/includes/paths.php'; 
?>