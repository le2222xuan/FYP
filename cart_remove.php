<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$cart_id = isset($_GET['cart_id']) ? (int)$_GET['cart_id'] : 0;

if ($cart_id > 0) {
    global $conn;
    $sql = "DELETE FROM carts WHERE cart_id = $cart_id AND user_id = $user_id";
    mysqli_query($conn, $sql);
}

$next_cart_id = isset($_GET['cart_id']) ? (int)$_GET['cart_id'] : 0;
$next = 'cart.php';
if ($next_cart_id > 0) {
    $next .= '?cart_id=' . $next_cart_id;
}
header('Location: ' . base_url($next));
exit;
