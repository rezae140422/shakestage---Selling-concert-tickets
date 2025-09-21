<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email address.');
    }

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // بررسی ایمیل در دیتابیس
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die('Error: User not found. <a href="/concert/public/register.php">Register here</a>');
        }

        // بررسی مقدار is_active
        if ($user['is_active'] != 1) {
            die('Your account is inactive or not valid. Please contact support.');
        }

        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            die('Error: Incorrect password.');
        }

        // ثبت زمان آخرین ورود
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // تولید توکن یکتا
        $token = bin2hex(random_bytes(32)); // ایجاد توکن یکتا

        // ذخیره توکن در دیتابیس
        $stmt = $pdo->prepare('UPDATE users SET token = :token WHERE email = :email');
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // ذخیره توکن در مرورگر (localStorage یا Cookie)
        setcookie('user_token', $token, time() + 11600, '/'); // مدت زمان 1 ساعت

        // ذخیره اطلاعات کاربر در سشن
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // ثبت لاگ ورود در جدول login_logs
        $user_ip = $_SERVER['REMOTE_ADDR']; // دریافت آی‌پی کاربر
        $login_time = date('Y-m-d H:i:s'); // زمان ورود

        $logStmt = $pdo->prepare('
            INSERT INTO login_logs (user_email, user_ip, login_time) 
            VALUES (:user_email, :user_ip, :login_time)
        ');
        $logStmt->bindParam(':user_email', $email);
        $logStmt->bindParam(':user_ip', $user_ip);
        $logStmt->bindParam(':login_time', $login_time);
        $logStmt->execute();

        // هدایت بر اساس نقش
        switch ($user['role']) {
            case 'admin':
                header('Location: /concert/public/admin_panel.php');
                break;
            case 'organizer':
                header('Location: /concert/public/organizer_panel.php');
                break;
            default:
                header('Location: /concert/public/user_panel.php');
        }
        exit;
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('Invalid request method.');
}
?>
