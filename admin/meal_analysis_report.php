<?php
include("../config/database.php");
include("../auth/role_check.php");

if (file_exists("../config/jdf.php")) {
    include_once("../config/jdf.php");
}

// --- اضافه کردن تابع تبدیل اعداد ---
function to_en_num($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $string = str_replace($persian, $english, $string);
    return str_replace($arabic, $english, $string);
}
// --- پایان اضافه کردن تابع تبدیل اعداد ---

// --- اضافه کردن توابع تبدیل تاریخ ---
function convert_persian_digits_to_english($value) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $value = str_replace($persian, $english, $value);
    return str_replace($arabic, $english, $value);
}

function jalali_to_gregorian_internal($jy, $jm, $jd) {
    $jy = (int)$jy;
    $jm = (int)$jm;
    $jd = (int)$jd;

    $gy = ($jy <= 979) ? 621 : 1600;
    $jy -= ($jy <= 979) ? 0 : 979;

    $days = (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4) + 78 + $jd;

    if ($jm < 7) {
        $days += ($jm - 1) * 31;
    } else {
        $days += (($jm - 7) * 30) + 186;
    }

    $gy += 400 * (int)($days / 146097);
    $days %= 146097;

    if ($days > 36524) {
        $gy += 100 * (int)(--$days / 36524);
        $days %= 36524;

        if ($days >= 365) {
            $days++;
        }
    }

    $gy += 4 * (int)($days / 1461);
    $days %= 1461;

    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }

    $gd = $days + 1;
    $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
        $gd -= $sal_a[$gm];
    }

    return sprintf("%04d-%02d-%02d", $gy, $gm, $gd);
}

function shamsi_to_miladi_custom($date) {
    $date = trim(convert_persian_digits_to_english($date));
    $date = str_replace('-', '/', $date);

    $parts = explode('/', $date);
    if (count($parts) !== 3) {
        return null; // تاریخ نامعتبر
    }

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jm > 12 || $jd <= 0 || $jd > 31) {
        return null; // تاریخ نامعتبر
    }

    if (function_exists('jalali_to_gregorian')) {
        $gregorian = jalali_to_gregorian($jy, $jm, $jd, '-');
        if (is_array($gregorian)) {
            return sprintf("%04d-%02d-%02d", $gregorian[0], $gregorian[1], $gregorian[2]);
        }
        // اگر خروجی آرایه نبود، ممکن است رشته باشد، ولی احتمال کمتری دارد
        // برای اطمینان، به تابع داخلی می‌رویم
    }

    return jalali_to_gregorian_internal($jy, $jm, $jd);
}
// --- پایان اضافه کردن توابع ---

// تابع تبدیل میلادی به شمسی (این یکی را نگه می‌داریم چون مشکلی ندارد)
if (!function_exists('gregorian_to_jalali_local')) {
    function gregorian_to_jalali_local($gy, $gm, $gd) {
        $g_d_m = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gy = (int)$gy - 1600; $gm = (int)$gm - 1; $gd = (int)$gd - 1;
        $g_day_no = 365 * $gy + (int)(($gy + 3) / 4) - (int)(($gy + 99) / 100) + (int)(($gy + 399) / 400);
        for ($i = 0; $i < $gm; ++$i) $g_day_no += $g_d_m[$i + 1];
        if ($gm > 1 && (($gy + 1600) % 4 == 0 && (($gy + 1600) % 100 != 0 || ($gy + 1600) % 400 == 0))) $g_day_no++;
        $g_day_no += $gd;
        $j_day_no = $g_day_no - 79;
        $j_np = (int)($j_day_no / 12053); $j_day_no %= 12053;
        $jy = 979 + 33 * $j_np + 4 * (int)($j_day_no / 1461); $j_day_no %= 1461;
        if ($j_day_no >= 366) { $jy += (int)(($j_day_no - 1) / 365); $j_day_no = ($j_day_no - 1) % 365; }
        $j_month_days = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        for ($i = 0; $i < 12 && $j_day_no >= $j_month_days[$i]; $i++) $j_day_no -= $j_month_days[$i];
        return sprintf('%04d/%02d/%02d', $jy, $i + 1, $j_day_no + 1);
    }
}

allowRoles(['admin', 'nutrition_manager', 'staff']);

// پارامترهای فیلتر و صفحه‌بندی
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$meal_type = $_GET['meal_type'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";

// فیلتر تاریخ - استفاده از تابع جدید
if (!empty($date_from) && !empty($date_to)) {
    // تبدیل تاریخ‌های شمسی به میلادی با استفاده از تابع جدید
    $g_from = shamsi_to_miladi_custom($date_from);
    $g_to = shamsi_to_miladi_custom($date_to);

    // بررسی معتبر بودن تاریخ‌های تبدیل شده قبل از اضافه کردن به کوئری
    if ($g_from !== null && $g_to !== null) {
        // استفاده از $conn->real_escape_string برای جلوگیری از SQL Injection
        $g_from_escaped = $conn->real_escape_string($g_from);
        $g_to_escaped = $conn->real_escape_string($g_to);
        $where .= " AND ma.analysis_date BETWEEN '$g_from_escaped' AND '$g_to_escaped'";
    }
    // else: اگر تاریخ نامعتبر است، فقط شرط اضافه نمی‌شود. می‌توانید پیام خطا نیز نمایش دهید.
}

// فیلتر وعده
if (!empty($meal_type)) {
    $where .= " AND ma.meal_type = '" . $conn->real_escape_string($meal_type) . "'";
}

// ۱. لیست داده‌های جدول اصلی با صفحه‌بندی
$sql = "SELECT ma.* FROM meal_analysis ma WHERE $where ORDER BY ma.analysis_date DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// ۲. تعداد کل برای صفحه‌بندی
$total_res = $conn->query("SELECT COUNT(*) as cnt FROM meal_analysis ma WHERE $where");
$total_rows = $total_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $limit);

// ۳. مجموع برای نمودار و جدول خلاصه (محدود به ۱۰ قلم برتر مصرف شده)
$summary = [];
$chart_labels = [];
$chart_data = [];
$sum_sql = "SELECT i.name, SUM(mi.quantity) as total, u.name as unit_name 
            FROM meal_analysis_items mi 
            JOIN meal_analysis ma ON mi.analysis_id = ma.id
            LEFT JOIN ingredients i ON mi.ingredient_id = i.id 
            LEFT JOIN units u ON i.unit_id = u.id
            WHERE $where 
            GROUP BY mi.ingredient_id
            ORDER BY total DESC 
            LIMIT 10"; // محدود به حداکثر ۱۰ مورد برتر
            
$sum_res = $conn->query($sum_sql);
if($sum_res) {
    while($s = $sum_res->fetch_assoc()){
        $summary[] = $s;
        $chart_labels[] = $s['name'];
        $chart_data[] = $s['total'];
    }
}

include("../layout/header.php");
?>

<link href="../assets/css/persian-datepicker.min.css" rel="stylesheet">

<div class="container-fluid py-4" dir="rtl">
    <div class="row text-end">
        <!-- پنل فیلتر -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">فیلتر گزارش آنالیز</div>
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">از تاریخ:</label>
                            <input type="text" name="date_from" id="date_from" class="form-control" value="<?=$date_from?>" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تا تاریخ:</label>
                            <input type="text" name="date_to" id="date_to" class="form-control" value="<?=$date_to?>" autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">وعده غذایی:</label>
                            <select name="meal_type" class="form-select">
                                <option value="">همه وعده‌ها</option>
                                <option value="breakfast" <?=$meal_type == 'breakfast' ? 'selected' : ''?>>صبحانه</option>
                                <option value="lunch" <?=$meal_type == 'lunch' ? 'selected' : ''?>>ناهار</option>
                                <option value="dinner" <?=$meal_type == 'dinner' ? 'selected' : ''?>>شام</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-start">
                            <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                            <a href="meal_analysis_report.php" class="btn btn-secondary">حذف فیلتر</a>
                            <a href="export_excel.php?date_from=<?=$date_from?>&date_to=<?=$date_to?>&meal_type=<?=$meal_type?>" class="btn btn-success">خروجی اکسل جزئیات</a>
                            <a href="export_ingredients.php?date_from=<?=$date_from?>&date_to=<?=$date_to?>&meal_type=<?=$meal_type?>" class="btn btn-warning text-dark fw-bold">خروجی مصرف مواد اولیه</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- مجموع مقادیر و نمودار -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">مجموع مقادیر مصرف شده (۱۰ قلم برتر)</div>
                <div class="card-body">
                    <canvas id="myChart" style="max-height: 250px;"></canvas>
                    <table class="table table-sm table-bordered mt-3 text-center">
                        <thead class="table-light"><tr><th>ماده</th><th>مقدار کل</th><th>واحد</th></tr></thead>
                        <tbody>
                            <?php if(empty($summary)): ?>
                                <tr><td colspan="3">داده‌ای یافت نشد</td></tr>
                            <?php else: foreach($summary as $row): ?>
                                <tr><td><?=$row['name']?></td><td><strong><?=number_format($row['total'], 2)?></strong></td><td><?=$row['unit_name']?></td></tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- جدول لیست آنالیزها -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">لیست آنالیزها</div>
                <div class="card-body">
                    <table class="table table-hover table-bordered text-center">
                        <thead class="table-light"><tr><th>ردیف</th><th>تاریخ ثبت</th><th>نام غذا</th><th>عملیات</th></tr></thead>
                        <tbody>
                            <?php 
                            $idx = $offset + 1; 
                            while($r = $result->fetch_assoc()): 
                                // استخراج مستقیم تاریخ از دیتابیس بدون تبدیل به تایم‌استمپ (روش ایمن)
                                $d_parts = explode('-', $r['analysis_date']); 
                                $shamsi_date = (count($d_parts) == 3) ? gregorian_to_jalali_local($d_parts[0], $d_parts[1], $d_parts[2]) : "نامشخص";
                            ?>
                            <tr>
                                <td><?=$idx++?></td>
                                <td><?=$shamsi_date?></td>
                                <td><?=$r['meal_name']?></td>
                                <td><button class="btn btn-sm btn-primary" onclick="viewItems(<?=$r['id']?>)">جزئیات</button></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- صفحه‌بندی -->
                    <?php if($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?=$i==$page?'active':''?>">
                                <a class="page-link" href="?page=<?=$i?>&date_from=<?=$date_from?>&date_to=<?=$date_to?>&meal_type=<?=$meal_type?>"><?=$i?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="detModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content" id="detContent"></div></div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/persian-date.min.js"></script>
<script src="../assets/js/persian-datepicker.js"></script>
<script src="../assets/js/chart.js"></script>

<script>
$(document).ready(function () {
    $('#date_from, #date_to').persianDatepicker({
        format: 'YYYY/MM/DD',
        initialValue: false,
        autoClose: true,
        calendar: { persian: { locale: 'fa' } }
    });

    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'مقدار مصرف',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: '#0dcaf0'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
});

function viewItems(id) {
    $.get('get_meal_details.php?id=' + id, function(data) {
        $('#detContent').html(data);
        var myModal = new bootstrap.Modal(document.getElementById('detModal'));
        myModal.show();
    });
}
</script>

<?php include("../layout/footer.php"); ?>