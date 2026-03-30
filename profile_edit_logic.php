<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

if (!isset($_SESSION['username']) || $_SESSION['usertype'] !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    header('Location: ' . base_url('profile_edit.php?err=1'));
    exit;
}

// only allow POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('profile_edit.php'));
    exit;
}

//get all input data
$email = trim($_POST['email'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$partner_first_name = trim($_POST['partner_first_name'] ?? '');
$partner_last_name = trim($_POST['partner_last_name'] ?? '');
$wedding_role = $_POST['wedding_role'] ?? 'both';
$partner_role = $_POST['partner_role'] ?? 'both';
$wedding_date = $_POST['wedding_date'] ?? null;
$city = trim($_POST['city'] ?? '');

$password = $_POST['password'] ?? '';
$password_confirmation = $_POST['password_confirmation'] ?? '';


// 2. basic validation
$err = '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Valid email required.';
} elseif ($password !== '' && strlen($password) < 8) {
    $err = 'Password must be at least 8 characters include uppercase lowercase number and special character.';
} elseif ($password !== '' && $password !== $password_confirmation) {
    $err = 'Passwords do not match.';
}

if ($err) {
    header('Location: ' . base_url('profile_edit.php?err=' . urlencode($err)));
    exit;
}


// 3. sanitize input
$email = mysqli_real_escape_string($conn, $email);
$first_name = mysqli_real_escape_string($conn, $first_name);
$last_name = mysqli_real_escape_string($conn, $last_name);
$phone = mysqli_real_escape_string($conn, $phone);
$p_fname = mysqli_real_escape_string($conn, $partner_first_name);
$p_lname = mysqli_real_escape_string($conn, $partner_last_name);
$city = mysqli_real_escape_string($conn, $city);
$w_date = !empty($wedding_date) 
    ? "'" . mysqli_real_escape_string($conn, $wedding_date) . "'" 
    : "NULL";


// check duplicate email
$dup = mysqli_query($conn, "SELECT id FROM login WHERE email = '$email' AND id != $user_id LIMIT 1");

if ($dup && mysqli_num_rows($dup) > 0) {
    header('Location: ' . base_url('profile_edit.php?err=Email+already+in+use'));
    exit;
}


// 4. update database

// start transaction to ensure both tables update together
mysqli_begin_transaction($conn);

try {
    // A. update login table (user basic info)
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_login = "
            UPDATE login 
            SET email = '$email',
                first_name = '$first_name',
                last_name = '$last_name',
                phone_number = '$phone',
                password = '$hash'
            WHERE id = $user_id
        ";
    } else {
        $sql_login = "
            UPDATE login 
            SET email = '$email',
                first_name = '$first_name',
                last_name = '$last_name',
                phone_number = '$phone'
            WHERE id = $user_id
        ";
    }

    mysqli_query($conn, $sql_login);


    // B. insert or update user profile
    $sql_profile = "
        INSERT INTO user_profiles (
            user_id,
            partner_first_name,
            partner_last_name,
            wedding_role,
            partner_role,
            wedding_date,
            city
        ) 
        VALUES (
            $user_id,
            '$p_fname',
            '$p_lname',
            '$wedding_role',
            '$partner_role',
            $w_date,
            '$city'
        )
        ON DUPLICATE KEY UPDATE 
            partner_first_name = VALUES(partner_first_name),
            partner_last_name = VALUES(partner_last_name),
            wedding_role = VALUES(wedding_role),
            partner_role = VALUES(partner_role),
            wedding_date = VALUES(wedding_date),
            city = VALUES(city)
    ";

    mysqli_query($conn, $sql_profile);

    // commit transaction
    mysqli_commit($conn);

    header('Location: ' . base_url('profile_edit.php?saved=1'));

} catch (Exception $e) {

    // rollback if error occurs
    mysqli_rollback($conn);

    header('Location: ' . base_url('profile_edit.php?err=Update+failed'));
}

exit;