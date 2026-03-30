<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['username']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';
$user_id = (int) ($_SESSION['user_id'] ?? 0);

// Get specific cart item by cart_id
$cart_id = isset($_GET['cart_id']) ? (int)$_GET['cart_id'] : 0;
$cart = null;

if ($cart_id > 0) {
    $cart = get_cart_item_by_id($cart_id);
}

if (!$cart || (int)$cart['user_id'] !== $user_id) {
    header('Location: ' . base_url('cart.php'));
    exit;
}

$userProfile = $user_id ? get_user_by_id($user_id) : null;
$total = (float) ($cart['total_price'] ?? 0);
if ($total === 0) {
    $total = (float) ($cart['price'] ?? 0);
}

$deposit_percent = (float) PAYMENT_DEPOSIT_PERCENT;
$midterm_percent = (float) PAYMENT_MIDTERM_PERCENT;
$final_percent = (float) PAYMENT_FINAL_PERCENT;

$deposit_due_now = round($total * ($deposit_percent / 100), 2);
$midterm_due = round($total * ($midterm_percent / 100), 2);
$final_due = round($total - $deposit_due_now - $midterm_due, 2);

$checkout_error = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

$minDate = date('Y-m-d', strtotime('+30 days'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/checkout.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <?php include 'includes/header_user.php'; ?>

    <div class="chk-wrapper">
        <div class="chk-container">
            <!-- Left: Form -->
            <div class="chk-form-panel">
                <form action="<?php echo base_url('checkout_process.php'); ?>" method="POST" id="checkoutForm" novalidate>
                    <input type="hidden" name="cart_id" value="<?php echo (int)$cart['cart_id']; ?>">
                    <input type="hidden" name="deposit_percent" value="<?php echo htmlspecialchars((string)$deposit_percent); ?>">
                    <input type="hidden" name="midterm_percent" value="<?php echo htmlspecialchars((string)$midterm_percent); ?>">
                    <input type="hidden" name="final_percent" value="<?php echo htmlspecialchars((string)$final_percent); ?>">

                    <?php if (!empty($checkout_error)): ?>
                    <div class="checkout-error"><?php echo htmlspecialchars($checkout_error); ?></div>
                    <?php endif; ?>
                    
                    <h2 class="chk-heading">Event & Billing Details</h2>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-input" placeholder="Your full name"
                            value="<?php echo $userProfile ? htmlspecialchars($_SESSION['username']) : ''; ?>" 
                            data-required="true" data-field="Full Name">
                        <span class="error-msg"></span>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-input" placeholder="your@email.com"
                            value="<?php echo $userProfile ? htmlspecialchars($userProfile['email'] ?? '') : ''; ?>" 
                            data-required="true" data-field="Email">
                        <span class="error-msg"></span>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label>Phone <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-input" placeholder="012-3456789"
                            value="<?php echo $userProfile ? htmlspecialchars($userProfile['phone'] ?? '') : ''; ?>" 
                            data-required="true" data-field="Phone">
                        <span class="error-msg"></span>
                    </div>

                    <!-- Wedding Date -->
                    <div class="form-group">
                        <label>Wedding Date <span class="required">*</span> <span class="hint">(Minimum 30 days from today)</span></label>
                        <input type="date" name="wedding_date" id="wedding_date" class="form-input" min="<?php echo $minDate; ?>" 
                            data-required="true" data-field="Wedding Date">
                        <span class="error-msg"></span>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label>Street Address <span class="required">*</span></label>
                        <input type="text" name="address" class="form-input" placeholder="Street address"
                            data-required="true" data-field="Address">
                        <span class="error-msg"></span>
                    </div>

                    <!-- City / Postcode -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" class="form-input" placeholder="e.g. Melaka"
                                data-required="true" data-field="City">
                            <span class="error-msg"></span>
                        </div>
                        <div class="form-group">
                            <label>Postcode <span class="required">*</span></label>
                            <input type="text" name="postcode" class="form-input" placeholder="75000"
                                data-required="true" data-field="Postcode">
                            <span class="error-msg"></span>
                        </div>
                    </div>

                    <!-- State -->
                    <div class="form-group">
                        <label>State <span class="required">*</span></label>
                        <input type="text" name="state" class="form-input" placeholder="e.g. Melaka"
                            data-required="true" data-field="State">
                        <span class="error-msg"></span>
                    </div>

                    <div class="form-group">
                        <label>Order Notes / Special Requests</label>
                        <textarea name="order_notes" class="form-input notes-area" placeholder="Any initial requirements? （例如喜欢的颜色/guest的dietary)"></textarea>
                    </div>

                    <h2 class="chk-heading" style="margin-top: 2rem;">Payment Information</h2>

                    <!-- Card Name -->
                    <div class="form-group">
                        <label>Name on Card <span class="required">*</span></label>
                        <input type="text" name="card_name" class="form-input" placeholder="Name on card"
                            data-required="true" data-field="Card Name">
                        <span class="error-msg"></span>
                    </div>

                    <!-- Card Number / CVC -->
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Card Number <span class="required">*</span></label>
                            <input type="text" name="card_number" class="form-input" placeholder="1234 5678 9012 3456" 
                                maxlength="19" data-required="true" data-field="Card Number" data-card="true">
                            <span class="error-msg"></span>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>CVC <span class="required">*</span></label>
                            <input type="text" name="cvc" class="form-input" placeholder="123" 
                                maxlength="4" data-required="true" data-field="CVC" data-cvc="true">
                            <span class="error-msg"></span>
                        </div>
                    </div>

                    <!-- Expiry Month / Year -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Month <span class="required">*</span></label>
                            <select name="expiry_month" class="form-input" data-required="true" data-field="Expiry Month">
                                <option value="">MM</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <span class="error-msg"></span>
                        </div>
                        <div class="form-group">
                            <label>Expiry Year <span class="required">*</span></label>
                            <select name="expiry_year" class="form-input" data-required="true" data-field="Expiry Year">
                                <option value="">YYYY</option>
                                <?php for ($y = date('Y'); $y <= date('Y') + 10; $y++): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="error-msg"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Confirm & Place Booking</button>
                </form>
            </div>

            <!-- Right: Summary -->
            <div class="chk-summary-panel">
                <div class="summary-card">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-item">
                        <span class="summary-label"><?php echo htmlspecialchars($cart['title'] ?? 'Package'); ?></span>
                        <span class="summary-price">RM <?php echo number_format((float)($cart['price'] ?? 0), 2); ?></span>
                    </div>

                    <?php if ((int)($cart['extra_guest_count'] ?? 0) > 0): ?>
                    <div class="summary-item">
                        <span class="summary-label">Extra Guests</span>
                        <span class="summary-sublabel"><?php echo (int)($cart['extra_guest_count']); ?> × RM <?php echo number_format((float)($cart['extra_guest_price'] ?? 0), 2); ?></span>
                        <span class="summary-price">RM <?php echo number_format((float)($cart['extra_guest_total'] ?? 0), 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-divider"></div>

                    <div class="due-now-box">
                        <span class="due-now-label">Pay Today</span>
                        <span class="due-now-value">RM <?php echo number_format($deposit_due_now, 2); ?></span>
                    </div>

                    <div class="summary-item payment-lines">
                        <div class="summary-row"><span>Deposit (<?php echo rtrim(rtrim(number_format($deposit_percent, 2), '0'), '.'); ?>%)</span><span>RM <?php echo number_format($deposit_due_now, 2); ?></span></div>
                        <div class="summary-row"><span>Mid-term (<?php echo rtrim(rtrim(number_format($midterm_percent, 2), '0'), '.'); ?>%)</span><span>RM <?php echo number_format($midterm_due, 2); ?></span></div>
                        <div class="summary-row"><span>Final Balance (<?php echo rtrim(rtrim(number_format($final_percent, 2), '0'), '.'); ?>%)</span><span>RM <?php echo number_format($final_due, 2); ?></span></div>
                    </div>

                    <div class="summary-total">
                        <span>Total Amount</span>
                        <span class="total-price">RM <?php echo number_format($total, 2); ?></span>
                    </div>

                    <p class="summary-note">* Demo project. No real charge.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <style>
        body {
            background-color: #faf7f4;
        }

        .chk-wrapper {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .chk-container {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 2rem;
            background: white;
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .chk-form-panel {
            padding: 3rem;
        }

        .chk-summary-panel {
            padding: 3rem;
            background: #faf7f4;
            border-left: 1px solid #e8e3de;
        }

        .chk-heading {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1a1a1a;
            letter-spacing: 0.3px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .required {
            color: #d32f2f;
        }

        .hint {
            font-size: 0.75rem;
            color: #999;
            font-weight: normal;
        }

        .checkout-error {
            border: 1px solid #d32f2f;
            background: #ffebee;
            color: #b71c1c;
            padding: 0.75rem 0.9rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            animation: shake 0.3s ease;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 2px;
            font-size: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #000;
            box-shadow: inset 0 0 0 1px #000;
        }

        .form-input.error {
            border-color: #d32f2f;
            background: #ffebee;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-msg {
            display: block;
            font-size: 0.75rem;
            color: #d32f2f;
            margin-top: 0.25rem;
            min-height: 1rem;
        }

        /* Summary */
        .summary-card {
            position: sticky;
            top: 2rem;
        }

        .summary-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #000;
            letter-spacing: 0.5px;
        }

        .summary-item {
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .summary-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .summary-sublabel {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .summary-price {
            display: block;
            font-size: 0.95rem;
            color: #333;
            margin-top: 0.25rem;
        }

        .summary-divider {
            height: 1px;
            background: #ddd;
            margin: 1rem 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            font-weight: 700;
        }

        .total-price {
            font-size: 1.3rem;
            color: #000;
        }

        .summary-note {
            font-size: 0.75rem;
            color: #999;
            margin-top: 1rem;
            font-style: italic;
        }

        .notes-area {
            min-height: 92px;
            resize: vertical;
        }

        .due-now-box {
            border: 1px solid #000;
            background: #fff;
            padding: 0.7rem 0.8rem;
            margin-bottom: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .due-now-label {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .due-now-value {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .payment-lines .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.84rem;
            color: #555;
            margin-bottom: 0.35rem;
            gap: 12px;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: #000;
            color: white;
            border: none;
            border-radius: 2px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 2rem;
            transition: all 0.2s ease;
        }

        .btn-submit:hover:not(:disabled) {
            background: #333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .chk-container {
                grid-template-columns: 1fr;
            }

            .chk-summary-panel {
                border-left: none;
                border-top: 1px solid #e8e3de;
            }

            .summary-card {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .chk-form-panel, .chk-summary-panel {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        (function() {
            const form = document.getElementById('checkoutForm');
            const inputs = form.querySelectorAll('[data-required="true"]');

            function validateField(field) {
                const value = field.value.trim();
                const errorMsg = field.parentElement.querySelector('.error-msg');
                const fieldName = field.getAttribute('data-field') || field.name;
                let isValid = true;
                let msg = '';

                if (!value) {
                    isValid = false;
                    msg = `Please enter ${fieldName.toLowerCase()}`;
                } else if (field.type === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        msg = 'Please enter a valid email address';
                    }
                } else if (field.getAttribute('data-card')) {
                    // Remove spaces and validate card number
                    const cardNum = value.replace(/\s/g, '');
                    if (!/^\d{13,19}$/.test(cardNum)) {
                        isValid = false;
                        msg = 'Invalid card number (13-19 digits)';
                    }
                } else if (field.getAttribute('data-cvc')) {
                    if (!/^\d{3,4}$/.test(value)) {
                        isValid = false;
                        msg = 'Invalid CVC (3-4 digits)';
                    }
                }

                if (!isValid) {
                    field.classList.add('error');
                    if (errorMsg) errorMsg.textContent = msg;
                } else {
                    field.classList.remove('error');
                    if (errorMsg) errorMsg.textContent = '';
                }

                return isValid;
            }

            // Real-time validation
            inputs.forEach(function(field) {
                field.addEventListener('blur', function() { validateField(this); });
                field.addEventListener('change', function() { validateField(this); });
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                let allValid = true;
                inputs.forEach(function(field) {
                    if (!validateField(field)) allValid = false;
                });

                // Validate wedding date
                const weddingDate = document.getElementById('wedding_date');
                if (weddingDate.value) {
                    const selectedDate = new Date(weddingDate.value);
                    const minDate = new Date('<?php echo $minDate; ?>');
                    if (selectedDate < minDate) {
                        allValid = false;
                        validateField(weddingDate);
                        const errorMsg = weddingDate.parentElement.querySelector('.error-msg');
                        if (errorMsg) errorMsg.textContent = 'Wedding date must be at least 30 days from today';
                    }
                }

                if (!allValid) {
                    e.preventDefault();
                }
            });
        })();
    </script>
</body>

</html>