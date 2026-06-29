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
    $name = $_POST['ward_name'];
    $floor = $_POST['floor'];
    $capacity = $_POST['capacity'];

    $stmt = $conn->prepare("INSERT INTO wards (ward_name, floor, capacity) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $name, $floor, $capacity);
    
    if ($stmt->execute()) {
        header("Location: wards.php?msg=success");
    } else {
        echo "خطا در ثبت اطلاعات";
    }
}
