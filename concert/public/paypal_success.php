<?php
session_start();

// بررسی اینکه پارامترهای مورد نیاز وجود دارند
if (!isset($_GET['paymentId']) || !isset($_GET['PayerID'])) {
    die('Error: Required parameters are missing.');
}

// دریافت پارامترهای بازگشتی از PayPal
$paymentId = $_GET['paymentId'];
$payerId = $_GET['PayerID'];

// بارگذاری تنظیمات PayPal
require_once __DIR__ . '/../config/paypal.php';

if (!isset($_SESSION['paypal_access_token'])) {
    die('Error: Access token is missing. Please try again.');
}

$accessToken = $_SESSION['paypal_access_token'];

// تأیید پرداخت در PayPal
$executeUrl = "https://api.paypal.com/v1/payments/payment/{$paymentId}/execute";
$data = ['payer_id' => $payerId];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $executeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// بررسی پاسخ HTTP
if ($httpCode !== 200) {
    die("Error: Unable to execute payment. HTTP Code: $httpCode");
}

// پردازش پاسخ PayPal
$jsonResponse = json_decode($response, true);

$paymentStatus = 'failed';
if (isset($jsonResponse['state']) && $jsonResponse['state'] === 'approved') {
    // جزئیات پرداخت
    $paymentAmount = $jsonResponse['transactions'][0]['amount']['total'];
    $paymentCurrency = $jsonResponse['transactions'][0]['amount']['currency'];
    $paymentToken = $jsonResponse['transactions'][0]['custom'] ?? null;

    if (!$paymentToken) {
        die('Error: Token not found in PayPal response.');
    }

    // به‌روزرسانی وضعیت پرداخت و رزروها
    try {
        require_once __DIR__ . '/../config/database.php';

        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // بررسی صحت توکن در دیتابیس
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE token = :token AND status = "pending"');
        $stmt->execute([':token' => $paymentToken]);
        $paymentRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paymentRecord) {
            die('Error: Invalid or already processed payment.');
        }

        // به‌روزرسانی وضعیت پرداخت
        $stmt = $pdo->prepare('UPDATE payments SET status = :status WHERE token = :token');
        $stmt->execute([
            ':status' => 'completed',
            ':token' => $paymentToken
        ]);

        // به‌روزرسانی وضعیت رزرو
        $stmt = $pdo->prepare('UPDATE reservations SET status = :status WHERE token = :token AND status = "pending"');
        $stmt->execute([
            ':status' => 'completed',
            ':token' => $paymentToken
        ]);

        $paymentStatus = 'success';
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        die("Database Error. Please contact support.");
    }
} else {
    error_log("PayPal response indicates payment failed. Response: " . json_encode($jsonResponse));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
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
            <?php if ($paymentStatus === 'success'): ?>
                <h1 class="text-success">
                    <i class="bi bi-check-circle-fill"></i> Payment Successful
                </h1>
                <p class="mt-3">Your payment of <strong>&euro;<?= htmlspecialchars($paymentAmount) ?> <?= htmlspecialchars($paymentCurrency) ?></strong> has been successfully processed.</p>
            <?php else: ?>
                <h1 class="text-danger">
                    <i class="bi bi-x-circle-fill"></i> Payment Failed
                </h1>
                <p class="mt-3">Unfortunately, your payment was not successful. Please try again later.</p>
            <?php endif; ?>

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
