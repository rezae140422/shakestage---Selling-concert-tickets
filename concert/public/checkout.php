<?php
session_start();

// بررسی توکن کاربر
if (!isset($_COOKIE['user_token'])) {
    header('Location: /concert/public/login.php');
    exit;
}

// اتصال به دیتابیس
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // دریافت اطلاعات کاربر
    $stmt = $pdo->prepare('SELECT email FROM users WHERE token = :token');
    $stmt->bindParam(':token', $_COOKIE['user_token']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('Error: User not found.');
    }

    // بررسی سبد خرید
    $cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
    if (empty($cart)) {
        die('Error: Your cart is empty.');
    }

    $orderDetails = '<ul class="list-group">';
    $totalPrice = 0;
    $concertIds = [];
    $seatIds = [];

    foreach ($cart as $item) {
        // بررسی صندلی‌ها در دیتابیس
        $stmt = $pdo->prepare('SELECT price, label, concert_id FROM venue_items WHERE seat_id = :seat_id');
        $stmt->bindParam(':seat_id', $item['seat_id']);
        $stmt->execute();
        $seat = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($seat) {
            $totalPrice += $seat['price'];
            $concertIds[] = $seat['concert_id'];
            $seatIds[] = $item['seat_id'];

            $orderDetails .= "
                <li class=\"list-group-item d-flex justify-content-between align-items-center\">
                    <span>
                        <strong>Seat ID:</strong> " . htmlspecialchars($item['seat_id']) . "<br>
                        <strong>Label:</strong> " . htmlspecialchars($seat['label']) . "<br>
                        <strong>Price:</strong> &euro;" . number_format($seat['price'], 2) . "
                    </span>
                    <i class=\"bi bi-ticket-fill text-success\"></i>
                </li>
            ";
        } else {
            die('Error: Invalid seat ID ' . htmlspecialchars($item['seat_id']));
        }
    }

    $orderDetails .= '</ul>';

    // تولید توکن یکتا برای رزرو
    $token = hash('sha256', uniqid(mt_rand(), true));

    // تولید شناسه تراکنش
    $transactionId = substr(bin2hex(random_bytes(8)), 0, 16);

    // ذخیره اطلاعات رزرو در دیتابیس
    $stmt = $pdo->prepare("
        INSERT INTO reservations (user_email, seat_id, concert_id, token, payment_type, transaction_id, status)
        VALUES (:email, :seat_id, :concert_id, :token, 'PayPal', :transaction_id, 'pending')
    ");
    $stmt->execute([
        ':email' => $user['email'],
        ':seat_id' => json_encode($seatIds),
        ':concert_id' => $concertIds[0],
        ':token' => $token,
        ':transaction_id' => $transactionId
    ]);

    // ذخیره اطلاعات پرداخت در دیتابیس
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_email, total_price, status, token, transaction_id)
        VALUES (:email, :total_price, 'pending', :token, :transaction_id)
    ");
    $stmt->execute([
        ':email' => $user['email'],
        ':total_price' => $totalPrice,
        ':token' => $token,
        ':transaction_id' => $transactionId
    ]);

    // ذخیره توکن در سشن برای پیگیری پرداخت
    $_SESSION['payment_token'] = $token;
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    die('Error: Unable to process your request. Please try again later.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
</head>
<body>
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-body">
            <h3 class="card-title text-primary">
                <i class="bi bi-receipt"></i> Order Summary
            </h3>
            <p><strong>User Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <hr>
            <h4 class="card-title text-primary">
                <i class="bi bi-cart-check"></i> Order Details
            </h4>
            <div><?= $orderDetails ?></div>
            <hr>
            <h5 class="text-end text-success">
                <strong>Total Price:</strong> &euro;<?= number_format($totalPrice, 2) ?>
            </h5>
            <div class="d-flex justify-content-between mt-4">
                <a href="/concert/public/home.php" class="btn btn-primary">
                    <i class="bi bi-house-door"></i> Return to Home
                </a>
                <form id="paypal-form" action="/concert/public/paypal_payment.php" method="POST">
                    <input type="hidden" name="total_price" value="<?= htmlspecialchars($totalPrice) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transactionId) ?>">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-paypal"></i> Pay with PayPal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
