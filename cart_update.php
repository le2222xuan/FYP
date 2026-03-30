<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['cart'])) {
    header('Location: ' . base_url('cart.php'));
    exit;
}

// Update quantity using + and − buttons
if (!empty($_POST['delta']) && is_array($_POST['delta'])) {

    foreach ($_POST['delta'] as $idx => $change) {
        $idx = (int)$idx;
        $change = (int)$change;

        if (!isset($_SESSION['cart'][$idx])) continue;

        $currentQty = (int)($_SESSION['cart'][$idx]['qty'] ?? 1);
        $newQty = $currentQty + $change;

        // ✅ Auto remove item if quantity goes below 1
        if ($newQty <= 0) {
            array_splice($_SESSION['cart'], $idx, 1);
            continue;
        }

        // Optional: limit quantity by stock & max per user
        require_once __DIR__ . '/includes/db.php';

        $maxPerUser = 10;
        $id = $_SESSION['cart'][$idx]['id'] ?? null;
        $pkg = $id ? get_package_by_id($id) : null;
        $stock = (int)($pkg['stock'] ?? $maxPerUser);

        $allowedMax = min($maxPerUser, $stock);

        if ($newQty > $allowedMax) {
            $newQty = $allowedMax;
            $_SESSION['cart_notice'] = "Only {$allowedMax} left for this package.";
        }

        $_SESSION['cart'][$idx]['qty'] = $newQty;
    }
}

header('Location: ' . base_url('cart.php'));
exit;
