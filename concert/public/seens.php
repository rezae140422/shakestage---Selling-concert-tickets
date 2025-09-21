<?php
session_start();

// بررسی دسترسی ادمین
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // گرفتن تعداد بازدیدهای امروز
    $stmt = $pdo->prepare("SELECT visit_count FROM site_visits WHERE visit_date = CURDATE()");
    $stmt->execute();
    $totalToday = $stmt->fetch(PDO::FETCH_ASSOC)['visit_count'] ?? 0;

    // گرفتن تعداد بازدیدهای دیروز
    $stmt = $pdo->prepare("SELECT visit_count FROM site_visits WHERE visit_date = CURDATE() - INTERVAL 1 DAY");
    $stmt->execute();
    $totalYesterday = $stmt->fetch(PDO::FETCH_ASSOC)['visit_count'] ?? 0;

    // گرفتن تعداد بازدیدهای هفته پیش
    $stmt = $pdo->prepare("SELECT SUM(visit_count) FROM site_visits WHERE visit_date >= CURDATE() - INTERVAL 7 DAY");
    $stmt->execute();
    $totalLastWeek = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(visit_count)'] ?? 0;

    // گرفتن تعداد بازدیدهای ماه پیش
    $stmt = $pdo->prepare("SELECT SUM(visit_count) FROM site_visits WHERE visit_date >= CURDATE() - INTERVAL 1 MONTH");
    $stmt->execute();
    $totalLastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(visit_count)'] ?? 0;

    // گرفتن تعداد کل بازدیدها
    $stmt = $pdo->prepare("SELECT SUM(visit_count) FROM site_visits");
    $stmt->execute();
    $totalVisits = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(visit_count)'] ?? 0;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-bar-chart me-2"></i> Site Visit Reports
    </h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="text-center text-muted">Statistics</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Today</h5>
                            <p class="card-text"><?= $totalToday ?> Visits</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Yesterday</h5>
                            <p class="card-text"><?= $totalYesterday ?> Visits</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Last Week</h5>
                            <p class="card-text"><?= $totalLastWeek ?> Visits</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Last Month</h5>
                            <p class="card-text"><?= $totalLastMonth ?> Visits</p>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="text-center text-muted">Total Visits</h5>
            <div class="text-center">
                <h4 class="text-primary"><?= $totalVisits ?> Total Visits</h4>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../src/views/partials/footer.php';
?>
