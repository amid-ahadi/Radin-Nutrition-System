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
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $ward_id = $_POST['ward_id'];
    $daily_menu_id = $_POST['daily_menu_id'];
    $count = $_POST['count'];

    // پیدا کردن اطلاعات غذا از روی daily_menu_id
    $menu_info = $conn->query("SELECT food_id, meal_type FROM daily_menu WHERE id = $daily_menu_id")->fetch_assoc();
    $food_id = $menu_info['food_id'];
    $meal_type = $menu_info['meal_type'];

    $sql = "INSERT INTO meal_requests (user_id, ward_id, meal_type, food_id, count, status) 
            VALUES (?, ?, ?, ?, ?, 'pending_matron')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisii", $user_id, $ward_id, $meal_type, $food_id, $count);
    
    if ($stmt->execute()) {
        header("Location: request_meal.php?msg=sent");
    } else {
        echo "خطا در ثبت درخواست";
    }
}
