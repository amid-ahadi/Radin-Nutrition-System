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
function logAction($conn,$user_id,$action){

$stmt=$conn->prepare("
INSERT INTO activity_logs (user_id,action)
VALUES (?,?)
");

$stmt->bind_param("is",$user_id,$action);
$stmt->execute();

}
