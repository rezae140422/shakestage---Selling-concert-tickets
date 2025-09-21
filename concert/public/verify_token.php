<?php
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
            // توکن معتبر است
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true; // برگشت true یعنی توکن معتبر است
        } else {
            // توکن معتبر نیست
            header('Location: /concert/public/login.php');
            exit;
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    // توکن وجود ندارد
    header('Location: /concert/public/login.php');
    exit;
}
?>
