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

// دیباگ ساده: اگر پارامتری نیست چاپ کن
if (!isset($_GET['ward_id']) || !isset($_GET['date'])) {
    echo "<div class='container mt-5 alert alert-danger'>
            <h5>خطا در انتقال اطلاعات!</h5>
            <p>پارامترهای مورد نیاز (ward_id یا date) یافت نشد.</p>
            <a href='matron.php' class='btn btn-primary'>بازگشت به لیست</a>
          </div>";
    exit;
}

$ward_id = (int)$_GET['ward_id'];
$meal_date = $conn->real_escape_string($_GET['date']);

// دریافت نام بخش
$ward_res = $conn->query("SELECT ward_name FROM wards WHERE id = $ward_id");
$ward_name = ($ward_res && $ward_res->num_rows > 0) ? $ward_res->fetch_assoc()['ward_name'] : "بخش نامشخص";

// دریافت لیست بر اساس تاریخ و بخش
$query = "SELECT * FROM meal_requests WHERE ward_id = $ward_id AND meal_date = '$meal_date'";
$result = $conn->query($query);
?>

<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">ریز درخواست‌های بخش: <?php echo $ward_name; ?></h5>
            <span class="badge bg-warning text-dark"><?php echo $meal_date; ?></span>
        </div>
        <div class="card-body">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th>وعده</th>
                        <th>تعداد بیمار</th>
                        <th>تعداد پرسنل</th>
                        <th>وضعیت فعلی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['meal_type']; ?></td>
                        <td><?php echo $row['patient_count']; ?></td>
                        <td><?php echo $row['staff_count']; ?></td>
                        <td><?php echo ($row['status'] == '0' ? 'در انتظار' : 'تایید شده'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="text-center mt-4">
                <a href="matron.php" class="btn btn-secondary px-4">بازگشت</a>
            </div>
        </div>
    </div>
</div>
