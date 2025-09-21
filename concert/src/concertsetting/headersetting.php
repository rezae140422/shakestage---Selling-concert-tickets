<?php
session_start();

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

// اتصال به دیتابیس
require_once __DIR__ . '/../../config/database.php';

$message = '';
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت اطلاعات هدر
    $stmt = $pdo->query("SELECT * FROM header_settings LIMIT 1");
    $headerSettings = $stmt->fetch(PDO::FETCH_ASSOC);

    // تبدیل لینک‌ها به آرایه
    $headerLinks = json_decode($headerSettings['links'] ?? '[]', true) ?: [];
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // لینک‌های جدید
        $headerLinks = [];
        for ($i = 0; $i < 5; $i++) {
            if (isset($_POST["header_title_$i"]) && isset($_POST["header_link_$i"])) {
                $headerLinks[] = [
                    'title' => $_POST["header_title_$i"],
                    'link' => $_POST["header_link_$i"],
                    'active' => isset($_POST["header_active_$i"]) ? 1 : 0,
                ];
            }
        }

        // بررسی حداقل 2 لینک فعال
        $activeCount = array_reduce($headerLinks, function ($count, $link) {
            return $count + ($link['active'] ? 1 : 0);
        }, 0);

        if ($activeCount < 2) {
            throw new Exception('At least 2 menu items must be active.');
        }

        // لینک Home را ثابت نگه می‌داریم
        $headerLinks[0] = [
            'title' => 'Home',
            'link' => 'https://shakestage.com/',
            'active' => 1, // همیشه فعال است
        ];

        // ذخیره در دیتابیس
        $stmt = $pdo->prepare("UPDATE header_settings SET links = :links WHERE id = 1");
        $stmt->execute([':links' => json_encode($headerLinks)]);

        $message = 'Header settings saved successfully.';
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
    <title>Header Settings</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Header Settings</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Link</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <input 
                                type="text" 
                                name="header_title_<?= $i ?>" 
                                class="form-control" 
                                value="<?= htmlspecialchars($headerLinks[$i]['title'] ?? ($i === 0 ? 'Home' : '')) ?>" 
                                <?= $i === 0 ? 'readonly' : '' ?> 
                                title="<?= $i === 0 ? 'Cannot edit Home title' : '' ?>">
                        </td>
                        <td>
                            <input 
                                type="url" 
                                name="header_link_<?= $i ?>" 
                                class="form-control" 
                                value="<?= htmlspecialchars($headerLinks[$i]['link'] ?? ($i === 0 ? 'https://shakestage.com/' : '')) ?>" 
                                <?= $i === 0 ? 'readonly' : '' ?> 
                                title="<?= $i === 0 ? 'Cannot edit Home link' : '' ?>">
                        </td>
                        <td class="text-center">
                            <input 
                                type="checkbox" 
                                name="header_active_<?= $i ?>" 
                                <?= isset($headerLinks[$i]['active']) && $headerLinks[$i]['active'] ? 'checked' : ($i === 0 ? 'checked disabled' : '') ?>>
                        </td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary w-100">Save Header Settings</button>
    </form>
</div>
<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
