<?php
require_once __DIR__ . '/../config/database.php';

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت اسلایدهای فعال از دیتابیس
    $stmt = $pdo->query("SELECT * FROM slider_settings WHERE is_active = 1 ORDER BY sort_order");
    $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<div id="custom-slider-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php if (count($sliders) > 0): ?>
            <?php foreach ($sliders as $index => $slider): ?>
                <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                    <!-- تصویر با Lazy Load -->
                    <img 
                        src="<?= htmlspecialchars($slider['image_path']) ?>" 
                        class="d-block w-100" 
                        alt="<?= htmlspecialchars($slider['title']) ?>" 
                        loading="lazy" 
                        style="object-fit: cover; height: 50vh;">
                    <div class="carousel-caption d-flex flex-column align-items-center">
                        <!-- تیتر -->
                        <h5 class="display-6 fw-bold d-inline-block bg-dark bg-opacity-50 text-white px-3 py-2 rounded mb-3 text-center">
                            <?= (strlen($slider['title']) > 50) ? substr(htmlspecialchars($slider['title']), 0, 50) . '...' : htmlspecialchars($slider['title']) ?>
                        </h5>
                        <!-- دکمه -->
                        <a href="<?= htmlspecialchars($slider['link']) ?>" class="btn btn-dark text-white shadow">
                            <i class="bi bi-arrow-right-circle me-2"></i> Read More
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No active sliders available.</p>
        <?php endif; ?>
    </div>
    <?php if (count($sliders) > 1): ?>
        <!-- کنترل‌های چپ و راست -->
        <button class="carousel-control-prev" type="button" data-bs-target="#custom-slider-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#custom-slider-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    <?php endif; ?>
</div>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
