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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['menu'])) {
    $menu_data = $_POST['menu']; // آرایه‌ای شامل [تاریخ][وعده_آیدی] => غذا_آیدی

    $conn->begin_transaction(); // شروع تراکنش برای امنیت داده‌ها

    try {
        foreach ($menu_data as $date => $meals) {
            foreach ($meals as $meal_id => $food_id) {
                if (!empty($food_id)) {
                    // استفاده از همان منطق طلایی نینجا (Upsert)
                    $sql = "INSERT INTO daily_menu (serving_date, food_id, meal_type_id, status) 
                            VALUES (?, ?, ?, 1) 
                            ON DUPLICATE KEY UPDATE food_id = VALUES(food_id)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $date, $food_id, $meal_id);
                    $stmt->execute();
                }
            }
        }
        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo "error";
    }
}
?>
