<?php
session_start();

require_once __DIR__ . '/verify_token.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/views/partials/header.php';

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

// مسیر پوشه‌ها
$storagePath = __DIR__ . '/../storage';
$uploadsPath = $storagePath . '/uploads';
$sliderPath = $uploadsPath . '/slider';

// محاسبه حجم پوشه
function getFolderSize($folder)
{
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder)) as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

// مشاهده فایل‌ها
function getFiles($path)
{
    return array_filter(scandir($path), function ($file) use ($path) {
        return is_file($path . '/' . $file);
    });
}

// بررسی استفاده از فایل
function checkFileUsage($pdo, $fileName, $table, $column, $isFullPath = false)
{
    $filePath = $isFullPath ? "https://shakestage.com/concert/storage/uploads/slider/$fileName" : "storage/uploads/$fileName";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = :filePath");
    $stmt->execute([':filePath' => $filePath]);
    return $stmt->fetchColumn() > 0;
}

// عملیات روی فایل‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filePath = $_POST['file_path'] ?? '';

    if (!empty($filePath)) {
        switch ($action) {
            case 'delete':
                if (unlink($filePath)) {
                    echo "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'File deleted successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Could not delete the file.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    </script>";
                }
                break;

            case 'copy_path':
                $fileUrl = str_replace($_SERVER['DOCUMENT_ROOT'], 'https://shakestage.com', $filePath);
                echo "<script>
                    navigator.clipboard.writeText('$fileUrl').then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'File path copied to clipboard.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }).catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Could not copy file path.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    });
                </script>";
                break;
        }
    }
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $totalSize = getFolderSize($storagePath);
    $uploadsFiles = getFiles($uploadsPath);
    $sliderFiles = getFiles($sliderPath);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

?>

<main class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-folder2 me-2"></i> Manage Storage
    </h2>

    <div class="alert alert-info">
        <strong>Total Storage Size:</strong> <?= number_format($totalSize / (1024 * 1024), 2) ?> MB
    </div>

    <!-- فایل‌های آپلود -->
    <section class="mb-5">
        <h4><i class="bi bi-upload me-2"></i> Concert Images (Files Used in Concerts)</h4>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Preview</th>
                        <th>File Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploadsFiles as $file): ?>
                        <tr>
                            <td>
                                <img src="/concert/storage/uploads/<?= $file ?>" alt="<?= $file ?>" style="height: 50px; object-fit: cover;">
                            </td>
                            <td><?= htmlspecialchars($file) ?></td>
                            <td>
                                <?php
                                $isUsed = checkFileUsage($pdo, $file, 'concerts', 'banner');
                                echo $isUsed
                                    ? '<span class="badge bg-success">Used in Concerts</span>'
                                    : '<span class="badge bg-secondary">Not Used</span>';
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="file_path" value="<?= $uploadsPath . '/' . $file ?>">
                                    <button name="action" value="copy_path" class="btn btn-info btn-sm">
                                        <i class="bi bi-link-45deg"></i> Copy Path
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="file_path" value="<?= $uploadsPath . '/' . $file ?>">
                                    <button name="action" value="delete" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- فایل‌های اسلایدر -->
    <section>
        <h4><i class="bi bi-sliders me-2"></i> Files in Slider</h4>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Preview</th>
                        <th>File Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sliderFiles as $file): ?>
                        <tr>
                            <td>
                                <img src="/concert/storage/uploads/slider/<?= $file ?>" alt="<?= $file ?>" style="height: 50px; object-fit: cover;">
                            </td>
                            <td><?= htmlspecialchars($file) ?></td>
                            <td>
                                <?php
                                $isUsed = checkFileUsage($pdo, $file, 'slider_settings', 'image_path', true);
                                echo $isUsed
                                    ? '<span class="badge bg-success">Used in Slider</span>'
                                    : '<span class="badge bg-secondary">Not Used</span>';
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="file_path" value="<?= $sliderPath . '/' . $file ?>">
                                    <button name="action" value="copy_path" class="btn btn-info btn-sm">
                                        <i class="bi bi-link-45deg"></i> Copy Path
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="file_path" value="<?= $sliderPath . '/' . $file ?>">
                                    <button name="action" value="delete" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="/concert/public/assets/js/sweetalert2.all.min.js"></script>
<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>
