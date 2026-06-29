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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ward_id = $_POST['ward_id'];
    $meal_date = $_POST['meal_date'];
    $status = $_POST['status']; // اینجا عدد 1 یا 2 دریافت می‌شود

    // آپدیت وضعیت از 0 به مقدار جدید (1 یا 2)
    $stmt = $conn->prepare("UPDATE meal_requests SET status = ? WHERE ward_id = ? AND meal_date = ? AND status = '0'");
    $stmt->bind_param("sis", $status, $ward_id, $meal_date);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }
    $stmt->close();
}
?>
