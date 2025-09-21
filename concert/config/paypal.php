<?php
return [
    // اطلاعات لایو
    'client_id' => 'AX0EJ3RCqco63Limnp0hi2zXliwCDnQW4quWA_TZUb_iQHthDHLz27UwOB5gDkwDc7V2HZTgS42aWZrY', // Client ID برای لایو
    'client_secret' => 'EI3SDDmFZ6hCGe_O2PYefWp6aI259pL6t_ryYnkS-8opspOjvyvyhAXlLR_rzFjvxNIdAp-f3uvzETIE', // Secret Key برای لایو
    'mode' => 'live',  // استفاده از حالت لایو

    // مسیر لاگ‌ها
    'log' => [
        'enabled' => true,
        'file' => __DIR__ . '/../storage/logs/paypal.log',  // مسیر فایل لاگ
        'level' => 'DEBUG',  // سطح لاگ برای اشکال‌زدایی
    ],

    // تنظیمات Auto Return و PDT
    'return_url' => 'https://shakestage.com/concert/public/paypal_success.php', // آدرس بازگشت موفقیت‌آمیز
    'cancel_url' => 'https://shakestage.com/concert/public/paypal_cancel.php',  // آدرس بازگشت در صورت لغو پرداخت
    'pdt_identity_token' => '7l0wI67IbVlETLTiEyn9AkhZx9C3ob-GL48j4sc7bJOrEN7zxsdpg-e5PY0', // توکن PDT که از تنظیمات PayPal دریافت می‌کنید

    // URL های PayPal
    'token_url' => 'https://api.paypal.com/v1/oauth2/token', // URL برای دریافت توکن لایو
    'payment_url' => 'https://api.paypal.com/v1/payments/payment', // URL برای ایجاد پرداخت
];
