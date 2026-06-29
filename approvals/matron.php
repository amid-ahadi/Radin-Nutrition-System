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

$query = "
    SELECT 
        mr.ward_id,
        w.ward_name,
        mr.meal_date,
        COUNT(*) as total_items,
        SUM(CAST(mr.patient_count AS UNSIGNED)) as total_patients,
        SUM(CAST(mr.staff_count AS UNSIGNED)) as total_staff
    FROM meal_requests mr
    JOIN wards w ON w.id = mr.ward_id
    WHERE mr.status = '0' 
    GROUP BY mr.ward_id, mr.meal_date
    ORDER BY mr.meal_date DESC
";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">کارتابل تاییدات مترون</h5>
            <span class="badge bg-white text-primary">امروز: <?php echo jdate("Y/m/d"); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-center mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>بخش</th>
                            <th>تاریخ درخواست</th>
                            <th>تعداد وعده</th>
                            <th class="text-primary">مجموع بیمار</th>
                            <th class="text-success">مجموع پرسنل</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): 
                            while($row = $result->fetch_assoc()): 
							$timestamp = strtotime($row['meal_date']);
							$sh_date = jdate("Y/m/d", $timestamp);

						?>
                        <tr>
                            <td class="fw-bold"><?php echo $row['ward_name']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $sh_date; ?></span></td>
                            <td><?php echo $row['total_items']; ?></td>
                            <td class="fw-bold text-primary"><?php echo $row['total_patients'] ?: 0; ?></td>
                            <td class="fw-bold text-success"><?php echo $row['total_staff'] ?: 0; ?></td>
                            <td>
                                <div class="btn-group shadow-sm">
                                    <!-- دکمه جزئیات -->
                                    <a href="request_details.php?ward_id=<?php echo $row['ward_id']; ?>&date=<?php echo $row['meal_date']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="مشاهده جزئیات">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- دکمه تایید (1) -->
                                    <button onclick="updateStatus(<?php echo $row['ward_id']; ?>, '<?php echo $row['meal_date']; ?>', 1)" 
                                            class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> تایید
                                    </button>
                                    <!-- دکمه رد (2) -->
                                    <button onclick="updateStatus(<?php echo $row['ward_id']; ?>, '<?php echo $row['meal_date']; ?>', 2)" 
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i> رد
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" class="p-5 text-muted">هیچ درخواستی در انتظار تایید نیست.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/sweetalert2@11.js"></script>
<script>
function updateStatus(wardId, mealDate, statusVal) {
    const actionText = statusVal === 1 ? 'تایید' : 'رد';
    const confirmColor = statusVal === 1 ? '#198754' : '#dc3545';

    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: `قصد ${actionText} این درخواست را دارید.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: 'بله، انجام شود',
        cancelButtonText: 'انصراف'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ward_id', wardId);
            formData.append('meal_date', mealDate);
            formData.append('status', statusVal);

            fetch("process_group.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === 'success') {
                    Swal.fire({ icon: 'success', title: 'انجام شد', timer: 800, showConfirmButton: false })
                    .then(() => location.reload());
                } else {
                    Swal.fire('خطا', data, 'error');
                }
            });
        }
    });
}
</script>
