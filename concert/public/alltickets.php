<?php
session_start();
include __DIR__ . '/verify_token.php';

// بررسی نقش کاربر
if (!isset($_SESSION['user_role'])) {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$searchEmail = $_GET['search'] ?? '';
$userEmail = $_SESSION['user_email'];  // ایمیل کاربر که وارد شده است
$userRole = $_SESSION['user_role'];  // نقش کاربر

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($userRole === 'admin') {
        // اگر کاربر ادمین است، همه بلیط‌ها را مشاهده می‌کند
        $stmt = $pdo->prepare("
            SELECT r.id, r.reservation_date, r.seat_id, r.status, r.transaction_id, r.user_email,
                   c.name AS concert_name, c.description AS concert_description, 
                   c.event_date, c.location, c.banner, c.email AS concert_email
            FROM reservations r
            JOIN concerts c ON r.concert_id = c.id
            WHERE r.status = 'completed'
            ORDER BY r.reservation_date DESC
        ");
    } elseif ($userRole === 'organizer') {
        // اگر کاربر برگزارکننده است، فقط بلیط‌هایی که ایمیل کنسرت با ایمیل او برابر است نمایش داده می‌شود
        $stmt = $pdo->prepare("
            SELECT r.id, r.reservation_date, r.seat_id, r.status, r.transaction_id, r.user_email,
                   c.name AS concert_name, c.description AS concert_description, 
                   c.event_date, c.location, c.banner, c.email AS concert_email
            FROM reservations r
            JOIN concerts c ON r.concert_id = c.id
            WHERE c.email = :user_email AND r.status = 'completed'
            ORDER BY r.reservation_date DESC
        ");
        $stmt->bindParam(':user_email', $userEmail, PDO::PARAM_STR);
    } else {
        // اگر نقشی تعریف نشده باشد، دسترسی به صفحه مجاز نیست
        header('Location: /concert/public/login.php');
        exit;
    }

    if (!empty($searchEmail)) {
        // اگر فیلتر جستجوی ایمیل وجود داشته باشد
        $stmt = $pdo->prepare("
            SELECT r.id, r.reservation_date, r.seat_id, r.status, r.transaction_id, r.user_email,
                   c.name AS concert_name, c.description AS concert_description, 
                   c.event_date, c.location, c.banner, c.email AS concert_email
            FROM reservations r
            JOIN concerts c ON r.concert_id = c.id
            WHERE LOWER(r.user_email) LIKE LOWER(:search) AND r.status = 'completed'
            ORDER BY r.reservation_date DESC
        ");
        $stmt->bindValue(':search', "%$searchEmail%", PDO::PARAM_STR);
    }

    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tickets as &$ticket) {
        $seatIds = json_decode($ticket['seat_id'], true);
        if (is_array($seatIds) && !empty($seatIds)) {
            $seatDetails = [];
            foreach ($seatIds as $seatId) {
                $seatStmt = $pdo->prepare("SELECT seat_number, label, price FROM venue_items WHERE seat_id = :seat_id");
                $seatStmt->bindValue(':seat_id', $seatId, PDO::PARAM_STR);
                $seatStmt->execute();
                $seatDetail = $seatStmt->fetch(PDO::FETCH_ASSOC);

                if ($seatDetail) {
                    $seatDetails[] = "Seat: " . htmlspecialchars($seatDetail['seat_number']) . 
                        ", Label: " . htmlspecialchars($seatDetail['label']) . 
                        ", Price: €" . htmlspecialchars($seatDetail['price']);
                }
            }
            $ticket['seat_details'] = $seatDetails;
        } else {
            $ticket['seat_details'] = ['<span class="text-warning">Not Assigned</span>'];
        }
    }

    if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
        require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

        $pdf = new FPDF('L', 'mm', 'A4'); // حالت افقی
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Completed Tickets Report', 0, 1, 'C');
        $pdf->Ln(10);

        // Header Style
        $pdf->SetFont('Arial', 'B', 12);

        foreach ($tickets as $ticket) {
            if ($ticket['status'] === 'completed') {
                // User Email
                $pdf->Cell(50, 10, 'User Email:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(100, 10, $ticket['user_email'], 0, 1, 'L');

                // Reservation Date
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(50, 10, 'Reservation Date:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 12);
                $reservationDate = new DateTime($ticket['reservation_date']);
                $pdf->Cell(100, 10, $reservationDate->format('F j, Y, g:i a'), 0, 1, 'L');

                // Concert Name
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(50, 10, 'Concert Name:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(100, 10, $ticket['concert_name'], 0, 1, 'L');

                // Seats
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(50, 10, 'Seats:', 0, 0, 'L');
                $pdf->SetFont('Arial', '', 12);
                $seatDetails = implode(", ", $ticket['seat_details']);
                $pdf->MultiCell(0, 10, $seatDetails, 0, 'L');

                // Separator
                $pdf->Ln(5);
                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetLineWidth(0.2);
                $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
                $pdf->Ln(5);
            }
        }

        $pdf->Output('D', 'Completed_Tickets_Report.pdf');
        exit;
    }

} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}

$headerPath = __DIR__ . '/../src/views/partials/header.php';
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    echo "<div class='alert alert-danger'>Header file not found: {$headerPath}</div>";
}
?>

<div class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-ticket-detailed me-2"></i>All Tickets
    </h2>
    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by email" value="<?= htmlspecialchars($searchEmail) ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
        </div>
    </form>
    <div class="text-end mb-3">
        <a href="alltickets.php?download=pdf" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-arrow-down"></i> Download PDF
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th><i class="bi bi-person me-2"></i>User Email</th>
                    <th><i class="bi bi-calendar-event me-2"></i>Reservation Date</th>
                    <th><i class="bi bi-music-note-list me-2"></i>Concert</th>
                    <th><i class="bi bi-ticket me-2"></i>Seats</th>
                    <th><i class="bi bi-info-circle me-2"></i>Status</th>
                    <th><i class="bi bi-eye me-2"></i>View Ticket</th> <!-- دکمه مشاهده بلیط -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <?php if ($ticket['status'] === 'completed'): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['id']) ?></td>
                            <td><?= htmlspecialchars($ticket['user_email']) ?></td>
                            <td><?= (new DateTime($ticket['reservation_date']))->format('F j, Y, g:i a') ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="/concert/<?= htmlspecialchars($ticket['banner']) ?>" 
                                         alt="<?= htmlspecialchars($ticket['concert_name']) ?>" 
                                         class="me-3" 
                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                                    <div>
                                        <strong><?= htmlspecialchars($ticket['concert_name']) ?></strong><br>
                                    </div>
                                </div>
                            </td>
                            <td><?= implode('<br>', $ticket['seat_details']) ?></td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>
                                <!-- دکمه مشاهده بلیط -->
                                <a href="https://shakestage.com/concert/public/tickets/<?= htmlspecialchars($ticket['transaction_id']) ?>" class="btn btn-info">
                                    <i class="bi bi-eye"></i> View Ticket
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$footerPath = __DIR__ . '/../src/views/partials/footer.php';
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "<div class='alert alert-danger'>Footer file not found: {$footerPath}</div>";
}
