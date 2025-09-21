<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <style>
        body {
            background-color: #fff; /* سفید */
            color: #004085; /* آبی تیره */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
            text-align: center;
        }
        .error-container {
            max-width: 500px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* سایه جذاب */
            animation: fadeIn 1.5s ease-in-out;
        }
        .logo img {
            max-width: 150px;
            margin-bottom: 20px;
            animation: float 2s infinite ease-in-out;
        }
        .error-icon {
            font-size: 80px;
            color: #007bff; /* رنگ آبی */
            margin-bottom: 20px;
            animation: shake 1.5s infinite;
        }
        .btn-home {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 18px;
            margin-top: 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); /* سایه برای دکمه */
        }
        .btn-home:hover {
            background-color: #0056b3;
            transform: scale(1.1);
        }
        #timer {
            font-size: 18px;
            margin-top: 10px;
            color: #555;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">
            <img src="https://shakestage.com/concert/logo/shs.jpg" alt="Logo">
        </div>
        <i class="bi bi-exclamation-circle-fill error-icon"></i>
        <h1 class="mt-3">404 - Page Not Found</h1>
        <p class="mt-3">Sorry, the page you are looking for does not exist.</p>
        <p id="timer">Redirecting to the homepage in <span id="countdown">20</span> seconds...</p>
        <a href="https://shakestage.com/" class="btn btn-home">
            <i class="bi bi-house-door-fill"></i> Back to Home Now
        </a>
        <p class="mt-4">Need help? Call us: <strong>+32 488 11 08 81</strong></p>
    </div>

    <script>
        // شمارش معکوس
        let countdown = 20;
        const countdownElement = document.getElementById('countdown');
        const interval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = 'https://shakestage.com/';
            }
        }, 1000);
    </script>
</body>
</html>
