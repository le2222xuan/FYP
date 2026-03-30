<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';

$user_id = (int) $_SESSION['user_id'];

// Clear cart
if (clear_cart($user_id)) {
    $_SESSION['cart_db_count'] = 0;
    $_SESSION['cart_notice'] = 'Cart cleared.';
}

header('Location: ' . base_url('cart.php'));
exit;
