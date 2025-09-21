<?php
// اتصال به دیتابیس
require_once __DIR__ . '/../config/database.php';

// بررسی توکن در مرورگر
if (isset($_COOKIE['user_token'])) {
    $token = $_COOKIE['user_token'];

    try {
        // بررسی توکن در دیتابیس
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // جستجو برای کاربر با توکن
        $stmt = $pdo->prepare('SELECT * FROM users WHERE token = :token');
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die('User not found or invalid token.');
        }
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('User token is required.');
}

// دریافت شناسه کنسرت از URL
if (isset($_GET['id'])) {
    $concert_id = $_GET['id'];

    try {
        // دریافت اطلاعات کنسرت
        $stmt = $pdo->prepare('SELECT * FROM concerts WHERE id = :id');
        $stmt->bindParam(':id', $concert_id, PDO::PARAM_INT);
        $stmt->execute();
        $concert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$concert) {
            die('Concert not found.');
        }

        // دریافت صندلی‌های رزرو شده با وضعیت `completed`
        $stmt = $pdo->prepare('
            SELECT seat_id 
            FROM reservations 
            WHERE concert_id = :concert_id AND status = "completed"
        ');
        $stmt->bindParam(':concert_id', $concert_id, PDO::PARAM_INT);
        $stmt->execute();
        $reservedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // تبدیل صندلی‌های رزرو شده به آرایه
        $reservedSeats = array_reduce($reservedSeats, function ($carry, $item) {
            return array_merge($carry, json_decode($item, true));
        }, []);

        // دریافت داده‌های صندلی و میز از سرور
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://shakestage.com//concert/src/layout/get_layout.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['concertId' => $concert_id]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $layout_data = json_decode($response, true);

        if (!$layout_data['success']) {
            die('Failed to fetch layout: ' . $layout_data['message']);
        }

        $seats = $layout_data['seats'];
        $tables = $layout_data['tables'];
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('Concert ID is required.');
}
?>


<!-- لینک به فایل CSS -->
<link rel="stylesheet" href="/concert/public/assets/css/boxconcerthomepage.css">

<!-- ایمپورت هدر -->
<?php include __DIR__ . '/../src/views/partials/header.php'; ?>
<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            <!-- اطلاعات کاربر -->
            <h3 class="card-title">User Info</h3>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <?php if (!empty($user['full_name'])): ?>
                <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
            <?php endif; ?>
            <hr>

            <!-- اطلاعات کنسرت -->
            <h4 class="card-title">Concert Information</h4>
            <p><strong>Capacity:</strong> <?= htmlspecialchars($concert['capacity']) ?></p>
            <hr>

            <!-- آموزش -->
            <h4 class="card-title text-info"><i class="bi bi-info-circle"></i> How to Use</h4>
            <p>
                Below is the seating arrangement for the concert. Here's what you need to know:
            </p>
            <ul>
                <li><strong>Seats:</strong> Each square on the canvas represents a seat. The colors indicate the seat's status:
                    <ul>
                        <li><span style="color: blue;">Blue:</span> Available for booking.</li>
                        <li><span style="color: #C0C0C0;">Silver:</span> Reserved or already booked.</li>
                        <li><span style="color: green;">Green:</span> Special VIP seats.</li>
                    </ul>
                </li>
                <li><strong>Tables:</strong> Tables are displayed as shapes:
                    <ul>
                        <li><span style="color: red;">Red circles:</span> Circular tables.</li>
                        <li><span style="color: green;">Green rectangles:</span> Rectangular tables.</li>
                    </ul>
                </li>
                <li><strong>Interaction:</strong> Click on available seats (blue) to view details and add them to your cart. Reserved seats cannot be clicked.</li>
            </ul>
            <hr>

            <!-- دکمه مشاهده سبد خرید -->
            <div class="mt-4">
                <button id="view-cart-btn" class="btn btn-success">
                    <i class="bi bi-cart"></i> View Cart
                </button>
            </div>

            <!-- کانواس برای نمایش صندلی‌ها و میزها -->
            <div class="mt-4">
            <h4 class="text-center text-primary">
    <i class="bi bi-easel"></i> Stage Layout
</h4>
                <div class="position-relative bg-white rounded" style="border: 1px solid #ddd; overflow-x: auto;">
                    <canvas id="fabric-canvas" width="1200" height="800"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ایمپورت فوتر -->
<?php include __DIR__ . '/../src/views/partials/footer.php'; ?>
<script src="/concert/public/assets/js/fabric.min.js"></script>
<script src="/concert/public/assets/js/sweetalert2.all.min.js"></script>
<script>
    const canvas = new fabric.Canvas('fabric-canvas');
    canvas.setWidth(1200);
    canvas.setHeight(800);

    // صندلی‌های رزرو شده (از PHP به جاوااسکریپت ارسال شده)
    const reservedSeats = <?= json_encode($reservedSeats) ?>;

    const initializeCanvasFromServer = (data) => {
        canvas.clear();

        // رسم صندلی‌ها
        data.seats.forEach((seat) => {
            const isReserved = reservedSeats.includes(seat.seat_id); // بررسی رزرو بودن صندلی

            const seatRect = new fabric.Rect({
                left: parseFloat(seat.position_x),
                top: parseFloat(seat.position_y),
                width: 15,
                height: 15,
                fill: isReserved ? '#C0C0C0' : (seat.color || 'blue'), // رنگ طوسی نقره‌ای برای رزرو شده‌ها
                selectable: !isReserved, // غیرفعال کردن انتخاب صندلی رزرو شده
                lockMovementX: true,
                lockMovementY: true,
                lockScalingX: true,
                lockScalingY: true,
                lockRotation: true,
                hasControls: false,
                metadata: {
                    seat_id: seat.seat_id,
                    price: parseFloat(seat.price) || 0,
                    label: seat.label || '',
                    reserved: isReserved, // ذخیره وضعیت رزرو
                },
            });

            // رویداد کلیک برای صندلی‌های رزرو نشده
            if (!isReserved) {
                seatRect.on('mousedown', () => showSeatDetails(seatRect.metadata));
            }

            canvas.add(seatRect);
        });

        // رسم میزها
        data.tables.forEach((table) => {
            let tableObject;

            if (table.table_shape === 'rectangle') {
                tableObject = new fabric.Rect({
                    left: parseFloat(table.position_x),
                    top: parseFloat(table.position_y),
                    width: 60,
                    height: 30,
                    fill: table.color || 'green',
                    selectable: false,
                    lockMovementX: true,
                    lockMovementY: true,
                    lockScalingX: true,
                    lockScalingY: true,
                    lockRotation: true,
                    hasControls: false,
                });
            } else if (table.table_shape === 'circle') {
                tableObject = new fabric.Circle({
                    left: parseFloat(table.position_x),
                    top: parseFloat(table.position_y),
                    radius: 30,
                    fill: table.color || 'red',
                    selectable: false,
                    lockMovementX: true,
                    lockMovementY: true,
                    lockScalingX: true,
                    lockScalingY: true,
                    lockRotation: true,
                    hasControls: false,
                });
            }

            canvas.add(tableObject);
        });

        canvas.renderAll();
    };

    const showSeatDetails = (metadata) => {
        if (metadata.reserved) {
            Swal.fire('Info', 'This seat is already reserved.', 'info');
            return;
        }

        Swal.fire({
            title: 'Seat Information',
            html: `
                <p><strong>Label:</strong> ${metadata.label || 'No Label'}</p>
                <p><strong>Price:</strong> €${metadata.price.toFixed(2)}</p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add to Cart',
            preConfirm: () => {
                addToCart(metadata);
            },
        });
    };

    const addToCart = (metadata) => {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const isAlreadyInCart = cart.some((item) => item.seat_id === metadata.seat_id);

        if (isAlreadyInCart) {
            Swal.fire('Info', 'This seat is already in your cart!', 'info');
            return;
        }

        cart.push({
            seat_id: metadata.seat_id,
            label: metadata.label,
            price: metadata.price,
        });

        localStorage.setItem('cart', JSON.stringify(cart));
        Swal.fire('Success', 'Seat added to your cart!', 'success');
    };

    document.getElementById('view-cart-btn').addEventListener('click', () => {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart.length === 0) {
            Swal.fire('Info', 'Your cart is empty!', 'info');
            return;
        }

        let cartDetails = '<ul class="list-group">';
        cart.forEach((seat, index) => {
            cartDetails += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong>Seat ID:</strong> ${seat.seat_id}<br>
                        <strong>Label:</strong> ${seat.label}<br>
                        <strong>Price:</strong> €${seat.price.toFixed(2)}
                    </span>
                    <button class="btn btn-danger btn-sm remove-seat-btn" data-index="${index}">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </li>
            `;
        });
        cartDetails += '</ul>';

        cartDetails += `
            <form id="finalize-form" action="/concert/public/checkout.php" method="POST">
                <input type="hidden" name="cart" id="cart-input" value='${JSON.stringify(cart)}'>
                <button type="submit" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-bag-check"></i> Finalize Purchase
                </button>
            </form>
        `;

        Swal.fire({
            title: 'Your Cart',
            html: cartDetails,
            showCancelButton: true,
            confirmButtonText: 'Close',
            didOpen: () => {
                document.querySelectorAll('.remove-seat-btn').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        const index = parseInt(event.target.dataset.index, 10);
                        removeFromCart(index);
                    });
                });
            },
        });
    });

    const removeFromCart = (index) => {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        cart.splice(index, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        Swal.fire('Success', 'Seat removed from your cart!', 'success').then(() => {
            document.getElementById('view-cart-btn').click();
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const layoutData = <?= json_encode(['seats' => $seats, 'tables' => $tables]) ?>;
        initializeCanvasFromServer(layoutData);
    });
</script>
