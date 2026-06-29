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
ob_start();
include("../layout/header.php"); 
include_once("../config/database.php");
include_once("../config/jdf.php"); 
include("../auth/role_check.php");

allowRoles(['admin', 'ward_secretary']);

$today_miladi = date("Y-m-d");
$today_shamsi = jdate("Y/m/d");
$ward_id = $_SESSION['ward_id'] ?? 0; 

$ward_query = $conn->query("SELECT ward_name FROM wards WHERE id = '$ward_id'");
$ward_data = $ward_query->fetch_assoc();
$ward_name = $ward_data['ward_name'] ?? 'نامشخص';

// بررسی وضعیت درخواست برای قفل کردن فرم
$check_status = $conn->query("
    SELECT status 
    FROM meal_requests 
    WHERE ward_id = '$ward_id' 
    AND meal_date = '$today_miladi' 
    LIMIT 1
");

$row = $check_status->fetch_assoc();
$current_status = isset($row['status']) ? (int)$row['status'] : null;

// قفل کردن فرم اگر تایید شده (1) یا در انتظار (0) باشد
$is_locked = ($current_status === 0 || $current_status === 1);
?>

<!-- SweetAlert2 -->
<script src="../assets/js/sweetalert2@11.js"></script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-9 mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        ثبت آمار بخش: <span class="text-warning"><?php echo $ward_name; ?></span>
                    </h5>
                    <div>
                        <?php if($is_locked): ?>
                            <span class="badge bg-warning text-dark p-2 me-2"><i class="fas fa-lock"></i> فرم قفل شده (<?php echo ($current_status === 0) ? 'در انتظار تایید' : 'تایید شده'; ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-info p-2 me-2"><i class="fas fa-edit"></i> آماده ثبت آمار</span>
                        <?php endif; ?>
                        <span class="badge bg-primary fs-6 p-2"><?php echo $today_shamsi; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <form action="save_request.php" method="POST">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 40%;">وعده غذایی</th>
                                        <th style="width: 30%;">تعداد بیمار</th>
                                        <th style="width: 30%;">تعداد پرسنل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // اصلاح شده: استفاده از JOIN برای دریافت نام وعده از جدول meal_types
                                    $sql_meals = "SELECT DISTINCT 
                                                    COALESCE(mt.meal_name, dm.meal_type) AS display_name,
                                                    dm.meal_type_id
                                                  FROM daily_menu dm
                                                  LEFT JOIN meal_types mt ON dm.meal_type_id = mt.id
                                                  WHERE dm.serving_date = '$today_miladi'";
                                    
                                    $daily_meals = $conn->query($sql_meals);

                                    if($daily_meals && $daily_meals->num_rows > 0): 
                                        while($m = $daily_meals->fetch_assoc()):
                                            $m_name = $m['display_name'];
                                            
                                            // واکشی مقادیر قبلی ثبت شده (اگر وجود داشته باشد)
                                            $val_query = $conn->query("SELECT patient_count, staff_count FROM meal_requests WHERE ward_id = '$ward_id' AND meal_date = '$today_miladi' AND meal_type = '$m_name'");
                                            $val = $val_query->fetch_assoc();
                                            $p_val = $val['patient_count'] ?? 0;
                                            $s_val = $val['staff_count'] ?? 0;
                                    ?>
                                    <tr>
                                        <td class="fw-bold bg-light"><?php echo $m_name; ?></td>
                                        <td>
                                            <input type="number" name="patients[<?php echo $m_name; ?>]" class="form-control text-center" value="<?php echo $p_val; ?>" <?php echo $is_locked ? 'disabled' : ''; ?> min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" name="staff[<?php echo $m_name; ?>]" class="form-control text-center" value="<?php echo $s_val; ?>" <?php echo $is_locked ? 'disabled' : ''; ?> min="0" required>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="3" class="py-5 text-muted">برنامه غذایی برای امروز توسط واحد تغذیه ثبت نشده است.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-4">
                            <?php if(!$is_locked && $daily_meals->num_rows > 0): ?>
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow"><i class="fas fa-paper-plane me-2"></i> ارسال نهایی</button>
                            <?php elseif($is_locked): ?>
                                <div class="alert alert-secondary d-inline-block px-5 border-0 shadow-sm">
                                    <i class="fas fa-lock me-2"></i> این فرم قبلاً ارسال شده و قفل است.
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg === 'success') {
        Swal.fire({
            title: 'موفقیت‌آمیز!',
            text: 'آمار با موفقیت ثبت و فرم قفل گردید.',
            icon: 'success',
            confirmButtonText: 'متوجه شدم'
        });
    } 
    else if (msg === 'locked') {
        Swal.fire({
            title: 'فرم قفل است',
            text: 'امکان ثبت مجدد آمار وجود ندارد.',
            icon: 'warning',
            confirmButtonText: 'تایید'
        });
    }
});
</script>

<?php include("../layout/footer.php"); ?>
