<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['usertype'] ?? '') !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/config.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$cart_items = get_all_cart_items($user_id);
$notice = $_SESSION['cart_notice'] ?? null;
unset($_SESSION['cart_notice']);

$first_cart_id = !empty($cart_items) ? (int) $cart_items[0]['cart_id'] : 0;
$selected_cart_id = isset($_GET['cart_id']) ? (int) $_GET['cart_id'] : $first_cart_id;

$selected_item = null;
foreach ($cart_items as $ci) {
    if ((int) $ci['cart_id'] === $selected_cart_id) {
        $selected_item = $ci;
        break;
    }
}
if (!$selected_item && !empty($cart_items)) {
    $selected_item = $cart_items[0];
    $selected_cart_id = (int) $selected_item['cart_id'];
}

$selected_total = (float) ($selected_item['total_price'] ?? 0);
$deposit_percent = (float) PAYMENT_DEPOSIT_PERCENT;
$midterm_percent = (float) PAYMENT_MIDTERM_PERCENT;
$final_percent = (float) PAYMENT_FINAL_PERCENT;

$deposit_due_now = round($selected_total * ($deposit_percent / 100), 2);
$midterm_due = round($selected_total * ($midterm_percent / 100), 2);
$final_due = round($selected_total - $deposit_due_now - $midterm_due, 2);

$wedding_date = $selected_item['wedding_date'] ?? null;
$phase2_date = $wedding_date ? date('j F Y', strtotime($wedding_date . ' -180 days')) : 'Before event';
$phase3_date = $wedding_date ? date('j F Y', strtotime($wedding_date . ' -60 days')) : 'Before event';

$total_all = 0.0;
foreach ($cart_items as $item) {
    $total_all += (float) ($item['total_price'] ?? 0);
}

$selected_total = (float) ($selected_item['total_price'] ?? 0);

$extra_guest_total = (float) ($selected_item['extra_guest_total'] ?? 0);
$base_package_price = $selected_total - $extra_guest_total;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --gold: #000000;
            --dark: #1a1a1a;
            --soft-gray: #f4f4f4;
            --border: #e0e0e0;
            --text-secondary: #757575;
            --white: #ffffff;
            --timeline-line: #c0c0c0;
            --bg-global: #faf7f4;
            --bg-sidebar: #f0ede9;
            --bg-card: #ffffff;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-global);
            color: var(--dark);
            line-height: 1.5;
        }

        .wrapper {
            max-width: 1280px;
            margin: 40px auto;
            padding: 0 30px;
            display: flex;
            gap: 70px;
        }

        .service-column {
            flex: 1.8;
        }

        .back-nav {
            font-size: 0.85rem;
            letter-spacing: 1px;
            margin-bottom: 30px;
            display: block;
            text-decoration: none;
            color: var(--dark);
            opacity: 0.8;
            transition: 0.2s;
        }

        .back-nav:hover {
            opacity: 1;
        }

        h1 {
            font-weight: 300;
            font-size: 2.2rem;
            margin-bottom: 35px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .notice {
            margin-bottom: 18px;
            padding: 10px 12px;
            border: 1px solid #efcf7f;
            background: var(--bg-card);
            color: #5e4a15;
            font-size: 0.86rem;
        }

        .service-check-item {
            display: flex;
            gap: 20px;
            padding: 25px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 4px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.2s ease;
        }

        .service-check-item.active-item {
            border-color: var(--dark);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .remove-x {
            position: absolute;
            top: 15px;
            right: 15px;
            border: 1px solid var(--border);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 22px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .remove-x:hover {
            color: #000;
            border-color: #000;
        }

        .checkbox-wrapper {
            width: 32px;
            padding-top: 6px;
        }

        .checkbox-wrapper input[type="radio"] {
            width: 22px;
            height: 22px;
            accent-color: var(--dark);
            cursor: pointer;
        }

        .service-media {
            width: 120px;
            height: 120px;
            background-color: var(--bg-global);
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .service-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .service-info {
            flex: 1;
        }

        .service-info h3 {
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .spec-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px 25px;
            margin: 12px 0 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .spec-list span strong {
            color: var(--dark);
            font-weight: 500;
            margin-right: 4px;
        }

        .included-note {
            font-size: 0.8rem;
            color: var(--text-secondary);
            background: var(--bg-global);
            padding: 10px 12px;
            border-radius: 2px;
            margin-top: 12px;
        }

        .edit-trigger {
            background: none;
            border: 1px solid var(--dark);
            padding: 5px 18px;
            font-size: 0.75rem;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 12px;
            transition: 0.25s;
            border-radius: 2px;
        }

        .edit-trigger:hover {
            background: var(--dark);
            color: var(--white);
        }

        .line-price {
            margin-top: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #111;
        }

        .payment-sidebar {
            flex: 1.2;
            background: var(--bg-sidebar);
            padding: 40px 35px;
            height: fit-content;
            position: sticky;
            top: 40px;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .order-title {
            font-size: 1rem;
            margin-bottom: 30px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--dark);
            padding-bottom: 15px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .total-row {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 20px;
            margin-top: 10px;
            font-weight: 600;
            font-size: 1.15rem;
        }

        .timeline-phases {
            margin: 40px 0 35px;
            position: relative;
        }

        .phase-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 28px;
            position: relative;
        }

        .phase-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 13px;
            top: 32px;
            width: 2px;
            height: calc(100% + 8px);
            background: linear-gradient(to bottom, var(--gold) 40%, rgba(0, 0, 0, 0.1) 40%);
            z-index: 1;
        }

        .phase-marker {
            width: 30px;
            height: 30px;
            background-color: var(--bg-card);
            border: 2px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            z-index: 3;
            flex-shrink: 0;
        }

        .phase-content {
            flex: 1;
        }

        .phase-title {
            display: flex;
            justify-content: space-between;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .phase-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-checkout {
            width: 100%;
            background: var(--dark);
            color: var(--white);
            padding: 20px;
            border: none;
            font-size: 0.9rem;
            letter-spacing: 2.5px;
            cursor: pointer;
            transition: 0.3s;
            margin: 20px 0 15px;
        }

        .btn-checkout:hover {
            background: #333;
        }

        .clear-all-link {
            color: #777;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .secure-footer {
            font-size: 0.82rem;
            color: #9a9a9a;
            line-height: 1.55;
            margin-top: 6px;
        }

        .empty-card {
            background: var(--bg-sidebar);
            padding: 24px;
            border-radius: 4px;
        }


        .modal {
            display: none;
            /* 默认隐藏 */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--bg-card);
            /* 保持白色背景 */
            padding: 34px;
            width: 520px;
            max-width: 100%;
            border-radius: 2px;
        }

        .modal-content h2 {
            font-weight: 300;
            margin-bottom: 18px;
            font-size: 1.6rem;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.76rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            background-color: var(--bg-card);
            color: var(--dark);
        }

        .btn-save {
            width: 100%;
            background: var(--dark);
            color: var(--white);
            border: none;
            padding: 14px;
            cursor: pointer;
            letter-spacing: 1px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .btn-save:hover {
            background: #333;
        }

        .btn-cancel {
            width: 100%;
            background: none;
            border: none;
            margin-top: 12px;
            color: #666;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .btn-cancel:hover {
            color: #000;
        }

        @media (max-width: 1000px) {
            .wrapper {
                flex-direction: column;
            }

            .payment-sidebar {
                position: static;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header_user.php'; ?>

    <div class="wrapper">
        <div class="service-column">
            <a href="<?php echo base_url('catalogue.php'); ?>" class="back-nav">&lt; CONTINUE BROWSING</a>
            <h1>Your Wedding Selection</h1>

            <?php if ($notice): ?>
                <div class="notice"><?php echo htmlspecialchars($notice); ?></div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="empty-card" style="text-align: center;">
                    <p>Your cart is empty.</p>
                    <a href="<?php echo base_url('catalogue.php'); ?>">Browse packages</a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div
                        class="service-check-item <?php echo ((int) $item['cart_id'] === $selected_cart_id) ? 'active-item' : ''; ?>">
                        <a href="<?php echo base_url('cart_remove.php?cart_id=' . (int) $item['cart_id']); ?>" class="remove-x"
                            onclick="return confirm('Remove this package from your cart?');">✕</a>

                        <div class="checkbox-wrapper">
                            <input type="radio" name="selected_cart" value="<?php echo (int) $item['cart_id']; ?>" <?php echo ((int) $item['cart_id'] === $selected_cart_id) ? 'checked' : ''; ?>
                                onchange="window.location.href='<?php echo base_url('cart.php?cart_id='); ?>'+this.value;">
                        </div>

                        <div class="service-media">
                            <img src="<?php echo htmlspecialchars((string) ($item['image'] ?? '')); ?>"
                                alt="<?php echo htmlspecialchars((string) ($item['title'] ?? 'Wedding package')); ?>">
                        </div>

                        <div class="service-info">
                            <h3><?php echo htmlspecialchars((string) ($item['title'] ?? 'Wedding package')); ?></h3>
                            <div class="spec-list">
                                <span><strong>Guest count</strong> <?php echo (int) ($item['guest_count'] ?? 0); ?></span>
                                <span><strong>BRIDAL DRESS SIZE</strong>
                                    <?php echo htmlspecialchars((string) ($item['bride_size'] ?? '-')); ?></span>
                                <span><strong>GROOM SUIT SIZE</strong>
                                    <?php echo htmlspecialchars((string) ($item['groom_size'] ?? '-')); ?></span>
                            </div>

                            <?php if ((int) ($item['extra_guest_count'] ?? 0) > 0): ?>
                                <div class="included-note">
                                    <span>Extra guests</span>
                                    <?php echo (int) $item['extra_guest_count']; ?> × RM
                                    <?php echo number_format((float) ($item['extra_guest_price'] ?? 0), 2); ?> = RM
                                    <?php echo number_format((float) ($item['extra_guest_total'] ?? 0), 2); ?>
                                </div>
                            <?php endif; ?>

                            <button type="button" class="edit-trigger" data-cart-id="<?php echo (int) $item['cart_id']; ?>"
                                data-guest="<?php echo (int) ($item['guest_count'] ?? 0); ?>"
                                data-bride="<?php echo htmlspecialchars((string) ($item['bride_size'] ?? 'M')); ?>"
                                data-groom="<?php echo htmlspecialchars((string) ($item['groom_size'] ?? 'M')); ?>"
                                onclick="openModal(this)">EDIT DETAILS</button>


                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="payment-sidebar">
            <div class="order-title">Payment & Reservation Schedule</div>

            <div class="price-row">
                <span>Base Package Price</span>
                <span>RM <?php echo number_format($base_package_price, 2); ?></span>
            </div>

            <?php if ((int) ($selected_item['extra_guest_count'] ?? 0) > 0): ?>
                <div class="price-row" style="margin-bottom: 18px;">
                    <span>Extra Guests (<?php echo (int) $selected_item['extra_guest_count']; ?> pax * RM
                        <?php echo number_format((float) $selected_item['extra_guest_price'], 2); ?>)</span>
                    <span>RM <?php echo number_format($extra_guest_total, 2); ?></span>
                </div>

                <!-- style2
                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 15px; text-align: right;">
                    <?php echo (int) $selected_item['extra_guest_count']; ?> × RM
                    <?php echo number_format((float) $selected_item['extra_guest_price'], 2); ?>
                </div>
                 -->
                
            <?php endif; ?>



            <div class="price-row total-row">
                <span>Total (All Items)</span>
                <span>RM <?php echo number_format($total_all, 2); ?></span>
            </div>

            <div class="timeline-phases">
                <div class="phase-item">
                    <div class="phase-marker">1</div>
                    <div class="phase-content">
                        <div class="phase-title">
                            <span class="phase-name">Reservation Deposit
                                (<?php echo rtrim(rtrim(number_format($deposit_percent, 2), '0'), '.'); ?>%)</span>
                            <span class="phase-amount">RM <?php echo number_format($deposit_due_now, 2); ?></span>
                        </div>
                        <div class="phase-date">
                            <span class="badge deposit">PAYABLE TODAY</span>
                            <span>Non-refundable</span>
                        </div>
                    </div>
                </div>

                <div class="phase-item">
                    <div class="phase-marker">2</div>
                    <div class="phase-content">
                        <div class="phase-title">
                            <span class="phase-name">Second Payment
                                (<?php echo rtrim(rtrim(number_format($midterm_percent, 2), '0'), '.'); ?>%)</span>
                            <span class="phase-amount">RM <?php echo number_format($midterm_due, 2); ?></span>
                        </div>
                        <div class="phase-date">
                            <span><?php echo htmlspecialchars($phase2_date); ?></span>
                        </div>
                    </div>
                </div>

                <div class="phase-item">
                    <div class="phase-marker">3</div>
                    <div class="phase-content">
                        <div class="phase-title">
                            <span class="phase-name">Final Settlement
                                (<?php echo rtrim(rtrim(number_format($final_percent, 2), '0'), '.'); ?>%)</span>
                            <span class="phase-amount">RM <?php echo number_format($final_due, 2); ?></span>
                        </div>
                        <div class="phase-date">
                            <span><?php echo htmlspecialchars($phase3_date); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="price-row total-row">
                <span>Amount payable today</span>
                <span>RM <?php echo number_format($deposit_due_now, 2); ?></span>
            </div>

            <form action="<?php echo base_url('checkout.php'); ?>" method="GET">
                <input type="hidden" name="cart_id" value="<?php echo (int) $selected_cart_id; ?>">
                <button type="submit" class="btn-checkout" <?php echo $selected_cart_id > 0 ? '' : 'disabled'; ?>>PAY
                    DEPOSIT</button>
            </form>

            <a class="clear-all-link" href="<?php echo base_url('cart_clear_db.php'); ?>"
                onclick="return confirm('Clear all items from your cart?');">Clear all items</a>
            <p class="secure-footer">Reservation deposit is strictly non-refundable and non-transferable. By proceeding,
                you agree to the full wedding service contract.</p>
        </div>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Refine service</h2>
            <form action="<?php echo base_url('cart_update_db.php'); ?>" method="POST">
                <input type="hidden" name="cart_id" id="edit_cart_id" value="0">
                <input type="hidden" name="return_cart_id" id="return_cart_id"
                    value="<?php echo (int) $selected_cart_id; ?>">

                <div class="form-group">
                    <label>Guest count</label>
                    <input type="number" id="edit_guest_count" name="guest_count" min="1" max="300" required>
                </div>

                <div class="form-group">
                    <label>Bridal Dress Size</label>
                    <select id="edit_bride_size" name="bride_size" required>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="XXXL">XXXL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Groom Suit Size</label>
                    <select id="edit_groom_size" name="groom_size" required>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                        <option value="XXXL">XXXL</option>
                    </select>
                </div>

                <button class="btn-save" type="submit">UPDATE CART</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function openModal(btn) {
            document.getElementById('edit_cart_id').value = btn.getAttribute('data-cart-id') || '0';
            document.getElementById('edit_guest_count').value = btn.getAttribute('data-guest') || '1';
            document.getElementById('edit_bride_size').value = btn.getAttribute('data-bride') || 'M';
            document.getElementById('edit_groom_size').value = btn.getAttribute('data-groom') || 'M';
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>

</html>