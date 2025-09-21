<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت اطلاعات فوتر از دیتابیس
    $stmt = $pdo->query("SELECT * FROM footer_settings LIMIT 1");
    $footerSettings = $stmt->fetch(PDO::FETCH_ASSOC);

    // تبدیل داده‌های JSON به آرایه
    $quickLinks = json_decode($footerSettings['quick_links'], true) ?: [];
    $socialLinks = json_decode($footerSettings['social_links'], true) ?: [];
    $phones = json_decode($footerSettings['phones'], true) ?: [];
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<footer class="bg-light py-4 border-top">
    <div class="container text-center text-md-start">
        <div class="row">
            <!-- About Section -->
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold">About ShakeStage</h5>
                <p class="small text-muted">
                    <?= htmlspecialchars($footerSettings['about_text']) ?: 'ShakeStage is your go-to platform for discovering and booking the best concerts around. Join us and feel the music like never before.'; ?>
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold">Quick Links</h5>
                <ul class="list-unstyled small">
                    <?php if (!empty($quickLinks)): ?>
                        <?php foreach ($quickLinks as $link): ?>
                            <li>
                                <a href="<?= htmlspecialchars($link['link']) ?>" class="text-decoration-none text-muted" target="_blank">
                                    <?= htmlspecialchars($link['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No quick links available.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Social Media & Contact -->
            <div class="col-md-4">
                <h5 class="fw-bold">Follow Us</h5>
                <p class="small text-muted">Stay connected and never miss an update!</p>
                <div>
                    <?php if (!empty($socialLinks)): ?>
                        <?php foreach ($socialLinks as $social): ?>
                            <a href="<?= htmlspecialchars($social['link']) ?>" target="_blank" class="text-decoration-none me-2">
                                <i class="bi bi-<?= strtolower($social['platform']) ?> fs-4"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No social links available.</p>
                    <?php endif; ?>
                </div>
                <p class="mt-3">
                    <i class="bi bi-envelope"></i> 
                    <a href="mailto:support@shakestage.com" class="text-decoration-none text-muted">support@shakestage.com</a>
                </p>
                <?php if (!empty($phones)): ?>
                    <ul class="list-unstyled mt-2">
                        <?php foreach ($phones as $phone): ?>
                            <li>
                                <i class="bi bi-telephone"></i> 
                                <?= htmlspecialchars($phone['phone']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No phone numbers available.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 text-center small text-muted border-top pt-3">
            &copy; <?php echo date('Y'); ?> 
            <a href="https://shakestage.com/" target="_blank" class="text-decoration-none">ShakeStage</a>. 
            All Rights Reserved.
        </div>
    </div>
</footer>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
