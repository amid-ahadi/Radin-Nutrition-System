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
session_start();
include("../config/database.php");

$conn->set_charset("utf8");

echo "<pre>";
print_r($_POST);
echo "</pre>";

$username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
$password = isset($_POST["password"]) ? trim($_POST["password"]) : '';

var_dump($username);
var_dump($password);

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<pre>";
print_r($user);
echo "</pre>";

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["role"] = $user["role"];

    die("login ok");
} else {
    die("login failed");
}
