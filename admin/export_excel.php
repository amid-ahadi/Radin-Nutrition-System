<?php
include("../config/database.php");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Meal_Analysis_Detailed_Report.xls");

// تابع اصلاح‌شده و دقیق تبدیل تاریخ میلادی به شمسی (حل مشکل تاریخ‌های عجیب و پرت)
function gregorian_to_jalali_local($gy, $gm, $gd) {
    $gy = (int)$gy;
    $gm = (int)$gm;
    $gd = (int)$gd;
    
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 335];
    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }
    
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

function get_meal_type_fa($type) {
    switch ($type) {
        case 'breakfast': return 'صبحانه';
        case 'lunch': return 'ناهار';
        case 'dinner': return 'شام';
        default: return $type;
    }
}

// کپی منطق تبدیل فیلتر تاریخ از صفحه اصلی
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
echo "<tr style='background:#0d6efd; color:#fff; font-weight:bold;'>
        <th>تاریخ پخت (شمسی)</th>
        <th>وعده</th>
        <th>نام غذا</th>
        <th>ماده اولیه</th>
        <th>مقدار مصرفی</th>
      </tr>";

$sql = "SELECT ma.analysis_date, ma.meal_type, ma.meal_name, i.name as ing, mi.quantity 
        FROM meal_analysis_items mi 
        JOIN meal_analysis ma ON mi.analysis_id = ma.id 
        LEFT JOIN ingredients i ON mi.ingredient_id = i.id
        WHERE $where 
        ORDER BY ma.analysis_date DESC";
$res = $conn->query($sql);

while($r = $res->fetch_assoc()) {
    $date_parts = explode('-', $r['analysis_date']);
    // تبدیل به شمسی با تابع اصلاح‌شده جدید
    $shamsi_date = gregorian_to_jalali_local($date_parts[0], $date_parts[1], $date_parts[2]);
    $meal_fa = get_meal_type_fa($r['meal_type']);
    
    echo "<tr>
            <td>{$shamsi_date}</td>
            <td>{$meal_fa}</td>
            <td>{$r['meal_name']}</td>
            <td>{$r['ing']}</td>
            <td>" . number_format($r['quantity'], 2) . "</td>
          </tr>";
}
echo "</table>";
