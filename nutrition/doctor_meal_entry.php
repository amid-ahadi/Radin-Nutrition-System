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



function fa_to_en_numbers($string) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $string = str_replace($persian, $english, $string);
    $string = str_replace($arabic, $english, $string);

    return $string;
}

function miladi_to_shamsi($date) {
    if (empty($date) || $date === '0000-00-00') return '';

    $date = substr($date, 0, 10);
    $parts = explode('-', $date);

    if (count($parts) !== 3) return '';

    $gy = (int)$parts[0];
    $gm = (int)$parts[1];
    $gd = (int)$parts[2];

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy -= 1600;
    $gm -= 1;
    $gd -= 1;

    $g_day_no = 365 * $gy + intdiv($gy + 3, 4) - intdiv($gy + 99, 100) + intdiv($gy + 399, 400);

    for ($i = 0; $i < $gm; $i++) {
        $g_day_no += $g_days_in_month[$i];
    }

    if ($gm > 1 && (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0))) {
        $g_day_no++;
    }

    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;
    $j_np = intdiv($j_day_no, 12053);
    $j_day_no %= 12053;

    $jy = 979 + 33 * $j_np + 4 * intdiv($j_day_no, 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += intdiv($j_day_no - 1, 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; $i++) {
        $j_day_no -= $j_days_in_month[$i];
    }

    $jm = $i + 1;
    $jd = $j_day_no + 1;

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function shamsi_to_miladi($date) {
    if (empty($date)) return '';

    $date = fa_to_en_numbers($date);
    $date = str_replace('-', '/', trim($date));

    $parts = explode('/', $date);

    if (count($parts) !== 3) return '';

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jd <= 0) return '';
    if ($jm > 12 || $jd > 31) return '';

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $jy -= 979;
    $jm -= 1;
    $jd -= 1;

    $j_day_no = 365 * $jy + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4);

    for ($i = 0; $i < $jm; $i++) {
        $j_day_no += $j_days_in_month[$i];
    }

    $j_day_no += $jd;

    $g_day_no = $j_day_no + 79;

    $gy = 1600 + 400 * intdiv($g_day_no, 146097);
    $g_day_no %= 146097;

    $leap = true;

    if ($g_day_no >= 36525) {
        $g_day_no--;
        $gy += 100 * intdiv($g_day_no, 36524);
        $g_day_no %= 36524;

        if ($g_day_no >= 365) {
            $g_day_no++;
        } else {
            $leap = false;
        }
    }

    $gy += 4 * intdiv($g_day_no, 1461);
    $g_day_no %= 1461;

    if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += intdiv($g_day_no, 365);
        $g_day_no %= 365;
    }

    for ($i = 0; $i < 12 && $g_day_no >= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0); $i++) {
        $g_day_no -= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0);
    }

    $gm = $i + 1;
    $gd = $g_day_no + 1;

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

function redirect_to_date($date, $msg = '') {
    $url = "doctor_meal_entry.php?date=" . urlencode($date);

    if (!empty($msg)) {
        $url .= "&msg=" . urlencode($msg);
    }

    header("Location: " . $url);
    exit;
}

/*
|--------------------------------------------------------------------------
| آماده‌سازی تاریخ جاری صفحه
|--------------------------------------------------------------------------
*/

$date = $_GET['date'] ?? date("Y-m-d");

// اگر تاریخ از URL شمسی آمده باشد
if (strpos($date, '/') !== false) {
    $convertedDate = shamsi_to_miladi($date);
    if (!empty($convertedDate)) {
        $date = $convertedDate;
    }
}

// اعتبارسنجی تاریخ میلادی
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date("Y-m-d");
}

$shamsiDate = miladi_to_shamsi($date);

/*
|--------------------------------------------------------------------------
| حذف رکورد
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM doctor_meals WHERE id = ? AND confirmed = 0");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    redirect_to_date($date, "deleted");
}

/*
|--------------------------------------------------------------------------
| ویرایش تعداد
|--------------------------------------------------------------------------
*/

if (isset($_POST['edit_quantity'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($quantity < 1) {
        $quantity = 1;
    }

    if ($editId > 0) {
        $stmt = $conn->prepare("UPDATE doctor_meals SET quantity = ? WHERE id = ? AND confirmed = 0");
        $stmt->bind_param("ii", $quantity, $editId);
        $stmt->execute();
    }

    redirect_to_date($date, "updated");
}

/*
|--------------------------------------------------------------------------
| ثبت وعده غذایی پزشک
|--------------------------------------------------------------------------
*/



if (isset($_POST['save_doctor_meal'])) {
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $meal_date_shamsi = $_POST['meal_date_shamsi'] ?? '';
    $meal_date = shamsi_to_miladi($meal_date_shamsi);
    if (empty($meal_date)) { $meal_date = date("Y-m-d"); }
    $meals = $_POST['meals'] ?? [];

    if ($doctor_id > 0 && !empty($meals)) {
        
        // ۱. پیدا کردن نوع مصرف‌کننده (پزشک)
        $doctorConsumerTypeId = 0;
        $stmtC = $conn->prepare("SELECT id FROM consumer_types WHERE type_name LIKE '%پزشک%' OR type_name LIKE '%دکتر%' LIMIT 1");
        $stmtC->execute();
        $resC = $stmtC->get_result();
        if ($rowC = $resC->fetch_assoc()) {
            $doctorConsumerTypeId = (int)$rowC['id'];
        } else {
            // اگر اینجا متوقف شد، یعنی در جدول consumer_types کلمه پزشک یا دکتر پیدا نشد
            die("خطا: نوع مصرف‌کننده 'پزشکان' در جدول consumer_types پیدا نشد. لطفاً ابتدا این نوع را بسازید.");
        }

        // ۲. چک کردن رایگان بودن پزشک
        $is_free = 0;
        $stmtD = $conn->prepare("SELECT is_free FROM doctors WHERE id = ?");
        $stmtD->bind_param("i", $doctor_id);
        $stmtD->execute();
        if ($rowD = $stmtD->get_result()->fetch_assoc()) {
            $is_free = (int)$rowD['is_free'];
        }

        foreach ($meals as $meal_id) {
            $meal_id = (int)$meal_id;
            
            // ۳. گرفتن قیمت
            $unit_price = 0;
            if ($is_free !== 1) {
                $stmtP = $conn->prepare("SELECT price FROM food_prices WHERE meal_type_id = ? AND consumer_type_id = ? LIMIT 1");
                $stmtP->bind_param("ii", $meal_id, $doctorConsumerTypeId);
                $stmtP->execute();
                $resP = $stmtP->get_result();
                if ($rowP = $resP->fetch_assoc()) {
                    $unit_price = (float)$rowP['price'];
                } else {
                    // اگر اینجا متوقف شد، یعنی برای این غذا و این نوع مصرف‌کننده در جدول food_prices قیمتی ثبت نشده
                    die("خطا: برای وعده (ID: $meal_id) و گروه پزشک (ID: $doctorConsumerTypeId) در جدول food_prices قیمتی ثبت نشده است.");
                }
            }

            $total_price = $unit_price * 1;

            // ۴. درج در دیتابیس
            $stmtInsert = $conn->prepare("INSERT INTO doctor_meals (doctor_id, meal_type_id, meal_date, quantity, unit_price, confirmed) VALUES (?, ?, ?, 1, ?, 0)");
            $stmtInsert->bind_param("iisd", $doctor_id, $meal_id, $meal_date, $unit_price);
            
            if (!$stmtInsert->execute()) {
                die("خطای دیتابیس در هنگام INSERT: " . $conn->error);
            }
        }
    }
    redirect_to_date($meal_date, "saved");
}


/*
|--------------------------------------------------------------------------
| پیدا کردن نوع مصرف‌کننده پزشکان برای daily_statistics
|--------------------------------------------------------------------------
| اگر در جدول consumer_types گروهی با نام پزشک/دکتر باشد همان را استفاده می‌کند.
| اگر نبود، مقدار 0 می‌ماند و تایید نهایی انجام نمی‌شود.
*/

$doctorConsumerTypeId = 17;

$ctQuery = "
    SELECT id 
    FROM consumer_types 
    WHERE type_name LIKE '%پزشک%' 
       OR type_name LIKE '%دکتر%' 
       OR type_name LIKE '%doctor%' 
    LIMIT 1
";

$ctResult = $conn->query($ctQuery);

if ($ctResult && $ctRow = $ctResult->fetch_assoc()) {
    $doctorConsumerTypeId = (int)$ctRow['id'];
}

/*
|--------------------------------------------------------------------------
| تایید نهایی روز
|--------------------------------------------------------------------------
*/

if (isset($_POST['confirm_day'])) {
    $confirm_date_shamsi = $_POST['confirm_date_shamsi'] ?? '';
    $confirm_date = shamsi_to_miladi($confirm_date_shamsi);

    if (empty($confirm_date)) {
        $confirm_date = $date;
    }

    if ($doctorConsumerTypeId <= 0) {
        redirect_to_date($confirm_date, "consumer_type_not_found");
    }

    /*
    | گرفتن جمع وعده‌های تاییدنشده همان روز
    */
    $stmtSummary = $conn->prepare("
        SELECT 
            meal_type_id,
            SUM(quantity) AS total_quantity,
            MAX(unit_price) AS unit_price
        FROM doctor_meals
        WHERE meal_date = ?
          AND confirmed = 0
        GROUP BY meal_type_id
    ");
    $stmtSummary->bind_param("s", $confirm_date);
    $stmtSummary->execute();
    $summaryResult = $stmtSummary->get_result();

    while ($row = $summaryResult->fetch_assoc()) {
        $mealTypeId = (int)$row['meal_type_id'];
        $totalQuantity = (int)$row['total_quantity'];
        $unitPrice = (float)$row['unit_price'];

        if ($mealTypeId <= 0 || $totalQuantity <= 0) {
            continue;
        }

        /*
        | بررسی وجود رکورد در daily_statistics
        */
        $stmtFind = $conn->prepare("
            SELECT id 
            FROM daily_statistics
            WHERE stat_date = ?
              AND meal_type_id = ?
              AND consumer_type_id = ?
            LIMIT 1
        ");
        $stmtFind->bind_param("sii", $confirm_date, $mealTypeId, $doctorConsumerTypeId);
        $stmtFind->execute();
        $findResult = $stmtFind->get_result();

        if ($existing = $findResult->fetch_assoc()) {
            /*
            | اگر رکورد وجود داشت، تعداد به آن اضافه می‌شود
            */
            $statId = (int)$existing['id'];

            $stmtUpdate = $conn->prepare("
                UPDATE daily_statistics
                SET quantity = quantity + ?
                WHERE id = ?
            ");
            $stmtUpdate->bind_param("ii", $totalQuantity, $statId);
            $stmtUpdate->execute();

        } else {
            /*
            | اگر رکورد وجود نداشت، ساخته می‌شود
            */
            $stmtInsertStat = $conn->prepare("
                INSERT INTO daily_statistics
                    (stat_date, meal_type_id, consumer_type_id, quantity, unit_price)
                VALUES
                    (?, ?, ?, ?, ?)
            ");
            $stmtInsertStat->bind_param("siiid", $confirm_date, $mealTypeId, $doctorConsumerTypeId, $totalQuantity, $unitPrice);
            $stmtInsertStat->execute();
        }
    }

    /*
    | قفل کردن رکوردهای پزشکان در آن تاریخ
    */
    $stmtConfirm = $conn->prepare("
        UPDATE doctor_meals
        SET confirmed = 1
        WHERE meal_date = ?
          AND confirmed = 0
    ");
    $stmtConfirm->bind_param("s", $confirm_date);
    $stmtConfirm->execute();

    redirect_to_date($confirm_date, "confirmed");
}

/*
|--------------------------------------------------------------------------
| دریافت لیست پزشکان، وعده‌ها و ثبت‌ها
|--------------------------------------------------------------------------
*/

$doctors = [];
$doctorResult = $conn->query("
    SELECT id, doctor_name, is_free 
    FROM doctors 
    WHERE is_active = 1 
    ORDER BY doctor_name ASC
");

if ($doctorResult) {
    while ($d = $doctorResult->fetch_assoc()) {
        $doctors[] = $d;
    }
}

$meals = [];
$mealResult = $conn->query("
    SELECT id, meal_name, price 
    FROM meal_types 
    ORDER BY id ASC
");

if ($mealResult) {
    while ($m = $mealResult->fetch_assoc()) {
        $meals[] = $m;
    }
}

$stmtList = $conn->prepare("
    SELECT 
        dm.id,
        dm.doctor_id,
        dm.meal_type_id,
        dm.meal_date,
        dm.quantity,
        dm.unit_price,
        dm.confirmed,
        d.doctor_name,
        d.is_free,
        mt.meal_name
    FROM doctor_meals dm
    INNER JOIN doctors d ON d.id = dm.doctor_id
    INNER JOIN meal_types mt ON mt.id = dm.meal_type_id
    WHERE dm.meal_date = ?
    ORDER BY dm.confirmed ASC, dm.id DESC
");
$stmtList->bind_param("s", $date);
$stmtList->execute();
$listResult = $stmtList->get_result();

$totalDayQuantity = 0;
$totalDayCost = 0;
$hasUnconfirmed = false;

include("../layout/header.php");
?>

<link rel="stylesheet" href="../assets/css/jalalidatepicker.min.css">
<script src="../assets/js/jalalidatepicker.min.js"></script>

<link href="../assets/css/select2.min.css" rel="stylesheet">

<style>
    .select2-container {
        width: 100% !important;
        direction: rtl;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        border-radius: 8px;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-right: 12px;
        padding-left: 25px;
        text-align: right;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        left: 8px;
        right: auto;
    }

    .badge-soft-warning {
        background: #fff3cd;
        color: #856404;
        padding: 6px 10px;
        border-radius: 6px;
    }

    .badge-soft-success {
        background: #d1e7dd;
        color: #0f5132;
        padding: 6px 10px;
        border-radius: 6px;
    }

    .doctor-free {
        font-size: 11px;
        color: #198754;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }
</style>

<div class="container-fluid py-4" style="direction: rtl; text-align: right;">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold mb-0">
            <i class="bi bi-egg-fried text-primary"></i>
            ثبت وعده غذایی پزشکان
        </h3>

        <div class="d-flex gap-2">
            <a href="manage_doctors.php" class="btn btn-outline-primary btn-sm">
                مدیریت پزشکان
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'saved'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                اطلاعات با موفقیت ثبت شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                رکورد مورد نظر حذف شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-info alert-dismissible fade show">
                تعداد با موفقیت ویرایش شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'confirmed'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                ثبت‌های این روز نهایی شدند و به آمار روزانه منتقل شدند.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'consumer_type_not_found'): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                نوع مصرف‌کننده مربوط به پزشکان در جدول consumer_types پیدا نشد.
                لطفاً یک رکورد با نام «پزشکان» یا «دکتر» در consumer_types ایجاد کنید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- فرم ثبت -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="POST" id="doctorMealForm" class="row g-3 align-items-end">

                <div class="col-md-2">
                    <label class="form-label fw-bold small">تاریخ</label>
                    <input
                        type="text"
                        name="meal_date_shamsi"
                        data-jdp
                        class="form-control"
                        placeholder="انتخاب تاریخ"
                        value="<?= htmlspecialchars($shamsiDate, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="off"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">پزشک</label>
                    <select name="doctor_id" id="doctor_id" class="form-select" required>
                        <option value="">جستجوی پزشک...</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= (int)$doctor['id'] ?>">
                                <?= htmlspecialchars($doctor['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= ((int)$doctor['is_free'] === 1) ? ' - رایگان' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small d-block">وعده‌ها</label>

                    <?php if (!empty($meals)): ?>
                        <?php foreach ($meals as $meal): ?>
                            <div class="form-check form-check-inline mt-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="meals[]"
                                    value="<?= (int)$meal['id'] ?>"
                                    id="meal_<?= (int)$meal['id'] ?>"
                                >
                                <label class="form-check-label" for="meal_<?= (int)$meal['id'] ?>">
                                    <?= htmlspecialchars($meal['meal_name'], ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-danger small">هیچ وعده‌ای تعریف نشده است.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <button type="submit" name="save_doctor_meal" class="btn btn-primary w-100">
                        ثبت
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- فرم تغییر تاریخ نمایش -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">نمایش ثبت‌های تاریخ</label>
                    <input
                        type="text"
                        name="date"
                        data-jdp
                        class="form-control"
                        value="<?= htmlspecialchars($shamsiDate, ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="off"
                    >
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        نمایش
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول لیست -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0">
                لیست ثبت شده: 
                <span class="text-primary"><?= htmlspecialchars($shamsiDate, ENT_QUOTES, 'UTF-8') ?></span>
            </h6>

            <?php if ($doctorConsumerTypeId <= 0): ?>
                <span class="badge bg-danger">
                    نوع مصرف‌کننده پزشکان پیدا نشد
                </span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ردیف</th>
                        <th>پزشک</th>
                        <th>وعده</th>
                        <th style="width: 180px;">تعداد</th>
                        <th>قیمت واحد</th>
                        <th>مبلغ کل</th>
                        <th>وضعیت</th>
                        <th style="width: 120px;">عملیات</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($listResult && $listResult->num_rows > 0): ?>
                        <?php
                        $rowNumber = 0;
                        while ($row = $listResult->fetch_assoc()):
                            $rowNumber++;
                            $quantity = (int)$row['quantity'];
                            $unitPrice = (float)$row['unit_price'];
                            $rowTotal = $quantity * $unitPrice;

                            $totalDayQuantity += $quantity;
                            $totalDayCost += $rowTotal;

                            if ((int)$row['confirmed'] === 0) {
                                $hasUnconfirmed = true;
                            }
                        ?>
                            <tr>
                                <td class="text-muted"><?= $rowNumber ?></td>

                                <td class="fw-bold">
                                    <?= htmlspecialchars($row['doctor_name'], ENT_QUOTES, 'UTF-8') ?>

                                    <?php if ((int)$row['is_free'] === 1): ?>
                                        <div class="doctor-free">رایگان</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['meal_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?php if ((int)$row['confirmed'] === 0): ?>
                                        <form method="POST" class="d-flex gap-2 justify-content-center align-items-center">
                                            <input type="hidden" name="edit_id" value="<?= (int)$row['id'] ?>">

                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= $quantity ?>"
                                                min="1"
                                                class="form-control form-control-sm text-center"
                                                style="width: 80px;"
                                            >

                                            <button type="submit" name="edit_quantity" class="btn btn-success btn-sm">
                                                ذخیره
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?= number_format($quantity) ?>
                                    <?php endif; ?>
                                </td>

                                <td class="text-success">
                                    <?= number_format($unitPrice) ?>
                                </td>

                                <td class="fw-bold text-primary">
                                    <?= number_format($rowTotal) ?>
                                </td>

                                <td>
                                    <?php if ((int)$row['confirmed'] === 0): ?>
                                        <span class="badge-soft-warning">ثبت موقت</span>
                                    <?php else: ?>
                                        <span class="badge-soft-success">تایید شده</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ((int)$row['confirmed'] === 0): ?>
                                        <a
                                            href="doctor_meal_entry.php?delete_id=<?= (int)$row['id'] ?>&date=<?= urlencode($date) ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('آیا از حذف این رکورد مطمئن هستید؟')"
                                        >
                                            حذف
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">قفل شده</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-5 text-muted">
                                برای این تاریخ هنوز وعده‌ای ثبت نشده است.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <?php if ($totalDayQuantity > 0): ?>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end px-4">
                                جمع کل روز
                            </td>
                            <td><?= number_format($totalDayQuantity) ?> پرس</td>
                            <td>-</td>
                            <td class="text-danger"><?= number_format($totalDayCost) ?> ریال</td>
                            <td colspan="2">-</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <?php if ($hasUnconfirmed): ?>
            <div class="card-footer bg-white text-end">
                <form method="POST">
                    <input
                        type="hidden"
                        name="confirm_date_shamsi"
                        value="<?= htmlspecialchars($shamsiDate, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <button
                        type="submit"
                        name="confirm_day"
                        class="btn btn-success"
                        onclick="return confirm('اطلاعات این روز نهایی شود؟ بعد از تایید قابل ویرایش و حذف نیست.')"
                    >
                        تایید نهایی روز و انتقال به آمار
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>

</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#doctor_id').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'جستجوی پزشک...',
            allowClear: true
        });

        $('#doctorMealForm').on('submit', function (e) {
            if (!$('#doctor_id').val()) {
                alert('لطفاً پزشک را انتخاب کنید.');
                e.preventDefault();
                return false;
            }

            if ($('input[name="meals[]"]:checked').length === 0) {
                alert('لطفاً حداقل یک وعده را انتخاب کنید.');
                e.preventDefault();
                return false;
            }
        });
    });

    jalaliDatepicker.startWatch({
        minDate: "attr",
        maxDate: "attr"
    });
</script>

<?php include("../layout/footer.php"); ?>
