<?php
session_start();

// بررسی دسترسی کاربر
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// دریافت اطلاعات کنسرت
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: Concert ID is required.');
}

$concert_id = $_GET['id'];
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT * FROM concerts WHERE id = :id');
    $stmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
    $stmt->execute();
    $concert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$concert) {
        die('Error: Concert not found.');
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// بروزرسانی اطلاعات کنسرت
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $event_date = $_POST['date'];
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);
    $capacity = (int) $_POST['capacity'];
    $tags = trim($_POST['tags']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $external_link = trim($_POST['external_link']);
    $location = $country . ', ' . $city . ', ' . $address;

    // مدیریت آپلود تصویر بنر (در صورت آپلود تصویر جدید)
    $banner = $concert['banner']; // بنر موجود در دیتابیس
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($_FILES['banner']['tmp_name']);
        $fileSize = $_FILES['banner']['size'];

        if (in_array($fileType, $allowedTypes)) {
            $fileExtension = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('concert_', true) . '.' . $fileExtension;
            $uploadDir = __DIR__ . '/../storage/uploads/';
            $uploadFile = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadFile)) {
                $banner = 'storage/uploads/' . $newFileName; // مسیر جدید بنر
            } else {
                die('Error: Failed to upload image.');
            }
        } else {
            die('Error: Invalid file type. Only JPEG, JPG, and PNG are allowed.');
        }
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE concerts 
            SET name = :name, description = :description, event_date = :event_date, location = :location, 
                capacity = :capacity, banner = :banner, tags = :tags, latitude = :latitude, longitude = :longitude, external_link = :external_link 
            WHERE id = :id'
        );
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':banner', $banner);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':external_link', $external_link);
        $stmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: /concert/public/my_concerts.php?success=1');
        exit;
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

include __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">Edit Concert</h2>
    <form method="POST" enctype="multipart/form-data" class="mx-auto shadow p-4 rounded" style="max-width: 600px; background-color: #f9f9f9;">
        <!-- نام کنسرت -->
        <div class="mb-3">
            <label for="name" class="form-label">Concert Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($concert['name']); ?>" required>
        </div>

        <!-- توضیحات -->
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($concert['description']); ?></textarea>
        </div>

        <!-- تاریخ و زمان -->
        <div class="mb-3">
            <label for="date" class="form-label">Date and Time</label>
            <input type="datetime-local" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($concert['event_date']); ?>" required>
        </div>

        <!-- کشور -->
        <div class="mb-3">
            <label for="country" class="form-label">Country</label>
            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars(explode(', ', $concert['location'])[0]); ?>" required>
        </div>

        <!-- شهر -->
        <div class="mb-3">
            <label for="city" class="form-label">City</label>
            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars(explode(', ', $concert['location'])[1]); ?>" required>
        </div>

        <!-- آدرس -->
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars(explode(', ', $concert['location'])[2]); ?>" required>
        </div>

        <!-- ظرفیت -->
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo htmlspecialchars($concert['capacity']); ?>" required>
        </div>

        <!-- تگ‌ها -->
        <div class="mb-3">
            <label for="tags" class="form-label">Tags (comma-separated)</label>
            <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($concert['tags']); ?>">
        </div>

        <!-- لینک خارجی -->
        <div class="mb-3">
            <label for="external_link" class="form-label">External Link</label>
            <input type="url" class="form-control" id="external_link" name="external_link" value="<?php echo htmlspecialchars($concert['external_link']); ?>">
        </div>

        <!-- مختصات نقشه -->
        <div class="mb-3">
            <label for="map" class="form-label">Location on Map</label>
            <div id="map" style="height: 300px;"></div>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($concert['latitude']); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($concert['longitude']); ?>">
        </div>

        <!-- آپلود تصویر -->
        <div class="mb-3">
            <label for="banner" class="form-label">Concert Banner</label>
            <input type="file" class="form-control" id="banner" name="banner">
            <img src="/concert/<?php echo htmlspecialchars($concert['banner']); ?>" alt="Current Banner" class="img-fluid mt-2" style="max-width: 200px;">
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Concert</button>
    </form>
</main>

<!-- Leaflet.js برای نقشه -->
<link rel="stylesheet" href="/concert/public/assets/leaflet/css/leaflet.css" />
<script src="/concert/public/assets/leaflet/js/leaflet.js"></script>
<script>
    var map = L.map('map').setView([<?php echo htmlspecialchars($concert['latitude'] ?: 51.505); ?>, <?php echo htmlspecialchars($concert['longitude'] ?: -0.09); ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker = L.marker([<?php echo htmlspecialchars($concert['latitude'] ?: 51.505); ?>, <?php echo htmlspecialchars($concert['longitude'] ?: -0.09); ?>]).addTo(map);

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitude').value = e.latlng.lat;
        document.getElementById('longitude').value = e.latlng.lng;
    });
</script>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
