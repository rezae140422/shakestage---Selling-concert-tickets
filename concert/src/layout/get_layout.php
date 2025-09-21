<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // اتصال به دیتابیس
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // دریافت ورودی از درخواست
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['concertId'])) {
            throw new Exception('Concert ID is required.');
        }

        $concert_id = (int) $input['concertId'];

        // دریافت اطلاعات صندلی‌ها
        $stmtSeats = $pdo->prepare("SELECT id, concert_id, item_type, position_x, position_y, price, status, seat_id, color, label, seat_number, updated_at FROM venue_items WHERE concert_id = :concert_id AND item_type = 'seat'");
        $stmtSeats->execute([':concert_id' => $concert_id]);
        $seats = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);

        // دریافت اطلاعات میزها
        $stmtTables = $pdo->prepare("SELECT id, concert_id, item_type, position_x, position_y, color, label, table_shape, updated_at FROM venue_items WHERE concert_id = :concert_id AND item_type = 'table'");
        $stmtTables->execute([':concert_id' => $concert_id]);
        $tables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);

        // بازگرداندن داده‌ها
        echo json_encode([
            'success' => true,
            'seats' => $seats,
            'tables' => $tables,
        ]);
    } catch (PDOException $e) {
        // مدیریت خطای دیتابیس
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // مدیریت سایر خطاها
        error_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}