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
include_once("../layout/header.php");
include_once("../config/database.php");
include_once("../config/jdf.php");
include_once("../auth/role_check.php");

allowRoles(["matron", "admin"]);

if (!isset($_GET['ward_id']) || !isset($_GET['date'])) {
    header("Location: matron.php");
    exit;
}

$ward_id = (int)$_GET['ward_id'];
$meal_date = $conn->real_escape_string($_GET['date']);

// دریافت اطلاعات بخش و لیست درخواست‌ها
$ward_name = $conn->query("SELECT ward_name FROM wards WHERE id = $ward_id")->fetch_assoc()['ward_name'];
$result = $conn->query("SELECT * FROM meal_requests WHERE ward_id = $ward_id AND meal_date = '$meal_date'");
?>

<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">جزئیات درخواست: <?php echo $ward_name; ?></h5>
            <span class="badge bg-info text-dark"><?php echo $meal_date; ?></span>
        </div>
        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>وعده</th>
                        <th>تعداد بیمار</th>
                        <th>تعداد پرسنل</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?php echo $row['meal_type']; ?></td>
                        <td><?php echo $row['patient_count']; ?></td>
                        <td><?php echo $row['staff_count']; ?></td>
                        <td>
                            <?php if($row['status'] == 0) echo '<span class="text-warning">در انتظار</span>'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- دکمه‌های عملیاتی در پایین صفحه جزئیات -->
            <div class="mt-4 d-flex justify-content-center gap-3">
                <button onclick="updateStatus(<?php echo $ward_id; ?>, '<?php echo $meal_date; ?>', 1)" class="btn btn-success px-4">
                    <i class="fas fa-check"></i> تایید کلی این لیست
                </button>
                <button onclick="updateStatus(<?php echo $ward_id; ?>, '<?php echo $meal_date; ?>', 2)" class="btn btn-danger px-4">
                    <i class="fas fa-times"></i> رد کل این لیست
                </button>
                <a href="matron.php" class="btn btn-secondary px-4">بازگشت</a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/sweetalert2@11.js"></script>
<script>
// اسکریپت مشابه صفحه قبل برای ارسال وضعیت به دیتابیس
function updateStatus(wardId, mealDate, statusVal) {
    const formData = new FormData();
    formData.append('ward_id', wardId);
    formData.append('meal_date', mealDate);
    formData.append('status', statusVal);

    fetch("process_group.php", { method: "POST", body: formData })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === 'success') {
            Swal.fire({ icon: 'success', title: 'ثبت شد', timer: 800, showConfirmButton: false })
            .then(() => window.location.href = 'matron.php');
        } else {
            alert("خطا در ثبت وضعیت");
        }
    });
}
</script>
