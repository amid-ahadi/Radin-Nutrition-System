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

// فقط ادمین اجازه دسترسی دارد
allowRoles(['admin']);

// عملیات حذف
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM wards WHERE id = $id");
    header("Location: wards.php?msg=deleted");
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-hospital me-2"></i> مدیریت بخش‌های بیمارستان</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWardModal">
            <i class="fas fa-plus me-1"></i> افزودن بخش جدید
        </button>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success">بخش جدید با موفقیت ثبت شد.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">شناسه</th>
                        <th>نام بخش</th>
                        <th>طبقه</th>
                        <th>ظرفیت تخت</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM wards ORDER BY id DESC");
                    while($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="px-4"><?php echo $row['id']; ?></td>
                        <td><strong><?php echo $row['ward_name']; ?></strong></td>
                        <td><?php echo $row['floor']; ?></td>
                        <td><?php echo $row['capacity']; ?> نفر</td>
                        <td class="text-center">
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('آیا از حذف این بخش اطمینان دارید؟')">
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

<!-- Modal افزودن بخش -->
<div class="modal fade" id="addWardModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="save_ward.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ثبت بخش جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">نام بخش</label>
                    <input type="text" name="ward_name" class="form-control" required placeholder="مثلاً: بخش اطفال">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">طبقه</label>
                        <input type="number" name="floor" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ظرفیت</label>
                        <input type="number" name="capacity" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">ذخیره بخش</button>
            </div>
        </form>
    </div>
</div>

<?php include("../layout/footer.php"); ?>
