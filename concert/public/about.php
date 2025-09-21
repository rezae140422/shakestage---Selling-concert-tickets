<?php
// ایمپورت هدر
include __DIR__ . '/../src/views/partials/header.php'; // مسیر صحیح به هدر
?>

<!-- ایمپورت فایل CSS -->
<link rel="stylesheet" href="/concert/public/assets/css/about.css">

<main class="container mt-5">
    <div class="text-center">
        <h1 class="mb-4 text-primary"><i class="bi bi-info-circle"></i> About Us</h1>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- کارت فارسی -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white text-end">
                        <h2 class="mb-0"><i class="bi bi-question-circle"></i> چرا ما؟</h2>
                    </div>
                    <div class="card-body text-end">
                        <h4><i class="bi bi-person-workspace"></i> چه خدماتی ارائه می‌دهید؟</h4>
                        <p>تیم ما با تجربه‌ای طولانی در برنامه‌ریزی و اجرای مراسم‌هایی نظیر دیسکوهای خصوصی، کنسرت‌های بزرگ، مراسم عقد و عروسی، آماده خدمت‌رسانی به شماست.</p>
                        <h4><i class="bi bi-tools"></i> از چه تجهیزاتی استفاده می‌کنید؟</h4>
                        <p>ما از تجهیزات پیشرفته‌ای نظیر دوربین‌های حرفه‌ای، کیترینگ حرفه‌ای، دی‌جی و سیستم‌های صوتی و تصویری مدرن بهره می‌گیریم.</p>
                        <h4><i class="bi bi-people-fill"></i> چه تیمی در اختیار دارید؟</h4>
                        <p>تیم حرفه‌ای ما شامل عکاسان، فیلم‌برداران، دی‌جی، خواننده، تیم امنیتی و تشریفاتی است که هر کدام تجربه‌ی بالایی در زمینه‌ی خود دارند.</p>
                    </div>
                </div>
                <!-- کارت انگلیسی -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white text-start">
                        <h2 class="mb-0"><i class="bi bi-question-circle"></i> Why Choose Us?</h2>
                    </div>
                    <div class="card-body">
                        <h4><i class="bi bi-person-workspace"></i> What Services Do We Offer?</h4>
                        <p>Our team specializes in planning and executing events such as private discos, large-scale concerts, weddings, and other celebrations.</p>
                        <h4><i class="bi bi-tools"></i> What Equipment Do We Use?</h4>
                        <p>We utilize advanced equipment including professional cameras, high-end catering, DJs, and state-of-the-art audio-visual systems.</p>
                        <h4><i class="bi bi-people-fill"></i> Who Is On Our Team?</h4>
                        <p>Our professional team consists of photographers, videographers, DJs, live performers, security personnel, and event coordinators, all highly experienced in their respective fields.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- اعضای تیم -->
        <div class="team-section">
            <h2 class="text-center text-primary mb-4"><i class="bi bi-people"></i> Meet Our Team</h2>
            <div class="team-card-container">
                <!-- عکاس -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="/concert/photoabout/akas.jpg" alt="Photographer">
                        <div class="card-body">
                            <h5 class="card-title">دانیال</h5>
                            <p>دانیال عکاس حرفه‌ای با سبکی مدرن و خلاق، لحظات شاد و پر انرژی هر رویداد را در قالب عکس‌هایی زیبا و هنری ثبت می‌کند.</p>
                            <p class="text-muted">Danial is a professional photographer with a modern and creative style, capturing joyful and energetic moments in beautiful artistic photos.</p>
                        </div>
                    </div>
                </div>
                <!-- فیلم‌بردار -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="/concert/photoabout/filmbardar.jpg" alt="Videographer">
                        <div class="card-body">
                            <h5 class="card-title">رضا حسینی</h5>
                            <p>رضا حسینی، فیلمبردار حرفه‌ای با نگاهی هنرمندانه، لحظات ناب و ارزشمند هر رویداد را با ظرافت و دقت ثبت می‌کند.</p>
                            <p class="text-muted">Reza Hosseini, a professional videographer, captures precious and unique moments of every event with precision and artistic vision.</p>
                        </div>
                    </div>
                </div>
                <!-- بنیان‌گذار -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="/concert/photoabout/fundershake.jpg" alt="Founder">
                        <div class="card-body">
                            <h5 class="card-title">حافظ درویش زاده</h5>
                            <p>حافظ درویش زاده با بیش از 10 سال تجربه در صنعت برگزاری رویداد، مسئولیت برنامه‌ریزی و اجرای تمامی مراسم در مجموعه Shake را بر عهده دارد.</p>
                            <p class="text-muted">Hafez Darvishzadeh, with over 10 years of experience in event planning, leads all ceremonies at Shake with creativity and passion.</p>
                        </div>
                    </div>
                </div>
                <!-- منیجر -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="/concert/photoabout/manager.jpg" alt="Manager">
                        <div class="card-body">
                            <h5 class="card-title">مهسا مرادی</h5>
                            <p>مهسا مرادی منیجر خلاق و ایده‌پرداز، با ارائه راهکارهای نوآورانه و خلاقانه، به برگزاری رویدادهایی با تم‌های خاص و منحصر به فرد کمک می‌کند.</p>
                            <p class="text-muted">Mahsa Moradi, a creative and innovative manager, helps organize unique themed events with fresh ideas and energy.</p>
                        </div>
                    </div>
                </div>
                <!-- دی‌جی -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="/concert/photoabout/dj.jpg" alt="DJ">
                        <div class="card-body">
                            <h5 class="card-title">شاهین</h5>
                            <p>شاهین دی‌جی خلاق و نوآور، با سبک‌های متنوع موسیقی، از پاپ و الکترونیک تا رقص‌های محلی، قادر است هر نوع سلیقه‌ای را راضی کند.</p>
                            <p class="text-muted">Shahin, a creative and innovative DJ, satisfies all music tastes with diverse styles ranging from pop and electronic to local dances.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- دکمه تماس -->
       
    </div>
</main>

<?php
// ایمپورت فوتر
include __DIR__ . '/../src/views/partials/footer.php'; // مسیر صحیح به فوتر
?>
