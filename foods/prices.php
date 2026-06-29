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

$foods = $conn->query("SELECT * FROM foods");

if($_POST){

$food_id=$_POST["food_id"];
$price=$_POST["price"];

$stmt=$conn->prepare("
INSERT INTO food_prices (food_id,price)
VALUES (?,?)
");

$stmt->bind_param("ii",$food_id,$price);
$stmt->execute();

echo "<div class='alert alert-success'>قیمت ثبت شد</div>";

}
?>

<h4>ثبت قیمت غذا</h4>

<form method="post">

<select name="food_id" class="form-control mb-3">

<?php while($f=$foods->fetch_assoc()): ?>

<option value="<?=$f["id"]?>"><?=$f["food_name"]?></option>

<?php endwhile; ?>

</select>

<input type="number" name="price" class="form-control mb-3" placeholder="قیمت">

<button class="btn btn-success">ثبت</button>

</form>

<?php include("../layout/footer.php"); ?>
