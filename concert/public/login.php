<?php
// ایمپورت هدر
include __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">Login</h2>
    <form id="loginForm" method="POST" action="/concert/public/login_handler.php" class="mx-auto shadow-lg p-4 rounded" style="max-width: 450px; background-color: #ffffff;">
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="bi bi-envelope-fill me-2"></i>Email Address
            </label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="bi bi-lock-fill me-2"></i>Password
            </label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>
        
        <!-- کپچا 3 رقمی -->
        <div class="mb-3">
            <label for="captcha" class="form-label"><i class="bi bi-shield-lock me-2"></i>Captcha (Enter the code below)</label>
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-md-8">
                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="Enter the captcha code" required>
                </div>
                <div class="col-md-3">
                    <img src="generate_captcha.php" alt="Captcha" class="img-fluid" id="captcha-img" style="max-width: 120px;">
                </div>
            </div>
            <button type="button" class="btn btn-link p-0 mt-2" id="change-captcha">
                <i class="bi bi-arrow-clockwise"></i> Change Captcha
            </button>
            <div id="timer" class="mt-2 text-muted"></div>
        </div>

        <button type="submit" class="btn btn-primary w-100" id="submit-btn">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>
        <p class="mt-3 text-center">
            Don't have an account? 
            <a href="/concert/public/register.php">Register here</a>
        </p>

        <div class="text-center mt-3">
            <p>Need Help? Contact Support</p>
            <a href="https://wa.me/32488110881" class="btn btn-success" target="_blank">
                <i class="bi bi-whatsapp me-2"></i>Chat with Support
            </a>
        </div>
    </form>
</main>

<script>
    // تغییر کد کپچا هر 1 دقیقه
    let captchaTimeout;
    let captchaTimeLeft = 60;

    function updateTimer() {
        document.getElementById('timer').textContent = `Captcha will reset in ${captchaTimeLeft}s`;
        if (captchaTimeLeft <= 0) {
            clearInterval(captchaTimeout);
            document.getElementById('change-captcha').disabled = false;
            captchaTimeLeft = 60;
            return;
        }
        captchaTimeLeft--;
    }

    // شروع شمارش معکوس
    function startCaptchaTimer() {
        captchaTimeout = setInterval(updateTimer, 1000);
        document.getElementById('change-captcha').disabled = true;
        updateTimer();
    }

    // تغییر کد کپچا
    document.getElementById('change-captcha').addEventListener('click', function() {
        document.getElementById('captcha-img').src = 'generate_captcha.php?' + new Date().getTime(); // به‌روزرسانی تصویر کپچا
        captchaTimeLeft = 60; // ریست کردن تایمر
        startCaptchaTimer();
    });

    // تغییر خودکار کد کپچا بعد از بارگذاری صفحه
    window.onload = function() {
        startCaptchaTimer();
    };
</script>

<?php
// ایمپورت فوتر
include __DIR__ . '/../src/views/partials/footer.php';
?>
