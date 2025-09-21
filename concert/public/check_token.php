<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// بررسی توکن در مرورگر
if (isset($_COOKIE['user_token'])) {
    $token = $_COOKIE['user_token'];

    try {
        // بررسی توکن در دیتابیس
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE token = :token');
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // کاربر پیدا شد و توکن معتبر است
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            // هدایت کاربر به صفحه مربوطه
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
        } else {
            // توکن معتبر نیست، کاربر خارج شده است
            header('Location: /concert/public/login.php');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    // توکن وجود ندارد، کاربر وارد نشده است
    header('Location: /concert/public/login.php');
}
?>
