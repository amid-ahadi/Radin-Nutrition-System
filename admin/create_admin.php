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

$name = "Admin";
$username = "admin";
$password = password_hash("123456", PASSWORD_DEFAULT);
$role = "admin";

$stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $username, $password, $role);
$stmt->execute();

echo "اکانت ادمین ساخته شد.";
