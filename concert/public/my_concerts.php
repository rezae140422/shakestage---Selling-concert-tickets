<?php
session_start();

// بررسی دسترسی کاربر
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /concert/public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../src/views/partials/header.php';

// دریافت لیست کنسرت‌ها از دیتابیس
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT id, name, event_date, location FROM concerts ORDER BY event_date DESC');
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">My Concerts</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Concert Name</th>
                <th>Date</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($concerts as $concert): ?>
                <tr>
                    <td><?php echo htmlspecialchars($concert['id']); ?></td>
                    <td><?php echo htmlspecialchars($concert['name']); ?></td>
                    <td><?php echo htmlspecialchars($concert['event_date']); ?></td>
                    <td><?php echo htmlspecialchars($concert['location']); ?></td>
                    <td>
                        <a href="/concert/public/edit_concert.php?id=<?php echo $concert['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="/concert/public/delete_concert.php?id=<?php echo $concert['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this concert?');">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
