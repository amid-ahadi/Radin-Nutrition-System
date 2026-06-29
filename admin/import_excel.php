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

allowRoles(['admin']);

$message = "";
$errors = [];
$successCount = 0;
$skipCount = 0;

/**
 * تبدیل اعداد فارسی/عربی به انگلیسی
 */
function convertPersianNumbers($string) {
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];

    $string = str_replace($persian, $english, $string);
    $string = str_replace($arabic, $english, $string);

    return $string;
}

/**
 * حذف BOM از ابتدای فایل CSV در صورت وجود
 */
function removeBom($string) {
    return preg_replace('/^\xEF\xBB\xBF/', '', $string);
}

/**
 * تبدیل تاریخ شمسی به میلادی
 * خروجی: Y-m-d
 */
function jalaliToGregorianDate($jy, $jm, $jd) {
    $jy = (int)$jy;
    $jm = (int)$jm;
    $jd = (int)$jd;

    $jy += 1595;

    $days = -355668
        + (365 * $jy)
        + ((int)($jy / 33) * 8)
        + (int)((($jy % 33) + 3) / 4)
        + $jd;

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

    $months = [
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

    for ($gm = 1; $gm <= 12 && $gd > $months[$gm]; $gm++) {
        $gd -= $months[$gm];
    }

    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

/**
 * تبدیل رشته تاریخ شمسی مثل 1405/01/01 به تاریخ میلادی
 */
function shamsiToGregorian($shamsiDate) {
    $shamsiDate = trim(removeBom(convertPersianNumbers($shamsiDate)));
    $shamsiDate = str_replace('-', '/', $shamsiDate);

    $parts = explode('/', $shamsiDate);

    if (count($parts) !== 3) {
        return null;
    }

    $jy = (int)$parts[0];
    $jm = (int)$parts[1];
    $jd = (int)$parts[2];

    if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
        return null;
    }

    return jalaliToGregorianDate($jy, $jm, $jd);
}

/**
 * هندل کردن آپلود و ایمپورت
 */
if (isset($_POST["import"])) {

    if (!isset($_FILES["excel_file"]) || $_FILES["excel_file"]["error"] !== UPLOAD_ERR_OK) {
        $message = "<div class='alert alert-danger'>خطا در آپلود فایل.</div>";
    } else {

        $fileName = $_FILES["excel_file"]["tmp_name"];
        $fileSize = $_FILES["excel_file"]["size"];

        if ($fileSize <= 0) {
            $message = "<div class='alert alert-danger'>فایل انتخاب‌شده خالی است.</div>";
        } else {

            $file = fopen($fileName, "r");

            if (!$file) {
                $message = "<div class='alert alert-danger'>امکان باز کردن فایل وجود ندارد.</div>";
            } else {

                $delimiter = ",";

                $conn->begin_transaction();

                try {
                    /*
                     * گرفتن قیمت‌ها برای ثبت unit_price لحظه‌ای
                     * اگر اسم جدول یا ستون‌های قیمت شما فرق دارد، این کوئری را باید اصلاح کنیم.
                     */
                    $prices = [];

                    $priceSql = "
                        SELECT meal_type_id, consumer_type_id, price
                        FROM food_prices
                    ";

                    $priceResult = $conn->query($priceSql);

                    if ($priceResult) {
                        while ($p = $priceResult->fetch_assoc()) {
                            $mId = (int)$p['meal_type_id'];
                            $cId = (int)$p['consumer_type_id'];
                            $prices[$mId][$cId] = (float)$p['price'];
                        }
                    }

                    /*
                     * رد کردن خط اول فایل CSV
                     * خط اول:
                     * date,count,meal_type,consumer_id
                     */
                    $header = fgetcsv($file, 0, $delimiter);

                    $stmt = $conn->prepare("
                        INSERT INTO daily_statistics
                            (stat_date, meal_type_id, consumer_type_id, quantity, unit_price)
                        VALUES
                            (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            quantity = VALUES(quantity),
                            unit_price = VALUES(unit_price)
                    ");

                    if (!$stmt) {
                        throw new Exception("خطا در آماده‌سازی کوئری: " . $conn->error);
                    }

                    $lineNumber = 1;

                    while (($column = fgetcsv($file, 0, $delimiter)) !== false) {
                        $lineNumber++;

                        /*
                         * فرمت مورد انتظار:
                         * 0 => date
                         * 1 => count
                         * 2 => meal_type
                         * 3 => consumer_id
                         */
                        if (count($column) < 4) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: تعداد ستون‌ها کمتر از ۴ است.";
                            continue;
                        }

                        $shamsiDateRaw = trim(removeBom($column[0] ?? ""));
                        $qtyRaw        = trim($column[1] ?? "0");
                        $mealRaw       = trim($column[2] ?? "0");
                        $consumerRaw   = trim($column[3] ?? "0");

                        $shamsiDate = convertPersianNumbers($shamsiDateRaw);
                        $qty        = (int)convertPersianNumbers($qtyRaw);
                        $mealId     = (int)convertPersianNumbers($mealRaw);
                        $consumerId = (int)convertPersianNumbers($consumerRaw);

                        $miladiDate = shamsiToGregorian($shamsiDate);

                        if (!$miladiDate) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: تاریخ نامعتبر است: " . htmlspecialchars($shamsiDateRaw);
                            continue;
                        }

                        if ($qty <= 0) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: تعداد نامعتبر است: " . htmlspecialchars($qtyRaw);
                            continue;
                        }

                        if ($mealId <= 0) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: meal_type نامعتبر است: " . htmlspecialchars($mealRaw);
                            continue;
                        }

                        if ($consumerId <= 0) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: consumer_id نامعتبر است: " . htmlspecialchars($consumerRaw);
                            continue;
                        }

                        $unitPrice = 0;

                        if (isset($prices[$mealId][$consumerId])) {
                            $unitPrice = (float)$prices[$mealId][$consumerId];
                        }

                        $stmt->bind_param(
                            "siiid",
                            $miladiDate,
                            $mealId,
                            $consumerId,
                            $qty,
                            $unitPrice
                        );

                        if (!$stmt->execute()) {
                            $skipCount++;
                            $errors[] = "خط $lineNumber: خطا در ثبت دیتابیس: " . $stmt->error;
                            continue;
                        }

                        $successCount++;
                    }

                    $stmt->close();
                    $conn->commit();

                    $message = "
                        <div class='alert alert-success'>
                            عملیات ایمپورت انجام شد.
                            <br>
                            تعداد ردیف‌های ثبت/بروزرسانی‌شده: <strong>$successCount</strong>
                            <br>
                            تعداد ردیف‌های ردشده: <strong>$skipCount</strong>
                        </div>
                    ";

                } catch (Exception $e) {
                    $conn->rollback();

                    $message = "
                        <div class='alert alert-danger'>
                            خطا در عملیات ایمپورت:
                            <br>
                            " . htmlspecialchars($e->getMessage()) . "
                        </div>
                    ";
                }

                fclose($file);
            }
        }
    }
}

include("../layout/header.php");
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        ایمپورت آمار روزانه از CSV
                    </h5>
                </div>

                <div class="card-body">

                    <?php echo $message; ?>

                    <div class="alert alert-info">
                        <strong>فرمت فایل CSV باید دقیقاً این باشد:</strong>
                        <pre class="mb-0 mt-2" style="direction:ltr;text-align:left;">date,count,meal_type,consumer_id
1405/01/01,68,24,10
1405/01/01,2,24,8</pre>
                    </div>

                    <form method="post" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                انتخاب فایل CSV
                            </label>
                            <input
                                type="file"
                                name="excel_file"
                                class="form-control"
                                accept=".csv"
                                required
                            >
                        </div>

                        <button type="submit" name="import" class="btn btn-success">
                            شروع ایمپورت
                        </button>

                    </form>

                    <?php if (!empty($errors)): ?>
                        <hr>

                        <div class="alert alert-warning">
                            <strong>گزارش ردیف‌های ردشده:</strong>
                            <br>
                            فقط ۳۰ خط اول نمایش داده می‌شود.
                        </div>

                        <div style="max-height: 300px; overflow:auto; direction:rtl;">
                            <ul class="list-group">
                                <?php foreach (array_slice($errors, 0, 30) as $err): ?>
                                    <li class="list-group-item text-danger">
                                        <?php echo htmlspecialchars($err); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</div>

<?php include("../layout/footer.php"); ?>
