<?php
session_start();
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// تابع برای کاهش حجم تصویر بر اساس حجم فایل
function compressImageBySize($sourcePath, $destinationPath, $fileType, $fileSize, $maxSizeKB) {
    // تبدیل مگابایت به کیلوبایت
    $fileSizeKB = $fileSize / 1024;

    if ($fileSizeKB > 30 * 1024) { // بیش از 30 مگابایت -> زیر 1 مگابایت
        $quality = 10;
    } elseif ($fileSizeKB > 10 * 1024) { // بین 10 تا 30 مگابایت -> زیر 500 کیلوبایت
        $quality = 20;
    } else { // زیر 10 مگابایت -> زیر 300 کیلوبایت
        $quality = 30;
    }

    if ($fileType === 'image/jpeg' || $fileType === 'image/jpg') {
        $image = imagecreatefromjpeg($sourcePath);
        if (imagejpeg($image, $destinationPath, $quality)) {
            imagedestroy($image);
            return true;
        } else {
            return false;
        }
    } elseif ($fileType === 'image/png') {
        $image = imagecreatefrompng($sourcePath);
        if (imagepng($image, $destinationPath, round($quality / 10))) {
            imagedestroy($image);
            return true;
        } else {
            return false;
        }
    } else {
        return false; // فرمت‌های دیگر پشتیبانی نمی‌شوند
    }
}

// دریافت داده‌های فرم
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$event_date = $_POST['date'];
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
$latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;
$external_link = isset($_POST['external_link']) ? trim($_POST['external_link']) : '';
$capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : null;
$location = $country . ', ' . $city . ', ' . $address;

// دریافت ایمیل از $_SESSION
$email = $_SESSION['user_email']; // ایمیل را از سشن می‌گیریم

// مدیریت آپلود تصویر بنر
$banner = null;
if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $fileType = mime_content_type($_FILES['banner']['tmp_name']);
    $fileSize = $_FILES['banner']['size']; // حجم فایل به بایت

    if (in_array($fileType, $allowedTypes)) {
        $fileExtension = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('concert_', true) . '.' . $fileExtension;
        $uploadDir = __DIR__ . '/../../storage/uploads/';
        $uploadFile = $uploadDir . $newFileName;

        // فراخوانی تابع کاهش حجم بر اساس اندازه فایل
        $isCompressed = compressImageBySize($_FILES['banner']['tmp_name'], $uploadFile, $fileType, $fileSize, 1024);
        if ($isCompressed) {
            $banner = 'storage/uploads/' . $newFileName;
        } else {
            die('Error: Failed to compress image.');
        }
    } else {
        die('Error: Invalid file type. Only JPEG, JPG, and PNG are allowed.');
    }
}

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // درج اطلاعات کنسرت
    $stmt = $pdo->prepare(
        'INSERT INTO concerts (name, description, event_date, location, banner, external_link, tags, latitude, longitude, capacity, email) 
        VALUES (:name, :description, :event_date, :location, :banner, :external_link, :tags, :latitude, :longitude, :capacity, :email)'
    );
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':event_date', $event_date);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':banner', $banner);
    $stmt->bindParam(':external_link', $external_link);
    $stmt->bindParam(':tags', $tags);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':capacity', $capacity);
    $stmt->bindParam(':email', $email); // ذخیره ایمیل در کنسرت
    $stmt->execute();

    // ایجاد صندلی‌ها
    $concert_id = $pdo->lastInsertId();
    $seatStmt = $pdo->prepare(
        'INSERT INTO venue_items (concert_id, item_type, seat_id) 
        VALUES (:concert_id, :item_type, :seat_id)'
    );

    for ($i = 1; $i <= $capacity; $i++) {
        $seat_id = uniqid('seat_', true);
        $seatStmt->execute([
            ':concert_id' => $concert_id,
            ':item_type' => 'seat',
            ':seat_id' => $seat_id,
        ]);
    }

    echo "Concert and seats created successfully! <a href='/../../public/admin_panel.php'>Go to Admin Panel</a>";
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
