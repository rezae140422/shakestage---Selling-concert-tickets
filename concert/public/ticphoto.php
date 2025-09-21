<?php
session_start();
include __DIR__ . '/verify_token.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header('Location: /concert/public/login.php');
    exit;
}

include __DIR__ . '/../src/views/partials/header.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // دریافت بلیط‌ها
    $stmt = $pdo->prepare("
        SELECT r.id AS reservation_id, r.reservation_date, r.seat_id, r.status, r.transaction_id, c.name AS concert_name, 
               c.description AS concert_description, c.event_date, c.location, c.banner
        FROM reservations r
        JOIN concerts c ON r.concert_id = c.id
        WHERE r.user_email = :email AND r.status = 'completed'
        ORDER BY r.reservation_date DESC
    ");
    $stmt->bindParam(':email', $_SESSION['user_email']);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<p class='text-danger'>Database Error: " . $e->getMessage() . "</p>";
    exit;
}
?>
<main class="container mt-5">
    <h2 class="text-center text-primary mb-4">
        <i class="bi bi-ticket-detailed"></i> My Tickets
    </h2>
    <?php if (empty($tickets)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> You have not purchased any tickets yet.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <img src="https://shakestage.com/concert/<?= htmlspecialchars($ticket['banner']) ?>" 
                             alt="<?= htmlspecialchars($ticket['concert_name']) ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?= htmlspecialchars($ticket['concert_name']) ?></h5>
                            <p class="card-text">
                                <strong>Event Date:</strong> <?= htmlspecialchars($ticket['event_date']) ?><br>
                                <strong>Location:</strong> <?= htmlspecialchars($ticket['location']) ?><br>
                                <strong>Seats:</strong>
                                <?php
                                // پردازش صندلی‌ها
                                $seatIds = json_decode($ticket['seat_id'], true);
                                if (is_array($seatIds) && !empty($seatIds)) {
                                    $seatDetails = [];
                                    foreach ($seatIds as $seatId) {
                                        $seatQuery = $pdo->prepare("SELECT seat_number, label FROM venue_items WHERE seat_id = :seat_id");
                                        $seatQuery->execute([':seat_id' => $seatId]);
                                        $seat = $seatQuery->fetch(PDO::FETCH_ASSOC);
                                        if ($seat) {
                                            $seatDetails[] = "Seat: " . htmlspecialchars($seat['seat_number']) . " (" . htmlspecialchars($seat['label']) . ")";
                                        } else {
                                            $seatDetails[] = "<span class='text-danger'>Invalid seat data</span>";
                                        }
                                    }
                                    echo implode('<br>', $seatDetails);
                                } else {
                                    echo "<span class='text-danger'>Invalid seat data</span>";
                                }
                                ?>
                                <br>
                                <strong>Reservation Date:</strong> <?= htmlspecialchars($ticket['reservation_date']) ?>
                            </p>
                            <span class="badge bg-success">Completed</span>
                            <!-- دکمه مشاهده بلیط -->
                            <div class="text-center mt-3">
                                <a href="https://shakestage.com/concert/public/tickets/<?= htmlspecialchars($ticket['transaction_id']) ?>" class="btn btn-primary">
                                    <i class="bi bi-eye"></i> View Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
