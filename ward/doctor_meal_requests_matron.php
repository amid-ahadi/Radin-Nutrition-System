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

allowRoles(['admin', 'matron']);

function gregorian_to_jalali_simple($gy, $gm, $gd) {
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];

    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }

    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;

    $days = 365 * $gy
        + intdiv($gy2 + 3, 4)
        - intdiv($gy2 + 99, 100)
        + intdiv($gy2 + 399, 400)
        - 80
        + $gd
        + $g_d_m[$gm - 1];

    $jy += 33 * intdiv($days, 12053);
    $days %= 12053;

    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;

    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }

    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }

    return [$jy, $jm, $jd];
}

function show_jalali_date($date) {
    if (!$date || $date === '0000-00-00') {
        return '';
    }

    $parts = explode('-', $date);
    if (count($parts) !== 3) {
        return $date;
    }

    [$gy, $gm, $gd] = array_map('intval', $parts);
    [$jy, $jm, $jd] = gregorian_to_jalali_simple($gy, $gm, $gd);

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_ids'])) {
    $action = $_POST['action'];
    $request_ids = $_POST['request_ids'];
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    if ($action === 'approved' || $action === 'rejected') {
        $conn->query("
            UPDATE doctor_meal_requests
            SET matron_status = '$action',
                matron_approved_by = $user_id,
                matron_approved_at = NOW()
            WHERE id IN ($request_ids)
              AND matron_status = 'pending'
        ");

        if ($action === 'approved') {
            $res = $conn->query("
                SELECT *
                FROM doctor_meal_requests
                WHERE id IN ($request_ids)
            ");

            while ($r = $res->fetch_assoc()) {
                $doctor_id = (int)$r['doctor_id'];
                $meal_type_id = (int)$r['meal_type_id'];
                $meal_date = $r['request_date'];
                $quantity = (int)$r['quantity'];
                $unit_price = (float)$r['unit_price'];
                $total_price = (float)$r['total_price'];

                $check = $conn->query("
                    SELECT id
                    FROM doctor_meals
                    WHERE doctor_id = $doctor_id
                      AND meal_type_id = $meal_type_id
                      AND meal_date = '$meal_date'
                      AND confirmed = 0
                    LIMIT 1
                ");

                if ($check && $check->num_rows > 0) {
                    $old = $check->fetch_assoc();
                    $doctor_meal_id = (int)$old['id'];

                    $conn->query("
                        UPDATE doctor_meals
                        SET quantity = quantity + $quantity,
                            total_price = total_price + $total_price
                        WHERE id = $doctor_meal_id
                    ");
                } else {
                    $conn->query("
                        INSERT INTO doctor_meals
                            (doctor_id, meal_type_id, meal_date, quantity, unit_price, total_price, confirmed, recorded_at)
                        VALUES
                            ($doctor_id, $meal_type_id, '$meal_date', $quantity, $unit_price, $total_price, 0, NOW())
                    ");
                }
            }

            $msg = "<div class='alert alert-success'>درخواست‌ها تایید شدند.</div>";
        } else {
            $msg = "<div class='alert alert-success'>درخواست‌ها رد شدند.</div>";
        }
    }
}

$sql = "
    SELECT
        d.doctor_name,
        r.doctor_id,
        r.request_date,
        GROUP_CONCAT(r.id) AS request_ids,

        SUM(
            CASE
                WHEN m.meal_name LIKE '%صبحانه%'
                  OR m.meal_name LIKE '%صبح%'
                THEN r.quantity
                ELSE 0
            END
        ) AS breakfast,

        SUM(
            CASE
                WHEN m.meal_name LIKE '%نهار%'
                  OR m.meal_name LIKE '%ناهار%'
                  OR m.meal_name LIKE '%ظهر%'
                THEN r.quantity
                ELSE 0
            END
        ) AS lunch,

        SUM(
            CASE
                WHEN m.meal_name LIKE '%شام%'
                  OR m.meal_name LIKE '%شب%'
                THEN r.quantity
                ELSE 0
            END
        ) AS dinner

    FROM doctor_meal_requests r
    JOIN doctors d ON r.doctor_id = d.id
    JOIN meal_types m ON r.meal_type_id = m.id
    WHERE r.matron_status = 'pending'
    GROUP BY r.doctor_id, r.request_date, d.doctor_name
    ORDER BY r.request_date DESC, d.doctor_name ASC
";

$result = $conn->query($sql);

include("../layout/header.php");
?>

<div class="container py-4">

    <h4 class="mb-4">تایید درخواست غذای پزشکان - مترون</h4>

    <?= $msg ?>

    <div class="card shadow">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>پزشک</th>
                            <th>تاریخ</th>
                            <th>صبحانه</th>
                            <th>نهار</th>
                            <th>شام</th>
                            <th>عملکرد</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>

                                    <td>
                                        <?= htmlspecialchars(show_jalali_date($row['request_date'])) ?>
                                    </td>

                                    <td><?= (int)$row['breakfast'] ?></td>
                                    <td><?= (int)$row['lunch'] ?></td>
                                    <td><?= (int)$row['dinner'] ?></td>

                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input
                                                type="hidden"
                                                name="request_ids"
                                                value="<?= htmlspecialchars($row['request_ids']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="action"
                                                value="approved"
                                                class="btn btn-success btn-sm"
                                                onclick="return confirm('درخواست تایید شود؟')"
                                            >
                                                تایید
                                            </button>

                                            <button
                                                type="submit"
                                                name="action"
                                                value="rejected"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('درخواست رد شود؟')"
                                            >
                                                رد
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">درخواستی برای بررسی وجود ندارد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<?php include("../layout/footer.php"); ?>
