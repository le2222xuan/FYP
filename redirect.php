<?php
session_start();
require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/config.php';
require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(getenv('GOOGLE_REDIRECT_URI'));

if (!isset($_GET["code"])) {
    header('Location: ' . base_url('signup.php'));
    exit;
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
    if (isset($token['error'])) {
        throw new Exception($token['error_description'] ?? 'Google login failed');
    }
    $client->setAccessToken($token["access_token"]);
    
    $oauth = new Google\Service\Oauth2($client);
    $userinfo = $oauth->userinfo->get();
    
    $google_email = $userinfo->email;
    $google_name = $userinfo->name;
    $google_givenName = $userinfo->givenName;
    $google_familyName = $userinfo->familyName;
    $google_id = $userinfo->id;
    $google_avatar = $userinfo->picture;

    // Escape strings for SQL
    $email_safe = mysqli_real_escape_string($conn, $google_email);
    $google_id_safe = mysqli_real_escape_string($conn, $google_id);
    
    // Check if user exists by email OR google_id
    $check_query = "SELECT id, username, usertype, first_name, last_name 
                    FROM login 
                    WHERE email='$email_safe' OR google_id='$google_id_safe' 
                    LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        // User doesn't exist - create new account
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $google_givenName ?? $google_name);
        $base_username = empty($base_username) ? 'user' : $base_username;
        
        // Generate unique username
        $username = $base_username;
        $counter = 1;
        while (true) {
            $u_result = mysqli_query($conn, "SELECT 1 FROM login WHERE username='$username' LIMIT 1");
            if (mysqli_num_rows($u_result) == 0) break;
            $username = $base_username . $counter++;
        }
        
        // Escape additional fields
        $first_name_safe = mysqli_real_escape_string($conn, $google_givenName ?? '');
        $last_name_safe = mysqli_real_escape_string($conn, $google_familyName ?? '');
        $avatar_safe = mysqli_real_escape_string($conn, $google_avatar ?? '');
        $token_safe = mysqli_real_escape_string($conn, json_encode($token));
        
        // Insert new Google user
        $insert_sql = "INSERT INTO login (
            username, 
            email, 
            password, 
            first_name, 
            last_name, 
            google_id, 
            google_token, 
            avatar, 
            registration_method, 
            email_verified,
            usertype
        ) VALUES (
            '$username', 
            '$email_safe', 
            NULL, 
            '$first_name_safe', 
            '$last_name_safe', 
            '$google_id_safe', 
            '$token_safe', 
            '$avatar_safe', 
            'google', 
            TRUE, 
            'user'
        )";
        
        if (!mysqli_query($conn, $insert_sql)) {
            throw new Exception('Failed to create user account: ' . mysqli_error($conn));
        }
    }

    // Get user data for session
    $final_result = mysqli_query($conn, "SELECT id, username, usertype, first_name, last_name, email 
                                        FROM login 
                                        WHERE email='$email_safe' OR google_id='$google_id_safe' 
                                        LIMIT 1");
    $user = mysqli_fetch_assoc($final_result);

    // Update last login
    mysqli_query($conn, "UPDATE login SET last_login = NOW() WHERE id = " . $user['id']);
    
    // Log login history
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip_safe = mysqli_real_escape_string($conn, $ip);
    $ua_safe = mysqli_real_escape_string($conn, $user_agent);
    mysqli_query($conn, "INSERT INTO login_history (user_id, login_method, ip_address, user_agent) 
                         VALUES ({$user['id']}, 'google', '$ip_safe', '$ua_safe')");

    // Set session variables based on user type
    if ($user['usertype'] === 'admin') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['usertype'] = 'admin';
        $_SESSION['admin_logged_in'] = true;
        
        header('Location: ' . base_url('admin/dashboard.php'));
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['usertype'] = 'user';
        $_SESSION['user_logged_in'] = true;
        $_SESSION['login_method'] = 'google';
        
        header('Location: user_dashboard.php');
    }
    exit;

} catch (Exception $e) {
    error_log('Google Login Error: ' . $e->getMessage());
    header('Location: ' . base_url('signup.php?error=google_login_failed'));
    exit;
}
?>