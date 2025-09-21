<?php
session_start();

if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'organizer')) {
    header('Location: /concert/public/login.php');
    exit;
}
// بررسی توکن معتبر
require_once __DIR__ . '/../../public/verify_token.php';

require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../views/partials/header.php';

$limit = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $total_concerts = $pdo->query("SELECT COUNT(*) FROM concerts")->fetchColumn();
    $total_pages = ceil($total_concerts / $limit);

    $stmt = $pdo->prepare("SELECT * FROM concerts ORDER BY created_at DESC LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<main class="container mt-5">
    <h2 class="text-center mb-4">
        <i class="bi bi-sliders2"></i> Manage Concerts Layout
    </h2>
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h4 class="text-primary mb-4">
                        <i class="bi bi-music-note-list"></i> Concert List
                    </h4>
                    <p class="text-muted">Click on a concert below to configure its seating layout.</p>

                    <ul class="list-group mb-4">
                        <?php foreach ($concerts as $concert): ?>
                            <li class="list-group-item concert-item d-flex justify-content-between align-items-center" 
                                onclick="loadConcertDetails(<?= htmlspecialchars(json_encode($concert)) ?>)">
                                <div>
                                    <i class="bi bi-music-note-beamed me-2"></i>
                                    <strong><?= htmlspecialchars($concert['name']) ?></strong>
                                    <small class="text-muted">(<?= htmlspecialchars($concert['capacity']) ?> seats)</small>
                                </div>
                                <span class="badge bg-primary"><?= date('M j, Y', strtotime($concert['created_at'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div id="layout-section" class="bg-light p-4 rounded shadow-sm" style="display:none;">
                        <h5 id="concert-name-header" class="text-dark mb-3"></h5>
                        <p id="remaining-seats" class="text-info mb-3">Seats remaining: 0</p>
                        <div class="mb-3">
    <button class="btn btn-success me-2" onclick="seatingLayout.addCircularTable()">
        <i class="bi bi-circle-fill"></i> Add Circular Table
    </button>
    <button class="btn btn-primary me-2" onclick="seatingLayout.addRectangularTable()">
        <i class="bi bi-square-fill"></i> Add Rectangular Table
    </button>
    <button class="btn btn-warning" onclick="seatingLayout.saveLayout()">
        <i class="bi bi-save2"></i> Save Layout
    </button>
    <button class="btn btn-danger" onclick="seatingLayout.resetLayout()">
        <i class="bi bi-arrow-clockwise"></i> Reset Layout
    </button>
</div>

                        <div class="position-relative bg-white rounded" style="border: 1px solid #ddd; overflow-x: auto;">
                            <canvas id="fabric-canvas" width="1200" height="800"></canvas>
                        </div>
                    </div>

                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>

<script src="/concert/public/assets/js/fabric.min.js"></script>
<script src="/concert/public/assets/js/sweetalert2.all.min.js"></script>
<script>
class SeatingLayout {
    constructor(canvasId) {
        this.canvas = new fabric.Canvas(canvasId);
        this.canvasWidth = 1200;
        this.canvasHeight = 800;
        this.gridSize = 25;
        this.selectedConcert = null;
        this.remainingSeats = 0;
        this.currentRemoveButton = null;
        this.loadingIndicator = document.getElementById('loading-indicator');
        this.initializeEvents();
    }

    resetLayout() {
    if (!this.selectedConcert) {
        Swal.fire('Error', 'No concert selected!', 'error');
        return;
    }

    // پاک کردن کانواس و تنظیمات اولیه
    this.canvas.clear();
    this.drawGridLines();

    const seats = [];
    const seatSize = 15;
    const padding = 15; // فاصله بین صندلی‌ها
    const cols = Math.min(Math.floor(this.canvasWidth / (seatSize + padding)), this.selectedConcert.capacity);
    const rows = Math.ceil(this.selectedConcert.capacity / cols);

    let seatIndex = 0;
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            if (seatIndex >= this.selectedConcert.capacity) return;

            // تولید شناسه یکتا برای هر صندلی
            const seatId = `seat_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;

            // ساخت صندلی با ویژگی‌ها
            const seat = new fabric.Rect({
                left: col * (seatSize + padding),
                top: row * (seatSize + padding),
                width: seatSize,
                height: seatSize,
                fill: 'blue',
                selectable: true,
                lockScalingX: true,
                lockScalingY: true,
                lockRotation: true,
                hasControls: false,
                metadata: {
                    seat_id: seatId,
                    price: '', // قیمت صندلی
                    color: 'blue', // رنگ پیش‌فرض
                    position_x: col * (seatSize + padding),
                    position_y: row * (seatSize + padding),
                    reserved: false // وضعیت رزرو
                }
            });

            this.canvas.add(seat);
            seats.push(seat);
            seatIndex++;
        }
    }

    // به‌روزرسانی تعداد صندلی‌های باقی‌مانده
    this.remainingSeats = this.selectedConcert.capacity;
    this.updateRemainingSeats();
    this.canvas.renderAll();
}


initializeCanvasFromServer(data) {
    this.canvas.clear();
    this.drawGridLines();
    data.seats.forEach(seat => {
    const seatRect = new fabric.Rect({
        left: parseFloat(seat.position_x),
        top: parseFloat(seat.position_y),
        width: 15,
        height: 15,
        fill: seat.color || 'blue',
        selectable: true,
        lockScalingX: true,
        lockScalingY: true,
        lockRotation: true,
        hasControls: false,
        metadata: {
            seat_id: seat.seat_id,
            price: parseFloat(seat.price) || 0,
            label: seat.label || '',
            seatNumber: seat.seat_number || 'N/A', // بازیابی شماره صندلی
            reserved: seat.status !== 'available',
        },
    });

    if (seat.price) {
        const priceText = new fabric.Text(`${seat.price} $`, {
            left: seatRect.left + seatRect.width / 2,
            top: seatRect.top + seatRect.height / 2,
            fontSize: 10,
            fill: 'white',
            originX: 'center',
            originY: 'center',
            selectable: false,
        });
        this.canvas.add(priceText);
        seatRect.metadata.priceText = priceText;
    }

    this.canvas.add(seatRect);
});


    // لود کردن میزها
    data.tables.forEach(table => {
        let tableObject;

        if (table.table_shape === 'rectangle') {
            tableObject = new fabric.Rect({
                left: parseFloat(table.position_x),
                top: parseFloat(table.position_y),
                width: 60,
                height: 30,
                fill: table.color || 'green',
                selectable: true,
                lockScalingX: true,
                lockScalingY: true,
                lockRotation: true,
                metadata: {
                    table_id: table.table_id,
                    label: table.label || '',
                    shape: 'rectangle',
                },
            });
        } else if (table.table_shape === 'circle') {
            tableObject = new fabric.Circle({
                left: parseFloat(table.position_x),
                top: parseFloat(table.position_y),
                radius: 30,
                fill: table.color || 'red',
                selectable: true,
                lockScalingX: true,
                lockScalingY: true,
                lockRotation: true,
                metadata: {
                    table_id: table.table_id,
                    label: table.label || '',
                    shape: 'circle',
                },
            });
        }

        if (tableObject) {
            this.canvas.add(tableObject);
        }
    });

    this.canvas.renderAll();
}


initializeEvents() {
    let clickTimer = null;

    this.canvas.on('mouse:down', (e) => {
        const target = e.target;

        if (clickTimer) {
            clearTimeout(clickTimer);
            clickTimer = null;

            // دابل کلیک
            if (target && target.type === 'rect') {
                this.setSeatPrice(target); // باز کردن مودال تنظیم قیمت و رنگ
            }
        } else {
            clickTimer = setTimeout(() => {
                clickTimer = null;

                // کلیک
                if (target && target.type === 'rect') {
                    // اطمینان از اینکه کنترل‌ها غیرفعال هستند
                    target.set({
                        lockScalingX: true, // غیرفعال کردن تغییر اندازه افقی
                        lockScalingY: true, // غیرفعال کردن تغییر اندازه عمودی
                        lockRotation: true, // غیرفعال کردن چرخش
                        hasControls: false, // حذف کنترل‌ها
                    });
                    this.canvas.renderAll();
                }
            }, 300); // زمان‌بندی برای تشخیص دابل کلیک
        }
    });

    // تنظیمات حین جابه‌جایی
    this.canvas.on('object:moving', (e) => {
        const obj = e.target;

        // محدود کردن حرکت به داخل محدوده کانواس
        if (obj.left < 0) obj.left = 0;
        if (obj.top < 0) obj.top = 0;
        if (obj.left + obj.width > this.canvasWidth) obj.left = this.canvasWidth - obj.width;
        if (obj.top + obj.height > this.canvasHeight) obj.top = this.canvasHeight - obj.height;

        // اطمینان از غیرفعال بودن تغییر اندازه و کنترل‌ها
        obj.set({
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            hasControls: false,
        });
    });
}

drawSeats(capacity) {
    this.canvas.clear();
    this.drawGridLines();
    const seatSize = 15;
    const cols = Math.min(Math.floor(this.canvasWidth / (seatSize + 15)), capacity);
    const rows = Math.ceil(capacity / cols);
    this.remainingSeats = capacity;

    let seatIndex = 0;
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            if (seatIndex >= capacity) return;

            const seat = new fabric.Rect({
                left: col * (seatSize + 15),
                top: row * (seatSize + 15),
                width: seatSize,
                height: seatSize,
                fill: 'blue',
                selectable: true,
                lockScalingX: true, // غیرفعال کردن تغییر اندازه افقی
                lockScalingY: true, // غیرفعال کردن تغییر اندازه عمودی
                lockRotation: true, // غیرفعال کردن چرخش
                hasControls: false, // حذف کنترل‌ها
                metadata: {
                    price: '', // مقدار پیش‌فرض قیمت
                    priceText: null, // مقدار پیش‌فرض برای متن قیمت
                    reserved: false // حالت پیش‌فرض رزرو
                }
            });

            // محدود کردن حرکت به داخل محدوده کانواس
            seat.on('moving', (options) => {
                const obj = options.target;
                if (obj.left < 0) obj.left = 0;
                if (obj.top < 0) obj.top = 0;
                if (obj.left + obj.width > this.canvasWidth) obj.left = this.canvasWidth - obj.width;
                if (obj.top + obj.height > this.canvasHeight) obj.top = this.canvasHeight - obj.height;
            });

            this.canvas.add(seat);
            seatIndex++;
            this.remainingSeats--;
        }
    }
}




setSeatPrice(seat) {
    // اطمینان از مقداردهی متادیتای صندلی
    if (!seat.metadata) {
    seat.metadata = {};
}

// مقداردهی پیش‌فرض برای ویژگی‌های متادیتا
seat.metadata.price = seat.metadata.price || '';
seat.metadata.priceText = seat.metadata.priceText || null;
seat.metadata.color = seat.metadata.color || '';
seat.metadata.label = seat.metadata.label || '';
seat.metadata.seat_id = seat.metadata.seat_id || `seat_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;

Swal.fire({
    title: 'Configure Seat',
    html: `
        <label for="seat-price" class="form-label">Price in USD</label>
        <input id="seat-price" type="number" class="form-control mb-3" placeholder="Enter the price">
        <label for="seat-color" class="form-label">Select Color</label>
        <select id="seat-color" class="form-select mb-3">
            <option value="blue">Blue</option>
            <option value="green">Green</option>
            <option value="red">Red</option>
            <option value="yellow">Yellow</option>
            <option value="purple">Purple</option>
        </select>
        <label for="seat-label" class="form-label">Seat Label</label>
        <input id="seat-label" type="text" class="form-control mb-3" placeholder="Enter seat label (e.g., VIP, Regular)">
        <label for="seat-number" class="form-label">Seat Number</label>
        <input id="seat-number" type="text" class="form-control" placeholder="Enter seat number (e.g., Row 1, Number 1)">
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Save',
    preConfirm: () => {
        const price = document.getElementById('seat-price').value;
        const color = document.getElementById('seat-color').value;
        const label = document.getElementById('seat-label').value;
        const seatNumber = document.getElementById('seat-number').value;

        if (!price || !color || !label || !seatNumber) {
            Swal.showValidationMessage('All fields are required!');
            return null;
        }

        return { price, color, label, seatNumber };
    }
}).then((result) => {
    if (result.isConfirmed) {
        const { price, color, label, seatNumber } = result.value;

        seat.metadata.price = price;
        seat.metadata.color = color;
        seat.metadata.label = label;
        seat.metadata.seatNumber = seatNumber; // ذخیره شماره صندلی

        seat.set('fill', color);

        if (seat.metadata.priceText) {
            this.canvas.remove(seat.metadata.priceText);
        }

        const priceText = new fabric.Text(`${price} $`, {
            left: seat.left + seat.width / 2,
            top: seat.top + seat.height / 2,
            fontSize: 10,
            fill: 'white',
            originX: 'center',
            originY: 'center',
            selectable: false,
        });

        seat.metadata.priceText = priceText;
        this.canvas.add(priceText);
        this.canvas.renderAll();

        Swal.fire('Success', `Seat configured with price $${price}, color ${color}, label "${label}", and number "${seatNumber}"`, 'success');
    }
});



}


    showLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'block';
        }
    }

    hideLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'none';
        }
    }

    drawGridLines() {
        this.canvas.getObjects('line').forEach(line => this.canvas.remove(line));
        for (let i = 0; i <= this.canvasWidth; i += this.gridSize) {
            const line = new fabric.Line([i, 0, i, this.canvasHeight], {
                stroke: '#ddd',
                selectable: false,
                evented: false
            });
            this.canvas.add(line);
        }
        for (let i = 0; i <= this.canvasHeight; i += this.gridSize) {
            const line = new fabric.Line([0, i, this.canvasWidth, i], {
                stroke: '#ddd',
                selectable: false,
                evented: false
            });
            this.canvas.add(line);
        }
    } 
    
    initializeCanvas(concert) {
    this.selectedConcert = concert;
    document.getElementById('layout-section').style.display = 'block';
    document.getElementById('concert-name-header').textContent = `Managing Layout for "${concert.name}"`;

    this.showLoading();

    const savedLayout = localStorage.getItem(`concert_${concert.id}_layout`);
    console.log("Saved layout data:", savedLayout);

    if (savedLayout) {
        try {
            const layoutData = JSON.parse(savedLayout);
            if (layoutData && layoutData.layout) {
                this.canvas.loadFromJSON(
                    layoutData.layout,
                    () => {
                        console.log("Layout loaded successfully!");

                        // اطمینان از مختصات صحیح و رندر بلافاصله
                        this.canvas.getObjects().forEach((object) => {
                            if (!object.left || !object.top || object.left < 0 || object.top < 0) {
                                object.left = 50;
                                object.top = 50;
                                object.setCoords();
                            }
                        });

                        // اطمینان از رندر نهایی
                        setTimeout(() => {
                            this.canvas.renderAll();
                        }, 100);

                        this.hideLoading();
                    },
                    (error) => {
                        console.error("Error loading layout:", error);
                        this.hideLoading();
                    }
                );
                return;
            }
        } catch (error) {
            console.error("Error parsing layout data:", error);
        }
    }

    console.log("No saved layout found. Initializing empty layout.");
    this.drawSeats(concert.capacity);

    // اطمینان از رندر نهایی
    setTimeout(() => {
        this.canvas.renderAll();
    }, 100);

    this.hideLoading();
}


addCircularTable() {
    const tableRadius = 20;
    const table = new fabric.Circle({
        left: this.canvasWidth / 2 - tableRadius,
        top: this.canvasHeight / 2 - tableRadius,
        radius: tableRadius,
        fill: 'red',
        selectable: true,
        metadata: { isTable: true, table_id: `table_${Date.now()}_${Math.random().toString(36).substring(2, 8)}` } // اضافه کردن metadata
    });
    this.addRemoveButton(table);
    this.canvas.add(table);
}

    addRectangularTable() {
    const tableWidth = 40;
    const tableHeight = 20;
    const table = new fabric.Rect({
        left: this.canvasWidth / 2 - tableWidth / 2,
        top: this.canvasHeight / 2 - tableHeight / 2,
        width: tableWidth,
        height: tableHeight,
        fill: 'green',
        selectable: true,
        metadata: { isTable: true, table_id: `table_${Date.now()}_${Math.random().toString(36).substring(2, 8)}` } // اضافه کردن metadata
    });
    this.addRemoveButton(table);
    this.canvas.add(table);
}


    addRemoveButton(object) {
        object.on('selected', () => {
            if (this.currentRemoveButton) {
                this.canvas.remove(this.currentRemoveButton);
            }
            const removeButton = new fabric.Text('X', {
                left: object.left + (object.width || object.radius * 2) / 2,
                top: object.top - 10,
                fontSize: 15,
                fill: 'red',
                selectable: false,
                evented: true
            });
            removeButton.on('mousedown', () => {
                this.canvas.remove(object);
                this.canvas.remove(removeButton);
                this.currentRemoveButton = null;
            });
            this.canvas.add(removeButton);
            this.currentRemoveButton = removeButton;
            this.updateRemoveButtonPosition();
        });

        object.on('deselected', () => {
            if (this.currentRemoveButton) {
                this.canvas.remove(this.currentRemoveButton);
                this.currentRemoveButton = null;
            }
        });
    }

    updateRemoveButtonPosition() {
        if (!this.currentRemoveButton || !this.currentRemoveButton.target) return;
        const target = this.currentRemoveButton.target;
        this.currentRemoveButton.left = target.left + (target.width || target.radius * 2) / 2;
        this.currentRemoveButton.top = target.top - 10;
        this.currentRemoveButton.setCoords();
        this.canvas.renderAll();
    }



    saveLayout() {
    if (!this.selectedConcert) {
        Swal.fire('Error', 'No concert selected!', 'error');
        return;
    }
// جمع‌آوری داده‌های صندلی‌ها
const seatData = this.canvas.getObjects()
    .filter(obj => obj.type === 'rect' && obj.metadata?.seat_id) // فقط صندلی‌هایی که seat_id دارند
    .map(obj => ({
        seat_id: obj.metadata.seat_id,
        price: obj.metadata.price || 0,
        label: obj.metadata.label || "Regular",
        color: obj.metadata.color || "blue",
        position_x: obj.left || 0,
        position_y: obj.top || 0,
        seatNumber: obj.metadata.seatNumber || "N/A", // اضافه کردن شماره صندلی
    }));

    
    const tableData = this.canvas.getObjects()
    .filter(obj => obj.metadata?.isTable) // فقط میزهایی که metadata.isTable دارند
    .map(obj => ({
        table_id: obj.metadata.table_id, // شناسه یکتا
        type: obj.type === 'circle' ? 'tableB' : 'tableA', // نوع میز
        position_x: obj.left || 0,
        position_y: obj.top || 0,
        color: obj.fill || 'green',
        label: obj.metadata?.label || '',
    }));



    console.log("Seat data before sending:", seatData);
    console.log("Table data before sending:", tableData);

    fetch('/concert/src/layout/save_layout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        concertId: this.selectedConcert.id,
        layout: { objects: seatData },
    }),
})
.then(response => response.json())
.then(data => {
    console.log("Save seats response:", data);
    if (data.success) {
        Swal.fire('Success', 'Seats saved successfully!', 'success');
    } else {
        Swal.fire('Error', data.message, 'error');
    }
})
.catch(error => {
    console.error('Error saving seats:', error);
    Swal.fire('Error', 'Failed to save seats.', 'error');
});

    // ارسال داده‌های میزها
    fetch('/concert/src/layout/save_tables.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            concertId: this.selectedConcert.id,
            tables: tableData,
        }),
    })
    .then(response => response.json())
    .then(data => {
        console.log("Save tables response:", data);
        if (data.success) {
            Swal.fire('Success', 'Tables saved successfully!', 'success');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving tables:', error);
        Swal.fire('Error', 'Failed to save tables.', 'error');
    });
}






updateRemainingSeats() {
    document.getElementById('remaining-seats').textContent = `Seats remaining: ${this.remainingSeats}`;
}

}

const seatingLayout = new SeatingLayout('fabric-canvas');
function loadConcertDetails(concert) {
    seatingLayout.selectedConcert = concert;
    document.getElementById('layout-section').style.display = 'block';
    document.getElementById('concert-name-header').textContent = `Managing Layout for "${concert.name}"`;

    seatingLayout.showLoading();

    fetch('/concert/src/layout/get_layout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ concertId: concert.id }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                seatingLayout.initializeCanvasFromServer(data);
            } else {
                console.error('Error fetching layout data:', data.message);
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching layout:', error);
            Swal.fire('Error', 'Failed to fetch layout.', 'error');
        })
        .finally(() => {
            seatingLayout.hideLoading();
        });
}




</script>