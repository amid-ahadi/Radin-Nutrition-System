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

allowRoles(['admin', 'nutrition_manager']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- بخش هوشمند: چک کردن و افزودن ستون description اگر وجود نداشت ---
    $checkColumn = $conn->query("SHOW COLUMNS FROM `foods` LIKE 'description'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE foods ADD COLUMN description TEXT NULL AFTER food_name");
    }
    // ---------------------------------------------------------

    $food_name = trim($_POST['food_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $meal_type = $_POST['meal_type'] ?? 'both';

    if (!empty($food_name)) {
        // استفاده از نام ستون‌های دقیق دیتابیس شما (طبق تصویر)
        $stmt = $conn->prepare("INSERT INTO foods (food_name, description, meal_type, is_active) VALUES (?, ?, ?, 1)");
        
        if ($stmt) {
            $stmt->bind_param("sss", $food_name, $description, $meal_type);
            if ($stmt->execute()) {
                header("Location: foods.php?success=1");
                exit();
            } else {
                echo "خطا در ثبت: " . $conn->error;
            }
        } else {
            echo "خطا در آماده‌سازی کوئری: " . $conn->error;
        }
    }
}
?>
