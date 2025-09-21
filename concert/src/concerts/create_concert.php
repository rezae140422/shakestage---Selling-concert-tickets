<?php
session_start();

// بررسی دسترسی کاربر
if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'organizer')) {
    header('Location: /concert/public/login.php');
    exit;
}


include __DIR__ . '/../views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">Create New Concert</h2>
    <form method="POST" action="/concert/src/concerts/create_concert_handler.php" class="mx-auto shadow p-4 rounded" style="max-width: 600px; background-color: #f9f9f9;" enctype="multipart/form-data" id="concert-form">
        
        <!-- فیلد نام کنسرت -->
        <div class="mb-3" id="name-check">
            <label for="name" class="form-label"><i class="bi bi-music-note-beamed"></i> Concert Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
            <i class="bi bi-check-circle text-success" id="name-check-icon" style="display:none;"></i>
        </div>

        <!-- فیلد توضیحات -->
        <div class="mb-3" id="description-check">
            <label for="description" class="form-label"><i class="bi bi-file-earmark-text"></i> Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            <i class="bi bi-check-circle text-success" id="description-check-icon" style="display:none;"></i>
        </div>

        <!-- تاریخ و زمان کنسرت -->
        <div class="mb-3" id="date-check">
            <label for="date" class="form-label"><i class="bi bi-calendar-event"></i> Date and Time</label>
            <input type="datetime-local" class="form-control" id="date" name="date" required>
            <i class="bi bi-check-circle text-success" id="date-check-icon" style="display:none;"></i>
        </div>

        <!-- نوع کنسرت -->
        <div class="mb-3" id="type-check">
            <label for="type" class="form-label"><i class="bi bi-tags"></i> Concert Type</label>
            <select class="form-select" id="type" name="type" required>
                <option value="music">Music</option>
                <option value="theatre">Theatre</option>
                <option value="comedy">Comedy</option>
                <option value="other">Other</option>
            </select>
            <i class="bi bi-check-circle text-success" id="type-check-icon" style="display:none;"></i>
        </div>

      <!-- ظرفیت کنسرت -->
<div class="mb-3" id="capacity-check">
    <label for="capacity" class="form-label"><i class="bi bi-person-fill"></i> Capacity</label>
    <input type="number" class="form-control" id="capacity" name="capacity" required>
    <i class="bi bi-check-circle text-success" id="capacity-check-icon" style="display:none;"></i>
</div>


        <!-- کشور -->
        <div class="mb-3" id="country-check">
            <label for="country" class="form-label"><i class="bi bi-globe"></i> Country</label>
            <input type="text" class="form-control" id="country" name="country" required>
            <i class="bi bi-check-circle text-success" id="country-check-icon" style="display:none;"></i>
        </div>

        <!-- شهر -->
        <div class="mb-3" id="city-check">
            <label for="city" class="form-label"><i class="bi bi-building"></i> City</label>
            <input type="text" class="form-control" id="city" name="city" required>
            <i class="bi bi-check-circle text-success" id="city-check-icon" style="display:none;"></i>
        </div>

        <!-- آدرس متنی -->
        <div class="mb-3" id="address-check">
            <label for="address" class="form-label"><i class="bi bi-house-door"></i> Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
            <i class="bi bi-check-circle text-success" id="address-check-icon" style="display:none;"></i>
        </div>

        <!-- تصویر بنر -->
        <div class="mb-3" id="banner-check">
            <label for="banner" class="form-label"><i class="bi bi-image"></i> Concert Banner Image</label>
            <input type="file" class="form-control" id="banner" name="banner" accept="image/*" required>
            <i class="bi bi-check-circle text-success" id="banner-check-icon" style="display:none;"></i>
        </div>

        <!-- لینک کنسرت خارجی -->
        <div class="mb-3">
            <label for="external_link" class="form-label"><i class="bi bi-link"></i> External Concert Link (Optional)</label>
            <input type="url" class="form-control" id="external_link" name="external_link">
        </div>

        <!-- تگ‌ها (حداکثر 5 عدد) -->
        <div class="mb-3">
            <label for="tags" class="form-label"><i class="bi bi-tags"></i> Tags (Up to 5)</label>
            <div class="input-group">
                <input type="text" class="form-control" id="tag-input" placeholder="Enter tag" maxlength="20">
                <button type="button" class="btn btn-outline-secondary" id="add-tag-btn"><i class="bi bi-plus"></i> Add Tag</button>
            </div>
            <div id="tags-list" class="mt-3"></div>
            <input type="hidden" name="tags" id="tags">
        </div>

        <!-- نقشه برای انتخاب لوکیشن -->
        <div class="mb-3">
            <label for="map" class="form-label"><i class="bi bi-geo-alt"></i> Select Location on Map</label>
            <div id="map" style="height: 300px;"></div>
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <div id="coordinates" style="margin-top: 10px; font-weight: bold;"></div>
        </div>
        <!-- ایمیل (غیر قابل تغییر) -->
<!-- ایمیل (غیر قابل تغییر) -->
<div class="mb-3">
    <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email</label>
    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['user_email']) ?>" readonly required>
</div>



        <button type="submit" class="btn btn-primary w-100" id="submit-btn" disabled>
            <i class="bi bi-plus-circle me-2"></i> Create Concert
        </button>
    </form>
</main>

<!-- لینک به leaflet.js -->
<link rel="stylesheet" href="/concert/public/assets/leaflet/css/leaflet.css" />
<script src="/concert/public/assets/leaflet/js/leaflet.js"></script>

<script>
    // تنظیم نقشه با استفاده از Leaflet
    var map = L.map('map').setView([51.505, -0.09], 13); // موقعیت اولیه (مثال: لندن)

    // اضافه کردن TileLayer نقشه (استفاده از OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // نشانگر نقشه
    var marker = L.marker([51.505, -0.09]).addTo(map); // موقعیت اولیه

    // فعال کردن انتخاب موقعیت توسط کاربر
    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;

        marker.setLatLng(e.latlng); // تغییر موقعیت نشانگر
        document.getElementById('latitude').value = lat; // ذخیره عرض جغرافیایی
        document.getElementById('longitude').value = lng; // ذخیره طول جغرافیایی
        document.getElementById('coordinates').textContent = "Latitude: " + lat + ", Longitude: " + lng; // نمایش مختصات زیر نقشه
    });

    // افزودن تگ‌ها به لیست
    let tags = [];
    const tagInput = document.getElementById('tag-input');
    const addTagBtn = document.getElementById('add-tag-btn');
    const tagsList = document.getElementById('tags-list');
    const tagsField = document.getElementById('tags');

    addTagBtn.addEventListener('click', function() {
        let tag = tagInput.value.trim();
        if (tag && tags.length < 5 && !tags.includes(tag)) {
            tags.push(tag);
            updateTagList();
            tagInput.value = ''; // پاک کردن ورودی
        }
    });

    // به روزرسانی لیست تگ‌ها
    function updateTagList() {
        tagsList.innerHTML = '';
        tags.forEach(function(tag, index) {
            const tagDiv = document.createElement('div');
            tagDiv.classList.add('badge', 'bg-info', 'me-2', 'mb-2');
            tagDiv.innerHTML = tag + 
                `<button type="button" class="btn-close btn-sm ms-2" onclick="removeTag(${index})"></button>`;
            tagsList.appendChild(tagDiv);
        });
        tagsField.value = tags.join(','); // ارسال تگ‌ها به فیلد مخفی
    }

    // حذف تگ
    function removeTag(index) {
        tags.splice(index, 1);
        updateTagList();
    }

    // بررسی وضعیت فیلدها برای فعال/غیرفعال کردن دکمه ارسال
    const formElements = document.querySelectorAll('input[required], select[required], textarea[required]');
    const submitBtn = document.getElementById('submit-btn');
    
    formElements.forEach(element => {
        element.addEventListener('input', function() {
            checkForm();
        });
    });

    function checkForm() {
        let formValid = true;
        formElements.forEach(element => {
            if (element.value === '' || (element.type === 'file' && !document.getElementById('banner').files.length)) {
                formValid = false;
            }
        });
        submitBtn.disabled = !formValid;
    }
</script>

<?php
include __DIR__ . '/../views/partials/footer.php';
?>
