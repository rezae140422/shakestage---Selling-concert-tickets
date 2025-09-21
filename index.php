<?php
// ایمپورت هدر
include __DIR__ . '/concert/src/views/partials/header.php'; // مسیر صحیح به هدر

// اتصال به دیتابیس
require_once __DIR__ . '/concert/config/database.php';

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // تاریخ امروز
    $today = date('Y-m-d');

    // بررسی اینکه آیا رکوردی برای امروز وجود دارد
    $stmt = $pdo->prepare("SELECT * FROM site_visits WHERE visit_date = :visit_date");
    $stmt->bindParam(':visit_date', $today);
    $stmt->execute();
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($visit) {
        // اگر رکورد برای امروز وجود داشت، تعداد بازدیدها را افزایش بده
        $new_count = $visit['visit_count'] + 1;
        $updateStmt = $pdo->prepare("UPDATE site_visits SET visit_count = :visit_count WHERE visit_date = :visit_date");
        $updateStmt->bindParam(':visit_count', $new_count);
        $updateStmt->bindParam(':visit_date', $today);
        $updateStmt->execute();
    } else {
        // اگر رکوردی برای امروز وجود نداشت، یک رکورد جدید ایجاد کن
        $insertStmt = $pdo->prepare("INSERT INTO site_visits (visit_date, visit_count) VALUES (:visit_date, 1)");
        $insertStmt->bindParam(':visit_date', $today);
        $insertStmt->execute();
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<main class="container mt-5">
    <div class="text-center">
        <h1>Welcome to our Concert Site!</h1>
        <p>Discover amazing concerts, events, and more!</p>
    </div>

    <!-- نمایش اسلایدر -->
    <?php include __DIR__ . '/concert/public/slider.php'; ?> <!-- مسیر صحیح به فایل اسلایدر -->

    <!-- نمایش کنسرت‌ها -->
    <?php include __DIR__ . '/concert/public/concerts_display.php'; ?> <!-- مسیر صحیح به فایل نمایش کنسرت‌ها -->
</main>

<?php
// ایمپورت فوتر
include __DIR__ . '/concert/src/views/partials/footer.php'; // مسیر صحیح به فوتر
?>
