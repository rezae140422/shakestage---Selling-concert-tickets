<?php
session_start();

// بررسی توکن معتبر
require_once __DIR__ . '/verify_token.php'; // مسیر به فایل در پوشه public

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// اگر درخواست پاک کردن ارسال شده باشد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    try {
        // اتصال به دیتابیس
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // حذف تمام گزارش‌های ورود از جدول
        $stmt = $pdo->prepare("DELETE FROM login_logs");
        $stmt->execute();

        // پیام موفقیت آمیز
        $message = "All records have been deleted recently.";
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // گرفتن گزارش ورودها از جدول login_logs
    $stmt = $pdo->prepare("SELECT * FROM login_logs ORDER BY login_time DESC");
    $stmt->execute();
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-file-earmark-lock me-2"></i> Login Reports
    </h2>

    <!-- Table to display login logs -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($logins)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i> No records found or all records have been deleted recently.
                </div>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="bi bi-hash me-2"></i>ID</th>
                            <th><i class="bi bi-person-circle me-2"></i>User Email</th>
                            <th><i class="bi bi-geo-alt me-2"></i>IP Address</th>
                            <th><i class="bi bi-clock me-2"></i>Login Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logins as $login): ?>
                            <tr>
                                <td><?= htmlspecialchars($login['id']) ?></td>
                                <td><?= htmlspecialchars($login['user_email']) ?></td>
                                <td><?= htmlspecialchars($login['user_ip']) ?></td>
                                <td><?= (new DateTime($login['login_time']))->format('F j, Y, g:i a') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Button to delete all logs -->
    <form method="POST" action="/concert/public/loglogins.php" class="mt-3 text-center">
        <button type="submit" name="delete_all" class="btn btn-danger">
            <i class="bi bi-trash me-2"></i>Delete All Logs
        </button>
    </form>
</main>

<?php
require_once __DIR__ . '/../src/views/partials/footer.php';
?>
