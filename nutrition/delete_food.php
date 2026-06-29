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
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include("../config/database.php");
include("../auth/role_check.php");

// فقط ادمین و مدیر تغذیه اجازه حذف دارند
allowRoles(['admin', 'nutrition_manager']);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // امنیت: تبدیل به عدد صحیح

    // [نینجا]: ابتدا بررسی می‌کنیم که آیا این غذا در منوی روزانه استفاده شده یا خیر
    // اگر در جدول daily_menu استفاده شده باشد، نباید حذف شود (برای حفظ تاریخچه)
    $check_stmt = $conn->prepare("SELECT id FROM daily_menu WHERE food_id = ? LIMIT 1");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // اگر غذا در منوهای قبلی استفاده شده، به جای حذف فیزیکی، آن را غیرفعال می‌کنیم
        $stmt = $conn->prepare("UPDATE foods SET is_active = 0 WHERE id = ?");
    } else {
        // اگر هیچ کجا استفاده نشده، حذف کامل
        $stmt = $conn->prepare("DELETE FROM foods WHERE id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: foods.php?msg=deleted");
            exit();
        } else {
            echo "خطا در عملیات: " . $conn->error;
        }
    }
} else {
    header("Location: foods.php");
    exit();
}
?>
