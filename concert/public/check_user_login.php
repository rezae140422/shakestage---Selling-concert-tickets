<?php
function check_user_login() {
    // توکن از کوکی گرفته می‌شود
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
                return true; // کاربر وارد شده است
            } else {
                return false; // توکن معتبر نیست
            }
        } catch (PDOException $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    return false; // اگر توکن وجود ندارد
}
?>
