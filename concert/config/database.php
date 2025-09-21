<?php
// تابع برای بارگذاری فایل .env
function loadEnv($path)
{
    if (!file_exists($path)) {
        die("Environment file not found: " . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // نادیده گرفتن خطوط کامنت
        }

        $keyValue = explode('=', $line, 2);
        if (count($keyValue) === 2) {
            $key = trim($keyValue[0]);
            $value = trim($keyValue[1]);
            $_ENV[$key] = $value;
        }
    }
}

// بارگذاری متغیرهای .env
loadEnv(__DIR__ . '/../.env');

// تعریف متغیرهای دیتابیس
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_DATABASE', $_ENV['DB_DATABASE']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
?>
