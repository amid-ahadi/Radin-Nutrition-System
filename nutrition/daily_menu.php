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
$msg = "";

// --- بخش پردازش فرم (جلوگیری از خطای Duplicate Entry) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_meal'])) {
    $serving_date = $_POST['serving_date'];
    $meal_type_id = intval($_POST['meal_type_id']);
    $food_id = intval($_POST['food_id']);

    if (!empty($serving_date) && !empty($meal_type_id) && !empty($food_id)) {
        // استفاده از دستور هوشمند برای جلوگیری از خطا: اگر تکراری بود، آپدیت کن
        $sql_upsert = "INSERT INTO daily_menu (serving_date, food_id, meal_type_id, status) 
                       VALUES (?, ?, ?, 1) 
                       ON DUPLICATE KEY UPDATE food_id = VALUES(food_id)";
        
        $stmt = $conn->prepare($sql_upsert);
        $stmt->bind_param("sii", $serving_date, $food_id, $meal_type_id);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>برنامه با موفقیت ثبت/بروزرسانی شد.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>خطا در ذخیره‌سازی: " . $conn->error . "</div>";
        }
    }
}
?>

<!-- ۱. اضافه کردن استایل Select2 -->
<link href="../assets/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <?php echo $msg; ?>
        </div>
        
        <!-- پنل افزودن غذا -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-calendar-plus me-2"></i>تعیین غذای امروز (<?php echo $today_date_shamsi; ?>)
                </div>
                <div class="card-body">
                    <form action="daily_menu.php" method="POST">
                        <input type="hidden" name="serving_date" value="<?php echo $today_date_miladi; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">نوع وعده</label>
                            <select name="meal_type_id" class="form-select" required>
                                <option value="">انتخاب وعده...</option>
                                <?php
                                $meals = $conn->query("SELECT id, meal_name FROM meal_types ORDER BY id ASC");
                                while($m = $meals->fetch_assoc()) {
                                    echo "<option value='{$m['id']}'>{$m['meal_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">انتخاب غذا (جستجو کنید)</label>
                            <select name="food_id" id="food_search" class="form-select" required>
                                <option value="">نام غذا...</option>
                                <?php
                                $foods = $conn->query("SELECT id, food_name FROM foods WHERE is_active = 1 ORDER BY food_name ASC");
                                while($f = $foods->fetch_assoc()) {
                                    echo "<option value='{$f['id']}'>{$f['food_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_meal" class="btn btn-success w-100 shadow-sm">
                            <i class="fas fa-save me-1"></i> ثبت در برنامه امروز
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
                    <span class="badge bg-secondary"><?php echo $today_date_shamsi; ?></span>
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
                                            <?php echo !empty($row['meal_name']) ? $row['meal_name'] : "نامشخص"; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $row['food_name']; ?></strong></td>
                                    <td class="text-center">
                                        <a href="delete_daily.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('آیا از حذف این وعده اطمینان دارید؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">هنوز غذایی برای امروز ثبت نشده است.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- اسکریپت‌ها -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#food_search').select2({
        dir: "rtl",
        placeholder: "نام غذا را جستجو کنید...",
        width: '100%'
    });
});
</script>

<style>
    .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #dee2e6 !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
</style>

<?php include("../layout/footer.php"); ?>
