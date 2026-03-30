<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['usertype'] ?? '') !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('cart.php'));
    exit;
}

require_once __DIR__ . '/config.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$cart_id = (int) ($_POST['cart_id'] ?? 0);
$return_cart_id = (int) ($_POST['return_cart_id'] ?? $cart_id);
$guest_count = (int) ($_POST['guest_count'] ?? 0);
$bride_size = strtoupper(trim((string) ($_POST['bride_size'] ?? '')));
$groom_size = strtoupper(trim((string) ($_POST['groom_size'] ?? '')));

if ($guest_count < 1) {
    $guest_count = 1;
}
if ($guest_count > 300) {
    $guest_count = 300;
}

$allowed_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
if (!in_array($bride_size, $allowed_sizes, true)) {
    $bride_size = 'M';
}
if (!in_array($groom_size, $allowed_sizes, true)) {
    $groom_size = 'M';
}

if ($cart_id <= 0 || $user_id <= 0) {
    header('Location: ' . base_url('cart.php'));
    exit;
}

$safe_bride = mysqli_real_escape_string($conn, $bride_size);
$safe_groom = mysqli_real_escape_string($conn, $groom_size);

$sql = "UPDATE carts
        SET guest_count = $guest_count,
            bride_size = '$safe_bride',
            groom_size = '$safe_groom',
            updated_at = NOW()
        WHERE cart_id = $cart_id AND user_id = $user_id";

if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) >= 0) {
    $_SESSION['cart_notice'] = 'Cart details updated.';
} else {
    $_SESSION['cart_notice'] = 'Unable to update cart details right now.';
}

header('Location: ' . base_url('cart.php?cart_id=' . $return_cart_id));
exit;
