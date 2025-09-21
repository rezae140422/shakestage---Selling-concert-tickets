<?php
// ایمپورت هدر
include __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">Register</h2>
    <form id="registerForm" method="POST" action="/concert/public/register_handler.php" class="mx-auto shadow p-4 rounded" style="max-width: 400px; background-color: #f9f9f9;">
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="bi bi-envelope-fill me-2"></i>Email address
            </label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="bi bi-lock-fill me-2"></i>Password
            </label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            <div id="passwordStrength" class="mt-2 text-muted">
                <i class="bi bi-shield-lock-fill me-2"></i>Password strength: 
                <span id="strengthIndicator">Weak</span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-plus-fill me-2"></i>Register
        </button>
        <p class="mt-3 text-center">
            Already have an account? 
            <a href="/concert/public/login.php">Login here</a>
        </p>
    </form>
</main>

<script>
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('strengthIndicator');

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        let strength = 'Weak';
        let color = 'red';

        if (password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password)) {
            strength = 'Strong';
            color = 'green';
        } else if (password.length >= 5 && /\d/.test(password) && /[a-zA-Z]/.test(password)) {
            strength = 'Moderate';
            color = 'orange';
        }

        strengthIndicator.textContent = strength;
        strengthIndicator.style.color = color;
    });
</script>

<?php
// ایمپورت فوتر
include __DIR__ . '/../src/views/partials/footer.php';
?>
