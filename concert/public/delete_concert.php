<?php
session_start();

// بررسی دسترسی کاربر
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// بررسی وجود شناسه کنسرت
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: Concert ID is required.');
}

$concert_id = intval($_GET['id']); // اطمینان از اینکه شناسه عددی است

try {
    // اتصال به دیتابیس
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // بررسی وجود کنسرت
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM concerts WHERE id = :id');
    $checkStmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $exists = $checkStmt->fetchColumn();

    if ($exists == 0) {
        die('Error: Concert with this ID does not exist.');
    }

    // حذف صندلی‌های مرتبط با این کنسرت
    $seatStmt = $pdo->prepare('DELETE FROM venue_items WHERE concert_id = :id');
    $seatStmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
    $seatStmt->execute();

    // حذف کنسرت
    $stmt = $pdo->prepare('DELETE FROM concerts WHERE id = :id');
    $stmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
    $stmt->execute();

    // بررسی موفقیت‌آمیز بودن عملیات حذف
    if ($stmt->rowCount() > 0) {
        header('Location: /concert/public/my_concerts.php?success=1');
        exit;
    } else {
        die('Error: Failed to delete concert.');
    }
} catch (PDOException $e) {
    // مدیریت ارورهای دیتابیس
    die('Database Error: ' . $e->getMessage());
}
