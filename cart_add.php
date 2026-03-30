<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) { header('Location: ' . base_url('catalogue.php')); exit; }
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']);
$qty = (int)($_POST['qty'] ?? 1);
if ($qty < 1) $qty = 1;

require_once __DIR__ . '/includes/db.php';
$pkg = get_package_by_id($id);
if (!$pkg) { header('Location: ' . base_url('catalogue.php')); exit; }

$title = (string)($pkg['title'] ?? 'Package');
$price = (int)($pkg['price'] ?? 0);
$stock = (int)($pkg['stock'] ?? 0);
$maxPerUser = 10;
$allowedMax = max(0, min($maxPerUser, $stock));
if ($allowedMax <= 0) {
    $_SESSION['cart_notice'] = 'Sorry, this package is currently out of stock.';
    header('Location: ' . base_url('product_details.php?id=' . urlencode($id))); exit;
}

$found = false;
foreach ($_SESSION['cart'] as &$c) {
    if (($c['id'] ?? '') === $id) {
        $currentQty = (int)($c['qty'] ?? 1);
        $newQty = $currentQty + $qty;
        if ($newQty > $allowedMax) {
            $newQty = $allowedMax;
            $_SESSION['cart_notice'] = "Only {$allowedMax} left for this package (max {$maxPerUser} per user). Quantity was capped.";
        }
        $c['qty'] = max(1, $newQty);
        $c['title'] = $title;
        $c['price'] = $price;
        $found = true;
        break;
    }
}
if (!$found) {
    $qty = min($qty, $allowedMax);
    if ($qty < 1) $qty = 1;
    if ($qty >= $allowedMax && $allowedMax < $maxPerUser) {
        $_SESSION['cart_notice'] = "Only {$allowedMax} left for this package. Added {$qty}.";
    }
    $_SESSION['cart'][] = ['id' => $id, 'title' => $title, 'price' => $price, 'qty' => $qty];
}
header('Location: ' . base_url('cart.php'));
exit;
