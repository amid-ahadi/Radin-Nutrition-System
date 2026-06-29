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
    for ($i = 0; $i < $gm; $i++) { $g_day_no += $g_days_in_month[$i]; }
    if ($gm > 1 && (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0))) { $g_day_no++; }
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = intdiv($j_day_no, 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * intdiv($j_day_no, 1461);
    $j_day_no %= 1461;
    if ($j_day_no >= 366) { $jy += intdiv($j_day_no - 1, 365); $j_day_no = ($j_day_no - 1) % 365; }
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; $i++) { $j_day_no -= $j_days_in_month[$i]; }
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
    for ($i = 0; $i < $jm; $i++) { $j_day_no += $j_days_in_month[$i]; }
    $j_day_no += $jd;
    $g_day_no = $j_day_no + 79;
    $gy = 1600 + 400 * intdiv($g_day_no, 146097);
    $g_day_no %= 146097;
    $leap = true;
    if ($g_day_no >= 36525) { $g_day_no--; $gy += 100 * intdiv($g_day_no, 36524); $g_day_no %= 36524; if ($g_day_no >= 365) $g_day_no++; else $leap = false; }
    $gy += 4 * intdiv($g_day_no, 1461);
    $g_day_no %= 1461;
    if ($g_day_no >= 366) { $leap = false; $g_day_no--; $gy += intdiv($g_day_no, 365); $g_day_no %= 365; }
    for ($i = 0; $i < 12 && $g_day_no >= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0); $i++) { $g_day_no -= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0); }
    $gm = $i + 1; $gd = $g_day_no + 1;
    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$mealTypeId = $_GET['meal_type_id'] ?? '';
$consumerTypeId = $_GET['consumer_type_id'] ?? '';

$mStart = shamsi_to_miladi_export($startDate);
$mEnd = shamsi_to_miladi_export($endDate);

$whereConditions = ["1=1"];
if (!empty($mStart)) { $whereConditions[] = "ds.stat_date >= '" . $conn->real_escape_string($mStart) . "'"; }
if (!empty($mEnd)) { $whereConditions[] = "ds.stat_date <= '" . $conn->real_escape_string($mEnd) . "'"; }
if (!empty($mealTypeId)) { $whereConditions[] = "ds.meal_type_id = " . (int)$mealTypeId; }
if (!empty($consumerTypeId)) { $whereConditions[] = "ds.consumer_type_id = " . (int)$consumerTypeId; }

$whereSQL = "WHERE " . implode(" AND ", $whereConditions);
$reportQuery = "SELECT ds.stat_date, mt.meal_name, ct.type_name, ds.quantity, ds.unit_price, (ds.quantity * ds.unit_price) AS total_price, ds.recorded_at FROM daily_statistics ds LEFT JOIN meal_types mt ON mt.id = ds.meal_type_id LEFT JOIN consumer_types ct ON ct.id = ds.consumer_type_id $whereSQL ORDER BY ds.stat_date DESC, ds.id DESC";
$reportResult = $conn->query($reportQuery);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش مالی تغذیه</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #666; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print();">چاپ سند / ذخیره به صورت PDF</button>
    </div>
    <div class="header"><h2>گزارش جامع آماری و مالی واحد تغذیه</h2></div>
    <table>
        <thead>
            <tr><th>ردیف</th><th>تاریخ مصرف</th><th>وعده</th><th>نوع مصرف‌کننده</th><th>تعداد</th><th>قیمت واحد</th><th>مبلغ کل</th></tr>
        </thead>
        <tbody>
            <?php 
            $totalQuantity = 0; $totalCost = 0; $rowCount = 0;
            if ($reportResult && $reportResult->num_rows > 0): 
                while ($row = $reportResult->fetch_assoc()): 
                    $rowCount++;
                    $totalQuantity += $row['quantity'];
                    $totalCost += $row['total_price'];
            ?>
                <tr>
                    <td><?= $rowCount ?></td>
                    <td><?= miladi_to_shamsi_export($row['stat_date']) ?></td>
                    <td><?= htmlspecialchars($row['meal_name'] ?? 'نامشخص') ?></td>
                    <td><?= htmlspecialchars($row['type_name'] ?? 'نامشخص') ?></td>
                    <td><?= number_format($row['quantity']) ?></td>
                    <td><?= number_format($row['unit_price']) ?></td>
                    <td><?= number_format($row['total_price']) ?></td>
                </tr>
            <?php 
                endwhile; 
            else:
            ?>
                <tr><td colspan="7">هیچ داده‌ای یافت نشد.</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if (isset($rowCount) && $rowCount > 0): ?>
        <tr style="font-weight:bold; background-color:#eee;">
            <td colspan="4">جمع کل</td>
            <td><?= number_format($totalQuantity) ?></td>
            <td>-</td>
            <td><?= number_format($totalCost) ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <script>window.onload = function() { setTimeout(function() { window.print(); }, 1000); };</script>
</body>
</html>
