<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// بروزرسانی اطلاعات کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $fullName = !empty($_POST['name']) ? $_POST['name'] : null;
    $role = $_POST['role'] ?? null;
    $isActive = $_POST['is_active'] ?? null;

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = 'UPDATE users SET email = :email, full_name = :full_name, role = :role, is_active = :is_active WHERE id = :id';
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':is_active', $isActive);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// تغییر رمز عبور کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = $_POST['user_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = 'UPDATE users SET password = :password WHERE id = :id';
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':password', $newPassword);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دریافت لیست کاربران
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email != :email');
    $stmt->bindParam(':email', $_SESSION['user_email']);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <script src="/concert/public/assets/js/sweetalert2.all.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/../src/views/partials/header.php'; ?>

<main class="container mt-5">
    <h2 class="text-center mb-4">User Management</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <td><?php echo $user['id']; ?></td>
                    <td>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control">
                    </td>
                    <td>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" class="form-control">
                    </td>
                    <td>
                        <select name="role" class="form-select">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </td>
                    <td>
                        <select name="is_active" class="form-select">
                            <option value="1" <?php echo $user['is_active'] == 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $user['is_active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" name="update_user" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>Save
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="resetPassword(<?php echo $user['id']; ?>)">
                            <i class="bi bi-key-fill me-1"></i>Reset Password
                        </button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<script>
function resetPassword(userId) {
    Swal.fire({
        title: 'Reset Password',
        input: 'password',
        inputLabel: 'Enter new password',
        inputPlaceholder: 'New password',
        inputAttributes: {
            minlength: 6,
            required: true
        },
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: (newPassword) => {
            if (!newPassword) {
                Swal.showValidationMessage('Password cannot be empty!');
                return;
            }
            const formData = new FormData();
            formData.append('reset_password', true);
            formData.append('user_id', userId);
            formData.append('new_password', newPassword);

            return fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to reset password.');
                    }
                    return data;
                })
                .catch(error => Swal.showValidationMessage(error.message));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Success', 'Password updated successfully!', 'success');
        }
    });
}
</script>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../src/views/partials/footer.php'; ?>
</body>
</html>
