<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

$uid = (int) $_SESSION['user_id'];
require_once __DIR__ . '/config.php';

// Get specific cart item by cart_id
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$cart = null;
if ($cart_id > 0) {
    $cart = get_cart_item_by_id($cart_id);
}

if (!$cart || (int)$cart['user_id'] !== $uid || !$cart['package_id']) {
    header('Location: ' . base_url('cart.php'));
    exit;
}

$name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
$email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
$phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
$address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
$city = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
$postcode = mysqli_real_escape_string($conn, trim($_POST['postcode'] ?? ''));
$state = mysqli_real_escape_string($conn, trim($_POST['state'] ?? ''));
$order_notes = mysqli_real_escape_string($conn, trim($_POST['order_notes'] ?? ''));
$wd = !empty($_POST['wedding_date']) ? mysqli_real_escape_string($conn, $_POST['wedding_date']) : null;

// Validate wedding date (minimum 30 days from now)
if ($wd) {
    $minDate = date('Y-m-d', strtotime('+30 days'));
    if ($wd < $minDate) {
        $_SESSION['cart_notice'] = 'Wedding date must be at least 30 days from today.';
        $_SESSION['checkout_error'] = 'Wedding date must be at least 30 days from today.';
        header('Location: ' . base_url('checkout.php?cart_id=' . $cart_id . '&err=date'));
        exit;
    }
}

$total = (float) ($cart['total_price'] ?? 0);
if ($total <= 0) {
    $total = (int) ($cart['price'] ?? 0);
}

if (!$name || !$email || !$phone || !$address || !$city || !$postcode || !$state || $total <= 0) {
    $_SESSION['checkout_error'] = 'Please fill out all required information.';
    header('Location: ' . base_url('checkout.php?cart_id=' . $cart_id . '&err=missing'));
    exit;
}

$deposit_percent = isset($_POST['deposit_percent']) ? (float)$_POST['deposit_percent'] : (float)PAYMENT_DEPOSIT_PERCENT;
$midterm_percent = isset($_POST['midterm_percent']) ? (float)$_POST['midterm_percent'] : (float)PAYMENT_MIDTERM_PERCENT;
$final_percent = isset($_POST['final_percent']) ? (float)$_POST['final_percent'] : (float)PAYMENT_FINAL_PERCENT;

if ($deposit_percent < 0) $deposit_percent = 0;
if ($midterm_percent < 0) $midterm_percent = 0;
if ($final_percent < 0) $final_percent = 0;

$percent_sum = $deposit_percent + $midterm_percent + $final_percent;
if ($percent_sum <= 0.0) {
    $deposit_percent = (float)PAYMENT_DEPOSIT_PERCENT;
    $midterm_percent = (float)PAYMENT_MIDTERM_PERCENT;
    $final_percent = (float)PAYMENT_FINAL_PERCENT;
}

$deposit_amount = round($total * ($deposit_percent / 100), 2);
$midterm_amount = round($total * ($midterm_percent / 100), 2);
$final_amount = round($total - $deposit_amount - $midterm_amount, 2);

$deposit_due_date = date('Y-m-d');
$midterm_due_date = $wd ? date('Y-m-d', strtotime($wd . ' -180 days')) : null;
$final_due_date = $wd ? date('Y-m-d', strtotime($wd . ' -60 days')) : null;

mysqli_begin_transaction($conn);
try {
    // Get package to verify stock
    $pid = mysqli_real_escape_string($conn, $cart['package_id']);
    $pkgRes = mysqli_query($conn, "SELECT stock, price FROM packages WHERE id = '$pid' AND is_deleted = 0 LIMIT 1");
    if (!$pkgRes || mysqli_num_rows($pkgRes) !== 1) {
        throw new Exception('package_not_found');
    }
    $pkg = mysqli_fetch_assoc($pkgRes);
    if ($pkg['stock'] <= 0) {
        throw new Exception('out_of_stock');
    }
    
    // Decrement stock
    mysqli_query($conn, "UPDATE packages SET stock = stock - 1 WHERE id = '$pid' AND stock > 0");

    // Create order
    $guest_count = (int)($cart['guest_count'] ?? 100);
    $extra_guests = (int)($cart['extra_guest_count'] ?? 0);
    $extra_total = (float)($cart['extra_guest_total'] ?? 0);

    $has_order_notes_col = false;
    $orderNotesColRes = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'order_notes'");
    if ($orderNotesColRes && mysqli_num_rows($orderNotesColRes) > 0) {
        $has_order_notes_col = true;
    }

    $order_notes_col = $has_order_notes_col ? ', order_notes' : '';
    $order_notes_val = $has_order_notes_col ? ", '$order_notes'" : '';

    $sql = "INSERT INTO orders (
                user_id, full_name, email, phone, address, city, postcode, state,
                wedding_date, guest_count, extra_guest_count, extra_guest_total,
                total, deposit_amount, midterm_amount, final_amount,
                deposit_due_date, midterm_due_date, final_due_date,
                payment_status, status$order_notes_col
            ) VALUES (
                $uid, '$name', '$email', '$phone', '$address', '$city', '$postcode', '$state',
                " . ($wd ? "'$wd'" : "NULL") . ",
                $guest_count, $extra_guests, $extra_total,
                $total, $deposit_amount, $midterm_amount, $final_amount,
                '$deposit_due_date', " . ($midterm_due_date ? "'$midterm_due_date'" : "NULL") . ", " . ($final_due_date ? "'$final_due_date'" : "NULL") . ",
                'partial', 'Confirmed'$order_notes_val
            )";
    if (!mysqli_query($conn, $sql))
        throw new Exception('order_insert: ' . mysqli_error($conn));
    $oid = mysqli_insert_id($conn);

    // Verify payment card
    $card_name = mysqli_real_escape_string($conn, trim($_POST['card_name'] ?? ''));
    $card_number = preg_replace('/\s+/', '', trim($_POST['card_number'] ?? ''));
    $expiry_month = mysqli_real_escape_string($conn, trim($_POST['expiry_month'] ?? ''));
    $expiry_year = mysqli_real_escape_string($conn, trim($_POST['expiry_year'] ?? ''));
    $cvc = mysqli_real_escape_string($conn, trim($_POST['cvc'] ?? ''));

    $checkCardSql = "SELECT * FROM dummy_cards WHERE card_number = '$card_number' 
    AND card_holder = '$card_name' 
    AND expiry_month = '$expiry_month' 
    AND expiry_year = '$expiry_year' 
    AND cvc = '$cvc'
    AND is_active = 1";

    $cardResult = mysqli_query($conn, $checkCardSql);
    if (!$cardResult || mysqli_num_rows($cardResult) !== 1) {
        throw new Exception('invalid_card');
    }

    // Insert order item
    $pkg_title = mysqli_real_escape_string($conn, $cart['title'] ?? 'Package');
    $insertItemSql = "INSERT INTO order_items (order_id, item_type, item_name, package_id, quantity, unit_price, subtotal) 
                      VALUES ($oid, 'package', '$pkg_title', '$pid', 1, $total, $total)";
    if (!mysqli_query($conn, $insertItemSql)) {
        throw new Exception('order_item_insert: ' . mysqli_error($conn));
    }

    // Record payment
    $payment_num = 'PAY-' . $oid . '-' . date('YmdHis');
    $payment_sql = "INSERT INTO payments (order_id, payment_number, payment_type, amount, payment_method, payment_status, paid_at) 
                    VALUES ($oid, '$payment_num', 'deposit', $deposit_amount, 'credit_card', 'success', NOW())";
    if (!mysqli_query($conn, $payment_sql)) {
        throw new Exception('payment_insert: ' . mysqli_error($conn));
    }

    // Clear this cart item after successful order
    mysqli_query($conn, "DELETE FROM carts WHERE cart_id = $cart_id");



    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log('Checkout error: ' . $e->getMessage());
    
    if (strpos($e->getMessage(), 'out_of_stock') !== false) {
        $_SESSION['cart_notice'] = 'Sorry, this package is no longer available.';
        header('Location: ' . base_url('cart.php'));
    } elseif (strpos($e->getMessage(), 'invalid_card') !== false) {
        $_SESSION['checkout_error'] = 'Invalid card details. Please make sure your card information matches our records.';
        header('Location: ' . base_url('checkout.php?cart_id=' . $cart_id . '&err=invalid_card'));
    } else {
        $_SESSION['checkout_error'] = 'Payment failed due to a system error. Please try again.';
        header('Location: ' . base_url('checkout.php?cart_id=' . $cart_id . '&err=db'));
    }
    exit;
}

$_SESSION['order_success'] = $oid;
header('Location: ' . base_url('order_confirmation.php'));
exit;