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
include("../layout/header.php");
include("../config/database.php");

$result = $conn->query("
SELECT meal_requests.*, foods.food_name, wards.ward_name
FROM meal_requests
JOIN foods ON foods.id = meal_requests.food_id
JOIN wards ON wards.id = meal_requests.ward_id
WHERE status = 1
");
?>
