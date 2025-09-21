<?php
session_start();

// بارگذاری تنظیمات PayPal
require_once __DIR__ . '/../config/paypal_token.php';  // دریافت توکن

if (!isset($_SESSION['paypal_access_token'])) {
    die('Error: Access Token is missing. Please try again later.');
}

$accessToken = $_SESSION['paypal_access_token'];  // دریافت توکن از سشن

// اطلاعات ارسال شده از فرم checkout.php
$totalPrice = isset($_POST['total_price']) ? $_POST['total_price'] : 0;
$token = isset($_POST['token']) ? $_POST['token'] : null;

// بررسی اینکه آیا قیمت و توکن موجود هستند
if ($totalPrice <= 0 || empty($token)) {
    die('Error: Invalid payment details.');
}

// آدرس برای ایجاد پرداخت
$paymentUrl = 'https://api.paypal.com/v1/payments/payment';
$data = [
    "intent" => "sale",  // نوع تراکنش
    "payer" => [
        "payment_method" => "paypal"
    ],
    "transactions" => [
        [
            "amount" => [
                "total" => number_format($totalPrice, 2),  // مبلغ کل با فرمت یورو
                "currency" => "EUR"  // واحد پول یورو
            ],
            "description" => "Concert Ticket Purchase",
            "custom" => $token  // ارسال توکن به عنوان اطلاعات اختصاصی
        ]
    ],
    "redirect_urls" => [
        "return_url" => "https://shakestage.com/concert/public/paypal_success.php",  // آدرس بازگشت پس از پرداخت موفق
        "cancel_url" => "https://shakestage.com/concert/public/paypal_cancel.php"   // آدرس بازگشت در صورت لغو پرداخت
    ]
];

// ارسال درخواست به PayPal برای ایجاد پرداخت
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paymentUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken",  // ارسال توکن در هدر
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// بررسی پاسخ از PayPal
if ($httpCode === 201 && $response) {
    $jsonResponse = json_decode($response, true);
    if (isset($jsonResponse['id'])) {
        $_SESSION['paypal_payment_id'] = $jsonResponse['id'];

        $approvalUrl = '';
        foreach ($jsonResponse['links'] as $link) {
            if ($link['rel'] === 'approval_url') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        if ($approvalUrl) {
            // هدایت به PayPal برای ادامه پرداخت
            header('Location: ' . $approvalUrl);
            exit;
        } else {
            die('Error: Approval URL not found.');
        }
    } else {
        die('Error: Failed to create payment. ' . json_encode($jsonResponse));
    }
} else {
    die('Error: Failed to communicate with PayPal. HTTP Code: ' . $httpCode);
}
?>
