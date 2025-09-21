<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$message = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت اطلاعات اسلایدرها از دیتابیس
    $stmt = $pdo->query("SELECT * FROM slider_settings ORDER BY sort_order");
    $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ایجاد مقدار پیش‌فرض برای slider_order
    $sliderOrderDefault = array_column($sliders, 'id');
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sliderOrder = isset($_POST['slider_order']) ? json_decode($_POST['slider_order'], true) : $sliderOrderDefault;

        if (empty($sliderOrder) || !is_array($sliderOrder)) {
            throw new Exception('Invalid slider order format or empty slider order.');
        }

        foreach ($sliderOrder as $order => $id) {
            $title = !empty($_POST["slider_title_$id"]) ? $_POST["slider_title_$id"] : null;
            $link = !empty($_POST["slider_link_$id"]) ? $_POST["slider_link_$id"] : null;
            $isActive = isset($_POST["slider_active_$id"]) ? 1 : 0;
            $imagePath = null;

            // مدیریت آپلود تصویر
            if (isset($_FILES["slider_image_$id"]) && $_FILES["slider_image_$id"]['error'] === UPLOAD_ERR_OK) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/concert/storage/uploads/slider/';
                $uploadUrl = 'https://shakestage.com/concert/storage/uploads/slider/';
                $file = $_FILES["slider_image_$id"];
                $fileName = uniqid('slider_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $fileName;
                $fileUrl = $uploadUrl . $fileName;

                if ($file['size'] > 5 * 1024 * 1024) {
                    $message .= "Slider $id: File size exceeds the limit of 5 MB.<br>";
                    continue;
                }

                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    $message .= "Slider $id: Failed to upload the file.<br>";
                    continue;
                }

                $imagePath = $fileUrl;
            }

            // به‌روزرسانی دیتابیس
            $stmt = $pdo->prepare("
                UPDATE slider_settings
                SET title = :title, link = :link, image_path = COALESCE(:image_path, image_path), is_active = :is_active, sort_order = :sort_order
                WHERE id = :id
            ");
            $stmt->execute([
                ':title' => $title,
                ':link' => $link,
                ':image_path' => $imagePath,
                ':is_active' => $isActive,
                ':sort_order' => $order + 1,
                ':id' => $id
            ]);
        }

        $message = 'Slider settings saved successfully.';
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
    <title>Slider Settings</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <style>
        .draggable { cursor: grab; }
        .draggable:active { cursor: grabbing; }
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        .card {
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            background-color: #007bff;
            color: #fff;
        }
        .card-header h5 {
            margin: 0;
            font-size: 1.2rem;
        }
        .btn-save {
            background-color: #28a745;
            border: none;
            border-radius: 30px;
        }
        .btn-save:hover {
            background-color: #218838;
        }
        .form-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4 text-primary"><i class="bi bi-sliders"></i> Manage Slider Settings</h2>
    <?php if ($message): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" id="slider-form">
        <input type="hidden" name="slider_order" id="slider_order" value='<?= json_encode($sliderOrderDefault) ?>'>
        <div id="sliders-container" class="row">
            <?php foreach ($sliders as $slider): ?>
                <div class="col-md-6 mb-4 draggable" draggable="true" data-slider-id="<?= $slider['id'] ?>">
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="bi bi-grid"></i> Slider <?= $slider['id'] ?></h5>
                            <div>
                                <label class="form-check-label me-2" for="slider_active_<?= $slider['id'] ?>">Active</label>
                                <input type="checkbox" id="slider_active_<?= $slider['id'] ?>" name="slider_active_<?= $slider['id'] ?>" class="form-check-input" <?= $slider['is_active'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="slider_title_<?= $slider['id'] ?>" class="form-label"><i class="bi bi-fonts"></i> Title</label>
                                <input type="text" id="slider_title_<?= $slider['id'] ?>" name="slider_title_<?= $slider['id'] ?>" class="form-control" value="<?= htmlspecialchars($slider['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter slider title">
                            </div>
                            <div class="mb-3">
                                <label for="slider_link_<?= $slider['id'] ?>" class="form-label"><i class="bi bi-link-45deg"></i> Link</label>
                                <input type="url" id="slider_link_<?= $slider['id'] ?>" name="slider_link_<?= $slider['id'] ?>" class="form-control" value="<?= htmlspecialchars($slider['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter slider link">
                            </div>
                            <div class="mb-3">
                                <label for="slider_image_<?= $slider['id'] ?>" class="form-label"><i class="bi bi-card-image"></i> Upload Image</label>
                                <input type="file" id="slider_image_<?= $slider['id'] ?>" name="slider_image_<?= $slider['id'] ?>" class="form-control">
                                <?php if ($slider['image_path']): ?>
                                    <img src="<?= $slider['image_path'] ?>" alt="Slider Image" class="img-thumbnail mt-2">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-save w-100 mt-3"><i class="bi bi-save2"></i> Save Settings</button>
    </form>
</div>

<div class="loading-spinner">
    <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
<script>
    const slidersContainer = document.getElementById('sliders-container');
    const sliderOrderInput = document.getElementById('slider_order');

    slidersContainer.addEventListener('dragstart', e => e.target.classList.add('dragging'));
    slidersContainer.addEventListener('dragend', e => {
        e.target.classList.remove('dragging');
        updateSliderOrder();
    });

    slidersContainer.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(slidersContainer, e.clientY);
        const draggable = document.querySelector('.dragging');
        if (afterElement == null) {
            slidersContainer.appendChild(draggable);
        } else {
            slidersContainer.insertBefore(draggable, afterElement);
        }
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.draggable:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateSliderOrder() {
        const sliderOrder = [...slidersContainer.querySelectorAll('.draggable')].map(slider => slider.dataset.sliderId);
        sliderOrderInput.value = JSON.stringify(sliderOrder);
    }
</script>
</body>
</html>
