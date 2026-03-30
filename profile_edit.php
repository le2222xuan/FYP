<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

// Login
$is_logged_in = isset($_SESSION['username']) || isset($_SESSION['user_username']);
if (!$is_logged_in || ($_SESSION['usertype'] ?? '') !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/includes/db.php';
$user_id = (int)($_SESSION['user_id'] ?? 0);
$user = get_user_by_id($user_id);

if (!$user) {
    header('Location: ' . base_url('user_dashboard.php?err=notfound'));
    exit;
}

$msg = '';
$msg_class = '';
if (isset($_GET['saved'])) {
    $msg = 'Profile updated successfully.';
    $msg_class = 'msg-success';
} elseif (isset($_GET['err'])) {
    $msg = strlen($_GET['err']) > 2 ? htmlspecialchars($_GET['err']) : 'Update failed. Please try again.';
    $msg_class = 'msg-error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/profile-edit.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --dark: #000000;
            --soft-gray: #f8f8f8;
            --border: #e0e0e0;
            --text-muted: #888888;
            --gold-accent: #b0935a;
        }
        
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-color: #fff;
            color: var(--dark);
            -webkit-font-smoothing: antialiased;
            margin: 0;
            padding: 0;
            overflow-y: scroll;
        }
        
        .container {
            max-width: 1100px;
            margin: 60px auto;
            padding: 0 40px;
            display: flex;
            gap: 100px;
        }
        
        /* Sidebar Navigation */
        .nav-sidebar {
            width: 220px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 60px;
            align-self: flex-start;
            height: fit-content;
        }
        
        .brand-area {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 50px;
        }
        
        .back-btn {
            cursor: pointer;
            color: var(--dark);
            text-decoration: none;
            transition: transform 0.25s ease;
            display: flex;
            align-items: center;
        }
        
        .back-btn:hover {
            transform: translateX(-6px);
        }
        
        .nav-sidebar h1 {
            font-family: serif;
            font-size: 1.8rem;
            font-weight: 400;
            margin: 0;
        }
        
        .menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-item {
            font-size: 0.8rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 18px 0;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
            border-bottom: 1px solid transparent;
            width: fit-content;
        }
        
        .menu-item:hover {
            color: var(--dark);
        }
        
        .menu-item.active {
            color: var(--dark);
            border-bottom: 1px solid var(--dark);
            font-weight: 500;
        }
        
        .sign-out {
            margin-top: auto;
            padding-top: 30px;
            border-top: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.7rem;
            letter-spacing: 1px;
            display: block;
        }
        
        .sign-out:hover {
            color: red;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        /* Main Content */
        .content-area {
            flex-grow: 1;
            max-width: 580px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .header-row {
            margin-bottom: 30px;
        }
        
        .header-row h2 {
            font-size: 1.6rem;
            font-weight: 400;
            font-family: serif;
            margin: 0 0 5px;
        }
        
        .subhead {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.3px;
            margin-bottom: 25px;
        }
        
        /* Form Styling */
        .field-group {
            margin-bottom: 25px;
        }
        
        .field-group label {
            display: block;
            font-size: 0.7rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        
        .styled-box {
            width: 100%;
            background-color: var(--soft-gray);
            border: 1px solid var(--border);
            padding: 14px 18px;
            font-size: 0.95rem;
            outline: none;
            color: var(--dark);
            border-radius: 0;
            box-sizing: border-box;
            font-family: inherit;
        }
        
        input.styled-box:focus,
        select.styled-box:focus {
            border-color: var(--dark);
            background-color: #ffffff;
        }
        
        input.readonly,
        input.styled-box[readonly] {
            cursor: default;
            background-color: #fcfcfc;
            color: #888;
            border: 1px dashed var(--border);
        }
        
        select.styled-box {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 12px;
        }
        
        .row-2col {
            display: flex;
            gap: 20px;
        }
        
        .row-2col .field-group {
            flex: 1;
        }
        
        /* Form Divider */
        .form-divider {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 40px 0 30px;
        }
        
        /* Message Banner */
        .msg-banner {
            padding: 14px 18px;
            margin-bottom: 30px;
            font-size: 0.85rem;
            border-left: 3px solid;
        }
        
        .msg-success {
            background: #f0f9f0;
            color: #2d5a27;
            border-left-color: #2d5a27;
        }
        
        .msg-error {
            background: #fff5f5;
            color: #b53b3b;
            border-left-color: #b53b3b;
        }
        
        /* Buttons */
        .btn-black {
            background: var(--dark);
            color: #fff;
            border: none;
            padding: 14px 40px;
            font-size: 0.75rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.25s;
            font-family: inherit;
        }
        
        .btn-black:hover {
            background: #2e2e2e;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--dark);
            border: 1px solid var(--dark);
            padding: 14px 40px;
            font-size: 0.75rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-family: inherit;
        }
        
        .btn-outline:hover {
            background: #f8f8f8;
        }
        
        .action-buttons {
            margin-top: 40px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        form {
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Sections */
        .section {
            display: none;
            width: 100%;
            box-sizing: border-box;
        }
        
        .section.active {
            display: block;
            animation: fade 0.3s ease;
        }
        
        @keyframes fade {
            from { opacity: 0.4; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header_user.php'; ?>

<div class="container">
    <!-- Sidebar Navigation -->
    <nav class="nav-sidebar">
        <div class="brand-area">
            <a href="<?php echo base_url('user_dashboard.php'); ?>" class="back-btn">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <h1>Account</h1>
        </div>
        
        <ul class="menu-list">
            <li class="menu-item active" onclick="switchTab('profile', this)">Profile</li>
            <li class="menu-item" onclick="switchTab('wedding', this)">Wedding Details</li>
            <li class="menu-item" onclick="switchTab('security', this)">Security</li>
        </ul>
        
        <a href="<?php echo base_url('logout.php'); ?>" class="sign-out">LOGOUT</a>
    </nav>

    <!-- Main Content -->
    <main class="content-area">
        <?php if ($msg): ?>
            <div class="msg-banner <?php echo $msg_class; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo base_url('profile_edit_logic.php'); ?>" method="POST">
            
            <!-- ===== PROFILE SECTION ===== -->
            <div id="profile" class="section active">
                <div class="header-row">
                    <h2>Personal details</h2>
                    <div class="subhead">Manage your basic account information</div>
                </div>

                <div class="field-group">
                    <label>Username</label>
                    <input type="text" class="styled-box readonly" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>

                <div class="row-2col">
                    <div class="field-group">
                        <label>First name</label>
                        <input type="text" name="first_name" class="styled-box" value="<?php echo htmlspecialchars($user['user_fname'] ?? ''); ?>" required>
                    </div>
                    <div class="field-group">
                        <label>Last name</label>
                        <input type="text" name="last_name" class="styled-box" value="<?php echo htmlspecialchars($user['user_lname'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="field-group">
                    <label>Email address</label>
                    <input type="email" name="email" class="styled-box" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="field-group">
                    <label>Phone number</label>
                    <input type="text" name="phone" class="styled-box" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="xxx-xxxxxxx">
                </div>
            </div>

            <!-- ===== WEDDING DETAILS SECTION ===== -->
            <div id="wedding" class="section">
                <div class="header-row">
                    <h2>Wedding details</h2>
                    <div class="subhead">Tell us more about your celebration</div>
                </div>

                <div class="row-2col">
                    <div class="field-group">
                        <label>Partner's first name</label>
                        <input type="text" name="partner_first_name" class="styled-box" value="<?php echo htmlspecialchars($user['partner_first_name'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label>Partner's last name</label>
                        <input type="text" name="partner_last_name" class="styled-box" value="<?php echo htmlspecialchars($user['partner_last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row-2col">
                    <div class="field-group">
                        <label>Wedding date</label>
                        <input type="date" name="wedding_date" class="styled-box" value="<?php echo htmlspecialchars($user['wedding_date'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label>Wedding city</label>
                        <input type="text" name="city" class="styled-box" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="e.g. Kuala Lumpur">
                    </div>
                </div>
                
                <div class="field-group">
                    <label>Guest count estimate</label>
                    <input type="number" name="guest_count" class="styled-box" value="<?php echo htmlspecialchars($user['guest_count'] ?? ''); ?>" placeholder="Approximate number of guests">
                </div>
            </div>

            <!-- ===== SECURITY SECTION ===== -->
            <div id="security" class="section">
                <div class="header-row">
                    <h2>Security</h2>
                    <div class="subhead">Leave blank to keep your current password</div>
                </div>

                <div class="field-group">
                    <label>New password</label>
                    <input type="password" name="password" class="styled-box" placeholder="Minimum 8 characters include uppercase lowercase number and special character" autocomplete="new-password">
                </div>

                <div class="field-group">
                    <label>Confirm new password</label>
                    <input type="password" name="password_confirmation" class="styled-box" placeholder="Repeat new password" autocomplete="new-password">
                </div>
                
            </div>

            <!-- Form Actions -->
            <div class="action-buttons">
                <button type="submit" class="btn-black">Save changes</button>
                <a href="<?php echo base_url('user_dashboard.php'); ?>" class="btn-outline">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    function switchTab(tabId, element) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Remove active class from all menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show selected section
        document.getElementById(tabId).classList.add('active');
        
        // Add active class to clicked menu item
        element.classList.add('active');
        
        // Smooth scroll to top of content area
        document.querySelector('.content-area').scrollTop = 0;
    }
    
    // Password confirmation validation (optional)
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        const confirm = document.querySelector('input[name="password_confirmation"]').value;
        
        if (password && password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return;
        }
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });
</script>
<?php include 'includes/footer.php'; ?>

</body>
</html>