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
 * تابع تبدیل تاریخ میلادی به شمسی برای فایل خروجی
 */
function miladi_to_shamsi_export($date) {
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
    $jm = $i + 1; $jd = $j_day_no + 1;

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function shamsi_to_miladi_export($date) {
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

// فیلترها
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$mealTypeId = $_GET['meal_type_id'] ?? '';
$consumerTypeId = $_GET['consumer_type_id'] ?? '';

$mStart = shamsi_to_miladi_export($startDate);
$mEnd = shamsi_to_miladi_export($endDate);

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

$reportQuery = "
    SELECT 
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
";
$reportResult = $conn->query($reportQuery);

// تنظیم هدرهای مرورگر برای دانلود فایل اکسل در فرمت xls
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="nutrition_financial_report_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// اضافه کردن UTF-8 BOM جهت رفع مشکل به هم ریختگی زبان فارسی در اکسل
echo "\xEF\xBB\xBF";
?>
<table border="1" style="direction: rtl; text-align: right; font-family: Tahoma; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th style="padding: 10px; width: 50px;">ردیف</th>
            <th style="padding: 10px; width: 120px;">تاریخ مصرف</th>
            <th style="padding: 10px; width: 120px;">وعده غذایی</th>
            <th style="padding: 10px; width: 150px;">نوع مصرف کننده</th>
            <th style="padding: 10px; width: 100px;">تعداد (پرس)</th>
            <th style="padding: 10px; width: 120px;">قیمت واحد (ریال)</th>
            <th style="padding: 10px; width: 150px;">مبلغ کل (ریال)</th>
            <th style="padding: 10px; width: 180px;">زمان ثبت</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $totalQuantity = 0;
        $totalCost = 0;
        $rowCount = 0;

        if ($reportResult && $reportResult->num_rows > 0): 
            while ($row = $reportResult->fetch_assoc()): 
                $rowCount++;
                $totalQuantity += $row['quantity'];
                $totalCost += $row['total_price'];
        ?>
            <tr>
                <td style="padding: 8px; text-align: center;"><?= $rowCount ?></td>
                <td style="padding: 8px; text-align: center;"><?= miladi_to_shamsi_export($row['stat_date']) ?></td>
                <td style="padding: 8px; text-align: center;"><?= htmlspecialchars($row['meal_name'] ?? 'نامشخص') ?></td>
                <td style="padding: 8px; text-align: center;"><?= htmlspecialchars($row['type_name'] ?? 'نامشخص') ?></td>
                <td style="padding: 8px; text-align: center; mso-number-format:'\#\,\#\#0';"><?= $row['quantity'] ?></td>
                <td style="padding: 8px; text-align: center; mso-number-format:'\#\,\#\#0';"><?= $row['unit_price'] ?></td>
                <td style="padding: 8px; text-align: center; mso-number-format:'\#\,\#\#0'; font-weight: bold;"><?= $row['total_price'] ?></td>
                <td style="padding: 8px; text-align: center; direction: ltr;"><?= htmlspecialchars($row['recorded_at']) ?></td>
            </tr>
        <?php 
            endwhile; 
        endif; 
        ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #e6e6e6; font-weight: bold;">
            <td colspan="4" style="padding: 10px; text-align: left;">جمع کل فیلترها:</td>
            <td style="padding: 10px; text-align: center; mso-number-format:'\#\,\#\#0'; color: red;"><?= $totalQuantity ?></td>
            <td style="padding: 10px; text-align: center;">-</td>
            <td style="padding: 10px; text-align: center; mso-number-format:'\#\,\#\#0'; color: red;"><?= $totalCost ?></td>
            <td style="padding: 10px; text-align: center;">-</td>
        </tr>
    </tfoot>
</table>
