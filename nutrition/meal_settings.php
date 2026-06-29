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

// عملیات افزودن وعده جدید
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_meal'])) {
    $meal_name = trim($_POST['meal_name']);
    if (!empty($meal_name)) {
        $stmt = $conn->prepare("INSERT INTO meal_types (meal_name) VALUES (?)");
        $stmt->bind_param("s", $meal_name);
        $stmt->execute();
        echo "<script>window.location.href='meal_settings.php?msg=added';</script>";
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i> تنظیمات انواع وعده‌های غذایی</h4>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <?php 
                if($_GET['msg'] == 'added') echo "وعده جدید با موفقیت اضافه شد.";
                if($_GET['msg'] == 'deleted') echo "وعده مورد نظر حذف شد.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- فرم افزودن -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">افزودن وعده جدید</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">نام وعده:</label>
                            <input type="text" name="meal_name" class="form-control" placeholder="مثلاً: میان وعده، افطار..." required>
                        </div>
                        <button type="submit" name="add_meal" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> ثبت در سیستم
                        </button>
                    </form>
                </div>
            </div>
            <div class="alert alert-info mt-3 small">
                <i class="fas fa-info-circle me-1"></i> وعده‌هایی که در اینجا تعریف می‌کنید، در هنگام ثبت غذا و چیدمان برنامه روزانه نمایش داده می‌شوند.
            </div>
        </div>

        <!-- لیست وعده‌ها -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">ID</th>
                                <th>نام وعده غذایی</th>
                                <th class="text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM meal_types ORDER BY id ASC");
                            if ($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="px-4 text-muted"><?php echo $row['id']; ?></td>
                                <td><strong><?php echo $row['meal_name']; ?></strong></td>
                                <td class="text-center">
                                    <!-- دکمه حذف -->
                                    <a href="delete_meal_type.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('آیا از حذف این وعده اطمینان دارید؟ غذاهای مرتبط با این وعده ممکن است دچار مشکل شوند.')">
                                        <i class="fas fa-trash-alt"></i> حذف
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr><td colspan="3" class="text-center py-4">هیچ وعده‌ای تعریف نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../layout/footer.php"); ?>
