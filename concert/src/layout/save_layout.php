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

        // دریافت داده‌ها از بدنه درخواست
        $input = json_decode(file_get_contents('php://input'), true);

        // لاگ کردن داده‌های دریافتی
        error_log("Received input: " . json_encode($input));

        // اعتبارسنجی ورودی‌ها
        if (!isset($input['concertId'], $input['layout']['objects']) || !is_array($input['layout']['objects'])) {
            throw new Exception('Invalid input data. concertId or layout objects are missing.');
        }

        $concert_id = $input['concertId'];
        $layout_objects = $input['layout']['objects'];

        // لاگ کردن تعداد آیتم‌ها
        error_log("Processing " . count($layout_objects) . " layout objects for concert ID: $concert_id");

        $insertStmt = $pdo->prepare("INSERT INTO venue_items (concert_id, item_type, position_x, position_y, price, status, seat_id, color, label, seat_number)
        VALUES (:concert_id, 'seat', :position_x, :position_y, :price, 'available', :seat_id, :color, :label, :seat_number)
        ON DUPLICATE KEY UPDATE
            position_x = :position_x,
            position_y = :position_y,
            price = :price,
            color = :color,
            label = :label,
            seat_number = :seat_number,
            updated_at = CURRENT_TIMESTAMP");

        $updatedCount = 0;
        $skippedCount = 0;

        // پردازش صندلی‌ها
        foreach ($layout_objects as $object) {
            if (!isset($object['seat_id'], $object['position_x'], $object['position_y'], $object['price'], $object['color'], $object['label'], $object['seatNumber'])) {
                error_log("Invalid seat data: " . json_encode($object));
                $skippedCount++;
                continue;
            }

            $seat_id = $object['seat_id'];
            $position_x = (float)$object['position_x'];
            $position_y = (float)$object['position_y'];
            $price = (float)$object['price'];
            $color = $object['color'];
            $label = $object['label'];
            $seat_number = $object['seatNumber']; // شماره صندلی

            // لاگ کردن داده‌های صندلی
            error_log("Inserting/Updating seat: seat_id=$seat_id, position_x=$position_x, position_y=$position_y, price=$price, color=$color, label=$label, seat_number=$seat_number");

            // اجرای کوئری
            $insertStmt->execute([
                ':concert_id' => $concert_id,
                ':seat_id' => $seat_id,
                ':position_x' => $position_x,
                ':position_y' => $position_y,
                ':price' => $price,
                ':color' => $color,
                ':label' => $label,
                ':seat_number' => $seat_number
            ]);

            $updatedCount++;
        }

        // نتیجه موفقیت‌آمیز
        error_log("$updatedCount seats updated, $skippedCount seats skipped.");

        echo json_encode([
            'success' => true,
            'message' => "$updatedCount seats updated successfully. $skippedCount seats skipped.",
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // روش درخواست نامعتبر
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
