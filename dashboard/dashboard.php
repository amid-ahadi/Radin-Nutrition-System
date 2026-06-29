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
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
include("../auth/check_access.php");
include("../config/database.php");

$role = $_SESSION["role_name"];
?>

<h2>داشبورد سامانه تغذیه رادین (RFS)</h2>

<?php if($role=="ward_secretary"): ?>
    <a href="../meals/add_request.php">ثبت درخواست غذا</a>
<?php endif; ?>

<?php if($role=="matron"): ?>
    <a href="../approvals/matron.php">کارتابل تایید مترون</a>
<?php endif; ?>

<?php if($role=="nutrition_manager"): ?>
    <a href="../approvals/nutrition_manager.php">کارتابل مدیر تغذیه</a>
<?php endif; ?>

<?php if($role=="finance"): ?>
    <a href="../reports/financial_report.php">گزارش مالی</a>
<?php endif; ?>
