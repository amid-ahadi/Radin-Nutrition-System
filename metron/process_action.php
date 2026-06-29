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
include_once("../config/database.php");
include("../auth/role_check.php");
allowRoles(['admin', 'metron']);

if(isset($_GET['ward_id']) && isset($_GET['status'])) {
    $ward_id = $_GET['ward_id'];
    $status = $_GET['status'];
    $today = date("Y-m-d");

    // آپدیت وضعیت تمام رکوردهای آن بخش برای امروز
    $stmt = $conn->prepare("UPDATE meal_requests SET status = ? WHERE ward_id = ? AND meal_date = ?");
    $stmt->bind_param("sis", $status, $ward_id, $today);

    if($stmt->execute()) {
        header("Location: view_requests.php?msg=updated");
    } else {
        header("Location: view_requests.php?msg=error");
    }
}
?>
