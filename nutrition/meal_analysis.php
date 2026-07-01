<?php
include("../config/database.php");
include("../auth/role_check.php");

if (file_exists("../config/jdf.php")) {
    include_once("../config/jdf.php");
}

// --- اضافه کردن توابع تبدیل تاریخ از کد قبلی ---
function convert_persian_digits_to_english($value) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $value = str_replace($persian, $english, $value);
    return str_replace($arabic, $english, $value);
}

// تابع داخلی تبدیل شمسی به میلادی - همان منطقی که در کد قبلی کار می‌کرد
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

// تابع اصلی تبدیل شمسی به میلادی - همان منطق کد قبلی
function shamsi_to_miladi_custom($date) {
    $date = trim(convert_persian_digits_to_english($date));
    $date = str_replace('-', '/', $date); // تبدیل خط تیره به اسلش

    $parts = explode('/', $date);
    if (count($parts) !== 3) {
        // اگر فرمت اشتباه باشد، خطا پرتاب کنیم تا در catch گرفته شود
        throw new Exception("فرمت تاریخ وارد شده صحیح نیست. باید به صورت YYYY/MM/DD یا YYYY-MM-DD باشد. دریافت شده: '$date'");
    }

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy <= 0 || $jm <= 0 || $jm > 12 || $jd <= 0 || $jd > 31) {
        throw new Exception("تاریخ شمسی وارد شده نامعتبر است. (سال: $jy, ماه: $jm, روز: $jd)");
    }

    // سعی می‌کنیم از تابع موجود در jdf.php یا سایر کتابخانه‌ها استفاده کنیم
    if (function_exists('jalali_to_gregorian')) {
        $gregorian = jalali_to_gregorian($jy, $jm, $jd, '-'); // jdf.php ممکن است این تابع را داشته باشد
        if (is_array($gregorian)) {
            return sprintf("%04d-%02d-%02d", $gregorian[0], $gregorian[1], $gregorian[2]);
        }
        // اگر خروجی آرایه نبود، ممکن است رشته باشد، ولی احتمال کمتری دارد
        // اگر فرمت خروجی متفاوت بود، باید اصلاح شود
        // برای اطمینان، به تابع داخلی می‌رویم
    }

    // اگر تابع خارجی وجود نداشت یا کار نکرد، از تابع داخلی استفاده می‌کنیم
    return jalali_to_gregorian_internal($jy, $jm, $jd);
}
// --- پایان اضافه کردن توابع ---

allowRoles(['admin', 'nutrition_manager', 'staff']);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_analysis'])) {

    $analysis_date_shamsi = $_POST['analysis_date']; // e.g., "1405/04/02" (Persian or Arabic digits)
    $meal_type = $_POST['meal_type'];
    $meal_name = trim($_POST['meal_name']);
    $user_id = $_SESSION['user_id'] ?? 1;

    if (!empty($analysis_date_shamsi) && !empty($meal_type) && !empty($meal_name)) {

        $conn->begin_transaction();

        try {
            // تبدیل تاریخ شمسی به میلادی با استفاده از تابع مشابه کد قبلی
            $analysis_date_gregorian = shamsi_to_miladi_custom($analysis_date_shamsi);

            // --- Debugging: Remove after testing ---
            error_log("Input Jalali: $analysis_date_shamsi -> Converted Gregorian: $analysis_date_gregorian");
            // --- End Debugging ---

            $stmt = $conn->prepare("
                INSERT INTO meal_analysis
                (analysis_date, meal_type, meal_name, created_by)
                VALUES (?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("خطا در آماده‌سازی تراکنش اصلی: " . $conn->error);
            }

            $stmt->bind_param("sssi", $analysis_date_gregorian, $meal_type, $meal_name, $user_id);
            $stmt->execute();

            $analysis_id = $stmt->insert_id;
            $stmt->close();

            if (isset($_POST['ingredient_id']) && is_array($_POST['ingredient_id'])) {

                $ingredient_ids = $_POST['ingredient_id'];
                $quantities = $_POST['quantity'];

                $stmt_item = $conn->prepare("
                    INSERT INTO meal_analysis_items
                    (analysis_id, ingredient_id, quantity)
                    VALUES (?, ?, ?)
                ");
                if (!$stmt_item) {
                    throw new Exception("خطا در آماده‌سازی تراکنش جزییات: " . $conn->error);
                }

                foreach ($ingredient_ids as $index => $ing_id) {
                    $qty = floatval(convert_persian_digits_to_english($quantities[$index])); // استفاده از همان تابع تبدیل اعداد
                    $ing_id = intval($ing_id);

                    if ($ing_id > 0 && $qty > 0) {
                        $stmt_item->bind_param("iid", $analysis_id, $ing_id, $qty);
                        $stmt_item->execute();
                    }
                }
                $stmt_item->close();
            }

            $conn->commit();
            $msg = "<div class='alert alert-success'>آنالیز غذا با موفقیت ثبت شد.</div>";

        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert alert-danger'>خطا در ثبت: " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("Meal Analysis Error: " . $e->getMessage()); // Log error for debugging
        }

    } else {
        $msg = "<div class='alert alert-danger'>لطفاً تمام اطلاعات الزامی را وارد کنید.</div>";
    }
}

// خواندن مواد اولیه به همراه نام واحد
$query_ingredients = "
    SELECT i.id, i.name, IFNULL(u.name, 'بدون واحد') AS unit_name
    FROM ingredients i
    LEFT JOIN units u ON i.unit_id = u.id
    ORDER BY i.name ASC
";
$result_ingredients = $conn->query($query_ingredients);

if (!$result_ingredients) {
    die("<div class='alert alert-danger'>خطا در دریافت لیست مواد اولیه از دیتابیس: " . htmlspecialchars($conn->error) . "</div>");
}

$ingredients = $result_ingredients->fetch_all(MYSQLI_ASSOC);

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
        <div class="card-header bg-primary text-white text-end">
            ثبت آنالیز غذای طبخ شده
        </div>
        <div class="card-body">
            <?= $msg ?>
            <form method="POST">
                <div class="row g-3 text-end" dir="rtl">
                    <div class="col-md-3">
                        <label class="form-label">تاریخ پخت</label>
                        <input
                            type="text"
                            id="analysis_date"
                            name="analysis_date"
                            class="form-control text-end"
                            placeholder="مثلاً 1405/04/02"
                            autocomplete="off"
                            required
                        >
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نوع وعده</label>
                        <select name="meal_type" class="form-select">
                            <option value="breakfast">صبحانه</option>
                            <option value="lunch">ناهار</option>
                            <option value="dinner">شام</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">نام غذا</label>
                        <input
                            type="text"
                            name="meal_name"
                            class="form-control"
                            placeholder="مثال: قیمه سیب زمینی"
                            required
                        >
                    </div>
                </div>

                <hr>

                <div class="table-responsive" dir="rtl">
                    <table class="table table-bordered text-center" id="ingredientsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60%;">ماده اولیه</th>
                                <th style="width: 25%;">مقدار</th>
                                <th style="width: 15%;">حذف</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="ingredient_id[]" class="form-select ingredient-select" required>
                                        <option value="">جستجو و انتخاب ماده اولیه</option>
                                        <?php foreach ($ingredients as $ing): ?>
                                            <option value="<?= (int)$ing['id'] ?>">
                                                <?= htmlspecialchars($ing['name']) ?> (<?= htmlspecialchars($ing['unit_name']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        name="quantity[]"
                                        class="form-control"
                                        required
                                    >
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger removeRow">حذف</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-start">
                    <button type="button" id="addRow" class="btn btn-primary mt-2">
                        افزودن ماده اولیه
                    </button>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" name="submit_analysis" class="btn btn-success">
                        ثبت آنالیز
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
    function initSelect2(element) {
        element.select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'جستجو و انتخاب ماده اولیه',
            allowClear: true
        });
    }

    initSelect2($('.ingredient-select'));

    $('#analysis_date').persianDatepicker({
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

    $('#addRow').click(function () {
        $('.ingredient-select').each(function () {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });

        let $newRow = $('#ingredientsTable tbody tr:first').clone(false);
        $newRow.find('select').val('');
        $newRow.find('input').val('');

        $('#ingredientsTable tbody').append($newRow);
        initSelect2($('.ingredient-select'));
    });

    $(document).on('click', '.removeRow', function () {
        if ($('#ingredientsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('حداقل باید یک ردیف ماده اولیه وجود داشته باشد.');
        }
    });
});
</script>

<?php include("../layout/footer.php"); ?>