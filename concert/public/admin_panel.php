<?php
session_start();

// بررسی توکن معتبر
require_once __DIR__ . '/verify_token.php'; // مسیر به فایل در پوشه public

// بررسی نقش کاربر
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
    $stmt = $pdo->query("SELECT SUM(visit_count) AS total_visits FROM site_visits");
    $totalVisits = $stmt->fetch(PDO::FETCH_ASSOC)['total_visits'] ?? 0;
    // بررسی وجود پیام‌های خوانده نشده
    $stmt = $pdo->query("SELECT COUNT(*) AS unread_count FROM contact_messages WHERE read_status = 0");
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/../src/views/partials/header.php';
?>

<main class="container mt-5">
    <h2 class="text-center mb-4 text-primary">
        <i class="bi bi-speedometer2 me-2"></i>Admin Panel
    </h2>
    <div class="alert alert-info mt-4 shadow-lg rounded">
    <h4 class="text-primary mb-3">
        <i class="bi bi-eye me-2"></i> Total Visits
    </h4>
    <p class="text-muted">Total visits: <strong><?= htmlspecialchars($totalVisits) ?></strong></p>
</div>

    <div class="row">
        <!-- Sidebar -->
        <aside class="col-md-3">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-list"></i> Menu
        </div>
        <div class="list-group list-group-flush">

            <!-- Dashboard Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </div>
            <a href="/concert/public/admin_panel.php" class="list-group-item list-group-item-action">
                <i class="bi bi-house-door-fill me-2"></i>Home
            </a>

            <!-- Concert Management Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-music-note"></i> Concert Management
            </div>
            <a href="/concert/src/concerts/create_concert.php" class="list-group-item list-group-item-action">
                <i class="bi bi-plus-circle me-2"></i>Create Concert
            </a>
            <a href="/concert/src/layout/layout.php" class="list-group-item list-group-item-action">
                <i class="bi bi-palette me-2"></i>Layout Management
            </a>
            <a href="/concert/public/my_concerts.php" class="list-group-item list-group-item-action">
                <i class="bi bi-calendar2-week me-2"></i>My Concerts
            </a>

            <!-- Transactions Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-cash-coin"></i> Transactions
            </div>
            <a href="/concert/public/alltickets.php" class="list-group-item list-group-item-action">
                <i class="bi bi-ticket-detailed me-2"></i>All Tickets
            </a>
            <a href="/concert/public/paymanetsadmin.php" class="list-group-item list-group-item-action">
                <i class="bi bi-cash-stack me-2"></i>All Payments
            </a>

            <!-- Messages Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-envelope"></i> Messages
            </div>
            <a href="/concert/public/contactmss.php" class="list-group-item list-group-item-action">
                <i class="bi bi-chat-dots me-2"></i>
                View Messages
                <?php if ($unreadMessages > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>

            <!-- Storage Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-archive-fill"></i> Storage Management
            </div>
            <a href="/concert/public/managestorage.php" class="list-group-item list-group-item-action">
                <i class="bi bi-folder-fill me-2"></i>Manage Storage
            </a>

            <!-- Reports Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </div>
            <a href="/concert/public/seens.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-lines-fill me-2"></i>Site Visits Reports
            </a>
            <a href="/concert/public/loglogins.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-lines-fill me-2"></i>Login Reports
            </a>

            <!-- Settings Section -->
            <div class="bg-light p-2 text-primary fw-bold">
                <i class="bi bi-tools"></i> Settings
            </div>
            <a href="/concert/public/change_password.php" class="list-group-item list-group-item-action">
                <i class="bi bi-key-fill me-2"></i>Change Password
            </a>
            <a href="/concert/public/user_management.php" class="list-group-item list-group-item-action">
                <i class="bi bi-people-fill me-2"></i>User Management
            </a>
            <a href="/concert/public/site_settings.php" class="list-group-item list-group-item-action">
                <i class="bi bi-gear-fill me-2"></i>Site Settings
            </a>
            <a href="/concert/public/mailadmin.php" class="list-group-item list-group-item-action">
                <i class="bi bi-envelope-fill me-2"></i>Send Email
            </a>

            <!-- Logout Section -->
            <div class="bg-light p-2 text-danger fw-bold">
                <i class="bi bi-box-arrow-left"></i> Logout
            </div>
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
            <div class="row">
                <!-- Persian Content -->
                <div class="col-md-6 text-right">
                    <h4 class="text-success">
                        خوش آمدید، <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>!
                    </h4>
                    <p class="text-muted">
                        شما کنترل کامل سیستم را دارید. شما می‌توانید کنسرت‌های جدید ایجاد کنید، کاربران را مدیریت کنید و موارد دیگر.
                    </p>
                    <hr>
                    <div class="alert alert-info mt-4 shadow-lg rounded">
                        <h4 class="text-primary mb-3">
                            <i class="bi bi-info-circle-fill me-2"></i> نکات سریع برای مدیریت پلتفرم شما
                        </h4>
                        <p class="text-muted">در اینجا برخی از نکات مفید برای شروع و بهره‌برداری بیشتر از ابزارهای ادمین آورده شده است:</p>
                        <div class="row">
                            <!-- Tips Group 1 -->
                            <div class="col-md-6 mb-3">
                                <h5 class="text-secondary"><i class="bi bi-music-note-list me-2"></i>مدیریت کنسرت‌ها</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-plus-circle-fill text-success me-2"></i>
                                        <strong>ایجاد کنسرت:</strong> برنامه‌ریزی و سازماندهی رویدادهای جدید. تعیین قیمت بلیط، زمان‌بندی تاریخ‌ها و غیره.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-palette-fill text-warning me-2"></i>
                                        <strong>مدیریت چیدمان:</strong> طراحی و شخصی‌سازی چینش صندلی‌ها برای کنسرت‌ها.
                                    </li>
                                </ul>
                            </div>

                            <!-- Tips Group 2 -->
                            <div class="col-md-6 mb-3">
                                <h5 class="text-secondary"><i class="bi bi-people-fill me-2"></i>مدیریت کاربران و سیستم</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-people-fill text-primary me-2"></i>
                                        <strong>مدیریت کاربران:</strong> مشاهده و مدیریت حساب‌های کاربری و تخصیص نقش‌ها به صورت دلخواه.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-gear-fill text-dark me-2"></i>
                                        <strong>تنظیمات سایت:</strong> تنظیم پیکربندی‌های سیستم و نگهداری پلتفرم به راحتی.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-key-fill text-danger me-2"></i>
                                        <strong>تغییر رمز عبور:</strong> امنیت حساب کاربری خود را با بروزرسانی رمز عبور به صورت منظم حفظ کنید.
                                    </li>
                                </ul>
                            </div>

                            <!-- Additional Tips -->
                            <div class="col-md-12">
                                <h5 class="text-secondary"><i class="bi bi-lightbulb-fill me-2"></i>نکات عمومی</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>مانیتورینگ فعالیت:</strong> پیگیری فعالیت‌ها و تراکنش‌های کاربران به صورت لحظه‌ای.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-chat-dots-fill text-info me-2"></i>
                                        <strong>ارتباط مؤثر:</strong> استفاده از ویژگی ایمیل برای ارتباط با کاربران و به اشتراک گذاشتن به‌روزرسانی‌ها.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                                        <strong>تحلیل عملکرد:</strong> بازبینی گزارش‌ها به‌صورت دوره‌ای برای بهبود برنامه‌ریزی کنسرت‌ها و تجربه کاربری.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- English Content -->
                <div class="col-md-6 text-left">
                    <h4 class="text-success">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>!
                    </h4>
                    <p class="text-muted">
                        You have full control over the system. You can create new concerts, manage users, and more.
                    </p>
                    <hr>
                    <div class="alert alert-info mt-4 shadow-lg rounded">
                        <h4 class="text-primary mb-3">
                            <i class="bi bi-info-circle-fill me-2"></i> Quick Tips to Manage Your Platform
                        </h4>
                        <p class="text-muted">Here are some helpful tips to get you started and make the most out of your admin tools:</p>
                        <div class="row">
                            <!-- Tips Group 1 -->
                            <div class="col-md-6 mb-3">
                                <h5 class="text-secondary"><i class="bi bi-music-note-list me-2"></i>Concert Management</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-plus-circle-fill text-success me-2"></i>
                                        <strong>Create Concert:</strong> Plan and organize new events. Set ticket prices, schedule dates, and more.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-palette-fill text-warning me-2"></i>
                                        <strong>Layout Management:</strong> Design and customize seating arrangements for your concerts.
                                    </li>
                                </ul>
                            </div>

                            <!-- Tips Group 2 -->
                            <div class="col-md-6 mb-3">
                                <h5 class="text-secondary"><i class="bi bi-people-fill me-2"></i>User & System Management</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-people-fill text-primary me-2"></i>
                                        <strong>User Management:</strong> View and manage user accounts and assign roles as needed.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-gear-fill text-dark me-2"></i>
                                        <strong>Site Settings:</strong> Adjust system configurations and maintain your platform effortlessly.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-key-fill text-danger me-2"></i>
                                        <strong>Change Password:</strong> Secure your account by updating your password regularly.
                                    </li>
                                </ul>
                            </div>

                            <!-- Additional Tips -->
                            <div class="col-md-12">
                                <h5 class="text-secondary"><i class="bi bi-lightbulb-fill me-2"></i>General Tips</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>Monitor Activity:</strong> Keep track of user activities and transactions in real-time.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-chat-dots-fill text-info me-2"></i>
                                        <strong>Communicate Effectively:</strong> Use the email feature to connect with users and share updates.
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                                        <strong>Analyze Performance:</strong> Regularly review reports to improve your concert planning and user experience.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<!-- Support Section -->
<section class="col-md-12 mt-5 ">
<div class="card shadow-lg text-end p-3" >
    <div class="card-body">
        <!-- فارسی -->
        <div class="text-end">
            <h4 class="alert-heading text-success">
                <i class="bi bi-check-circle-fill me-2"></i> پشتیبانی و اطلاعات
            </h4>
            <p class="text-muted mb-4">
                <strong>تولیدکننده:</strong> <a href="https://anoshagasht.ir/" target="_blank">شرکت آنوشا گشت</a><br>
                پلتفرم حاضر با استفاده از آخرین تکنولوژی‌های برنامه‌نویسی و طراحی وب توسعه یافته است. در صورتی که به مشکلی برخوردید یا سوالی دارید، لطفاً از طریق راه‌های زیر با ما در تماس باشید:
            </p>
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary">تماس با ما:</h5>
                    <p class="text-muted">
                        - در صورت مواجهه با هرگونه مشکل یا سوال، می‌توانید از طریق تلگرام با ما در ارتباط باشید: <a href="https://t.me/AnoshaGasht_ir" target="_blank">@AnoshaGasht_ir</a><br>
                        - ما آماده‌ایم تا هرگونه مشکل شما را برطرف کنیم و در کنار شما باشیم.
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-primary">جزئیات فنی:</h5>
                    <p class="text-muted">
                        <strong>فرانت‌اند:</strong> JavaScript (JS)<br>
                        <strong>بک‌اند:</strong> PHP<br>
                        <strong>پایگاه داده:</strong> MySQL<br>
                        این پلتفرم با استفاده از این تکنولوژی‌ها بهینه‌سازی شده و از لحاظ فنی بسیار پایدار و مقیاس‌پذیر است.
                    </p>
                </div>
            </div>
            <hr>
            <p class="text-muted">
                ما به نظرات و بازخوردهای شما اهمیت می‌دهیم و همواره در تلاشیم تا بهترین تجربه را برای شما فراهم کنیم. در صورت بروز هرگونه مشکل، لطفاً بدون هیچ تردیدی با ما تماس بگیرید.
            </p>
        </div>
    </div>

            <!-- English -->
            <div class="text-start ">
                <h4 class="alert-heading text-success">
                    <i class="bi bi-check-circle-fill me-2"></i> Support and Information
                </h4>
                <p class="text-muted mb-4">
                    <strong>Developer:</strong> <a href="https://anoshagasht.ir/" target="_blank">Anosha Gasht</a><br>
                    This platform has been built using modern and advanced technologies. If you encounter any issues or have questions, please feel free to reach out using the following contact methods:
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">Contact Us:</h5>
                        <p class="text-muted">
                            - For issues or questions, please contact us via Telegram: <a href="https://t.me/AnoshaGasht_ir" target="_blank">@AnoshaGasht_ir</a><br>
                            - We are here to assist you with any questions or concerns you may have.
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary">Technical Details:</h5>
                        <p class="text-muted">
                            <strong>Frontend:</strong> JavaScript (JS)<br>
                            <strong>Backend:</strong> PHP<br>
                            <strong>Database:</strong> MySQL<br>
                            These are the core technologies used in this platform.
                        </p>
                    </div>
                </div>
                <hr>
                <p class="text-muted">
                    We value your feedback and strive to improve your experience. Please feel free to contact us if you encounter any issues!
                </p>
            </div>
        </div>
    </div>
    
</section>




    </div>
</main>

<?php
require_once __DIR__ . '/../src/views/partials/footer.php';
?>
