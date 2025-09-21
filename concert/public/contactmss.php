<?php
session_start();

// بررسی نقش کاربر
require_once __DIR__ . '/verify_token.php'; // مسیر بررسی توکن
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

// اتصال به دیتابیس
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // حذف پیام در صورت درخواست
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);
        header('Location: /concert/public/contactmss.php');
        exit;
    }

    // علامت‌گذاری به عنوان خوانده شده
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        $stmt = $pdo->prepare("UPDATE contact_messages SET read_status = 1 WHERE id = :id");
        $stmt->execute([':id' => $_GET['mark_read']]);
        header('Location: /concert/public/contactmss.php');
        exit;
    }

    // گرفتن تمامی پیام‌ها
    $stmt = $pdo->query("SELECT id, name, email, message, created_at, read_status FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
<div class="text-center mb-5">
    <h2 class="text-primary mb-3">
        <i class="bi bi-chat-dots"></i> Messages
    </h2>
    <p class="text-muted">
        These messages are collected from the <strong>Contact Us</strong> form on the <strong>About Us</strong> page.
    </p>
    <a href="https://shakestage.com/concert/public/contact.php" target="_blank" class="btn btn-outline-primary mt-3">
        <i class="bi bi-eye"></i> View Contact Page
    </a>
</div>



    <?php if (count($messages) === 0): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No messages found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $index => $message): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($message['name']) ?></td>
                            <td><?= htmlspecialchars($message['email']) ?></td>
                            <td>
                                <?= strlen($message['message']) > 50 ? 
                                    htmlspecialchars(substr($message['message'], 0, 50)) . '...' : 
                                    htmlspecialchars($message['message']); ?>
                            </td>
                            <td>
                                <?= $message['read_status'] == 0 ? '<span class="badge bg-warning">Unread</span>' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($message['created_at']) ?></td>
                            <td>
                                <div class="d-flex">
                                    <button class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#messageModal<?= $message['id'] ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <?php if ($message['read_status'] == 0): ?>
                                        <a href="?mark_read=<?= $message['id'] ?>" class="btn btn-success btn-sm me-2">
                                            <i class="bi bi-check-circle"></i> Mark Read
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $message['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this message?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal برای نمایش پیام -->
                        <div class="modal fade" id="messageModal<?= $message['id'] ?>" tabindex="-1" aria-labelledby="messageModalLabel<?= $message['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="messageModalLabel<?= $message['id'] ?>">
                                            Message from <?= htmlspecialchars($message['name']) ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Email:</strong> <?= htmlspecialchars($message['email']) ?></p>
                                        <p><strong>Message:</strong></p>
                                        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../src/views/partials/footer.php'; ?>
