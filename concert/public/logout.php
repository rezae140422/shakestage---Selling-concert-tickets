<?php
session_start();

// پاک کردن تمام کوکی‌ها
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode('; ', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
    }
}

// پاک کردن سشن
session_unset();
session_destroy();

// پاک کردن داده‌های Local Storage و Session Storage
echo "<script>
localStorage.clear();
sessionStorage.clear();
</script>";

// هدایت به صفحه لاگین یا صفحه اصلی
header('Location: /concert/public/login.php');
exit;
?>
