<?php
// اتصال به دیتابیس
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../public/check_user_login.php';

// دریافت شناسه کنسرت از URL
if (isset($_GET['id'])) {
    $concert_id = $_GET['id'];

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // دریافت اطلاعات کنسرت
        $stmt = $pdo->prepare('SELECT * FROM concerts WHERE id = :id');
        $stmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
        $stmt->execute();
        $concert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$concert) {
            die('Concert not found.');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('Concert ID is required.');
}

// بررسی وضعیت ورود کاربر
$is_logged_in = check_user_login();

// محاسبه زمان باقی‌مانده تا رویداد
$eventDate = new DateTime($concert['event_date']);
$now = new DateTime();
$interval = $now->diff($eventDate);
$isPast = $eventDate < $now; // بررسی اینکه تاریخ گذشته است
$daysRemaining = $interval->days;
$timeRemainingTextFa = "";
$timeRemainingTextEn = "";

if ($isPast) {
    $timeRemainingTextFa = "تمام شده";
    $timeRemainingTextEn = "Finished";
} elseif ($daysRemaining === 0) {
    $timeRemainingTextFa = "امروز اجرا می‌شود";
    $timeRemainingTextEn = "Happening Today";
} else {
    $months = $interval->m;
    $days = $interval->d;
    if ($months > 0) {
        $timeRemainingTextFa = sprintf("تنها %d ماه و %d روز مانده", $months, $days);
        $timeRemainingTextEn = sprintf("%d months and %d days remaining", $months, $days);
    } else {
        $timeRemainingTextFa = sprintf("تنها %d روز مانده", $days);
        $timeRemainingTextEn = sprintf("%d days remaining", $days);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concert Details</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>
<?php include __DIR__ . '/../src/views/partials/header.php'; ?>

<div class="container mt-5">
    <!-- کارت کنسرت -->
    <div class="card shadow-lg concert-card">
        <img src="/concert/<?= htmlspecialchars($concert['banner']) ?>" class="card-img-top" alt="Concert Image" style="width:100%; height: 400px; object-fit: cover;">

        <div class="card-body">
            <!-- عنوان کنسرت -->
            <h2 class="text-primary text-center mb-4"><?= htmlspecialchars($concert['name']) ?></h2>

            <!-- جزئیات کنسرت -->
            <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
        <i class="bi bi-calendar-event me-2 text-primary"></i>
        <strong>Event Date:</strong> <?= date('F j, Y, g:i a', strtotime($concert['event_date'])) ?>
        <br>
        <span class="badge bg-info text-white"><?= htmlspecialchars($timeRemainingTextEn) ?></span>
        <span class="badge bg-secondary text-white"><?= htmlspecialchars($timeRemainingTextFa) ?></span>
    </li>
                <li class="list-group-item">
                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                    <strong>Location:</strong> <?= htmlspecialchars($concert['location']) ?>
                </li>
                <li class="list-group-item">
                    <i class="bi bi-tags me-2 text-primary"></i>
                    <strong>Tags:</strong>
                    <?php foreach (explode(',', $concert['tags']) as $tag): ?>
                        <span class="badge bg-primary text-white"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </li>
                <li class="list-group-item">
                    <i class="bi bi-person-fill me-2 text-primary"></i>
                    <strong>Capacity:</strong> <?= htmlspecialchars($concert['capacity']) ?>
                </li>
                <li class="list-group-item">
                    <i class="bi bi-file-text me-2 text-primary"></i>
                    <strong>Description:</strong> <?= nl2br(htmlspecialchars($concert['description'])) ?>
                </li>
            </ul>

            <!-- لینک خارجی -->
            <?php if (!empty($concert['external_link'])): ?>
                <a href="<?= htmlspecialchars($concert['external_link']) ?>" target="_blank" class="btn btn-outline-primary mb-3">
                    <i class="bi bi-link"></i> Visit External Link
                </a>
            <?php endif; ?>

            <!-- دکمه رزرو -->
            <?php if ($isPast): ?>
                <button class="btn btn-secondary mb-3" disabled>
                    <i class="bi bi-check-circle"></i> Reservation Closed
                </button>
            <?php elseif ($is_logged_in): ?>
                <a href="reserve_ticket.php?id=<?= urlencode($concert['id']) ?>" class="btn btn-success mb-3">
                    <i class="bi bi-check-circle"></i> Reserve Ticket
                </a>
            <?php else: ?>
                <a href="/concert/public/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary mb-3">
                    <i class="bi bi-person-fill"></i> Login to Reserve
                </a>
            <?php endif; ?>

            <!-- نقشه -->
            <?php if ($concert['latitude'] && $concert['longitude']): ?>
                <div id="map" style="height: 400px; margin-top: 20px;"></div>
                <h5 class="text-primary mt-3"><i class="bi bi-map"></i> Navigation Options:</h5>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $concert['latitude'] ?>,<?= $concert['longitude'] ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-geo-alt"></i> Google Maps
                    </a>
                    <a href="https://waze.com/ul?ll=<?= $concert['latitude'] ?>,<?= $concert['longitude'] ?>&navigate=yes" target="_blank" class="btn btn-outline-info">
                        <i class="bi bi-compass"></i> Waze
                    </a>
                    <a href="https://wego.here.com/directions/mix/<?= $concert['latitude'] ?>,<?= $concert['longitude'] ?>" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-map"></i> Here WeGo
                    </a>
                </div>
                <script>
                    var map = L.map('map').setView([<?= $concert['latitude'] ?>, <?= $concert['longitude'] ?>], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);
                    var marker = L.marker([<?= $concert['latitude'] ?>, <?= $concert['longitude'] ?>]).addTo(map);
                    marker.bindPopup("<b><?= $concert['name'] ?></b><br><?= $concert['location'] ?>").openPopup();
                </script>
            <?php endif; ?>

            <!-- بازگشت به رویدادها -->
            <a href="/concert/public/index.php" class="btn btn-secondary mt-3">
                <i class="bi bi-arrow-left-circle"></i> Back to Events
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/views/partials/footer.php'; ?>
<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
