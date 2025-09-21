<?php
session_start();
include __DIR__ . '/verify_token.php';

// بررسی نقش کاربر (باید ادمین باشد)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// متغیرهای جستجو
$searchEmail = $_GET['search'] ?? '';

// واکشی اطلاعات پرداخت‌ها از پایگاه داده
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // جستجو و مرتب‌سازی بر اساس تاریخ
    if (!empty($searchEmail)) {
        $stmt = $pdo->prepare("
            SELECT id, user_email, total_price, status, token, transaction_id, payment_id, created_at, updated_at
            FROM payments
            WHERE LOWER(user_email) LIKE LOWER(:search)
            ORDER BY created_at DESC
        ");
        $stmt->bindValue(':search', "%$searchEmail%", PDO::PARAM_STR);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, user_email, total_price, status, token, transaction_id, payment_id, created_at, updated_at
            FROM payments
            ORDER BY created_at DESC
        ");
    }

    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // حذف پرداخت‌های "Pending"
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $deleteId = $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = :id AND status = 'pending'");
        $stmt->bindValue(':id', $deleteId, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: paymanetsadmin.php');
        exit;
    }

} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// فایل header
$headerPath = __DIR__ . '/../src/views/partials/header.php';
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    echo "<div class='alert alert-danger'>Header file not found: {$headerPath}</div>";
}
?>

<div class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-cash-stack me-2"></i>All Payments
    </h2>
    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by email" value="<?= htmlspecialchars($searchEmail) ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th><i class="bi bi-person me-2"></i>User Email</th>
                    <th><i class="bi bi-currency-dollar me-2"></i>Total Price</th>
                    <th><i class="bi bi-info-circle me-2"></i>Status</th>
                    <th><i class="bi bi-key me-2"></i>Token</th>
                    <th><i class="bi bi-receipt me-2"></i>Transaction ID</th>
                    <th><i class="bi bi-credit-card me-2"></i>Payment ID</th>
                    <th><i class="bi bi-calendar me-2"></i>Created At</th>
                    <th><i class="bi bi-calendar me-2"></i>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['id']) ?></td>
                            <td><?= htmlspecialchars($payment['user_email']) ?></td>
                            <td>$<?= htmlspecialchars(number_format($payment['total_price'], 2)) ?></td>
                            <td>
                                <?php if ($payment['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($payment['status'] === 'failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($payment['token']) ?></td>
                            <td><?= htmlspecialchars($payment['transaction_id']) ?></td>
                            <td><?= htmlspecialchars($payment['payment_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($payment['created_at']) ?></td>
                            <td><?= htmlspecialchars($payment['updated_at']) ?></td>
                            <td>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($payment['id']) ?>">
                                        <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No payments found.</td>
                    </tr>
                <?php endif; ?>
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
?>
