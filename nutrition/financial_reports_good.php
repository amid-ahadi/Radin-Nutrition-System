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

/**
 * تبدیل صحیح میلادی به شمسی
 * مثال:
 * 2026-03-21 => 1405/01/01
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
 * تبدیل صحیح شمسی به میلادی برای فیلتر گزارش
 * مثال:
 * 1405/01/01 => 2026-03-21
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

    for ($i = 0; $g_day_no >= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0); $i++) {
        $g_day_no -= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0);
    }

    $gm = $i + 1;
    $gd = $g_day_no + 1;

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

$inputStart = $_POST['start_date'] ?? '';
$inputEnd = $_POST['end_date'] ?? '';

$mStart = shamsi_to_miladi($inputStart);
$mEnd = shamsi_to_miladi($inputEnd);

$whereClause = " WHERE 1=1 ";

if (!empty($mStart) && !empty($mEnd)) {
    $whereClause .= " AND ds.stat_date BETWEEN '" . $conn->real_escape_string($mStart) . "' AND '" . $conn->real_escape_string($mEnd) . "' ";
    $reportPeriodText = 'بازه: ' . htmlspecialchars($inputStart) . ' تا ' . htmlspecialchars($inputEnd);
} else {
    $whereClause .= " AND ds.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    $reportPeriodText = 'بازه: ۳۰ روز اخیر';
}

$statsQuery = "
    SELECT 
        IFNULL(SUM(ds.quantity * ds.unit_price), 0) AS total_cost,
        IFNULL(SUM(ds.quantity), 0) AS total_count,
        IFNULL(SUM(ds.quantity * ds.unit_price) / NULLIF(SUM(ds.quantity), 0), 0) AS avg_cost
    FROM daily_statistics ds
    $whereClause
";

$stats = $conn->query($statsQuery)->fetch_assoc();

/**
 * شناسایی ID وعده‌ها برای جلوگیری از مشکل تفاوت حروف فارسی/عربی یا فاصله اضافه
 * مشکل خط فلت ناهار از همین بخش برطرف می‌شود.
 */
$mealTypeMap = [
    'صبحانه' => 0,
    'ناهار' => 0,
    'شام' => 0,
];

$mealTypeRes = $conn->query("SELECT id, meal_name FROM meal_types");

if ($mealTypeRes) {
    while ($meal = $mealTypeRes->fetch_assoc()) {
        $name = trim($meal['meal_name']);

        // نرمال‌سازی حروف عربی به فارسی
        $name = str_replace(['ي', 'ك'], ['ی', 'ک'], $name);

        if ($name === 'صبحانه') {
            $mealTypeMap['صبحانه'] = (int)$meal['id'];
        } elseif ($name === 'ناهار') {
            $mealTypeMap['ناهار'] = (int)$meal['id'];
        } elseif ($name === 'شام') {
            $mealTypeMap['شام'] = (int)$meal['id'];
        }
    }
}

$breakfastId = $mealTypeMap['صبحانه'];
$lunchId = $mealTypeMap['ناهار'];
$dinnerId = $mealTypeMap['شام'];

$dataQuery = "
    SELECT 
        ds.stat_date,
        IFNULL(SUM(ds.quantity * ds.unit_price), 0) AS daily_sum,

        IFNULL(SUM(CASE 
            WHEN ds.meal_type_id = {$breakfastId} THEN ds.quantity 
            ELSE 0 
        END), 0) AS breakfast_count,

        IFNULL(SUM(CASE 
            WHEN ds.meal_type_id = {$lunchId} THEN ds.quantity 
            ELSE 0 
        END), 0) AS lunch_count,

        IFNULL(SUM(CASE 
            WHEN ds.meal_type_id = {$dinnerId} THEN ds.quantity 
            ELSE 0 
        END), 0) AS dinner_count

    FROM daily_statistics ds
    $whereClause
    GROUP BY ds.stat_date
    ORDER BY ds.stat_date ASC
";

$result = $conn->query($dataQuery);

$labels = [];
$dailyCosts = [];
$breakfastCounts = [];
$lunchCounts = [];
$dinnerCounts = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = miladi_to_shamsi($row['stat_date']);
    $dailyCosts[] = (float)$row['daily_sum'];
    $breakfastCounts[] = (int)$row['breakfast_count'];
    $lunchCounts[] = (int)$row['lunch_count'];
    $dinnerCounts[] = (int)$row['dinner_count'];
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
            <form method="POST" class="d-flex gap-2 justify-content-md-end">
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
                    <canvas id="costChart" style="height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">تعداد پرس به تفکیک وعده</h6>
                </div>
                <div class="card-body">
                    <canvas id="countChart" style="height: 320px;"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    jalaliDatepicker.startWatch();

    const chartLabels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    const dailyCosts = <?= json_encode($dailyCosts, JSON_NUMERIC_CHECK) ?>;
    const breakfastCounts = <?= json_encode($breakfastCounts, JSON_NUMERIC_CHECK) ?>;
    const lunchCounts = <?= json_encode($lunchCounts, JSON_NUMERIC_CHECK) ?>;
    const dinnerCounts = <?= json_encode($dinnerCounts, JSON_NUMERIC_CHECK) ?>;

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
                    }
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

    new Chart(document.getElementById('costChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
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

    new Chart(document.getElementById('countChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'صبحانه',
                    data: breakfastCounts,
                    borderColor: '#ffc107',
                    backgroundColor: '#ffc107',
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5
                },
                {
                    label: 'ناهار',
                    data: lunchCounts,
                    borderColor: '#dc3545',
                    backgroundColor: '#dc3545',
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5
                },
                {
                    label: 'شام',
                    data: dinnerCounts,
                    borderColor: '#3f51b5',
                    backgroundColor: '#3f51b5',
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: commonOptions
    });
</script>

<?php include("../layout/footer.php"); ?>
