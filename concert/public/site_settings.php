<?php
session_start();

// بررسی توکن معتبر
include __DIR__ . '/verify_token.php';

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

include __DIR__ . '/../src/views/partials/header.php';

// دریافت تب فعال از کوئری استرینگ
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'concert_settings';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">Site Settings</h2>
    <div class="row">
        <!-- منوی کناری -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="?tab=concert_settings" class="list-group-item list-group-item-action <?php echo $activeTab === 'concert_settings' ? 'active' : ''; ?>">
                    <i class="bi bi-music-note-beamed me-2"></i> Concert Settings
                </a>
                <a href="?tab=slider_settings" class="list-group-item list-group-item-action <?php echo $activeTab === 'slider_settings' ? 'active' : ''; ?>">
                    <i class="bi bi-sliders me-2"></i> Slider Settings
                </a>
                <a href="?tab=footer_settings" class="list-group-item list-group-item-action <?php echo $activeTab === 'footer_settings' ? 'active' : ''; ?>">
                    <i class="bi bi-columns-gap me-2"></i> Footer Settings
                </a>
                <a href="?tab=header_settings" class="list-group-item list-group-item-action <?php echo $activeTab === 'header_settings' ? 'active' : ''; ?>">
                    <i class="bi bi-layout-text-sidebar-reverse me-2"></i> Header Settings
                </a>
            </div>
        </div>

        <!-- محتوای تب -->
        <div class="col-md-9">
            <?php if ($activeTab === 'concert_settings'): ?>
                <?php include __DIR__ . '/../src/concertsetting/concertsetting.php'; ?>

            <?php elseif ($activeTab === 'slider_settings'): ?>
                <?php include __DIR__ . '/../src/concertsetting/concertslidersetting.php'; ?>

            <?php elseif ($activeTab === 'footer_settings'): ?>
                <?php include __DIR__ . '/../src/concertsetting/footersetting.php'; ?>

            <?php elseif ($activeTab === 'header_settings'): ?>
                <?php include __DIR__ . '/../src/concertsetting/headersetting.php'; ?>

            <?php else: ?>
                <p class="text-center">Invalid tab selected.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
