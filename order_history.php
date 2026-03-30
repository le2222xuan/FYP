<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['usertype'] ?? '') !== 'user')) {
    header('Location: ' . base_url('login.php')); exit;
}
require_once __DIR__ . '/includes/db.php';
$user_id = (int)($_SESSION['user_id'] ?? 0);
$orders = $user_id ? get_orders_by_user($user_id) : [];
$active_page = 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/mySidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/order-history.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/header_user.php'; ?>


    <main class="app-main">
        <div class="content-card order-history-page">
            <div class="order-history-head">
                <div>
                    <h1 class="order-history-heading">Order History</h1>
                    <p class="order-history-sub">Your bookings, payments, and package details.</p>
                </div>
                <a href="<?php echo base_url('catalogue.php'); ?>" class="btn-primary">Browse Packages</a>
            </div>

            <?php if (empty($orders)): ?>
            <div class="order-history-empty">
                <i class="fas fa-receipt"></i>
                <p>You have no orders yet.</p>
            </div>
            <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $o): ?>
                <article class="order-card">
                    <div class="order-card-header">
                        <div>
                            <div class="order-id">Order #<?php echo (int) $o['order_id']; ?></div>
                            <?php
                            $pkg_name = 'Package';
                            foreach ($o['items'] as $_item) {
                                if (($_item['item_type'] ?? '') === 'package' && !empty($_item['item_name'])) {
                                    $pkg_name = $_item['item_name'];
                                    break;
                                }
                            }
                            if ($pkg_name === 'Package' && !empty($o['items'][0]['item_name'])) {
                                $pkg_name = $o['items'][0]['item_name'];
                            }
                            ?>
                            <div name="order-package" class="order-package"><?php echo htmlspecialchars($pkg_name); ?></div>
                            <div class="order-date">Placed on <?php echo date('d M Y', strtotime($o['created_at'])); ?></div>
                        </div>
                        <span class="order-status status-<?php echo strtolower(htmlspecialchars($o['status'])); ?>"><?php echo htmlspecialchars($o['status']); ?></span>
                    </div>

                    <div class="order-summary-grid">
                        <div class="summary-tile">
                            <span class="summary-label">Wedding Date</span>
                            <strong><?php echo $o['wedding_date'] ? date('d M Y', strtotime($o['wedding_date'])) : 'Not set'; ?></strong>
                        </div>
                        <div class="summary-tile">
                            <span class="summary-label">Venue</span>
                            <strong><?php echo !empty($o['hall_name']) ? htmlspecialchars($o['hall_name']) : 'No venue selected'; ?></strong>
                        </div>
                        <div class="summary-tile">
                            <span class="summary-label">Guests</span>
                            <strong><?php echo number_format((int) ($o['guest_count'] ?? 0)); ?></strong>
                        </div>
                        <div class="summary-tile">
                            <span class="summary-label">Payment</span>
                            <strong><?php echo strtoupper(htmlspecialchars((string) ($o['payment_status'] ?? 'unpaid'))); ?></strong>
                        </div>
                    </div>

                    <div class="order-card-body">
                        <div class="order-financials">
                            <p class="order-meta"><strong>Total:</strong> RM <?php echo number_format((float) $o['total'], 2); ?></p>
                            <p class="order-meta"><strong>Paid:</strong> RM <?php echo number_format((float) ($o['paid_total'] ?? 0), 2); ?></p>
                            <p class="order-meta"><strong>Balance:</strong> RM <?php echo number_format(max(0, (float) $o['total'] - (float) ($o['paid_total'] ?? 0)), 2); ?></p>
                        </div>

                        <div class="order-section">
                            <h2>Items</h2>
                            <?php if (!empty($o['items'])): ?>
                            <ul class="order-items">
                                <?php foreach ($o['items'] as $it): ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) $it['item_name']); ?></strong>
                                        <?php if (!empty($it['description'])): ?>
                                        <span><?php echo htmlspecialchars((string) $it['description']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-item-price">
                                        <span><?php echo (int) ($it['quantity'] ?? 1); ?> × RM <?php echo number_format((float) ($it['unit_price'] ?? 0), 2); ?></span>
                                        <strong>RM <?php echo number_format((float) ($it['subtotal'] ?? 0), 2); ?></strong>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="section-empty">No order items recorded yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="order-section">
                            <h2>Payments</h2>
                            <?php if (!empty($o['payments'])): ?>
                            <ul class="payment-list">
                                <?php foreach ($o['payments'] as $payment): ?>
                                <li>
                                    <span><?php echo ucfirst(htmlspecialchars((string) $payment['payment_type'])); ?></span>
                                    <span>RM <?php echo number_format((float) $payment['amount'], 2); ?></span>
                                    <span class="payment-pill payment-<?php echo strtolower(htmlspecialchars((string) $payment['payment_status'])); ?>"><?php echo strtoupper(htmlspecialchars((string) $payment['payment_status'])); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p class="section-empty">No payment records yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
