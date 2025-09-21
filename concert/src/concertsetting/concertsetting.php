<?php
// اتصال به دیتابیس
include __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sort_option'])) {
        $selectedSortOption = $_POST['sort_option'];

        // ابتدا همه گزینه‌ها را غیرفعال می‌کنیم
        $pdo->exec("UPDATE layout_settings SET setting_value = 0");

        // گزینه انتخاب‌شده را فعال می‌کنیم
        $stmt = $pdo->prepare("UPDATE layout_settings SET setting_value = 1 WHERE setting_name = ?");
        $stmt->execute([$selectedSortOption]);

        echo "<div class='alert alert-success mt-3'>Sorting option saved successfully!</div>";
    }

    if (isset($_POST['date_display'])) {
        $selectedDateDisplay = $_POST['date_display'];

        // غیرفعال کردن تمام گزینه‌های نمایش تاریخ
        $pdo->exec("UPDATE date_display_settings SET is_active = 0");

        // فعال کردن گزینه انتخاب‌شده
        $stmt = $pdo->prepare("UPDATE date_display_settings SET is_active = 1 WHERE display_type = ?");
        $stmt->execute([$selectedDateDisplay]);

        echo "<div class='alert alert-success mt-3'>Date display type saved successfully!</div>";
    }

    if (isset($_POST['additional_fields'])) {
        $selectedFields = $_POST['additional_fields'];

        // غیرفعال کردن تمام فیلدها
        $pdo->exec("UPDATE additional_fields SET is_active = 0");

        // فعال کردن فیلدهای انتخاب‌شده
        $stmt = $pdo->prepare("UPDATE additional_fields SET is_active = 1 WHERE field_name = ?");
        foreach ($selectedFields as $field) {
            $stmt->execute([$field]);
        }

        echo "<div class='alert alert-success mt-3'>Additional fields updated successfully!</div>";
    }
}

// دریافت تنظیمات فعلی برای نوع چیدمان
$stmt = $pdo->query("SELECT setting_name FROM layout_settings WHERE setting_value = 1");
$currentSortSetting = $stmt->fetchColumn() ?: 'Date Added'; // مقدار پیش‌فرض

// دریافت تنظیمات فعلی برای نمایش تاریخ
$stmt = $pdo->query("SELECT display_type FROM date_display_settings WHERE is_active = 1");
$currentDateDisplay = $stmt->fetchColumn() ?: 'Show Date'; // مقدار پیش‌فرض

// دریافت فیلدهای اضافی فعال
$stmt = $pdo->query("SELECT field_name FROM additional_fields WHERE is_active = 1");
$activeFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="card shadow p-4">
    <h4 class="text-center mb-4">
        <i class="bi bi-gear-fill me-2"></i>Concert Settings
    </h4>
    <p class="text-muted text-center mb-4">Manage how concerts and their details are displayed on the homepage.</p>
    <form method="post">
        <div class="form-group mb-3">
            <label for="sort_option" class="form-label">
                <i class="bi bi-sort-alpha-down me-2"></i>Sort By:
            </label>
            <select name="sort_option" id="sort_option" class="form-select">
                <option value="Date Added" <?php echo $currentSortSetting === 'Date Added' ? 'selected' : ''; ?>>Date Added</option>
                <option value="Nearest Event Date" <?php echo $currentSortSetting === 'Nearest Event Date' ? 'selected' : ''; ?>>Nearest Event Date</option>
                <option value="Most Popular" <?php echo $currentSortSetting === 'Most Popular' ? 'selected' : ''; ?>>Most Popular</option>
                <option value="Offers" <?php echo $currentSortSetting === 'Offers' ? 'selected' : ''; ?>>Offers</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="date_display" class="form-label">
                <i class="bi bi-calendar-event me-2"></i>Date Display Type:
            </label>
            <select name="date_display" id="date_display" class="form-select">
                <option value="Show Date" <?php echo $currentDateDisplay === 'Show Date' ? 'selected' : ''; ?>>Show Date</option>
                <option value="Show Day" <?php echo $currentDateDisplay === 'Show Day' ? 'selected' : ''; ?>>Show Day</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="additional_fields" class="form-label">
                <i class="bi bi-list-check me-2"></i>Additional Fields:
            </label>
            <select name="additional_fields[]" id="additional_fields" class="form-select" multiple>
                <option value="event_date" <?php echo in_array('event_date', $activeFields) ? 'selected' : ''; ?>>Event Date</option>
                <option value="tags" <?php echo in_array('tags', $activeFields) ? 'selected' : ''; ?>>Tags</option>
                <option value="description" <?php echo in_array('description', $activeFields) ? 'selected' : ''; ?>>Description</option>
                <option value="capacity" <?php echo in_array('capacity', $activeFields) ? 'selected' : ''; ?>>Capacity</option>
                <option value="location" <?php echo in_array('location', $activeFields) ? 'selected' : ''; ?>>Location</option>
            </select>
            <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple fields.</small>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-circle me-2"></i>Save
        </button>
    </form>
</div>