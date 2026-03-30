<?php
require_once __DIR__ . "/vendor/autoload.php";

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/config.php';

// Check logged in status
if (isset($_SESSION['username']) && isset($_SESSION['usertype'])) {
    if ($_SESSION['usertype'] === 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } else {
        header('Location: ' . base_url('index.php'));
    }
    exit;
}

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['last_attempt'])) $_SESSION['last_attempt'] = 0;

// Google Login
function getGoogleLoginUrl() {
    try {
        $client = new Google\Client();
        $client->setClientId(getenv('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(getenv('GOOGLE_REDIRECT_URI'));
        $client->addScope("email");
        $client->addScope("profile");
        
        return $client->createAuthUrl();
    } catch (Exception $e) {
        error_log("Google Auth Error: " . $e->getMessage());
        return '#';
    }
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $err = 'Invalid security token.';
    } else {
        if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt'] < 900)) {
            $err = 'Too many attempts. Please try again in 15 minutes.';
        } else {
            $u = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
            $p = $_POST['password'] ?? '';

            if ($u && $p) {
                $stmt = $conn->prepare("SELECT id, username, password, usertype FROM login WHERE username=? LIMIT 1");
                $stmt->bind_param("s", $u);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();

                if ($row && password_verify($p, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['usertype'] = $row['usertype'];
                    $_SESSION['login_attempts'] = 0;

                    if ($row['usertype'] === 'admin') {
                        header('Location: ' . base_url('admin/dashboard.php'));
                    } else {
                        header('Location: ' . base_url('index.php'));
                    }
                    exit;
                }
            }
            $err = 'Username or password incorrect.';
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            usleep(500000); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin-login.css'); ?>">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
    body { 
        background: url("<?php echo base_url('assets/images/home/longg.png'); ?>") no-repeat center center fixed; 
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .wrapper {
        width: 500px;
        min-height: 550px;
        background: rgba(255, 255, 255, 0.12);
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(25px);
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 30px;
        position: relative;
    }

    form {
        width: 100%;
    }

    .wrapper h1 {
        font-size: 42px;
        margin-bottom: 40px;
    }

    .input-box {
        height: 60px;
        margin: 25px 0;
    }

    .input-box input {
        font-size: 18px;
        padding: 20px 45px 20px 20px;
    }

    .input-box i {
        font-size: 24px;
    }

    .btn {
        height: 55px;
        font-size: 18px;
        font-weight: 700;
        margin-top: 10px;
    }

    .err { 
        color: #fff;
        background: rgba(255, 107, 107, 0.2);
        border: 1px solid rgba(255, 107, 107, 0.3);
        backdrop-filter: blur(5px);
        text-align: center; 
        margin-bottom: 25px; 
        font-size: 14px;
        padding: 12px;
        border-radius: 30px;
        width: 100%;
        animation: shake 0.4s ease-in-out;
    }

    .attempts { 
        color: #eee0b6; 
        font-size: 14px; 
        text-align: center; 
        margin-top: 20px;
        padding: 8px;
        border-radius: 5px;
    }
    
    .separator {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 20px 0;
        color: #fff;
    }
    
    .separator::before,
    .separator::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .separator span {
        padding: 0 10px;
        font-size: 14px;
    }
    
    .google-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 50px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        color: #fff;
        text-decoration: none;
        font-size: 16px;
        transition: all 0.3s ease;
        margin-top: 10px;
    }
    
    .google-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }
    
    .google-btn i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    </style>
</head>
<body>
<div class="wrapper">
    <a href="index.php" style="position:absolute; top:20px; left:20px; color:#fff; text-decoration:none;">
        <i class='bx bx-arrow-back'></i> Home
    </a>
    
    <form method="POST" action="">
        <h1>Welcome</h1>
        
        <?php if ($err): ?>
            <div class="err"><?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="input-box">
            <input type="text" name="username" placeholder="Username" required 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <i class='bx bx-user'></i>
        </div>

        <div class="input-box">
            <input type="password" name="password" placeholder="Password" required>
            <i class='bx bx-lock-alt'></i>
        </div>

        <button type="submit" class="btn">Login</button>

        <div class="forgot" style="text-align:center; margin-top:20px;">
            <p>Don't have an account? <a href="register.php" style="color:#ead1cd;">Sign up</a></p>
        </div>

        <?php if ($_SESSION['login_attempts'] > 0): ?>
            <div class="attempts">Remaining attempts: <?php echo (5 - $_SESSION['login_attempts']); ?></div>
        <?php endif; ?>

        <div class="separator">
            <span>or</span>
        </div>

        <!-- Google Sign In -->
        <a href="<?php echo getGoogleLoginUrl(); ?>" class="google-btn">
            <i class="fa-brands fa-google"></i> Sign in with Google
        </a>
    </form>
</div>
</body>
</html>