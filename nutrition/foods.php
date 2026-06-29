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
include("../config/database.php");
include("../auth/role_check.php");

allowRoles(['admin', 'nutrition_manager']); 

// اطمینان از وجود ستون‌های مورد نیاز در دیتابیس (هوشمند)
$conn->query("ALTER TABLE foods ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER food_name");
$conn->query("ALTER TABLE foods ADD COLUMN IF NOT EXISTS meal_type VARCHAR(50) DEFAULT 'both' AFTER description");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-utensils me-2 text-primary"></i> مدیریت مخزن غذاها</h4>
        <button class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#addFoodModal">
            <i class="fas fa-plus me-1"></i> افزودن غذای جدید
        </button>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm">عملیات با موفقیت انجام شد.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="px-4">ID</th>
                            <th>نام غذا</th>
                            <th>وعده اختصاصی</th>
                            <th>توضیحات</th>
                            <th>وضعیت</th>
                            <th class="text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM foods ORDER BY id DESC");
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="px-4 text-muted"><?php echo $row['id']; ?></td>
                            <td><strong><?php echo $row['food_name']; ?></strong></td>
                            <td><span class="badge bg-info text-dark"><?php echo $row['meal_type']; ?></span></td>
                            <td><small class="text-muted"><?php echo $row['description'] ?: '---'; ?></small></td>
                            <td>
                                <?php if($row['is_active'] == 1): ?>
                                    <span class="text-success small"><i class="fas fa-check-circle me-1"></i> فعال</span>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fas fa-times-circle me-1"></i> غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="delete_food.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal افزودن غذا -->
<div class="modal fade" id="addFoodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">ثبت غذای جدید</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_food.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">نام غذا:</label>
                        <input type="text" name="food_name" class="form-control" placeholder="مثلاً: حلیم" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">وعده (صبحانه/ناهار/شام...):</label>
                        <select name="meal_type" class="form-select">
                            <option value="همه وعده‌ها">همه وعده‌ها</option>
                            <?php
                            // دریافت لیست وعده‌ها از دیتابیس
                            $mt_res = $conn->query("SELECT meal_name FROM meal_types");
                            if($mt_res && $mt_res->num_rows > 0){
                                while($mt = $mt_res->fetch_assoc()){
                                    echo "<option value='{$mt['meal_name']}'>{$mt['meal_name']}</option>";
                                }
                            } else {
                                // مقادیر پیش‌فرض اگر جدول خالی بود
                                echo '<option value="صبحانه">صبحانه</option>';
                                echo '<option value="ناهار">ناهار</option>';
                                echo '<option value="شام">شام</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">توضیحات:</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیره در مخزن غذا</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("../layout/footer.php"); ?>
