<?php
// بارگذاری تنظیمات PayPal
$config = require_once __DIR__ . '/paypal.php';

$clientId = $config['client_id'];
$clientSecret = $config['client_secret'];
$tokenUrl = $config['token_url'];

// تبدیل اطلاعات به فرمت Base64 برای Authorization Header
$basicAuth = base64_encode("$clientId:$clientSecret");

// داده‌های ارسال شده به PayPal
$data = [
    'grant_type' => 'client_credentials'
];

// درخواست CURL برای دریافت توکن
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic $basicAuth",
    "Content-Type: application/x-www-form-urlencoded"
]);

$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $jsonResponse = json_decode($response, true);
    if (isset($jsonResponse['access_token'])) {
        // ذخیره توکن در سشن
        $_SESSION['paypal_access_token'] = $jsonResponse['access_token'];
    } else {
        echo "Error: " . $jsonResponse['error_description'];
    }
} else {
    echo "Error: " . curl_error($ch);
}
?>
