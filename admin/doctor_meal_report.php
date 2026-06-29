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

allowRoles(['admin', 'nutrition_manager' , 'finance']);

/*
|--------------------------------------------------------------------------
| توابع تاریخ و اعداد
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| دریافت فیلترها
|--------------------------------------------------------------------------
*/

$todayMiladi = date("Y-m-d");
$todayShamsi = miladi_to_shamsi($todayMiladi);

$fromDateShamsi = $_GET['from_date'] ?? $todayShamsi;
$toDateShamsi   = $_GET['to_date'] ?? $todayShamsi;

$fromDate = shamsi_to_miladi($fromDateShamsi);
$toDate   = shamsi_to_miladi($toDateShamsi);

if (empty($fromDate)) {
    $fromDate = $todayMiladi;
    $fromDateShamsi = $todayShamsi;
}

if (empty($toDate)) {
    $toDate = $todayMiladi;
    $toDateShamsi = $todayShamsi;
}

$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$freeStatus = $_GET['free_status'] ?? 'all'; 
$confirmStatus = $_GET['confirm_status'] ?? 'all';

$reportType = $_GET['report_type'] ?? 'detail';

/*
|--------------------------------------------------------------------------
| دریافت پزشکان برای فیلتر
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
    while ($row = $doctorResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| ساخت شرط‌های گزارش
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];
$types = "";

$where[] = "dm.meal_date BETWEEN ? AND ?";
$params[] = $fromDate;
$params[] = $toDate;
$types .= "ss";

if ($doctorId > 0) {
    $where[] = "dm.doctor_id = ?";
    $params[] = $doctorId;
    $types .= "i";
}

if ($freeStatus === 'free') {
    $where[] = "d.is_free = 1";
} elseif ($freeStatus === 'paid') {
    $where[] = "d.is_free = 0";
}

if ($confirmStatus === 'confirmed') {
    $where[] = "dm.confirmed = 1";
} elseif ($confirmStatus === 'unconfirmed') {
    $where[] = "dm.confirmed = 0";
}

$whereSql = implode(" AND ", $where);

/*
|--------------------------------------------------------------------------
| خروجی اکسل ساده
|--------------------------------------------------------------------------
*/

$isExcel = isset($_GET['export']) && $_GET['export'] === 'excel';

if ($isExcel) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=doctor_meal_report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
}

/*
|--------------------------------------------------------------------------
| کوئری گزارش
|--------------------------------------------------------------------------
*/

if ($reportType === 'summary_doctor') {

    $sql = "
        SELECT 
            d.id AS doctor_id,
            d.doctor_name,
            d.is_free,
            SUM(dm.quantity) AS total_quantity,
            SUM(dm.quantity * dm.unit_price) AS total_amount,
            COUNT(dm.id) AS record_count
        FROM doctor_meals dm
        INNER JOIN doctors d ON d.id = dm.doctor_id
        INNER JOIN meal_types mt ON mt.id = dm.meal_type_id
        WHERE $whereSql
        GROUP BY d.id, d.doctor_name, d.is_free
        ORDER BY d.doctor_name ASC
    ";

} elseif ($reportType === 'summary_meal') {

    $sql = "
        SELECT 
            mt.id AS meal_type_id,
            mt.meal_name,
            SUM(dm.quantity) AS total_quantity,
            SUM(dm.quantity * dm.unit_price) AS total_amount,
            COUNT(dm.id) AS record_count
        FROM doctor_meals dm
        INNER JOIN doctors d ON d.id = dm.doctor_id
        INNER JOIN meal_types mt ON mt.id = dm.meal_type_id
        WHERE $whereSql
        GROUP BY mt.id, mt.meal_name
        ORDER BY mt.id ASC
    ";

} else {

    $sql = "
        SELECT 
            dm.id,
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
        WHERE $whereSql
        ORDER BY dm.meal_date DESC, d.doctor_name ASC, mt.id ASC
    ";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$reportResult = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| اگر اکسل نیست، هدر سایت لود شود
|--------------------------------------------------------------------------
*/

if (!$isExcel) {
    include("../layout/header.php");
}
?>

<?php if (!$isExcel): ?>
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
        text-align: right;
        padding-right: 12px;
        padding-left: 25px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        left: 8px;
        right: auto;
        height: 36px;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
        }
    }
</style>
<?php endif; ?>

<div class="container-fluid py-4" style="direction: rtl; text-align: right;">

    <?php if (!$isExcel): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
            <h3 class="fw-bold mb-0">
                <i class="bi bi-file-earmark-text text-primary"></i>
                گزارش وعده‌های غذایی پزشکان
            </h3>

            <div class="d-flex gap-2">
                <a href="doctor_meal_entry.php" class="btn btn-outline-primary btn-sm">
                    ثبت غذای پزشکان
                </a>

                <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
                    چاپ گزارش
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">از تاریخ</label>
                        <input 
                            type="text" 
                            name="from_date" 
                            data-jdp
                            class="form-control"
                            value="<?= htmlspecialchars($fromDateShamsi, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">تا تاریخ</label>
                        <input 
                            type="text" 
                            name="to_date" 
                            data-jdp
                            class="form-control"
                            value="<?= htmlspecialchars($toDateShamsi, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="off"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">پزشک</label>
                        <select name="doctor_id" id="doctor_id" class="form-select">
                            <option value="0">همه پزشکان</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option 
                                    value="<?= (int)$doctor['id'] ?>"
                                    <?= ($doctorId === (int)$doctor['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($doctor['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= ((int)$doctor['is_free'] === 1) ? ' - رایگان' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">نوع پزشک</label>
                        <select name="free_status" class="form-select">
                            <option value="all" <?= ($freeStatus === 'all') ? 'selected' : '' ?>>همه</option>
                            <option value="free" <?= ($freeStatus === 'free') ? 'selected' : '' ?>>رایگان</option>
                            <option value="paid" <?= ($freeStatus === 'paid') ? 'selected' : '' ?>>غیر رایگان</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">وضعیت تایید</label>
                        <select name="confirm_status" class="form-select">
                            <option value="all" <?= ($confirmStatus === 'all') ? 'selected' : '' ?>>همه</option>
                            <option value="confirmed" <?= ($confirmStatus === 'confirmed') ? 'selected' : '' ?>>تایید شده</option>
                            <option value="unconfirmed" <?= ($confirmStatus === 'unconfirmed') ? 'selected' : '' ?>>تایید نشده</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">نوع گزارش</label>
                        <select name="report_type" class="form-select">
                            <option value="detail" <?= ($reportType === 'detail') ? 'selected' : '' ?>>
                                گزارش ریز
                            </option>
                            <option value="summary_doctor" <?= ($reportType === 'summary_doctor') ? 'selected' : '' ?>>
                                خلاصه به تفکیک پزشک
                            </option>
                            <option value="summary_meal" <?= ($reportType === 'summary_meal') ? 'selected' : '' ?>>
                                خلاصه به تفکیک وعده
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            نمایش گزارش
                        </button>
                    </div>

                    <div class="col-md-2">
                        <?php
                        $excelParams = $_GET;
                        $excelParams['export'] = 'excel';
                        $excelUrl = "doctor_meal_report.php?" . http_build_query($excelParams);
                        ?>
                        <a href="<?= htmlspecialchars($excelUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success w-100">
                            خروجی اکسل
                        </a>
                    </div>

                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-1">
                گزارش وعده‌های غذایی پزشکان
            </h5>

            <div class="text-muted small">
                بازه گزارش:
                <?= htmlspecialchars($fromDateShamsi, ENT_QUOTES, 'UTF-8') ?>
                تا
                <?= htmlspecialchars($toDateShamsi, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="table-responsive">
            <?php if ($reportType === 'summary_doctor'): ?>

                <table class="table table-bordered table-hover text-center align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>نام پزشک</th>
                            <th>نوع پزشک</th>
                            <th>تعداد رکورد</th>
                            <th>جمع تعداد پرس</th>
                            <th>جمع مبلغ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $i = 0;
                        $grandQuantity = 0;
                        $grandAmount = 0;
                        $grandRecords = 0;
                        ?>

                        <?php if ($reportResult && $reportResult->num_rows > 0): ?>
                            <?php while ($row = $reportResult->fetch_assoc()): ?>
                                <?php
                                $i++;
                                $quantity = (int)$row['total_quantity'];
                                $amount = (float)$row['total_amount'];
                                $records = (int)$row['record_count'];

                                $grandQuantity += $quantity;
                                $grandAmount += $amount;
                                $grandRecords += $records;
                                ?>

                                <tr>
                                    <td><?= $i ?></td>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($row['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$row['is_free'] === 1): ?>
                                            <span class="badge bg-success">رایگان</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غیر رایگان</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($records) ?></td>
                                    <td><?= number_format($quantity) ?></td>
                                    <td class="text-primary fw-bold"><?= number_format($amount) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-5 text-muted">
                                    اطلاعاتی برای این بازه یافت نشد.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if ($i > 0): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3">جمع کل</td>
                                <td><?= number_format($grandRecords) ?></td>
                                <td><?= number_format($grandQuantity) ?></td>
                                <td class="text-danger"><?= number_format($grandAmount) ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>

            <?php elseif ($reportType === 'summary_meal'): ?>

                <table class="table table-bordered table-hover text-center align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>وعده غذایی</th>
                            <th>تعداد رکورد</th>
                            <th>جمع تعداد پرس</th>
                            <th>جمع مبلغ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $i = 0;
                        $grandQuantity = 0;
                        $grandAmount = 0;
                        $grandRecords = 0;
                        ?>

                        <?php if ($reportResult && $reportResult->num_rows > 0): ?>
                            <?php while ($row = $reportResult->fetch_assoc()): ?>
                                <?php
                                $i++;
                                $quantity = (int)$row['total_quantity'];
                                $amount = (float)$row['total_amount'];
                                $records = (int)$row['record_count'];

                                $grandQuantity += $quantity;
                                $grandAmount += $amount;
                                $grandRecords += $records;
                                ?>

                                <tr>
                                    <td><?= $i ?></td>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($row['meal_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= number_format($records) ?></td>
                                    <td><?= number_format($quantity) ?></td>
                                    <td class="text-primary fw-bold"><?= number_format($amount) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-5 text-muted">
                                    اطلاعاتی برای این بازه یافت نشد.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if ($i > 0): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">جمع کل</td>
                                <td><?= number_format($grandRecords) ?></td>
                                <td><?= number_format($grandQuantity) ?></td>
                                <td class="text-danger"><?= number_format($grandAmount) ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>

            <?php else: ?>

                <table class="table table-bordered table-hover text-center align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>تاریخ</th>
                            <th>پزشک</th>
                            <th>نوع پزشک</th>
                            <th>وعده</th>
                            <th>تعداد</th>
                            <th>قیمت واحد</th>
                            <th>مبلغ کل</th>
                            <th>وضعیت تایید</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $i = 0;
                        $grandQuantity = 0;
                        $grandAmount = 0;
                        ?>

                        <?php if ($reportResult && $reportResult->num_rows > 0): ?>
                            <?php while ($row = $reportResult->fetch_assoc()): ?>
                                <?php
                                $i++;
                                $quantity = (int)$row['quantity'];
                                $unitPrice = (float)$row['unit_price'];
                                $amount = $quantity * $unitPrice;

                                $grandQuantity += $quantity;
                                $grandAmount += $amount;
                                ?>

                                <tr>
                                    <td><?= $i ?></td>

                                    <td>
                                        <?= htmlspecialchars(miladi_to_shamsi($row['meal_date']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="fw-bold">
                                        <?= htmlspecialchars($row['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$row['is_free'] === 1): ?>
                                            <span class="badge bg-success">رایگان</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غیر رایگان</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['meal_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= number_format($quantity) ?>
                                    </td>

                                    <td>
                                        <?= number_format($unitPrice) ?>
                                    </td>

                                    <td class="text-primary fw-bold">
                                        <?= number_format($amount) ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$row['confirmed'] === 1): ?>
                                            <span class="badge bg-success">تایید شده</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">تایید نشده</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-5 text-muted">
                                    اطلاعاتی برای این بازه یافت نشد.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if ($i > 0): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="5">جمع کل</td>
                                <td><?= number_format($grandQuantity) ?></td>
                                <td>-</td>
                                <td class="text-danger"><?= number_format($grandAmount) ?></td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>

            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!$isExcel): ?>
<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#doctor_id').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'انتخاب پزشک',
            allowClear: false
        });
    });

    jalaliDatepicker.startWatch({
        minDate: "attr",
        maxDate: "attr"
    });
</script>

<?php include("../layout/footer.php"); ?>
<?php endif; ?>
