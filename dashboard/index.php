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
    header("Location: ../auth/login.php");
    exit();
}

include("../layout/header.php");

$role = $_SESSION["role"];
?>

<div class="container-fluid">

<h4 class="mb-4 fw-bold">داشبورد سامانه تغذیه رادین (RFS)</h4>

<div class="row g-4">

<?php if($role=="ward_secretary" || $role=="secretary" || $role=="admin"): ?>
<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-plus-circle fa-2x text-primary mb-3"></i>
            <h6 class="mb-2">ثبت درخواست غذا</h6>
            <p class="text-muted small">ثبت غذای پزشکان توسط بخش</p>
            <a href="../ward/doctor_meal_request_entry.php" class="btn btn-primary btn-sm">
                ورود به صفحه
            </a>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($role=="matron" || $role=="admin"): ?>
<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-check-double fa-2x text-success mb-3"></i>
            <h6 class="mb-2">تایید درخواست‌ها</h6>
            <p class="text-muted small">کارتابل تایید مترون</p>
            <a href="../ward/doctor_meal_requests_matron.php" class="btn btn-success btn-sm">
                مشاهده
            </a>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($role=="nutrition_manager" || $role=="admin"): ?>
<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-chart-bar fa-2x text-warning mb-3"></i>
            <h6 class="mb-2">آمار روزانه</h6>
            <p class="text-muted small">مدیریت آمار غذای روزانه</p>
            <a href="../nutrition/daily_stats.php" class="btn btn-warning btn-sm">
                مشاهده
            </a>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-user-md fa-2x text-info mb-3"></i>
            <h6 class="mb-2">آمار پزشکان</h6>
            <p class="text-muted small">ثبت غذای پزشکان</p>
            <a href="../nutrition/doctor_meal_entry.php" class="btn btn-info btn-sm">
                ورود
            </a>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-tags fa-2x text-secondary mb-3"></i>
            <h6 class="mb-2">قیمت‌گذاری وعده‌ها</h6>
            <p class="text-muted small">مدیریت قیمت غذاها</p>
            <a href="../nutrition/meal_pricing.php" class="btn btn-secondary btn-sm">
                مدیریت
            </a>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($role=="admin"): ?>
<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-users fa-2x text-dark mb-3"></i>
            <h6 class="mb-2">مدیریت کاربران</h6>
            <p class="text-muted small">ایجاد و ویرایش کاربران سیستم</p>
            <a href="../admin/users.php" class="btn btn-dark btn-sm">
                مدیریت
            </a>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card info-card shadow-sm border-0">
        <div class="card-body text-center">
            <i class="fas fa-hospital fa-2x text-primary mb-3"></i>
            <h6 class="mb-2">مدیریت بخش‌ها</h6>
            <p class="text-muted small">ثبت و ویرایش بخش‌های بیمارستان</p>
            <a href="../admin/wards.php" class="btn btn-primary btn-sm">
                ورود
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

</div>

</div>

<?php include("../layout/footer.php"); ?>
