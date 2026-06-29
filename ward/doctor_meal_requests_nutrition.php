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
include("../config/database.php");
include("../auth/role_check.php");

allowRoles(['admin', 'nutrition_manager']);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_final'], $_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $nutrition_note = trim($_POST['nutrition_note'] ?? '');
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    $conn->begin_transaction();

    try {
        $stmtReq = $conn->prepare("
            SELECT r.*, d.is_free
            FROM doctor_meal_requests r
            LEFT JOIN doctors d ON d.id = r.doctor_id
            WHERE r.id = ?
              AND r.matron_status = 'approved'
              AND r.nutrition_status = 'pending'
              AND r.finalized = 0
            LIMIT 1
        ");
        $stmtReq->bind_param("i", $request_id);
        $stmtReq->execute();
        $request = $stmtReq->get_result()->fetch_assoc();

        if (!$request) {
            throw new Exception("درخواست معتبر برای تایید نهایی پیدا نشد.");
        }

        $doctor_id = (int)$request['doctor_id'];
        $meal_type_id = (int)$request['meal_type_id'];
        $request_date = $request['request_date'];
        $quantity = (int)$request['quantity'];
        $unit_price = (float)$request['unit_price'];
        $total_price = (float)$request['total_price'];

        $stmtCheckDoctorMeal = $conn->prepare("
            SELECT id FROM doctor_meals
            WHERE doctor_id = ? AND meal_type_id = ? AND meal_date = ?
            LIMIT 1
        ");
        $stmtCheckDoctorMeal->bind_param("iis", $doctor_id, $meal_type_id, $request_date);
        $stmtCheckDoctorMeal->execute();
        $existsDoctorMeal = $stmtCheckDoctorMeal->get_result()->fetch_assoc();

        if (!$existsDoctorMeal) {
            $stmtInsertDoctorMeal = $conn->prepare("
                INSERT INTO doctor_meals
                (doctor_id, meal_type_id, meal_date, quantity, unit_price, total_price, confirmed)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmtInsertDoctorMeal->bind_param(
                "iisidd",
                $doctor_id,
                $meal_type_id,
                $request_date,
                $quantity,
                $unit_price,
                $total_price
            );
            $stmtInsertDoctorMeal->execute();
        }

        $doctorConsumerTypeId = 17;

        $stmtDaily = $conn->prepare("
            INSERT INTO daily_statistics
            (stat_date, meal_type_id, consumer_type_id, quantity, unit_price)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = quantity + VALUES(quantity),
                unit_price = VALUES(unit_price)
        ");
        $stmtDaily->bind_param(
            "siiid",
            $request_date,
            $meal_type_id,
            $doctorConsumerTypeId,
            $quantity,
            $unit_price
        );
        $stmtDaily->execute();

        $approvedStatus = 'approved';
        $stmtUpdateReq = $conn->prepare("
            UPDATE doctor_meal_requests
            SET nutrition_status = ?,
                nutrition_note = ?,
                nutrition_approved_by = ?,
                nutrition_approved_at = NOW(),
                finalized = 1,
                finalized_at = NOW()
            WHERE id = ?
        ");
        $stmtUpdateReq->bind_param("ssii", $approvedStatus, $nutrition_note, $user_id, $request_id);
        $stmtUpdateReq->execute();

        $conn->commit();
        $msg = "<div class='alert alert-success'>درخواست نهایی شد و به آمار روزانه منتقل شد.</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger'>خطا: " . $e->getMessage() . "</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_final'], $_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $nutrition_note = trim($_POST['nutrition_note'] ?? '');
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $rejectedStatus = 'rejected';

    $stmt = $conn->prepare("
        UPDATE doctor_meal_requests
        SET nutrition_status = ?,
            nutrition_note = ?,
            nutrition_approved_by = ?,
            nutrition_approved_at = NOW()
        WHERE id = ?
          AND matron_status = 'approved'
          AND nutrition_status = 'pending'
    ");
    $stmt->bind_param("ssii", $rejectedStatus, $nutrition_note, $user_id, $request_id);
    $stmt->execute();

    $msg = "<div class='alert alert-warning'>درخواست رد شد.</div>";
}

$sql = "
    SELECT r.*, d.doctor_name, m.meal_name
    FROM doctor_meal_requests r
    LEFT JOIN doctors d ON d.id = r.doctor_id
    LEFT JOIN meal_types m ON m.id = r.meal_type_id
    WHERE r.matron_status = 'approved'
      AND r.nutrition_status = 'pending'
      AND r.finalized = 0
    ORDER BY r.request_date ASC, r.id DESC
";
$requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

include("../layout/header.php");
?>

<div class="container py-4">
    <?=$msg?>
    <div class="card shadow">
        <div class="card-header bg-success text-white">تایید نهایی درخواست غذای پزشکان</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>پزشک</th>
                        <th>وعده</th>
                        <th>تاریخ</th>
                        <th>تعداد</th>
                        <th>قیمت واحد</th>
                        <th>قیمت کل</th>
                        <th>اقدام</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $row): ?>
                    <tr>
                        <td><?=$row['doctor_name']?></td>
                        <td><?=$row['meal_name']?></td>
                        <td><?=$row['request_date']?></td>
                        <td><?=$row['quantity']?></td>
                        <td><?=number_format($row['unit_price'])?></td>
                        <td><?=number_format($row['total_price'])?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="request_id" value="<?=$row['id']?>">
                                <input type="text" name="nutrition_note" class="form-control form-control-sm" placeholder="توضیح">
                                <button name="approve_final" class="btn btn-success btn-sm">تایید نهایی</button>
                                <button name="reject_final" class="btn btn-danger btn-sm">رد</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-center">موردی برای تایید نهایی وجود ندارد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../layout/footer.php"); ?>
