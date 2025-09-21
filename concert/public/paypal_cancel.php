<?php
session_start();

// بررسی پارامترهای بازگشتی از PayPal
if (!isset($_GET['token'])) {
    die('Cancel token is missing.');
}

// دریافت توکن پرداخت
$paymentToken = $_GET['token'];

// بارگذاری تنظیمات دیتابیس
require_once __DIR__ . '/../config/database.php';

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // به‌روزرسانی وضعیت پرداخت
    $stmt = $pdo->prepare('UPDATE payments SET status = :status WHERE token = :token');
    $stmt->execute([
        ':status' => 'canceled',
        ':token' => $paymentToken
    ]);

    // به‌روزرسانی وضعیت رزروها
    $stmt = $pdo->prepare('UPDATE reservations SET status = :status WHERE token = :token AND status = "pending"');
    $stmt->execute([
        ':status' => 'canceled',
        ':token' => $paymentToken
    ]);

    $paymentStatus = 'canceled';

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Canceled</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <script>
        let countdown = 10;
        function updateCountdown() {
            document.getElementById('countdown').innerText = countdown;
            if (countdown > 0) {
                countdown--;
                setTimeout(updateCountdown, 1000);
            } else {
                window.location.href = 'https://shakestage.com/';
            }
        }
        window.onload = updateCountdown;
    </script>
</head>
<body>
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-body text-center">
            <h1 class="text-danger">
                <i class="bi bi-x-circle-fill"></i> Payment Canceled
            </h1>
            <p class="mt-3">Your payment has been canceled successfully. If you wish to try again, please go back to the payment page.</p>

            <a href="https://shakestage.com/" class="btn btn-primary mt-4">
                <i class="bi bi-house-door"></i> Return to Home
            </a>
            <p class="mt-4">You will be redirected to the homepage in <span id="countdown">10</span> seconds...</p>
        </div>
    </div>
    <footer class="mt-5 text-center">
        <p>Support: <a href="mailto:info@shakestage.com">info@shakestage.com</a> | Phone: +32 488110881</p>
    </footer>
</div>
<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>