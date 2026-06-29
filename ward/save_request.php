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
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../config/database.php");

$ward_id = $_SESSION['ward_id'] ?? 0;
$created_by = $_SESSION['user_id'] ?? 0;
$today = date("Y-m-d");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['patients'])) {
    
    // اول چک کن ببین امروز قبلاً چیزی ثبت شده که وضعیتش pending یا approved باشه؟
    $check_lock = $conn->query("SELECT status FROM meal_requests WHERE ward_id = '$ward_id' AND meal_date = '$today' AND (status = 'pending' OR status = 'approved') LIMIT 1");
    
    if ($check_lock->num_rows > 0) {
        // اگر قبلاً ثبت شده و منتظر تاییده، اجازه نده و برگردونش به صفحه اصلی
        header("Location: request_meal.php?msg=locked");
        exit();
    }

    // اگر قفل نبود، حالا ثبت کن
    foreach ($_POST['patients'] as $meal_name => $patient_count) {
        $staff_count = intval($_POST['staff'][$meal_name]);
        $patient_count = intval($patient_count);
        $status = 'pending'; 

        $sql = "INSERT INTO meal_requests (ward_id, created_by, meal_date, meal_type, patient_count, staff_count, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                patient_count = VALUES(patient_count), 
                staff_count = VALUES(staff_count), 
                status = VALUES(status), 
                created_by = VALUES(created_by)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissiis", $ward_id, $created_by, $today, $meal_name, $patient_count, $staff_count, $status);
        $stmt->execute();
    }

    header("Location: request_meal.php?msg=success");
    exit();
}
