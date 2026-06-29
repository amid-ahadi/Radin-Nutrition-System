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
include_once("../config/jdf.php");

allowRoles(['admin', 'nutrition_manager', 'staff']);

$msg = "";


// =========================
//   تبدیل اعداد فارسی/عربی به انگلیسی
// =========================
function fa_digits_to_en($string) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $string = str_replace($persian, $english, $string);
    $string = str_replace($arabic, $english, $string);

    return $string;
}


// =========================
//   تبدیل تاریخ شمسی به میلادی
// =========================
function custom_jalali_to_gregorian($jy, $jm, $jd) {
    $jy = (int)$jy;
    $jm = (int)$jm;
    $jd = (int)$jd;

    $jy += 1595;

    $days = -355668 + (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4) + $jd;

    if ($jm < 7) {
        $days += ($jm - 1) * 31;
    } else {
        $days += (($jm - 7) * 30) + 186;
    }

    $gy = 400 * (int)($days / 146097);
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

    $sal_a = [
        0,
        31,
        (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28,
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31
    ];

    for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
        $gd -= $sal_a[$gm];
    }

    return sprintf("%04d-%02d-%02d", $gy, $gm, $gd);
}


// =========================
//   آماده‌سازی تاریخ انتخاب‌شده
// =========================
function get_selected_date() {
    if (!empty($_GET['shamsi_date'])) {
        $shamsi = fa_digits_to_en(trim($_GET['shamsi_date']));
        $shamsi = str_replace('-', '/', $shamsi);

        $parts = explode('/', $shamsi);

        if (count($parts) === 3) {
            return custom_jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
        }
    }

    if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
        return $_GET['date'];
    }

    return date('Y-m-d');
}

$selected_date = get_selected_date();
$is_edit_mode = isset($_GET['edit']);


// =========================
//   2) بررسی قفل بودن روز
// =========================
$check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM daily_statistics WHERE stat_date = ?");
$check_stmt->bind_param("s", $selected_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$is_locked = ($check_result['total'] > 0 && !$is_edit_mode);


// =========================
//   3) پردازش فرم + ذخیره
// =========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$is_locked) {

    $date = $_POST['stat_date'];
    $conn->begin_transaction();

    try {

        $price_map = [];
        $price_res = $conn->query("SELECT meal_type_id, consumer_type_id, price FROM food_prices");
        while($p = $price_res->fetch_assoc()){
            $price_map[$p['meal_type_id']][$p['consumer_type_id']] = $p['price'];
        }

        $stmt = $conn->prepare("
            INSERT INTO daily_statistics
            (stat_date, meal_type_id, consumer_type_id, quantity, unit_price)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = VALUES(quantity),
                unit_price = VALUES(unit_price)
        ");

        foreach ($_POST['stats'] as $meal_id => $consumers) {
            foreach ($consumers as $consumer_id => $qty) {
                $qty = intval($qty);
                $price = $price_map[$meal_id][$consumer_id] ?? 0;

                $stmt->bind_param("siiii", $date, $meal_id, $consumer_id, $qty, $price);
                $stmt->execute();
            }
        }

        $conn->commit();
        header("Location: daily_stats.php?date=".$date);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger'>".$e->getMessage()."</div>";
    }
}


// =========================
//   4) لود هدر و داده‌ها
// =========================
include("../layout/header.php");

$meals = $conn->query("SELECT id, meal_name FROM meal_types ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$consumers = $conn->query("SELECT id, type_name FROM consumer_types ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$existing_stats = [];
$stats_res = $conn->prepare("SELECT meal_type_id, consumer_type_id, quantity FROM daily_statistics WHERE stat_date = ?");
$stats_res->bind_param("s", $selected_date);
$stats_res->execute();
$res = $stats_res->get_result();

while ($row = $res->fetch_assoc()){
    $existing_stats[$row['meal_type_id']][$row['consumer_type_id']] = $row['quantity'];
}

function toShamsi($date){
    return jdate("Y/m/d", strtotime($date));
}
?>

<link rel="stylesheet" href="../assets/css/jalalidatepicker.min.css">

<style>
.locked-overlay{
    position:absolute;
    background:rgba(255,255,255,0.85);
    width:100%;
    height:100%;
    z-index:10;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    font-weight:bold;
    color:#dc3545;
}
.table-container{position:relative;}
</style>

<div class="container-fluid py-4">
<div class="card shadow border-0">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

    <div>
        ثبت آمار روزانه — <?= toShamsi($selected_date); ?>
    </div>

    <div class="d-flex gap-2 align-items-center">

        <form method="GET" class="d-flex gap-2 align-items-center mb-0">
            <input
                type="text"
                data-jdp
                data-jdp-only-date
                name="shamsi_date"
                id="shamsi_picker"
                value="<?= toShamsi($selected_date); ?>"
                class="form-control form-control-sm text-center"
                style="cursor:pointer; width:130px;"
                readonly
                autocomplete="off"
                placeholder="انتخاب تاریخ"
            >

            <?php if($is_edit_mode): ?>
                <input type="hidden" name="edit" value="1">
            <?php endif; ?>

            <button type="submit" class="btn btn-light btn-sm">نمایش</button>
        </form>

        <?php if($is_locked): ?>
        <button class="btn btn-warning btn-sm" onclick="unlockEdit()">
            <i class="fas fa-edit"></i> ویرایش
        </button>
        <?php endif; ?>

    </div>
</div>

<div class="card-body table-container">

<?php if($is_locked): ?>
<div class="locked-overlay">
    🔒 این روز ثبت نهایی شده است
</div>
<?php endif; ?>

<?php if($msg) echo $msg; ?>

<form method="POST">
<input type="hidden" name="stat_date" value="<?=$selected_date?>">

<table class="table table-bordered text-center align-middle">
<thead class="table-light">
<tr>
    <th>وعده غذایی</th>
    <?php foreach($consumers as $c): ?>
        <th><?=$c['type_name']?></th>
    <?php endforeach; ?>
</tr>
</thead>

<tbody>
<?php foreach($meals as $row_idx => $m): ?>
<tr>
    <td class="fw-bold bg-light"><?=$m['meal_name']?></td>

    <?php foreach($consumers as $col_idx => $c):
        $val = $existing_stats[$m['id']][$c['id']] ?? 0;
        $tab = ($col_idx * count($meals)) + ($row_idx + 1);
    ?>
    <td>
        <input
            type="number"
            min="0"
            name="stats[<?=$m['id']?>][<?=$c['id']?>]"
            value="<?=$val?>"
            tabindex="<?=$tab?>"
            data-row="<?=$row_idx?>"
            data-col="<?=$col_idx?>"
            class="form-control text-center stat-input"
            onfocus="this.select()"
            <?=$is_locked?'disabled':''?>
        >
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if(!$is_locked): ?>
<div class="text-end mt-3">
    <button class="btn btn-success px-5">
        <i class="fas fa-save"></i> ذخیره نهایی
    </button>
</div>
<?php endif; ?>

</form>

</div>
</div>
</div>

<script src="../assets/js/sweetalert2@11.js"></script>
<script src="../assets/js/jalalidatepicker.min.js"></script>

<script>
jalaliDatepicker.startWatch({
    separatorChar: "/",
    autoShow: true,
    autoHide: true
});

function unlockEdit(){
    Swal.fire({
        title: 'فعال‌سازی حالت ویرایش',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: 'بله، ویرایش فعال شود',
        cancelButtonText: 'لغو'
    }).then((result)=>{
        if(result.isConfirmed){
            window.location = '?date=<?=$selected_date?>&edit=1';
        }
    });
}

document.querySelectorAll('.stat-input').forEach(inp=>{
    inp.addEventListener('keydown', function(e){
        let r = parseInt(this.dataset.row);
        let c = parseInt(this.dataset.col);
        let target;

        if(e.key==="ArrowDown") target = document.querySelector(`[data-row="${r+1}"][data-col="${c}"]`);
        if(e.key==="ArrowUp") target = document.querySelector(`[data-row="${r-1}"][data-col="${c}"]`);
        if(e.key==="ArrowLeft") target = document.querySelector(`[data-row="${r}"][data-col="${c+1}"]`);
        if(e.key==="ArrowRight") target = document.querySelector(`[data-row="${r}"][data-col="${c-1}"]`);

        if(target) {
            e.preventDefault();
            target.focus();
        }
    });
});
</script>

<?php include("../layout/footer.php"); ?>
