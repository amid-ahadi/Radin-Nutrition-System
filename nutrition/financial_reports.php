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
 * تبدیل میلادی به شمسی
 */
function miladi_to_shamsi($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }

    $date = substr($date, 0, 10);
    $parts = explode('-', $date);

    if (count($parts) !== 3) {
        return '';
    }

    $gy = (int)$parts[0];
    $gm = (int)$parts[1];
    $gd = (int)$parts[2];

    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy -= 1600;
    $gm -= 1;
    $gd -= 1;

    $g_day_no = 365 * $gy
        + intdiv($gy + 3, 4)
        - intdiv($gy + 99, 100)
        + intdiv($gy + 399, 400);

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

/**
 * تبدیل شمسی به میلادی
 */
function shamsi_to_miladi($date)
{
    if (empty($date)) {
        return '';
    }

    $date = str_replace('-', '/', trim($date));
    $parts = explode('/', $date);

    if (count($parts) !== 3) {
        return '';
    }

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jd <= 0) {
        return '';
    }

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

/**
 * دریافت تاریخ‌های فیلتر
 */
$inputStart = $_POST['start_date'] ?? '';
$inputEnd = $_POST['end_date'] ?? '';

$mStart = shamsi_to_miladi($inputStart);
$mEnd = shamsi_to_miladi($inputEnd);

/**
 * شرط بازه گزارش
 */
$whereClause = " WHERE 1=1 ";

if (!empty($mStart) && !empty($mEnd)) {
    $safeStart = $conn->real_escape_string($mStart);
    $safeEnd = $conn->real_escape_string($mEnd);

    $whereClause .= " AND ds.stat_date BETWEEN '{$safeStart}' AND '{$safeEnd}' ";
    $reportPeriodText = 'بازه: ' . htmlspecialchars($inputStart) . ' تا ' . htmlspecialchars($inputEnd);
} else {
    $whereClause .= " AND ds.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    $reportPeriodText = 'بازه: ۳۰ روز اخیر';
}

/**
 * آمار کلی کارت‌ها
 */
$statsQuery = "
    SELECT 
        IFNULL(SUM(ds.quantity * ds.unit_price), 0) AS total_cost,
        IFNULL(SUM(ds.quantity), 0) AS total_count,
        IFNULL(SUM(ds.quantity * ds.unit_price) / NULLIF(SUM(ds.quantity), 0), 0) AS avg_cost
    FROM daily_statistics ds
    $whereClause
";

$statsResult = $conn->query($statsQuery);

$stats = [
    'total_cost' => 0,
    'total_count' => 0,
    'avg_cost' => 0,
];

if ($statsResult) {
    $stats = $statsResult->fetch_assoc();
}

/**
 * نمودار روند مخارج روزانه
 */
$costQuery = "
    SELECT 
        ds.stat_date,
        IFNULL(SUM(ds.quantity * ds.unit_price), 0) AS daily_sum
    FROM daily_statistics ds
    $whereClause
    GROUP BY ds.stat_date
    ORDER BY ds.stat_date ASC
";

$costResult = $conn->query($costQuery);

$labels = [];
$dailyCosts = [];

if ($costResult) {
    while ($row = $costResult->fetch_assoc()) {
        $labels[] = miladi_to_shamsi($row['stat_date']);
        $dailyCosts[] = (float)$row['daily_sum'];
    }
}

/**
 * نمودار تعداد پرس به تفکیک وعده - داینامیک
 *
 * منطق:
 * daily_statistics.meal_type_id
 * وصل می‌شود به:
 * meal_types.id
 * سپس اسم وعده از:
 * meal_types.meal_name
 * خوانده می‌شود.
 *
 * بنابراین هیچ ID وعده‌ای هاردکد نشده است.
 */
$mealQuery = "
    SELECT 
        ds.stat_date,
        ds.meal_type_id,
        mt.meal_name,
        IFNULL(SUM(ds.quantity), 0) AS total_quantity
    FROM daily_statistics ds
    INNER JOIN meal_types mt ON mt.id = ds.meal_type_id
    $whereClause
    GROUP BY ds.stat_date, ds.meal_type_id, mt.meal_name
    ORDER BY ds.stat_date ASC, ds.meal_type_id ASC
";

$mealResult = $conn->query($mealQuery);

$mealNamesMap = [];
$mealDataByDate = [];
$allMealDates = [];

if ($mealResult) {
    while ($row = $mealResult->fetch_assoc()) {
        $shamsiDate = miladi_to_shamsi($row['stat_date']);

        $mealName = trim((string)$row['meal_name']);
        $mealName = str_replace(['ي', 'ك'], ['ی', 'ک'], $mealName);

        if ($mealName === '') {
            $mealName = 'وعده بدون نام';
        }

        $quantity = (int)$row['total_quantity'];

        $allMealDates[$shamsiDate] = true;
        $mealNamesMap[$mealName] = true;

        if (!isset($mealDataByDate[$shamsiDate])) {
            $mealDataByDate[$shamsiDate] = [];
        }

        $mealDataByDate[$shamsiDate][$mealName] = $quantity;
    }
}

$mealLabels = array_keys($allMealDates);
$mealNames = array_keys($mealNamesMap);

$mealDatasets = [];

$colors = [
    '#ffc107',
    '#dc3545',
    '#3f51b5',
    '#198754',
    '#0dcaf0',
    '#6f42c1',
    '#fd7e14',
    '#20c997',
    '#6610f2',
    '#d63384',
    '#0d6efd',
    '#6c757d',
];

foreach ($mealNames as $index => $mealName) {
    $data = [];

    foreach ($mealLabels as $date) {
        $data[] = $mealDataByDate[$date][$mealName] ?? 0;
    }

    $color = $colors[$index % count($colors)];

    $mealDatasets[] = [
        'label' => $mealName,
        'data' => $data,
        'borderColor' => $color,
        'backgroundColor' => $color,
        'tension' => 0.3,
        'pointRadius' => 3,
        'pointHoverRadius' => 5,
        'fill' => false,
    ];
}

include("../layout/header.php");
?>

<link rel="stylesheet" href="../assets/css/jalalidatepicker.min.css">
<script src="../assets/js/jalalidatepicker.min.js"></script>
<script src="../assets/js/chart.js"></script>

<div class="container-fluid py-4" style="direction: rtl;">

    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3 class="fw-bold">داشبورد مالی و آماری تغذیه</h3>
        </div>

        <div class="col-md-6">
            <form method="POST" class="d-flex gap-2 justify-content-md-end flex-wrap">
                <input 
                    type="text" 
                    name="start_date" 
                    data-jdp 
                    class="form-control form-control-sm w-auto" 
                    placeholder="از تاریخ"
                    value="<?= htmlspecialchars($inputStart) ?>"
                >

                <input 
                    type="text" 
                    name="end_date" 
                    data-jdp 
                    class="form-control form-control-sm w-auto" 
                    placeholder="تا تاریخ"
                    value="<?= htmlspecialchars($inputEnd) ?>"
                >

                <button type="submit" class="btn btn-primary btn-sm px-4">
                    مشاهده گزارش
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">

        <div class="col-md-4">
            <div class="card bg-primary text-white p-3 shadow-sm border-0">
                <small class="opacity-75">جمع کل هزینه‌ها</small>

                <div style="font-size:12px; opacity:.85; margin-top:4px;">
                    <?= $reportPeriodText ?>
                </div>

                <h2 class="fw-bold mb-0 mt-1">
                    <?= number_format((float)($stats['total_cost'] ?? 0)) ?>
                    <span style="font-size:14px">ریال</span>
                </h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-success text-white p-3 shadow-sm border-0">
                <small class="opacity-75">تعداد کل پرس توزیع شده</small>

                <div style="font-size:12px; opacity:.85; margin-top:4px;">
                    <?= $reportPeriodText ?>
                </div>

                <h2 class="fw-bold mb-0 mt-1">
                    <?= number_format((float)($stats['total_count'] ?? 0)) ?>
                </h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-info text-white p-3 shadow-sm border-0">
                <small class="opacity-75">میانگین هزینه هر پرس</small>

                <div style="font-size:12px; opacity:.85; margin-top:4px;">
                    <?= $reportPeriodText ?>
                </div>

                <h2 class="fw-bold mb-0 mt-1">
                    <?= number_format((float)($stats['avg_cost'] ?? 0)) ?>
                    <span style="font-size:14px">ریال</span>
                </h2>
            </div>
        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">روند مخارج روزانه (ریال)</h6>
                </div>

                <div class="card-body">
                    <?php if (empty($labels)): ?>
                        <div class="text-center text-muted py-5">
                            داده‌ای برای نمایش نمودار هزینه وجود ندارد.
                        </div>
                    <?php else: ?>
                        <canvas id="costChart" style="height: 320px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">تعداد پرس به تفکیک وعده</h6>
                </div>

                <div class="card-body">
                    <?php if (empty($mealLabels) || empty($mealDatasets)): ?>
                        <div class="text-center text-muted py-5">
                            داده‌ای برای نمایش نمودار وعده‌ها وجود ندارد.
                        </div>
                    <?php else: ?>
                        <canvas id="countChart" style="height: 320px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    jalaliDatepicker.startWatch();

    const costLabels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    const dailyCosts = <?= json_encode($dailyCosts, JSON_NUMERIC_CHECK) ?>;

    const mealLabels = <?= json_encode($mealLabels, JSON_UNESCAPED_UNICODE) ?>;
    const mealDatasets = <?= json_encode($mealDatasets, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) ?>;

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Tahoma',
                        size: 12
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                bodyFont: {
                    family: 'Tahoma'
                },
                titleFont: {
                    family: 'Tahoma'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    font: {
                        family: 'Tahoma',
                        size: 10
                    }
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Tahoma',
                        size: 10
                    }
                }
            }
        }
    };

    if (document.getElementById('costChart')) {
        new Chart(document.getElementById('costChart'), {
            type: 'line',
            data: {
                labels: costLabels,
                datasets: [
                    {
                        label: 'هزینه کل (ریال)',
                        data: dailyCosts,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: commonOptions
        });
    }

    if (document.getElementById('countChart')) {
        new Chart(document.getElementById('countChart'), {
            type: 'line',
            data: {
                labels: mealLabels,
                datasets: mealDatasets
            },
            options: commonOptions
        });
    }
</script>

<?php include("../layout/footer.php"); ?>
