<?php
/**
 * -------------------------In the name of ALLAH-----------------------------
 * --------------------------------------------------------------------------
 * Programmer:  Amid Ahadi
 * Email:       Amid-ahadi@gmail.com
 * Website:     amid-ahadi.ir
 * Copyright:   All rights reserved for Amid Ahadi
 * --------------------------------------------------------------------------
 * Coded for Karaj Emam Hospital with love ❤️
 * Created:     2026-06-20
 */
include("../config/database.php");
include("../auth/role_check.php");

allowRoles(['admin', 'nutrition_manager']);

$message = "";
$messageType = "";

/**
 * تابع کمکی برای ریدایرکت بعد از POST
 */
function redirectWithMessage($type, $msg)
{
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $url . "?type=" . urlencode($type) . "&msg=" . urlencode($msg));
    exit;
}

/**
 * گرفتن پیام بعد از ریدایرکت
 */
if (isset($_GET['msg'], $_GET['type'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'];
}

/**
 * تبدیل اعداد فارسی/عربی به انگلیسی
 */
function normalizeNumber($value)
{
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $value = str_replace($persian, $english, $value);
    $value = str_replace($arabic, $english, $value);
    $value = str_replace([',', '٬', ' '], '', $value);

    return $value;
}

/**
 * ۱. افزودن نوع مصرف‌کننده جدید
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consumer'])) {

    $name = trim($_POST['type_name'] ?? '');

    if ($name === '') {
        redirectWithMessage('danger', 'نام نوع مصرف‌کننده نمی‌تواند خالی باشد.');
    }

    $stmt = $conn->prepare("
        INSERT INTO consumer_types (type_name)
        VALUES (?)
    ");

    if (!$stmt) {
        redirectWithMessage('danger', 'خطا در آماده‌سازی ثبت نوع مصرف‌کننده.');
    }

    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage('success', 'نوع مصرف‌کننده با موفقیت ثبت شد.');
    } else {
        $error = $stmt->error;
        $stmt->close();
        redirectWithMessage('danger', 'خطا در ثبت نوع مصرف‌کننده: ' . $error);
    }
}

/**
 * ۲. ثبت یا بروزرسانی قیمت برای ترکیب وعده و مصرف‌کننده
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_price'])) {

    $meal_id = (int)($_POST['meal_id'] ?? 0);
    $consumer_id = (int)($_POST['consumer_id'] ?? 0);

    $priceRaw = normalizeNumber($_POST['price'] ?? '0');
    $price = (int)$priceRaw;

    if ($meal_id <= 0 || $consumer_id <= 0) {
        redirectWithMessage('danger', 'وعده یا نوع مصرف‌کننده معتبر نیست.');
    }

    if ($price < 0) {
        redirectWithMessage('danger', 'قیمت نمی‌تواند منفی باشد.');
    }

    /*
     * این کوئری نیاز دارد روی جدول food_prices
     * برای meal_type_id و consumer_type_id کلید Unique داشته باشی.
     */
    $stmt = $conn->prepare("
        INSERT INTO food_prices (meal_type_id, consumer_type_id, price)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            price = VALUES(price)
    ");

    if (!$stmt) {
        redirectWithMessage('danger', 'خطا در آماده‌سازی ثبت قیمت.');
    }

    $stmt->bind_param("iii", $meal_id, $consumer_id, $price);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage('success', 'قیمت با موفقیت ثبت/بروزرسانی شد.');
    } else {
        $error = $stmt->error;
        $stmt->close();
        redirectWithMessage('danger', 'خطا در ثبت قیمت: ' . $error);
    }
}

/**
 * ۳. ویرایش قیمت مستقیم از داخل جدول
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {

    $meal_id = (int)($_POST['meal_id'] ?? 0);
    $consumer_id = (int)($_POST['consumer_id'] ?? 0);

    $priceRaw = normalizeNumber($_POST['price'] ?? '0');
    $price = (int)$priceRaw;

    if ($meal_id <= 0 || $consumer_id <= 0) {
        redirectWithMessage('danger', 'اطلاعات ردیف برای ویرایش معتبر نیست.');
    }

    if ($price < 0) {
        redirectWithMessage('danger', 'قیمت نمی‌تواند منفی باشد.');
    }

    $stmt = $conn->prepare("
        UPDATE food_prices
        SET price = ?
        WHERE meal_type_id = ?
          AND consumer_type_id = ?
    ");

    if (!$stmt) {
        redirectWithMessage('danger', 'خطا در آماده‌سازی ویرایش قیمت.');
    }

    $stmt->bind_param("iii", $price, $meal_id, $consumer_id);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage('success', 'قیمت با موفقیت ویرایش شد.');
    } else {
        $error = $stmt->error;
        $stmt->close();
        redirectWithMessage('danger', 'خطا در ویرایش قیمت: ' . $error);
    }
}

/**
 * دریافت داده‌های اولیه
 */
$meals = $conn->query("
    SELECT id, meal_name
    FROM meal_types
    ORDER BY id ASC
");

$consumers = $conn->query("
    SELECT id, type_name
    FROM consumer_types
    ORDER BY id ASC
");

$consumerList = $conn->query("
    SELECT id, type_name
    FROM consumer_types
    ORDER BY id ASC
");

$prices = $conn->query("
    SELECT 
        p.meal_type_id,
        p.consumer_type_id,
        p.price,
        m.meal_name,
        c.type_name
    FROM food_prices p
    JOIN meal_types m ON p.meal_type_id = m.id
    JOIN consumer_types c ON p.consumer_type_id = c.id
    ORDER BY m.id ASC, c.id ASC
");

include("../layout/header.php");
?>

<div class="container-fluid py-4">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
        </div>
    <?php endif; ?>

    <div class="row">

        <!-- ستون سمت راست: تعریف مصرف‌کننده -->
        <div class="col-md-4">

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    تعریف انواع مصرف‌کننده
                </div>

                <div class="card-body">

                    <form method="POST" class="d-flex gap-2 mb-3">
                        <input
                            type="text"
                            name="type_name"
                            class="form-control"
                            placeholder="مثلاً: پزشک، همراه..."
                            required
                        >

                        <button type="submit" name="add_consumer" class="btn btn-primary">
                            ثبت
                        </button>
                    </form>

                    <hr>

                    <div class="list-group">
                        <?php if ($consumerList && $consumerList->num_rows > 0): ?>
                            <?php while ($c = $consumerList->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($c['type_name']) ?></span>
                                    <small class="text-muted">ID: <?= (int)$c['id'] ?></small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                هنوز نوع مصرف‌کننده‌ای ثبت نشده است.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>

        <!-- ستون سمت چپ: جدول قیمت‌گذاری ترکیبی -->
        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    تنظیم قیمت نهایی (وعده + مصرف‌کننده)
                </div>

                <div class="card-body">

                    <!-- فرم ثبت قیمت جدید -->
                    <form method="POST" class="row g-3 align-items-end mb-4 bg-light p-3 rounded border">

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                انتخاب وعده:
                            </label>

                            <select name="meal_id" class="form-select" required>
                                <?php if ($meals && $meals->num_rows > 0): ?>
                                    <?php while ($m = $meals->fetch_assoc()): ?>
                                        <option value="<?= (int)$m['id'] ?>">
                                            <?= htmlspecialchars($m['meal_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                نوع مصرف‌کننده:
                            </label>

                            <select name="consumer_id" class="form-select" required>
                                <?php if ($consumers && $consumers->num_rows > 0): ?>
                                    <?php while ($c = $consumers->fetch_assoc()): ?>
                                        <option value="<?= (int)$c['id'] ?>">
                                            <?= htmlspecialchars($c['type_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-bold">
                                قیمت:
                            </label>

                            <input
                                type="text"
                                name="price"
                                class="form-control price-input"
                                inputmode="numeric"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <div class="col-md-2">
                            <button type="submit" name="save_price" class="btn btn-success w-100">
                                ثبت قیمت
                            </button>
                        </div>

                    </form>

                    <!-- جدول قیمت‌ها -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 20%;">وعده</th>
                                    <th style="width: 30%;">مصرف‌کننده</th>
                                    <th style="width: 30%;">قیمت مصوب</th>
                                    <th style="width: 20%;">عملیات</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if ($prices && $prices->num_rows > 0): ?>
                                    <?php while ($p = $prices->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?= htmlspecialchars($p['meal_name']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($p['type_name']) ?>
                                            </td>

                                            <td>
                                                <form method="POST" class="d-flex gap-2 justify-content-center align-items-center price-edit-form">

                                                    <input
                                                        type="hidden"
                                                        name="meal_id"
                                                        value="<?= (int)$p['meal_type_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="consumer_id"
                                                        value="<?= (int)$p['consumer_type_id'] ?>"
                                                    >

                                                    <input
                                                        type="text"
                                                        name="price"
                                                        class="form-control form-control-sm text-center fw-bold text-success price-input"
                                                        value="<?= number_format((int)$p['price']) ?>"
                                                        inputmode="numeric"
                                                        autocomplete="off"
                                                        style="max-width: 180px;"
                                                        required
                                                    >

                                                    <span class="text-muted small">
                                                        ریال
                                                    </span>

                                            </td>

                                            <td>
                                                    <button
                                                        type="submit"
                                                        name="update_price"
                                                        class="btn btn-sm btn-warning"
                                                    >
                                                        ذخیره ویرایش
                                                    </button>

                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="alert alert-warning mb-0">
                                                هنوز قیمتی ثبت نشده است.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <strong>نکته:</strong>
                        تغییر قیمت در این صفحه فقط قیمت‌های جدید را تغییر می‌دهد.
                        آمارهایی که قبلاً در جدول
                        <code>daily_statistics</code>
                        با ستون
                        <code>unit_price</code>
                        ثبت شده‌اند، تغییر نمی‌کنند.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const priceInputs = document.querySelectorAll('.price-input');

    priceInputs.forEach(function (input) {

        input.addEventListener('focus', function () {
            this.select();
        });

        input.addEventListener('input', function () {
            let value = this.value;

            value = value.replace(/[۰-۹]/g, function (d) {
                return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
            });

            value = value.replace(/[٠-٩]/g, function (d) {
                return '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
            });

            value = value.replace(/[^0-9]/g, '');

            if (value.length > 0) {
                this.value = Number(value).toLocaleString('en-US');
            } else {
                this.value = '';
            }
        });

    });

    const forms = document.querySelectorAll('form');

    forms.forEach(function (form) {
        form.addEventListener('submit', function () {
            const inputs = form.querySelectorAll('.price-input');

            inputs.forEach(function (input) {
                input.value = input.value.replace(/,/g, '');
            });
        });
    });
});
</script>

<?php include("../layout/footer.php"); ?>
