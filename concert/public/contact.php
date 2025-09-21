<?php
session_start(); // شروع جلسه برای مدیریت کپچا
require_once __DIR__ . '/../config/database.php'; // فایل اتصال به دیتابیس

// تابع تولید کپچای سه‌رقمی
function generateCaptcha() {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // حذف حروف مشابه مثل I و O
    $captcha = substr(str_shuffle($characters), 0, 3); // تولید کپچا 3 کاراکتری
    $_SESSION['captcha'] = $captcha;
    $_SESSION['captcha_expiry'] = time() + 60; // تنظیم زمان انقضای کپچا (1 دقیقه)
}

// تولید کپچا در بارگذاری اولیه یا انقضای زمان
if (!isset($_SESSION['captcha']) || time() > $_SESSION['captcha_expiry']) {
    generateCaptcha();
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    $captcha = $_POST['captcha'] ?? '';

    // بررسی کپچا (بدون حساسیت به بزرگی و کوچکی حروف)
    if (strcasecmp($captcha, $_SESSION['captcha']) !== 0) {
        $error = "Invalid CAPTCHA. Please try again.";
        generateCaptcha(); // تولید کپچای جدید
    } else {
        try {
            // اتصال به دیتابیس
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
                DB_USERNAME,
                DB_PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // ذخیره اطلاعات در دیتابیس
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, created_at) VALUES (:name, :email, :message, NOW())");
            $stmt->execute([
                ':name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                ':email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            ]);

            // پاک کردن کپچا پس از ذخیره موفقیت‌آمیز
            unset($_SESSION['captcha']);
            $success = "Your message has been successfully sent!";
            generateCaptcha(); // تولید کپچای جدید برای استفاده مجدد
        } catch (PDOException $e) {
            $error = "Failed to save your message. Please try again later.";
        }
    }
}
?>

<?php include __DIR__ . '/../src/views/partials/header.php'; ?>

<main class="container mt-5">
    <div class="text-center">
        <h1 class="mb-4 text-primary"><i class="bi bi-telephone-fill"></i> Contact Us</h1>
        <p class="text-muted mb-4">We are here to assist you! Feel free to reach out to us through the following methods.</p>

        <!-- اطلاعات تماس -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-start">
                        <h4 class="text-primary"><i class="bi bi-envelope-fill"></i> Email</h4>
                        <p class="text-muted">info@shakestage.com</p>

                        <h4 class="text-primary mt-4"><i class="bi bi-whatsapp"></i> WhatsApp</h4>
                        <p class="text-muted">+32 488110881</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- فرم تماس -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="text-primary text-center mb-4"><i class="bi bi-chat-dots"></i> Send Us a Message</h4>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your message" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="captcha" class="form-label">Enter CAPTCHA</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light" id="captcha-code">
                                        <strong style="font-family: monospace;"><?= $_SESSION['captcha'] ?></strong>
                                    </span>
                                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="Enter the CAPTCHA" required>
                                    <button type="button" class="btn btn-secondary" id="refresh-captcha"><i class="bi bi-arrow-clockwise"></i></button>
                                </div>
                                <small class="text-muted d-block mt-1" id="captcha-timer">Expires in 60 seconds</small>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // مدیریت تایمر و رفرش کپچا
    let timer = 60;
    const timerElement = document.getElementById('captcha-timer');
    const captchaCode = document.getElementById('captcha-code');
    const refreshButton = document.getElementById('refresh-captcha');

    const countdown = setInterval(() => {
        timer--;
        timerElement.textContent = `Expires in ${timer} seconds`;
        if (timer <= 0) {
            refreshCaptcha(); // تولید کپچای جدید
        }
    }, 1000);

    const refreshCaptcha = () => {
        clearInterval(countdown);
        fetch(location.href) // رفرش کپچا
            .then(() => location.reload());
    };

    refreshButton.addEventListener('click', refreshCaptcha);
</script>

<?php include __DIR__ . '/../src/views/partials/footer.php'; ?>
