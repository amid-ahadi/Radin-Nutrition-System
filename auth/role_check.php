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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function allowRoles($roles){

if(!isset($_SESSION["role"])){
header("Location: ../auth/login.php");
exit();
}

if(!in_array($_SESSION["role"],$roles)){

echo "<h3>دسترسی غیر مجاز</h3>";
exit();

}

}
