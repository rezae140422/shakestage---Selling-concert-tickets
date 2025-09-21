<?php
// نمایش خطاهای PHP برای رفع اشکال
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// شروع سشن
session_start();

// بررسی نقش کاربر
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

// بارگذاری فایل .env
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// اتصال به پایگاه داده
$dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// بررسی ارسال فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_send'])) {
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    $mail = new PHPMailer(true);

    try {
        // تنظیمات SMTP از فایل .env
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        // اطلاعات ارسال‌کننده
        $mail->setFrom($_ENV['SMTP_USERNAME'], 'Shakestage Admin');
        $mail->addReplyTo($_ENV['SMTP_USERNAME'], 'Shakestage Admin');

        // اطلاعات گیرنده
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($recipient);
        } else {
            throw new Exception('Invalid recipient email address.');
        }

        // محتوای ایمیل
        $mail->isHTML(true);
        $mail->Subject = htmlspecialchars($subject);
        $mail->Body    = nl2br(htmlspecialchars($message));
        $mail->AltBody = htmlspecialchars($message);

        // ارسال ایمیل
        $mail->send();

        // ذخیره اطلاعات ایمیل در پایگاه داده
        $stmt = $pdo->prepare("INSERT INTO emails (recipient_email, subject, message) VALUES (:recipient_email, :subject, :message)");
        $stmt->execute([
            ':recipient_email' => $recipient,
            ':subject'         => $subject,
            ':message'         => $message
        ]);

        // هدایت به admin_panel.php
        header('Location: /concert/public/admin_panel.php');
        exit;
    } catch (Exception $e) {
        $errorMessage = "Email could not be sent. Error: {$e->getMessage()}";
    }
}

// بازیابی لیست ایمیل‌های ارسال‌شده
$emails = $pdo->query("SELECT * FROM emails ORDER BY sent_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-primary mb-4">
            <i class="bi bi-envelope-fill me-2"></i>Send Email
        </h2>
        
        <!-- فرم ارسال ایمیل -->
        <form id="emailForm" method="POST" action="mailadmin.php">
            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Email</label>
                <input type="email" class="form-control" id="recipient" name="recipient" placeholder="Enter recipient email" required>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter email subject" required>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Enter your message" required></textarea>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                <i class="bi bi-send-fill me-1"></i> Preview & Send
            </button>
        </form>

        <hr>

        <!-- لیست ایمیل‌های ارسال‌شده -->
        <h2 class="mt-5">Sent Emails</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($email['id']); ?></td>
                        <td><?php echo htmlspecialchars($email['recipient_email']); ?></td>
                        <td><?php echo htmlspecialchars($email['subject']); ?></td>
                        <td><?php echo htmlspecialchars($email['message']); ?></td>
                        <td><?php echo htmlspecialchars($email['sent_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- مودال تایید ارسال -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Recipient:</strong> <span id="modalRecipient"></span></p>
                    <p><strong>Subject:</strong> <span id="modalSubject"></span></p>
                    <p><strong>Message:</strong></p>
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="confirm_send" form="emailForm">
                        <i class="bi bi-send-check-fill me-1"></i> Confirm & Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('[data-bs-target="#confirmModal"]').addEventListener('click', function() {
            // پر کردن مقادیر مودال
            document.getElementById('modalRecipient').textContent = document.getElementById('recipient').value;
            document.getElementById('modalSubject').textContent = document.getElementById('subject').value;
            document.getElementById('modalMessage').textContent = document.getElementById('message').value;
        });
    </script>
</body>
</html>
