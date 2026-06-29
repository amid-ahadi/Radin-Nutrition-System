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

/**
 * توابع تبدیل تاریخ شمسی و میلادی
 */
function miladi_to_shamsi($date) {
    if (empty($date) || $date === '0000-00-00') return '';
    $date = substr($date, 0, 10);
    $parts = explode('-', $date);
    if (count($parts) !== 3) return '';
    
    $gy = (int)$parts[0]; $gm = (int)$parts[1]; $gd = (int)$parts[2];
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy -= 1600; $gm -= 1; $gd -= 1;
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
    $date = str_replace('-', '/', trim($date));
    $parts = explode('/', $date);
    if (count($parts) !== 3) return '';

    $jy = (int)$parts[0]; $jm = (int)$parts[1]; $jd = (int)$parts[2];
    if ($jy <= 0 || $jm <= 0 || $jd <= 0) return '';

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $jy -= 979; $jm -= 1; $jd -= 1;
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
        if ($g_day_no >= 365) $g_day_no++; else $leap = false;
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
    $gm = $i + 1; $gd = $g_day_no + 1;

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

// مقداردهی اولیه فیلترها
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$mealTypeId = $_GET['meal_type_id'] ?? '';
$consumerTypeId = $_GET['consumer_type_id'] ?? '';

// تبدیل تاریخ‌ها جهت اعمال در کوئری دیتابیس
$mStart = shamsi_to_miladi($startDate);
$mEnd = shamsi_to_miladi($endDate);

// ساخت بندهای WHERE به صورت پویا
$whereConditions = ["1=1"];

if (!empty($mStart)) {
    $whereConditions[] = "ds.stat_date >= '" . $conn->real_escape_string($mStart) . "'";
}
if (!empty($mEnd)) {
    $whereConditions[] = "ds.stat_date <= '" . $conn->real_escape_string($mEnd) . "'";
}
if (!empty($mealTypeId)) {
    $whereConditions[] = "ds.meal_type_id = " . (int)$mealTypeId;
}
if (!empty($consumerTypeId)) {
    $whereConditions[] = "ds.consumer_type_id = " . (int)$consumerTypeId;
}

$whereSQL = "WHERE " . implode(" AND ", $whereConditions);
// تنظیمات صفحه‌بندی
$perPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($currentPage < 1) {
    $currentPage = 1;
}

$offset = ($currentPage - 1) * $perPage;

// شمارش کل رکوردهای منطبق با فیلترها
$countQuery = "
    SELECT COUNT(*) AS total_records
    FROM daily_statistics ds
    LEFT JOIN meal_types mt ON mt.id = ds.meal_type_id
    LEFT JOIN consumer_types ct ON ct.id = ds.consumer_type_id
    $whereSQL
";

$countResult = $conn->query($countQuery);
$totalRecords = 0;

if ($countResult && $countRow = $countResult->fetch_assoc()) {
    $totalRecords = (int)$countRow['total_records'];
}

$totalPages = (int)ceil($totalRecords / $perPage);

if ($totalPages > 0 && $currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $perPage;
}


// کوئری دریافت اطلاعات گزارش
$reportQuery = "
    SELECT 
        ds.id,
        ds.stat_date,
        mt.meal_name,
        ct.type_name,
        ds.quantity,
        ds.unit_price,
        (ds.quantity * ds.unit_price) AS total_price,
        ds.recorded_at
    FROM daily_statistics ds
    LEFT JOIN meal_types mt ON mt.id = ds.meal_type_id
    LEFT JOIN consumer_types ct ON ct.id = ds.consumer_type_id
    $whereSQL
    ORDER BY ds.stat_date DESC, ds.id DESC
    LIMIT $perPage OFFSET $offset
";

$reportResult = $conn->query($reportQuery);
// جمع کل همه رکوردهای منطبق با فیلترها، نه فقط صفحه فعلی
$summaryQuery = "
    SELECT 
        COALESCE(SUM(ds.quantity), 0) AS total_quantity,
        COALESCE(SUM(ds.quantity * ds.unit_price), 0) AS total_cost
    FROM daily_statistics ds
    LEFT JOIN meal_types mt ON mt.id = ds.meal_type_id
    LEFT JOIN consumer_types ct ON ct.id = ds.consumer_type_id
    $whereSQL
";

$summaryResult = $conn->query($summaryQuery);

$filteredTotalQuantity = 0;
$filteredTotalCost = 0;

if ($summaryResult && $summaryRow = $summaryResult->fetch_assoc()) {
    $filteredTotalQuantity = (int)$summaryRow['total_quantity'];
    $filteredTotalCost = (float)$summaryRow['total_cost'];
}


// دریافت لیست وعده‌ها برای منوی کشویی
$mealsResult = $conn->query("SELECT id, meal_name FROM meal_types ORDER BY id ASC");
$meals = [];
if ($mealsResult) {
    while ($m = $mealsResult->fetch_assoc()) {
        $meals[] = $m;
    }
}

// دریافت لیست مصرف‌کنندگان برای منوی کشویی
$consumersResult = $conn->query("SELECT id, type_name FROM consumer_types ORDER BY id ASC");
$consumers = [];
if ($consumersResult) {
    while ($c = $consumersResult->fetch_assoc()) {
        $consumers[] = $c;
    }
}

// متغیرهای ساخت لینک خروجی‌ها به همراه فیلترهای فعال
$queryString = http_build_query([
    'start_date' => $startDate,
    'end_date' => $endDate,
    'meal_type_id' => $mealTypeId,
    'consumer_type_id' => $consumerTypeId
]);

include("../layout/header.php");
?>

<link rel="stylesheet" href="../assets/css/jalalidatepicker.min.css">
<script src="../assets/js/jalalidatepicker.min.js"></script>

<div class="container-fluid py-4" style="direction: rtl; text-align: right;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold mb-0">گزارش جدولی مصارف و هزینه‌ها</h3>
        <a href="financial_reports.php" class="btn btn-outline-secondary btn-sm">بازگشت به داشبورد مالی</a>
    </div>

    <!-- فرم فیلترها -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">از تاریخ</label>
                    <input type="text" name="start_date" data-jdp class="form-control form-control-sm" placeholder="انتخاب تاریخ" value="<?= htmlspecialchars($startDate) ?>" autocomplete="off">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small fw-bold">تا تاریخ</label>
                    <input type="text" name="end_date" data-jdp class="form-control form-control-sm" placeholder="انتخاب تاریخ" value="<?= htmlspecialchars($endDate) ?>" autocomplete="off">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">وعده غذایی</label>
                    <select name="meal_type_id" class="form-select form-select-sm">
                        <option value="">همه وعده‌ها</option>
                        <?php foreach ($meals as $meal): ?>
                            <option value="<?= $meal['id'] ?>" <?= $mealTypeId == $meal['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($meal['meal_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">نوع مصرف‌کننده</label>
                    <select name="consumer_type_id" class="form-select form-select-sm">
                        <option value="">همه گروه‌ها</option>
                        <?php foreach ($consumers as $consumer): ?>
                            <option value="<?= $consumer['id'] ?>" <?= $consumerTypeId == $consumer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($consumer['type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2">
                        <i class="bi bi-filter"></i> فیلتر
                    </button>
                    <a href="financial_report_table.php" class="btn btn-light btn-sm w-100 py-2 border">پاک کردن</a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول گزارش -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-0">
            <h6 class="fw-bold mb-0 text-secondary">لیست داده‌های مالی منطبق بر فیلترها</h6>
            
            <div class="d-flex gap-2">
                <a href="export_financial_excel.php?<?= $queryString ?>" target="_blank" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> خروجی اکسل
                </a>
				<a 
					href="export_financial_management_excel.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&meal_type_id=<?= urlencode($mealTypeId) ?>&consumer_type_id=<?= urlencode($consumerTypeId) ?>"
					class="btn btn-success">
					خروجی اکسل مدیریتی
				</a>
                <a href="export_financial_pdf.php?<?= $queryString ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> چاپ / خروجی PDF
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ردیف</th>
                            <th>تاریخ مصرف</th>
                            <th>وعده غذایی</th>
                            <th>نوع مصرف‌کننده</th>
                            <th>تعداد (پرس)</th>
                            <th>قیمت واحد (ریال)</th>
                            <th>مبلغ کل (ریال)</th>
                            <th>زمان ثبت در سیستم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalQuantity = 0;
                        $totalCost = 0;
                        $rowCount = $offset;

                        if ($reportResult && $reportResult->num_rows > 0): 
                            while ($row = $reportResult->fetch_assoc()): 
                                $rowCount++;
                                $totalQuantity += $row['quantity'];
                                $totalCost += $row['total_price'];
                        ?>
                            <tr>
                                <td class="text-muted"><?= $rowCount ?></td>
                                <td class="fw-bold"><?= htmlspecialchars(miladi_to_shamsi($row['stat_date'])) ?></td>
                                <td><span class="badge bg-secondary opacity-75 py-2 px-3"><?= htmlspecialchars($row['meal_name'] ?? 'نامشخص') ?></span></td>
                                <td><span class="badge bg-light text-dark border py-2 px-3"><?= htmlspecialchars($row['type_name'] ?? 'نامشخص') ?></span></td>
                                <td class="fw-bold"><?= number_format($row['quantity']) ?></td>
                                <td class="text-success"><?= number_format($row['unit_price']) ?></td>
                                <td class="fw-bold text-primary"><?= number_format($row['total_price']) ?></td>
                                <td class="text-muted small" style="direction: ltr;"><?= htmlspecialchars($row['recorded_at']) ?></td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    داده‌ای منطبق با فیلترهای انتخابی شما یافت نشد.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
						<?php if ($totalRecords > 0): ?>
							<tfoot class="table-light border-top">
								<tr class="fw-bold fs-6">
									<td colspan="4" class="text-end py-3 px-4">
										جمع کل فیلترهای جاری:
										<span class="text-muted small">
											تعداد رکوردها: <?= number_format($totalRecords) ?>
										</span>
									</td>
									<td class="text-danger py-3"><?= number_format($filteredTotalQuantity) ?> پرس</td>
									<td>-</td>
									<td class="text-danger py-3"><?= number_format($filteredTotalCost) ?> ریال</td>
									<td>-</td>
								</tr>
							</tfoot>
						<?php endif; ?>

                </table>
				<?php if ($totalPages > 1): ?>
    <?php
    $paginationParams = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'meal_type_id' => $mealTypeId,
        'consumer_type_id' => $consumerTypeId
    ];

    $visiblePages = 5;
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $startPage + $visiblePages - 1);

    if (($endPage - $startPage + 1) < $visiblePages) {
        $startPage = max(1, $endPage - $visiblePages + 1);
    }
    ?>

    <div class="p-3 border-top bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

            <div class="text-muted small">
                نمایش رکوردهای
                <?= number_format($offset + 1) ?>
                تا
                <?= number_format(min($offset + $perPage, $totalRecords)) ?>
                از
                <?= number_format($totalRecords) ?>
                رکورد
            </div>

            <nav>
                <ul class="pagination pagination-sm mb-0">

                    <?php
                    $paginationParams['page'] = 1;
                    ?>
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($paginationParams) ?>">
                            ابتدا
                        </a>
                    </li>

                    <?php
                    $paginationParams['page'] = max(1, $currentPage - 1);
                    ?>
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($paginationParams) ?>">
                            قبلی
                        </a>
                    </li>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <?php
                        $paginationParams['page'] = $p;
                        ?>
                        <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($paginationParams) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php
                    $paginationParams['page'] = min($totalPages, $currentPage + 1);
                    ?>
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($paginationParams) ?>">
                            بعدی
                        </a>
                    </li>

                    <?php
                    $paginationParams['page'] = $totalPages;
                    ?>
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($paginationParams) ?>">
                            انتها
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
<?php endif; ?>


            </div>
        </div>
    </div>
</div>

<script>
    jalaliDatepicker.startWatch({
        minDate: "attr",
        maxDate: "attr"
    });
</script>

<?php include("../layout/footer.php"); ?>
