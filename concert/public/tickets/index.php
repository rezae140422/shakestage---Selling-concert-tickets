<?php
// شروع بافرینگ
ob_start();

// بررسی اینکه transaction_id در URL وجود دارد
if (!isset($_GET['transaction_id']) || empty($_GET['transaction_id'])) {
    die('<p class="text-danger">Invalid or missing transaction ID.</p>');
}

$transaction_id = $_GET['transaction_id']; // گرفتن transaction_id از URL

// اتصال به دیتابیس
require_once __DIR__ . '/../../config/database.php';

// استفاده از کتابخانه QR Code
require __DIR__ . '/../../vendor/autoload.php';  // مسیر صحیح برای فایل autoload.php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // جستجوی اطلاعات بلیط بر اساس transaction_id
    $stmt = $pdo->prepare("
        SELECT r.reservation_date, r.seat_id, r.status, r.transaction_id, c.name AS concert_name, 
               c.event_date, c.location, c.banner, r.user_email
        FROM reservations r
        JOIN concerts c ON r.concert_id = c.id
        WHERE r.transaction_id = :transaction_id
    ");
    $stmt->bindParam(':transaction_id', $transaction_id);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // بررسی اینکه آیا بلیط پیدا شده است
    if (!$ticket) {
        die('<p class="text-danger">Ticket not found or invalid transaction ID.</p>');
    }

} catch (PDOException $e) {
    die('<p class="text-danger">Database error: ' . $e->getMessage() . '</p>');
}

// ایجاد QR Code
$qrCode = new QrCode('https://shakestage.com/concert/public/tickets/' . $transaction_id);
$writer = new PngWriter();
$qrCode->setSize(200); // تنظیم سایز QR Code
$qrCodeImage = $writer->write($qrCode);

// تبدیل تصویر به base64
$qrCodeBase64 = base64_encode($qrCodeImage->getString()); // استفاده از getString() برای دریافت داده‌های تصویر

// اگر download=true در URL باشد، بلافاصله PDF را تولید کنیم
if (isset($_GET['download']) && $_GET['download'] == 'true') {
    // استفاده از FPDF برای ساخت PDF
    require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();

    // تنظیمات متن PDF
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Ticket Information');

    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 10, 'Concert Name: ' . htmlspecialchars($ticket['concert_name']));
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Transaction ID: ' . htmlspecialchars($ticket['transaction_id']));
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Event Date: ' . date('F j, Y', strtotime($ticket['event_date'])));
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Location: ' . htmlspecialchars($ticket['location']));
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Reservation Date: ' . htmlspecialchars($ticket['reservation_date']));
    $pdf->Ln(10);

    $pdf->Cell(40, 10, 'Seats: ');
    $seatIds = json_decode($ticket['seat_id'], true);
    foreach ($seatIds as $seatId) {
        // جستجوی اطلاعات صندلی
        $seatQuery = $pdo->prepare("SELECT seat_number, label FROM venue_items WHERE seat_id = :seat_id");
        $seatQuery->execute([':seat_id' => $seatId]);
        $seat = $seatQuery->fetch(PDO::FETCH_ASSOC);
        if ($seat) {
            $pdf->Cell(40, 10, 'Seat: ' . htmlspecialchars($seat['seat_number']) . ' (' . htmlspecialchars($seat['label']) . ')');
            $pdf->Ln(10);
        }
    }

    // ارسال PDF به مرورگر برای دانلود
    $pdf->Output('D', 'ticket_' . $ticket['transaction_id'] . '.pdf');
    exit();
}

// در غیر این صورت محتوای بلیط را نمایش دهیم
?>
<head>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/concert/public/assets/css/style.css">
</head>

<main class="container mt-5">
    <h2 class="text-center text-primary mb-4">
        <i class="bi bi-ticket-detailed"></i> Ticket Information
    </h2>
    <div class="card shadow-lg">
        <div class="card-body">
            <h3 class="text-primary"><?= htmlspecialchars($ticket['concert_name']) ?></h3>
            <p><strong>Event Date:</strong> <?= date('F j, Y', strtotime($ticket['event_date'])) ?></p>
            <hr>
            <p><strong>Transaction ID:</strong> <?= htmlspecialchars($ticket['transaction_id']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($ticket['location']) ?></p>
            <p><strong>Reservation Date:</strong> <?= htmlspecialchars($ticket['reservation_date']) ?></p>
            <strong>Seats:</strong>
            <ul>
                <?php
                // پردازش صندلی‌ها
                $seatIds = json_decode($ticket['seat_id'], true);
                if (is_array($seatIds) && !empty($seatIds)) {
                    foreach ($seatIds as $seatId) {
                        // جستجوی اطلاعات صندلی
                        $seatQuery = $pdo->prepare("SELECT seat_number, label FROM venue_items WHERE seat_id = :seat_id");
                        $seatQuery->execute([':seat_id' => $seatId]);
                        $seat = $seatQuery->fetch(PDO::FETCH_ASSOC);
                        if ($seat) {
                            echo "<li>Seat: " . htmlspecialchars($seat['seat_number']) . " (" . htmlspecialchars($seat['label']) . ")</li>";
                        } else {
                            echo "<li class='text-danger'>Invalid seat data</li>";
                        }
                    }
                } else {
                    echo "<li class='text-danger'>No seats found</li>";
                }
                ?>
            </ul>
            <p><strong>Email:</strong> <?= htmlspecialchars($ticket['user_email']) ?></p>
            <span class="badge bg-success"><?= htmlspecialchars(ucfirst($ticket['status'])) ?></span>

            <hr>
            <!-- QR Code -->
            <div class="text-center">
                <h5>Scan this QR Code to view the ticket:</h5>
                <img src="data:image/png;base64,<?= $qrCodeBase64 ?>" alt="QR Code">
            </div>

            
        </div>
    </div>
</main>

<?php
// پایان بافرینگ
ob_end_flush();
?>
