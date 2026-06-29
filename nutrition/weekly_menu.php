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
include("../layout/header.php"); 
include_once("../config/database.php");
include_once("../config/jdf.php"); 
include("../auth/role_check.php");

allowRoles(['admin', 'nutrition_manager']);

// دریافت لیست وعده‌ها و غذاها
$meal_types = $conn->query("SELECT id, meal_name FROM meal_types ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$foods = $conn->query("SELECT id, food_name FROM foods WHERE is_active = 1 ORDER BY food_name ASC")->fetch_all(MYSQLI_ASSOC);

function get_persian_day($date) {
    $days = ['Saturday'=>'شنبه', 'Sunday'=>'یکشنبه', 'Monday'=>'دوشنبه', 'Tuesday'=>'سه‌شنبه', 'Wednesday'=>'چهارشنبه', 'Thursday'=>'پنجشنبه', 'Friday'=>'جمعه'];
    return $days[date('l', strtotime($date))];
}
?>

<link href="../assets/css/select2.min.css" rel="stylesheet" />
<script src="../assets/js/sweetalert2@11.js"></script>

<style>
    .weekly-card { border-radius: 12px; border: none; background: #fff; height: 100%; transition: 0.3s; }
    .day-header { border-radius: 12px 12px 0 0; padding: 8px; background: #4e73df; color: white; text-align: center; }
    .meal-box { background: #f8f9fc; border-radius: 8px; padding: 8px; margin-bottom: 6px; border-right: 3px solid #d1d3e2; }
    .select2-container--default .select2-selection--single { border-radius: 6px; height: 32px; font-size: 0.8rem; }
    .sticky-bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 15px; box-shadow: 0 -5px 15px rgba(0,0,0,0.1); z-index: 1000; }
    body { padding-bottom: 80px; } /* فضای خالی برای نوار پایین */
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <h4 class="fw-bold text-primary mb-0"><i class="fas fa-calendar-week me-2"></i>تنظیم برنامه هفتگی</h4>
        <span class="badge bg-light text-dark p-2">امروز: <?php echo jdate("Y/m/d"); ?></span>
    </div>

    <!-- فرم اصلی برای ارسال دسته‌جمعی -->
    <form id="weeklyMenuForm">
        <div class="row row-cols-1 row-cols-md-4 row-cols-lg-7 g-2">
            <?php 
            for($i = 0; $i < 7; $i++): 
                $current_date = date('Y-m-d', strtotime("+$i days"));
                $day_name = get_persian_day($current_date);
            ?>
            <div class="col">
                <div class="card weekly-card shadow-sm">
                    <div class="day-header">
                        <div class="small fw-bold"><?php echo $day_name; ?></div>
                        <div style="font-size: 0.75rem; opacity: 0.9;"><?php echo jdate("m/d", strtotime($current_date)); ?></div>
                    </div>
                    <div class="card-body p-2">
                        <?php foreach($meal_types as $meal): 
                            // چک کردن مقادیر موجود در دیتابیس
                            $db_val = $conn->query("SELECT food_id FROM daily_menu WHERE serving_date = '$current_date' AND meal_type_id = {$meal['id']}")->fetch_assoc();
                            $selected_id = $db_val ? $db_val['food_id'] : '';
                        ?>
                        <div class="meal-box">
                            <label class="small d-block mb-1 fw-bold text-muted"><?php echo $meal['meal_name']; ?></label>
                            <select name="menu[<?php echo $current_date; ?>][<?php echo $meal['id']; ?>]" class="form-select food-select">
                                <option value="">---</option>
                                <?php foreach($foods as $food): ?>
                                    <option value="<?php echo $food['id']; ?>" <?php echo ($selected_id == $food['id']) ? 'selected' : ''; ?>>
                                        <?php echo $food['food_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- نوار شناور عملیات -->
        <div class="sticky-bottom-bar d-flex justify-content-center">
            <button type="button" id="saveAllBtn" class="btn btn-success btn-lg px-5 shadow">
                <i class="fas fa-check-double me-2"></i> ذخیره نهایی کل برنامه هفته
            </button>
        </div>
    </form>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // فعالسازی Select2 برای تمام دراپ‌داون‌ها
    $('.food-select').select2({
        dir: "rtl",
        width: '100%',
        placeholder: "انتخاب غذا"
    });

    // عملکرد دکمه ذخیره نهایی
    $('#saveAllBtn').click(function() {
        Swal.fire({
            title: 'ذخیره برنامه هفتگی؟',
            text: "تمام تغییرات در دیتابیس ثبت خواهند شد.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1cc88a',
            cancelButtonColor: '#858796',
            confirmButtonText: 'بله، ذخیره کن',
            cancelButtonText: 'بررسی مجدد'
        }).then((result) => {
            if (result.isConfirmed) {
                // نمایش لودینگ
                Swal.fire({ title: 'در حال ذخیره‌سازی...', didOpen: () => { Swal.showLoading() } });

                // ارسال کل فرم به صورت یکجا
                $.ajax({
                    url: 'save_weekly_batch.php',
                    type: 'POST',
                    data: $('#weeklyMenuForm').serialize(),
                    success: function(response) {
                        Swal.fire({
                            title: 'موفقیت‌آمیز',
                            text: 'برنامه کل هفته با موفقیت بروزرسانی شد.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('خطا', 'مشکلی در ارتباط با سرور پیش آمد.', 'error');
                    }
                });
            }
        });
    });
});
</script>

<?php include("../layout/footer.php"); ?>
