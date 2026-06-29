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

$today_date_shamsi = jdate("Y/m/d");
$today_date_miladi = date("Y-m-d"); 
?>

<!-- ۱. اضافه کردن استایل Select2 -->
<link href="../assets/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row">
        <!-- پنل افزودن غذا -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-calendar-plus me-2"></i>تعیین غذای امروز (<?php echo $today_date_shamsi; ?>)
                </div>
                <div class="card-body">
                    <form action="save_daily_menu.php" method="POST">
                        <input type="hidden" name="serving_date" value="<?php echo $today_date_miladi; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">نوع وعده</label>
                            <select name="meal_type_id" class="form-select" required>
                                <option value="">انتخاب وعده...</option>
                                <?php
                                // اصلاح: واکشی ID بجای نام متنی
                                $meals = $conn->query("SELECT id, meal_name FROM meal_types ORDER BY id ASC");
                                while($m = $meals->fetch_assoc()) {
                                    echo "<option value='{$m['id']}'>{$m['meal_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">انتخاب غذا (با قابلیت جستجو)</label>
                            <select name="food_id" id="food_search" class="form-select" required>
                                <option value="">نام غذا را تایپ کنید...</option>
                                <?php
                                $foods = $conn->query("SELECT id, food_name FROM foods WHERE is_active = 1 ORDER BY food_name ASC");
                                if($foods && $foods->num_rows > 0) {
                                    while($f = $foods->fetch_assoc()) {
                                        echo "<option value='{$f['id']}'>{$f['food_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 shadow-sm">
                            <i class="fas fa-plus me-1"></i> افزودن به برنامه امروز
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- پنل نمایش جدول -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-ul me-2"></i>برنامه غذایی فعال امروز</span>
                    <span class="badge bg-secondary"><?php echo $today_date_miladi; ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">وعده</th>
                                    <th>نام غذا</th>
                                    <th class="text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // اصلاح کوئری: اتصال به جدول meal_types برای نمایش نام صحیح بر اساس ID
                                $sql = "SELECT dm.*, f.food_name, mt.meal_name 
                                        FROM daily_menu dm 
                                        JOIN foods f ON dm.food_id = f.id 
                                        LEFT JOIN meal_types mt ON dm.meal_type_id = mt.id 
                                        WHERE dm.serving_date = '$today_date_miladi'
                                        ORDER BY mt.id ASC";
                                $res = $conn->query($sql);
                                
                                if($res && $res->num_rows > 0):
                                    while($row = $res->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-info text-dark">
                                            <?php 
                                            // اگر meal_name در JOIN پیدا شد نمایش بده، در غیر این صورت مقدار قدیمی را نشان بده
                                            echo !empty($row['meal_name']) ? $row['meal_name'] : $row['meal_type']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $row['food_name']; ?></strong></td>
                                    <td class="text-center">
                                        <a href="delete_daily.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('آیا از حذف این مورد اطمینان دارید؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">برنامه‌ای برای امروز تنظیم نشده است.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ۳. اضافه کردن اسکریپت‌ها قبل از فوتر -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#food_search').select2({
        dir: "rtl",
        placeholder: "جستجوی سریع غذا...",
        allowClear: true,
        width: '100%'
    });
});
</script>

<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-right: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<?php include("../layout/footer.php"); ?>
