<?php
session_start();

// تولید کد تصادفی 3 رقمی
$captchaCode = rand(100, 999);

// ذخیره کد در سشن
$_SESSION['captcha_code'] = $captchaCode;

// تنظیم هدر برای خروجی تصویر
header('Content-Type: image/png');

// ایجاد تصویر جدید با ابعاد 100x40
$image = imagecreate(100, 40);

// انتخاب رنگ‌های پس‌زمینه و متن
$backgroundColor = imagecolorallocate($image, 255, 255, 255); // پس‌زمینه سفید
$textColor = imagecolorallocate($image, 0, 0, 0); // رنگ متن مشکی

// پر کردن پس‌زمینه
imagefill($image, 0, 0, $backgroundColor);

// درج کد روی تصویر با فونت و موقعیت دلخواه
imagestring($image, 5, 30, 10, $captchaCode, $textColor);

// تولید و نمایش تصویر
imagepng($image);

// آزادسازی منابع
imagedestroy($image);
?>
