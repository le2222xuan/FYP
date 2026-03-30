<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
$oid = (int)($_SESSION['order_success'] ?? 0);
unset($_SESSION['order_success']);
if (!$oid) { header('Location: ' . base_url('index.php')); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/order-confirmation.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'includes/header_user.php'; ?>
<div class="thanks">
    <h1><i class="fas fa-check-circle" style="color:#2e7d32;"></i> Thank You</h1>
    <p>Your booking has been received successfully.</p>
    <p class="order-id">Order #<?php echo $oid; ?></p>
    <p>We will contact you shortly to confirm the details.</p>
    <div class="thanks-actions">
        <a href="<?php echo base_url('order_history.php'); ?>">View Order History</a>
        <a href="<?php echo base_url('catalogue.php'); ?>">Browse More Packages</a>
        <a href="<?php echo base_url('index.php'); ?>" class="btn-secondary">Back to Home</a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
