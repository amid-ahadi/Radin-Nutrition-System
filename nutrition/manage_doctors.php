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

// عملیات افزودن دکتر جدید
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_doctor'])) {
    $doctor_name = trim($_POST['doctor_name']);
    $specialty = trim($_POST['specialty']);
    $is_free = isset($_POST['is_free']) ? 1 : 0; // چک کردن تیک رایگان

    if (!empty($doctor_name)) {
        $stmt = $conn->prepare("INSERT INTO doctors (doctor_name, specialty, is_free) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $doctor_name, $specialty, $is_free);
        $stmt->execute();
        echo "<script>window.location.href='manage_doctors.php?msg=added';</script>";
    }
}

// عملیات حذف
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("UPDATE doctors SET is_active = 0 WHERE id = $id");
    echo "<script>window.location.href='manage_doctors.php?msg=deleted';</script>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-user-md me-2 text-primary"></i> مدیریت پزشکان</h4>
    </div>

    <div class="row">
        <!-- فرم افزودن -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">افزودن پزشک جدید</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">نام پزشک:</label>
                            <input type="text" name="doctor_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تخصص:</label>
                            <input type="text" name="specialty" class="form-control">
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_free" id="isFree">
                            <label class="form-check-label" for="isFree">وعده غذایی رایگان (بدون هزینه)</label>
                        </div>
                        <button type="submit" name="add_doctor" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> ثبت پزشک
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- لیست پزشکان -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>نام پزشک</th>
                                <th>تخصص</th>
                                <th class="text-center">وضعیت مالی</th>
                                <th class="text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM doctors WHERE is_active = 1 ORDER BY id DESC");
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><strong><?php echo $row['doctor_name']; ?></strong></td>
                                <td><?php echo $row['specialty']; ?></td>
                                <td class="text-center">
                                    <?php if($row['is_free']): ?>
                                        <span class="badge bg-info text-dark">رایگان (بدون هزینه)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted">دارای هزینه</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')">
                                        <i class="fas fa-trash-alt"></i>
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
</div>

<?php include("../layout/footer.php"); ?>
