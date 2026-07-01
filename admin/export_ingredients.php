<?php
include("../config/database.php");

if (ob_get_length()) { @ob_end_clean(); }
@ob_start();

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Ingredients_Consumed_Summary.xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM برای تشخیص UTF-8 در Excel ویندوز
echo "\xEF\xBB\xBF";

// کمک به Excel برای charset
echo "<html><head><meta charset='UTF-8'></head><body>";
// توابع کمکی
function to_en_num($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($arabic, $english, str_replace($persian, $english, $string));
}

function jalali_to_gregorian_local($jy, $jm, $jd) {
    $jy = (int)to_en_num($jy); $jm = (int)to_en_num($jm); $jd = (int)to_en_num($jd);
    $gy = $jy + 621;
    $g_d_m = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) $g_d_m[2] = 29;
    $days = ($jy - 979) * 365 + (int)(($jy - 978) / 33) * 8 + (int)((($jy - 978) % 33) / 4);
    if ($jm <= 6) $days += ($jm - 1) * 31; else $days += 186 + ($jm - 7) * 30;
    $days += $jd;
    $gy = 1600 + (int)($days / 146097) * 400; $days %= 146097;
    if ($days > 36524) { $gy += (int)(--$days / 36524) * 100; $days %= 36524; if ($days >= 365) $days++; }
    $gy += (int)($days / 1461) * 4; $days %= 1461;
    if ($days > 365) { $gy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    for ($gm = 1; $gm < 13; $gm++) { if ($days <= $g_d_m[$gm]) break; $days -= $g_d_m[$gm]; }
    return sprintf('%04d-%02d-%02d', $gy, $gm, $days);
}

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$meal_type = $_GET['meal_type'] ?? '';

$where = "1=1";
if (!empty($date_from) && !empty($date_to)) {
    $df = explode('/', $date_from);
    $dt = explode('/', $date_to);
    if(count($df) == 3 && count($dt) == 3) {
        $g_from = jalali_to_gregorian_local($df[0], $df[1], $df[2]);
        $g_to = jalali_to_gregorian_local($dt[0], $dt[1], $dt[2]);
        $where .= " AND ma.analysis_date BETWEEN '$g_from' AND '$g_to'";
    }
}
if (!empty($meal_type)) {
    $where .= " AND ma.meal_type = '" . $conn->real_escape_string($meal_type) . "'";
}

echo "<table border='1' dir='rtl' style='font-family:Tahoma; text-align:center;'>";
echo "<tr style='background:#ffc107; color:#000; font-weight:bold;'>
        <th>نام ماده اولیه</th>
        <th>مجموع مقدار مصرف شده</th>
        <th>واحد سنجش</th>
      </tr>";

$sql = "SELECT i.name as ing_name, SUM(mi.quantity) as total_qty, u.name as unit_name 
        FROM meal_analysis_items mi 
        JOIN meal_analysis ma ON mi.analysis_id = ma.id 
        LEFT JOIN ingredients i ON mi.ingredient_id = i.id
        LEFT JOIN units u ON i.unit_id = u.id
        WHERE $where 
        GROUP BY mi.ingredient_id
        ORDER BY total_qty DESC";

$res = $conn->query($sql);

if($res) {
    while($r = $res->fetch_assoc()) {
        $unit = !empty($r['unit_name']) ? $r['unit_name'] : 'بدون واحد';
        echo "<tr>
                <td>{$r['ing_name']}</td>
                <td>" . number_format($r['total_qty'], 2) . "</td>
                <td>{$unit}</td>
              </tr>";
    }
}
echo "</table>";
