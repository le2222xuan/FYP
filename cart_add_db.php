<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: ' . base_url('catalogue.php'));
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'user') {
    $_SESSION['redirect_after_login'] = base_url('product_details.php?id=' . urlencode($_POST['id']));
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';

$id          = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']);
$guest_count = (int)($_POST['guest_count'] ?? 100);
if ($guest_count < 1)   $guest_count = 1;
if ($guest_count > 300) $guest_count = 300;

$bride_size = preg_replace('/[^A-Z]/', '', strtoupper($_POST['bride_size'] ?? ''));
$groom_size = preg_replace('/[^A-Z]/', '', strtoupper($_POST['groom_size'] ?? ''));

$user_id = (int) $_SESSION['user_id'];

// Get package
$pkg = get_package_by_id($id);
if (!$pkg) {
    $_SESSION['cart_notice'] = 'Package not found.';
    header('Location: ' . base_url('catalogue.php'));
    exit;
}

if ((int)($pkg['stock'] ?? 0) <= 0) {
    $_SESSION['cart_notice'] = 'Sorry, this package is currently out of stock.';
    header('Location: ' . base_url('product_details.php?id=' . urlencode($id)));
    exit;
}

// Add/update cart in database
if (add_package_to_cart($user_id, $id, $guest_count, $bride_size, $groom_size)) {
    $_SESSION['cart_db_count'] = 1;
    $_SESSION['cart_notice'] = 'Package added to your cart!';
    header('Location: ' . base_url('cart.php'));
} else {
    $_SESSION['cart_notice'] = 'Error adding package to cart. Please try again.';
    header('Location: ' . base_url('product_details.php?id=' . urlencode($id)));
}
exit;
