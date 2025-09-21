<?php
// ایمپورت هدر
include __DIR__ . '/../src/views/partials/header.php'; // مسیر صحیح به فایل هدر

// اتصال به دیتابیس
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // دریافت تنظیمات فعال برای نمایش فیلدها
    $stmt = $pdo->query("SELECT field_name FROM additional_fields WHERE is_active = 1");
    $activeFields = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // دریافت تنظیمات نمایش تاریخ
    $stmt = $pdo->query("SELECT display_type FROM date_display_settings WHERE is_active = 1");
    $dateDisplayType = $stmt->fetchColumn() ?: 'Show Date';

    // دریافت تنظیمات چیدمان
    $stmt = $pdo->query("SELECT setting_name FROM layout_settings WHERE setting_value = 1");
    $sortOption = $stmt->fetchColumn();

    // مرتب‌سازی کنسرت‌ها بر اساس تنظیمات چیدمان
    $orderBy = 'event_date DESC'; // پیش‌فرض
    if ($sortOption === 'Date Added') {
        $orderBy = 'created_at DESC';
    } elseif ($sortOption === 'Nearest Event Date') {
        $orderBy = 'event_date ASC';
    } elseif ($sortOption === 'Most Popular') {
        $orderBy = 'capacity DESC';
    }

    // دریافت تمام کنسرت‌ها
    $stmt = $pdo->query("SELECT * FROM concerts ORDER BY $orderBy");
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تابع برای محاسبه فاصله زمانی
    function getTimeRemaining($eventDate) {
        $now = new DateTime();
        $event = new DateTime($eventDate);
        $interval = $now->diff($event);

        $parts = [];
        if ($interval->m > 0) {
            $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
        }
        if ($interval->d > 0) {
            $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
        }

        return $interval->invert ? 'Event passed' : implode(' and ', $parts);
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Concerts</title>
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/concert/public/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/concert/public/assets/css/boxallcon.css">

</head>
<main class="container mt-5">
    <section class="concerts-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">All Concerts</h2>

            <!-- فرم جستجو -->
            <form id="search-form" class="mb-4 position-relative">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control" placeholder="Search concerts by name or tags">
                    <button type="button" id="clear-search" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </form>

            <div id="concert-results" class="row justify-content-center">
                <!-- نتایج جستجو اینجا نمایش داده می‌شود -->
            </div>
        </div>
    </section>
</main>

<script src="/concert/public/assets/js/bootstrap.bundle.min.js"></script>
<script>
    const concerts = <?= json_encode($concerts) ?>;
    const activeFields = <?= json_encode($activeFields) ?>;
    const dateDisplayType = <?= json_encode($dateDisplayType) ?>;

    const searchInput = document.getElementById('search-input');
    const concertResults = document.getElementById('concert-results');
    const clearSearch = document.getElementById('clear-search');

    function truncateText(text, maxLength) {
        if (text.length > maxLength) {
            return text.substring(0, maxLength) + '...';
        }
        return text;
    }

    function displayConcerts(filteredConcerts) {
        concertResults.innerHTML = '';
        if (filteredConcerts.length === 0) {
            concertResults.innerHTML = '<p class="text-center">No concerts found. Please try a different search.</p>';
            return;
        }

        let currentRow;
        filteredConcerts.forEach((concert, index) => {
            if (index % 4 === 0) {
                currentRow = document.createElement('div');
                currentRow.className = 'row mb-4';
                concertResults.appendChild(currentRow);
            }

            const concertCard = document.createElement('div');
            concertCard.className = 'col-md-3 col-sm-6 d-flex align-items-stretch';

            concertCard.innerHTML = `
            <div class="custom-card w-100 d-flex flex-column">
                ${activeFields.includes('event_date') ? `
                <div class="custom-card-header text-white bg-primary">
                    <i class="bi bi-calendar-event me-2"></i>
                    ${dateDisplayType === 'Show Date' ? 
                        new Date(concert.event_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) :
                        getTimeRemaining(concert.event_date)
                    }
                </div>` : ''}
                <img src="/concert/${concert.banner}" class="custom-card-img-top" alt="Concert Image">
                <div class="custom-card-body d-flex flex-column flex-grow-1">
                    <h5 class="custom-card-title">${truncateText(concert.name, 25)}</h5>
                    ${activeFields.includes('description') ? `<p class="custom-card-text">${truncateText(concert.description || '', 100)}</p>` : ''}
                    ${activeFields.includes('location') ? `<p><strong>Location:</strong> ${truncateText(concert.location, 30)}</p>` : ''}
                    ${activeFields.includes('tags') ? `<p><strong>Tags:</strong> ${truncateText(concert.tags, 50)}</p>` : ''}
                    ${activeFields.includes('capacity') ? `<p><strong>Capacity:</strong> ${concert.capacity}</p>` : ''}
                    <div class="custom-card-btn mt-auto">
                        <a href="/concert/public/concert_detail.php?id=${concert.id}" 
                        class="btn btn-primary w-100 text-center">
                        <i class="bi bi-eye me-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
            `;
            currentRow.appendChild(concertCard);
        });
    }

    function getTimeRemaining(eventDate) {
        const now = new Date();
        const event = new Date(eventDate);
        const diffTime = event - now;

        if (diffTime <= 0) return 'Event passed';

        const days = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        const months = Math.floor(days / 30);

        const parts = [];
        if (months > 0) parts.push(`${months} month${months > 1 ? 's' : ''}`);
        if (days % 30 > 0) parts.push(`${days % 30} day${days % 30 > 1 ? 's' : ''}`);

        return parts.join(' and ');
    }

    function filterConcerts(query) {
        const lowerQuery = query.toLowerCase();
        return concerts.filter(concert =>
            concert.name.toLowerCase().includes(lowerQuery) ||
            (concert.tags && concert.tags.toLowerCase().includes(lowerQuery))
        );
    }

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        const filteredConcerts = filterConcerts(query);
        displayConcerts(filteredConcerts);
    });

    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        displayConcerts(concerts);
    });

    displayConcerts(concerts);
</script>

<?php
// ایمپورت فوتر
include __DIR__ . '/../src/views/partials/footer.php'; // مسیر صحیح به فایل فوتر
?>
