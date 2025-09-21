<?php
session_start();

// بررسی دسترسی به پنل برگزارکننده
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'organizer') {
    header('Location: /concert/public/login.php');
    exit;
}

include __DIR__ . '/verify_token.php'; // بررسی توکن معتبر
include __DIR__ . '/../src/views/partials/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Panel</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
</head>
<body>
<main class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-people-fill me-2"></i>Organizer Panel
    </h2>
    <div class="row">
        <!-- Sidebar -->
        <aside class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-list"></i> Menu
                </div>
                <div class="list-group list-group-flush">
                    <a href="/concert/public/organizer_panel.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-house-door-fill me-2"></i>Dashboard
                    </a>
                    <a href="/concert/src/concerts/create_concert.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-event me-2"></i>Manage Events
                    </a>
                    <a href="/concert/src/layout/layout.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-palette me-2"></i>Layout Management
                    </a>
                    <a href="/concert/public/alltickets.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-ticket-detailed me-2"></i>All Tickets
                    </a>
                    <a href="/concert/public/change_password.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-key-fill me-2"></i>Change Password
                    </a>
                    <a href="/concert/public/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <section class="col-md-9">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h4 class="text-success">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>!</h4>
                    <p class="text-muted">
                        Manage your events, customize layouts, and ensure everything is ready for your audience.
                    </p>
                    <hr>
                    <div class="alert alert-info shadow-lg rounded">
                        <h5 class="text-primary">
                            <i class="bi bi-info-circle-fill me-2"></i> Quick Tips for Organizers
                        </h5>
                        <ul>
                            <li><i class="bi bi-calendar-event text-success me-2"></i>Create and manage your events efficiently.</li>
                            <li><i class="bi bi-palette text-warning me-2"></i>Use the layout tools to design seating arrangements.</li>
                            <li><i class="bi bi-key-fill text-danger me-2"></i>Regularly update your password to secure your account.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
include __DIR__ . '/../src/views/partials/footer.php';
?>
<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
