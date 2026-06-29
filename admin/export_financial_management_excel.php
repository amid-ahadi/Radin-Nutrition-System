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

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=management_financial_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

function shamsi_to_miladi_export($date) {
    if (empty($date)) return '';

    $date = str_replace('-', '/', trim($date));
    $parts = explode('/', $date);

    if (count($parts) !== 3) return '';

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jd <= 0) return '';

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

    for (
        $i = 0;
        $i < 12 && $g_day_no >= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0);
        $i++
    ) {
        $g_day_no -= $g_days_in_month[$i] + (($i === 1 && $leap) ? 1 : 0);
    }

    $gm = $i + 1;
    $gd = $g_day_no + 1;

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

function miladi_to_shamsi_export($date) {
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

/*
|--------------------------------------------------------------------------
| دریافت وعده‌ها به صورت داینامیک
|--------------------------------------------------------------------------
*/
$mealTypes = [];

$mealQuery = "
    SELECT id, meal_name
    FROM meal_types
    ORDER BY id ASC
";

$mealResult = $conn->query($mealQuery);

if ($mealResult && $mealResult->num_rows > 0) {
    while ($meal = $mealResult->fetch_assoc()) {
        if (!empty($mealTypeId) && (int)$meal['id'] !== (int)$mealTypeId) {
            continue;
        }

        $mealTypes[] = [
            'id' => (int)$meal['id'],
            'name' => $meal['meal_name']
        ];
    }
}

/*
|--------------------------------------------------------------------------
| گزارش تجمیعی بر اساس نوع مصرف‌کننده و وعده
|--------------------------------------------------------------------------
*/
$query = "
    SELECT
        ct.id AS consumer_type_id,
        ct.type_name AS consumer_type_name,
        mt.id AS meal_type_id,
        mt.meal_name,
        SUM(ds.quantity) AS total_quantity,
        SUM(ds.quantity * ds.unit_price) AS total_cost
    FROM daily_statistics ds
    LEFT JOIN consumer_types ct ON ct.id = ds.consumer_type_id
    LEFT JOIN meal_types mt ON mt.id = ds.meal_type_id
    $whereSQL
    GROUP BY ct.id, ct.type_name, mt.id, mt.meal_name
    ORDER BY ct.id ASC, mt.id ASC
";

$result = $conn->query($query);

/*
|--------------------------------------------------------------------------
| ساخت آرایه مدیریتی
|--------------------------------------------------------------------------
*/
$reportData = [];

$grandMealTotals = [];
$grandQuantity = 0;
$grandCost = 0;

foreach ($mealTypes as $meal) {
    $grandMealTotals[$meal['id']] = 0;
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consumerId = (int)($row['consumer_type_id'] ?? 0);
        $consumerName = $row['consumer_type_name'] ?? 'نامشخص';

        $mealId = (int)($row['meal_type_id'] ?? 0);
        $quantity = (int)($row['total_quantity'] ?? 0);
        $cost = (float)($row['total_cost'] ?? 0);

        if (!isset($reportData[$consumerId])) {
            $reportData[$consumerId] = [
                'consumer_name' => $consumerName,
                'meals' => [],
                'total_quantity' => 0,
                'total_cost' => 0
            ];

            foreach ($mealTypes as $meal) {
                $reportData[$consumerId]['meals'][$meal['id']] = 0;
            }
        }

        if (isset($reportData[$consumerId]['meals'][$mealId])) {
            $reportData[$consumerId]['meals'][$mealId] += $quantity;
            $grandMealTotals[$mealId] += $quantity;
        }

        $reportData[$consumerId]['total_quantity'] += $quantity;
        $reportData[$consumerId]['total_cost'] += $cost;

        $grandQuantity += $quantity;
        $grandCost += $cost;
    }
}

$reportTitle = "گزارش مدیریتی تجمیعی وعده‌ها بر اساس نوع مصرف‌کننده";

$periodText = "همه بازه‌ها";

if (!empty($startDate) && !empty($endDate)) {
    $periodText = "از تاریخ " . htmlspecialchars($startDate) . " تا " . htmlspecialchars($endDate);
} elseif (!empty($startDate)) {
    $periodText = "از تاریخ " . htmlspecialchars($startDate);
} elseif (!empty($endDate)) {
    $periodText = "تا تاریخ " . htmlspecialchars($endDate);
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background-color: #d9ead3;
            font-weight: bold;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            mso-number-format: "\@";
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            background-color: #cfe2f3;
        }

        .period {
            background-color: #fff2cc;
            font-weight: bold;
        }

        .total-row {
            background-color: #f4cccc;
            font-weight: bold;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="<?= count($mealTypes) + 4 ?>" class="title">
            <?= $reportTitle ?>
        </td>
    </tr>

    <tr>
        <td colspan="<?= count($mealTypes) + 4 ?>" class="period">
            بازه گزارش: <?= $periodText ?>
        </td>
    </tr>

    <tr>
        <th>ردیف</th>
        <th>نوع مصرف‌کننده</th>

        <?php foreach ($mealTypes as $meal): ?>
            <th><?= htmlspecialchars($meal['name']) ?></th>
        <?php endforeach; ?>

        <th>جمع کل پرس</th>
        <th>جمع کل هزینه</th>
    </tr>

    <?php if (!empty($reportData)): ?>
        <?php
        $index = 1;
        foreach ($reportData as $consumer):
        ?>
            <tr>
                <td><?= $index++ ?></td>
                <td><?= htmlspecialchars($consumer['consumer_name']) ?></td>

                <?php foreach ($mealTypes as $meal): ?>
                    <td><?= number_format($consumer['meals'][$meal['id']] ?? 0) ?></td>
                <?php endforeach; ?>

                <td><?= number_format($consumer['total_quantity']) ?></td>
                <td><?= number_format($consumer['total_cost']) ?></td>
            </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="2">جمع کل</td>

            <?php foreach ($mealTypes as $meal): ?>
                <td><?= number_format($grandMealTotals[$meal['id']] ?? 0) ?></td>
            <?php endforeach; ?>

            <td><?= number_format($grandQuantity) ?></td>
            <td><?= number_format($grandCost) ?></td>
        </tr>

    <?php else: ?>
        <tr>
            <td colspan="<?= count($mealTypes) + 4 ?>">
                داده‌ای برای این بازه یافت نشد.
            </td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>
