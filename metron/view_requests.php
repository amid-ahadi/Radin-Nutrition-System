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

// فقط ادمین و مترون اجازه دسترسی دارند
allowRoles(['admin', 'matron']);

$today_miladi = date("Y-m-d");
$today_shamsi = jdate("Y/m/d");

// واکشی لیست بخش‌هایی که برای امروز آمار رد کرده‌اند
// استفاده از GROUP BY برای اینکه هر بخش را در یک ردیف ببینیم
$query = "SELECT mr.ward_id, w.ward_name, mr.status, 
          SUM(mr.patient_count) as total_patients, 
          SUM(mr.staff_count) as total_staff,
          MAX(mr.created_at) as last_update
          FROM meal_requests mr
          JOIN wards w ON mr.ward_id = w.id
          WHERE mr.meal_date = '$today_miladi'
          GROUP BY mr.ward_id, mr.status";

$requests = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i> لیست درخواست‌های تغذیه بخش‌ها</h5>
                    <span class="badge bg-light text-primary fs-6"><?php echo $today_shamsi; ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>نام بخش</th>
                                    <th>مجموع بیماران</th>
                                    <th>مجموع پرسنل</th>
                                    <th>آخرین بروزرسانی</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($requests->num_rows > 0): ?>
                                    <?php while($row = $requests->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $row['ward_name']; ?></td>
                                            <td><span class="badge bg-info px-3"><?php echo $row['total_patients']; ?></span></td>
                                            <td><span class="badge bg-secondary px-3"><?php echo $row['total_staff']; ?></span></td>
                                            <td><?php echo date("H:i", strtotime($row['last_update'])); ?></td>
                                            <td>
                                                <?php 
                                                if($row['status'] == 'pending') echo '<span class="badge bg-warning text-dark">در انتظار تایید</span>';
                                                elseif($row['status'] == 'approved') echo '<span class="badge bg-success">تایید نهایی</span>';
                                                elseif($row['status'] == 'rejected') echo '<span class="badge bg-danger">رد شده</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group shadow-sm">
                                                    <a href="request_details.php?ward_id=<?php echo $row['ward_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="مشاهده جزئیات">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if($row['status'] == 'pending'): ?>
                                                        <button onclick="updateStatus(<?php echo $row['ward_id']; ?>, 'approved')" 
                                                                class="btn btn-sm btn-success" title="تایید">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button onclick="updateStatus(<?php echo $row['ward_id']; ?>, 'rejected')" 
                                                                class="btn btn-sm btn-danger" title="رد درخواست">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-5 text-muted">هیچ درخواستی برای امروز ثبت نشده است.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 برای عملیات تایید/رد -->
<script src="../assets/js/sweetalert2@11.js"></script>
<script>
function updateStatus(wardId, newStatus) {
    let actionText = newStatus === 'approved' ? 'تایید' : 'رد';
    let confirmColor = newStatus === 'approved' ? '#28a745' : '#dc3545';

    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: `قصد ${actionText} آمار این بخش را دارید؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'بله، انجام شود',
        cancelButtonText: 'انصراف'
    }).then((result) => {
        if (result.isConfirmed) {
            // ارسال درخواست به فایل پردازشگر
            window.location.href = `process_action.php?ward_id=${wardId}&status=${newStatus}`;
        }
    });
}

// نمایش پیام‌های بازگشتی
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('msg') === 'updated') {
    Swal.fire('بروزرسانی شد!', 'وضعیت درخواست با موفقیت تغییر یافت.', 'success');
}
</script>

<?php include("../layout/footer.php"); ?>
