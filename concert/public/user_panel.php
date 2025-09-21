<?php
session_start();
include __DIR__ . '/verify_token.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header('Location: /concert/public/login.php');
    exit;
}

include __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center text-primary mb-4">
        <i class="bi bi-person-circle"></i> User Panel
    </h2>
    <div class="row">
        <div class="col-md-3">
            <div class="list-group shadow-sm">
                <a href="/concert/public/user_panel.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-house-door-fill me-2"></i> Dashboard
                </a>
                <a href="/concert/public/ticphoto.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-ticket-detailed me-2"></i> My Tickets
                </a>
                <a href="/concert/public/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary">Welcome, <?= htmlspecialchars($_SESSION['user_email']) ?>!</h3>
                    <p>Use the menu on the left to navigate through your panel.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
