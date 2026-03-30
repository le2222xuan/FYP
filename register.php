<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/config.php';

// Initialize variables
$err = '';
$field = '';
$emailErr = '';
$passwordErr = '';
$cpasswordErr = '';

$ph = [
    'username' => 'Username',
    'email' => 'Email Address',
    'phone_num' => 'Phone Number',
    'password' => 'Password',
    'confirm' => 'Confirm Password'
];

// Check for error from Google login
if (isset($_GET['error']) && $_GET['error'] === 'google_login_failed') {
    $err = 'Google login failed. Please try again.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $err = 'Username is required';
        $field = 'username';
    }
    
    // Validate email
    elseif (empty($email)) {
        $err = 'Email is required';
        $field = 'email';
    }
    
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = 'Invalid email format';
        $err = 'Invalid email format';
        $field = 'email';
    }
    
    // Validate password presence
    elseif (empty($password)) {
        $passwordErr = "Please enter password";
        $err = "Please enter password";
        $field = 'password';
    }
    
    // Validate password length
    elseif (strlen($password) < 8) {
        $passwordErr = "Your Password Must Contain At Least 8 Characters!";
        $err = "Your Password Must Contain At Least 8 Characters!";
        $field = 'password';
    }
    
    // Validate password has number
    elseif (!preg_match("#[0-9]+#", $password)) {
        $passwordErr = "Your Password Must Contain At Least 1 Number!";
        $err = "Your Password Must Contain At Least 1 Number!";
        $field = 'password';
    }
    
    // Validate password has uppercase
    elseif (!preg_match("#[A-Z]+#", $password)) {
        $passwordErr = "Your Password Must Contain At Least 1 Capital Letter!";
        $err = "Your Password Must Contain At Least 1 Capital Letter!";
        $field = 'password';
    }
    
    // Validate password has lowercase
    elseif (!preg_match("#[a-z]+#", $password)) {
        $passwordErr = "Your Password Must Contain At Least 1 Lowercase Letter!";
        $err = "Your Password Must Contain At Least 1 Lowercase Letter.";
        $field = 'password';
    }
    
    // Validate password confirmation presence
    elseif (empty($confirm)) {
        $cpasswordErr = "Please confirm your password";
        $err = "Please confirm your password";
        $field = 'confirm';
    }
    
    // Validate password match
    elseif ($password !== $confirm) {
        $err = 'Passwords do not match';
        $field = 'confirm';
    }
    
    // If all validations pass
    else {
        
        // Escape strings for database
        $username_escaped = mysqli_real_escape_string($conn, $username);
        $email_escaped = mysqli_real_escape_string($conn, $email);
        
        // Check if username exists
        $check_username = mysqli_query($conn, "SELECT id FROM login WHERE username='$username_escaped' LIMIT 1");
        
        if (mysqli_num_rows($check_username) > 0) {
            $err = 'Username already taken';
            $field = 'username';
        }
        
        // Check if email exists
        else {
            $check_email = mysqli_query($conn, "SELECT id FROM login WHERE email='$email_escaped' LIMIT 1");
            
            if (mysqli_num_rows($check_email) > 0) {
                $err = 'Email already registered';
                $field = 'email';
            }
            
            // Create new user
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_sql = "INSERT INTO login (
                    username, 
                    email, 
                    password, 
                    first_name, 
                    last_name, 
                    phone_number, 
                    usertype, 
                    registration_method, 
                    email_verified
                ) VALUES (
                    '$username_escaped', 
                    '$email_escaped', 
                    '$hash', 
                    '', 
                    '', 
                    '', 
                    'user', 
                    'email', 
                    FALSE
                )";
                
                if (mysqli_query($conn, $insert_sql)) {
                    
                    // Get the new user ID
                    $user_id = mysqli_insert_id($conn);
                    
                    // Set session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_username'] = $username;
                    $_SESSION['user_logged_in'] = true;
                    
                    // Redirect to dashboard
                    header('Location: ' . base_url('user_dashboard.php'));
                    exit;
                    
                } else {
                    $err = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin-login.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { 
            background-image: url("<?php echo base_url('assets/images/home/longg.png'); ?>") !important; 
            background-size: cover; 
            background-attachment: fixed; 
        }
        
        .back-link { 
            position: absolute; 
            top: 20px; 
            left: 20px; 
            color: #fff; 
            text-decoration: none; 
            z-index: 10; 
        }
        
        .err { 
            color: #ff6b6b; 
            text-align: center; 
            margin-bottom: 10px; 
        }
        
        /* Error Input Styles */
        .error-input { 
            border-color: #ff4d4d !important; 
        }
        
        .error-input::placeholder { 
            color: #ff4d4d !important; 
            opacity: 1 !important; 
        }
        /* 添加名字行的样式 */
.name-row {
    display: flex;
    gap: 15px;
    width: 100%;
    margin: 25px 0;
}

.name-row .input-box {
    flex: 1;
    margin: 0; /* 移除原来的margin，因为name-row已经有margin */
}

/* 调整输入框内边距以适应更小的空间 */
.name-row .input-box input {
    padding: 20px 40px 20px 20px;
    font-size: 16px;
}

.name-row .input-box i {
    right: 15px;
}
        /* Shake Animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        
        .shake-animate { 
            animation: shake 0.4s ease-in-out; 
        }
        
        /* Password Visibility Icons */
        .input-box {
            position: relative;
        }
        
        /* Lock icon */
        .input-box i.lock-icon {
            position: absolute;
            right: 15px;
            pointer-events: none;
            color: #999;
            transition: opacity 0.3s ease;
            font-size: 20px;
        }
        
        /* Eye icon*/
        .input-box i.toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #888;
            user-select: none;
            opacity: 0;
            pointer-events: none;
            font-size: 20px;
        }
        
        /* Show eye and hide lock when input has value */
        .input-box input:not(:placeholder-shown) ~ i.toggle-password {
            opacity: 1;
            pointer-events: auto;
            color: #ead1cd;
        }
        
        .input-box input:not(:placeholder-shown) ~ i.lock-icon {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Show eye when focused */
        .input-box input:focus ~ i.toggle-password {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* Eye hover effect */
        .input-box i.toggle-password:hover {
            color: #fff;
        }
        
        /* Password hint */
        .pwd-hint {
            font-size: 12px;
            color: #ead1cd;
            margin-top: -15px;
            margin-bottom: 10px;
            display: block;
            text-align: left;
            padding-left: 5px;
            opacity: 0.8;
        }
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #ead1cd;
            opacity: 0.8;
        }

        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ead1cd;
        }

        .separator:not(:empty)::before {
            margin-right: .5em;
        }

        .separator:not(:empty)::after {
            margin-left: .5em;
        }

        /* Google button style */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 45px;
            background: white;
            border: none;
            outline: none;
            border-radius: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
            cursor: pointer;
            font-size: 16px;
            color: #333;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .google-btn i {
            font-size: 24px;
            margin-right: 10px;
            color: #DB4437;
        }

        .google-btn:hover {
            background: #f1f1f1;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="wrapper">
    <a href="index.php" class="back-link"><i class='bx bx-arrow-back'></i> Home</a>
    <form method="POST" action="" id="signupForm">
        <div class="header"><h1>Create Account</h1></div>
        <?php if ($err): ?><div class="err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
        
        <!-- Username -->
        <div class="input-box">
            <input type="text" name="username" id="username"
                placeholder="<?php echo htmlspecialchars($ph['username']); ?>" 
                class="<?php echo ($field === 'username' || $field === 'both') ? 'error-input shake-animate' : '';?>" 
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            <i class='bx bx-user'></i>
        </div>

        <!-- First & Last Name -->
        <div class="name-row">
            <div class="input-box half">
                <input type="text" name="first_name" id="first_name"
                    placeholder="First Name" 
                    value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
            </div>
            
            <div class="input-box half">
                <input type="text" name="last_name" id="last_name"
                    placeholder="Last Name" 
                    value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
            </div>
        </div>
        
        <!-- Email -->
        <div class="input-box">
            <input type="email" name="email" id="email"
                placeholder="<?php echo htmlspecialchars($ph['email']); ?>" 
                class="<?php echo ($field === 'email' || $field === 'both') ? 'error-input shake-animate' : '';?>" 
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            <i class='bx bx-envelope'></i>
        </div>

        <!-- Phone -->
        <div class="input-box" style="display: flex; align-items: center;">
            <span style="position: absolute; left: 15px; color: #e9e9e9; font-weight: 600; z-index: 1; line-height: 1.2;">+60</span>
            <input type="tel" name="phone_num" id="phone_num"
                placeholder="123456789" 
                style="padding-left: 50px;
                    transform: translateY(-1px);
                    line-height: 1.2;"
                class="<?php echo ($field === 'phone_num' || $field === 'both') ? 'error-input shake-animate' : '';?>" 
                value="<?php echo htmlspecialchars($_POST['phone_num'] ?? ''); ?>" required>
            <i class='bx bx-phone' style="right: 15px;"></i>
        </div>
        
        <!-- Password -->
        <div class="input-box">
            <input type="password" name="password" id="password"
                placeholder="<?php echo htmlspecialchars($ph['password']); ?>" 
                class="<?php echo $field === 'password' ? 'error-input shake-animate' : ''; ?>" required>
            <i class='bx bx-lock-alt lock-icon' id="lockIcon"></i>
            <i class='bx bx-hide toggle-password' id="togglePassword" onclick="togglePasswordVisibility('password', this)"></i>
        </div>
        <small class="pwd-hint">Password must be at least 8 characters</small>

        <!-- Confirm Password -->
        <div class="input-box">
            <input type="password" name="password_confirmation" id="confirm"
                placeholder="<?php echo htmlspecialchars($ph['confirm']); ?>" 
                class="<?php echo $field === 'confirm' ? 'error-input shake-animate' : ''; ?>" required>
            <i class='bx bx-lock-alt lock-icon' id="confirmLockIcon"></i>
            <i class='bx bx-hide toggle-password' id="toggleConfirm" onclick="togglePasswordVisibility('confirm', this)"></i>
        </div>

        <button type="submit" class="btn">Sign Up</button>

        <div class="forgot" style="text-align:center;margin-top:20px;">
            <label>Already have an account? <a href="login.php" style="color:#ead1cd;">Login</a></label>
        </div>
    </form>
</div>

<script>
function togglePasswordVisibility(fieldId, icon) {
    const input = document.getElementById(fieldId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bx-hide', 'bx-show');
        icon.style.color = '#fff';
    } else {
        input.type = 'password';
        icon.classList.replace('bx-show', 'bx-hide');
        icon.style.color = '#ead1cd';
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;

    if (confirm.length > 0) {
        if (password === confirm && password.length > 0) {
            document.getElementById('confirm').classList.add('match-success');
            document.getElementById('confirm').classList.remove('error-input');
        } else {
            document.getElementById('confirm').classList.remove('match-success');
            if (password !== confirm) {
                document.getElementById('confirm').classList.add('error-input');
            }
        }
    } else {
        document.getElementById('confirm').classList.remove('match-success', 'error-input');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm');

    // Real-time password match checking
    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmInput.addEventListener('input', checkPasswordMatch);

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;
        
        let hasError = false;

        // Clear previous animations
        document.querySelectorAll('input').forEach(el => {
            el.classList.remove('shake-animate');
        });

        // Validate Username
        if (!username) {
            document.getElementById('username').classList.add('error-input', 'shake-animate');
            hasError = true;
        } else {
            document.getElementById('username').classList.remove('error-input');
        }

        // Validate Email
        if (!email || !email.includes('@')) {
            document.getElementById('email').classList.add('error-input', 'shake-animate');
            hasError = true;
        } else {
            document.getElementById('email').classList.remove('error-input');
        }

        // Validate Password Length
        if (password.length < 8) {
            passwordInput.classList.add('error-input', 'shake-animate');
            hasError = true;
        } else {
            passwordInput.classList.remove('error-input');
        }

        // Validate Password Match
        if (password !== confirmPassword) {
            confirmInput.classList.add('error-input', 'shake-animate');
            confirmInput.classList.remove('match-success');
            hasError = true;
        } else if (password.length >= 8) {
            confirmInput.classList.remove('error-input', 'shake-animate');
            confirmInput.classList.add('match-success');
        }

        if (hasError) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>