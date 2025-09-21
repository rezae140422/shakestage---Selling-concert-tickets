<?php
session_start();

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$message = '';

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت تنظیمات (ایجاد رکورد پیش‌فرض در صورت خالی بودن)
    $stmt = $pdo->query("SELECT * FROM footer_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        $pdo->exec("
            INSERT INTO `footer_settings` (`id`, `about_text`, `quick_links`, `social_links`, `phones`, `created_at`, `updated_at`)
            VALUES (1, '', '[]', '[]', '[]', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt = $pdo->query("SELECT * FROM footer_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $aboutText = $_POST['about_text'] ?? '';
        $quickLinks = isset($_POST['quick_links']) && count($_POST['quick_links']) > 0 ? json_encode($_POST['quick_links']) : '[]';
        $socialLinks = isset($_POST['social_links']) && count($_POST['social_links']) > 0 ? json_encode($_POST['social_links']) : '[]';
        $phones = isset($_POST['phones']) && count($_POST['phones']) > 0 ? json_encode($_POST['phones']) : '[]';

        // بررسی حداقل یک المان برای هر بخش
        if (empty($aboutText) && $quickLinks === '[]' && $socialLinks === '[]' && $phones === '[]') {
            throw new Exception('At least one item in each section is required.');
        }

        $stmt = $pdo->prepare("
            UPDATE footer_settings
            SET about_text = :about_text, quick_links = :quick_links, social_links = :social_links, phones = :phones, updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        $stmt->execute([
            ':about_text' => $aboutText,
            ':quick_links' => $quickLinks,
            ':social_links' => $socialLinks,
            ':phones' => $phones,
        ]);

        $message = 'Footer settings saved successfully.';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Settings</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Footer Settings</h2>
    <?php if ($message): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST">
        <!-- About ShakeStage -->
        <h4>About ShakeStage</h4>
        <div class="mb-3">
            <textarea name="about_text" class="form-control" rows="3" placeholder="Enter about text"><?= htmlspecialchars($settings['about_text']) ?></textarea>
        </div>

        <!-- Quick Links -->
        <h4>Quick Links</h4>
        <div id="quick-links-wrapper" class="mb-3">
            <button type="button" class="btn btn-primary btn-sm mb-2" onclick="addQuickLink()">Add Quick Link</button>
            <div id="quick-links-list">
                <?php
                $quickLinks = json_decode($settings['quick_links'], true);
                if (empty($quickLinks)) {
                    $quickLinks[] = ['name' => '', 'link' => ''];
                }
                foreach ($quickLinks as $index => $link): ?>
                    <div class="input-group mb-2">
                        <input type="text" name="quick_links[<?= $index ?>][name]" class="form-control" placeholder="Name" value="<?= htmlspecialchars($link['name']) ?>">
                        <input type="url" name="quick_links[<?= $index ?>][link]" class="form-control" placeholder="Link" value="<?= htmlspecialchars($link['link']) ?>">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Social Links -->
        <h4>Social Links</h4>
        <div id="social-links-wrapper" class="mb-3">
            <button type="button" class="btn btn-primary btn-sm mb-2" onclick="addSocialLink()">Add Social Link</button>
            <div id="social-links-list">
                <?php
                $socialLinks = json_decode($settings['social_links'], true);
                if (empty($socialLinks)) {
                    $socialLinks[] = ['platform' => 'WhatsApp', 'link' => ''];
                }
                foreach ($socialLinks as $index => $link): ?>
                    <div class="input-group mb-2">
                        <select name="social_links[<?= $index ?>][platform]" class="form-select">
                            <option value="WhatsApp" <?= $link['platform'] === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                            <option value="Instagram" <?= $link['platform'] === 'Instagram' ? 'selected' : '' ?>>Instagram</option>
                            <option value="Facebook" <?= $link['platform'] === 'Facebook' ? 'selected' : '' ?>>Facebook</option>
                            <option value="Twitter" <?= $link['platform'] === 'Twitter' ? 'selected' : '' ?>>Twitter</option>
                            <option value="Telegram" <?= $link['platform'] === 'Telegram' ? 'selected' : '' ?>>Telegram</option>
                        </select>
                        <input type="url" name="social_links[<?= $index ?>][link]" class="form-control" placeholder="Link" value="<?= htmlspecialchars($link['link']) ?>">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Phone Numbers -->
        <h4>Phone Numbers</h4>
        <div id="phones-wrapper" class="mb-3">
            <button type="button" class="btn btn-primary btn-sm mb-2" onclick="addPhone()">Add Phone</button>
            <div id="phones-list">
                <?php
                $phones = json_decode($settings['phones'], true);
                if (empty($phones)) {
                    $phones[] = ['phone' => ''];
                }
                foreach ($phones as $index => $phone): ?>
                    <div class="input-group mb-2">
                        <input type="text" name="phones[<?= $index ?>][phone]" class="form-control" placeholder="Phone Number" value="<?= htmlspecialchars($phone['phone']) ?>">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100">Save Settings</button>
    </form>
</div>

<script>
    function addQuickLink() {
        const wrapper = document.getElementById('quick-links-list');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="input-group mb-2">
                <input type="text" name="quick_links[][name]" class="form-control" placeholder="Name">
                <input type="url" name="quick_links[][link]" class="form-control" placeholder="Link">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            </div>
        `);
    }

    function addSocialLink() {
        const wrapper = document.getElementById('social-links-list');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="input-group mb-2">
                <select name="social_links[][platform]" class="form-select">
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Instagram">Instagram</option>
                    <option value="Facebook">Facebook</option>
                    <option value="Twitter">Twitter</option>
                    <option value="Telegram">Telegram</option>
                </select>
                <input type="url" name="social_links[][link]" class="form-control" placeholder="Link">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            </div>
        `);
    }

    function addPhone() {
        const wrapper = document.getElementById('phones-list');
        wrapper.insertAdjacentHTML('beforeend', `
            <div class="input-group mb-2">
                <input type="text" name="phones[][phone]" class="form-control" placeholder="Phone Number">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
            </div>
        `);
    }
</script>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
