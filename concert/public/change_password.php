<?php
session_start();

// بررسی نقش کاربر و توکن
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'organizer', 'user'])) {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        echo '<div class="alert alert-danger text-center">New passwords do not match!</div>';
    } elseif (strlen($newPassword) < 5 || !preg_match('/[a-zA-Z]/', $newPassword)) {
        echo '<div class="alert alert-danger text-center">Password must be at least 5 characters long and include at least one letter.</div>';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
                DB_USERNAME,
                DB_PASSWORD
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // هش کردن رمز عبور جدید
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // بروزرسانی رمز عبور در دیتابیس
            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE email = :email');
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $_SESSION['user_email']);
            $stmt->execute();

            echo '<div class="alert alert-success text-center">Password changed successfully!</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger text-center">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// ایمپورت هدر
include __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4"><?php echo ucfirst($_SESSION['user_role']); ?> Change Password</h2>
    <form method="POST" class="mx-auto shadow p-4 rounded" style="max-width: 400px; background-color: #f9f9f9;">
        <div class="mb-3">
            <label for="new_password" class="form-label">
                <i class="bi bi-key-fill me-2"></i>New Password
            </label>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">
                <i class="bi bi-key-fill me-2"></i>Confirm New Password
            </label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save me-2"></i>Change Password
        </button>
    </form>
</main>

<?php
// ایمپورت فوتر
include __DIR__ . '/../src/views/partials/footer.php';
?>