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

if (file_exists("../config/jdf.php")) {
    include_once("../config/jdf.php");
}

allowRoles(['admin', 'ward_secretary']);

$msg = '';

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
        return '';
    }

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jm > 12 || $jd <= 0 || $jd > 31) {
        return '';
    }

    if (function_exists('jalali_to_gregorian')) {
        $gregorian = jalali_to_gregorian($jy, $jm, $jd, '-');

        if (is_array($gregorian)) {
            return sprintf("%04d-%02d-%02d", $gregorian[0], $gregorian[1], $gregorian[2]);
        }

        return $gregorian;
    }

    return jalali_to_gregorian_internal($jy, $jm, $jd);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_request'])) {
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $request_date_shamsi = trim($_POST['request_date_shamsi'] ?? '');
    $request_date = shamsi_to_miladi_custom($request_date_shamsi);
    $meals = $_POST['meals'] ?? [];
    $requested_by = (int)($_SESSION['user_id'] ?? 0);

    if ($doctor_id <= 0 || empty($request_date) || empty($meals)) {
        $msg = "<div class='alert alert-danger'>لطفاً پزشک، تاریخ و حداقل یک وعده را انتخاب کنید.</div>";
    } else {
        $conn->begin_transaction();

        try {
            $doctorConsumerTypeId = 17;

            $is_free = 0;
            $stmtDoctor = $conn->prepare("SELECT is_free FROM doctors WHERE id = ? LIMIT 1");
            $stmtDoctor->bind_param("i", $doctor_id);
            $stmtDoctor->execute();
            $doctorRow = $stmtDoctor->get_result()->fetch_assoc();

            if ($doctorRow) {
                $is_free = (int)$doctorRow['is_free'];
            }

            $stmtPrice = $conn->prepare("
                SELECT price
                FROM food_prices
                WHERE meal_type_id = ? AND consumer_type_id = ?
                LIMIT 1
            ");

            $stmtInsert = $conn->prepare("
                INSERT INTO doctor_meal_requests
                (doctor_id, meal_type_id, request_date, quantity, unit_price, total_price, requested_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $insertedCount = 0;

            foreach ($meals as $meal_id => $qty) {
                $meal_id = (int)$meal_id;
                $qty = (int)$qty;

                if ($meal_id <= 0 || $qty <= 0) {
                    continue;
                }

                $unit_price = 0;

                if ($is_free !== 1) {
                    $stmtPrice->bind_param("ii", $meal_id, $doctorConsumerTypeId);
                    $stmtPrice->execute();
                    $priceRow = $stmtPrice->get_result()->fetch_assoc();

                    if ($priceRow) {
                        $unit_price = (float)$priceRow['price'];
                    }
                }

                $total_price = $unit_price * $qty;

                $stmtInsert->bind_param(
                    "iisiddi",
                    $doctor_id,
                    $meal_id,
                    $request_date,
                    $qty,
                    $unit_price,
                    $total_price,
                    $requested_by
                );

                $stmtInsert->execute();
                $insertedCount++;
            }

            if ($insertedCount === 0) {
                throw new Exception("حداقل تعداد یک وعده باید بیشتر از صفر باشد.");
            }

            $conn->commit();
            $msg = "<div class='alert alert-success'>درخواست غذا با موفقیت ثبت شد.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert alert-danger'>خطا: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

$doctors = $conn->query("
    SELECT id, doctor_name, is_free
    FROM doctors
    ORDER BY doctor_name ASC
")->fetch_all(MYSQLI_ASSOC);

$meals = $conn->query("
    SELECT id, meal_name
    FROM meal_types
    ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);

include("../layout/header.php");
?>

<link href="../assets/css/select2.min.css" rel="stylesheet">
<link href="../assets/css/persian-datepicker.min.css" rel="stylesheet">

<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: .375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>

<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            ثبت درخواست غذای پزشک
        </div>

        <div class="card-body">
            <?= $msg ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">پزشک</label>
                        <select name="doctor_id" class="form-select doctor-select" required>
                            <option value="">جستجو و انتخاب پزشک</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?= (int)$doctor['id'] ?>">
                                    <?= htmlspecialchars($doctor['doctor_name']) ?>
                                    <?= ((int)$doctor['is_free'] === 1) ? ' - رایگان' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاریخ درخواست</label>
                        <input
                            type="text"
                            id="request_date_shamsi"
                            name="request_date_shamsi"
                            class="form-control"
                            placeholder="مثلاً 1405/04/02"
                            autocomplete="off"
                            required
                        >
                    </div>
                </div>

                <hr>

                <div class="row">
                    <?php foreach ($meals as $meal): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">
                                <?= htmlspecialchars($meal['meal_name']) ?>
                            </label>
                            <input
                                type="number"
                                min="0"
                                name="meals[<?= (int)$meal['id'] ?>]"
                                class="form-control"
                                value="0"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-end">
                    <button type="submit" name="save_request" class="btn btn-success">
                        ثبت درخواست
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/select2.min.js"></script>
<script src="../assets/js/persian-date.min.js"></script>
<script src="../assets/js/persian-datepicker.js"></script>

<script>
$(document).ready(function () {
    $('.doctor-select').select2({
        dir: 'rtl',
        width: '100%',
        placeholder: 'جستجو و انتخاب پزشک',
        allowClear: true,
        language: {
            noResults: function () {
                return 'پزشکی پیدا نشد';
            },
            searching: function () {
                return 'در حال جستجو...';
            }
        }
    });

    $('#request_date_shamsi').persianDatepicker({
        format: 'YYYY/MM/DD',
        initialValue: false,
        autoClose: true,
        observer: true,
        calendar: {
            persian: {
                locale: 'fa'
            }
        }
    });
});
</script>

<?php include("../layout/footer.php"); ?>
