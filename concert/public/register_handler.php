<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // دریافت اطلاعات از فرم
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ipAddress = $_SERVER['REMOTE_ADDR']; // دریافت آدرس IP کاربر
    $token = bin2hex(random_bytes(32)); // تولید توکن یکتا برای کاربر

    // اعتبارسنجی ایمیل
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email address.');
    }

    // اعتبارسنجی رمز عبور
    if (strlen($password) < 5 || preg_match('/^(12345|11111)$/', $password)) {
        die('Password is too weak.');
    }

    // هش کردن رمز عبور
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // ذخیره اطلاعات در دیتابیس
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ذخیره اطلاعات در جدول
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password, ip_address, token) VALUES (:email, :password, :ip_address, :token)'
        );
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        // پیام موفقیت
        echo "Registration successful! <a href='/concert/public/login.php'>Login here</a>";
    } catch (PDOException $e) {
        // مدیریت خطای ایمیل تکراری
        if ($e->getCode() == 23000) {
            die('Error: Email already registered.');
        }
        // مدیریت سایر خطاها
        die('Error: ' . $e->getMessage());
    }
} else {
    // اگر درخواست POST نبود
    die('Invalid request method.');
}
?>
