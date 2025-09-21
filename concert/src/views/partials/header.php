<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShakeStage - Concert Tickets</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/concert/public/assets/css/style.css">
    <style>
        .navbar-toggler {
            border: none;
            outline: none;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .navbar-brand img {
            transition: transform 0.3s ease;
        }
        .navbar-brand img:hover {
            transform: scale(1.1);
        }
        .nav-link {
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #007bff !important;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand d-flex align-items-center" href="/">
                    <img src="/concert/logo/shss.png" alt="ShakeStage Logo" class="img-fluid" style="height: 40px; object-fit: contain;">
                </a>

                <!-- Toggler -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Links -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="/concert/public/event.php">Events</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="/concert/public/about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="/concert/public/contact.php">Contact</a>
                        </li>
                    </ul>

                    <!-- Dynamic Buttons -->
                    <div class="d-flex ms-lg-3">
                        <?php if (isset($_COOKIE['user_token'])): ?>
                            <?php
                            session_start();
                            $role = $_SESSION['user_role'] ?? '';
                            $panelUrl = '/concert/public/user_panel.php';
                            if ($role === 'admin') {
                                $panelUrl = '/concert/public/admin_panel.php';
                            } elseif ($role === 'organizer') {
                                $panelUrl = '/concert/public/organizer_panel.php';
                            }
                            ?>
                            <a href="<?= $panelUrl ?>" class="btn btn-outline-success btn-sm me-2">User Panel</a>
                        <?php else: ?>
                            <a href="/concert/public/login.php" class="btn btn-outline-primary btn-sm me-2">Login</a>
                            <a href="/concert/public/register.php" class="btn btn-primary btn-sm">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Bootstrap JS -->
    <script src="/concert/public/assets/js/bootstrap.bundle.min.js" defer></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const navbarToggler = document.querySelector(".navbar-toggler");
            const navbarCollapse = document.querySelector("#navbarNav");

            navbarToggler.addEventListener("click", function () {
                navbarCollapse.classList.toggle("show");
            });

            document.addEventListener("click", function (event) {
                if (!navbarCollapse.contains(event.target) && !navbarToggler.contains(event.target)) {
                    navbarCollapse.classList.remove("show");
                }
            });
        });
    </script>
</body>
</html>
