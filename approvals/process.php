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

// بررسی دسترسی
allowRoles(["matron", "admin"]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // تبدیل وضعیت‌های متنی به عددی (اگر سیستم شما عددی کار می‌کند)
    // 0: pending, 1: approved, 4: rejected
    $finalStatus = $status;
    if ($status === 'approved') $finalStatus = 1;
    if ($status === 'rejected') $finalStatus = 4;

    // آماده‌سازی کوئری برای آپدیت
    $stmt = $conn->prepare("UPDATE meal_requests SET status = ? WHERE id = ?");
    
    // اگر وضعیت عدد است از "ii" و اگر متن است از "si" استفاده می‌کنیم
    // اینجا فرض می‌کنیم دیتابیس شما عدد می‌پذیرد
    $stmt->bind_param("si", $finalStatus, $id);
    
    if($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "invalid_request";
}
?>
