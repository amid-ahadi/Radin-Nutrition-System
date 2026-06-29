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
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../config/database.php");
include("../auth/role_check.php");

// امنیت: فقط ادمین و مدیر تغذیه
allowRoles(['admin', 'nutrition_manager']);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // بررسی اینکه آیا این وعده در لیست غذاها استفاده شده یا نه
    // اگر استفاده شده باشد، بهتر است حذف نشود تا دیتابیس به هم نریزد
    $stmt = $conn->prepare("DELETE FROM meal_types WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: meal_settings.php?msg=deleted");
    } else {
        echo "خطا در حذف: " . $conn->error;
    }
}
?>
