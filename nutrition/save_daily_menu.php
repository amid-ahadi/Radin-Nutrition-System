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
session_start();

// ۱. بررسی سطح دسترسی (فقط ادمین و مدیر تغذیه)
if (!isset($_SESSION['user_id'])) {
    die("دسترسی غیرمجاز");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ۲. دریافت و ایمن‌سازی داده‌ها
    $serving_date = mysqli_real_escape_string($conn, $_POST['serving_date']);
    $food_id = intval($_POST['food_id']);
    $meal_type_id = intval($_POST['meal_type_id']); // آی‌دی عددی وعده

    if (empty($serving_date) || empty($food_id) || empty($meal_type_id)) {
        header("Location: daily_menu.php?date=$serving_date&msg=empty_fields");
        exit();
    }

    // ۳. منطق ضد تکرار: بررسی وجود رکورد مشابه برای همین تاریخ و همین وعده
    $check_query = "SELECT id FROM daily_menu WHERE serving_date = ? AND meal_type_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("si", $serving_date, $meal_type_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // ۴. اگر قبلاً ثبت شده بود: آن را بروزرسانی کن (Update)
        // این کار جلوی داشتن دو صبحانه در یک روز را می‌گیرد
        $update_query = "UPDATE daily_menu SET food_id = ? WHERE serving_date = ? AND meal_type_id = ?";
        $stmt_upd = $conn->prepare($update_query);
        $stmt_upd->bind_param("isi", $food_id, $serving_date, $meal_type_id);
        
        if ($stmt_upd->execute()) {
            // هدایت به صفحه قبل با پیام موفقیت در ویرایش
            header("Location: daily_menu.php?date=$serving_date&msg=updated");
        } else {
            header("Location: daily_menu.php?date=$serving_date&msg=error");
        }
    } else {
        // ۵. اگر رکوردی یافت نشد: درج جدید انجام بده (Insert)
        $insert_query = "INSERT INTO daily_menu (serving_date, food_id, meal_type_id, status) VALUES (?, ?, ?, 1)";
        $stmt_ins = $conn->prepare($insert_query);
        $stmt_ins->bind_param("sii", $serving_date, $food_id, $meal_type_id);
        
        if ($stmt_ins->execute()) {
            header("Location: daily_menu.php?date=$serving_date&msg=added");
        } else {
            header("Location: daily_menu.php?date=$serving_date&msg=error");
        }
    }
} else {
    header("Location: daily_menu.php");
}
exit();
?>
