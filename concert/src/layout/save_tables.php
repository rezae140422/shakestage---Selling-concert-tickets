<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

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

        if (!isset($input['concertId'], $input['tables']) || !is_array($input['tables'])) {
            throw new Exception('Invalid input data: Missing concertId or tables.');
        }

        $concertId = (int)$input['concertId'];
        $tables = $input['tables'];

        // آماده‌سازی کوئری برای درج یا به‌روزرسانی میزها
        $insertOrUpdateStmt = $pdo->prepare("
            INSERT INTO venue_items (concert_id, item_type, position_x, position_y, color, label, table_id, table_shape)
            VALUES (:concert_id, 'table', :position_x, :position_y, :color, :label, :table_id, :table_shape)
            ON DUPLICATE KEY UPDATE
                position_x = VALUES(position_x),
                position_y = VALUES(position_y),
                color = VALUES(color),
                label = VALUES(label),
                table_shape = VALUES(table_shape),
                updated_at = CURRENT_TIMESTAMP
        ");

        $processedCount = 0;

        foreach ($tables as $table) {
            // بررسی صحت داده‌های ورودی
            if (!isset($table['type'], $table['position_x'], $table['position_y'], $table['color'], $table['table_id'])) {
                error_log("Invalid table data: " . json_encode($table));
                continue;
            }

            $tableShape = ($table['type'] === 'tableA') ? 'rectangle' : (($table['type'] === 'tableB') ? 'circle' : null);
            if (!$tableShape) {
                error_log("Invalid table type for table_id: " . ($table['table_id'] ?? 'Unknown'));
                continue;
            }

            // اجرای کوئری درج یا به‌روزرسانی
            $insertOrUpdateStmt->execute([
                ':concert_id' => $concertId,
                ':position_x' => (float)$table['position_x'],
                ':position_y' => (float)$table['position_y'],
                ':color' => htmlspecialchars($table['color']),
                ':label' => htmlspecialchars($table['label'] ?? ''),
                ':table_id' => htmlspecialchars($table['table_id']),
                ':table_shape' => $tableShape
            ]);

            $processedCount++;
        }

        echo json_encode([
            'success' => true,
            'message' => "$processedCount tables processed successfully."
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
