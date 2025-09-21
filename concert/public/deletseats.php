<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// بررسی دسترسی
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied.');
}

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // جستجوی صندلی‌ها و میزهایی که concert_id آن‌ها معتبر نیست
    $query = "
        SELECT COUNT(*) AS invalid_items_count
        FROM venue_items
        WHERE concert_id NOT IN (SELECT id FROM concerts)
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // نمایش تعداد
    $invalidItemsCount = $result['invalid_items_count'];
    echo "Number of invalid seats and tables: " . $invalidItemsCount;
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
