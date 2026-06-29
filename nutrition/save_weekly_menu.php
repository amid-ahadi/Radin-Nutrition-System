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
    foreach ($_POST['menu'] as $date => $meals) {
        foreach ($meals as $meal_type_id => $food_id) {
            if (!empty($food_id)) {
                $m_id = intval($meal_type_id);
                $f_id = intval($food_id);

                // بررسی برای جلوگیری از ثبت تکراری
                $check = $conn->query("SELECT id FROM daily_menu WHERE serving_date = '$date' AND meal_type_id = $m_id");
                
                if ($check->num_rows == 0) {
                    $conn->query("INSERT INTO daily_menu (serving_date, food_id, meal_type_id, meal_type) VALUES ('$date', $f_id, $m_id, '')");
                } else {
                    $conn->query("UPDATE daily_menu SET food_id = $f_id WHERE serving_date = '$date' AND meal_type_id = $m_id");
                }
            }
        }
    }
    header("Location: weekly_menu.php?msg=success");
} else {
    header("Location: weekly_menu.php?msg=error");
}
