<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['seat_id'])) {
    echo json_encode(['reserved' => false]);
    exit;
}

$seat_id = $_GET['seat_id'];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // بررسی اگر `seat_id` در `reservations` و وضعیت `completed` باشد
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS count
        FROM reservations
        WHERE JSON_CONTAINS(seat_id, :seat_id) AND status = 'completed'
    ");
    $stmt->execute([':seat_id' => json_encode("Seat ID: $seat_id")]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['reserved' => $result['count'] > 0]);
} catch (PDOException $e) {
    echo json_encode(['reserved' => false, 'error' => $e->getMessage()]);
}
