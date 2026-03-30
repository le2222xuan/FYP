<?php
// ── Load .env file (never committed to Git) ───────────────────────
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$_env_name, $_env_value] = explode('=', $line, 2);
            $_env_name  = trim($_env_name);
            $_env_value = trim($_env_value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($_env_name, $_ENV)) {
                putenv("$_env_name=$_env_value");
                $_ENV[$_env_name] = $_env_value;
            }
        }
    }
    unset($lines, $line, $_env_name, $_env_value);
}

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'testing';

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