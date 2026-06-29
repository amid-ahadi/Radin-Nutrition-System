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

if($_POST){

$name = $_POST["food_name"];
$meal = $_POST["meal_type"];

$stmt = $conn->prepare("INSERT INTO foods (food_name, meal_type) VALUES (?,?)");
$stmt->bind_param("ss",$name,$meal);
$stmt->execute();

echo "<div class='alert alert-success'>غذا ثبت شد</div>";
}
?>

<h4>افزودن غذا</h4>

<form method="post">

<input class="form-control mb-3" name="food_name" placeholder="نام غذا">

<select class="form-control mb-3" name="meal_type">
<option value="breakfast">صبحانه</option>
<option value="lunch">ناهار</option>
<option value="dinner">شام</option>
</select>

<button class="btn btn-success">ثبت</button>

</form>

<?php include("../layout/footer.php"); ?>
