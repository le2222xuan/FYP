<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
if (!isset($_SESSION['username']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/userDashboard.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="user-dashboard">
    <?php include 'includes/header_user.php'; ?>

    <div class="member-home">
        <header class="portal-header">
            <div class="header-title">
                <h1>Welcome back</h1>
                <p>Great to see you again!</p>
            </div>
            <div class="header-greeting">
                <div class="member-name">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'Guest'); ?></div>
            </div>
        </header>

        <div class="luxe-grid">
            <a href="<?php echo base_url('profile_edit.php'); ?>" class="grid-tile" data-index="01">
                <h2>ACCOUNT</h2>
                <div class="tile-sub">Personal Information · Security</div>
                <div class="tile-ornament"></div>
                <div class="hover-desc">Update your details</div>
                <span class="index-num"> —</span>
            </a>

            <a href="<?php echo base_url('order_history.php'); ?>" class="grid-tile" data-index="02">
                <h2>MY BOOKINGS</h2>
                <div class="tile-sub">Booking History</div>
                <div class="tile-ornament"></div>
                <div class="hover-desc">Review Order & Complete Payment</div>
                <span class="index-num"> —</span>
            </a>

            <a href="<?php echo base_url('progress.php'); ?>" class="grid-tile" data-index="03">
                <h2>WEDDING</h2>
                <div class="tile-sub">Preparation Progress</div>
                <div class="tile-ornament"></div>
                <div class="hover-desc">View Preparation Status & Details</div>
                <span class="index-num"> —</span>
            </a>

            <a href="<?php echo base_url('contact_us.php'); ?>" class="grid-tile" data-index="04">
                <h2>Contact Us</h2>
                <div class="tile-sub">Get in Touch</div>
                <div class="tile-ornament"></div>
                <div class="hover-desc">Have questions? We're here to help!</div>
                <span class="index-num"> —</span>
            </a>
        </div>

        <footer class="portal-footer" style="background-color: white;">
            <div class="footer-links"><span>LOGOUT</span></div>
        </footer>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>